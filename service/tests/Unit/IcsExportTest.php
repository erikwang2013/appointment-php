<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\model\Order;
use app\model\OrderItem;
use app\order\v1\controller\IcsController;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 我的预约 ICS 日历导出测试
 *
 * 覆盖：导出含合法日历骨架且 VEVENT 数等于订单数（排除已取消）、
 * VEVENT 含 DTSTART 与转义后的 SUMMARY、无订单返回合法空日历、仅导出本人订单。
 * 基建与 LogisticsTrackingTest 一致（真实 DB + tearDown 清理）。
 */
class IcsExportTest extends TestCase
{
    /** @var string[] 用例创建的订单 ID，tearDown 统一清理 */
    private array $orderIds = [];

    protected function tearDown(): void
    {
        foreach ($this->orderIds as $id) {
            OrderItem::where('order_id', $id)->delete();
            Order::where('id', $id)->delete();
        }
        $this->orderIds = [];
    }

    private function makeRequest(): Request
    {
        $head = "Host: localhost\r\n"
            . "Content-Type: application/x-www-form-urlencoded\r\n"
            . "Content-Length: 0\r\n";
        return new Request("GET /api/v1/order/ics HTTP/1.1\r\n" . $head . "\r\n");
    }

    private function export(string $userId): Response
    {
        $request = $this->makeRequest();
        $request->user_id = $userId;
        return (new IcsController())->export($request);
    }

    private function newUserId(): string
    {
        return (string) (9900000000000000 + random_int(1, 999999));
    }

    /** 造指定用户的预约订单（默认 status=paid，可指定状态与明细名） */
    private function makeAppointmentOrder(string $userId, string $status = Order::STATUS_PAID, ?string $itemName = null): Order
    {
        $order = Order::create([
            'id'              => Order::generateId(),
            'order_no'        => 'ORD_ICS_' . uniqid(),
            'user_id'         => $userId,
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => 299.0,
            'discount_amount' => 0.0,
            'paid_amount'     => 299.0,
            'status'          => $status,
            'service_time'    => date('Y-m-d H:i:s', strtotime('+2 days')),
        ]);
        $this->orderIds[] = $order->id;

        if ($itemName !== null) {
            OrderItem::create([
                'id'          => OrderItem::generateId(),
                'order_id'    => $order->id,
                'target_type' => 'service',
                'target_id'   => (string) (1 + random_int(0, 99999)),
                'name'        => $itemName,
                'cover_image' => 'https://example.com/cover.jpg',
                'price'       => 299.0,
                'quantity'    => 1,
                'spec_info'   => [],
            ]);
        }

        return $order;
    }

    // ── 导出含合法日历骨架，VEVENT 数等于未取消订单数 ──

    #[Test] public function export_contains_calendar_with_one_vevent_per_active_order(): void
    {
        $userId = $this->newUserId();
        $paid = $this->makeAppointmentOrder($userId, Order::STATUS_PAID, '运动损伤康复');
        $confirmed = $this->makeAppointmentOrder($userId, Order::STATUS_CONFIRMED, '肩颈理疗');
        $this->makeAppointmentOrder($userId, Order::STATUS_CANCELLED, '已取消订单');

        $body = $this->export($userId)->rawBody();

        $this->assertStringContainsString('BEGIN:VCALENDAR', $body);
        $this->assertStringContainsString('VERSION:2.0', $body);
        $this->assertStringContainsString('END:VCALENDAR', $body);
        $this->assertSame(2, substr_count($body, 'BEGIN:VEVENT'), $body);
        $this->assertStringContainsString('UID:' . $paid->id, $body);
        $this->assertStringContainsString('UID:' . $confirmed->id, $body);
    }

    // ── VEVENT 含 DTSTART，SUMMARY 按 RFC5545 转义逗号/分号 ──

    #[Test] public function vevent_contains_dtstart_and_escaped_summary(): void
    {
        $userId = $this->newUserId();
        $this->makeAppointmentOrder($userId, Order::STATUS_PAID, '康复理疗,高级;套餐（VIP）');

        $body = $this->export($userId)->rawBody();

        $this->assertStringContainsString('DTSTART;TZID=Asia/Shanghai:', $body);
        $this->assertStringContainsString('DTEND;TZID=Asia/Shanghai:', $body);
        $this->assertStringContainsString('SUMMARY:预约：康复理疗\,高级\;套餐（VIP）', $body);
        $this->assertStringContainsString('LOCATION:未指定门店', $body);
    }

    // ── 无订单返回合法空日历，且不报错 ──

    #[Test] public function no_orders_returns_valid_empty_calendar(): void
    {
        $userId = $this->newUserId();

        $response = $this->export($userId);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/calendar; charset=utf-8', $response->getHeader('Content-Type'));
        $this->assertStringContainsString('attachment; filename="my-appointments.ics"', (string) $response->getHeader('Content-Disposition'));

        $body = $response->rawBody();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $body);
        $this->assertStringContainsString('END:VCALENDAR', $body);
        $this->assertStringNotContainsString('BEGIN:VEVENT', $body);
    }

    // ── 仅导出本人订单，不泄露他人 ──

    #[Test] public function only_own_orders_are_exported(): void
    {
        $userA = $this->newUserId();
        $userB = $this->newUserId();
        $orderA = $this->makeAppointmentOrder($userA, Order::STATUS_PAID, 'A 的服务');
        $orderB = $this->makeAppointmentOrder($userB, Order::STATUS_PAID, 'B 的服务');

        $body = $this->export($userA)->rawBody();

        $this->assertSame(1, substr_count($body, 'BEGIN:VEVENT'), $body);
        $this->assertStringContainsString('UID:' . $orderA->id, $body);
        $this->assertStringNotContainsString('UID:' . $orderB->id, $body);
        $this->assertStringNotContainsString('B 的服务', $body);
    }
}
