<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\common;

use app\model\Order;
use app\model\ReferralLevel2Reward;
use app\model\UserReferral;
use app\model\UserWallet;
use app\model\WalletTxn;
use support\Db;
use support\Log;

/**
 * 分销返佣服务
 *
 * 被推荐人首单完成（订单进入 completed）时，给推荐人钱包发放返佣，
 * 金额 = 该单 paid_amount × 返佣比例（appointment_system_config group=referral key=reward_rate，默认 0.05）。
 *
 * 幂等：appointment_user_referral 行锁 lockForUpdate + rewarded_at 判空，同一推荐记录只发一次；
 * 仅首单：锁内复查该被推荐人已无其他已完成订单。
 *
 * 注意：本服务不管理事务，必须在调用方事务内调用（钱包行锁依赖事务）。
 */
class ReferralRewardService
{
    private const CONFIG_GROUP       = 'referral';
    private const CONFIG_KEY_RATE    = 'reward_rate';
    private const CONFIG_KEY_LEVEL2  = 'level2_rate';
    private const DEFAULT_RATE       = 0.05;
    private const DEFAULT_LEVEL2_RATE = 0.02;

    /** 返佣入账类型（WalletTxn type） */
    public const TYPE_REFERRAL_REWARD = 'referral_reward';
    public const TYPE_REFERRAL_LEVEL2 = 'referral_level2';

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

        self::creditWallet((string) $referral->referrer_id, $amount, (string) $order->order_no, self::TYPE_REFERRAL_REWARD);

        $now = date('Y-m-d H:i:s');
        $referral->reward_type    = 'balance';
        $referral->reward_amount  = (string) $amount;
        $referral->rewarded_at    = $now;
        $referral->first_order_at = $now;
        $referral->save();

        // 二级返佣：给一级推荐人的推荐人（上上级）发放，失败不阻塞一级发放
        self::payLevel2Reward($order, (string) $referral->referrer_id, $paidAmount);
    }

    /**
     * 二级返佣：查一级推荐人自己的推荐人（appointment_user_referral 中 referred_user_id = 一级推荐人 id），
     * 存在且非同一人时发放 paid_amount × level2_rate。幂等由
     * appointment_referral_level2_reward 唯一键 (order_id, referred_user_id) + 行锁复验保证。
     * 仅在被推荐人首单（一级返佣本次发放成功）时到达，失败仅告警不阻塞一级发放。
     */
    private static function payLevel2Reward(Order $order, string $level1ReferrerId, float $paidAmount): void
    {
        try {
            $level2 = UserReferral::where('referred_user_id', $level1ReferrerId)->first();
            if (!$level2 || empty($level2->referrer_id)) {
                return; // 一级推荐人无上上级 → 不发放
            }

            $amount = round($paidAmount * self::getLevel2Rate(), 2);
            if ($amount <= 0) {
                return;
            }

            // 行锁复验：并发完成同一被推荐人多笔首单候选时串行化
            $level2 = UserReferral::where('id', $level2->id)->lockForUpdate()->first();
            if (!$level2 || empty($level2->referrer_id)) {
                return;
            }

            $level2ReferrerId = (string) $level2->referrer_id;
            if ($level2ReferrerId === $level1ReferrerId) {
                return; // 防死循环
            }

            // 幂等：该订单该被推荐人已发过 → 跳过（唯一键兜底）
            $exists = ReferralLevel2Reward::where('order_id', (string) $order->id)
                ->where('referred_user_id', (string) $order->user_id)
                ->exists();
            if ($exists) {
                return;
            }

            self::creditWallet($level2ReferrerId, $amount, (string) $order->order_no, self::TYPE_REFERRAL_LEVEL2);

            ReferralLevel2Reward::create([
                'id'               => ReferralLevel2Reward::generateId(),
                'order_id'         => (string) $order->id,
                'referred_user_id' => (string) $order->user_id,
                'referrer_id'      => $level2ReferrerId,
                'amount'           => $amount,
                'status'           => 1,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[ReferralRewardService] level2 reward failed', [
                'order_id' => (string) $order->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    /**
     * 推荐人钱包入账（必须在事务内调用，钱包行 lockForUpdate）
     */
    private static function creditWallet(string $userId, float $amount, string $orderNo, string $type = self::TYPE_REFERRAL_REWARD): void
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
            'type'          => $type,
            'amount'        => $amount,
            'balance_after' => (float) $wallet->balance,
            'remark'        => ($type === self::TYPE_REFERRAL_LEVEL2 ? '二级返佣' : '推荐返佣') . ' 订单 ' . $orderNo,
        ]);
    }

    /**
     * 返佣比例：appointment_system_config (group=referral, key=reward_rate)，缺省 0.05
     */
    public static function getRewardRate(): float
    {
        return self::getConfigRate(self::CONFIG_KEY_RATE, self::DEFAULT_RATE);
    }

    /**
     * 二级返佣比例：appointment_system_config (group=referral, key=level2_rate)，缺省 0.02
     */
    public static function getLevel2Rate(): float
    {
        return self::getConfigRate(self::CONFIG_KEY_LEVEL2, self::DEFAULT_LEVEL2_RATE);
    }

    private static function getConfigRate(string $key, float $default): float
    {
        try {
            $rate = (float) Db::table('appointment_system_config')
                ->where('group', self::CONFIG_GROUP)
                ->where('key', $key)
                ->value('value');
        } catch (\Throwable) {
            $rate = 0.0;
        }

        if ($rate <= 0 || $rate > 1) {
            return $default;
        }
        return $rate;
    }
}
