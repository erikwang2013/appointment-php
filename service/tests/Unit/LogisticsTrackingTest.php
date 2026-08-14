<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\model\Order;
use app\model\OrderItem;
use app\model\OrderPayment;
use app\order\v1\controller\OrderController;
use support\Container;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 用户端物流跟踪测试
 *
 * 覆盖：商品订单发货后返回物流字段、未录入物流 404、非本人订单 404、
 * 预约订单无物流 404、收货人手机号脱敏。
 * 基建与 AftersaleTest 一致（真实 DB + tearDown 清理）。
 */
class LogisticsTrackingTest extends TestCase
{
    /** @var int[] 用例创建的订单 ID，tearDown 统一清理 */
    private array $orderIds = [];

    protected function tearDown(): void
    {
        foreach ($this->orderIds as $id) {
            OrderItem::where('order_id', $id)->delete();
            OrderPayment::where('order_id', $id)->delete();
            Order::where('id', $id)->delete();
        }
        $this->orderIds = [];
    }

    private function makeRequest(): Request
    {
        $head = "Host: localhost\r\n"
            . "Content-Type: application/x-www-form-urlencoded\r\n"
            . "Content-Length: 0\r\n";
        return new Request("GET /api/order/logistics/1 HTTP/1.1\r\n" . $head . "\r\n");
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    private function newUserId(): string
    {
        return (string) (9900000000000000 + random_int(1, 999999));
    }

    /** 造指定用户的商品订单（默认已发货，可选 remark） */
    private function makeProductOrder(string $userId, ?string $remark = null): Order
    {
        $order = Order::create([
            'id'              => Order::generateId(),
            'order_no'        => 'ORD_LOG_' . uniqid(),
            'user_id'         => $userId,
            'order_type'      => Order::ORDER_TYPE_PRODUCT,
            'total_amount'    => 199.0,
            'discount_amount' => 0.0,
            'paid_amount'     => 199.0,
            'status'          => Order::STATUS_SERVING,
            'remark'          => $remark,
        ]);
        $this->orderIds[] = $order->id;

        OrderItem::create([
            'id'          => OrderItem::generateId(),
            'order_id'    => $order->id,
            'target_type' => 'product',
            'target_id'   => (string) (1 + random_int(0, 99999)),
            'name'        => '康复理疗护具套装',
            'cover_image' => 'https://example.com/cover.jpg',
            'price'       => 199.0,
            'quantity'    => 1,
            'spec_info'   => ['颜色' => '黑色', '尺码' => 'L'],
        ]);

        return $order;
    }

    private function logistics(string $userId, string $hashid): array
    {
        $request = $this->makeRequest();
        $request->user_id = $userId;
        return $this->body((new OrderController())->logistics($request, $hashid));
    }

    private function encodeId(int $id): string
    {
        return Container::get('hashids')->encode($id);
    }

    private function shippedRemark(): string
    {
        return json_encode([
            'shipping_company' => '顺丰速运',
            'tracking_no'      => 'SF1234567890',
            'shipped_at'       => '2026-08-14 10:30:00',
        ], JSON_UNESCAPED_UNICODE);
    }

    // ── 已发货商品订单：返回物流字段 ──

    #[Test] public function shipped_product_order_returns_logistics(): void
    {
        $userId = $this->newUserId();
        $order = $this->makeProductOrder($userId, $this->shippedRemark());

        $resp = $this->logistics($userId, $this->encodeId((int) $order->id));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame($order->order_no, $resp['data']['order_no']);
        $this->assertSame(Order::STATUS_SERVING, $resp['data']['status']);
        $this->assertSame('顺丰速运', $resp['data']['shipping_company']);
        $this->assertSame('SF1234567890', $resp['data']['tracking_no']);
        $this->assertSame('2026-08-14 10:30:00', $resp['data']['shipped_at']);
        $this->assertSame([], $resp['data']['traces'], '无轨迹明细表，返回空数组');

        // 商品快照
        $this->assertCount(1, $resp['data']['items']);
        $item = $resp['data']['items'][0];
        $this->assertSame('康复理疗护具套装', $item['name']);
        $this->assertSame(1, $item['quantity']);
        $this->assertSame('https://example.com/cover.jpg', $item['cover_image']);
        $this->assertSame('黑色', $item['spec_info']['颜色']);

        // 无收货快照约定
        $this->assertNull($resp['data']['receiver']);
    }

    // ── 商品订单未发货：404 ──

    #[Test] public function product_order_without_shipping_returns_404(): void
    {
        $userId = $this->newUserId();
        $order = $this->makeProductOrder($userId, '');

        $resp = $this->logistics($userId, $this->encodeId((int) $order->id));

        $this->assertSame(404, $resp['code']);
    }

    // ── 非本人订单：404 ──

    #[Test] public function foreign_order_returns_404(): void
    {
        $owner = $this->newUserId();
        $attacker = $this->newUserId();
        $order = $this->makeProductOrder($owner, $this->shippedRemark());

        $resp = $this->logistics($attacker, $this->encodeId((int) $order->id));

        $this->assertSame(404, $resp['code']);
    }

    // ── 预约订单：404 ──

    #[Test] public function appointment_order_returns_404(): void
    {
        $userId = $this->newUserId();
        $order = Order::create([
            'id'              => Order::generateId(),
            'order_no'        => 'ORD_LOG_APP_' . uniqid(),
            'user_id'         => $userId,
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => 100.0,
            'discount_amount' => 0.0,
            'paid_amount'     => 100.0,
            'status'          => Order::STATUS_PAID,
            'service_time'    => date('Y-m-d H:i:s', time() + 43200),
        ]);
        $this->orderIds[] = $order->id;

        $resp = $this->logistics($userId, $this->encodeId((int) $order->id));

        $this->assertSame(404, $resp['code']);
    }

    // ── 无效 hashid：404 ──

    #[Test] public function invalid_hashid_returns_404(): void
    {
        $resp = $this->logistics($this->newUserId(), 'not-a-hashid');

        $this->assertSame(404, $resp['code']);
    }

    // ── remark 含收货信息：手机号脱敏透出 ──

    #[Test] public function receiver_info_is_masked_when_present(): void
    {
        $userId = $this->newUserId();
        $remark = json_encode([
            'shipping_company' => '中通快递',
            'tracking_no'      => 'ZT9876543210',
            'shipped_at'       => '2026-08-14 11:00:00',
            'receiver_name'    => '张三',
            'receiver_phone'   => '13812345678',
            'receiver_address' => '上海市浦东新区世纪大道 100 号',
        ], JSON_UNESCAPED_UNICODE);
        $order = $this->makeProductOrder($userId, $remark);

        $resp = $this->logistics($userId, $this->encodeId((int) $order->id));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame('张三', $resp['data']['receiver']['receiver_name']);
        $this->assertSame('138****5678', $resp['data']['receiver']['receiver_phone']);
        $this->assertSame('上海市浦东新区世纪大道 100 号', $resp['data']['receiver']['receiver_address']);
    }
}
