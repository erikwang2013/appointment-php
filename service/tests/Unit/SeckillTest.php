<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\api\v1\controller\SeckillController;
use app\model\Order;
use app\model\OrderItem;
use app\model\OrderPayment;
use app\model\OrderVerification;
use app\model\SeckillActivity;
use app\model\Service;
use app\model\User;
use support\Container;
use support\Db;
use support\Redis;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 秒杀闭环测试
 *
 * 覆盖：进行中活动列表（含已售量）、详情（剩余库存）、
 * 秒杀价下单（实付=秒杀价、库存扣减、seckill_id 落库）、
 * 售罄 422、未开始/已结束 422、client_token 幂等 422、
 * 下单失败回补库存、服务不匹配回补库存。
 * 基建与 GroupBuyOrderTest 一致（真实 DB + tearDown 清理）。
 */
class SeckillTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理 */
    private array $userIds = [];

    /** @var string[] 用例活动 ID，tearDown 统一清理 */
    private array $activityIds = [];

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
        foreach ($this->activityIds as $aid) {
            SeckillActivity::where('id', $aid)->delete();
        }
        foreach ($this->serviceIds as $sid) {
            Db::table('erik_service')->where('id', $sid)->delete();
        }
        foreach ($this->userIds as $uid) {
            User::where('id', $uid)->delete();
        }
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

    private function makeActivity(Service $service, array $overrides = []): SeckillActivity
    {
        $data = array_merge([
            'name'           => '限时秒杀',
            'service_id'     => $service->id,
            'seckill_price'  => 19.9,
            'original_price' => 100.0,
            'stock'          => 3,
            'start_at'       => date('Y-m-d H:i:s', time() - 3600),
            'end_at'         => date('Y-m-d H:i:s', time() + 3600),
            'status'         => 1,
        ], $overrides);

        $activity = new SeckillActivity();
        $activity->id = SeckillActivity::generateId();
        foreach ($data as $key => $value) {
            $activity->{$key} = $value;
        }
        $activity->save();
        $this->activityIds[] = $activity->id;
        return $activity;
    }

    /** 标准抢购参数（encoded）；每个用例用独立技师，避免时段锁（EX 180s）跨用例干扰 */
    private function buyPost(Service $service, string $clientToken = 'test-client-token-001', int $technicianId = 99001): array
    {
        return [
            'client_token'  => $clientToken,
            'technician_id' => $this->encode($technicianId),
            'store_id'      => $this->encode(99001),
            'service_time'  => date('Y-m-d H:i:s', time() + 86400),
            'items'         => [[
                'target_type' => 'service',
                'target_id'   => $this->encode((int) $service->id),
                'name'        => $service->name,
                'price'       => $service->price,
                'quantity'    => 1,
                'spec_info'   => ['period' => 'morning'],
            ]],
        ];
    }

    private function buy(string $userId, SeckillActivity $activity, array $post): array
    {
        $request = $this->makeRequest($post);
        $request->user_id = $userId;
        return $this->body((new SeckillController())->buy($request, $this->encode((int) $activity->id)));
    }

    private function orderOf(SeckillActivity $activity): ?Order
    {
        return Order::where('seckill_id', $activity->id)->first();
    }

    // ── 列表 / 详情 ──

    #[Test]
    public function index_lists_ongoing_activities_with_sold_count(): void
    {
        $service = $this->makeService(100.0);
        $this->makeActivity($service);
        // 已结束活动不应出现
        $this->makeActivity($service, [
            'name'     => '已结束秒杀',
            'start_at' => date('Y-m-d H:i:s', time() - 7200),
            'end_at'   => date('Y-m-d H:i:s', time() - 3600),
        ]);

        $resp = $this->body((new SeckillController())->index($this->makeRequest()));
        $this->assertSame(0, $resp['code'], json_encode($resp));

        $names = array_column($resp['data']['list'], 'name');
        $this->assertContains('限时秒杀', $names);
        $this->assertNotContains('已结束秒杀', $names);

        foreach ($resp['data']['list'] as $item) {
            if ($item['name'] === '限时秒杀') {
                $this->assertSame(0, (int) $item['sold']);
                $this->assertSame(3, (int) $item['remaining_stock']);
            }
        }
    }

    #[Test]
    public function show_returns_remaining_stock(): void
    {
        $service = $this->makeService(100.0);
        $activity = $this->makeActivity($service, ['stock' => 5]);

        $request = $this->makeRequest();
        $resp = $this->body((new SeckillController())->show($request, $this->encode((int) $activity->id)));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame(5, (int) $resp['data']['remaining_stock']);
        $this->assertSame('ongoing', $resp['data']['state']);
        $this->assertSame(19.9, (float) $resp['data']['seckill_price']);
    }

    #[Test]
    public function show_rejects_offline_activity(): void
    {
        $service = $this->makeService(100.0);
        $activity = $this->makeActivity($service, ['status' => 0]);

        $resp = $this->body((new SeckillController())->show($this->makeRequest(), $this->encode((int) $activity->id)));
        $this->assertSame(422, $resp['code']);
    }

    // ── 抢购下单 ──

    #[Test]
    public function buy_creates_order_at_seckill_price(): void
    {
        $user = $this->makeUser();
        $service = $this->makeService(100.0);
        $activity = $this->makeActivity($service, ['stock' => 3]);

        $resp = $this->buy((string) $user->id, $activity, $this->buyPost($service));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $order = $this->orderOf($activity);
        $this->assertNotNull($order, '订单已创建');
        $this->orderIds[] = $order->id;
        $this->assertSame(19.9, (float) $order->paid_amount, '实付为秒杀价');
        $this->assertSame(100.0, (float) $order->total_amount, '订单项原价');
        $this->assertSame(80.1, (float) $order->discount_amount);
        $this->assertSame((string)$activity->id, (string)$order->seckill_id);

        $activity->refresh();
        $this->assertSame(2, (int) $activity->stock, '库存扣减 1');
    }

    #[Test]
    public function buy_rejected_when_sold_out(): void
    {
        $user = $this->makeUser();
        $service = $this->makeService(100.0);
        $activity = $this->makeActivity($service, ['stock' => 0]);

        $resp = $this->buy((string) $user->id, $activity, $this->buyPost($service));

        $this->assertSame(422, $resp['code']);
        $this->assertSame('已售罄', $resp['message']);
        $this->assertNull($this->orderOf($activity));
    }

    #[Test]
    public function buy_rejected_outside_time_window(): void
    {
        $user = $this->makeUser();
        $service = $this->makeService(100.0);
        $notStarted = $this->makeActivity($service, [
            'start_at' => date('Y-m-d H:i:s', time() + 3600),
            'end_at'   => date('Y-m-d H:i:s', time() + 7200),
        ]);
        $ended = $this->makeActivity($service, [
            'start_at' => date('Y-m-d H:i:s', time() - 7200),
            'end_at'   => date('Y-m-d H:i:s', time() - 3600),
        ]);

        $resp = $this->buy((string) $user->id, $notStarted, $this->buyPost($service));
        $this->assertSame(422, $resp['code']);
        $this->assertSame('秒杀未开始', $resp['message']);

        $resp = $this->buy((string) $user->id, $ended, $this->buyPost($service));
        $this->assertSame(422, $resp['code']);
        $this->assertSame('秒杀已结束', $resp['message']);

        $this->assertNull($this->orderOf($notStarted));
        $this->assertNull($this->orderOf($ended));
    }

    #[Test]
    public function buy_rejected_on_duplicate_client_token(): void
    {
        $user = $this->makeUser();
        $service = $this->makeService(100.0);
        $activity = $this->makeActivity($service, ['stock' => 3]);

        $post = $this->buyPost($service, 'same-token-0000001', 99002);
        $first = $this->buy((string) $user->id, $activity, $post);
        $this->assertSame(0, $first['code'], json_encode($first));
        $this->orderIds[] = $this->orderOf($activity)->id;

        $second = $this->buy((string) $user->id, $activity, $post);
        $this->assertSame(422, $second['code']);
        $this->assertSame('请勿重复提交', $second['message']);

        $activity->refresh();
        $this->assertSame(2, (int) $activity->stock, '重复提交不重复扣库存');
    }

    #[Test]
    public function buy_restores_stock_when_order_creation_fails(): void
    {
        $user = $this->makeUser();
        $service = $this->makeService(100.0);
        $activity = $this->makeActivity($service, ['stock' => 3]);

        // 占用技师时段：先造一条 pending 订单占住该技师同一服务时间
        $slotTime = date('Y-m-d H:i:s', time() + 86400);
        $conflictTech = 99003;
        $conflict = Order::create([
            'id'            => Order::generateId(),
            'order_no'      => 'CONFLICT' . substr((string) random_int(10000000, 99999999), 0, 8),
            'user_id'       => $user->id,
            'technician_id' => $conflictTech,
            'order_type'    => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'  => 100.0,
            'discount_amount' => 0.0,
            'paid_amount'   => 100.0,
            'service_time'  => $slotTime,
            'status'        => Order::STATUS_PENDING,
        ]);
        $this->orderIds[] = $conflict->id;

        $resp = $this->buy((string) $user->id, $activity, $this->buyPost($service, 'test-client-token-001', $conflictTech));
        $this->assertSame(400, $resp['code'], json_encode($resp));
        $this->assertSame('该时段技师已被预约，请选择其他时间段', $resp['message']);

        $activity->refresh();
        $this->assertSame(3, (int) $activity->stock, '下单失败回补库存');
        $this->assertSame(0, Order::where('seckill_id', $activity->id)->count(), '未生成秒杀订单');
    }

    #[Test]
    public function buy_restores_stock_when_service_mismatch(): void
    {
        $user = $this->makeUser();
        $service = $this->makeService(100.0);
        $other = $this->makeService(200.0);
        $activity = $this->makeActivity($service, ['stock' => 3]);

        $post = $this->buyPost($service, 'test-client-token-001', 99004);
        $post['items'][0]['target_id'] = $this->encode((int) $other->id);

        $resp = $this->buy((string) $user->id, $activity, $post);
        $this->assertSame(422, $resp['code']);
        $this->assertSame('订单服务与秒杀活动不匹配', $resp['message']);

        $activity->refresh();
        $this->assertSame(3, (int) $activity->stock, '服务不匹配回补库存');
        $this->assertSame(0, Order::where('seckill_id', $activity->id)->count());
    }

    #[Test]
    public function buy_ignores_redis_lock_release_of_others(): void
    {
        // 锁 token 校验：模拟锁已被他人持有/过期覆盖，释放不应误删
        $user = $this->makeUser();
        $service = $this->makeService(100.0);
        $activity = $this->makeActivity($service, ['stock' => 3]);

        $lockKey = "seckill_buy:{$activity->id}";
        Redis::connection()->set($lockKey, 'someone-else-token', 'EX', 30);

        $resp = $this->buy((string) $user->id, $activity, $this->buyPost($service, 'test-client-token-001', 99005));
        $this->assertSame(422, $resp['code']);
        $this->assertSame('抢购人数过多，请稍后重试', $resp['message']);
        $this->assertSame('someone-else-token', (string) Redis::get($lockKey), '他人锁未被释放');
    }
}
