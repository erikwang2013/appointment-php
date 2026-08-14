<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\api\v1\controller\AuthController;
use support\Request;
use support\Response;

/**
 * AuthController 注册接口系统初始化保护测试（第 4 轮审计 S1）
 *
 * 背景：/api/auth/register 此前无鉴权即可创建 status=1 管理员并签发 token。
 * 修复后注册接口仅在系统未初始化时可用（ADMIN_REGISTER_ENABLED=1 显式开启，
 * 安装向导阶段使用），否则返回 410。
 *
 * 策略：isSystemInstalled() 优先读取 ADMIN_REGISTER_ENABLED env（'1' 才放行），
 * 测试通过 putenv 控制该开关即可确定性断言 410/422 分支，不依赖真实 DB/Redis。
 */
class AuthControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        // 还原环境变量，避免影响同进程内其他测试
        putenv('ADMIN_REGISTER_ENABLED');
    }

    private function makeRequest(array $post = []): Request
    {
        $body = http_build_query($post);
        $head = "Host: localhost\r\n"
            . "Content-Type: application/x-www-form-urlencoded\r\n"
            . "Content-Length: " . strlen($body) . "\r\n";
        return new Request("POST /api/auth/register HTTP/1.1\r\n" . $head . "\r\n" . $body);
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    #[Test]
    public function register_denied_when_system_initialized(): void
    {
        // ADMIN_REGISTER_ENABLED 非 '1'（默认关闭）→ 视为已初始化 → 410
        putenv('ADMIN_REGISTER_ENABLED=0');

        $resp = (new AuthController())->register($this->makeRequest([
            'username' => 'newadmin',
            'password' => 'secret123',
            'real_name' => '测试管理员',
        ]));

        // 注意：admin json() 辅助函数业务码在 body['code']，HTTP 状态恒为 200
        $this->assertSame(410, $this->body($resp)['code']);
        // 测试环境未加载翻译文件时 trans() 回退为原始 key，两种均可
        $this->assertContains($this->body($resp)['message'], ['系统已初始化，注册接口已关闭', 'messages.system_initialized']);
    }

    #[Test]
    public function register_not_blocked_when_explicitly_enabled(): void
    {
        // 安装向导阶段显式开启 → 不命中 410 守卫，进入参数校验
        putenv('ADMIN_REGISTER_ENABLED=1');

        $resp = (new AuthController())->register($this->makeRequest([]));

        // 空参数在参数校验层被拦截（422），证明未走 410 分支，且不触碰 DB/Redis
        $this->assertSame(422, $this->body($resp)['code']);
    }
}
