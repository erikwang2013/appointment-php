<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
use support\Redis;

/**
 * 限流中间件
 * Redis 滑动窗口（Lua 原子化），默认 60 次/分钟/IP/路由
 * 敏感端点（登录/注册）使用更严格的限制
 */
class RateLimit implements MiddlewareInterface
{
    private int $defaultLimit = 60;
    private int $defaultWindow = 60;

    private array $sensitive = [
        '/api/auth/login'         => ['limit' => 10, 'window' => 60],
        '/api/auth/login-by-code' => ['limit' => 10, 'window' => 60],
        '/api/auth/register'      => ['limit' => 5,  'window' => 60],
        '/api/auth/forget-password' => ['limit' => 5, 'window' => 60],
        '/api/captcha/send'       => ['limit' => 5,  'window' => 60],
    ];

    public function process(Request $request, callable $handler): Response
    {
        $path = $request->path();
        $ip   = $request->getRealIp();

        $limit  = $this->defaultLimit;
        $window = $this->defaultWindow;

        foreach ($this->sensitive as $pattern => $cfg) {
            if ($path === $pattern || str_starts_with($path, rtrim($pattern, '/') . '/')) {
                $limit  = $cfg['limit'];
                $window = $cfg['window'];
                break;
            }
        }

        $safePath = preg_replace('/[^a-zA-Z0-9_-]/', '_', $path);
        $key = "rate_limit:{$ip}:{$safePath}";
        $now = (int) (microtime(true) * 1000);
        $windowStart = $now - $window * 1000;

        $lua = <<<'LUA'
redis.call('ZREMRANGEBYSCORE', KEYS[1], 0, ARGV[1])
local count = redis.call('ZCARD', KEYS[1])
if count >= tonumber(ARGV[2]) then
    return {0, count}
end
redis.call('ZADD', KEYS[1], ARGV[3], ARGV[4])
redis.call('EXPIRE', KEYS[1], ARGV[5])
return {1, count + 1}
LUA;
        try {
            $result = Redis::eval($lua, 1, $key, $windowStart, $limit, $now, $now . '.' . mt_rand(), $window + 10);
        } catch (\Throwable $e) {
            return $handler($request);
        }

        $count     = (int) ($result[1] ?? 0);
        $remaining = max($limit - $count, 0);
        $reset     = time() + $window;

        if (empty($result[0])) {
            return json([
                'code'    => 429,
                'message' => '请求过于频繁，请稍后再试',
                'data'    => null,
            ])->withStatus(429)->withHeaders([
                'X-RateLimit-Limit'     => (string) $limit,
                'X-RateLimit-Remaining' => '0',
                'X-RateLimit-Reset'     => (string) $reset,
                'Retry-After'           => (string) $window,
            ]);
        }

        $response = $handler($request);
        return $response->withHeaders([
            'X-RateLimit-Limit'     => (string) $limit,
            'X-RateLimit-Remaining' => (string) $remaining,
            'X-RateLimit-Reset'     => (string) $reset,
        ]);
    }
}
