<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\api\v1\controller\VersionController;
use app\model\AppVersion;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * APP 版本检查测试（公开接口，无需登录）
 *
 * 覆盖：最新上架版本返回、platform 非法 422、无上架版本返回空对象、
 * ios 平台返回 ios 版本。真实 DB + tearDown 清理（含演示种子状态恢复）。
 */
class AppVersionTest extends TestCase
{
    /** @var string[] 用例版本 ID，tearDown 统一清理 */
    private array $versionIds = [];

    protected function tearDown(): void
    {
        foreach ($this->versionIds as $id) {
            AppVersion::where('id', $id)->delete();
        }
        // 恢复演示种子上架状态（测试期间置为下架以隔离查询）
        AppVersion::whereIn('id', ['10000000000000001', '10000000000000002'])
            ->where('status', 0)
            ->update(['status' => 1]);
        $this->versionIds = [];
    }

    private function makeRequest(string $uri): Request
    {
        return new Request("GET $uri HTTP/1.1\r\nHost: localhost\r\n\r\n");
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    /** 直接插入一个版本（默认 android 上架） */
    private function makeVersion(array $overrides = []): AppVersion
    {
        $version = AppVersion::create(array_merge([
            'id'           => AppVersion::generateId(),
            'platform'     => 'android',
            'version_code' => '1.0.0',
            'version_name' => 'v1.0.0',
            'force_update' => 0,
            'changelog'    => '测试版本',
            'download_url' => '',
            'status'       => 1,
        ], $overrides));
        $this->versionIds[] = $version->id;
        return $version;
    }

    /** 测试期间隐藏演示种子，隔离「无版本」场景 */
    private function hideSeedVersions(): void
    {
        AppVersion::whereIn('id', ['10000000000000001', '10000000000000002'])
            ->update(['status' => 0]);
    }

    #[Test]
    public function returnsLatestOnShelfVersion(): void
    {
        $this->hideSeedVersions();
        $older = $this->makeVersion([
            'version_code' => '1.5.0',
            'updated_at'   => date('Y-m-d H:i:s', time() - 3600),
        ]);
        $newer = $this->makeVersion([
            'version_code' => '2.0.0',
            'version_name' => 'v2.0.0',
            'force_update' => 1,
            'changelog'    => '新增检测更新',
            'updated_at'   => date('Y-m-d H:i:s', time() + 3600),
        ]);

        $data = $this->body((new VersionController())->index(
            $this->makeRequest('/api/app/version?platform=android')
        ))['data'];

        $this->assertNotEmpty($data['id']);
        $this->assertSame('2.0.0', $data['version_code']);
        $this->assertSame('v2.0.0', $data['version_name']);
        $this->assertSame(1, $data['force_update']);
        $this->assertSame('新增检测更新', $data['changelog']);
        $this->assertSame('android', $data['platform']);
    }

    #[Test]
    public function invalidPlatformReturns422(): void
    {
        $response = $this->body((new VersionController())->index(
            $this->makeRequest('/api/app/version?platform=wp')
        ));

        $this->assertSame(422, $response['code']);
        $this->assertSame('platform 仅支持 android/ios', $response['message']);
    }

    #[Test]
    public function missingPlatformReturns422(): void
    {
        $response = $this->body((new VersionController())->index(
            $this->makeRequest('/api/app/version')
        ));

        $this->assertSame(422, $response['code']);
        $this->assertSame('platform 仅支持 android/ios', $response['message']);
    }

    #[Test]
    public function noOnShelfVersionReturnsEmptyObject(): void
    {
        $this->hideSeedVersions();

        $response = $this->body((new VersionController())->index(
            $this->makeRequest('/api/app/version?platform=android')
        ));

        $this->assertSame(0, $response['code']);
        $this->assertSame([], $response['data']);
    }

    #[Test]
    public function iosPlatformReturnsIosVersion(): void
    {
        // 演示种子 ios 版本保持上架
        $data = $this->body((new VersionController())->index(
            $this->makeRequest('/api/app/version?platform=ios')
        ))['data'];

        $this->assertSame('ios', $data['platform']);
        $this->assertSame('1.0.0', $data['version_code']);
    }
}
