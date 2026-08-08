<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\middleware;

use app\model\User;
use ErikJwt\JWTFactory;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

/**
 * JWT 认证中间件
 * 从 Authorization 头提取 Bearer token，解码 JWT 并设置 $request->user_id
 * 验证失败返回 401 未授权响应
 */
class Auth implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        $token = $this->extractToken($request);

        if ($token === null) {
            return $this->unauthorized('缺少认证令牌');
        }

        try {
            $jwt = JWTFactory::createFromConfig(config('plugin.erikwang2013.jwt.jwt'));
            $payload = $jwt->decode($token);
        } catch (\Exception $e) {
            return $this->unauthorized('认证令牌无效或已过期');
        }

        if (empty($payload['user_id'])) {
            return $this->unauthorized('认证令牌格式错误');
        }

        // 验证用户是否存在且未被禁用或删除
        $user = User::find($payload['user_id']);
        if (!$user || $user->status !== 1) {
            return $this->unauthorized('账号不存在或已被禁用');
        }

        $request->user_id = $payload['user_id'];
        $request->jwt_payload = $payload;

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function unauthorized(string $message): Response
    {
        return json([
            'code' => 401,
            'message' => $message,
            'data' => null,
        ])->withStatus(401);
    }
}
