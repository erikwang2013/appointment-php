<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\common\ReturnCustomerRewardService;
use app\model\Order;
use app\model\TechnicianEarning;
use app\model\TechnicianProfile;
use app\model\User;
use app\technician\v1\controller\WorkController;
use support\Container;
use support\Db;
use Webman\Http\Request;

/**
 * 回头客奖励测试（R24：30 天内二次消费奖金）
 *
 * 规则：用户对同一技师 30 天窗口内已有 ≥2 次 completed 订单时，
 * 第 2 次起发放奖金（金额=订单实付×ratio，落 erik_technician_earnings
 * type='return_customer' status='pending'）。
 * 覆盖：首单不发/窗口内第 2 单发放/窗口外不发/幂等/开关与比例配置/complete 集成。
 * 策略：真实库，tearDown 清理；配置用例恢复原值。
 */
class ReturnCustomerRewardTest extends TestCase
{
    private array $orderIds = [];
    private array $technicianIds = [];
    private array $userIds = [];
    private array $earningIds = [];
    private array $savedConfigs = [];

    protected function tearDown(): void
    {
        foreach ($this->earningIds as $id) {
            TechnicianEarning::where('id', $id)->delete();
        }
        // 服务内部发放的奖励（未登记 earningIds）按订单关联清理
        foreach ($this->orderIds as $id) {
            TechnicianEarning::where('order_id', $id)
                ->where('type', ReturnCustomerRewardService::TYPE_RETURN_CUSTOMER)
                ->delete();
        }
        foreach ($this->orderIds as $id) {
            Order::where('id', $id)->delete();
        }
        foreach ($this->technicianIds as $id) {
            TechnicianProfile::where('id', $id)->forceDelete();
        }
        foreach ($this->userIds as $id) {
            User::where('id', $id)->forceDelete();
        }
        foreach ($this->savedConfigs as $key => $value) {
            Db::table('erik_system_config')
                ->where('group', 'return_customer')
                ->where('key', $key)
                ->update(['value' => $value]);
        }
        $this->earningIds = [];
        $this->orderIds = [];
        $this->technicianIds = [];
        $this->userIds = [];
        $this->savedConfigs = [];
    }

    private function makeUser(): User
    {
        $user = User::create([
            'phone'     => '198' . substr((string) random_int(10000000, 99999999), 0, 8),
            'wx_openid' => '',
            'user_type' => 'user',
            'status'    => 1,
        ]);
        $this->userIds[] = $user->id;
        return $user;
    }

    private function makeTechnician(User $user): TechnicianProfile
    {
        $tp = TechnicianProfile::create([
            'user_id'    => $user->id,
            'real_name'  => '回头客奖励测试技师',
            'status'     => 'active',
            'audited_at' => date('Y-m-d H:i:s'),
        ]);
        $this->technicianIds[] = $tp->id;
        return $tp;
    }

    /** 造订单（order_no 唯一，uk_order_no） */
    private function makeOrder(User $user, TechnicianProfile $technician, float $paid, string $status, ?string $serviceEndAt = null): Order
    {
        $order = Order::create([
            'order_no'      => 'RC' . time() . random_int(1000, 9999),
            'user_id'       => $user->id,
            'technician_id' => $technician->id,
            'order_type'    => Order::ORDER_TYPE_APPOINTMENT,
            'paid_amount'   => $paid,
            'status'        => $status,
            'service_time'  => date('Y-m-d H:i:s'),
            'service_end_at' => $serviceEndAt,
        ]);
        $this->orderIds[] = $order->id;
        return $order;
    }

    /** 写 return_customer 配置并保存原值（tearDown 恢复） */
    private function setConfig(string $key, string $value): void
    {
        if (!isset($this->savedConfigs[$key])) {
            $this->savedConfigs[$key] = (string) Db::table('erik_system_config')
                ->where('group', 'return_customer')
                ->where('key', $key)
                ->value('value');
        }
        Db::table('erik_system_config')
            ->where('group', 'return_customer')
            ->where('key', $key)
            ->update(['value' => $value]);
    }

    private function countRewards(Order $order): int
    {
        return TechnicianEarning::where('order_id', $order->id)
            ->where('type', ReturnCustomerRewardService::TYPE_RETURN_CUSTOMER)
            ->count();
    }

    // ── 规则边界 ──

    #[Test]
    public function first_completed_order_gets_no_reward(): void
    {
        $user = $this->makeUser();
        $technician = $this->makeTechnician($this->makeUser());

        $first = $this->makeOrder($user, $technician, 100.0, Order::STATUS_COMPLETED, date('Y-m-d H:i:s'));

        ReturnCustomerRewardService::handleOrderCompleted($first);

        $this->assertSame(0, $this->countRewards($first));
    }

    #[Test]
    public function second_order_within_30_days_grants_reward(): void
    {
        $user = $this->makeUser();
        $technician = $this->makeTechnician($this->makeUser());

        $this->makeOrder($user, $technician, 200.0, Order::STATUS_COMPLETED, date('Y-m-d H:i:s', strtotime('-5 days')));
        $second = $this->makeOrder($user, $technician, 100.0, Order::STATUS_COMPLETED, date('Y-m-d H:i:s'));

        ReturnCustomerRewardService::handleOrderCompleted($second);

        $this->assertSame(1, $this->countRewards($second));
        $reward = TechnicianEarning::where('order_id', $second->id)
            ->where('type', ReturnCustomerRewardService::TYPE_RETURN_CUSTOMER)
            ->first();
        $this->assertSame(5.0, (float) $reward->amount); // 100 × 0.05
        $this->assertSame((string) $technician->id, (string) $reward->technician_id);
        $this->assertSame('pending', $reward->status);
        $this->assertStringContainsString($second->order_no, $reward->description);
    }

