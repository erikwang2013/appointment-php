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
use app\model\OrderVerification;
use app\model\Promotion;
use app\model\PromotionParticipant;
use app\model\Service;
use app\model\User;
use app\model\UserWallet;
use app\model\WalletTxn;
use app\order\v1\controller\OrderController;
use support\Container;
use support\Db;
use support\Redis;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 秒杀下单闭环测试
 *
 * 覆盖：参与者以秒杀价下单（金额=原价×(1-discount_percent/100)）、
 * 非 flash_sale 类型 422、活动过期 422、非参与者 422、售罄 422、
 * 秒杀订单禁用优惠叠加、支付时活动过期自动取消并释放技师锁。
 * 基建与 GroupBuyOrderTest 一致（真实 DB + tearDown 清理）。
 */
class FlashSaleOrderTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理 */
    private array $userIds = [];

    /** @var string[] 用例活动 ID，tearDown 统一清理参与记录与活动 */
    private array $promotionIds = [];

    /** @var string[] 用例服务 ID，tearDown 统一清理 */
    private array $serviceIds = [];

    /** @var string[] 用例订单 ID，tearDown 统一清理核销码/明细/支付记录/订单 */
    private array $orderIds = [];

    protected function tearDown(): void
    {
        foreach ($this->orderIds as $id) {
            OrderVerification::where('order_id', $id)->delete();
            OrderItem::where('order_id', $id)->delete();
            OrderPayment::where('order_id', $id)->delete();
            Order::where('id', $id)->delete();
        }
        foreach ($this->promotionIds as $pid) {
            PromotionParticipant::where('promotion_id', $pid)->delete();
            Promotion::where('id', $pid)->delete();
        }
        if ($this->serviceIds) {
            Db::table('erik_service')->whereIn('id', $this->serviceIds)->delete();
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
            'phone'     => '188' . substr((string) random_int(10000000, 99999999), 0, 8),
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
        Db::table('erik_service')->insert([
            'id'             => $id,
            'category_id'    => 1,
            'name'           => '秒杀测试服务',
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

    private function makePromotion(Service $service, int $maxPeople, string $startAt, string $endAt): Promotion
    {
        $promotion = Promotion::create([
            'id'               => Promotion::generateId(),
            'name'             => '限时秒杀',
            'type'             => Promotion::TYPE_FLASH_SALE,
            'service_id'       => $service->id,
            'min_people'       => 1,
            'max_people'       => $maxPeople,
            'discount_percent' => 30.0,
            'start_at'         => $startAt,
            'end_at'           => $endAt,
            'status'           => 1,
        ]);
        $this->promotionIds[] = $promotion->id;
        return $promotion;
    }

    private function activePromotion(Service $service, int $maxPeople): Promotion
    {
        return $this->makePromotion(
            $service,
            $maxPeople,
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

    private function expirePromotion(Promotion $promotion): void
    {
        Promotion::where('id', $promotion->id)->update([
            'end_at' => date('Y-m-d H:i:s', time() - 60),
        ]);
    }

    private function firstOrder(Promotion $promotion): ?Order
    {
        return Order::where('promotion_id', $promotion->id)->first();
    }

    // ── 参与者以秒杀价下单 ──

    #[Test] public function flash_sale_participant_orders_at_flash_price(): void
    {
        $service = $this->makeService(100.0);
        $promo = $this->activePromotion($service, 5);
        $u1 = $this->makeUser();

        $this->assertSame(0, $this->join($u1->id, $promo)['code']);
        $resp = $this->order($u1->id, $promo, $service);

        $this->assertSame(0, $resp['code'], json_encode($resp));

        $order = $this->firstOrder($promo);
        $this->assertNotNull($order);
        $this->assertSame(100.0, (float) $order->total_amount, '原价');
        $this->assertSame(30.0, (float) $order->discount_amount, '折扣额 = 原价 × discount_percent/100');
        $this->assertSame(70.0, (float) $order->paid_amount, '秒杀价 = 原价 × (1 - discount_percent/100)');
        $this->assertSame($promo->id, (string) $order->promotion_id, '订单携带 promotion_id');
        $participant = PromotionParticipant::where('promotion_id', $promo->id)->where('user_id', $u1->id)->first();
        $this->assertSame((string) $participant->id, (string) $order->participant_id, '订单携带 participant_id');
        $this->assertSame(Order::STATUS_PENDING, $order->status);
        $this->assertSame(1, OrderItem::where('order_id', $order->id)->count(), '订单明细已落库');
        $this->assertSame(1, OrderPayment::where('order_id', $order->id)->count(), '支付记录已落库');
    }

    // ── 类型校验 ──

    #[Test] public function order_rejected_for_unsupported_promotion_type(): void
    {
        $service = $this->makeService(100.0);
        $promotion = Promotion::create([
            'id'               => Promotion::generateId(),
            'name'             => '未知活动',
            'type'             => 'vip',
            'service_id'       => $service->id,
            'min_people'       => 1,
            'max_people'       => 5,
            'discount_percent' => 50.0,
            'start_at'         => date('Y-m-d H:i:s', time() - 3600),
            'end_at'           => date('Y-m-d H:i:s', time() + 3600),
            'status'           => 1,
        ]);
        $this->promotionIds[] = $promotion->id;
        $u1 = $this->makeUser();

        $resp = $this->order($u1->id, $promotion, $service);

        $this->assertSame(422, $resp['code'], json_encode($resp));
        $this->assertStringContainsString('不支持下单', (string) $resp['message']);
        $this->assertSame(0, Order::where('promotion_id', $promotion->id)->count());
    }

    // ── 活动过期 ──

    #[Test] public function order_rejected_when_flash_sale_expired(): void
    {
        $service = $this->makeService(100.0);
        $promo = $this->activePromotion($service, 5);
        $u1 = $this->makeUser();

        $this->join($u1->id, $promo);
        $this->expirePromotion($promo);

        $resp = $this->order($u1->id, $promo, $service);

        $this->assertSame(422, $resp['code'], json_encode($resp));
        $this->assertStringContainsString('活动不在有效时间内', (string) $resp['message']);
        $this->assertSame(1, (int) Promotion::find($promo->id)->status, '秒杀过期不做惰性关闭');
        $this->assertSame(0, Order::where('promotion_id', $promo->id)->count());
    }

    // ── 非参与者 ──

    #[Test] public function order_rejected_for_non_participant(): void
    {
        $service = $this->makeService(100.0);
        $promo = $this->activePromotion($service, 5);
        $u1 = $this->makeUser();

        $resp = $this->order($u1->id, $promo, $service);

        $this->assertSame(422, $resp['code'], json_encode($resp));
        $this->assertStringContainsString('未参与', (string) $resp['message']);
        $this->assertSame(0, Order::where('promotion_id', $promo->id)->count());
    }

    // ── 售罄（max_people 为库存）──

    #[Test] public function order_rejected_when_flash_sale_sold_out(): void
    {
        $service = $this->makeService(100.0);
        $promo = $this->activePromotion($service, 2);
        $u1 = $this->makeUser();
        $u2 = $this->makeUser();

        $this->assertSame(0, $this->join($u1->id, $promo)['code']);
        $this->assertSame(0, $this->join($u2->id, $promo)['code'], '未售罄仍可参与');

        $resp = $this->order($u1->id, $promo, $service);

        $this->assertSame(422, $resp['code'], json_encode($resp));
        $this->assertStringContainsString('抢光', (string) $resp['message']);
        $this->assertSame(0, Order::where('promotion_id', $promo->id)->count());
    }

    // ── 禁用叠加 ──

    #[Test] public function flash_sale_order_rejects_coupon_or_points_stacking(): void
    {
        $service = $this->makeService(100.0);
        $promo = $this->activePromotion($service, 5);
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

    // ── 支付时活动过期：自动取消 + 释放技师锁 ──

    #[Test] public function pay_rejected_and_order_cancelled_when_flash_sale_expired(): void
    {
        $service = $this->makeService(100.0);
        $promo = $this->activePromotion($service, 5);
        $u1 = $this->makeUser();
        UserWallet::create([
            'user_id'        => $u1->id,
            'balance'        => 100.0,
            'total_recharge' => 100.0,
            'total_consume'  => 0.0,
        ]);

        $this->join($u1->id, $promo);
        // 唯一技师 ID：避免与其他用例（共用技师 1）的时段锁互斥
        $technicianId = random_int(1000, 999999);
        $serviceTime = date('Y-m-d H:i:s', time() + 7200);
        $lockKey = 'technician_lock:' . $technicianId . ':' . date('YmdHi', strtotime($serviceTime));
        $this->assertSame(0, $this->order($u1->id, $promo, $service, [
            'order_type'   => Order::ORDER_TYPE_APPOINTMENT,
            'technician_id' => $this->encode($technicianId),
            'service_time' => $serviceTime,
        ])['code'], '预约单下单成功');
        $order = $this->firstOrder($promo);

        $this->assertSame((string) $u1->id, (string) Redis::get($lockKey), '下单后技师锁已持有');

        $this->expirePromotion($promo);
        $resp = $this->pay($u1->id, $order);

        $this->assertSame(422, $resp['code'], json_encode($resp));
        $this->assertStringContainsString('已自动取消', (string) $resp['message']);

        $fresh = Order::find($order->id);
        $this->assertSame(Order::STATUS_CANCELLED, $fresh->status, '支付时懒判定自动取消');
        $this->assertStringContainsString('秒杀', (string) $fresh->cancel_reason);
        $this->assertNull(Redis::get($lockKey), '技师锁已释放');
        $this->assertSame(100.0, (float) UserWallet::where('user_id', $u1->id)->first()->balance, '未扣款');
    }
}
