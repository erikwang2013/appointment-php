<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class Cors implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        $origin = $this->resolveAllowedOrigin($request);

        if ($request->method() === 'OPTIONS') {
            return response('', 204, [
                'Access-Control-Allow-Origin'      => $origin,
                'Access-Control-Allow-Methods'     => 'GET,POST,PUT,DELETE,OPTIONS',
                'Access-Control-Allow-Headers'     => 'Authorization,Content-Type,API-Version',
                'Access-Control-Max-Age'           => '86400',
            ]);
        }

        $response = $handler($request);
        $response = $response->withHeaders([
            'Access-Control-Allow-Origin'   => $origin,
            'X-Content-Type-Options'        => 'nosniff',
            'X-Frame-Options'               => 'DENY',
            'X-XSS-Protection'              => '1; mode=block',
            'Referrer-Policy'               => 'strict-origin-when-cross-origin',
            'Permissions-Policy'            => 'camera=(), microphone=(), geolocation=()',
            'Content-Security-Policy'       => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; connect-src 'self' http: https:;",
            'X-Permitted-Cross-Domain-Policies' => 'none',
        ]);
        return $response;
    }

    /**
     * M5: 计算允许回显的 Origin
     * - CORS_ALLOW_ORIGIN 配置为 * 时回显 *；
     * - 配置为逗号分隔白名单时，仅当请求 Origin 在白名单内回显；
     * - 未配置时默认仅允许同源：请求 Origin 的 host:port 与请求 Host 一致才回显。
     */
    private function resolveAllowedOrigin(Request $request): string
    {
        $configured = trim((string) getenv('CORS_ALLOW_ORIGIN'));
        $origin = (string) $request->header('origin', '');

        if ($configured === '*') {
            return '*';
        }

        if ($configured !== '') {
            $allowList = array_map('trim', explode(',', $configured));
            return in_array($origin, $allowList, true) ? $origin : '';
        }

        // 未配置：默认仅允许同源
        if ($origin === '') {
            return '';
        }
        $originHost = parse_url($origin, PHP_URL_HOST);
        if ($originHost === null || $originHost === '') {
            return '';
        }
        $originPort = parse_url($origin, PHP_URL_PORT);
        $originAuthority = $originPort === null ? $originHost : $originHost . ':' . $originPort;
        return $originAuthority === $request->host() ? $origin : '';
    }
}
