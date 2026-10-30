<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\middleware;

use app\model\AdminUser;
use support\Request;
use support\Response;
use support\Redis;
use Erikwang2013\Jwt\JWT;
use Erikwang2013\Jwt\JWTFactory;
use Erikwang2013\Jwt\JWTException;

class AdminAuth
{
    private static ?JWT $jwt = null;

    private static function getJWT(): JWT
    {
        if (self::$jwt === null) {
            $config = config('plugin.erikwang2013.jwt.jwt', []);
            self::$jwt = JWTFactory::createFromConfig($config, null, ['redis' => fn() => \support\Redis::connection()]);
        }
        return self::$jwt;
    }

    public function process(Request $request, callable $next): Response
    {
        $token = $request->header('Authorization', '');
        $token = str_replace('Bearer ', '', $token);

        if (empty($token)) {
            return json(['code' => 401, 'message' => '未登录', 'data' => []])->withStatus(401);
        }

        // 检查 JWT 黑名单
        $blacklistKey = 'jwt_blacklist:' . md5($token);
        try {
            if (Redis::get($blacklistKey)) {
                return json(['code' => 401, 'message' => 'Token已失效，请重新登录', 'data' => []])->withStatus(401);
            }
        } catch (\Throwable $e) {
            // Redis down, skip blacklist check
        }

        try {
            $payload = self::getJWT()->decode($token);
        } catch (JWTException | \Exception $e) {
            return json(['code' => 401, 'message' => 'Token已过期或无效', 'data' => []])->withStatus(401);
        }

        // refresh token 仅用于换发 access token，不能直接访问业务接口
        if (($payload['token_type'] ?? '') === 'refresh') {
            return json(['code' => 401, 'message' => 'Token已过期或无效', 'data' => []])->withStatus(401);
        }

        $adminId = (int) ($payload['sub'] ?? 0);
        if ($adminId <= 0) {
            return json(['code' => 401, 'message' => 'Token已过期或无效', 'data' => []])->withStatus(401);
        }

        // 校验管理员仍存在且启用（管理端流量低，每请求一次主键查询可接受）
        $admin = AdminUser::find($adminId);
        if (!$admin || (int) $admin->status !== 1) {
            return json(['code' => 401, 'message' => '账号不存在或已被禁用', 'data' => []])->withStatus(401);
        }

        $request->adminId = $adminId;
        $request->adminUsername = $payload['username'] ?? $admin->username;

        return $next($request);
    }
}
