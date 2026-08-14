<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\common\ReferralRewardService;
use app\model\Order;
use app\model\ReferralLevel2Reward;
use app\model\User;
use app\model\UserReferral;
use app\model\UserWallet;
use app\model\WalletTxn;
use support\Db;

/**
 * 多级分销二级返佣测试
 *
 * 覆盖：一级+二级同发、仅一级无上上级、无推荐链、重复核销幂等、
 * level2_rate 配置非法回落默认、发放记录落库。
 * 策略：真实库 + tearDown 清理；金额 = paid_amount × rate（0.05 / 0.02）。
 */
class MultilevelTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理 */
    private array $userIds = [];

    /** @var int[] 用例订单 ID */
    private array $orderIds = [];

    /** @var string[] 用例推荐记录 ID */
    private array $referralIds = [];

    /** @var string[] 用例二级返佣记录 ID */
    private array $level2RewardIds = [];

    /** 用例是否改过 level2_rate 配置（tearDown 恢复） */
    private bool $configChanged = false;

    protected function tearDown(): void
    {
        if ($this->orderIds) {
            ReferralLevel2Reward::whereIn('order_id', $this->orderIds)->delete();
            WalletTxn::whereIn('order_id', $this->orderIds)->delete();
            Order::whereIn('id', $this->orderIds)->delete();
        }
        if ($this->level2RewardIds) {
            ReferralLevel2Reward::whereIn('id', $this->level2RewardIds)->delete();
        }
        foreach ($this->userIds as $uid) {
            UserWallet::where('user_id', $uid)->delete();
            WalletTxn::where('user_id', $uid)->delete();
            UserReferral::where('referrer_id', $uid)
                ->orWhere('referred_user_id', $uid)
                ->delete();
            User::where('id', $uid)->delete();
        }
        if ($this->configChanged) {
            Db::table('erik_system_config')
                ->where('group', 'referral')
                ->where('key', 'level2_rate')
                ->update(['value' => '0.02']);
        }
        $this->userIds = $this->orderIds = $this->referralIds = $this->level2RewardIds = [];
        $this->configChanged = false;
    }

    /** 建用户（固定 id 防 snowflake 同毫秒冲突），返回 id */
    private function newUser(string $prefix, string $nickname): string
    {
        $user = new User();
        $user->id = $prefix . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        $user->phone = '18' . substr(uniqid(), -9);
        $user->nickname = $nickname;
        $user->password = '';
        $user->status = 1;
        $user->user_type = 'user';
        $user->save();
        $this->userIds[] = (string) $user->id;
        return (string) $user->id;
    }

    /** 建推荐记录（B 推荐 C；A 推荐 B），返回记录 id */
    private function newReferral(string $referrerId, string $referredId): string
    {
        $now = date('Y-m-d H:i:s');
        $referral = new UserReferral();
        $referral->id = '9800' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        $referral->referrer_id = $referrerId;
        $referral->referred_user_id = $referredId;
        $referral->registered_at = $now;
        $referral->save();
        $this->referralIds[] = (string) $referral->id;
        return (string) $referral->id;
    }

    /** 建已完成订单，返回 Order 模型 */
    private function newCompletedOrder(string $userId, float $paidAmount): Order
    {
        $order = Order::create([
            'order_no'        => 'ORD_L2_' . uniqid(),
            'user_id'         => $userId,
            'technician_id'   => (string) (9900000000000000 + random_int(1, 999999)),
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => $paidAmount,
            'discount_amount' => 0.0,
            'paid_amount'     => $paidAmount,
            'status'          => Order::STATUS_COMPLETED,
        ]);
        $this->orderIds[] = (int) $order->id;
        return $order;
    }

    private function walletBalance(string $userId): float
    {
        return (float) (UserWallet::where('user_id', $userId)->value('balance') ?? 0);
    }

    private function txnCount(string $userId, string $type): int
    {
        return (int) WalletTxn::where('user_id', $userId)->where('type', $type)->count();
    }

    private function level2Count(string $orderId): int
    {
        return (int) ReferralLevel2Reward::where('order_id', (string) $orderId)->count();
    }

    #[Test] public function pays_level1_and_level2_together(): void
    {
        $a = $this->newUser('970000001', '上上级A');
        $b = $this->newUser('970000002', '一级B');
        $c = $this->newUser('970000003', '被推荐C');
        $this->newReferral($a, $b);
        $this->newReferral($b, $c);

        $order = $this->newCompletedOrder($c, 100.0);
        ReferralRewardService::handleOrderCompleted($order);

        // 一级：100 × 0.05 = 5.00
        $this->assertSame(5.0, $this->walletBalance($b));
        $this->assertSame(1, $this->txnCount($b, WalletTxn::TYPE_REFERRAL_REWARD));
        // 二级：100 × 0.02 = 2.00
        $this->assertSame(2.0, $this->walletBalance($a));
        $this->assertSame(1, $this->txnCount($a, WalletTxn::TYPE_REFERRAL_LEVEL2));

        $reward = ReferralLevel2Reward::where('order_id', (string) $order->id)->first();
        $this->assertNotNull($reward);
        $this->assertSame($a, (string) $reward->referrer_id);
        $this->assertSame($c, (string) $reward->referred_user_id);
        $this->assertSame(2.0, (float) $reward->amount);
        $this->level2RewardIds[] = (string) $reward->id;
    }

    #[Test] public function pays_level1_only_when_no_upper_level(): void
    {
        $b = $this->newUser('970000004', '一级B');
        $c = $this->newUser('970000005', '被推荐C');
        $this->newReferral($b, $c);

        $order = $this->newCompletedOrder($c, 100.0);
        ReferralRewardService::handleOrderCompleted($order);

        $this->assertSame(5.0, $this->walletBalance($b));
        $this->assertSame(0, $this->level2Count((string) $order->id));
    }

    #[Test] public function pays_nothing_without_referral_chain(): void
    {
        $c = $this->newUser('970000006', '无推荐C');

        $order = $this->newCompletedOrder($c, 100.0);
        ReferralRewardService::handleOrderCompleted($order);

        $this->assertSame(0.0, $this->walletBalance($c));
        $this->assertSame(0, $this->level2Count((string) $order->id));
        $this->assertSame(0, WalletTxn::where('user_id', $c)->count());
    }

    #[Test] public function level2_paid_once_on_repeated_completion(): void
    {
        $a = $this->newUser('970000007', '上上级A');
        $b = $this->newUser('970000008', '一级B');
        $c = $this->newUser('970000009', '被推荐C');
        $this->newReferral($a, $b);
        $this->newReferral($b, $c);

        $order = $this->newCompletedOrder($c, 100.0);
        ReferralRewardService::handleOrderCompleted($order);
        ReferralRewardService::handleOrderCompleted($order);

        $this->assertSame(5.0, $this->walletBalance($b));
        $this->assertSame(2.0, $this->walletBalance($a));
        $this->assertSame(1, $this->txnCount($a, WalletTxn::TYPE_REFERRAL_LEVEL2));
        $this->assertSame(1, $this->level2Count((string) $order->id));
    }

    #[Test] public function level2_rate_invalid_falls_back_to_default(): void
    {
        Db::table('erik_system_config')
            ->where('group', 'referral')
            ->where('key', 'level2_rate')
            ->update(['value' => '0']);
        $this->configChanged = true;

        $a = $this->newUser('970000010', '上上级A');
        $b = $this->newUser('970000011', '一级B');
        $c = $this->newUser('970000012', '被推荐C');
        $this->newReferral($a, $b);
        $this->newReferral($b, $c);

        $order = $this->newCompletedOrder($c, 100.0);
        ReferralRewardService::handleOrderCompleted($order);

        // 非法值回落默认 0.02 → 2.00
        $this->assertSame(2.0, $this->walletBalance($a));
        $this->assertSame(1, $this->txnCount($a, WalletTxn::TYPE_REFERRAL_LEVEL2));
    }

    #[Test] public function level2_rate_configurable(): void
    {
        Db::table('erik_system_config')
            ->where('group', 'referral')
            ->where('key', 'level2_rate')
            ->update(['value' => '0.03']);
        $this->configChanged = true;

        $a = $this->newUser('970000013', '上上级A');
        $b = $this->newUser('970000014', '一级B');
        $c = $this->newUser('970000015', '被推荐C');
        $this->newReferral($a, $b);
        $this->newReferral($b, $c);

        $order = $this->newCompletedOrder($c, 100.0);
        ReferralRewardService::handleOrderCompleted($order);

        $this->assertSame(3.0, $this->walletBalance($a));
        $this->assertSame(1, $this->txnCount($a, WalletTxn::TYPE_REFERRAL_LEVEL2));
    }
}
