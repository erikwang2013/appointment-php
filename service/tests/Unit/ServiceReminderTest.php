<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\model\Notification;
use app\model\Order;
use app\model\OrderItem;
use app\model\Store;
use app\model\User;
use app\process\ServiceReminderTimer;

/**
 * 服务开始前预约提醒闭环测试
 *
 * 覆盖：confirmed/serving 订单落入 [now+1h, now+1h+60s) 窗口生成
 * type=service_reminder 站内通知（内容含服务/技师/门店/时间）、窗口外不提醒、
 * paid 状态不提醒、重复扫描幂等（不重复通知）。
 * 基建与 NotificationReminderServiceTest 一致（真实 DB + tearDown 清理）。
 */
class ServiceReminderTest extends TestCase
{
    /** @var string[] 用例创建的订单 ID，tearDown 统一清理 */
    private array $orderIds = [];

    /** @var string[] 用例创建的服务项 ID */
    private array $itemIds = [];

    /** @var string[] 用例创建的门店 ID */
    private array $storeIds = [];

    /** @var string[] 用例创建的用户 ID */
    private array $userIds = [];

    protected function tearDown(): void
    {
        foreach ($this->orderIds as $id) {
            Notification::where('order_id', $id)->delete();
            OrderItem::where('order_id', $id)->delete();
            Order::where('id', $id)->delete();
        }
        foreach ($this->storeIds as $id) {
            Store::where('id', $id)->forceDelete();
        }
        foreach ($this->userIds as $id) {
            User::where('id', $id)->forceDelete();
        }
        $this->orderIds = [];
        $this->itemIds = [];
        $this->storeIds = [];
        $this->userIds = [];
    }

    /** 造门店（name/address 用于通知内容断言） */
    private function makeStore(): Store
    {
        $store = Store::create([
            'id'      => Store::generateId(),
            'name'    => '测试门店',
            'address' => '测试路 1 号',
            'status'  => 1,
        ]);
        $this->storeIds[] = $store->id;
        return $store;
    }

    /** 造用户（技师/顾客共用；wx_openid 留空避免测试触发真实微信订阅消息） */
    private function makeUser(string $nickname): User
    {
        $user = User::create([
            'phone'     => '199' . substr((string) random_int(10000000, 99999999), 0, 8),
            'nickname'  => $nickname,
            'wx_openid' => '',
            'user_type' => 'user',
            'status'    => 1,
        ]);
        $this->userIds[] = $user->id;
        return $user;
    }

    /** 造订单（service_time 由调用方控制以决定是否落入提醒窗口） */
    private function makeOrder(
        string $status,
        string $serviceTime,
        Store $store,
        User $technician,
        User $customer
    ): Order {
        $order = Order::create([
            'order_no'        => 'ORD_SVC_' . uniqid(),
            'user_id'         => $customer->id,
            'technician_id'   => $technician->id,
            'store_id'        => $store->id,
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => 100.0,
            'discount_amount' => 0.0,
            'paid_amount'     => 100.0,
            'service_time'    => $serviceTime,
            'status'          => $status,
        ]);
        $this->orderIds[] = $order->id;
        return $order;
    }

    /** 造订单服务项（name 用于通知内容断言） */
    private function makeItem(Order $order, string $name): void
    {
        $item = OrderItem::create([
            'id'          => OrderItem::generateId(),
            'order_id'    => $order->id,
            'target_type' => 'service',
            'target_id'   => OrderItem::generateId(),
            'name'        => $name,
            'price'       => 100.0,
            'quantity'    => 1,
        ]);
        $this->itemIds[] = $item->id;
    }

    /** 造一组完整预约（门店/技师/顾客/服务项），service_time 为 now + 偏移秒 */
    private function makeAppointment(string $status, int $offsetSeconds, string $itemName = '肩颈按摩'): Order
    {
        $store      = $this->makeStore();
        $technician = $this->makeUser('测试技师');
        $customer   = $this->makeUser('测试顾客');
        $order      = $this->makeOrder(
            $status,
            Carbon::now()->addSeconds($offsetSeconds)->format('Y-m-d H:i:s'),
            $store,
            $technician,
            $customer
        );
        $this->makeItem($order, $itemName);
        return $order;
    }

    /** 反射实例化进程（构造函数注册 Workerman Timer，CLI 单测下不可用） */
    private function scan(): void
    {
        $timer = (new \ReflectionClass(ServiceReminderTimer::class))->newInstanceWithoutConstructor();
        $timer->scanAndRemind();
    }

    // ── 提醒生成 ──

    #[Test] public function confirmed_order_in_window_generates_service_reminder(): void
    {
        // 服务开始前 1 小时 + 30 秒，落在 [1h, 1h+60s) 窗口内
        $order = $this->makeAppointment(Order::STATUS_CONFIRMED, 3600 + 30);

        $this->scan();

        $notifications = Notification::where('order_id', $order->id)
            ->where('type', 'service_reminder')
            ->get();
        $this->assertCount(1, $notifications, '窗口内 confirmed 订单应生成一条 service_reminder 通知');
        $this->assertSame((string) $order->user_id, (string) $notifications[0]->user_id);
        $this->assertSame('服务即将开始', (string) $notifications[0]->title);
        $content = (string) $notifications[0]->content;
        $this->assertStringContainsString('肩颈按摩', $content, '内容应含服务名');
        $this->assertStringContainsString('测试技师', $content, '内容应含技师昵称');
        $this->assertStringContainsString('测试门店', $content, '内容应含门店名');
        $this->assertStringContainsString(
            Carbon::now()->addSeconds(3600 + 30)->format('Y-m-d H:i'),
            $content,
            '内容应含服务开始时间'
        );
    }

    #[Test] public function serving_order_in_window_generates_reminder(): void
    {
        $order = $this->makeAppointment(Order::STATUS_SERVING, 3600 + 10);

        $this->scan();

        $this->assertSame(1, Notification::where('order_id', $order->id)
            ->where('type', 'service_reminder')->count());
    }

    #[Test] public function order_outside_window_is_not_reminded(): void
    {
        // 服务开始前 2 小时，窗口外
        $order = $this->makeAppointment(Order::STATUS_CONFIRMED, 2 * 3600);

        $this->scan();

        $this->assertSame(0, Notification::where('order_id', $order->id)->count());
    }

    #[Test] public function paid_order_is_not_reminded(): void
    {
        // 仅 confirmed/serving 触发，paid 由既有 NotificationReminderService（2h~1h）负责
        $order = $this->makeAppointment(Order::STATUS_PAID, 3600 + 30);

        $this->scan();

        $this->assertSame(0, Notification::where('order_id', $order->id)->count());
    }

    // ── 幂等 ──

    #[Test] public function scan_is_idempotent_on_repeat_runs(): void
    {
        $order = $this->makeAppointment(Order::STATUS_CONFIRMED, 3600 + 30);

        $this->scan();
        $this->scan();

        $this->assertSame(1, Notification::where('order_id', $order->id)
            ->where('type', 'service_reminder')->count(), '重复扫描不得重复通知');
    }
}
