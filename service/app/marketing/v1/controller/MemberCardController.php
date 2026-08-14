<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\marketing\v1\controller;

use app\common\BaseController;
use app\model\MemberCard;
use app\model\User;
use app\model\UserMemberCard;
use app\model\UserWallet;
use app\model\WalletTxn;
use support\Db;
use support\Redis;
use Webman\Http\Request;

/**
 * 会员卡（等级卡）控制器：月卡/vip 卡购买（余额支付）+ 我的列表
 */
class MemberCardController extends BaseController
{
    /**
     * 可购买会员卡列表（不含次卡）
     * GET /api/marketing/member-cards
     */
    public function index(Request $request)
    {
        $cards = MemberCard::where('status', 1)
            ->where('type', '!=', 'times')
            ->orderBy('price', 'asc')
            ->get();

        return $this->success($cards);
    }

    /**
     * 购买会员卡（余额支付，购买后升级 member_level）
     * POST /api/marketing/member-cards/buy
     *
     * 幂等：Redis NX 锁 member_card_buy:{user_id}:{card_id}（30s TTL）；
     * 事务内：钱包行 lockForUpdate → 余额充足校验（toCents 精确比较）
     * → 扣减 + total_consume 累加 → 写流水(consume) → 创建 UserMemberCard
     * → month/vip 卡联动 user.member_level。
     */
    public function buy(Request $request)
    {
        $userId = $request->user_id;
        $cardId = $this->decodeId($request->input('card_id'));

        if (!$cardId) {
            return $this->error('会员卡ID无效');
        }

        $card = MemberCard::find($cardId);
        if (!$card) {
            return $this->error('会员卡不存在', 404);
        }
        if ((int) $card->status !== 1) {
            return $this->error('会员卡已下架');
        }
        if ($card->type === 'times') {
            return $this->error('次卡请从次卡频道购买');
        }

        // 已拥有未过期同卡：拒绝重复购买
        $exists = UserMemberCard::where('user_id', $userId)
            ->where('card_id', $cardId)
            ->where('status', 'active')
            ->first();
        if ($exists && (!$exists->end_at || strtotime((string) $exists->end_at) > time())) {
            return $this->error('您已拥有该会员卡', 422);
        }

        $lockKey = "member_card_buy:{$userId}:{$cardId}";
        if (!Redis::connection()->set($lockKey, (string) $userId, 'EX', 30, 'NX')) {
            return $this->error('请勿重复提交');
        }

        $price = (float) $card->price;
        $now = date('Y-m-d H:i:s');

        try {
            Db::beginTransaction();

            // 钱包行锁（不存在则创建；并发首购由 uk_user_id 唯一约束兜底，冲突整体回滚）
            $wallet = UserWallet::where('user_id', $userId)->lockForUpdate()->first();
            if (!$wallet) {
                $wallet = UserWallet::create([
                    'user_id'        => $userId,
                    'balance'        => 0.00,
                    'total_recharge' => 0.00,
                    'total_consume'  => 0.00,
                ]);
            }

            if (UserWallet::toCents((float) $wallet->balance) < UserWallet::toCents($price)) {
                Db::rollBack();
                return $this->error('余额不足');
            }

            $wallet->balance = round((float) $wallet->balance - $price, 2);
            $wallet->total_consume = round((float) $wallet->total_consume + $price, 2);
            $wallet->save();

            WalletTxn::create([
                'user_id'       => $userId,
                'type'          => WalletTxn::TYPE_CONSUME,
                'amount'        => $price,
                'balance_after' => (float) $wallet->balance,
                'remark'        => '购买会员卡 ' . $card->name,
            ]);

            $userCard = new UserMemberCard();
            $userCard->id = UserMemberCard::generateId();
            $userCard->user_id = $userId;
            $userCard->card_id = $cardId;
            $userCard->start_at = $now;
            if (!empty($card->duration_days) && (int) $card->duration_days > 0) {
                $userCard->end_at = date('Y-m-d H:i:s', strtotime("+{$card->duration_days} days"));
            }
            $userCard->total_times = 0;
            $userCard->used_times = 0;
            $userCard->status = 'active';
            $userCard->save();

            // 会员等级联动（month/vip 卡升级等级）
            if (in_array($card->type, ['month', 'vip'], true)) {
                User::where('id', $userId)->update(['member_level' => $card->type]);
            }

            Db::commit();

            return $this->success([
                'id' => (string) $userCard->id,
                'card_id' => (string) $userCard->card_id,
                'name' => $card->name,
                'start_at' => $userCard->start_at,
                'end_at' => $userCard->end_at,
                'status' => $userCard->status,
            ], '购买成功');
        } catch (\Throwable $e) {
            Db::rollBack();
            return $this->error('购买失败，请稍后重试');
        } finally {
            Redis::del($lockKey);
        }
    }

    /**
     * 我的会员卡列表
     * GET /api/marketing/member-cards/my
     */
    public function my(Request $request)
    {
        $userId = $request->user_id;

        $list = UserMemberCard::where('user_id', $userId)
            ->with('card')
            ->orderBy('created_at', 'desc')
            ->get();

        $now = time();
        $result = [];
        foreach ($list as $uc) {
            $status = $uc->status;
            if ($status === 'active' && $uc->end_at && strtotime((string) $uc->end_at) < $now) {
                $status = 'expired';
            }
            $result[] = [
                'id' => (string) $uc->id,
                'card_id' => (string) $uc->card_id,
                'name' => $uc->card?->name ?? '',
                'type' => $uc->card?->type ?? '',
                'price' => (float) ($uc->card?->price ?? 0),
                'start_at' => (string) $uc->start_at,
                'end_at' => $uc->end_at ? (string) $uc->end_at : null,
                'status' => $status,
            ];
        }

        return $this->success($result);
    }
}
