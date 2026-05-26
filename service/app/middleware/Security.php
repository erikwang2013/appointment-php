<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

/**
 * 安全中间件 - 占位实现
 * erikwang2013/security-php 包在全局中间件链中处理攻击检测（XSS/SQL注入/CSRF等31种检测）
 * 此中间件作为应用层安全兜底占位，直接放行请求
 */
class Security implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        // 实际安全检测由 \Erikwang2013\Security\Middleware\Webman\SecurityMiddleware 处理
        // 此中间件保留为占位，用于需要自定义安全逻辑的场景
        return $next($request);
    }
}
