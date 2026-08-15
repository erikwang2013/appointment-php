<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\common\WechatProfitSharingService;
use app\model\Order;
use app\model\ProfitSharing;
use app\model\User;
use support\Db;

/**
 * 微信官方分账服务测试（真实 DB + tearDown 清理）
 *
 * 覆盖：
 * - 未启用 → disabled 降级结果，不抛异常、不落记录
 * - 启用（无商户凭据）→ 构造分账请求、落 pending 记录（金额=实付×0.7）
 * - 金额计算：比例四舍五入到分
 * - 幂等：同单同技师已存在 pending/success 记录则跳过
 * - 实付为 0 → 跳过且不落记录
 *
 * HTTP 调用隔离在私有方法 doRequest（需商户凭据），测试环境不触发网络请求。
 */
class ProfitSharingTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理 */
    private array $userIds = [];

    /** @var string[] 用例订单 ID */
    private array $orderIds = [];

    protected function tearDown(): void
    {
        foreach ($this->orderIds as $id) {
            ProfitSharing::where('order_id', $id)->delete();
            Order::where('id', $id)->delete();
        }
        foreach ($this->userIds as $id) {
            User::where('id', $id)->forceDelete();
        }
        Db::table('erik_system_config')->where('group', 'profit_sharing')->delete();
        $this->userIds  = [];
        $this->orderIds = [];
    }

    /** 造技师用户 */
    private function makeUser(): User
    {
        $user = User::create([
            'phone'     => '199' . substr((string) random_int(10000000, 99999999), 0, 8),
            'nickname'  => '分账测试技师',
            'user_type' => 'technician',
            'status'    => 1,
        ]);
        $this->userIds[] = $user->id;
        return $user;
    }

    /** 造订单（指定实付） */
    private function makeOrder(User $technician, float $paid): Order
    {
        $order = Order::create([
            'id'           => Order::generateId(),
            'order_no'     => 'PS' . date('YmdHis') . random_int(1000, 9999),
            'user_id'      => $technician->id,
            'technician_id'=> $technician->id,
            'order_type'   => 'service',
            'total_amount' => $paid,
            'paid_amount'  => $paid,
            'status'       => Order::STATUS_PAID,
        ]);
        $this->orderIds[] = $order->id;
        return $order;
    }

    /** 写入分账配置（enabled + receiver_ratio；system_config 的 id 无默认值，须显式生成） */
    private function setConfig(string $enabled, string $ratio): void
    {
        foreach (['enabled' => $enabled, 'receiver_ratio' => $ratio] as $key => $value) {
            $exists = Db::table('erik_system_config')
                ->where('group', 'profit_sharing')->where('key', $key)->exists();
            if ($exists) {
                Db::table('erik_system_config')
                    ->where('group', 'profit_sharing')->where('key', $key)
                    ->update(['value' => $value]);
            } else {
                Db::table('erik_system_config')->insert([
                    'id' => ProfitSharing::generateId(), 'group' => 'profit_sharing',
                    'key' => $key, 'value' => $value,
                ]);
            }
        }
    }

    #[Test]
    public function disabled_returns_degraded_without_record(): void
    {
        $this->setConfig('0', '0.7');
        $user  = $this->makeUser();
        $order = $this->makeOrder($user, 100.00);

        $result = (new WechatProfitSharingService())->requestSharing($order->id);

        $this->assertSame(ProfitSharing::STATUS_DISABLED, $result['status'], json_encode($result));
        $this->assertSame(0, ProfitSharing::where('order_id', $order->id)->count(), '未启用不得落记录');
    }

    #[Test]
    public function enabled_constructs_request_and_persists_record(): void
    {
        $this->setConfig('1', '0.7');
        $user  = $this->makeUser();
        $order = $this->makeOrder($user, 100.00);

        $result = (new WechatProfitSharingService())->requestSharing($order->id);

        $this->assertSame(ProfitSharing::STATUS_PENDING, $result['status'], json_encode($result));
        $this->assertSame($order->order_no, $result['sharing_no']);

        $record = ProfitSharing::where('order_id', $order->id)->first();
        $this->assertNotNull($record, '启用后应落分账记录');
        $this->assertSame((string) $user->id, (string) $record->user_id);
        $this->assertSame($order->order_no, $record->sharing_no);
        $this->assertSame('70.00', $record->amount, '金额 = 实付 × 0.7');
        $this->assertSame('0.7000', $record->ratio);
        $this->assertSame(ProfitSharing::STATUS_PENDING, $record->status);
    }

    #[Test]
    public function amount_rounds_to_cents(): void
    {
        $this->setConfig('1', '0.7');
        $user  = $this->makeUser();
        $order = $this->makeOrder($user, 99.9);

        $result = (new WechatProfitSharingService())->requestSharing($order->id);

        $this->assertSame(ProfitSharing::STATUS_PENDING, $result['status'], json_encode($result));
        $record = ProfitSharing::where('order_id', $order->id)->first();
        $this->assertSame('69.93', $record->amount, '99.9 × 0.7 = 69.93 四舍五入到分');
    }

    #[Test]
    public function duplicate_request_is_idempotent(): void
    {
        $this->setConfig('1', '0.7');
        $user  = $this->makeUser();
        $order = $this->makeOrder($user, 100.00);

        $service = new WechatProfitSharingService();
        $service->requestSharing($order->id);

        $result = $service->requestSharing($order->id);

        $this->assertSame('skipped', $result['status'], json_encode($result));
        $this->assertSame(1, ProfitSharing::where('order_id', $order->id)->count(), '同单重复分账应跳过，不新增记录');
    }

    #[Test]
    public function zero_paid_amount_skips(): void
    {
        $this->setConfig('1', '0.7');
        $user  = $this->makeUser();
        $order = $this->makeOrder($user, 0.0);

        $result = (new WechatProfitSharingService())->requestSharing($order->id);

        $this->assertSame('skipped', $result['status'], json_encode($result));
        $this->assertSame(0, ProfitSharing::where('order_id', $order->id)->count());
    }
}
