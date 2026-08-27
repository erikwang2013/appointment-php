<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\api\v1\controller\PromotionController;
use app\model\Order;
use app\model\OrderItem;
use app\model\OrderPayment;
use app\model\Promotion;
use app\model\PromotionParticipant;
use app\model\Service;
use app\model\User;
use app\model\UserWallet;
use app\model\WalletTxn;
use app\order\v1\controller\OrderController;
use support\Container;
use support\Db;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 拼团成团下单闭环测试
 *
 * 覆盖：join 返回拼团价信息、参与者以拼团价下单（金额=原价×折扣）、
 * 非参与者 422、未满员期间正常下单、已成团锁定后下单拒绝、
 * 活动关闭后下单拒绝 + 存量 pending 订单自动取消、订单携带 promotion_id、
 * 支付复用（余额渠道）、支付时活动已关闭自动取消、拼团订单禁用优惠叠加。
 * 基建与 PromotionJoinTest 一致（真实 DB + tearDown 清理）。
 */
class GroupBuyOrderTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理 */
    private array $userIds = [];

    /** @var string[] 用例活动 ID，tearDown 统一清理参与记录与活动 */
    private array $promotionIds = [];

    /** @var string[] 用例服务 ID，tearDown 统一清理 */
    private array $serviceIds = [];

    /** @var string[] 用例订单 ID，tearDown 统一清理明细/支付记录/订单 */
    private array $orderIds = [];

    protected function tearDown(): void
    {
        foreach ($this->orderIds as $id) {
            OrderItem::where('order_id', $id)->delete();
            OrderPayment::where('order_id', $id)->delete();
            Order::where('id', $id)->delete();
        }
        foreach ($this->promotionIds as $pid) {
            PromotionParticipant::where('promotion_id', $pid)->delete();
            Promotion::where('id', $pid)->delete();
        }
        if ($this->serviceIds) {
            Db::table('appointment_service')->whereIn('id', $this->serviceIds)->delete();
        }
        foreach ($this->userIds as $uid) {
            WalletTxn::where('user_id', $uid)->delete();
            UserWallet::where('user_id', $uid)->delete();
            User::where('id', $uid)->forceDelete();
        }
        $this->userIds = [];
        $this->promotionIds = [];
        $this->serviceIds = [];
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

    private function encode(int $id): string
    {
        return Container::get('hashids')->encode($id);
    }

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

    private function makeService(float $price = 100.0): Service
    {
        // Service 模型带 Scout 搜索索引，测试环境索引引擎不可用，直接经 Db::table 落库
        $id = Service::generateId();
        $now = date('Y-m-d H:i:s');
        Db::table('appointment_service')->insert([
            'id'             => $id,
            'category_id'    => 1,
            'name'           => '拼团测试服务',
            'cover_image'    => '',
            'price'          => $price,
            'original_price' => $price,
            'duration'       => 30,
            'sales_volume'   => 0,
            'sort'           => 0,
            'status'         => 1,
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);
        $this->serviceIds[] = $id;
        return Service::find($id);
    }

    private function makePromotion(Service $service, int $minPeople, string $startAt, string $endAt): Promotion
    {
        $promotion = Promotion::create([
            'id'               => Promotion::generateId(),
            'name'             => '限时拼团',
            'type'             => Promotion::TYPE_GROUP_BUY,
            'service_id'       => $service->id,
            'min_people'       => $minPeople,
            'max_people'       => 5,
            'discount_percent' => 50.0,
            'start_at'         => $startAt,
            'end_at'           => $endAt,
            'status'           => 1,
        ]);
        $this->promotionIds[] = $promotion->id;
        return $promotion;
    }

    private function activePromotion(Service $service, int $minPeople): Promotion
    {
        return $this->makePromotion(
            $service,
            $minPeople,
            date('Y-m-d H:i:s', time() - 3600),
            date('Y-m-d H:i:s', time() + 3600)
        );
    }

    private function join(string $userId, Promotion $promotion): array
    {
        $request = $this->makeRequest();
        $request->user_id = $userId;
        return $this->body((new PromotionController())->join($this->encode((int) $promotion->id), $request));
    }

    private function order(string $userId, Promotion $promotion, Service $service, array $extra = []): array
    {
        $request = $this->makeRequest(array_merge([
            'order_type'   => Order::ORDER_TYPE_PRODUCT,
            'technician_id' => $this->encode(1),
            'store_id'     => $this->encode(1),
            'promotion_id' => $this->encode((int) $promotion->id),
            'items'        => [[
                'target_type' => 'service',
                'target_id'   => $this->encode((int) $service->id),
                'name'        => $service->name,
                'price'       => $service->price,
                'quantity'    => 1,
                'spec_info'   => ['period' => 'morning'],
            ]],
        ], $extra));
        $request->user_id = $userId;
        return $this->body((new OrderController())->store($request));
    }

    private function pay(string $userId, Order $order): array
    {
        $request = $this->makeRequest(['pay_channel' => 'balance']);
        $request->user_id = $userId;
        return $this->body((new OrderController())->pay($request, $this->encode((int) $order->id)));
    }

    private function closePromotion(Promotion $promotion): void
    {
        Promotion::where('id', $promotion->id)->update([
            'end_at' => date('Y-m-d H:i:s', time() - 60),
        ]);
    }

    private function firstOrder(Promotion $promotion): ?Order
    {
        return Order::where('promotion_id', $promotion->id)->first();
    }

    // ── join 返回拼团价信息 ──

    #[Test] public function join_returns_group_price_info(): void
    {
        $service = $this->makeService(100.0);
        $promo = $this->activePromotion($service, 2);
        $u1 = $this->makeUser();

        $resp = $this->join($u1->id, $promo);

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertEquals(50.0, $resp['data']['discount_percent']);
        $this->assertEquals(100.0, $resp['data']['original_price']);
        $this->assertEquals(50.0, $resp['data']['group_price'], '拼团价 = 原价 × discount_percent/100');
    }

    // ── 参与者以拼团价下单 ──

    #[Test] public function participant_orders_at_group_price(): void
    {
        $service = $this->makeService(100.0);
        $promo = $this->activePromotion($service, 2);
        $u1 = $this->makeUser();

        $this->join($u1->id, $promo);
        $resp = $this->order($u1->id, $promo, $service);

        $this->assertSame(0, $resp['code'], json_encode($resp));

        $order = $this->firstOrder($promo);
        $this->assertNotNull($order);
        $this->assertSame(100.0, (float) $order->total_amount, '原价');
        $this->assertSame(50.0, (float) $order->discount_amount, '折扣额 = 原价 × (1 - percent/100)');
        $this->assertSame(50.0, (float) $order->paid_amount, '拼团价 = 原价 × percent/100');
        $this->assertSame($promo->id, (string) $order->promotion_id, '订单携带 promotion_id');
        $participant = PromotionParticipant::where('promotion_id', $promo->id)->where('user_id', $u1->id)->first();
        $this->assertSame((string) $participant->id, (string) $order->participant_id, '订单携带 participant_id');
        $this->assertSame(Order::STATUS_PENDING, $order->status);
        $this->assertSame(1, OrderItem::where('order_id', $order->id)->count(), '订单明细已落库');
        $this->assertSame(1, OrderPayment::where('order_id', $order->id)->count(), '支付记录已落库');
    }

    #[Test] public function non_participant_order_rejected_with_422(): void
    {
        $service = $this->makeService(100.0);
        $promo = $this->activePromotion($service, 2);
        $u1 = $this->makeUser();

        $resp = $this->order($u1->id, $promo, $service);

        $this->assertSame(422, $resp['code'], json_encode($resp));
        $this->assertStringContainsString('未参与', (string) $resp['message']);
        $this->assertSame(0, Order::where('promotion_id', $promo->id)->count());
    }

    #[Test] public function orders_allowed_while_group_not_locked(): void
    {
        $service = $this->makeService(100.0);
        $promo = $this->activePromotion($service, 3);
        $u1 = $this->makeUser();
        $u2 = $this->makeUser();

        $this->join($u1->id, $promo);
        $this->assertSame(0, $this->order($u1->id, $promo, $service)['code'], '未满员第一单成功');
        $this->join($u2->id, $promo);
        $this->assertSame(0, $this->order($u2->id, $promo, $service)['code'], '未满员第二单成功');

        $this->assertSame(2, Order::where('promotion_id', $promo->id)->count());
    }

    #[Test] public function group_order_rejects_coupon_or_points_stacking(): void
    {
        $service = $this->makeService(100.0);
        $promo = $this->activePromotion($service, 2);
        $u1 = $this->makeUser();
        $this->join($u1->id, $promo);

        $r1 = $this->order($u1->id, $promo, $service, ['user_coupon_id' => $this->encode(123456)]);
        $this->assertSame(422, $r1['code'], json_encode($r1));
        $this->assertStringContainsString('不支持叠加', (string) $r1['message']);

        $r2 = $this->order($u1->id, $promo, $service, ['use_points' => 100]);
        $this->assertSame(422, $r2['code'], json_encode($r2));
        $this->assertStringContainsString('不支持叠加', (string) $r2['message']);

        $this->assertSame(0, Order::where('promotion_id', $promo->id)->count());
    }

    #[Test] public function order_rejects_service_not_matching_promotion(): void
    {
        $serviceA = $this->makeService(100.0);
        $serviceB = $this->makeService(200.0);
        $promo = $this->activePromotion($serviceA, 2);
        $u1 = $this->makeUser();
        $this->join($u1->id, $promo);

        $resp = $this->order($u1->id, $promo, $serviceB);

        $this->assertSame(422, $resp['code'], json_encode($resp));
        $this->assertStringContainsString('不匹配', (string) $resp['message']);
        $this->assertSame(0, Order::where('promotion_id', $promo->id)->count());
    }

    // ── 已成团锁定 ──

    #[Test] public function order_rejected_after_group_locked(): void
    {
        $service = $this->makeService(100.0);
        $promo = $this->activePromotion($service, 2);
        $u1 = $this->makeUser();
        $u2 = $this->makeUser();
        $u3 = $this->makeUser();

        $this->join($u1->id, $promo);
        $this->join($u2->id, $promo); // 满员成团锁定

        $rNew = $this->join($u3->id, $promo);
        $this->assertSame(422, $rNew['code'], json_encode($rNew));
        $this->assertStringContainsString('已成团', (string) $rNew['message']);

        $rOrder = $this->order($u1->id, $promo, $service);
        $this->assertSame(422, $rOrder['code'], json_encode($rOrder));
        $this->assertStringContainsString('已成团', (string) $rOrder['message']);

        $this->assertSame(0, Order::where('promotion_id', $promo->id)->count());
    }

    // ── 活动关闭：拒绝下单 + 存量 pending 订单自动取消 ──

    #[Test] public function order_rejected_when_group_closed_and_pending_orders_cancelled(): void
    {
        $service = $this->makeService(100.0);
        $promo = $this->activePromotion($service, 2);
        $u1 = $this->makeUser();

        $this->join($u1->id, $promo);
        $this->assertSame(0, $this->order($u1->id, $promo, $service)['code'], '关闭前下单成功');
        $this->closePromotion($promo);

        $resp = $this->order($u1->id, $promo, $service);

        $this->assertSame(422, $resp['code'], json_encode($resp));
        $this->assertStringContainsString('未成团', (string) $resp['message']);
        $this->assertSame(0, (int) Promotion::find($promo->id)->status, '到期未满员惰性关闭');

        $order = $this->firstOrder($promo);
        $this->assertSame(Order::STATUS_CANCELLED, $order->status, '存量 pending 拼团订单自动取消');
        $this->assertStringContainsString('未成团', (string) $order->cancel_reason);
        $this->assertNotNull($order->cancel_at);
    }

    // ── 支付复用 ──

    #[Test] public function group_buy_order_pays_via_balance_channel(): void
    {
        $service = $this->makeService(100.0);
        $promo = $this->activePromotion($service, 2);
        $u1 = $this->makeUser();
        UserWallet::create([
            'user_id'        => $u1->id,
            'balance'        => 100.0,
            'total_recharge' => 100.0,
            'total_consume'  => 0.0,
        ]);

        $this->join($u1->id, $promo);
        $this->assertSame(0, $this->order($u1->id, $promo, $service)['code']);
        $order = $this->firstOrder($promo);

        $resp = $this->pay($u1->id, $order);

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame(Order::STATUS_PAID, $resp['data']['status'], '拼团订单复用支付链路');
        $this->assertSame(50.0, (float) $resp['data']['amount']);

        $fresh = Order::find($order->id);
        $this->assertSame(Order::STATUS_PAID, $fresh->status);
        $payment = OrderPayment::where('order_id', $order->id)->first();
        $this->assertSame('success', $payment->status);
        $this->assertSame('balance', $payment->pay_type);
        $this->assertSame(50.0, (float) UserWallet::where('user_id', $u1->id)->first()->balance, '余额扣减 50');
    }

    #[Test] public function pay_rejected_and_order_cancelled_when_group_closed(): void
    {
        $service = $this->makeService(100.0);
        $promo = $this->activePromotion($service, 2);
        $u1 = $this->makeUser();
        UserWallet::create([
            'user_id'        => $u1->id,
            'balance'        => 100.0,
            'total_recharge' => 100.0,
            'total_consume'  => 0.0,
        ]);

        $this->join($u1->id, $promo);
        $this->assertSame(0, $this->order($u1->id, $promo, $service)['code']);
        $order = $this->firstOrder($promo);

        $this->closePromotion($promo);
        $resp = $this->pay($u1->id, $order);

        $this->assertSame(422, $resp['code'], json_encode($resp));
        $this->assertStringContainsString('已自动取消', (string) $resp['message']);

        $fresh = Order::find($order->id);
        $this->assertSame(Order::STATUS_CANCELLED, $fresh->status, '支付时懒判定自动取消');
        $this->assertSame(100.0, (float) UserWallet::where('user_id', $u1->id)->first()->balance, '未扣款');
    }
}
