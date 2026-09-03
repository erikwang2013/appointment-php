<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * URL 版本前缀路由回归测试（硬切换：API-Version 请求头 → /api/v1 URL 前缀）
 *
 * 策略：纯源码自省 admin/config/route.php（同 BackendEnhancementTest 惯例，不做
 * Route::load——其 require_once 单进程语义会使第二个加载者拿到空路由表）。断言：
 * - /api/v1 版本化组已注册，组内含 auth/login、captcha/generate 等公开端点
 * - 无裸 /api/*（无版本前缀）路径注册——URL 不带 /api/v1 即 404
 * - 顶层非版本化基础设施端点（/health、/metrics、/api/docs）未被误伤
 */
class ApiVersionRoutingTest extends TestCase
{
    /** 路由文件中所有路由路径字面量（get/post/put/delete/patch/any/resource） */
    private function routePathLiterals(string $content): array
    {
        preg_match_all('/Route::(?:get|post|put|delete|patch|any|resource)\(\s*[\'"]([^\'"]+)/', $content, $m);
        return $m[1];
    }

    #[Test] public function versioned_api_group_registered_with_public_endpoints(): void
    {
        $content = file_get_contents(__DIR__ . '/../config/route.php');

        $this->assertStringContainsString("Route::group('/api/v1'", $content, '应注册 /api/v1 版本化路由组');

        $v1Pos = strpos($content, "Route::group('/api/v1'");
        $block = substr($content, $v1Pos, strpos($content, 'Route::disableDefaultRoute', $v1Pos) - $v1Pos);
        foreach (['/auth/login', '/auth/register', '/auth/refresh', '/captcha/generate', '/captcha/verify'] as $path) {
            $this->assertStringContainsString("'{$path}'", $block, "/api/v1 组内应注册 {$path}");
        }
    }

    #[Test] public function no_unversioned_api_path_registered(): void
    {
        $content = file_get_contents(__DIR__ . '/../config/route.php');

        // 曾裸注册的入口路径在源码中若以 '/api/auth 连写出现即残留（组前缀与子路径在源码中分离）
        $this->assertStringNotContainsString("'/api/auth", $content, '裸 /api/auth 路径不应存在');
        $this->assertStringNotContainsString("'/api/captcha", $content, '裸 /api/captcha 路径不应存在');

        // 通用规则：/api 开头的路径字面量必须以 /api/v 版本前缀开头，仅顶层 /api/docs 例外
        $violations = array_values(array_filter(
            $this->routePathLiterals($content),
            fn (string $p) => str_starts_with($p, '/api') && !str_starts_with($p, '/api/v') && $p !== '/api/docs'
        ));
        $this->assertSame([], $violations, '发现无版本前缀的 /api 路由注册（URL 版本硬切换后应 404）');
    }

    #[Test] public function top_level_infra_endpoints_not_collateral_damaged(): void
    {
        $content = file_get_contents(__DIR__ . '/../config/route.php');

        foreach (['/health', '/metrics', '/api/docs'] as $path) {
            $this->assertStringContainsString("'{$path}'", $content, "顶层端点 {$path} 应保持注册");
        }

        // 注册总数烟测：源码字面量基线 159（2026-09-04 实测，resource 运行时还会展开）；下限 100 防整组误删
        $total = count($this->routePathLiterals($content));
        $this->assertGreaterThanOrEqual(100, $total, "路由注册字面量总数异常：{$total}");
    }
}
