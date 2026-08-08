<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\marketing\v1\controller;

use app\common\BaseController;
use app\model\Coupon;
use app\model\UserCoupon;
use support\Db;
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
        } catch (\Throwable $e) {
            Db::rollBack();
            return $this->error('领取失败，请稍后重试');
        }
    }
}
