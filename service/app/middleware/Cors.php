<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

/**
 * CORS 跨域中间件
 * 处理 OPTIONS 预检请求，设置 CORS + 安全响应头
 */
class Cors implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        // 处理 OPTIONS 预检请求，直接返回 204
        if ($request->method() === 'OPTIONS') {
            $response = response('', 204);
        } else {
            $response = $next($request);
        }

        // 设置 CORS 响应头，来源可通过 CORS_ALLOW_ORIGIN 环境变量配置
        $response->withHeaders([
            'Access-Control-Allow-Origin' => getenv('CORS_ALLOW_ORIGIN') ?: '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
            'Access-Control-Allow-Headers' => 'Authorization, Content-Type, X-Requested-With, API-Version, Accept, Origin, X-CSRF-Token',
            'Access-Control-Max-Age' => '86400',
        ]);

        // 安全响应头
        $response->withHeaders([
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; connect-src 'self' http: https:;",
            'X-Permitted-Cross-Domain-Policies' => 'none',
        ]);

        return $response;
    }
}
