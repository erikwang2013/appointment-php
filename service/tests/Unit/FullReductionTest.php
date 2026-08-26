<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\api\v1\controller\FullReductionController;
use app\api\v1\controller\PromotionController;
use app\model\FullReductionActivity;
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
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 满减活动（满 X 减 Y）测试
 *
 * 覆盖：生效活动可被公开接口列出、下架/过期活动不返回、
 * 标准订单券后金额达门槛触发满减（discount_amount + 备注）、未达门槛不触发、
 * 拼团订单跳过满减。基建与 FlashSaleOrderTest 一致（真实 DB + tearDown 清理）。
 */
class FullReductionTest extends TestCase
{
    /** @var string[] 用例活动 ID，tearDown 统一清理 */
    private array $activityIds = [];

    /** @var string[] 用例用户 ID，tearDown 统一清理 */
    private array $userIds = [];

    /** @var string[] 用例服务 ID，tearDown 统一清理 */
    private array $serviceIds = [];

    /** @var string[] 用例活动 ID，tearDown 统一清理参与记录与活动 */
    private array $promotionIds = [];

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
        foreach ($this->activityIds as $aid) {
            FullReductionActivity::where('id', $aid)->delete();
        }
        $this->activityIds = [];
        $this->userIds = [];
        $this->serviceIds = [];
        $this->promotionIds = [];
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
            'name'           => '满减测试服务',
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

    private function makeActivity(
        string $title,
        float $threshold,
        float $reduction,
        int $status = 1,
        ?string $startAt = null,
        ?string $endAt = null
    ): FullReductionActivity {
        $activity = FullReductionActivity::create([
            'id'        => FullReductionActivity::generateId(),
            'title'     => $title,
            'threshold' => $threshold,
            'reduction' => $reduction,
            'status'    => $status,
            'start_at'  => $startAt ?? date('Y-m-d H:i:s', time() - 3600),
            'end_at'    => $endAt ?? date('Y-m-d H:i:s', time() + 3600),
        ]);
        $this->activityIds[] = (string) $activity->id;
        return $activity;
    }

    private function index(): array
    {
        $request = $this->makeRequest();
        return $this->body((new FullReductionController())->index($request));
    }

    private function order(string $userId, Service $service, array $extra = []): array
    {
        $request = $this->makeRequest(array_merge([
            'order_type'    => Order::ORDER_TYPE_PRODUCT,
            'technician_id' => $this->encode(1),
            'store_id'      => $this->encode(1),
            'items'         => [[
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

    private function firstOrderByUser(string $userId): ?Order
    {
        return Order::where('user_id', $userId)->orderBy('created_at', 'desc')->first();
    }

    private function titles(array $resp): array
    {
        $titles = [];
        foreach (($resp['data'] ?? []) as $item) {
            $titles[] = (string) ($item['title'] ?? '');
        }
        return $titles;
    }

    private function makePromotion(Service $service, int $maxPeople): Promotion
    {
        $promotion = Promotion::create([
            'id'               => Promotion::generateId(),
            'name'             => '限时拼团',
            'type'             => Promotion::TYPE_GROUP_BUY,
            'service_id'       => $service->id,
            'min_people'       => 2,
            'max_people'       => $maxPeople,
            'discount_percent' => 50.0,
            'start_at'         => date('Y-m-d H:i:s', time() - 3600),
            'end_at'           => date('Y-m-d H:i:s', time() + 3600),
            'status'           => 1,
        ]);
        $this->promotionIds[] = $promotion->id;
        return $promotion;
    }

    private function join(string $userId, Promotion $promotion): array
    {
        $request = $this->makeRequest();
        $request->user_id = $userId;
        return $this->body((new PromotionController())->join($this->encode((int) $promotion->id), $request));
    }

    // ── 生效活动可被公开接口列出 ──

    #[Test] public function index_lists_active_activity(): void
    {
        $activity = $this->makeActivity('满减生效活动A', 100.0, 10.0);

        $resp = $this->index();

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertContains($activity->title, $this->titles($resp));
    }

    // ── 未上架活动不返回 ──

    #[Test] public function index_excludes_off_shelf_activity(): void
    {
        $activity = $this->makeActivity('满减下架活动B', 100.0, 10.0, 0);

        $resp = $this->index();

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertNotContains($activity->title, $this->titles($resp));
    }

    // ── 已过期活动不返回 ──

    #[Test] public function index_excludes_expired_activity(): void
    {
        $activity = $this->makeActivity(
            '满减过期活动C',
            100.0,
            10.0,
            1,
            date('Y-m-d H:i:s', time() - 7200),
            date('Y-m-d H:i:s', time() - 3600)
        );

        $resp = $this->index();

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertNotContains($activity->title, $this->titles($resp));
    }

    // ── 券后金额达门槛触发满减 ──

    #[Test] public function order_meets_threshold_applies_reduction(): void
    {
        $this->makeActivity('满100减10', 100.0, 10.0);
        $service = $this->makeService(100.0);
        $u1 = $this->makeUser();

        $resp = $this->order($u1->id, $service);

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame(100.0, (float) $resp['data']['total_amount'], '原价');
        $this->assertSame(10.0, (float) $resp['data']['discount_amount'], '满减优惠额并入 discount_amount');
        $this->assertSame(90.0, (float) $resp['data']['paid_amount'], '实付 = 券后金额 - 减免');
        $this->assertStringContainsString('满减：满100.00减10.00', (string) $resp['data']['remark']);
        $this->orderIds[] = (string) $this->firstOrderByUser($u1->id)->id;
    }

    // ── 未达门槛不触发 ──

    #[Test] public function order_below_threshold_skips_reduction(): void
    {
        $this->makeActivity('满100减10', 100.0, 10.0);
        $service = $this->makeService(80.0);
        $u1 = $this->makeUser();

        $resp = $this->order($u1->id, $service);

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame(0.0, (float) $resp['data']['discount_amount'], '未达门槛无满减');
        $this->assertSame(80.0, (float) $resp['data']['paid_amount']);
        $this->assertStringNotContainsString('满减', (string) $resp['data']['remark']);
        $this->orderIds[] = (string) $this->firstOrderByUser($u1->id)->id;
    }

    // ── 拼团订单跳过满减 ──

    #[Test] public function group_buy_order_skips_full_reduction(): void
    {
        $this->makeActivity('满100减10', 100.0, 10.0);
        $service = $this->makeService(100.0);
        $promo = $this->makePromotion($service, 5);
        $u1 = $this->makeUser();

        $this->assertSame(0, $this->join($u1->id, $promo)['code']);
        $resp = $this->order($u1->id, $service, ['promotion_id' => $this->encode((int) $promo->id)]);

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame(50.0, (float) $resp['data']['paid_amount'], '拼团价 = 原价 × 50%');
        $this->assertSame(50.0, (float) $resp['data']['discount_amount'], '仅拼团折扣，无满减');
        $this->assertStringNotContainsString('满减', (string) $resp['data']['remark']);
        $this->orderIds[] = (string) $this->firstOrderByUser($u1->id)->id;
    }
}
