<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\model\Notification;
use app\model\Order;
use app\model\OrderPayment;
use app\model\OrderRefund;
use app\model\UserPoints;
use app\model\UserWallet;
use app\model\WalletTxn;
use app\order\v1\controller\OrderController;
use support\Container;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 积分抵现回补（订单取消全额退还 / 退款按比例退还）集成测试
 *
 * 覆盖：
 * - 取消订单：points_offset 消费积分全额回补（消费 -100，回补 +100，退款比例 < 1 也全额）
 * - 退款（比例 0.90）：按 floor(原扣点 × 退款金额/实付) 比例回补（-100 → +90）
 * - 幂等：重复取消/重复退款不重复回补（回补流水仅一条）
 * - 无积分抵现的订单取消/退款无副作用（无新增积分流水）
 *
 * 依赖真实 DB / Redis（与 PointsRefundTest / OrderPointsPayTest 同基建）。
 * 订单走 balance 渠道支付（无微信 IO，回补逻辑可完整落库验证）。
 */
class OrderPointsRefundTest extends TestCase
{
    /** @var string[] 用例创建的订单 ID，tearDown 统一清理 */
    private array $orderIds = [];

    /** @var string[] 用例创建的测试用户 ID（含钱包），tearDown 统一清理 */
    private array $userIds = [];

