<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\middleware;

use Erikwang2013\Jwt\JWT;
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
    /**
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function process(Request $request, callable $next): Response
    {
        // 从 Authorization 头提取 token
        $token = $this->extractToken($request);

        if ($token === null) {
            return $this->unauthorized('缺少认证令牌');
        }

        try {
            // 使用 erikwang2013/jwt-webman 解码 JWT
            $payload = JWT::decode($token);
        } catch (\Exception $e) {
            return $this->unauthorized('认证令牌无效或已过期');
        }

        // 校验 payload 中必须包含 user_id
        if (empty($payload['user_id'])) {
            return $this->unauthorized('认证令牌格式错误');
        }

        // 将用户信息注入请求对象，供后续控制器使用
        $request->user_id = $payload['user_id'];
        $request->jwt_payload = $payload;

        return $next($request);
    }

    /**
     * 从请求头提取 Bearer token
     * @param Request $request
     * @return string|null
     */
    private function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');

        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * 返回 401 未授权响应
     * @param string $message
     * @return Response
     */
    private function unauthorized(string $message): Response
    {
        return json([
            'code' => 401,
            'message' => $message,
            'data' => null,
        ])->withStatus(401);
    }
}
