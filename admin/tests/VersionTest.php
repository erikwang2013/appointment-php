<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\admin\controller\VersionController;
use app\common\HashidsService;
use app\model\AppVersion;
use support\Db;
use support\Request;
use support\Response;

/**
 * APP 版本管理测试
 *
 * 覆盖：新增版本落库并出现在列表、编辑、删除、platform 非法 422、
 * 版本不存在 404。策略：真实库 + 事务回滚，不留脏数据；DB 不可用时整体跳过。
 */
class VersionTest extends TestCase
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
        // 版本管理路由需管理员身份（权限中间件层面），控制器测试直接注入
        $request->user_id = 777;
        return $request;
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    private function versionPost(array $overrides = []): array
    {
        return array_merge([
            'platform'     => 'android',
            'version_code' => '2.0.0',
            'version_name' => 'v2.0.0',
            'force_update' => 0,
            'changelog'    => '测试版本',
            'download_url' => 'https://example.com/app.apk',
            'status'       => 1,
        ], $overrides);
    }

    private function makeVersion(): AppVersion
    {
        $version = new AppVersion();
        $version->id           = AppVersion::generateId();
        $version->platform     = 'android';
        $version->version_code = '1.0.0';
        $version->version_name = 'v1.0.0';
        $version->force_update = 0;
        $version->changelog    = '初始版本';
        $version->download_url = '';
        $version->status       = 1;
        $version->save();
        return $version;
    }

    // ── 新增版本落库 + 列表 ──

    #[Test]
    public function store_creates_version_and_index_lists_it(): void
    {
        $resp = $this->body((new VersionController())->store(
            $this->makeRequest('POST', '/admin/versions', $this->versionPost(['version_code' => '3.0.0']))
        ));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertNotEmpty($resp['data']['id'], '返回 hashid');
        $id = HashidsService::decode((string) $resp['data']['id']);
        $version = AppVersion::find($id);
        $this->assertNotNull($version);
        $this->assertSame('android', (string) $version->platform);
        $this->assertSame('3.0.0', (string) $version->version_code);
        $this->assertSame('v2.0.0', (string) $version->version_name);
        $this->assertSame(0, (int) $version->force_update);
        $this->assertSame('https://example.com/app.apk', (string) $version->download_url);
        $this->assertSame(1, (int) $version->status);

        $listResp = $this->body((new VersionController())->index(
            $this->makeRequest('GET', '/admin/versions')
        ));
        $this->assertSame(0, $listResp['code']);
        $codes = array_column($listResp['data']['list'], 'version_code');
        $this->assertContains('3.0.0', $codes);
    }

    #[Test]
    public function store_rejects_invalid_platform(): void
    {
        $resp = $this->body((new VersionController())->store(
            $this->makeRequest('POST', '/admin/versions', $this->versionPost(['platform' => 'harmonyos']))
        ));

        $this->assertSame(422, $resp['code']);
    }

    // ── 编辑 ──

    #[Test]
    public function update_edits_version(): void
    {
        $version = $this->makeVersion();
        $hashid = HashidsService::encode((int) $version->id);

        $resp = $this->body((new VersionController())->update(
            $this->makeRequest('PUT', "/admin/versions/{$hashid}", [
                'version_code' => '2.5.0',
                'force_update' => 1,
                'status'       => 0,
            ]),
            $hashid
        ));
        $this->assertSame(0, $resp['code'], json_encode($resp));
        $updated = AppVersion::find($version->id);
        $this->assertSame('2.5.0', (string) $updated->version_code);
        $this->assertSame(1, (int) $updated->force_update);
        $this->assertSame(0, (int) $updated->status);
    }

    #[Test]
    public function update_missing_version_returns404(): void
    {
        $ghostHashid = HashidsService::encode(999999999999);
        $resp = $this->body((new VersionController())->update(
            $this->makeRequest('PUT', "/admin/versions/{$ghostHashid}", ['version_code' => '9.9.9']),
            $ghostHashid
        ));

        $this->assertSame(404, $resp['code']);
    }

    // ── 删除 ──

    #[Test]
    public function destroy_deletes_version(): void
    {
        $version = $this->makeVersion();
        $hashid = HashidsService::encode((int) $version->id);

        $resp = $this->body((new VersionController())->destroy(
            $this->makeRequest('DELETE', "/admin/versions/{$hashid}"),
            $hashid
        ));
        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertNull(AppVersion::find($version->id));
    }
}
