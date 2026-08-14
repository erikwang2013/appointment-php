<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\model\CheckIn;
use app\model\Notification;
use app\model\Order;
use app\model\OrderPayment;
use app\model\OrderRefund;
use app\model\UserPoints;
use app\model\UserWallet;
use app\model\WalletTxn;
use app\order\v1\controller\OrderController;
use app\user\v1\controller\CheckInController;
use support\Container;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 积分回扣 + 签到口径统一（第 10 轮）集成测试
 *
 * 覆盖：
 * - 全额退款回扣：已返积分按 1:1 回扣，balance 快照回退到 0
 * - 部分退款（ratio 0.9）：回扣 = floor(已返 × 退款金额 / 实付)，balance 正确
 * - 重复退款不重复回扣：订单 refunded 后不可再退，回扣流水仅一条
 * - 未返积分订单退款：不回扣（无副作用）
 * - 签到积分 type 统一为 earn（与订单返积分口径一致，历史 'income' 数据不动）
 *
 * 依赖真实 DB / Redis（与 PointsFlowTest / OrderRefundFlowTest 同基建）。
 */
class PointsRefundTest extends TestCase
{
    /** @var string[] 用例创建的订单 ID，tearDown 统一清理 */
    private array $orderIds = [];

    /** @var string[] 用例创建的测试用户 ID（含钱包/签到），tearDown 统一清理 */
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
            CheckIn::where('user_id', $id)->delete();
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
            'order_no'        => 'ORD_RFD_' . uniqid(),
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

    /** 模拟订单已返积分（与 rewardOrderPoints 同落库形态） */
    private function awardOrderPoints(Order $order, int $points): void
    {
        $lastBalance = (int) (UserPoints::where('user_id', $order->user_id)
            ->orderBy('created_at', 'desc')
            ->value('balance') ?? 0);
        UserPoints::create([
            'id'          => UserPoints::generateId(),
            'user_id'     => $order->user_id,
            'type'        => 'earn',
            'points'      => $points,
            'balance'     => $lastBalance + $points,
            'source'      => 'order',
            'order_id'    => $order->id,
            'description' => '订单消费返积分（订单 ' . $order->order_no . '）',
        ]);
    }

    /** 发起退款（URL 参数需 hashid 编码，与控制器 decodeId 对应） */
    private function refund(Order $order, string $reason = '测试退款'): Response
    {
        $req = $this->makeRequest(['reason' => $reason]);
        $req->user_id = $order->user_id;
        $encodedId = Container::get('hashids')->encode((int) $order->id);
        return (new OrderController())->refund($req, (string) $encodedId);
    }

    private function clawbackRows(Order $order): \Illuminate\Support\Collection
    {
        return UserPoints::where('order_id', $order->id)
            ->where('source', 'order')
            ->where('type', 'use')
            ->get();
    }

    #[Test] public function full_refund_clawbacks_all_awarded_points(): void
    {
        $order = $this->makePaidBalanceOrder(100.0);
        $this->awardOrderPoints($order, 100);

        $resp = $this->refund($order);
        $this->assertSame(0, $this->body($resp)['code']);

        // 回扣流水：type=use / source=order / points=-100，balance 快照归零
        $clawback = $this->clawbackRows($order)->first();
        $this->assertNotNull($clawback);
        $this->assertSame(-100, (int) $clawback->points);
        $this->assertSame(0, (int) $clawback->balance);

        // 返积分流水余额保持 100，快照链条正确
        $earn = UserPoints::where('order_id', $order->id)
            ->where('source', 'order')->where('type', 'earn')->first();
        $this->assertNotNull($earn);
        $this->assertSame(100, (int) $earn->balance);

        // description 关联退款单号
        $refund = OrderRefund::where('order_id', $order->id)->first();
        $this->assertNotNull($refund);
        $this->assertStringContainsString($refund->refund_no, (string) $clawback->description);
    }

    #[Test] public function partial_refund_clawbacks_proportionally(): void
    {
        // 下单 1 小时前 + 距服务 2 小时 → 退款比例 0.90，退款金额 90 元
        $createdAt   = date('Y-m-d H:i:s', time() - 3600);
        $serviceTime = date('Y-m-d H:i:s', time() + 7200);
        $order = $this->makePaidBalanceOrder(100.0, $serviceTime, $createdAt);
        $this->awardOrderPoints($order, 100);

        $resp = $this->refund($order);
        $this->assertSame(0, $this->body($resp)['code']);

        // 回扣 = floor(100 × 90 / 100) = 90，balance 快照 = 100 - 90 = 10
        $clawback = $this->clawbackRows($order)->first();
        $this->assertNotNull($clawback);
        $this->assertSame(-90, (int) $clawback->points);
        $this->assertSame(10, (int) $clawback->balance);
    }

    #[Test] public function repeated_refund_does_not_clawback_twice(): void
    {
        $order = $this->makePaidBalanceOrder(100.0);
        $this->awardOrderPoints($order, 100);

        $this->assertSame(0, $this->body($this->refund($order))['code']);
        $this->assertSame(1, $this->clawbackRows($order)->count());

        // 订单已 refunded，再次退款被拒，不产生第二笔回扣
        $resp2 = $this->refund($order);
        $this->assertNotSame(0, $this->body($resp2)['code']);
        $this->assertSame(1, $this->clawbackRows($order)->count());

        // 返积分流水仍只有一条
        $this->assertSame(1, UserPoints::where('order_id', $order->id)
            ->where('source', 'order')->where('type', 'earn')->count());
    }

    #[Test] public function refund_without_awarded_points_has_no_side_effect(): void
    {
        $order = $this->makePaidBalanceOrder(100.0);

        $resp = $this->refund($order);
        $this->assertSame(0, $this->body($resp)['code']);

        // 未返积分 → 不回扣，无任何积分流水
        $this->assertSame(0, UserPoints::where('order_id', $order->id)->count());
    }

    #[Test] public function check_in_points_use_earn_type(): void
    {
        $userId = $this->makeTestUserId();
        $req = $this->makeRequest();
        $req->user_id = $userId;
        $resp = (new CheckInController())->store($req);
        $this->assertSame(0, $this->body($resp)['code']);

        $row = UserPoints::where('user_id', $userId)->where('source', 'check_in')->first();
        $this->assertNotNull($row);
        $this->assertSame('earn', $row->type);
        $this->assertSame(10, (int) $row->points);
        $this->assertSame(10, (int) $row->balance);
    }
}
