<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\admin\controller\ProfitSharingController;
use app\model\ProfitSharing;
use support\Db;
use support\Request;
use support\Response;

/**
 * 微信分账记录管理测试
 *
 * 覆盖：列表分页 + 订单号/技师名联查、状态筛选。
 * 策略：真实库 + 事务回滚，不留脏数据；DB 不可用时整体跳过。
 */
class ProfitSharingControllerTest extends TestCase
{
    private static bool $dbReady = false;
    private static bool $dbChecked = false;

    /** 事务内基线（共享库可能残留他人数据，计数断言用增量） */
    private int $baseTotal = 0;

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
        $this->baseTotal = ProfitSharing::count();
    }

    protected function tearDown(): void
    {
        if (self::$dbReady) {
            Db::rollBack();
        }
    }

    /** 重建全局 Eloquent 连接（prefix 空，模型 $table 已内嵌 erik_ 前缀） */
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

    /** 造分账记录 + 对应订单/技师用户行 */
    private function makeSharing(string $status, string $orderNo = '', string $techName = ''): string
    {
        $id        = (string) (90000000000009000 + random_int(1, 999));
        $userId    = (string) (99000000000008000 + random_int(1, 999));
        $orderId   = (string) (98000000000007000 + random_int(1, 999));
        $sharingNo = $orderNo ?: 'PS' . $id . random_int(1000, 9999);

        $now = date('Y-m-d H:i:s');
        Db::table('erik_user')->insertOrIgnore([
            'id' => $userId, 'phone' => '138' . substr((string) random_int(10000000, 99999999), 0, 8),
            'nickname' => $techName ?: '分账技师', 'user_type' => 'technician',
            'status' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        Db::table('erik_order')->insertOrIgnore([
            'id' => $orderId, 'order_no' => $sharingNo, 'user_id' => $userId, 'technician_id' => $userId,
            'order_type' => 'service', 'total_amount' => 100, 'paid_amount' => 100,
            'status' => 'paid', 'created_at' => $now, 'updated_at' => $now,
        ]);
        Db::table('erik_profit_sharing')->insertOrIgnore([
            'id' => $id, 'user_id' => $userId, 'order_id' => $orderId, 'sharing_no' => $sharingNo,
            'amount' => 70.00, 'ratio' => 0.7000, 'status' => $status,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        return $id;
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    private function fetch(string $query = ''): array
    {
        $request = new Request("GET /admin/profit-sharing{$query} HTTP/1.1\r\nHost: localhost\r\n\r\n");
        return $this->body((new ProfitSharingController())->index($request));
    }

    #[Test]
    public function index_returns_paginated_list_with_join(): void
    {
        $id      = $this->makeSharing('pending', 'PSLIST' . random_int(100000, 999999), '联查技师');
        $orderNo = Db::table('erik_profit_sharing')->where('id', $id)->value('sharing_no');

        $data = $this->fetch();

        $this->assertSame(0, $data['code'], json_encode($data));
        $this->assertSame($this->baseTotal + 1, $data['data']['total'], '总数为基线+本用例');
        $row = collect($data['data']['list'])->firstWhere('sharing_no', $orderNo);
        $this->assertNotNull($row, '列表应包含本用例分账记录');
        $this->assertSame($orderNo, $row['order_no'], '应联查订单号');
        $this->assertStringContainsString('联查技师', $row['nickname'] ?? '', '应联查技师昵称');
        $this->assertNotSame((string) $id, (string) $row['id'], 'id 应 hashid 编码');
    }

    #[Test]
    public function index_filters_by_status(): void
    {
        $this->makeSharing('success', 'PSSUC' . random_int(100000, 999999));
        $this->makeSharing('failed', 'PSFAIL' . random_int(100000, 999999));

        $data = $this->fetch('?status=success');

        $this->assertSame(0, $data['code'], json_encode($data));
        $this->assertGreaterThan(0, $data['data']['total'], 'success 筛选应有结果');
        foreach ($data['data']['list'] as $row) {
            $this->assertSame('success', $row['status'], '筛选结果状态应全为 success');
        }
        $failed = collect($data['data']['list'])->firstWhere('sharing_no', 'like', 'PSFAIL%');
        $this->assertNull($failed, 'success 筛选不得包含 failed 记录');
    }
}
