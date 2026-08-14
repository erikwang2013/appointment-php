<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\middleware\AdminPermission;
use support\Redis;
use support\Request;
use support\Response;
use Webman\Route;

/**
 * AdminPermission 中间件权限匹配测试（第 4 轮审计 S6）
 *
 * 修复背景：中间件原按 method.path 精确匹配，动态路由（/admin/user/123）
 * 永远无法命中种子权限 get.admin/user/{id}；且种子缺漏大量路由权限。
 * 修复后中间件按已注册路由模式做 {id} 通配归一化。
 *
 * 覆盖：
 *   - 动态路由 {id} 通配：种子 get.admin/user/{id} 命中 /admin/user/123
 *   - 多段通配：post.admin/user/{id}/toggle-status 命中 /admin/user/123/toggle-status
 *   - 无匹配权限 → 403
 *   - 精确 slug 兜底：权限表存具体 ID 仍放行
 *   - 超级管理员 * 放行 / 未认证（无 adminId）跳过
 *   - 种子覆盖：/admin 组全部路由 slug 均存在于权限种子（两份 SQL）
 *
 * 注意：routeSlugs() 有进程内静态缓存，必须在首次 process() 调用前注册齐
 * 全部测试路由 —— setUp() 统一注册。
 */
class AdminPermissionTest extends TestCase
{
    private const ADMIN_ID = 777;
    private const CACHE_KEY = 'perm:777';

    private array $redisKeys = [];

    protected function setUp(): void
    {
        // 统一注册测试路由（任何 process() 调用之前），仅注册一次。
        // 注：Route::get() 依赖路由收集器（$collector），单元测试环境下为 null；
        // 中间件只读 Route::getRoutes()（static::$allRoutes），直接追加 RouteObject 即可。
        static $registered = false;
        if ($registered) {
            return;
        }
        $this->registerRoute('GET', '/admin/user');
        $this->registerRoute('GET', '/admin/user/{id}');
        $this->registerRoute('POST', '/admin/user/{id}/toggle-status');
        $registered = true;
    }

    private function registerRoute(string $method, string $path): void
    {
        $route = new \Webman\Route\Route([$method], $path, fn () => json(['code' => 0]));
        $prop = new \ReflectionProperty(\Webman\Route::class, 'allRoutes');
        $prop->setAccessible(true);
        $all = $prop->getValue();
        $all[] = $route;
        $prop->setValue(null, $all);
    }

    protected function tearDown(): void
    {
        foreach ($this->redisKeys as $key) {
            try {
                Redis::del($key);
            } catch (\Throwable) {
                // Redis 不可用时静默
            }
        }
        $this->redisKeys = [];
    }

    // ── 工具方法 ──

    private function redisAvailable(): bool
    {
        try {
            $probe = 'test:probe:' . uniqid();
            $this->trackKey($probe);
            Redis::setex($probe, 5, '1');
            return Redis::get($probe) === '1';
        } catch (\Throwable) {
            return false;
        }
    }

    private function trackKey(string $key): void
    {
        if (!in_array($key, $this->redisKeys, true)) {
            $this->redisKeys[] = $key;
        }
    }

    private function makeRequest(string $method, string $path): Request
    {
        return new Request("$method $path HTTP/1.1\r\nHost: localhost\r\n\r\n");
    }

    /** 以指定权限列表运行中间件，返回最终响应 */
    private function runMiddleware(string $method, string $path, array $permissions): Response
    {
        if (!$this->redisAvailable()) {
            $this->markTestSkipped('Redis 不可用');
        }
        $request = $this->makeRequest($method, $path);
        $request->adminId = self::ADMIN_ID;
        $this->trackKey(self::CACHE_KEY);
        Redis::setex(self::CACHE_KEY, 60, json_encode($permissions));
        return (new AdminPermission())->process($request, fn () => json(['code' => 0]));
    }

    /** 业务码在 body['code']（admin json() 辅助函数 HTTP 状态恒为 200） */
    private function code(Response $response): int
    {
        return (int) (json_decode($response->rawBody(), true)['code'] ?? -1);
    }

