<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\admin\controller\DashboardController;
use app\model\Order;
use app\model\TechnicianProfile;
use support\Redis;
use support\Request;
use support\Response;

/**
 * 仪表盘接口测试（admin 端 DashboardController）
 *
 * 与 ReportControllerTest 同基建：自建 Capsule 连接真实 MySQL。
 * 覆盖 index() 返回结构：stats 卡片（待审技师/今日预约）/趋势序列/分布/时段分布/
 * 排行榜，以及缓存键 svc:dashboard:data 写入。
 *
 * 缓存键含 range（svc:dashboard:data:{range}），setUp/tearDown 删除
 * 用到的各 range 键避免命中残留缓存。
 */
class DashboardControllerTest extends TestCase
{
    /** @var string[] 用例创建的订单 ID，tearDown 统一清理 */
    private array $orderIds = [];

    /** @var string[] 用例创建的技师档案 ID，tearDown 统一清理 */
    private array $profileIds = [];

    protected function setUp(): void
    {
        foreach (['svc:dashboard:data', 'svc:dashboard:data:1', 'svc:dashboard:data:7', 'svc:dashboard:data:30', 'svc:dashboard:data:92'] as $key) {
            Redis::del($key);
        }

        $capsule = new \Illuminate\Database\Capsule\Manager();
        $capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => env('DB_HOST', '127.0.0.1'),
            'port'      => env('DB_PORT', '3306'),
            'database'  => env('DB_DATABASE', 'appointment'),
            'username'  => env('DB_USERNAME', 'root'),
            'password'  => env('DB_PASSWORD', ''),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    protected function tearDown(): void
    {
        foreach (['svc:dashboard:data', 'svc:dashboard:data:1', 'svc:dashboard:data:7', 'svc:dashboard:data:30', 'svc:dashboard:data:92'] as $key) {
            Redis::del($key);
        }
        foreach ($this->orderIds as $id) {
            Order::where('id', $id)->delete();
        }
        foreach ($this->profileIds as $id) {
            TechnicianProfile::where('id', $id)->forceDelete();
        }
        $this->orderIds = $this->profileIds = [];
    }

    private function makeRequest(): Request
    {
        return new Request("GET /admin/dashboard HTTP/1.1\r\nHost: localhost\r\n\r\n");
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    private function makeTechnician(string $status, string $name = '仪表盘技师', float $rating = 5.0): TechnicianProfile
    {
        $profile = new TechnicianProfile();
        $profile->id        = TechnicianProfile::generateId();
        $profile->user_id   = (string) (9900000000000000 + random_int(1, 999999));
        $profile->real_name = $name;
        $profile->rating    = $rating;
        $profile->status    = $status;
        $profile->save();
        $this->profileIds[] = $profile->id;
        return $profile;
    }

    private function makeOrder(string $technicianId, string $status, float $paidAmount): Order
    {
        $order = Order::forceCreate([
            'id'              => Order::generateId(),
            'order_no'        => 'ORD_DASH_' . uniqid(),
            'user_id'         => (string) (9900000000000000 + random_int(1, 999999)),
            'technician_id'   => $technicianId,
            'order_type'      => 'appointment',
            'total_amount'    => $paidAmount,
            'discount_amount' => 0.0,
            'paid_amount'     => $paidAmount,
            'status'          => $status,
            'created_at'      => date('Y-m-d') . ' 10:00:00',
        ]);
        $this->orderIds[] = $order->id;
        return $order;
    }

    #[Test] public function index_returns_structure_with_pending_technician_and_today_appointment(): void
    {
        $this->makeTechnician('pending', '待审技师甲');
        $approved = $this->makeTechnician('approved', '已审技师乙', 4.9);
        $this->makeOrder($approved->id, 'completed', 200.0);

        $resp = (new DashboardController())->index($this->makeRequest());
        $data = $this->body($resp)['data'];
        $this->assertSame(0, $this->body($resp)['code']);

        // stats 卡片：待审技师只统计 pending，今日预约命中今日订单
        $stats = array_column($data['stats'], null, 'label');
        $this->assertSame('1', $stats['待审技师']['value']);
        $this->assertSame('1', $stats['今日预约']['value']);

        // 趋势：30 天日期序列（倒序，dates[0]=今天），5 条序列，每日预约首日（今天）= 1
        $this->assertCount(30, $data['trends']['dates']);
        $this->assertCount(5, $data['trends']['series']);
        $this->assertSame(date('Y-m-d'), $data['trends']['dates'][0]);
        $series = array_column($data['trends']['series'], null, 'name');
        $this->assertSame(1, $series['每日预约']['data'][0]);

        // 分布与时段：user_status 数组，24 小时，今日订单计入 10:00 时段
        $this->assertArrayHasKey('user_status', $data['distribution']);
        $this->assertCount(24, $data['peak_hours']['hours']);
        $this->assertGreaterThanOrEqual(1, $data['peak_hours']['hours'][10]['count']);

        // 排行榜：approved 技师上榜（ID 已编码为 hashid），pending 技师不在榜
        $this->assertNotEmpty($data['technician_ranking']);
        $ranked = array_column($data['technician_ranking'], null, 'technician_name');
        $this->assertArrayHasKey('已**', $ranked);
        $this->assertEquals(4.9, $ranked['已**']['rating']);
        $this->assertNotSame($approved->id, $ranked['已**']['technician_id']);

        // 其余区块结构齐全
        $this->assertArrayHasKey('stores', $data['store_comparison']);
        $this->assertSame('近7天', $data['store_comparison']['range']);
        $this->assertIsArray($data['service_ranking']);
        $this->assertIsArray($data['recent_logs']);

        // 缓存键写入 svc:dashboard:data:7（默认 range），无固定键/无前缀缺失
        $this->assertNotEmpty(Redis::get('svc:dashboard:data:7'));
        $this->assertNull(Redis::get('svc:dashboard:data'));
        $this->assertNull(Redis::get('dashboard:data'));
    }

    #[Test] public function store_comparison_range_is_validated_and_keyed(): void
    {
        // 缓存键含 range，非法/越界 range 一律回退 7，避免 strtotime 落到 1970 日期
        foreach (['0', '-5', '999', 'abc'] as $bad) {
            Redis::del('svc:dashboard:data:7');
            $resp = (new DashboardController())->index(new Request("GET /admin/dashboard?range={$bad} HTTP/1.1\r\nHost: localhost\r\n\r\n"));
            $this->assertSame(0, $this->body($resp)['code']);
            $this->assertSame('近7天', $this->body($resp)['data']['store_comparison']['range']);
        }

        // 边界值 1 与 92 正常透传，且各自写入独立缓存键
        Redis::del('svc:dashboard:data:1');
        $resp = (new DashboardController())->index(new Request("GET /admin/dashboard?range=1 HTTP/1.1\r\nHost: localhost\r\n\r\n"));
        $this->assertSame('近1天', $this->body($resp)['data']['store_comparison']['range']);
        $this->assertNotEmpty(Redis::get('svc:dashboard:data:1'));

        Redis::del('svc:dashboard:data:92');
        $resp = (new DashboardController())->index(new Request("GET /admin/dashboard?range=92 HTTP/1.1\r\nHost: localhost\r\n\r\n"));
        $this->assertSame('近92天', $this->body($resp)['data']['store_comparison']['range']);
        $this->assertNotEmpty(Redis::get('svc:dashboard:data:92'));

        // 始终无固定键写入（range 不再依赖固定键缓存，杜绝串味）
        $this->assertNull(Redis::get('svc:dashboard:data'));
    }

    #[Test] public function range_cache_keys_do_not_cross_contaminate(): void
    {
        // range=7 与 range=30 写各自缓存键，第二次请求不命中第一次的数据
        (new DashboardController())->index(new Request("GET /admin/dashboard?range=7 HTTP/1.1\r\nHost: localhost\r\n\r\n"));
        $this->assertNotEmpty(Redis::get('svc:dashboard:data:7'));
        $this->assertNull(Redis::get('svc:dashboard:data:30'));

        $resp = (new DashboardController())->index(new Request("GET /admin/dashboard?range=30 HTTP/1.1\r\nHost: localhost\r\n\r\n"));
        $this->assertSame(0, $this->body($resp)['code']);
        $this->assertSame('近30天', $this->body($resp)['data']['store_comparison']['range']);
        $this->assertNotEmpty(Redis::get('svc:dashboard:data:30'));
    }
}
