<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\order\v1\controller\OrderController;
use app\model\Order;
use app\model\OrderPayment;
use app\model\OrderRefund;
use support\Container;
use support\Db;
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

    /** @var string[] 用例创建的优惠券记录 ID，tearDown 统一清理 */
    private array $couponIds = [];

    protected function tearDown(): void
    {
        foreach ($this->orderIds as $id) {
            Db::table('appointment_notification')->where('order_id', $id)->delete();
            OrderRefund::where('order_id', $id)->delete();
            OrderPayment::where('order_id', $id)->delete();
            Order::where('id', $id)->delete();
        }
        foreach ($this->couponIds as $cid) {
            Db::table('appointment_user_coupon')->where('id', $cid)->delete();
        }
        $this->orderIds = [];
        $this->couponIds = [];
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

    // ── B3: 部分退款不归还优惠（券/次卡）──

    #[Test] public function should_restore_benefits_only_for_full_refund(): void
    {
        $ctl = new OrderController();
        $this->assertTrue((bool) self::invokePrivate($ctl, 'shouldRestoreBenefits', [1.0]));
        $this->assertTrue((bool) self::invokePrivate($ctl, 'shouldRestoreBenefits', [1.00]));
        $this->assertFalse((bool) self::invokePrivate($ctl, 'shouldRestoreBenefits', [0.9]));
        $this->assertFalse((bool) self::invokePrivate($ctl, 'shouldRestoreBenefits', [0.0]));
    }

    #[Test] public function calc_refund_amount_uses_shared_helper(): void
    {
        $ctl = new OrderController();
        $order = $this->makePaidOrder();
        $this->assertSame(100.0, self::invokePrivate($ctl, 'calcRefundAmount', [$order, 1.0]));
        $this->assertSame(90.0, self::invokePrivate($ctl, 'calcRefundAmount', [$order, 0.9]));
    }

    // ── B4: 退款补偿（微信已退款但落库失败 → 幂等补写）──

    /** 造 used 优惠券记录（供归还断言） */
    private function makeUsedCoupon(int $userId): string
    {
        $id = (string) (9900000000000000 + random_int(1, 999999));
        Db::table('appointment_user_coupon')->insert([
            'id' => $id,
            'user_id' => $userId,
            'coupon_id' => $id,
            'status' => 'used',
            'used_at' => date('Y-m-d H:i:s'),
            'received_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->couponIds[] = $id;
        return $id;
    }

    /** 造滞留单：refunding/cancelled + pending 退款单（11 分钟前，超过补偿阈值） */
    private function makeStuckRefundOrder(string $orderStatus, float $ratio): Order
    {
        $userId = 9900000000000000 + random_int(1, 999999);
        $couponId = $this->makeUsedCoupon($userId);

        $order = Order::create([
            'order_no'        => 'ORD_STUCK_' . uniqid(),
            'user_id'         => (string) $userId,
            'technician_id'   => (string) (9900000000000000 + random_int(1, 999999)),
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => 100.0,
            'discount_amount' => 0.0,
            'paid_amount'     => 100.0,
            'user_coupon_id'  => (int) $couponId,
            'status'          => $orderStatus,
            'created_at'      => Carbon::now()->subMinutes(30),
        ]);
        $this->orderIds[] = $order->id;

        $payment = OrderPayment::create([
            'order_id'   => $order->id,
            'payment_no' => 'PAYSTUCK_' . uniqid(),
            'pay_type'   => 'wechat',
            'amount'     => 100.0,
            'status'     => OrderPayment::STATUS_SUCCESS,
            'paid_at'    => date('Y-m-d H:i:s'),
        ]);

        $refund = OrderRefund::create([
            'id'         => OrderRefund::generateId(),
            'order_id'   => $order->id,
            'payment_id' => $payment->id,
            'refund_no'  => OrderRefund::generateRefundNo(),
            'amount'     => round(100.0 * $ratio, 2),
            'ratio'      => $ratio,
            'reason'     => '落库失败滞留',
            'status'     => OrderRefund::STATUS_PENDING,
        ]);
        // 回拨 created_at 越过补偿阈值（600s）
        Db::table('appointment_order_refund')
            ->where('id', $refund->id)
            ->update(['created_at' => date('Y-m-d H:i:s', time() - 660)]);

        return $order;
    }

    #[Test] public function compensation_completes_stuck_refunding_order_and_restores_coupon(): void
    {
        $order = $this->makeStuckRefundOrder(Order::STATUS_REFUNDING, 1.0);

        (new OrderController())->completeRefundCompensation();

        // 退款单补写 success
        $refund = OrderRefund::where('order_id', $order->id)->first();
        $this->assertSame(OrderRefund::STATUS_SUCCESS, $refund->status);
        $this->assertNotNull($refund->refunded_at);
        // 订单 refunding → refunded（不再卡死）
        $this->assertSame(Order::STATUS_REFUNDED, Order::find($order->id)->status);
        // 全额退款归还券
        $coupon = Db::table('appointment_user_coupon')->where('id', $order->user_coupon_id)->first();
        $this->assertSame('available', $coupon->status);
        $this->assertNull($coupon->used_at);
    }

    #[Test] public function compensation_is_idempotent(): void
    {
        $order = $this->makeStuckRefundOrder(Order::STATUS_REFUNDING, 1.0);
        $ctl = new OrderController();

        $ctl->completeRefundCompensation();
        $refund = OrderRefund::where('order_id', $order->id)->first();
        $this->assertSame(OrderRefund::STATUS_SUCCESS, $refund->status);

        // 再次扫描：退款单已 success，不得重复处理/重复归还
        $couponId = $order->user_coupon_id;
        $ctl->completeRefundCompensation();
        $this->assertSame(OrderRefund::STATUS_SUCCESS, OrderRefund::find($refund->id)->status);
        $this->assertSame('available', Db::table('appointment_user_coupon')->where('id', $couponId)->value('status'));
        $this->assertSame(Order::STATUS_REFUNDED, Order::find($order->id)->status);
    }

    #[Test] public function compensation_keeps_coupon_for_partial_refund(): void
    {
        // B3/B4 联动：0.9 部分退款补偿后订单 refunded，但券不归还
        $order = $this->makeStuckRefundOrder(Order::STATUS_REFUNDING, 0.9);

        (new OrderController())->completeRefundCompensation();

        $this->assertSame(Order::STATUS_REFUNDED, Order::find($order->id)->status);
        $this->assertSame('used', Db::table('appointment_user_coupon')->where('id', $order->user_coupon_id)->value('status'));
    }

    #[Test] public function compensation_keeps_cancelled_terminal_state_and_restores_benefits(): void
    {
        // doCancel 落库失败：订单滞留 cancelled + pending 退款单 → 补偿补写退款单并归还券，不覆盖终态
        $order = $this->makeStuckRefundOrder(Order::STATUS_CANCELLED, 1.0);

        (new OrderController())->completeRefundCompensation();

        $refund = OrderRefund::where('order_id', $order->id)->first();
        $this->assertSame(OrderRefund::STATUS_SUCCESS, $refund->status);
        $this->assertSame(Order::STATUS_CANCELLED, Order::find($order->id)->status);
        $this->assertSame('available', Db::table('appointment_user_coupon')->where('id', $order->user_coupon_id)->value('status'));
    }

    // ── 退款信息入契约：show()/index() 返回 refund_status/refund_amount/refunded_at ──

    /** 造退款记录（订单已有支付记录） */
    private function makeRefund(Order $order, string $status, ?string $refundedAt = null): OrderRefund
    {
        $payment = OrderPayment::where('order_id', $order->id)->first();
        return OrderRefund::create([
            'id'          => OrderRefund::generateId(),
            'order_id'    => $order->id,
            'payment_id'  => $payment->id,
            'refund_no'   => OrderRefund::generateRefundNo(),
            'amount'      => 100.0,
            'ratio'       => 1.0,
            'reason'      => '测试退款',
            'status'      => $status,
            'refunded_at' => $refundedAt,
        ]);
    }

    #[Test] public function show_returns_refund_fields_from_latest_refund(): void
    {
        $order = $this->makePaidOrder();
        $this->makeRefund($order, OrderRefund::STATUS_SUCCESS, date('Y-m-d H:i:s', time() - 60));

        $request = $this->makeRequest();
        $request->user_id = $order->user_id;
        $id = Container::get('hashids')->encode((int) $order->id);
        $data = $this->body((new OrderController())->show($request, (string) $id))['data'];

        $this->assertSame(OrderRefund::STATUS_SUCCESS, $data['refund_status']);
        $this->assertSame(100.0, (float) $data['refund_amount']);
        $this->assertNotEmpty($data['refunded_at']);
    }

    #[Test] public function show_returns_null_refund_fields_when_no_refund(): void
    {
        $order = $this->makePaidOrder();

        $request = $this->makeRequest();
        $request->user_id = $order->user_id;
        $id = Container::get('hashids')->encode((int) $order->id);
        $data = $this->body((new OrderController())->show($request, (string) $id))['data'];

        $this->assertNull($data['refund_status']);
        $this->assertNull($data['refund_amount']);
        $this->assertNull($data['refunded_at']);
    }

    #[Test] public function index_prefetches_refund_fields_per_order(): void
    {
        // 同用户两单：一单 pending 退款、一单无退款（验证批量预取 + 空值形状）
        $userId = (string) (9900000000000000 + random_int(1, 999999));
        $orderA = $this->makePaidOrder();
        $orderA->user_id = $userId;
        $orderA->save();
        $orderB = $this->makePaidOrder();
        $orderB->user_id = $userId;
        $orderB->save();
        $this->makeRefund($orderA, OrderRefund::STATUS_PENDING);

        $request = $this->makeRequest(['per_page' => 15]);
        $request->user_id = $userId;
        $items = $this->body((new OrderController())->index($request))['data'];

        $this->assertCount(2, $items);
        $byNo = [];
        foreach ($items as $item) {
            $byNo[$item['order_no']] = $item;
        }
        $this->assertSame(OrderRefund::STATUS_PENDING, $byNo[$orderA->order_no]['refund_status']);
        $this->assertSame(100.0, (float) $byNo[$orderA->order_no]['refund_amount']);
        $this->assertNull($byNo[$orderA->order_no]['refunded_at']);
        $this->assertNull($byNo[$orderB->order_no]['refund_status']);
        $this->assertNull($byNo[$orderB->order_no]['refund_amount']);
        $this->assertNull($byNo[$orderB->order_no]['refunded_at']);
    }

    // ── 退款站内通知（受理/到账）与幂等 ──

    #[Test] public function doRefund_writes_accepted_notification_on_apply(): void
    {
        // 测试环境微信退款必失败：阶段一受理通知已写，阶段二失败不写「到账」
        $order = $this->makePaidOrder();

        self::invokePrivate(new OrderController(), 'doRefund', [
            $this->makeRequest(['reason' => '测试退款']),
            $order,
            1.0,
        ]);

        $notifications = Db::table('appointment_notification')->where('order_id', $order->id)->get();
        $this->assertCount(1, $notifications);
        $this->assertSame('order', $notifications->first()->type);
        $this->assertSame('退款申请已受理', $notifications->first()->title);
        $this->assertStringContainsString('100.00', (string) $notifications->first()->content);
    }

    #[Test] public function doCancel_writes_accepted_notification_on_refund_path(): void
    {
        $order = $this->makePaidOrder();

        self::invokePrivate(new OrderController(), 'doCancel', [
            $this->makeRequest(['cancel_reason' => '测试取消']),
            $order,
        ]);

        $count = Db::table('appointment_notification')
            ->where('order_id', $order->id)
            ->where('title', '退款申请已受理')
            ->count();
        $this->assertSame(1, $count);
    }

    #[Test] public function write_refund_notification_derives_title_by_status(): void
    {
        $order = $this->makePaidOrder();
        $ctl = new OrderController();

        // pending → 受理
        $pending = $this->makeRefund($order, OrderRefund::STATUS_PENDING);
        self::invokePrivate($ctl, 'writeRefundNotification', [$order, $pending]);

        // success → 到账
        $success = $this->makeRefund($order, OrderRefund::STATUS_SUCCESS, date('Y-m-d H:i:s'));
        self::invokePrivate($ctl, 'writeRefundNotification', [$order, $success]);

        // failed → 不写通知
        $failed = $this->makeRefund($order, OrderRefund::STATUS_FAILED);
        self::invokePrivate($ctl, 'writeRefundNotification', [$order, $failed]);

        $this->assertSame(1, Db::table('appointment_notification')->where('order_id', $order->id)->where('title', '退款申请已受理')->count());
        $this->assertSame(1, Db::table('appointment_notification')->where('order_id', $order->id)->where('title', '退款已到账')->count());
        $this->assertSame(2, Db::table('appointment_notification')->where('order_id', $order->id)->count());
    }

    #[Test] public function refund_notification_is_idempotent_by_order_and_title(): void
    {
        $order = $this->makePaidOrder();
        $refund = $this->makeRefund($order, OrderRefund::STATUS_SUCCESS, date('Y-m-d H:i:s'));
        $ctl = new OrderController();

        // 模拟主路径与补偿并发双写：同订单同标题只落一条
        self::invokePrivate($ctl, 'writeRefundNotification', [$order, $refund]);
        self::invokePrivate($ctl, 'writeRefundNotification', [$order, $refund]);

        $count = Db::table('appointment_notification')
            ->where('order_id', $order->id)
            ->where('title', '退款已到账')
            ->count();
        $this->assertSame(1, $count);
    }

    #[Test] public function compensation_writes_arrival_notification_idempotently(): void
    {
        $order = $this->makeStuckRefundOrder(Order::STATUS_REFUNDING, 1.0);
        $ctl = new OrderController();

        $ctl->completeRefundCompensation();
        $ctl->completeRefundCompensation(); // 幂等：重复扫描不得重复通知

        $rows = Db::table('appointment_notification')->where('order_id', $order->id)->get();
        $this->assertCount(1, $rows);
        $this->assertSame('退款已到账', $rows->first()->title);
        $this->assertStringContainsString('100.00', (string) $rows->first()->content);
    }
}
