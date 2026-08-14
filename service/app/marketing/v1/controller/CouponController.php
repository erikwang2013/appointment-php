<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\marketing\v1\controller;

use app\common\BaseController;
use app\model\Coupon;
use app\model\UserCoupon;
use app\model\UserCouponTransfer;
use support\Db;
use support\Redis;
use Webman\Http\Request;

/**
 * 优惠券控制器
 */
class CouponController extends BaseController
{
    /**
     * 获取用户优惠券列表
     * GET /api/marketing/coupons?status=available|used|expired
     */
    public function index(Request $request)
    {
        $userId = $request->user_id;
        $status = $request->input('status', 'available');

        $query = UserCoupon::where('user_id', $userId)->with('coupon');

        if ($status === 'available') {
            $query->where('status', 'available');
        } elseif ($status === 'used') {
            $query->where('status', 'used');
        } elseif ($status === 'expired') {
            $query->where('status', 'expired');
        }

        $userCoupons = $query->orderBy('received_at', 'desc')->get();

        $result = [];
        foreach ($userCoupons as $uc) {
            $result[] = [
                'id' => $uc->id,
                'coupon_id' => $uc->coupon_id,
                'user_id' => $uc->user_id,
                'status' => $uc->status,
                'used_at' => $uc->used_at,
                'received_at' => $uc->received_at,
                'coupon' => $uc->coupon ? [
                    'id' => $uc->coupon->id,
                    'name' => $uc->coupon->name,
                    'type' => $uc->coupon->type,
                    'amount' => $uc->coupon->amount,
                    'min_amount' => $uc->coupon->min_amount,
                    'start_at' => $uc->coupon->start_at,
                    'end_at' => $uc->coupon->end_at,
                ] : null,
            ];
        }

        return $this->success($result);
    }

    /**
     * 领取优惠券
     * POST /api/marketing/coupons/receive
     */
    public function receive(Request $request)
    {
        $userId = $request->user_id;
        $couponId = $this->decodeId($request->input('coupon_id'));

        if (!$couponId) {
            return $this->error('优惠券ID无效');
        }

        $coupon = Coupon::find($couponId);
        if (!$coupon) {
            return $this->error('优惠券不存在', 404);
        }

        if ($coupon->status != 1) {
            return $this->error('优惠券已下架');
        }

        if ($coupon->remain_qty <= 0) {
            return $this->error('优惠券已被领完');
        }

        // 检查是否已领取
        $exists = UserCoupon::where('user_id', $userId)
            ->where('coupon_id', $couponId)
            ->exists();
        if ($exists) {
            return $this->error('您已领取过该优惠券');
        }

        Db::beginTransaction();
        try {
            // 扣减库存
            $coupon->decrement('remain_qty');

            // 创建用户优惠券
            $userCoupon = new UserCoupon();
            $userCoupon->id = UserCoupon::generateId();
            $userCoupon->user_id = $userId;
            $userCoupon->coupon_id = $couponId;
            $userCoupon->status = 'available';
            $userCoupon->received_at = date('Y-m-d H:i:s');
            $userCoupon->save();

            Db::commit();

            return $this->success([
                'id' => $userCoupon->id,
                'coupon_id' => $userCoupon->coupon_id,
                'status' => $userCoupon->status,
            ], '领取成功');
        } catch (\Illuminate\Database\QueryException $e) {
            Db::rollBack();
            // uk_user_coupon(user_id, coupon_id) 唯一键冲突：并发重复领取，幂等返回已领取
            if (($e->errorInfo[1] ?? null) === 1062) {
                return $this->error('您已领取过该优惠券');
            }
            return $this->error('领取失败，请稍后重试');
        } catch (\Throwable $e) {
            Db::rollBack();
            return $this->error('领取失败，请稍后重试');
        }
    }

    /**
     * 转赠优惠券：生成一次性转赠码（7 天有效）
     * POST /api/marketing/coupons/transfer  body: {user_coupon_id}
     *
     * 规则：券属于本人、available 未使用、券定义未过期、未被转赠过；
     * uk_user_coupon 唯一索引兜底并发重复转赠。
     */
    public function transfer(Request $request)
    {
        $userId = $request->user_id;
        $userCouponId = $this->decodeId($request->input('user_coupon_id'));

        if (!$userCouponId) {
            return $this->error('优惠券ID无效', 422);
        }

        $uc = UserCoupon::find($userCouponId);
        if (!$uc) {
            return $this->error('优惠券不存在', 404);
        }
        if ((string) $uc->user_id !== (string) $userId) {
            return $this->error('只能转赠自己持有的优惠券', 422);
        }
        if ($uc->status !== 'available') {
            return $this->error('优惠券已使用或已过期，无法转赠', 422);
        }
        // 券有效期以券定义 end_at 为准
        $coupon = Coupon::find($uc->coupon_id);
        if ($coupon && $coupon->end_at && strtotime((string) $coupon->end_at) < time()) {
            return $this->error('优惠券已过期，无法转赠', 422);
        }
        if (UserCouponTransfer::where('user_coupon_id', $userCouponId)->exists()) {
            return $this->error('该优惠券已转赠过，不能重复转赠', 422);
        }

        $transfer = new UserCouponTransfer();
        $transfer->id = UserCouponTransfer::generateId();
        $transfer->user_coupon_id = $userCouponId;
        $transfer->coupon_id = $uc->coupon_id;
        $transfer->from_user_id = $userId;
        $transfer->code = $this->generateTransferCode();
        $transfer->status = 'pending';
        $transfer->expire_at = date('Y-m-d H:i:s', time() + 7 * 86400);
        try {
            $transfer->save();
        } catch (\Illuminate\Database\QueryException $e) {
            // uk_code / uk_user_coupon 唯一键冲突：并发下已存在转赠记录
            if (($e->errorInfo[1] ?? null) === 1062) {
                return $this->error('该优惠券已转赠过，不能重复转赠', 422);
            }
            return $this->error('转赠失败，请稍后重试');
        }

        return $this->success([
            'code' => $transfer->code,
            'expire_at' => $transfer->expire_at,
        ], '转赠码生成成功');
    }

