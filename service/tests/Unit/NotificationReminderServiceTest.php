<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\common\NotificationReminderService;
use app\model\Notification;
use app\model\Order;
use app\model\OrderItem;
use app\model\Store;
use app\model\User;
use Carbon\Carbon;

/**
 * 预约前提醒服务测试（真实 DB，与 OrderRefundFlowTest 同基建）
 *
 * 覆盖：
 * - paid 订单进入 2h 提醒窗口 → 生成站内通知（type=order/标题/内容含服务名、技师名、门店地址、时间）
 * - 幂等：同订单重复扫描不重复生成
 * - 已提醒（已有同标题通知）不重复生成
 * - 不满足条件（pending / 服务时间不在窗口）不生成
 * - 订阅消息降级：未配置 WECHAT_SUBSCRIBE_TEMPLATE_ID 时钩子返回 false（仅站内通知）
 */
class NotificationReminderServiceTest extends TestCase
{
    /** @var string[] 用例创建的订单 ID，tearDown 统一清理 */
    private array $orderIds = [];

    /** @var string[] 用例创建的通知 ID，tearDown 统一清理 */
    private array $notificationIds = [];

    /** @var string[] 用例创建的用户 ID */
    private array $userIds = [];

    /** @var string[] 用例创建的门店 ID */
    private array $storeIds = [];

    /** @var string[] 用例创建的服务项 ID */
    private array $itemIds = [];