    // ── 通配匹配 ──

    #[Test]
    public function wildcard_permission_grants_dynamic_id_route(): void
    {
        $resp = $this->runMiddleware('GET', '/admin/user/123', ['get.admin/user/{id}']);
        $this->assertSame(0, $this->code($resp));
    }

    #[Test]
    public function multi_segment_wildcard_route_matches(): void
    {
        $resp = $this->runMiddleware('POST', '/admin/user/123/toggle-status', ['post.admin/user/{id}/toggle-status']);
        $this->assertSame(0, $this->code($resp));
    }

    #[Test]
    public function denies_request_without_matching_permission(): void
    {
        $resp = $this->runMiddleware('GET', '/admin/user/123', ['get.admin/role']);
        $this->assertSame(403, $this->code($resp));
        $this->assertSame('无权限访问', json_decode($resp->rawBody(), true)['message']);
    }

    #[Test]
    public function exact_slug_in_permissions_still_passes(): void
    {
        // 权限表可能存具体 ID 形式的旧数据，精确匹配兜底仍应放行
        $resp = $this->runMiddleware('GET', '/admin/user/123', ['get.admin/user/123']);
        $this->assertSame(0, $this->code($resp));
    }

    #[Test]
    public function super_admin_wildcard_passes(): void
    {
        $resp = $this->runMiddleware('GET', '/admin/user/123', ['*']);
        $this->assertSame(0, $this->code($resp));
    }

    #[Test]
    public function request_without_admin_id_bypasses_permission_check(): void
    {
        $request = $this->makeRequest('GET', '/admin/user/123');
        $resp = (new AdminPermission())->process($request, fn () => json(['code' => 0]));
        $this->assertSame(0, $this->code($resp));
    }

    // ── 种子覆盖验证 ──

    #[Test]
    public function seed_covers_all_admin_route_slugs(): void
    {
        // 1. 从 route.php 提取 /admin 组路由 slug（含 resource 展开为 4 个方法）
        $routes = file_get_contents(__DIR__ . '/../config/route.php');
        // 只取 /admin 组块（组外 health/metrics/docs/auth/captcha 路由不经过权限校验）
        $groupStart = strpos($routes, "Route::group('/admin'");
        $groupEnd = strpos($routes, '公开接口', $groupStart);
        $adminBlock = $groupStart === false ? '' : substr($routes, $groupStart, $groupEnd - $groupStart);
        preg_match_all("/Route::(get|post|put|delete|resource)\(['\"]([^'\"]+)/", $adminBlock, $m, PREG_SET_ORDER);
        $routeSlugs = [];
        foreach ($m as $r) {
            $path = trim($r[2], '/');
            if ($r[1] === 'resource') {
                $routeSlugs[] = "get.admin/$path";
                $routeSlugs[] = "post.admin/$path";
                $routeSlugs[] = "put.admin/$path/{id}";
                $routeSlugs[] = "delete.admin/$path/{id}";
            } else {
                $routeSlugs[] = "{$r[1]}.admin/$path";
            }
        }

        // 2. 各份权限种子文件（文件名含 permission）中的权限 slug 全集
        //    用 glob 自动发现，新增迁移文件无需再改本测试
        $seeded = [];
        foreach (glob(__DIR__ . '/../database/migrations/*permission*.sql') as $file) {
            $sql = file_get_contents($file);
            preg_match_all("/'([a-z]+\.[a-z0-9\/{}_-]+)'/", $sql, $sm);
            foreach ($sm[1] as $slug) {
                $seeded[$slug] = true;
            }
        }

        // 3. 仅比对 /admin 组（health/metrics/security.txt/docs/auth/captcha 无需权限）
        $missing = [];
        foreach ($routeSlugs as $slug) {
            if (strpos($slug, '.admin/') === false) {
                continue;
            }
            if (!isset($seeded[$slug])) {
                $missing[] = $slug;
            }
        }

        $this->assertSame([], array_values(array_unique($missing)), '以下 /admin 组路由缺少种子权限');
    }
}