    /**
     * 领取转赠优惠券（转赠码一次性，Redis NX 锁防并发双花）
     * POST /api/marketing/coupons/claim  body: {code}
     *
     * 事务：原券置 used + 生成新券绑定接收人（有效期随券定义不变）+ 记录置 claimed。
     * 过期懒判定：claim 时发现 pending 已过期 → 置 expired 并恢复原券 available。
     */
    public function claim(Request $request)
    {
        $userId = $request->user_id;
        $code = trim((string) $request->input('code', ''));

        if ($code === '') {
            return $this->error('转赠码不能为空', 422);
        }

        $transfer = UserCouponTransfer::where('code', $code)->first();
        if (!$transfer) {
            return $this->error('转赠码无效', 404);
        }
        if ($transfer->status === 'claimed') {
            return $this->error('该转赠码已被领取', 422);
        }
        if ((string) $transfer->from_user_id === (string) $userId) {
            return $this->error('不能领取自己转赠的优惠券', 422);
        }

        // 并发防双花：同一转赠码 30 秒内仅一个请求进入领取事务
        $lockKey = 'coupon_transfer_claim:' . $code;
        if (!Redis::connection()->set($lockKey, (string) $userId, 'EX', 30, 'NX')) {
            return $this->error('领取过于频繁，请稍后重试', 422);
        }
        try {
            Db::beginTransaction();

            $transfer = UserCouponTransfer::where('code', $code)->lockForUpdate()->first();
            if (!$transfer) {
                Db::rollBack();
                return $this->error('转赠码无效', 404);
            }
            if ($transfer->status === 'claimed') {
                Db::rollBack();
                return $this->error('该转赠码已被领取', 422);
            }
            if ($transfer->status === 'expired') {
                Db::rollBack();
                return $this->error('该转赠码已过期', 422);
            }
            if ((string) $transfer->from_user_id === (string) $userId) {
                Db::rollBack();
                return $this->error('不能领取自己转赠的优惠券', 422);
            }

            // 过期懒判定：置 expired 并恢复原券 available
            if (strtotime((string) $transfer->expire_at) < time()) {
                $transfer->status = 'expired';
                $transfer->save();
                UserCoupon::where('id', $transfer->user_coupon_id)
                    ->update(['status' => 'available', 'used_at' => null]);
                Db::commit();
                return $this->error('转赠码已过期', 422);
            }

            $original = UserCoupon::where('id', $transfer->user_coupon_id)->lockForUpdate()->first();
            if (!$original || $original->status !== 'available') {
                Db::rollBack();
                return $this->error('原优惠券不可用，无法领取', 422);
            }

            // 转赠即消耗原券
            $original->status = 'used';
            $original->used_at = date('Y-m-d H:i:s');
            $original->save();

            // 生成新券绑定接收人：字段从原券复制（coupon_id 不变即有效期不变）
            $newUc = new UserCoupon();
            $newUc->id = UserCoupon::generateId();
            $newUc->user_id = $userId;
            $newUc->coupon_id = $original->coupon_id;
            $newUc->status = 'available';
            $newUc->received_at = date('Y-m-d H:i:s');
            $newUc->save();

            $transfer->status = 'claimed';
            $transfer->to_user_id = $userId;
            $transfer->claimed_at = date('Y-m-d H:i:s');
            $transfer->save();

            Db::commit();

            return $this->success([
                'id' => $newUc->id,
                'coupon_id' => $newUc->coupon_id,
                'status' => $newUc->status,
            ], '领取成功');
        } catch (\Throwable $e) {
            Db::rollBack();
            return $this->error('领取失败，请稍后重试');
        } finally {
            Redis::connection()->del($lockKey);
        }
    }

    /**
     * 我的转赠记录：发出（pending/claimed/expired）+ 收到（claimed），分页
     * GET /api/marketing/coupons/transfers
     */
    public function transfers(Request $request)
    {
        $userId = $request->user_id;

        $query = UserCouponTransfer::where('from_user_id', $userId)
            ->orWhere(function ($q) use ($userId) {
                $q->where('to_user_id', $userId)->where('status', 'claimed');
            })
            ->orderBy('created_at', 'desc');

        return $this->paginate($query->paginate(15));
    }

    /**
     * 生成 8 位唯一转赠码（去易混淆字符，uk_code 唯一索引兜底）
     */
    private function generateTransferCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
        } while (UserCouponTransfer::where('code', $code)->exists());
        return $code;
    }
}