    protected function tearDown(): void
    {
        foreach ($this->orderIds as $id) {
            Notification::where('order_id', $id)->delete();
            OrderItem::where('order_id', $id)->delete();
            Order::where('id', $id)->delete();
        }
        foreach ($this->notificationIds as $id) {
            Notification::where('id', $id)->delete();
        }
        foreach ($this->storeIds as $id) {
            Store::where('id', $id)->forceDelete();
        }
        foreach ($this->userIds as $id) {
            User::where('id', $id)->forceDelete();
        }
        $this->orderIds = [];
        $this->notificationIds = [];
        $this->userIds = [];
        $this->storeIds = [];
        $this->itemIds = [];
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

    /** 造用户（技师/顾客共用；nickname 用于通知内容断言） */
    private function makeUser(string $nickname): User
    {
        $user = User::create([
            'phone'     => '199' . substr((string) random_int(10000000, 99999999), 0, 8),
            'nickname'  => $nickname,
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
            'order_no'        => 'ORD_REM_' . uniqid(),
            'user_id'         => $customer->id,
            'technician_id'   => $technician->id,
            'store_id'        => $store->id,
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => 100.0,
            'discount_amount' => 0.0,
            'paid_amount'     => 100.0,
            'service_time'    => $serviceTime,
            'status'          => $status,
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
        $this->orderIds[] = $order->id;
        return $order;
    }

    /** 造订单服务项（name 用于通知内容断言） */
    private function makeItem(Order $order, string $name): void
    {
        $item = OrderItem::create([
            'id'         => OrderItem::generateId(), // 列实为 BIGINT，需 snowflake 数值 ID
            'order_id'   => $order->id,
            'target_type'=> 'service',
            'target_id'  => OrderItem::generateId(), // 列实为 BIGINT
            'name'       => $name,
            'price'      => 100.0,
            'quantity'   => 1,
        ]);
        $this->itemIds[] = $item->id;
    }

    /** 造一组完整预约（门店/技师/顾客/服务项），service_time 为 now + 偏移秒 */
    private function makeAppointment(string $status, int $offsetSeconds, string $itemName = '肩颈按摩'): Order
    {
        $store     = $this->makeStore();
        $technician = $this->makeUser('测试技师');
        $customer  = $this->makeUser('测试顾客');
        $order     = $this->makeOrder(
            $status,
            Carbon::now()->addSeconds($offsetSeconds)->format('Y-m-d H:i:s'),
            $store,
            $technician,
            $customer
        );
        $this->makeItem($order, $itemName);
        return $order;
    }

    // ── 提醒生成 ──

    #[Test] public function paid_order_in_window_generates_reminder(): void
    {
        // 服务开始前 90 分钟，落在 [2h, 1h) 窗口内
        $order = $this->makeAppointment(Order::STATUS_PAID, 90 * 60);

        $sent = (new NotificationReminderService())->sendReminderForDueOrders();

        $this->assertSame(1, $sent);
        $notifications = Notification::where('order_id', $order->id)->get();
        $this->assertCount(1, $notifications);

        $n = $notifications->first();
        $this->assertSame('order', $n->type);
        $this->assertSame('预约即将开始', $n->title);
        $this->assertSame($order->user_id, $n->user_id);
        // order_id 列未 cast（BIGINT），数值等价即可
        $this->assertEquals($order->id, $n->order_id);
        $this->assertSame(0, $n->is_read);

        // 内容含服务名 / 技师名 / 门店名与地址 / 时间
        $this->assertStringContainsString('服务：肩颈按摩', $n->content);
        $this->assertStringContainsString('技师：测试技师', $n->content);
        $this->assertStringContainsString('门店：测试门店（测试路 1 号）', $n->content);
        $this->assertStringContainsString(
            Carbon::parse($order->service_time)->format('Y-m-d H:i'),
            $n->content
        );
    }

    // ── 幂等：同订单重复扫描不重复生成 ──

    #[Test] public function repeated_scan_is_idempotent(): void
    {
        $order = $this->makeAppointment(Order::STATUS_PAID, 90 * 60);
        $service = new NotificationReminderService();

        $this->assertSame(1, $service->sendReminderForDueOrders());
        // 窗口内第二次扫描：查重命中，不再生成
        $this->assertSame(0, $service->sendReminderForDueOrders());
        $this->assertSame(1, Notification::where('order_id', $order->id)->count());
    }

    // ── 已提醒（已有同标题通知）不重复生成 ──

    #[Test] public function already_reminded_order_is_skipped(): void
    {
        $order = $this->makeAppointment(Order::STATUS_PAID, 90 * 60);

        // 模拟早前已生成过提醒（如历史数据或并发窗口首扫）
        $notification = Notification::create([
            'id'         => Notification::generateId(),
            'user_id'    => $order->user_id,
            'type'       => 'order',
            'title'      => '预约即将开始',
            'content'    => '历史提醒',
            'order_id'   => $order->id,
            'is_read'    => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->notificationIds[] = $notification->id;

        $sent = (new NotificationReminderService())->sendReminderForDueOrders();

        $this->assertSame(0, $sent);
        $this->assertSame(1, Notification::where('order_id', $order->id)->count());
    }

    // ── 不满足条件 ──

    #[Test] public function pending_order_in_window_not_reminded(): void
    {
        $order = $this->makeAppointment(Order::STATUS_PENDING, 90 * 60);

        $sent = (new NotificationReminderService())->sendReminderForDueOrders();

        $this->assertSame(0, $sent);
        $this->assertSame(0, Notification::where('order_id', $order->id)->count());
    }

    #[Test] public function paid_order_outside_window_not_reminded(): void
    {
        // 服务开始前 3 小时：尚未进入 2h 提醒窗口
        $early = $this->makeAppointment(Order::STATUS_PAID, 3 * 3600);
        // 服务开始前 30 分钟：已滑出 [2h, 1h) 窗口（视为漏扫，不再补发）
        $late = $this->makeAppointment(Order::STATUS_PAID, 30 * 60);

        $sent = (new NotificationReminderService())->sendReminderForDueOrders();

        $this->assertSame(0, $sent);
        $this->assertSame(0, Notification::where('order_id', $early->id)->count());
        $this->assertSame(0, Notification::where('order_id', $late->id)->count());
    }

    // ── 订阅消息降级（未配置微信模板 → 仅站内通知）──

    #[Test] public function subscribe_reminder_degrades_to_in_app_only(): void
    {
        $order = $this->makeAppointment(Order::STATUS_PAID, 90 * 60);

        // 测试环境未配置 WECHAT_SUBSCRIBE_TEMPLATE_ID：钩子返回 false，仅写站内通知
        $sent = (new NotificationReminderService())->sendReminderForDueOrders();
        $this->assertSame(1, $sent);
        $this->assertSame(1, Notification::where('order_id', $order->id)->count());

        // 钩子本身恒返回 false（未实现发送，仅日志）
        $this->assertFalse((new NotificationReminderService())->sendSubscribeReminder($order));
    }
}
