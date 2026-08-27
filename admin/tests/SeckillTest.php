<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\admin\controller\SeckillController;
use app\common\HashidsService;
use app\model\Order;
use app\model\SeckillActivity;
use app\model\Service;
use support\Db;
use support\Request;
use support\Response;

/**
 * 秒杀活动管理测试
 *
 * 覆盖：新增活动落库并出现在列表、价格/服务校验、编辑与上下架、
 * 删除、秒杀订单列表（按 seckill_id 过滤）。
 * 策略：真实库 + 事务回滚，不留脏数据；DB 不可用时整体跳过。
 * Service 模型带 Scout 搜索索引，测试环境索引引擎不可用，直接经 Db::table 落库。
 */
class SeckillTest extends TestCase
{
    private static bool $dbReady = false;
    private static bool $dbChecked = false;

    protected function setUp(): void
    {
        if (!self::$dbChecked) {
            self::$dbChecked = true;
            try {
                Db::select('SELECT 1');
                self::$dbReady = true;
            } catch (\Throwable) {
                self::$dbReady = false;
            }
        }
        if (!self::$dbReady) {
            $this->markTestSkipped('数据库不可用');
        }

        $this->bootEloquent();
        Db::beginTransaction();
    }

    protected function tearDown(): void
    {
        if (self::$dbReady) {
            Db::rollBack();
        }
    }

