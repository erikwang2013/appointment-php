<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\model\Notification;
use app\model\Order;
use app\model\OrderVerification;
use app\model\TechnicianEarning;
use app\model\TechnicianProfile;
use app\order\v1\controller\OrderController;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 扫码核销闭环集成测试（技师端 verify-by-code）
 *
 * 覆盖：
 * - 正常核销：paid → serving 状态推进 + 写 erik_order_verification + 站内通知用户
 * - 水平越权：非订单所属技师核销被拒（M1）
 * - 幂等：同 code 重复核销返回已核销（不报错）
 * - 状态机：未支付（pending）订单拒绝核销
 *
 * 依赖真实 DB / Redis（与 OrderRefundFlowTest 同基建）。
 */
class OrderVerificationFlowTest extends TestCase
{
    /** @var string[] 用例创建的订单 ID，tearDown 统一清理 */
    private array $orderIds = [];

    /** @var string[] 用例创建的技师档案 ID，tearDown 统一清理 */
    private array $profileIds = [];

    protected function tearDown(): void
    {
        foreach ($this->orderIds as $id) {
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
        $request = new Request("POST / HTTP/1.1\r\n" . $head . "\r\n" . $body);
        return $request;
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    /** 造已审核技师档案（返回模型，id/user_id 均为测试段 snowflake 段） */
    private function makeTechnician(): TechnicianProfile
    {
        $profile = new TechnicianProfile();
        $profile->id       = TechnicianProfile::generateId();
        $profile->user_id  = (string) (9900000000000000 + random_int(1, 999999));
        $profile->real_name = '测试技师';
        $profile->gender   = 1;
        $profile->status   = 'approved';
        $profile->save();
        $this->profileIds[] = $profile->id;
        return $profile;
    }

    /** 造已支付订单 + 核销码记录（返回 [order, code]） */
    private function makePaidOrderWithCode(string $technicianId, string $status = Order::STATUS_PAID): array
    {
        $order = Order::create([
            'order_no'        => 'ORD_VER_' . uniqid(),
            'user_id'         => (string) (9900000000000000 + random_int(1, 999999)),
            'technician_id'   => $technicianId,
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => 100.0,
            'discount_amount' => 0.0,
            'paid_amount'     => 100.0,
            'status'          => $status,
        ]);
        $this->orderIds[] = $order->id;

        $code = bin2hex(random_bytes(16));
        OrderVerification::create([
            'id'       => OrderVerification::generateId(),
            'order_id' => $order->id,
            'code'     => $code,
        ]);

        return [$order, $code];
    }

    #[Test] public function verify_success_transitions_paid_to_serving(): void
    {
        $technician = $this->makeTechnician();
        [$order, $code] = $this->makePaidOrderWithCode($technician->id);

        $request = $this->makeRequest(['code' => $code]);
        $request->user_id = $technician->user_id;
        $resp = (new OrderController())->verifyByCode($request);

        $body = $this->body($resp);
        $this->assertSame(0, $body['code']);
        $this->assertSame('核销成功', $body['message']);

        // 状态推进 paid → serving + service_start_at
        $fresh = Order::find($order->id);
        $this->assertSame(Order::STATUS_SERVING, $fresh->status);
        $this->assertNotNull($fresh->service_start_at);

        // 核销记录落库
        $verification = OrderVerification::where('order_id', $order->id)->first();
        $this->assertNotNull($verification->verified_at);
        $this->assertSame($technician->user_id, (string) $verification->verified_by);
        $this->assertSame(OrderVerification::VERIFY_TYPE_SCAN, $verification->verify_type);

        // 站内消息通知用户（type=order）
        $this->assertTrue(
            Notification::where('order_id', $order->id)->where('type', 'order')->exists()
        );
    }

    #[Test] public function verify_rejected_for_non_owner_technician(): void
    {
        $owner = $this->makeTechnician();
        $intruder = $this->makeTechnician();
        [$order, $code] = $this->makePaidOrderWithCode($owner->id);

        $request = $this->makeRequest(['code' => $code]);
        $request->user_id = $intruder->user_id;
        $resp = (new OrderController())->verifyByCode($request);

        $body = $this->body($resp);
        $this->assertSame(403, $body['code']);
        $this->assertSame('无权限核销该订单', $body['message']);

        // 订单与核销记录均未推进
        $this->assertSame(Order::STATUS_PAID, Order::find($order->id)->status);
        $this->assertNull(OrderVerification::where('order_id', $order->id)->first()->verified_at);
    }

    #[Test] public function verify_idempotent_when_already_verified(): void
    {
        $technician = $this->makeTechnician();
        [$order, $code] = $this->makePaidOrderWithCode($technician->id);

        $controller = new OrderController();

        $first = $this->makeRequest(['code' => $code]);
        $first->user_id = $technician->user_id;
        $this->assertSame(0, $this->body($controller->verifyByCode($first))['code']);

        // 第二次核销：返回已核销（code=0，不报错），状态不再推进
        $second = $this->makeRequest(['code' => $code]);
        $second->user_id = $technician->user_id;
        $body = $this->body($controller->verifyByCode($second));
        $this->assertSame(0, $body['code']);
        $this->assertSame('该订单已核销', $body['message']);
        $this->assertTrue($body['data']['already_verified'] === true);
        $this->assertSame(Order::STATUS_SERVING, Order::find($order->id)->status);
    }

    #[Test] public function verify_rejected_when_order_not_paid(): void
    {
        $technician = $this->makeTechnician();
        [$order, $code] = $this->makePaidOrderWithCode($technician->id, Order::STATUS_PENDING);

        $request = $this->makeRequest(['code' => $code]);
        $request->user_id = $technician->user_id;
        $resp = (new OrderController())->verifyByCode($request);

        $body = $this->body($resp);
        $this->assertSame(400, $body['code']);
        $this->assertSame('当前订单状态不可核销', $body['message']);
        $this->assertSame(Order::STATUS_PENDING, Order::find($order->id)->status);
    }

    #[Test] public function verify_rejected_for_unknown_or_empty_code(): void
    {
        $technician = $this->makeTechnician();

        // 无效核销码
        $request = $this->makeRequest(['code' => 'deadbeef']);
        $request->user_id = $technician->user_id;
        $body = $this->body((new OrderController())->verifyByCode($request));
        $this->assertSame(404, $body['code']);
        $this->assertSame('核销码无效', $body['message']);

        // 空核销码
        $request2 = $this->makeRequest(['code' => '   ']);
        $request2->user_id = $technician->user_id;
        $body2 = $this->body((new OrderController())->verifyByCode($request2));
        $this->assertSame('核销码不能为空', $body2['message']);
    }
}
