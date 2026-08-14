<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\common;

use app\model\Order;
use app\model\UserReferral;
use app\model\UserWallet;
use app\model\WalletTxn;
use support\Db;

/**
 * 分销返佣服务
 *
 * 被推荐人首单完成（订单进入 completed）时，给推荐人钱包发放返佣，
 * 金额 = 该单 paid_amount × 返佣比例（erik_system_config group=referral key=reward_rate，默认 0.05）。
 *
 * 幂等：erik_user_referral 行锁 lockForUpdate + rewarded_at 判空，同一推荐记录只发一次；
 * 仅首单：锁内复查该被推荐人已无其他已完成订单。
 *
 * 注意：本服务不管理事务，必须在调用方事务内调用（钱包行锁依赖事务）。
 */
class ReferralRewardService
{
    private const CONFIG_GROUP    = 'referral';
    private const CONFIG_KEY_RATE = 'reward_rate';
    private const DEFAULT_RATE    = 0.05;

    /** 返佣入账类型（WalletTxn type） */
    public const TYPE_REFERRAL_REWARD = 'referral_reward';

    /**
     * 订单完成回调：发放推荐人返佣（幂等；需在事务内调用）
     */
    public static function handleOrderCompleted(Order $order): void
    {
        if ((string) $order->status !== Order::STATUS_COMPLETED) {
            return;
        }

        $referredUserId = (string) $order->user_id;
        $paidAmount = (float) $order->paid_amount;
        if ($paidAmount <= 0) {
            return;
        }

        $referral = UserReferral::where('referred_user_id', $referredUserId)->first();
        if (!$referral || empty($referral->referrer_id)) {
            return;
        }

        $amount = round($paidAmount * self::getRewardRate(), 2);
        if ($amount <= 0) {
            return;
        }

        // 行锁 + 锁内复验：并发完成同一被推荐人多笔订单时串行化
        $referral = UserReferral::where('id', $referral->id)->lockForUpdate()->first();
        if (!$referral) {
            return;
        }

        // 幂等：已发放过不再发
        if ($referral->rewarded_at) {
            return;
        }

        // 仅首单：该被推荐人已存在其他已完成订单 → 跳过
        $hasOtherCompleted = Order::where('user_id', $referredUserId)
            ->where('status', Order::STATUS_COMPLETED)
            ->where('id', '<>', $order->id)
            ->exists();
        if ($hasOtherCompleted) {
            return;
        }

        self::creditWallet((string) $referral->referrer_id, $amount, (string) $order->order_no);

        $now = date('Y-m-d H:i:s');
        $referral->reward_type    = 'balance';
        $referral->reward_amount  = (string) $amount;
        $referral->rewarded_at    = $now;
        $referral->first_order_at = $now;
        $referral->save();
    }

    /**
     * 推荐人钱包入账（必须在事务内调用，钱包行 lockForUpdate）
     */
    private static function creditWallet(string $userId, float $amount, string $orderNo): void
    {
        $wallet = UserWallet::where('user_id', $userId)->lockForUpdate()->first();
        if (!$wallet) {
            $wallet = UserWallet::create([
                'user_id'        => $userId,
                'balance'        => 0.00,
                'total_recharge' => 0.00,
                'total_consume'  => 0.00,
            ]);
        }

        $wallet->balance = round((float) $wallet->balance + $amount, 2);
        $wallet->save();

        WalletTxn::create([
            'user_id'       => $userId,
            'type'          => self::TYPE_REFERRAL_REWARD,
            'amount'        => $amount,
            'balance_after' => (float) $wallet->balance,
            'remark'        => '推荐返佣 订单 ' . $orderNo,
        ]);
    }

    /**
     * 返佣比例：erik_system_config (group=referral, key=reward_rate)，缺省 0.05
     */
    public static function getRewardRate(): float
    {
        try {
            $rate = (float) Db::table('erik_system_config')
                ->where('group', self::CONFIG_GROUP)
                ->where('key', self::CONFIG_KEY_RATE)
                ->value('value');
        } catch (\Throwable) {
            $rate = 0.0;
        }

        if ($rate <= 0 || $rate > 1) {
            return self::DEFAULT_RATE;
        }
        return $rate;
    }
}
