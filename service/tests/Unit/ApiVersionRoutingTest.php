<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webman\Route;

/**
 * URL 版本前缀路由回归测试（硬切换：API-Version 请求头 → /api/v1 URL 前缀）
 *
 * 通过真实路由加载 + 派发（同 admin StatsControllerTest 的 Route::load 惯例）断言：
 * - 带 /api/v1/ 前缀的公开端点可达（auth/login、guest/services 命中路由）
 * - 无版本前缀的裸 /api/* 路径不命中任何路由（disableDefaultRoute → HTTP 404）
 * - 顶层非版本化端点（/health、/payment/wechat-notify、/api/docs）未被误伤
 *
 * 注：Route::load 依赖 require_once，进程内只生效一次——本类是全仓库唯一加载点，
 * 用 static 标记保证进程内首个用例注册路由、后续用例复用同一路由表。
 */
class ApiVersionRoutingTest extends TestCase
{
    private static bool $routesLoaded = false;

    protected function setUp(): void
    {
        if (!self::$routesLoaded) {
            Route::load([config_path()]);
            self::$routesLoaded = true;
        }
    }

    #[Test] public function versioned_public_endpoints_are_reachable(): void
    {
        $login = Route::dispatch('POST', '/api/v1/auth/login');
        $this->assertSame(1, $login[0], 'POST /api/v1/auth/login 应命中 /api/v1 组路由');

        $guest = Route::dispatch('GET', '/api/v1/guest/services');
        $this->assertSame(1, $guest[0], 'GET /api/v1/guest/services 应命中 /api/v1 组路由');
    }

    #[Test] public function bare_unversioned_paths_are_not_found(): void
    {
        $login = Route::dispatch('POST', '/api/auth/login');
        $this->assertSame(0, $login[0], '裸 /api/auth/login 不应命中任何路由（URL 不带版本前缀 → 404）');

        $guest = Route::dispatch('GET', '/api/guest/services');
        $this->assertSame(0, $guest[0], '裸 /api/guest/services 不应命中任何路由');
    }

    #[Test] public function non_versioned_infra_endpoints_still_reachable(): void
    {
        $this->assertSame(1, Route::dispatch('GET', '/health')[0], '/health 不应被误伤');
        $this->assertSame(1, Route::dispatch('POST', '/payment/wechat-notify')[0], '/payment/wechat-notify 不应被误伤');
        $this->assertSame(1, Route::dispatch('GET', '/api/docs')[0], '/api/docs 不应被误伤');
    }

    #[Test] public function route_table_registration_count_smoke(): void
    {
        // 基线 220 条注册（2026-09-04 实测）；下限 150 防整组路由误删，精确数记录在 message
        $total = count(Route::getRoutes());
        $this->assertGreaterThanOrEqual(150, $total, "路由注册总数异常：{$total}");
    }
}
