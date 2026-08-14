<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

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
use app\model\UserNotifySetting;
use app\user\v1\controller\NotifySettingController;
use Carbon\Carbon;
use Webman\Http\Request;

/**
 * 消息偏好设置测试（真实 DB，基建同 NotificationReminderServiceTest）
 *
 * 覆盖：
 * - 默认全开：无设置行时 GET 返回 5 类全开（未设置 = 默认开）
 * - 设置开关落库：PUT 批量设置后 DB 行与 GET 结果一致
 * - 关闭后不写站内通知：service_reminder=0 时提醒扫描与订单事件补建均跳过
 * - system 不可关：PUT system=0 强制为 1，且订单交易事件（SCENE_PAY）仍写站内通知
 * - 批量 upsert：同类型重复设置不产生重复行
 * - 非法入参拒绝
 */
class NotifySettingTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理 */
    private array $userIds = [];

    /** @var string[] 用例订单 ID */
    private array $orderIds = [];

    /** @var string[] 用例门店 ID */
    private array $storeIds = [];

    protected function tearDown(): void
    {
        foreach ($this->userIds as $id) {
            UserNotifySetting::where('user_id', $id)->delete();
            Notification::where('user_id', $id)->delete();
        }
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
        $this->userIds = [];
        $this->orderIds = [];
        $this->storeIds = [];
    }

    /** 造用户 */
    private function makeUser(): User
    {
        $user = User::create([
            'phone'     => '199' . substr((string) random_int(10000000, 99999999), 0, 8),
            'nickname'  => '偏好测试用户',
            'user_type' => 'user',
            'status'    => 1,
        ]);
        $this->userIds[] = $user->id;
        return $user;
    }

    /** 造门店 */
    private function makeStore(): Store
    {
        $store = Store::create([
            'id'      => Store::generateId(),
            'name'    => '偏好测试门店',
            'address' => '测试路 2 号',
            'status'  => 1,
        ]);
        $this->storeIds[] = $store->id;
        return $store;
    }

    /** 造预约订单（service_time 由调用方控制） */
    private function makeOrder(string $status, string $serviceTime, User $customer): Order
    {
        $order = Order::create([
            'order_no'        => 'ORD_NFS_' . uniqid(),
            'user_id'         => $customer->id,
            'technician_id'   => 0,
            'store_id'        => $this->makeStore()->id,
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

    /** 造 JSON 请求（user_id 由 Auth 中间件注入，测试直接赋值） */
    private function makeRequest(string $method, string $userId, array $body = []): Request
    {
        $encoded = json_encode($body);
        $head    = "Host: localhost\r\n"
            . "Content-Type: application/json\r\n"
            . "Content-Length: " . strlen($encoded) . "\r\n";
        $request = new Request($method . " / HTTP/1.1\r\n" . $head . "\r\n" . $encoded);
        $request->user_id = $userId;
        return $request;
    }

    /** 调控制器并解码响应 */
    private function callController(string $method, string $userId, array $body = []): array
    {
        $controller = new NotifySettingController();
        $response   = $method === 'GET'
            ? $controller->index($this->makeRequest('GET', $userId))
            : $controller->update($this->makeRequest('PUT', $userId, $body));
        return json_decode($response->rawBody(), true);
    }

    /** 响应 data 转 [type => switch] 映射 */
    private function settingsOf(array $response): array
    {
        return array_column($response['data'] ?? [], 'switch', 'type');
    }

    /**
     * 该订单上「属于该订单用户」的站内通知数
     *
     * 按 user_id + order_id 双重作用域：跨测试泄漏的孤儿通知（同 order_id 但
     * 属其他测试用户）不计入，避免污染导致误报。
     */
    private function notificationsOf(Order $order): int
    {
        return Notification::where('order_id', $order->id)
            ->where('user_id', $order->user_id)
            ->count();
    }

    // ── 默认全开 ──

    #[Test] public function unset_user_gets_all_types_default_on(): void
    {
        $user = $this->makeUser();

        $resp = $this->callController('GET', (string) $user->id);

        $this->assertSame(0, $resp['code']);
        $this->assertCount(5, $resp['data']);
        $this->assertSame([
            'service_reminder' => 1,
            'card_expiry'      => 1,
            'points_expiry'    => 1,
            'marketing'        => 1,
            'system'           => 1,
        ], $this->settingsOf($resp));
    }

    // ── 设置开关落库 ──

    #[Test] public function put_persists_switches_and_get_reflects(): void
    {
        $user = $this->makeUser();

        $resp = $this->callController('PUT', (string) $user->id, [
            'settings' => [
                ['type' => 'service_reminder', 'switch' => 0],
                ['type' => 'points_expiry', 'switch' => 0],
            ],
        ]);

        $this->assertSame(0, $resp['code']);
        $settings = $this->settingsOf($resp);
        $this->assertSame(0, $settings['service_reminder']);
        $this->assertSame(0, $settings['points_expiry']);
        $this->assertSame(1, $settings['card_expiry']);

        // 落库断言
        $row = UserNotifySetting::where('user_id', (string) $user->id)
            ->where('type', 'service_reminder')->first();
        $this->assertNotNull($row);
        $this->assertSame(0, $row->switch);
        $this->assertSame(2, UserNotifySetting::where('user_id', (string) $user->id)->count());
    }

    // ── 关闭后不写站内通知 ──

    #[Test] public function turned_off_service_reminder_skips_in_app_notification(): void
    {
        $user = $this->makeUser();
        $this->callController('PUT', (string) $user->id, [
            'settings' => [['type' => 'service_reminder', 'switch' => 0]],
        ]);

        // 预约提醒扫描路径（processOrder）：该用户不写站内通知
        $order = $this->makeOrder(Order::STATUS_PAID, Carbon::now()->addMinutes(90)->format('Y-m-d H:i:s'), $user);
        (new NotificationReminderService())->sendReminderForDueOrders();
        $this->assertSame(0, $this->notificationsOf($order));

        // 订单事件路径（SCENE_REMINDER 归服务提醒）：补建同样跳过
        $this->assertFalse((new NotificationReminderService())->sendSubscribeForOrderEvent(
            $order,
            NotificationReminderService::SCENE_REMINDER
        ));
        $this->assertSame(0, $this->notificationsOf($order));
    }

    #[Test] public function default_on_still_writes_in_app_notification(): void
    {
        // 对照组：未设置行 = 默认开，提醒正常生成
        $user  = $this->makeUser();
        $order = $this->makeOrder(Order::STATUS_PAID, Carbon::now()->addMinutes(90)->format('Y-m-d H:i:s'), $user);

        (new NotificationReminderService())->sendReminderForDueOrders();

        $this->assertSame(1, $this->notificationsOf($order));
    }

    // ── system 不可关 ──

    #[Test] public function system_cannot_be_turned_off(): void
    {
        $user = $this->makeUser();

        $resp = $this->callController('PUT', (string) $user->id, [
            'settings' => [['type' => 'system', 'switch' => 0]],
        ]);
        $this->assertSame(0, $resp['code']);
        $this->assertSame(1, $this->settingsOf($resp)['system']);

        // 落库强制为 1
        $row = UserNotifySetting::where('user_id', (string) $user->id)
            ->where('type', 'system')->first();
        $this->assertNotNull($row);
        $this->assertSame(1, $row->switch);

        // 订单交易事件（SCENE_PAY 归 system）：仍补建站内通知
        $order = $this->makeOrder(Order::STATUS_PAID, Carbon::now()->addMinutes(90)->format('Y-m-d H:i:s'), $user);
        (new NotificationReminderService())->sendSubscribeForOrderEvent($order, NotificationReminderService::SCENE_PAY);
        $this->assertSame(1, Notification::where('order_id', $order->id)
            ->where('user_id', $order->user_id)
            ->where('title', '订单支付成功')->count());
    }

    // ── 批量 upsert ──

    #[Test] public function repeated_batch_update_upserts_no_duplicates(): void
    {
        $user = $this->makeUser();

        $this->callController('PUT', (string) $user->id, [
            'settings' => [
                ['type' => 'service_reminder', 'switch' => 0],
                ['type' => 'marketing', 'switch' => 0],
            ],
        ]);
        // 再次设置：同类型覆盖，不开新行
        $this->callController('PUT', (string) $user->id, [
            'settings' => [
                ['type' => 'service_reminder', 'switch' => 1],
                ['type' => 'card_expiry', 'switch' => 0],
            ],
        ]);

        $this->assertSame(3, UserNotifySetting::where('user_id', (string) $user->id)->count());
        $this->assertSame(1, UserNotifySetting::where('user_id', (string) $user->id)
            ->where('type', 'service_reminder')->count());

        $resp = $this->callController('GET', (string) $user->id);
        $this->assertSame(1, $this->settingsOf($resp)['service_reminder']);
        $this->assertSame(0, $this->settingsOf($resp)['card_expiry']);
        $this->assertSame(0, $this->settingsOf($resp)['marketing']);
    }

    // ── 非法入参 ──

    #[Test] public function invalid_type_or_switch_rejected(): void
    {
        $user = $this->makeUser();

        $badType = $this->callController('PUT', (string) $user->id, [
            'settings' => [['type' => 'not_a_type', 'switch' => 1]],
        ]);
        $this->assertNotSame(0, $badType['code']);

        $badSwitch = $this->callController('PUT', (string) $user->id, [
            'settings' => [['type' => 'marketing', 'switch' => 2]],
        ]);
        $this->assertNotSame(0, $badSwitch['code']);

        $this->assertSame(0, UserNotifySetting::where('user_id', (string) $user->id)->count());
    }
}