    #[Test]
    public function second_order_beyond_30_days_gets_no_reward(): void
    {
        $user = $this->makeUser();
        $technician = $this->makeTechnician($this->makeUser());

        $this->makeOrder($user, $technician, 200.0, Order::STATUS_COMPLETED, date('Y-m-d H:i:s', strtotime('-40 days')));
        $second = $this->makeOrder($user, $technician, 100.0, Order::STATUS_COMPLETED, date('Y-m-d H:i:s'));

        ReturnCustomerRewardService::handleOrderCompleted($second);

        $this->assertSame(0, $this->countRewards($second));
    }

    #[Test]
    public function window_uses_technician_and_user_pair(): void
    {
        $user = $this->makeUser();
        $techA = $this->makeTechnician($this->makeUser());
        $techB = $this->makeTechnician($this->makeUser());

        // 用户在技师 A 处 5 天前完成过一单，在技师 B 处无历史
        $this->makeOrder($user, $techA, 200.0, Order::STATUS_COMPLETED, date('Y-m-d H:i:s', strtotime('-5 days')));
        $bOrder = $this->makeOrder($user, $techB, 100.0, Order::STATUS_COMPLETED, date('Y-m-d H:i:s'));

        ReturnCustomerRewardService::handleOrderCompleted($bOrder);

        $this->assertSame(0, $this->countRewards($bOrder)); // 技师 B 首单，不发
    }

    // ── 幂等 ──

    #[Test]
    public function duplicate_call_grants_only_once(): void
    {
        $user = $this->makeUser();
        $technician = $this->makeTechnician($this->makeUser());

        $this->makeOrder($user, $technician, 200.0, Order::STATUS_COMPLETED, date('Y-m-d H:i:s', strtotime('-5 days')));
        $second = $this->makeOrder($user, $technician, 100.0, Order::STATUS_COMPLETED, date('Y-m-d H:i:s'));

        ReturnCustomerRewardService::handleOrderCompleted($second);
        ReturnCustomerRewardService::handleOrderCompleted($second);
        ReturnCustomerRewardService::handleOrderCompleted($second);

        $this->assertSame(1, $this->countRewards($second));
    }

    // ── 配置 ──

    #[Test]
    public function disabled_config_suppresses_reward(): void
    {
        $this->setConfig('enabled', '0');
        $user = $this->makeUser();
        $technician = $this->makeTechnician($this->makeUser());

        $this->makeOrder($user, $technician, 200.0, Order::STATUS_COMPLETED, date('Y-m-d H:i:s', strtotime('-5 days')));
        $second = $this->makeOrder($user, $technician, 100.0, Order::STATUS_COMPLETED, date('Y-m-d H:i:s'));

        ReturnCustomerRewardService::handleOrderCompleted($second);

        $this->assertSame(0, $this->countRewards($second));
    }

    #[Test]
    public function ratio_reads_config_and_falls_back_on_invalid(): void
    {
        $this->setConfig('ratio', '0.1');
        $this->assertSame(0.1, ReturnCustomerRewardService::getRatio());

        $this->setConfig('ratio', '2');
        $this->assertSame(0.05, ReturnCustomerRewardService::getRatio()); // 非法回落默认

        $this->setConfig('ratio', '-1');
        $this->assertSame(0.05, ReturnCustomerRewardService::getRatio());
    }

    #[Test]
    public function custom_ratio_applies_to_amount(): void
    {
        $this->setConfig('ratio', '0.1');
        $user = $this->makeUser();
        $technician = $this->makeTechnician($this->makeUser());

        $this->makeOrder($user, $technician, 200.0, Order::STATUS_COMPLETED, date('Y-m-d H:i:s', strtotime('-5 days')));
        $second = $this->makeOrder($user, $technician, 100.0, Order::STATUS_COMPLETED, date('Y-m-d H:i:s'));

        ReturnCustomerRewardService::handleOrderCompleted($second);

        $reward = TechnicianEarning::where('order_id', $second->id)
            ->where('type', ReturnCustomerRewardService::TYPE_RETURN_CUSTOMER)
            ->first();
        $this->assertSame(10.0, (float) $reward->amount); // 100 × 0.1
    }

    // ── WorkController complete 集成 ──

    #[Test]
    public function complete_workflow_grants_reward_in_transaction(): void
    {
        $user = $this->makeUser();
        $technician = $this->makeTechnician($this->makeUser());

        // 5 天前已完成一单（触发回头客的窗口内历史）
        $this->makeOrder($user, $technician, 200.0, Order::STATUS_COMPLETED, date('Y-m-d H:i:s', strtotime('-5 days')));
        // 当前服务中订单
        $serving = $this->makeOrder($user, $technician, 100.0, Order::STATUS_SERVING);

        $request = new Request("POST /? HTTP/1.1\r\nHost: localhost\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: 0\r\n\r\n");
        $request->technician_id = $technician->id;

        $hashid = Container::get('hashids')->encode((int) $serving->id);
        $response = (new WorkController())->complete($request, $hashid);
        $body = json_decode($response->rawBody(), true);

        $this->assertSame(0, $body['code']);
        $this->assertSame(Order::STATUS_COMPLETED, Order::find($serving->id)->status);
        $this->assertSame(1, $this->countRewards($serving));
        $reward = TechnicianEarning::where('order_id', $serving->id)
            ->where('type', ReturnCustomerRewardService::TYPE_RETURN_CUSTOMER)
            ->first();
        $this->assertSame(5.0, (float) $reward->amount);
    }
}
