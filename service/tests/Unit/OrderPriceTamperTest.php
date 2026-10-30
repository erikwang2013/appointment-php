<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\model\Order;
use app\model\OrderItem;
use app\model\OrderPayment;
use app\model\Product;
use app\model\Service;
use app\order\v1\controller\OrderController;
use support\Container;
use support\Db;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 订单项价格防篡改测试
 *
 * store() 的 name/cover_image/price 一律以库中记录为准，客户端传值仅用于标识
 * 目标项与规格名：客户端把 100 元商品改成 0.01 元下单，订单金额仍为 100 元。
 * 基建与 CouponDeductionTest 一致（真实 DB + tearDown 清理）。
 */
class OrderPriceTamperTest extends TestCase
{
    /** @var string[] 用例创建的订单 ID */
    private array $orderIds = [];

    /** @var string[] 用例创建的商品 ID */
    private array $productIds = [];

    /** @var string[] 用例创建的服务 ID */
    private array $serviceIds = [];

    protected function tearDown(): void
    {
        foreach ($this->orderIds as $id) {
            OrderItem::where('order_id', $id)->delete();
            OrderPayment::where('order_id', $id)->delete();
            Order::where('id', $id)->delete();
        }
        if ($this->productIds) {
            Db::table('appointment_product')->whereIn('id', $this->productIds)->delete();
        }
        if ($this->serviceIds) {
            Db::table('appointment_service')->whereIn('id', $this->serviceIds)->delete();
        }
        $this->orderIds = [];
        $this->productIds = [];
        $this->serviceIds = [];
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

    private function hash(int $id): string
    {
        return Container::get('hashids')->encode($id);
    }

    private function makeUserId(): string
    {
        return (string) (9900000000000000 + random_int(1, 999999));
    }

    /** 造商品（经 Db::table 落库，避免 Scout 索引） */
    private function makeProduct(float $price, string $name = '测试商品', string $coverImage = '/product.jpg'): Product
    {
        $id = Product::generateId();
        $now = date('Y-m-d H:i:s');
        Db::table('appointment_product')->insert([
            'id'          => $id,
            'name'        => $name,
            'cover_image' => $coverImage,
            'price'       => $price,
            'stock'       => 100,
            'status'      => 1,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
        $this->productIds[] = $id;
        return Product::find($id);
    }

    /** 造服务（specs 为 JSON 规格配置） */
    private function makeService(float $price, array $specs = []): Service
    {
        $id = Service::generateId();
        $now = date('Y-m-d H:i:s');
        Db::table('appointment_service')->insert([
            'id'          => $id,
            'category_id' => 1,
            'name'        => '测试服务',
            'cover_image' => '/service.jpg',
            'price'       => $price,
            'specs'       => $specs ? json_encode($specs, JSON_UNESCAPED_UNICODE) : null,
            'status'      => 1,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
        $this->serviceIds[] = $id;
        return Service::find($id);
    }

    /** 下单（product 类型，避开预约锁/排班校验） */
    private function placeOrder(string $userId, array $items): array
    {
        $request = $this->makeRequest([
            'order_type'    => Order::ORDER_TYPE_PRODUCT,
            'technician_id' => $this->hash((int) (9900000000000000 + random_int(1, 999999))),
            'store_id'      => $this->hash((int) (9900000000000000 + random_int(1, 999999))),
            'items'         => $items,
        ]);
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

    #[Test] public function client_price_is_ignored_for_product(): void
    {
        $userId  = $this->makeUserId();
        $product = $this->makeProduct(100.0);

        $resp = $this->placeOrder($userId, [[
            'target_type' => 'product',
            'target_id'   => $this->hash((int) $product->id),
            'name'        => '篡改名称',
            'cover_image' => '/evil.jpg',
            'price'       => 0.01,
            'quantity'    => 1,
            'spec_info'   => ['default' => true],
        ]]);

        $this->assertSame(0, (int) ($resp['code'] ?? -1), json_encode($resp));

        $order = $this->latestOrder($userId);
        $this->assertNotNull($order);
        $this->assertSame(100.0, (float) $order->total_amount, '订单总额按库中价格');
        $this->assertSame(100.0, (float) $order->paid_amount);

        $item = OrderItem::where('order_id', $order->id)->first();
        $this->assertNotNull($item);
        $this->assertSame(100.0, (float) $item->price, '订单项价格按库中价格');
        $this->assertSame('测试商品', (string) $item->name, '名称按库中记录');
        $this->assertSame('/product.jpg', (string) $item->cover_image, '封面按库中记录');

        $payment = OrderPayment::where('order_id', $order->id)->first();
        $this->assertNotNull($payment);
        $this->assertSame(100.0, (float) $payment->amount);
    }

    #[Test] public function service_spec_price_overrides_base_price(): void
    {
        $userId  = $this->makeUserId();
        $service = $this->makeService(100.0, [
            ['name' => '标准', 'price' => 100],
            ['name' => '高级', 'price' => 150],
        ]);

        $resp = $this->placeOrder($userId, [[
            'target_type' => 'service',
            'target_id'   => $this->hash((int) $service->id),
            'name'        => '篡改名称',
            'price'       => 0.01,
            'quantity'    => 1,
            'spec_info'   => ['name' => '高级', 'price' => 0.01],
        ]]);

        $this->assertSame(0, (int) ($resp['code'] ?? -1), json_encode($resp));

        $order = $this->latestOrder($userId);
        $this->assertNotNull($order);
        $this->assertSame(150.0, (float) $order->total_amount, '命中规格时按规格价');

        $item = OrderItem::where('order_id', $order->id)->first();
        $this->assertNotNull($item);
        $this->assertSame(150.0, (float) $item->price);
    }

    #[Test] public function unknown_spec_name_falls_back_to_base_price(): void
    {
        $userId  = $this->makeUserId();
        $service = $this->makeService(100.0, [['name' => '标准', 'price' => 100]]);

        $resp = $this->placeOrder($userId, [[
            'target_type' => 'service',
            'target_id'   => $this->hash((int) $service->id),
            'name'        => '测试服务',
            'price'       => 0.01,
            'quantity'    => 1,
            'spec_info'   => ['period' => 'morning'],
        ]]);

        $this->assertSame(0, (int) ($resp['code'] ?? -1), json_encode($resp));

        $order = $this->latestOrder($userId);
        $this->assertNotNull($order);
        $this->assertSame(100.0, (float) $order->total_amount, '无匹配规格时用基础价');
    }
}
