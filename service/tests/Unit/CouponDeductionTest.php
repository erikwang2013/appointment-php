<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\common\PriceCalculator;
use app\model\Coupon;
use app\model\Order;
use app\model\OrderItem;
use app\model\OrderPayment;
use app\model\OrderVerification;
use app\model\UserCoupon;
use app\order\v1\controller\OrderController;
use support\Container;
use support\Db;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 优惠券抵扣闭环测试（第 9 轮）
 *
 * 覆盖：
 * - store() 下单接入券抵扣：fixed/percent 算额、coupon_id/user_coupon_id/discount_amount 落库
 * - 业务拒绝状态码：门槛不足 422 / 过期 422 / 他人券 404 / 非法 hashid 422
 * - 券消费时机为支付成功（PriceCalculator::consume），下单时仅校验与算额
 * - 退款/取消归还（restoreCouponAndCard 幂等：used → available，used_at 清空）
 *
 * 真实 MySQL（与 OrderRefundFlowTest 同基建），tearDown 统一清理。
 */
class CouponDeductionTest extends TestCase
{
    /** @var int[] 用例创建的订单 ID */
    private array $orderIds = [];

    /** @var string[] 用例创建的券定义 ID */
    private array $couponIds = [];

    /** @var string[] 用例创建的用户券记录 ID */
    private array $userCouponIds = [];

