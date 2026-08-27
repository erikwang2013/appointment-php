<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\common\ReferralRewardService;
use app\model\Order;
use app\model\User;
use app\model\UserReferral;
use app\model\UserWallet;
use app\model\WalletTxn;
use app\user\v1\controller\ReferralController;
use support\Db;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 分销返佣闭环测试
 *
 * 覆盖：首单完成发放、非首单不发、重复调用幂等、未完成不发、
 * 金额 = paid_amount × 比例、返佣明细接口。
 * 基建与 GiftCardRedeemTest 一致（真实 DB + tearDown 清理）。
 */
class ReferralRewardTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理 */
    private array $userIds = [];

    /** @var string[] 用例订单 ID，tearDown 统一清理 */
    private array $orderIds = [];

    /** @var string[] 用例推荐记录 ID，tearDown 统一清理 */
    private array $referralIds = [];

    /** 测试期间被覆盖的返佣比例原值，tearDown 恢复 */
    private ?string $priorRate = null;

    private bool $rateChanged = false;

    protected function tearDown(): void
    {
        if ($this->rateChanged) {
            if ($this->priorRate === null) {
                Db::table('appointment_system_config')->where('group', 'referral')->where('key', 'reward_rate')->delete();
            } else {
                Db::table('appointment_system_config')->where('group', 'referral')->where('key', 'reward_rate')->update([
                    'value' => $this->priorRate,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        if ($this->referralIds) {
            UserReferral::whereIn('id', $this->referralIds)->delete();
        }
        if ($this->orderIds) {
            Order::whereIn('id', $this->orderIds)->delete();
        }
        foreach ($this->userIds as $uid) {
            WalletTxn::where('user_id', $uid)->delete();
            UserWallet::where('user_id', $uid)->delete();
            User::where('id', $uid)->delete();
        }
        $this->userIds = [];
        $this->orderIds = [];
        $this->referralIds = [];
        $this->rateChanged = false;
        $this->priorRate = null;
    }

    /** 覆盖 appointment_system_config 返佣比例（tearDown 恢复原值） */
    private function setRate(string $rate): void
    {
        if (!$this->rateChanged) {
            $this->priorRate = Db::table('appointment_system_config')
                ->where('group', 'referral')->where('key', 'reward_rate')
                ->value('value');
            $this->rateChanged = true;
        }
        Db::table('appointment_system_config')->where('group', 'referral')->where('key', 'reward_rate')
            ->update(['value' => $rate, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    private function newUser(string $nickname = ''): User
    {
        $user = User::create([
            'id'       => User::generateId(),
            'phone'    => '199' . substr((string) random_int(10000000, 99999999), 0, 8),
            'wx_openid' => '',
            'user_type' => 'user',
            'status'   => 1,
            'nickname' => $nickname,
        ]);
        $this->userIds[] = (string) $user->id;
        return $user;
    }

    private function newReferral(string $referrerId, string $referredUserId): UserReferral
    {
        $referral = UserReferral::create([
            'id'               => UserReferral::generateId(),
            'referrer_id'      => $referrerId,
            'referred_user_id' => $referredUserId,
            'registered_at'    => date('Y-m-d H:i:s'),
        ]);
        $this->referralIds[] = (string) $referral->id;
        return $referral;
    }

    private function makeOrder(string $userId, float $paidAmount, string $status = Order::STATUS_COMPLETED): Order
    {
        static $seq = 0;
        $seq++;
        $now = date('Y-m-d H:i:s');
        $order = Order::create([
            'id'            => Order::generateId(),
            'order_no'      => 'T' . date('YmdHis') . str_pad((string) ($seq % 100000), 5, '0', STR_PAD_LEFT),
            'user_id'       => $userId,
            'order_type'    => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'  => $paidAmount,
            'discount_amount' => 0.00,
            'paid_amount'   => $paidAmount,
            'status'        => $status,
            'service_start_at' => $now,
            'service_end_at'   => $now,
        ]);
        $this->orderIds[] = (string) $order->id;
        return $order;
    }

    /** 模拟 WorkController::complete 事务内调用返佣服务 */
    private function runReward(Order $order): void
    {
        Db::beginTransaction();
        try {
            ReferralRewardService::handleOrderCompleted($order);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }

    private function makeRequest(): Request
    {
        $body = '';
        $head = "Host: localhost\r\n"
            . "Content-Type: application/x-www-form-urlencoded\r\n"
            . "Content-Length: " . strlen($body) . "\r\n";
        return new Request("GET / HTTP/1.1\r\n" . $head . "\r\n" . $body);
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    // ── 首单完成发放 ──

    #[Test] public function first_completed_order_rewards_referrer(): void
    {
        $this->setRate('0.05');
        $referrer = $this->newUser('推荐人A');
        $referred = $this->newUser('被推荐人B');
        $referral = $this->newReferral((string) $referrer->id, (string) $referred->id);
        $order = $this->makeOrder((string) $referred->id, 100.0);

        $this->runReward($order);

        // 推荐人钱包入账 100 × 0.05 = 5
        $wallet = UserWallet::where('user_id', $referrer->id)->first();
        $this->assertNotNull($wallet);
        $this->assertSame(5.0, (float) $wallet->balance);

        // 流水：referral_reward 类型 + 金额 + balance_after + remark 含订单号
        $txn = WalletTxn::where('user_id', $referrer->id)->where('type', WalletTxn::TYPE_REFERRAL_REWARD)->first();
        $this->assertNotNull($txn);
        $this->assertSame(5.0, (float) $txn->amount);
        $this->assertSame(5.0, (float) $txn->balance_after);
        $this->assertStringContainsString($order->order_no, (string) $txn->remark);

        // 推荐记录落库：reward_type/reward_amount/rewarded_at/first_order_at
        $fresh = UserReferral::find($referral->id);
        $this->assertSame('balance', $fresh->reward_type);
        $this->assertSame(5.0, (float) $fresh->reward_amount);
        $this->assertNotNull($fresh->rewarded_at);
        $this->assertNotNull($fresh->first_order_at);

        // 被推荐人钱包分文未动
        $this->assertNull(UserWallet::where('user_id', $referred->id)->first());
    }

    #[Test] public function amount_equals_paid_amount_times_rate(): void
    {
        $this->setRate('0.10');
        $referrer = $this->newUser();
        $referred = $this->newUser();
        $this->newReferral((string) $referrer->id, (string) $referred->id);
        $order = $this->makeOrder((string) $referred->id, 88.0);

        $this->runReward($order);

        $wallet = UserWallet::where('user_id', $referrer->id)->first();
        $this->assertNotNull($wallet);
        $this->assertSame(8.8, (float) $wallet->balance);

        $referral = UserReferral::where('referred_user_id', (string) $referred->id)->first();
        $this->assertSame(8.8, (float) $referral->reward_amount);
    }

    // ── 非首单不发 ──

    #[Test] public function second_completed_order_does_not_reward(): void
    {
        $this->setRate('0.05');
        $referrer = $this->newUser();
        $referred = $this->newUser();
        $this->newReferral((string) $referrer->id, (string) $referred->id);

        $first = $this->makeOrder((string) $referred->id, 100.0);
        $this->runReward($first);

        // 第二笔已完成订单 → 不再发放
        $second = $this->makeOrder((string) $referred->id, 200.0);
        $this->runReward($second);

        $wallet = UserWallet::where('user_id', $referrer->id)->first();
        $this->assertSame(5.0, (float) $wallet->balance);

        $txns = WalletTxn::where('user_id', $referrer->id)->where('type', WalletTxn::TYPE_REFERRAL_REWARD)->get();
        $this->assertCount(1, $txns);

        $referral = UserReferral::where('referred_user_id', (string) $referred->id)->first();
        $this->assertSame(5.0, (float) $referral->reward_amount);
    }

    // ── 幂等：重复调用只发一次 ──

    #[Test] public function repeated_call_is_idempotent(): void
    {
        $this->setRate('0.05');
        $referrer = $this->newUser();
        $referred = $this->newUser();
        $this->newReferral((string) $referrer->id, (string) $referred->id);
        $order = $this->makeOrder((string) $referred->id, 100.0);

        $this->runReward($order);
        $this->runReward($order);
        $this->runReward($order);

        $wallet = UserWallet::where('user_id', $referrer->id)->first();
        $this->assertSame(5.0, (float) $wallet->balance);

        $txns = WalletTxn::where('user_id', $referrer->id)->where('type', WalletTxn::TYPE_REFERRAL_REWARD)->get();
        $this->assertCount(1, $txns);
    }

    // ── 未完成（未核销）不发 ──

    #[Test] public function non_completed_order_does_not_reward(): void
    {
        $this->setRate('0.05');
        $referrer = $this->newUser();
        $referred = $this->newUser();
        $this->newReferral((string) $referrer->id, (string) $referred->id);

        $paid = $this->makeOrder((string) $referred->id, 100.0, Order::STATUS_PAID);
        $serving = $this->makeOrder((string) $referred->id, 100.0, Order::STATUS_SERVING);

        $this->runReward($paid);
        $this->runReward($serving);

        $this->assertNull(UserWallet::where('user_id', $referrer->id)->first());
        $referral = UserReferral::where('referred_user_id', (string) $referred->id)->first();
        $this->assertNull($referral->rewarded_at);
    }

    // ── 无推荐记录不发 ──

    #[Test] public function no_referral_record_does_not_reward(): void
    {
        $this->setRate('0.05');
        $referrer = $this->newUser();
        $stranger = $this->newUser();
        $this->newReferral((string) $referrer->id, (string) $stranger->id);
        $order = $this->makeOrder((string) $referrer->id, 100.0); // 非被推荐人，无自己的推荐记录

        $this->runReward($order);

        $this->assertNull(UserWallet::where('user_id', $referrer->id)->first());
    }

    // ── 返佣明细接口 ──

    #[Test] public function earnings_endpoint_lists_rewards(): void
    {
        $this->setRate('0.05');
        $referrer = $this->newUser('推荐人A');
        $referred = $this->newUser('被推荐人B');
        $this->newReferral((string) $referrer->id, (string) $referred->id);
        $order = $this->makeOrder((string) $referred->id, 100.0);
        $this->runReward($order);

        $request = $this->makeRequest();
        $request->user_id = (string) $referrer->id;
        $resp = $this->body((new ReferralController())->earnings($request));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertCount(1, $resp['data']);
        $this->assertSame('被推荐人B', $resp['data'][0]['nickname']);
        $this->assertSame($order->order_no, $resp['data'][0]['order_no']);
        $this->assertSame(5.0, (float) $resp['data'][0]['reward_amount']);
        $this->assertNotEmpty($resp['data'][0]['rewarded_at']);
    }
}
