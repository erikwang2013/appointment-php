<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\model\Order;
use app\model\OrderPayment;
use app\model\User;
use app\model\UserPoints;
use app\order\v1\controller\OrderController;
use support\Container;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 积分抵现（OrderController::pay use_points）测试
 *
 * 覆盖：余额不足 422；抵扣金额计算与支付金额扣减；消费流水写入（含重试幂等）；
 * 抵扣按应付满减（至少保留 0.01 元）；use_points 缺省 0 时 pay 行为不变。
 *
 * 说明：微信统一下单为真实网络调用（WechatPayService::unifiedOrder），测试环境不可达；
 * 用例统一在「微信调用前」断言——用户无 wx_openid 时 pay 在预支付前即返回错误，
 * 但积分校验/抵扣额/支付记录金额/消费流水均已在锁内落库，抵扣链路可完整验证。
 */
class OrderPointsPayTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理 */
    private array $userIds = [];

    /** @var string[] 用例订单 ID，tearDown 统一清理 */
    private array $orderIds = [];

    protected function tearDown(): void
    {
        foreach ($this->orderIds as $id) {
            UserPoints::where('order_id', $id)->delete();
            OrderPayment::where('order_id', $id)->delete();
            Order::where('id', $id)->delete();
        }
        foreach ($this->userIds as $id) {
            UserPoints::where('user_id', $id)->delete();
            User::where('id', $id)->delete();
        }
        $this->orderIds = [];
        $this->userIds = [];
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

    /** 造用户（wx_openid 留空：pay 在微信预支付前即返回，便于断言抵扣链路） */
    private function makeUser(): User
    {
        $user = User::create([
            'phone'     => '199' . substr((string) random_int(10000000, 99999999), 0, 8),
            'wx_openid' => '',
            'user_type' => 'user',
            'status'    => 1,
        ]);
        $this->userIds[] = (string) $user->id;
        return $user;
    }

    /** 造待支付订单 */
    private function makePendingOrder(string $userId, float $paidAmount): Order
    {
        $order = Order::create([
            'order_no'        => 'ORD_PTSOFF_' . uniqid(),
            'user_id'         => $userId,
            'technician_id'   => '0',
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => $paidAmount,
            'discount_amount' => 0.0,
            'paid_amount'     => $paidAmount,
            'status'          => Order::STATUS_PENDING,
        ]);
        $this->orderIds[] = (string) $order->id;
        return $order;
    }

    /** 造积分（earn 流水，balance 仅作单次增量快照） */
    private function creditPoints(string $userId, int $points): void
    {
        $row = new UserPoints();
        $row->id          = UserPoints::generateId();
        $row->user_id     = $userId;
        $row->type        = 'earn';
        $row->points      = $points;
        $row->balance     = $points;
        $row->source      = 'check_in';
        $row->description = '测试签到积分';
        $row->save();
    }

    /** 调 OrderController::pay（hashid 编码订单 ID）并返回响应 body */
    private function pay(string $userId, Order $order, array $post = []): array
    {
        $request = $this->makeRequest($post);
        $request->user_id = $userId;
        $hashid = Container::get('hashids')->encode((int) $order->id);
        return $this->body((new OrderController())->pay($request, (string) $hashid));
    }

    #[Test] public function insufficient_points_returns_422(): void
    {
        $user  = $this->makeUser();
        $order = $this->makePendingOrder((string) $user->id, 100.0);
        $this->creditPoints((string) $user->id, 50);

        $resp = $this->pay((string) $user->id, $order, ['use_points' => 100]);

        $this->assertSame(422, $resp['code'], json_encode($resp));
        $this->assertStringContainsString('积分不足', (string) $resp['message']);
        // 校验失败无任何副作用：无消费流水、无支付记录
        $this->assertSame(0, UserPoints::where('order_id', $order->id)->count());
        $this->assertNull(OrderPayment::where('order_id', $order->id)->first());
    }

    #[Test] public function points_offset_reduces_pay_amount_and_writes_consume_txn(): void
    {
        $user  = $this->makeUser();
        $order = $this->makePendingOrder((string) $user->id, 30.0);
        $this->creditPoints((string) $user->id, 1000);

        // 250 积分 → floor(250/100) = 2 元抵扣，实付 28.00；实际消耗 200 积分
        $resp = $this->pay((string) $user->id, $order, ['use_points' => 250]);

        // 无 wx_openid → pay 在微信预支付前返回（但抵扣已落库）
        $this->assertNotSame(0, $resp['code'], json_encode($resp));
        $this->assertStringContainsString('微信信息缺失', (string) $resp['message']);

        $payment = OrderPayment::where('order_id', $order->id)->first();
        $this->assertNotNull($payment);
        $this->assertSame(28.0, (float) $payment->amount, '支付金额 = 30 - 2 = 28');

        $txn = UserPoints::where('order_id', $order->id)->where('source', 'points_offset')->first();
        $this->assertNotNull($txn);
        $this->assertSame('consume', $txn->type);
        $this->assertSame(-200, (int) $txn->points);
        $this->assertSame(800, (int) $txn->balance, '余额快照 = 1000 - 200');

        // 重试支付不重复扣积分（幂等）
        $this->pay((string) $user->id, $order, ['use_points' => 250]);
        $this->assertSame(1, UserPoints::where('order_id', $order->id)->where('source', 'points_offset')->count());
    }

    #[Test] public function offset_capped_to_keep_at_least_0_01(): void
    {
        $user  = $this->makeUser();
        $order = $this->makePendingOrder((string) $user->id, 1.5);
        $this->creditPoints((string) $user->id, 10000);

        $resp = $this->pay((string) $user->id, $order, ['use_points' => 10000]);

        $this->assertNotSame(0, $resp['code'], json_encode($resp));
        $payment = OrderPayment::where('order_id', $order->id)->first();
        $this->assertNotNull($payment);
        $this->assertSame(0.01, (float) $payment->amount, '抵扣后至少保留 0.01 元');
        $txn = UserPoints::where('order_id', $order->id)->where('source', 'points_offset')->first();
        $this->assertNotNull($txn);
        $this->assertSame(-149, (int) $txn->points, '按应付满减：1.5 元最多抵扣 1.49 元 = 149 积分');
    }

    #[Test] public function default_without_use_points_keeps_original_behavior(): void
    {
        $user  = $this->makeUser();
        $order = $this->makePendingOrder((string) $user->id, 100.0);
        $this->creditPoints((string) $user->id, 1000);

        $resp = $this->pay((string) $user->id, $order);

        $this->assertNotSame(0, $resp['code'], json_encode($resp));
        $this->assertStringContainsString('微信信息缺失', (string) $resp['message']);

        // 支付记录金额 = 订单应付（未扣减）；未写任何积分流水
        $payment = OrderPayment::where('order_id', $order->id)->first();
        $this->assertNotNull($payment);
        $this->assertSame(100.0, (float) $payment->amount);
        $this->assertSame(0, UserPoints::where('order_id', $order->id)->count());
    }
}