    protected function tearDown(): void
    {
        foreach ($this->orderIds as $id) {
            UserPoints::where('order_id', $id)->delete();
            Notification::where('order_id', $id)->delete();
            WalletTxn::where('order_id', $id)->delete();
            OrderPayment::where('order_id', $id)->delete();
            OrderRefund::where('order_id', $id)->delete();
            Order::where('id', $id)->delete();
        }
        foreach ($this->userIds as $id) {
            UserPoints::where('user_id', $id)->delete();
            UserWallet::where('user_id', $id)->delete();
            WalletTxn::where('user_id', $id)->delete();
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

    private function makeTestUserId(): string
    {
        $userId = (string) (9900000000000000 + random_int(1, 999999));
        $this->userIds[] = $userId;
        return $userId;
    }

    /** 造已支付订单（balance 渠道退款，无微信 IO）+ 钱包；created_at/service_time 控制退款比例 */
    private function makePaidBalanceOrder(float $paidAmount, ?string $serviceTime = null, ?string $createdAt = null): Order
    {
        $userId = $this->makeTestUserId();
        $data = [
            'order_no'        => 'ORD_PTSREF_' . uniqid(),
            'user_id'         => $userId,
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => $paidAmount,
            'discount_amount' => 0.0,
            'paid_amount'     => $paidAmount,
            'status'          => Order::STATUS_PAID,
        ];
        // 仅显式传入非 null 值——created_at 为 NOT NULL，传 null 会覆盖 Eloquent 自动填充
        if ($serviceTime !== null) {
            $data['service_time'] = $serviceTime;
        }
        if ($createdAt !== null) {
            $data['created_at'] = $createdAt;
        }
        $order = Order::create($data);
        $this->orderIds[] = $order->id;

        OrderPayment::create([
            'id'         => OrderPayment::generateId(),
            'order_id'   => $order->id,
            'payment_no' => OrderPayment::generatePaymentNo(),
            'pay_type'   => 'balance',
            'amount'     => $paidAmount,
            'status'     => OrderPayment::STATUS_SUCCESS,
        ]);

        UserWallet::create([
            'user_id'        => $userId,
            'balance'        => 0.00,
            'total_recharge' => 0.00,
            'total_consume'  => 0.00,
        ]);

        return $order;
    }

    /** 模拟积分抵现消费（与 applyPointsOffset 同落库形态）：先造余额再扣减 */
    private function offsetPoints(Order $order, int $points): void
    {
        // 造余额（500），后续回补断言余额快照 = 500 - 扣点 + 回补
        $earn = UserPoints::create([
            'id'          => UserPoints::generateId(),
            'user_id'     => $order->user_id,
            'type'        => 'earn',
            'points'      => 500,
            'balance'     => 500,
            'source'      => 'check_in',
            'description' => '测试签到积分',
        ]);
        // created_at 不在 fillable：显式后置，早于消费流水，保证余额快照链条顺序确定
        $earn->created_at = date('Y-m-d H:i:s', time() - 5);
        $earn->save();

        UserPoints::create([
            'id'          => UserPoints::generateId(),
            'user_id'     => $order->user_id,
            'type'        => 'consume',
            'points'      => -$points,
            'balance'     => 500 - $points,
            'source'      => 'points_offset',
            'order_id'    => $order->id,
            'description' => '积分抵扣订单 ' . $order->order_no,
        ]);
    }

    /** 发起取消（URL 参数需 hashid 编码，与控制器 decodeId 对应） */
    private function cancel(Order $order, string $reason = '测试取消'): Response
    {
        $req = $this->makeRequest(['cancel_reason' => $reason]);
        $req->user_id = $order->user_id;
        $encodedId = Container::get('hashids')->encode((int) $order->id);
        return (new OrderController())->cancel($req, (string) $encodedId);
    }

    /** 发起退款（URL 参数需 hashid 编码，与控制器 decodeId 对应） */
    private function refund(Order $order, string $reason = '测试退款'): Response
    {
        $req = $this->makeRequest(['reason' => $reason]);
        $req->user_id = $order->user_id;
        $encodedId = Container::get('hashids')->encode((int) $order->id);
        return (new OrderController())->refund($req, (string) $encodedId);
    }

    private function refundRows(Order $order): \Illuminate\Support\Collection
    {
        return UserPoints::where('order_id', $order->id)
            ->where('source', 'points_refund')
            ->get();
    }

    #[Test] public function cancel_refunds_all_offset_points(): void
    {
        // 下单 1 小时前 + 距服务 2 小时 → 退款比例 0.90（现金退 90 元），取消仍全额退还抵现积分
        $createdAt   = date('Y-m-d H:i:s', time() - 3600);
        $serviceTime = date('Y-m-d H:i:s', time() + 7200);
        $order = $this->makePaidBalanceOrder(100.0, $serviceTime, $createdAt);
        $this->offsetPoints($order, 100);

        $resp = $this->cancel($order);
        $this->assertSame(0, $this->body($resp)['code'], json_encode($this->body($resp)));

        // 回补流水：type=earn / source=points_refund / points=+100，余额快照 = 500 - 100 + 100
        $refund = $this->refundRows($order)->first();
        $this->assertNotNull($refund);
        $this->assertSame('earn', $refund->type);
        $this->assertSame(100, (int) $refund->points);
        $this->assertSame(500, (int) $refund->balance);
        $this->assertStringContainsString('订单取消退还积分', (string) $refund->description);
    }

    #[Test] public function refund_returns_offset_points_proportionally(): void
    {
        $createdAt   = date('Y-m-d H:i:s', time() - 3600);
        $serviceTime = date('Y-m-d H:i:s', time() + 7200);
        $order = $this->makePaidBalanceOrder(100.0, $serviceTime, $createdAt);
        $this->offsetPoints($order, 100);

        $resp = $this->refund($order);
        $this->assertSame(0, $this->body($resp)['code'], json_encode($this->body($resp)));

        // 比例 0.90：回补 = floor(100 × 90 / 100) = 90，余额快照 = 500 - 100 + 90
        $refund = $this->refundRows($order)->first();
        $this->assertNotNull($refund);
        $this->assertSame('earn', $refund->type);
        $this->assertSame(90, (int) $refund->points);
        $this->assertSame(490, (int) $refund->balance);
        $this->assertStringContainsString('订单退款退还积分', (string) $refund->description);
    }

    #[Test] public function repeated_cancel_or_refund_does_not_return_points_twice(): void
    {
        $createdAt   = date('Y-m-d H:i:s', time() - 3600);
        $serviceTime = date('Y-m-d H:i:s', time() + 7200);
        $order = $this->makePaidBalanceOrder(100.0, $serviceTime, $createdAt);
        $this->offsetPoints($order, 100);

        $this->assertSame(0, $this->body($this->cancel($order))['code']);
        $this->assertSame(1, $this->refundRows($order)->count());

        // 订单已终态：重复取消被拒，不产生第二笔回补
        $resp2 = $this->cancel($order);
        $this->assertNotSame(0, $this->body($resp2)['code']);
        $this->assertSame(1, $this->refundRows($order)->count());

        // 重复退款同样被拒（订单已 refunded），回补流水仍仅一条
        $resp3 = $this->refund($order);
        $this->assertNotSame(0, $this->body($resp3)['code']);
        $this->assertSame(1, $this->refundRows($order)->count());
    }

    #[Test] public function cancel_or_refund_without_points_offset_has_no_side_effect(): void
    {
        $createdAt   = date('Y-m-d H:i:s', time() - 3600);
        $serviceTime = date('Y-m-d H:i:s', time() + 7200);

        $order = $this->makePaidBalanceOrder(100.0, $serviceTime, $createdAt);
        $this->assertSame(0, $this->body($this->cancel($order))['code']);
        // 未抵现 → 无任何积分流水
        $this->assertSame(0, UserPoints::where('order_id', $order->id)->count());

        $order2 = $this->makePaidBalanceOrder(100.0, $serviceTime, $createdAt);
        $this->assertSame(0, $this->body($this->refund($order2))['code']);
        $this->assertSame(0, UserPoints::where('order_id', $order2->id)->count());
    }
}