    protected function tearDown(): void
    {
        Db::table('erik_product')->where('id', 1)->delete();
        foreach ($this->orderIds as $id) {
            OrderVerification::where('order_id', $id)->delete();
            OrderItem::where('order_id', $id)->delete();
            OrderPayment::where('order_id', $id)->delete();
            Order::where('id', $id)->delete();
        }
        foreach ($this->userCouponIds as $id) {
            Db::table('erik_user_coupon')->where('id', $id)->delete();
        }
        foreach ($this->couponIds as $id) {
            Db::table('erik_coupon')->where('id', $id)->delete();
        }
        $this->orderIds = [];
        $this->couponIds = [];
        $this->userCouponIds = [];
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

    private function makeUserId(): string
    {
        return (string) (9900000000000000 + random_int(1, 999999));
    }

    /** 造一张券定义（默认固定 20 元、无门槛、有效期内、上架） */
    private function makeCoupon(array $overrides = []): Coupon
    {
        $coupon = new Coupon();
        $coupon->id = Coupon::generateId();
        $coupon->name = '测试券';
        $coupon->type = 'fixed';
        $coupon->amount = 20.0;
        $coupon->min_amount = 0.0;
        $coupon->total_qty = 100;
        $coupon->remain_qty = 100;
        $coupon->start_at = date('Y-m-d H:i:s', time() - 86400);
        $coupon->end_at = date('Y-m-d H:i:s', time() + 86400);
        $coupon->status = 1;
        foreach ($overrides as $k => $v) {
            $coupon->$k = $v;
        }
        $coupon->save();
        $this->couponIds[] = $coupon->id;
        return $coupon;
    }

    /** 发一张用户券（available） */
    private function makeUserCoupon(Coupon $coupon, string $userId): UserCoupon
    {
        $uc = new UserCoupon();
        $uc->id = UserCoupon::generateId();
        $uc->user_id = $userId;
        $uc->coupon_id = $coupon->id;
        $uc->status = 'available';
        $uc->received_at = date('Y-m-d H:i:s');
        $uc->save();
        $this->userCouponIds[] = $uc->id;
        return $uc;
    }

    /** 下单（product 类型，避开预约锁/排班校验） */
    private function placeOrder(string $userId, float $price, ?string $userCouponIdHash): Response
    {
        $post = [
            'order_type'   => Order::ORDER_TYPE_PRODUCT,
            // erik_order.technician_id/store_id 为 NOT NULL，product 单也须给（store 会 decodeId）
            'technician_id' => $this->hash((int) (9900000000000000 + random_int(1, 999999))),
            'store_id'      => $this->hash((int) (9900000000000000 + random_int(1, 999999))),
            'items'        => [[
                'target_type' => 'product',
                // 同 technician_id/store_id：items 经 http_build_query 后 *_id 为字符串，
                // decodeIds 会按 hashid 解码，target_id 必须传编码值（raw 1 → 解码失败 → 0）
                'target_id'   => $this->hash(1),
                'name'        => '测试商品',
                'price'       => $price,
                'quantity'    => 1,
                // erik_order_item.spec_info 为 NOT NULL
                'spec_info'   => ['default' => true],
            ]],
        ];
        if ($userCouponIdHash !== null) {
            $post['user_coupon_id'] = $userCouponIdHash;
        }
        // 下单校验商品存在且价格以库中记录为准：种 id=1 商品行，价格与用例一致
        Db::table('erik_product')->updateOrInsert(['id' => 1], [
            'name'   => '测试商品',
            'price'  => $price,
            'stock'  => 100,
            'status' => 1,
        ]);
        $request = $this->makeRequest($post);
        $request->user_id = $userId;
        return (new OrderController())->store($request);
    }

    private function latestOrder(string $userId): ?Order
    {
        return Order::where('user_id', $userId)->orderBy('created_at', 'desc')->first();
    }

    private function hash(int $id): string
    {
        return Container::get('hashids')->encode($id);
    }

    // ── 抵扣成功 ──

    #[Test] public function fixed_coupon_deducts_and_persists(): void
    {
        $userId = $this->makeUserId();
        $coupon = $this->makeCoupon();
        $uc = $this->makeUserCoupon($coupon, $userId);

        $resp = $this->placeOrder($userId, 100.0, $this->hash((int) $uc->id));
        $this->assertSame(0, (int) ($this->body($resp)['code'] ?? -1), $this->body($resp)['message'] ?? '');

        $order = $this->latestOrder($userId);
        $this->assertNotNull($order);
        $this->orderIds[] = $order->id;
        $this->assertSame(100.0, (float) $order->total_amount);
        $this->assertSame(20.0, (float) $order->discount_amount);
        $this->assertSame(80.0, (float) $order->paid_amount);
        $this->assertSame((int) $coupon->id, (int) $order->coupon_id);
        $this->assertSame((int) $uc->id, (int) $order->user_coupon_id);

        // 消费时机为支付成功：下单后券仍为 available（不提前标记）
        $this->assertSame('available', UserCoupon::find($uc->id)->status);
    }

    #[Test] public function percent_coupon_deducts_ratio(): void
    {
        $userId = $this->makeUserId();
        $coupon = $this->makeCoupon(['type' => 'percent', 'amount' => 10.0]); // 折扣 10%
        $uc = $this->makeUserCoupon($coupon, $userId);

        $resp = $this->placeOrder($userId, 100.0, $this->hash((int) $uc->id));
        $this->assertSame(0, (int) ($this->body($resp)['code'] ?? -1), $this->body($resp)['message'] ?? '');

        $order = $this->latestOrder($userId);
        $this->assertNotNull($order);
        $this->orderIds[] = $order->id;
        $this->assertSame(10.0, (float) $order->discount_amount);
        $this->assertSame(90.0, (float) $order->paid_amount);
    }

    // ── 拒绝路径 ──

    #[Test] public function min_amount_not_met_returns_422(): void
    {
        $userId = $this->makeUserId();
        $coupon = $this->makeCoupon(['min_amount' => 100.0]);
        $uc = $this->makeUserCoupon($coupon, $userId);

        $resp = $this->placeOrder($userId, 50.0, $this->hash((int) $uc->id));
        $this->assertSame(422, $resp->getStatusCode());
        $this->assertSame('未满足优惠券使用门槛', $this->body($resp)['message']);
        $this->assertNull($this->latestOrder($userId));
    }

    #[Test] public function expired_coupon_returns_422(): void
    {
        $userId = $this->makeUserId();
        $coupon = $this->makeCoupon(['end_at' => date('Y-m-d H:i:s', time() - 3600)]);
        $uc = $this->makeUserCoupon($coupon, $userId);

        $resp = $this->placeOrder($userId, 100.0, $this->hash((int) $uc->id));
        $this->assertSame(422, $resp->getStatusCode());
        $this->assertSame('优惠券已过期', $this->body($resp)['message']);
        $this->assertNull($this->latestOrder($userId));
    }

    #[Test] public function foreign_coupon_returns_404(): void
    {
        $owner = $this->makeUserId();
        $coupon = $this->makeCoupon();
        $uc = $this->makeUserCoupon($coupon, $owner);

        $intruder = $this->makeUserId();
        $resp = $this->placeOrder($intruder, 100.0, $this->hash((int) $uc->id));
        $this->assertSame(404, $resp->getStatusCode());
        $this->assertSame('优惠券不存在', $this->body($resp)['message']);
        $this->assertNull($this->latestOrder($intruder));
    }

    #[Test] public function invalid_hashid_returns_422(): void
    {
        $userId = $this->makeUserId();
        $resp = $this->placeOrder($userId, 100.0, 'not-a-hashid');
        $this->assertSame(422, $resp->getStatusCode());
        $this->assertSame('优惠券ID无效', $this->body($resp)['message']);
    }

    // ── 消费与归还 ──

    #[Test] public function consume_marks_coupon_used_at_pay(): void
    {
        $userId = $this->makeUserId();
        $coupon = $this->makeCoupon();
        $uc = $this->makeUserCoupon($coupon, $userId);

        PriceCalculator::consume([
            ['target_type' => 'product', 'target_id' => 1, 'price' => 100.0, 'quantity' => 1],
        ], ['user_id' => $userId, 'user_coupon_id' => (int) $uc->id]);

        $uc->refresh();
        $this->assertSame('used', $uc->status);
        $this->assertNotNull($uc->used_at);
    }

    #[Test] public function restore_returns_coupon_to_available_idempotently(): void
    {
        $userId = $this->makeUserId();
        $coupon = $this->makeCoupon();
        $uc = $this->makeUserCoupon($coupon, $userId);
        $uc->status = 'used';
        $uc->used_at = date('Y-m-d H:i:s');
        $uc->save();

        $order = Order::create([
            'order_no'        => 'ORD_CPN_' . uniqid(),
            'user_id'         => $userId,
            'technician_id'   => (string) (9900000000000000 + random_int(1, 999999)),
            'order_type'      => Order::ORDER_TYPE_PRODUCT,
            'total_amount'    => 100.0,
            'discount_amount' => 20.0,
            'paid_amount'     => 80.0,
            'coupon_id'       => (int) $coupon->id,
            'user_coupon_id'  => (int) $uc->id,
            'status'          => Order::STATUS_CANCELLED,
        ]);
        $this->orderIds[] = $order->id;

        $ctl = new OrderController();
        $m = new \ReflectionMethod(OrderController::class, 'restoreCouponAndCard');
        $m->setAccessible(true);
        $m->invokeArgs($ctl, [$order]);
        // 幂等：重复归还不产生副作用
        $m->invokeArgs($ctl, [$order]);

        $uc->refresh();
        $this->assertSame('available', $uc->status);
        $this->assertNull($uc->used_at);
    }
}
