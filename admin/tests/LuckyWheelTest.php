<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\admin\controller\LuckyWheelController;
use app\common\HashidsService;
use app\model\LuckyWheel;
use support\Db;
use support\Request;
use support\Response;

/**
 * 幸运转盘奖品管理测试
 *
 * 覆盖：新增奖品落库并出现在列表、编辑与上下架、删除。
 * 策略：真实库 + 事务回滚，不留脏数据；DB 不可用时整体跳过。
 */
class LuckyWheelTest extends TestCase
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

    private function prizePost(array $overrides = []): array
    {
        return array_merge([
            'name'        => '测试奖品',
            'cost_points' => 10,
            'prize_type'  => 'points',
            'prize_value' => 100,
            'weight'      => 40,
            'stock'       => -1,
            'sort'        => 1,
            'status'      => 0,
        ], $overrides);
    }

    private function makePrize(): LuckyWheel
    {
        $prize = new LuckyWheel();
        $prize->id          = LuckyWheel::generateId();
        $prize->name        = '测试奖品';
        $prize->cost_points = 10;
        $prize->prize_type  = 'points';
        $prize->prize_value = 100;
        $prize->weight      = 40;
        $prize->stock       = -1;
        $prize->sort        = 1;
        $prize->status      = 0;
        $prize->save();
        return $prize;
    }

    // ── 新增奖品落库 + 列表 ──

    #[Test]
    public function store_creates_prize_and_index_lists_it(): void
    {
        $resp = $this->body((new LuckyWheelController())->store(
            $this->makeRequest('POST', '/admin/lucky-wheel', $this->prizePost(['name' => '100积分返还']))
        ));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertNotEmpty($resp['data']['id'], '返回 hashid');
        $id = HashidsService::decode((string) $resp['data']['id']);
        $prize = LuckyWheel::find($id);
        $this->assertNotNull($prize);
        $this->assertSame('100积分返还', (string) $prize->name);
        $this->assertSame(10, (int) $prize->cost_points);
        $this->assertSame('points', (string) $prize->prize_type);
        $this->assertSame(40, (int) $prize->weight);
        $this->assertSame(-1, (int) $prize->stock);

        $listResp = $this->body((new LuckyWheelController())->index(
            $this->makeRequest('GET', '/admin/lucky-wheel')
        ));
        $this->assertSame(0, $listResp['code']);
        $names = array_column($listResp['data']['list'], 'name');
        $this->assertContains('100积分返还', $names);
    }

    // ── 编辑 + 上下架 ──

    #[Test]
    public function update_and_toggle_status(): void
    {
        $prize = $this->makePrize();
        $hashid = HashidsService::encode((int) $prize->id);

        $resp = $this->body((new LuckyWheelController())->update(
            $this->makeRequest('PUT', "/admin/lucky-wheel/{$hashid}", ['name' => '改名称奖品', 'weight' => 60]),
            $hashid
        ));
        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame('改名称奖品', (string) LuckyWheel::find($prize->id)->name);
        $this->assertSame(60, (int) LuckyWheel::find($prize->id)->weight);

        $resp = $this->body((new LuckyWheelController())->toggleStatus(
            $this->makeRequest('POST', "/admin/lucky-wheel/{$hashid}/toggle-status", ['status' => 1]),
            $hashid
        ));
        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame(1, (int) LuckyWheel::find($prize->id)->status);

        $resp = $this->body((new LuckyWheelController())->toggleStatus(
            $this->makeRequest('POST', "/admin/lucky-wheel/{$hashid}/toggle-status"),
            $hashid
        ));
        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame(0, (int) LuckyWheel::find($prize->id)->status);
    }

    // ── 删除 ──

    #[Test]
    public function destroy_deletes_prize(): void
    {
        $prize = $this->makePrize();
        $hashid = HashidsService::encode((int) $prize->id);

        $resp = $this->body((new LuckyWheelController())->destroy(
            $this->makeRequest('DELETE', "/admin/lucky-wheel/{$hashid}"),
            $hashid
        ));
        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertNull(LuckyWheel::find($prize->id));
    }
}
