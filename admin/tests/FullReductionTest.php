<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\admin\controller\FullReductionController;
use app\common\HashidsService;
use app\model\FullReductionActivity;
use support\Db;
use support\Request;
use support\Response;

/**
 * 满减活动管理测试
 *
 * 覆盖：新增活动落库、编辑标题、上下架状态切换。
 * 策略：真实库 + 事务回滚，不留脏数据；DB 不可用时整体跳过。
 */
class FullReductionTest extends TestCase
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

    private function makeActivity(string $title = '满100减10'): FullReductionActivity
    {
        $activity = new FullReductionActivity();
        $activity->id        = '90000000000003201';
        $activity->title     = $title;
        $activity->threshold = 100.0;
        $activity->reduction = 10.0;
        $activity->status    = 1;
        $activity->start_at  = date('Y-m-d H:i:s', time() - 3600);
        $activity->end_at    = date('Y-m-d H:i:s', time() + 3600);
        $activity->save();
        return $activity;
    }

    // ── 新增活动落库 ──

    #[Test] public function store_creates_activity(): void
    {
        $post = [
            'title'     => '周年庆满减',
            'threshold' => '200.00',
            'reduction' => '30.00',
            'status'    => '1',
            'start_at'  => date('Y-m-d H:i:s', time() - 3600),
            'end_at'    => date('Y-m-d H:i:s', time() + 3600),
        ];

        $resp = $this->body((new FullReductionController())->store($this->makeRequest('POST', '/admin/full-reduction-activities', $post)));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertNotEmpty($resp['data']['id'], '返回 hashid');
        $id = HashidsService::decode((string) $resp['data']['id']);
        $activity = FullReductionActivity::find($id);
        $this->assertNotNull($activity);
        $this->assertSame('周年庆满减', (string) $activity->title);
        $this->assertSame('200.00', (string) $activity->threshold);
        $this->assertSame('30.00', (string) $activity->reduction);
        $this->assertSame(1, (int) $activity->status);
    }

    // ── 编辑活动 ──

    #[Test] public function update_edits_activity(): void
    {
        $activity = $this->makeActivity();
        $hashid = HashidsService::encode((int) $activity->id);

        $resp = $this->body((new FullReductionController())->update(
            $this->makeRequest('PUT', "/admin/full-reduction-activities/{$hashid}", ['title' => '改标题满减']),
            $hashid
        ));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame('改标题满减', (string) FullReductionActivity::find($activity->id)->title);
        $this->assertSame('100.00', (string) FullReductionActivity::find($activity->id)->threshold, '未传字段不受影响');
    }

    // ── 上下架 ──

    #[Test] public function toggle_status_flips_on_and_off(): void
    {
        $activity = $this->makeActivity();
        $hashid = HashidsService::encode((int) $activity->id);

        $resp = $this->body((new FullReductionController())->toggleStatus(
            $this->makeRequest('POST', "/admin/full-reduction-activities/{$hashid}/toggle-status", ['status' => '0']),
            $hashid
        ));
        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame(0, (int) FullReductionActivity::find($activity->id)->status, '已下架');

        $resp = $this->body((new FullReductionController())->toggleStatus(
            $this->makeRequest('POST', "/admin/full-reduction-activities/{$hashid}/toggle-status", ['status' => '1']),
            $hashid
        ));
        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame(1, (int) FullReductionActivity::find($activity->id)->status, '已上架');
    }
}
