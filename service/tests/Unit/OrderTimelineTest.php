<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\model\Notification;
use app\model\Order;
use app\model\OrderStatusLog;
use app\model\OrderVerification;
use app\model\TechnicianEarning;
use app\model\TechnicianProfile;
use app\order\v1\controller\OrderController;
use app\order\v1\controller\TimelineController;
use support\Container;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 订单状态时间线集成测试
 *
 * 覆盖：
 * - 多次状态变更后 timeline 返回全部记录且倒序（最新在前）
 * - 非本人订单 404（M1 水平越权）
 * - 记录携带 operator 与 remark
 * - 核销埋点：verifyByCode 推进 paid → serving 时自动写时间线
 *
 * 依赖真实 DB / Redis（与 OrderRefundFlowTest 同基建）。
 */
class OrderTimelineTest extends TestCase
{
    /** @var string[] 用例创建的订单 ID，tearDown 统一清理 */
    private array $orderIds = [];

    /** @var string[] 用例创建的技师档案 ID，tearDown 统一清理 */
    private array $profileIds = [];

    protected function tearDown(): void
    {
        foreach ($this->orderIds as $id) {
            OrderStatusLog::where('order_id', $id)->delete();
            Notification::where('order_id', $id)->delete();
            TechnicianEarning::where('order_id', $id)->delete();
            OrderVerification::where('order_id', $id)->delete();
            Order::where('id', $id)->delete();
        }
        foreach ($this->profileIds as $id) {
            TechnicianProfile::where('id', $id)->forceDelete();
        }
        $this->orderIds = [];
        $this->profileIds = [];
    }

    private function makeRequest(array $post = []): Request
    {
        $body = http_build_query($post);
        $head = "Host: localhost\r\n"
            . "Content-Type: application/x-www-form-urlencoded\r\n"
            . "Content-Length: " . strlen($body) . "\r\n";
        return new Request("POST / HTTP/1.1\r\n" . $head . "\r\n" . $body);
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    private function makeOrder(string $status = Order::STATUS_PENDING): Order
    {
        $order = Order::create([
            'order_no'        => 'ORD_TL_' . uniqid(),
            'user_id'         => (string) (9900000000000000 + random_int(1, 999999)),
            'technician_id'   => (string) (9900000000000000 + random_int(1, 999999)),
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => 100.0,
            'discount_amount' => 0.0,
            'paid_amount'     => 100.0,
            'status'          => $status,
        ]);
        $this->orderIds[] = $order->id;
        return $order;
    }

    private function hashid(string $id): string
    {
        return (string) Container::get('hashids')->encode((int) $id);
    }

    /** 造已审核技师档案（verifyByCode 集成用例用） */
    private function makeTechnician(): TechnicianProfile
    {
        $profile = new TechnicianProfile();
        $profile->id        = TechnicianProfile::generateId();
        $profile->user_id   = (string) (9900000000000000 + random_int(1, 999999));
        $profile->real_name = '测试技师';
        $profile->gender    = 1;
        $profile->status    = 'approved';
        $profile->save();
        $this->profileIds[] = $profile->id;
        return $profile;
    }

    #[Test] public function timeline_returns_all_entries_newest_first(): void
    {
        $order = $this->makeOrder();
        OrderStatusLog::record($order->id, null, Order::STATUS_PENDING, '创建订单', 'user');
        OrderStatusLog::record($order->id, Order::STATUS_PENDING, Order::STATUS_PAID, '支付成功', 'user');
        OrderStatusLog::record($order->id, Order::STATUS_PAID, Order::STATUS_CONFIRMED, '商家确认接单', 'admin');
        OrderStatusLog::record($order->id, Order::STATUS_CONFIRMED, Order::STATUS_COMPLETED, '服务完成', 'technician');

        $request = $this->makeRequest();
        $request->user_id = $order->user_id;
        $data = $this->body((new TimelineController())->show($request, $this->hashid($order->id)))['data'];

        $this->assertCount(4, $data);
        // 倒序：最新在前
        $this->assertSame(Order::STATUS_COMPLETED, $data[0]['to_status']);
        $this->assertSame(Order::STATUS_CONFIRMED, $data[1]['to_status']);
        $this->assertSame(Order::STATUS_PAID, $data[2]['to_status']);
        $this->assertSame(Order::STATUS_PENDING, $data[3]['to_status']);
        // 状态链正确：后一条的 from 是前一条的 to
        $this->assertSame($data[0]['from_status'], $data[1]['to_status']);
        $this->assertSame($data[1]['from_status'], $data[2]['to_status']);
        // 首条 from_status 为 NULL（订单创建）
        $this->assertNull($data[3]['from_status']);
    }

    #[Test] public function timeline_404_when_order_not_owned(): void
    {
        $order = $this->makeOrder();
        OrderStatusLog::record($order->id, null, Order::STATUS_PENDING, '创建订单', 'user');

        $request = $this->makeRequest();
        $request->user_id = (string) (9900000000000000 + random_int(1, 999999));
        $resp = (new TimelineController())->show($request, $this->hashid($order->id));

        $this->assertSame(404, $this->body($resp)['code']);
    }

    #[Test] public function timeline_entries_carry_operator_and_remark(): void
    {
        $order = $this->makeOrder();
        OrderStatusLog::record($order->id, Order::STATUS_PAID, Order::STATUS_REFUNDING, '用户申请退款，到账原路退回', 'user');

        $request = $this->makeRequest();
        $request->user_id = $order->user_id;
        $data = $this->body((new TimelineController())->show($request, $this->hashid($order->id)))['data'];

        $this->assertCount(1, $data);
        $this->assertSame('refunding', $data[0]['to_status']);
        $this->assertSame('user', $data[0]['operator']);
        $this->assertSame('用户申请退款，到账原路退回', $data[0]['remark']);
    }

    #[Test] public function verify_instrumentation_writes_serving_entry(): void
    {
        $technician = $this->makeTechnician();
        $order = $this->makeOrder(Order::STATUS_PAID);
        $order->technician_id = $technician->id;
        $order->save();

        $code = bin2hex(random_bytes(16));
        OrderVerification::create([
            'id'       => OrderVerification::generateId(),
            'order_id' => $order->id,
            'code'     => $code,
        ]);

        $request = $this->makeRequest(['code' => $code]);
        $request->user_id = $technician->user_id;
        $resp = (new OrderController())->verifyByCode($request);
        $this->assertSame(0, $this->body($resp)['code']);

        // 核销推进 paid → serving 时自动写入时间线
        $latest = OrderStatusLog::where('order_id', $order->id)
            ->orderByDesc('id')
            ->first();
        $this->assertNotNull($latest);
        $this->assertSame(Order::STATUS_PAID, $latest->from_status);
        $this->assertSame(Order::STATUS_SERVING, $latest->to_status);
        $this->assertSame('technician', $latest->operator);
        $this->assertSame('核销开始服务', $latest->remark);
    }
}
