<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\order\v1\controller\OrderController;
use app\model\Order;
use app\model\OrderPayment;
use app\model\OrderRefund;
use Webman\Http\Request;
use Webman\Http\Response;
use Carbon\Carbon;

/**
 * 两段式退款/取消（本次返工 M3）集成测试
 *
 * 覆盖 doCancel()/doRefund() 的「阶段二失败回滚」分支：
 * 事务外微信退款确定性失败（测试环境证书未配置 → refund() 返回 error，不发真实请求），
 * 断言退款单置 failed、订单回滚 PAID（cancel 分支同时清空取消标记）。
 *
 * 说明：控制器中两段式逻辑与 DB 事务/微信 IO 内联，无法抽取纯逻辑单测；
 * 此处通过反射直接驱动私有方法 + 真实 DB（与 WechatPayServiceTest 同基建），
 * 等价于最小集成测试。成功分支依赖真实微信退款响应，建议在集成环境覆盖。
 */
class OrderRefundFlowTest extends TestCase
{
    /** @var int[] 用例创建的订单 ID，tearDown 统一清理 */
    private array $orderIds = [];

    protected function tearDown(): void
    {
        foreach ($this->orderIds as $id) {
            OrderRefund::where('order_id', $id)->delete();
            OrderPayment::where('order_id', $id)->delete();
            Order::where('id', $id)->delete();
        }
        $this->orderIds = [];
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

    /** 造已支付订单 + 成功支付记录（返回订单模型） */
    private function makePaidOrder(): Order
    {
        $order = Order::create([
            'order_no'        => 'ORD_TEST_' . uniqid(),
            'user_id'         => (string) (9900000000000000 + random_int(1, 999999)),
            'technician_id'   => (string) (9900000000000000 + random_int(1, 999999)),
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => 100.0,
            'discount_amount' => 0.0,
            'paid_amount'     => 100.0,
            'status'          => Order::STATUS_PAID,
            'service_time'    => Carbon::now()->addHours(12), // 全额退款比例 1.00
            'created_at'      => Carbon::now()->subMinutes(30),
        ]);
        $this->orderIds[] = $order->id;

        OrderPayment::create([
            'order_id'   => $order->id,
            'payment_no' => 'PAYTEST_' . uniqid(),
            'pay_type'   => 'wechat',
            'amount'     => 100.0,
            'status'     => OrderPayment::STATUS_SUCCESS,
            'paid_at'    => date('Y-m-d H:i:s'),
        ]);

        return $order;
    }

    private static function invokePrivate(OrderController $ctl, string $method, array $args = []): mixed
    {
        $m = new \ReflectionMethod(OrderController::class, $method);
        $m->setAccessible(true);
        return $m->invokeArgs($ctl, $args);
    }

    // ── doCancel：阶段二退款失败 → 退款单 failed + 订单回滚 PAID ──

    #[Test] public function cancel_rolls_back_to_paid_when_refund_fails(): void
    {
        $order = $this->makePaidOrder();

        $resp = self::invokePrivate(new OrderController(), 'doCancel', [
            $this->makeRequest(['cancel_reason' => '测试取消']),
            $order,
        ]);

        // 失败语义：提示重试
        $this->assertSame('退款处理失败请重试', $this->body($resp)['message']);

        // 退款单置 failed（不重复建单）
        $refunds = OrderRefund::where('order_id', $order->id)->get();
        $this->assertCount(1, $refunds);
        $this->assertSame(OrderRefund::STATUS_FAILED, $refunds->first()->status);

        // 订单回滚 PAID，取消标记清空（可重试）
        $fresh = Order::find($order->id);
        $this->assertSame(Order::STATUS_PAID, $fresh->status);
        $this->assertSame('', $fresh->cancel_reason); // 列 NOT NULL，回滚置空串
        $this->assertNull($fresh->cancel_at);
    }

    // ── doRefund：阶段二退款失败 → 退款单 failed + 订单回滚 PAID ──

    #[Test] public function refund_rolls_back_to_paid_when_refund_fails(): void
    {
        $order = $this->makePaidOrder();

        $resp = self::invokePrivate(new OrderController(), 'doRefund', [
            $this->makeRequest(['reason' => '测试退款']),
            $order,
            1.0,
        ]);

        $this->assertSame('退款处理失败请重试', $this->body($resp)['message']);

        $refunds = OrderRefund::where('order_id', $order->id)->get();
        $this->assertCount(1, $refunds);
        $this->assertSame(OrderRefund::STATUS_FAILED, $refunds->first()->status);

        // 订单从 refunding 回滚 PAID，避免永久卡 REFUNDING
        $fresh = Order::find($order->id);
        $this->assertSame(Order::STATUS_PAID, $fresh->status);
    }
}
