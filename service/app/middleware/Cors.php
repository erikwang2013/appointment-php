<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

/**
 * CORS 跨域中间件
 * 处理 OPTIONS 预检请求，设置 CORS 响应头，允许跨域访问
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

        // 设置 CORS 响应头
        $response->withHeaders([
            // 允许的来源，生产环境应限制为具体域名
            'Access-Control-Allow-Origin' => '*',
            // 允许的请求方法
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
            // 允许的请求头
            'Access-Control-Allow-Headers' => 'Authorization, Content-Type, X-Requested-With, API-Version, Accept, Origin, X-CSRF-Token',
            // 预检请求缓存时间（秒）
            'Access-Control-Max-Age' => '86400',
            // 允许携带凭证（cookie）
        ]);

        return $response;
    }
}
