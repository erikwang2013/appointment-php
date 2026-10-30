<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\model\Order;
use app\model\OrderPayment;
use app\model\UserPoints;
use app\process\AutoCancelTimer;
use support\Db;
use support\Redis;

/**
 * 自动取消定时器并发锁测试
 *
 * 覆盖：
 * - order_lock 被支付/回调持有时，自动取消跳过该订单（不取消、不退积分）
 * - 正常取消时释放 order_lock，并退还 points_offset 已扣积分
 *
 * 依赖真实 DB / Redis（与 OrderPointsPayTest 同基建）。
 */
class AutoCancelLockTest extends TestCase
{
    /** @var string[] 用例订单 ID */
    private array $orderIds = [];

    /** @var string[] 用例用户 ID（清理无 order_id 的签到积分） */
    private array $userIds = [];

    protected function tearDown(): void
    {
        foreach ($this->userIds as $id) {
            UserPoints::where('user_id', $id)->delete();
        }
        foreach ($this->orderIds as $id) {
            try {
                Redis::connection()->del('order_lock:' . $id);
            } catch (\Throwable) {
            }
            Db::table('appointment_notification')->where('order_id', $id)->delete();
            Db::table('appointment_order_status_log')->where('order_id', $id)->delete();
            UserPoints::where('order_id', $id)->delete();
            OrderPayment::where('order_id', $id)->delete();
            Order::where('id', $id)->delete();
        }
        $this->orderIds = [];
        $this->userIds = [];
    }

    private function timer(): AutoCancelTimer
    {
        return (new \ReflectionClass(AutoCancelTimer::class))->newInstanceWithoutConstructor();
    }

    private function cancel(AutoCancelTimer $timer, Order $order): bool
    {
        $method = new \ReflectionMethod(AutoCancelTimer::class, 'cancelOrder');
        $method->setAccessible(true);
        return (bool) $method->invoke($timer, $order);
    }

    private function makePendingOrder(): Order
    {
        $userId = (string) (9900000000000000 + random_int(1, 999999));
        $this->userIds[] = $userId;
        $order = Order::create([
            'order_no'        => 'ORD_AUTOCANCEL_' . uniqid(),
            'user_id'         => $userId,
            'technician_id'   => 0,
            'store_id'        => 0,
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => 100.0,
            'discount_amount' => 0.0,
            'paid_amount'     => 100.0,
            'status'          => Order::STATUS_PENDING,
            'created_at'      => now()->subMinutes(31),
        ]);
        $this->orderIds[] = $order->id;
        return $order;
    }

    #[Test] public function cancel_skips_when_order_lock_is_held(): void
    {
        $order = $this->makePendingOrder();
        Redis::connection()->set('order_lock:' . $order->id, 'pay-holder', 'EX', 30);

        $this->assertFalse($this->cancel($this->timer(), $order));
        $this->assertSame(Order::STATUS_PENDING, Order::find($order->id)->status);
        $this->assertSame(0, UserPoints::where('order_id', $order->id)->where('source', 'points_refund')->count());
    }

    #[Test] public function cancel_releases_lock_and_refunds_offset_points(): void
    {
        $order = $this->makePendingOrder();

        // 模拟支付尝试已预扣 100 积分
        UserPoints::create([
            'id'          => UserPoints::generateId(),
            'user_id'     => $order->user_id,
            'type'        => 'earn',
            'points'      => 500,
            'balance'     => 500,
            'source'      => 'check_in',
            'description' => '测试签到积分',
        ]);
        UserPoints::create([
            'id'          => UserPoints::generateId(),
            'user_id'     => $order->user_id,
            'type'        => 'consume',
            'points'      => -100,
            'balance'     => 400,
            'source'      => 'points_offset',
            'order_id'    => $order->id,
            'description' => '积分抵扣订单 ' . $order->order_no,
        ]);

        $this->assertTrue($this->cancel($this->timer(), $order));

        $this->assertSame(Order::STATUS_CANCELLED, Order::find($order->id)->status);
        $refund = UserPoints::where('order_id', $order->id)->where('source', 'points_refund')->first();
        $this->assertNotNull($refund);
        $this->assertSame(100, (int) $refund->points);
        $this->assertSame(0, (int) Redis::connection()->exists('order_lock:' . $order->id));
    }
}