    private function bootEloquent(): void
    {
        $dbConfig = config('database.connections.default');
        $capsule = new \Illuminate\Database\Capsule\Manager();
        $capsule->addConnection([
            'driver'    => $dbConfig['driver'] ?? 'mysql',
            'host'      => $dbConfig['host'] ?? getenv('DB_HOST') ?: '127.0.0.1',
            'port'      => $dbConfig['port'] ?? getenv('DB_PORT') ?: '3306',
            'database'  => $dbConfig['database'] ?? getenv('DB_DATABASE') ?: 'appointment',
            'username'  => $dbConfig['username'] ?? getenv('DB_USERNAME') ?: 'root',
            'password'  => $dbConfig['password'] ?? getenv('DB_PASSWORD') ?: '',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    private function makeRequest(string $method, string $path, array $post = []): Request
    {
        $request = new Request("$method $path HTTP/1.1\r\nHost: localhost\r\n\r\n");
        if ($post) {
            $request->setPost($post);
        }
        return $request;
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    private function makeService(): string
    {
        $id = Service::generateId();
        $now = date('Y-m-d H:i:s');
        Db::table('appointment_service')->insert([
            'id'             => $id,
            'category_id'    => 1,
            'name'           => '秒杀管理测试服务',
            'cover_image'    => '',
            'price'          => 100.0,
            'original_price' => 100.0,
            'duration'       => 30,
            'sales_volume'   => 0,
            'sort'           => 0,
            'status'         => 1,
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);
        return $id;
    }

    private function activityPost(string $serviceId, array $overrides = []): array
    {
        return array_merge([
            'name'           => '限时秒杀',
            'service_id'     => HashidsService::encode((int) $serviceId),
            'seckill_price'  => 19.9,
            'original_price' => 100.0,
            'stock'          => 3,
            'start_at'       => date('Y-m-d H:i:s', time() - 3600),
            'end_at'         => date('Y-m-d H:i:s', time() + 3600),
            'status'         => 1,
        ], $overrides);
    }

    private function makeActivity(): SeckillActivity
    {
        $activity = new SeckillActivity();
        $activity->id             = SeckillActivity::generateId();
        $activity->name           = '限时秒杀';
        $activity->service_id     = $this->makeService();
        $activity->seckill_price  = 19.9;
        $activity->original_price = 100.0;
        $activity->stock          = 3;
        $activity->start_at       = date('Y-m-d H:i:s', time() - 3600);
        $activity->end_at         = date('Y-m-d H:i:s', time() + 3600);
        $activity->status         = 1;
        $activity->save();
        return $activity;
    }

    // ── 新增落库 + 列表 ──

    #[Test]
    public function store_creates_activity_and_index_lists_it(): void
    {
        $serviceId = $this->makeService();
        $resp = $this->body((new SeckillController())->store(
            $this->makeRequest('POST', '/admin/seckill', $this->activityPost($serviceId))
        ));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertNotEmpty($resp['data']['id'], '返回 hashid');
        $id = HashidsService::decode((string) $resp['data']['id']);
        $activity = SeckillActivity::find($id);
        $this->assertNotNull($activity);
        $this->assertSame('限时秒杀', (string) $activity->name);
        $this->assertSame((string) $serviceId, (string) $activity->service_id);
        $this->assertSame(19.9, (float) $activity->seckill_price);
        $this->assertSame(3, (int) $activity->stock);

        $listResp = $this->body((new SeckillController())->index(
            $this->makeRequest('GET', '/admin/seckill', ['name' => '限时秒杀'])
        ));
        $this->assertSame(0, $listResp['code']);
        $names = array_column($listResp['data']['list'], 'name');
        $this->assertContains('限时秒杀', $names);
    }

    #[Test]
    public function store_rejects_invalid_price_and_service(): void
    {
        $serviceId = $this->makeService();

        $resp = $this->body((new SeckillController())->store(
            $this->makeRequest('POST', '/admin/seckill', $this->activityPost($serviceId, [
                'seckill_price'  => 120.0,
                'original_price' => 100.0,
            ]))
        ));
        $this->assertSame(422, $resp['code']);
        $this->assertSame('原价不能低于秒杀价', $resp['message']);

        $resp = $this->body((new SeckillController())->store(
            $this->makeRequest('POST', '/admin/seckill', $this->activityPost($serviceId, [
                'service_id' => HashidsService::encode(999999999),
            ]))
        ));
        $this->assertSame(422, $resp['code']);
        $this->assertSame('服务不存在', $resp['message']);
    }

    // ── 编辑 + 上下架 ──

    #[Test]
    public function update_and_toggle_status(): void
    {
        $activity = $this->makeActivity();
        $hashid = HashidsService::encode((int) $activity->id);

        $resp = $this->body((new SeckillController())->update(
            $this->makeRequest('PUT', "/admin/seckill/{$hashid}", ['name' => '改名称秒杀', 'stock' => 8]),
            $hashid
        ));
        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame('改名称秒杀', (string) SeckillActivity::find($activity->id)->name);
        $this->assertSame(8, (int) SeckillActivity::find($activity->id)->stock);

        $resp = $this->body((new SeckillController())->toggleStatus(
            $this->makeRequest('POST', "/admin/seckill/{$hashid}/toggle-status"),
            $hashid
        ));
        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame(0, (int) SeckillActivity::find($activity->id)->status);

        $resp = $this->body((new SeckillController())->toggleStatus(
            $this->makeRequest('POST', "/admin/seckill/{$hashid}/toggle-status"),
            $hashid
        ));
        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame(1, (int) SeckillActivity::find($activity->id)->status);
    }

    // ── 删除 ──

    #[Test]
    public function destroy_deletes_activity(): void
    {
        $activity = $this->makeActivity();
        $hashid = HashidsService::encode((int) $activity->id);

        $resp = $this->body((new SeckillController())->destroy(
            $this->makeRequest('DELETE', "/admin/seckill/{$hashid}"),
            $hashid
        ));
        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertNull(SeckillActivity::find($activity->id));
    }

    // ── 秒杀订单列表 ──

    #[Test]
    public function orders_lists_seckill_orders_by_activity(): void
    {
        $activity = $this->makeActivity();
        $hashid = HashidsService::encode((int) $activity->id);
        $orderNo = 'SK' . substr((string) random_int(10000000, 99999999), 0, 8);

        $order = new Order();
        $order->id              = Order::generateId();
        $order->order_no        = $orderNo;
        $order->user_id         = 99001;
        $order->technician_id   = 99002;
        $order->order_type      = Order::ORDER_TYPE_APPOINTMENT;
        $order->total_amount    = 100.0;
        $order->discount_amount = 80.1;
        $order->paid_amount     = 19.9;
        $order->service_time    = date('Y-m-d H:i:s', time() + 86400);
        $order->status          = Order::STATUS_PENDING;
        $order->seckill_id      = $activity->id;
        $order->save();
        $this->assertNotNull($order, '订单已创建');

        $resp = $this->body((new SeckillController())->orders(
            $this->makeRequest('GET', "/admin/seckill/{$hashid}/orders"),
            $hashid
        ));
        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame(1, (int) $resp['data']['total']);
        $this->assertSame($orderNo, $resp['data']['list'][0]['order_no']);
        $this->assertSame(HashidsService::encode((int) $activity->id), $resp['data']['activity']['id']);

        $resp = $this->body((new SeckillController())->orders(
            $this->makeRequest('GET', "/admin/seckill/{$hashid}/orders", ['order_no' => 'NOMATCH']),
            $hashid
        ));
        $this->assertSame(0, $resp['code']);
        $this->assertSame(0, (int) $resp['data']['total']);
    }
}
