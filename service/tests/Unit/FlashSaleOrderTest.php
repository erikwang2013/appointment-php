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
 * 旧秒杀促销通道下线测试
 *
 * 覆盖：FLASH_SALE 促销不再可参与（join 400）、详情不再返回（show 400）、
 * 不再进入促销结算（store 422「不支持下单」）、列表过滤（index 不含 flash_sale）、
 * 存量 flash_sale 促销订单支付时仍按旧规则懒判定自动取消（历史数据兼容）。
 * 秒杀下单统一走 SeckillActivity 通道（见 SeckillTest）。
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

    private function makePromotion(Service $service, string $type, string $name, int $minPeople, int $maxPeople): Promotion
    {
        $promotion = Promotion::create([
            'id'               => Promotion::generateId(),
            'name'             => $name,
            'type'             => $type,
            'service_id'       => $service->id,
            'min_people'       => $minPeople,
            'max_people'       => $maxPeople,
            'discount_percent' => 30.0,
            'start_at'         => date('Y-m-d H:i:s', time() - 3600),
            'end_at'           => date('Y-m-d H:i:s', time() + 3600),
            'status'           => 1,
        ]);
        $this->promotionIds[] = $promotion->id;
        return $promotion;
    }

    private function flashSalePromotion(Service $service, int $maxPeople): Promotion
    {
        return $this->makePromotion($service, Promotion::TYPE_FLASH_SALE, '限时秒杀', 1, $maxPeople);
    }

    private function order(string $userId, Promotion $promotion, Service $service): array
    {
        $request = $this->makeRequest([
            'order_type'    => Order::ORDER_TYPE_PRODUCT,
            'technician_id' => $this->encode(1),
            'store_id'      => $this->encode(1),
            'promotion_id'  => $this->encode((int) $promotion->id),
            'items'         => [[
                'target_type' => 'service',
                'target_id'   => $this->encode((int) $service->id),
                'name'        => $service->name,
                'price'       => $service->price,
                'quantity'    => 1,
                'spec_info'   => ['period' => 'morning'],
            ]],
        ]);
        $request->user_id = $userId;
        return $this->body((new OrderController())->store($request));
    }

    private function pay(string $userId, Order $order): array
    {
        $request = $this->makeRequest(['pay_channel' => 'balance']);
        $request->user_id = $userId;
        return $this->body((new OrderController())->pay($request, $this->encode((int) $order->id)));
    }

    private function index(string $userId): array
    {
        $request = $this->makeRequest();
        $request->user_id = $userId;
        return $this->body((new PromotionController())->index($request));
    }

    // ── store：FLASH_SALE 不再进入促销结算 ──

    #[Test] public function store_rejects_flash_sale_promotion_order(): void
    {
        $service = $this->makeService(100.0);
        $promo = $this->flashSalePromotion($service, 5);
        $u1 = $this->makeUser();

        $resp = $this->order($u1->id, $promo, $service);

        $this->assertSame(422, $resp['code'], json_encode($resp));
        $this->assertStringContainsString('不支持下单', (string) $resp['message']);
        $this->assertSame(0, Order::where('promotion_id', $promo->id)->count());
    }

    // ── join：不再接受参与 ──

    #[Test] public function join_rejects_flash_sale_promotion(): void
    {
        $service = $this->makeService(100.0);
        $promo = $this->flashSalePromotion($service, 5);
        $u1 = $this->makeUser();

        $request = $this->makeRequest();
        $request->user_id = $u1->id;
        $resp = $this->body((new PromotionController())->join($this->encode((int) $promo->id), $request));

        $this->assertSame(400, $resp['code'], json_encode($resp));
        $this->assertStringContainsString('不存在或已结束', (string) $resp['message']);
        $this->assertSame(0, PromotionParticipant::where('promotion_id', $promo->id)->count());
    }

    // ── show：详情不再返回 ──

    #[Test] public function show_rejects_flash_sale_promotion(): void
    {
        $service = $this->makeService(100.0);
        $promo = $this->flashSalePromotion($service, 5);

        $request = $this->makeRequest();
        $request->user_id = $this->makeUser()->id;
        $resp = $this->body((new PromotionController())->show($this->encode((int) $promo->id), $request));

        $this->assertSame(400, $resp['code'], json_encode($resp));
        $this->assertStringContainsString('不存在或已结束', (string) $resp['message']);
    }

    // ── index：列表过滤 flash_sale，保留拼团 ──

    #[Test] public function index_excludes_flash_sale_keeps_group_buy(): void
    {
        $service = $this->makeService(100.0);
        $flash = $this->flashSalePromotion($service, 5);
        $group = $this->makePromotion($service, Promotion::TYPE_GROUP_BUY, '限时拼团', 2, 5);

        $resp = $this->index($this->makeUser()->id);

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $titles = array_column((array) ($resp['data'] ?? []), 'name');
        $this->assertNotContains($flash->name, $titles, '秒杀促销不再出现在列表中');
        $this->assertContains($group->name, $titles, '拼团促销正常展示');
    }

    // ── 存量 flash_sale 订单：支付时懒判定自动取消（历史数据兼容）──

    #[Test] public function legacy_flash_sale_order_lazy_cancelled_on_pay(): void
    {
        $service = $this->makeService(100.0);
        $promo = $this->flashSalePromotion($service, 5);
        $u1 = $this->makeUser();

        // 旧通道已无法经 store 创建订单，直接落一条历史 flash_sale 促销订单
        $order = Order::create([
            'id'           => Order::generateId(),
            'order_no'     => generate_order_no(),
            'user_id'      => $u1->id,
            'order_type'   => Order::ORDER_TYPE_PRODUCT,
            'promotion_id' => $promo->id,
            'status'       => Order::STATUS_PENDING,
        ]);
        $this->orderIds[] = (string) $order->id;

        Promotion::where('id', $promo->id)->update([
            'end_at' => date('Y-m-d H:i:s', time() - 60),
        ]);

        $resp = $this->pay($u1->id, $order);

        $this->assertSame(422, $resp['code'], json_encode($resp));
        $this->assertStringContainsString('已自动取消', (string) $resp['message']);
        $fresh = Order::find($order->id);
        $this->assertSame(Order::STATUS_CANCELLED, $fresh->status);
        $this->assertStringContainsString('秒杀', (string) $fresh->cancel_reason);
        $this->assertSame(0, (int) Promotion::find($promo->id)->status, '过期活动懒判定关闭');
    }
}
