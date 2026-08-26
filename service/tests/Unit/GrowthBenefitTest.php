<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\common\WechatPayService;
use app\model\Coupon;
use app\model\Order;
use app\model\OrderItem;
use app\model\OrderPayment;
use app\model\OrderVerification;
use app\model\UserCoupon;
use app\model\UserGrowth;
use app\order\v1\controller\OrderController;
use support\Container;
use support\Db;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 成长等级权益落地测试
 *
 * 覆盖：
 * - 标准订单按等级折扣率计价（白银 98 折，discount_amount/paid_amount/备注可追溯）
 * - 无成长（青铜档 discount_rate=1.0）不产生折扣
 * - 最低价保护：折扣后实付不小于 0.01 元（小额订单折扣截断）
 * - 消费成长值入账按等级积分倍率（白银 1.1 倍）
 * - 倍率对普通用户 = 1（青铜档）
 * - 等级折扣与优惠券叠加（券先减，等级折扣在应付金额上再折）
 *
 * 依赖真实 DB（与 GrowthTest 同基建）。
 */
class GrowthBenefitTest extends TestCase
{
    /** @var string[] 测试用户 ID，tearDown 统一清理 */
    private array $userIds = [];

    /** @var string[] 测试订单 ID，tearDown 统一清理明细/支付记录/订单 */
    private array $orderIds = [];

    /** @var string[] 测试券定义 ID */
    private array $couponIds = [];

    /** @var string[] 测试用户券记录 ID */
    private array $userCouponIds = [];

    protected function tearDown(): void
    {
        foreach ($this->orderIds as $id) {
            OrderVerification::where('order_id', $id)->delete();
            OrderItem::where('order_id', $id)->delete();
            OrderPayment::where('order_id', $id)->delete();
            Order::where('id', $id)->delete();
        }
        foreach ($this->userIds as $id) {
            UserGrowth::where('user_id', $id)->delete();
        }
        foreach ($this->userCouponIds as $id) {
            Db::table('erik_user_coupon')->where('id', $id)->delete();
        }
        foreach ($this->couponIds as $id) {
            Db::table('erik_coupon')->where('id', $id)->delete();
        }
        Db::table('erik_product')->where('id', 1)->delete();
        $this->userIds = [];
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
        $userId = (string) (9900000000000000 + random_int(1, 999999));
        $this->userIds[] = $userId;
        return $userId;
    }

    private function hash(int $id): string
    {
        return Container::get('hashids')->encode($id);
    }

    /** 累计成长值达到白银档（≥100） */
    private function seedSilverGrowth(string $userId): void
    {
        UserGrowth::create([
            'id'      => UserGrowth::generateId(),
            'user_id' => $userId,
            'type'    => UserGrowth::TYPE_CONSUME,
            'value'   => 100,
            'balance' => 100,
        ]);
        UserGrowth::create([
            'id'      => UserGrowth::generateId(),
            'user_id' => $userId,
            'type'    => UserGrowth::TYPE_REVIEW,
            'value'   => 20,
            'balance' => 20,
        ]);
        UserGrowth::create([
            'id'      => UserGrowth::generateId(),
            'user_id' => $userId,
            'type'    => UserGrowth::TYPE_SIGNIN,
            'value'   => 10,
            'balance' => 10,
        ]);
    }

