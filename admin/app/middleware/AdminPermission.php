<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\middleware;

use app\model\AdminUser;
use support\Redis;
use support\Request;
use support\Response;
use Webman\Route;

class AdminPermission
{
    private const CACHE_TTL = 60; // 权限缓存 60 秒

    public function process(Request $request, callable $next): Response
    {
        $adminId = $request->adminId ?? 0;
        if (!$adminId) {
            return $next($request);
        }

        $path = $request->path();
        $method = $request->method();

        $permissions = $this->getUserPermissions($adminId);

        if (in_array('*', $permissions, true)) {
            return $next($request);
        }

        // 动态路由归一化：/admin/user/123 → get.admin/user/{id}
        // 与权限种子（含 {id} 占位）匹配，避免具体 ID 永远无法命中权限条目
        $exactSlug = strtolower($method) . '.' . trim($path, '/');
        $requiredPermission = $this->resolvePermission($method, trim($path, '/'));

        if (!in_array($requiredPermission, $permissions, true) && !in_array($exactSlug, $permissions, true)) {
            return json(['code' => 403, 'message' => '无权限访问', 'data' => []]);
        }

        return $next($request);
    }

    /**
     * 将请求路径解析为权限 slug：
     * 1. 静态路由直接取 method.path；
     * 2. 动态路由（如 /admin/user/{id}）将具体 ID 段替换为路由占位符后返回，
     *    使权限条目可用 {id} 通配覆盖任意具体 ID。
     */
    private function resolvePermission(string $method, string $path): string
    {
        $exact = strtolower($method) . '.' . $path;
        foreach ($this->routeSlugs() as $slug) {
            if ($slug === $exact) {
                return $exact;
            }
        }
        foreach ($this->routeSlugs() as $slug) {
            $patternPath = substr($slug, strpos($slug, '.') + 1);
            if ($this->pathMatchesPattern($path, $patternPath)) {
                return $slug;
            }
        }
        return $exact;
    }

    /**
     * 已注册路由的 method.path 集合（含 {id} 等占位符），进程内缓存
     */
    private function routeSlugs(): array
    {
        static $slugs = null;
        if ($slugs !== null) {
            return $slugs;
        }
        $slugs = [];
        try {
            foreach (Route::getRoutes() as $route) {
                $pattern = trim((string) $route->getPath(), '/');
                if ($pattern === '') {
                    continue;
                }
                foreach ($route->getMethods() as $routeMethod) {
                    $slugs[] = strtolower($routeMethod) . '.' . $pattern;
                }
            }
        } catch (\Throwable $e) {
            // 路由表不可用（如部分测试环境）时退回精确匹配
        }
        $slugs = array_values(array_unique($slugs));
        return $slugs;
    }

    /**
     * 路径与路由模式匹配：{xxx} 段匹配任意单个段，其余段精确相等
     */
    private function pathMatchesPattern(string $path, string $pattern): bool
    {
        $parts = explode('/', $path);
        $pats = explode('/', $pattern);
        if (count($parts) !== count($pats)) {
            return false;
        }
        foreach ($pats as $i => $p) {
            if (strlen($p) > 2 && $p[0] === '{' && substr($p, -1) === '}') {
                continue;
            }
            if (($parts[$i] ?? null) !== $p) {
                return false;
            }
        }
        return true;
    }

    private function getUserPermissions(int $adminId): array
    {
        // Redis 缓存，避免每请求 N+1 查询
        $cacheKey = "perm:{$adminId}";
        try {
            $cached = Redis::get($cacheKey);
            if ($cached) {
                return json_decode($cached, true);
            }
        } catch (\Throwable) {}

        $user = AdminUser::find($adminId);
        if (!$user) return [];

        $permissions = [];
        foreach ($user->roles as $role) {
            if ($role->status === 0) continue;
            foreach ($role->permissions as $perm) {
                $permissions[] = $perm->slug;
            }
        }
        $permissions = array_unique($permissions);

        try {
            Redis::setex($cacheKey, self::CACHE_TTL, json_encode($permissions));
        } catch (\Throwable) {}

        return $permissions;
    }
}