    /** 标准订单下单（product 类型，避开预约锁/排班校验），返回响应 body */
    private function placeOrder(string $userId, float $price, ?string $userCouponIdHash = null): array
    {
        $post = [
            'order_type'    => Order::ORDER_TYPE_PRODUCT,
            'technician_id' => $this->hash((int) (9900000000000000 + random_int(1, 999999))),
            'store_id'      => $this->hash((int) (9900000000000000 + random_int(1, 999999))),
            'items'         => [[
                'target_type' => 'product',
                // 同 technician_id/store_id：items 经 http_build_query 后 *_id 为字符串，
                // decodeIds 会按 hashid 解码，target_id 必须传编码值（raw 1 → 解码失败 → 0）
                'target_id'   => $this->hash(1),
                'name'        => '测试商品',
                'price'       => $price,
                'quantity'    => 1,
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
        return $this->body((new OrderController())->store($request));
    }

    private function latestOrder(string $userId): ?Order
    {
        $order = Order::where('user_id', $userId)->orderBy('created_at', 'desc')->orderBy('id', 'desc')->first();
        if ($order) {
            $this->orderIds[] = $order->id;
        }
        return $order;
    }

    /** 造一张固定 20 元无门槛券 + 用户券记录 */
    private function makeCoupon(string $userId): UserCoupon
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
        $coupon->save();
        $this->couponIds[] = $coupon->id;

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

    // ── 等级折扣 ──

    #[Test] public function silver_level_standard_order_applies_discount(): void
    {
        $userId = $this->makeUserId();
        $this->seedSilverGrowth($userId);

        $resp = $this->placeOrder($userId, 100.0);
        $this->assertSame(0, (int) ($resp['code'] ?? -1), $resp['message'] ?? '');

        $order = $this->latestOrder($userId);
        $this->assertNotNull($order);
        $this->assertSame(100.0, (float) $order->total_amount, '原价不变');
        $this->assertSame(2.0, (float) $order->discount_amount, '折扣额 = 100 × (1 - 0.98)');
        $this->assertSame(98.0, (float) $order->paid_amount, '实付 = 100 × 0.98');
        $this->assertStringContainsString('等级折扣', (string) $order->remark, '折扣可追溯：备注含等级折扣说明');
        $this->assertStringContainsString('9.8折', (string) $order->remark);
        $this->assertStringContainsString('优惠¥2.00', (string) $order->remark);
    }

    #[Test] public function no_level_order_has_no_discount(): void
    {
        $userId = $this->makeUserId();

        $resp = $this->placeOrder($userId, 100.0);
        $this->assertSame(0, (int) ($resp['code'] ?? -1), $resp['message'] ?? '');

        $order = $this->latestOrder($userId);
        $this->assertNotNull($order);
        $this->assertSame(100.0, (float) $order->total_amount);
        $this->assertSame(0.0, (float) $order->discount_amount, '青铜档 discount_rate=1.0 无折扣');
        $this->assertSame(100.0, (float) $order->paid_amount);
        $this->assertSame('', (string) $order->remark, '无折扣不追加备注');
    }

    #[Test] public function discount_floor_protection_keeps_paid_not_below_one_cent(): void
    {
        $userId = $this->makeUserId();
        $this->seedSilverGrowth($userId);

        // 1.00 元订单：98 折理论 0.98 元 < 0.01 元下限 → 折扣截断为 0，实付保持 1.00
        $this->assertSame(0, (int) ($this->placeOrder($userId, 1.0)['code'] ?? -1));
        $order1 = $this->latestOrder($userId);
        $this->assertSame(1.0, (float) $order1->paid_amount);
        $this->assertSame(0.0, (float) $order1->discount_amount);

        // 2.00 元订单：98 折 = 1.96 元 ≥ 0.01 元下限 → 正常打折，折扣 0.04
        $this->assertSame(0, (int) ($this->placeOrder($userId, 2.0)['code'] ?? -1));
        $order2 = $this->latestOrder($userId);
        $this->assertSame(1.96, (float) $order2->paid_amount);
        $this->assertSame(0.04, (float) $order2->discount_amount);
    }

    #[Test] public function growth_discount_stacks_with_coupon(): void
    {
        $userId = $this->makeUserId();
        $this->seedSilverGrowth($userId);
        $uc = $this->makeCoupon($userId);

        $resp = $this->placeOrder($userId, 100.0, $this->hash((int) $uc->id));
        $this->assertSame(0, (int) ($resp['code'] ?? -1), $resp['message'] ?? '');

        $order = $this->latestOrder($userId);
        $this->assertNotNull($order);
        $this->assertSame(100.0, (float) $order->total_amount);
        $this->assertSame(21.6, (float) $order->discount_amount, '折扣额 = 券 20 + 等级折扣 80×2% = 21.6');
        $this->assertSame(78.4, (float) $order->paid_amount, '实付 = (100-20) × 0.98');
        $this->assertStringContainsString('等级折扣', (string) $order->remark);
        $this->assertStringContainsString('优惠¥1.60', (string) $order->remark);
    }

    // ── 积分倍率 ──

    #[Test] public function consume_growth_applies_points_multiplier(): void
    {
        $userId = $this->makeUserId();
        $this->seedSilverGrowth($userId); // 累计 130 → 白银，倍率 1.1
        $order = $this->makeOrder($userId, 100.0);
        $payment = $this->makePayment($order);

        $result = (new WechatPayService())->markOrderPaid($payment->payment_no, 'GRWTXN_' . uniqid(), 100.0, 'wechat');
        $this->assertTrue($result['success'], $result['message'] ?? '');

        $growth = UserGrowth::where('user_id', $userId)->where('type', UserGrowth::TYPE_CONSUME)
            ->orderBy('created_at', 'desc')->first();
        $this->assertNotNull($growth);
        $this->assertSame(110, (int) $growth->value, '入账 = floor(100 × 1.1) = 110');
    }

    #[Test] public function consume_growth_multiplier_is_one_for_bronze(): void
    {
        $userId = $this->makeUserId();
        $order = $this->makeOrder($userId, 125.6);
        $payment = $this->makePayment($order);

        $result = (new WechatPayService())->markOrderPaid($payment->payment_no, 'GRWTXN_' . uniqid(), 125.6, 'wechat');
        $this->assertTrue($result['success'], $result['message'] ?? '');

        $growth = UserGrowth::where('user_id', $userId)->where('type', UserGrowth::TYPE_CONSUME)->first();
        $this->assertNotNull($growth);
        $this->assertSame(125, (int) $growth->value, '青铜倍率 1.0：floor(125.6) = 125');
    }

    private function makeOrder(string $userId, float $paidAmount): Order
    {
        $order = Order::create([
            'order_no'        => 'ORD_GB_' . uniqid(),
            'user_id'         => $userId,
            'technician_id'   => (string) (9900000000000000 + random_int(1, 999999)),
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => $paidAmount,
            'discount_amount' => 0.0,
            'paid_amount'     => $paidAmount,
            'status'          => Order::STATUS_PENDING,
            'service_time'    => date('Y-m-d H:i:s', time() + 86400),
        ]);
        $this->orderIds[] = $order->id;
        return $order;
    }

    private function makePayment(Order $order): OrderPayment
    {
        return OrderPayment::create([
            'id'         => OrderPayment::generateId(),
            'order_id'   => $order->id,
            'payment_no' => 'GBPAY_' . uniqid(),
            'pay_type'   => 'wechat',
            'amount'     => (float) $order->paid_amount,
            'status'     => OrderPayment::STATUS_PENDING,
        ]);
    }
}
