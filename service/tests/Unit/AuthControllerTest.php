<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\api\v1\controller\AuthController;
use app\model\User;
use Webman\Http\Request;
use Webman\Http\Response;
use support\Redis;

/**
 * AuthController 单元测试
 *
 * 策略：
 *   - 参数校验分支（不依赖 Redis/DB）：通过构造原生 HTTP buffer 的 Request 直接调用
 *   - 验证码校验 / 登录失败锁定限流（依赖 Redis）：Redis 可用时执行，不可用时 skip
 *   - 覆盖边界：完整注册/登录成功路径涉及 DB 写入 + JWT 签发，未在本套件覆盖
 *     （由集成/接口测试承担）
 */
class AuthControllerTest extends TestCase
{
    /** @var string[] 测试写入的 Redis key，tearDown 统一清理 */
    private array $redisKeys = [];

    protected function tearDown(): void
    {
        foreach ($this->redisKeys as $key) {
            try {
                Redis::del($key);
            } catch (\Throwable) {
                // Redis 不可用时静默
            }
        }
        $this->redisKeys = [];
    }

    // ── 工具方法 ──

    private function makeRequest(array $post = [], array $headers = []): Request
    {
        $body = http_build_query($post);
        $head = "Host: localhost\r\n"
            . "Content-Type: application/x-www-form-urlencoded\r\n"
            . "Content-Length: " . strlen($body) . "\r\n";
        foreach ($headers as $k => $v) {
            $head .= $k . ": " . $v . "\r\n";
        }
        return new Request("POST / HTTP/1.1\r\n" . $head . "\r\n" . $body);
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    private function controller(): AuthController
    {
        return new AuthController();
    }

    private static function invokePrivate(AuthController $ctl, string $method, array $args = []): mixed
    {
        $m = new \ReflectionMethod(AuthController::class, $method);
        $m->setAccessible(true);
        return $m->invokeArgs($ctl, $args);
    }

    private function redisAvailable(): bool
    {
        try {
            $probe = 'test:probe:' . uniqid();
            $this->redisKeys[] = $probe;
            Redis::setex($probe, 5, '1');
            return Redis::get($probe) === '1';
        } catch (\Throwable) {
            return false;
        }
    }

    /** 记录待清理的 Redis key（同一 key 幂等） */
    private function trackKey(string $key): void
    {
        if (!in_array($key, $this->redisKeys, true)) {
            $this->redisKeys[] = $key;
        }
    }

    // ── register：参数校验分支（不依赖 Redis/DB） ──

    #[Test] public function register_requires_complete_fields(): void
    {
        $resp = $this->controller()->register($this->makeRequest([]));
        $this->assertSame(400, $resp->getStatusCode());
        $this->assertSame('请填写完整信息', $this->body($resp)['message']);
    }

    #[Test] public function register_rejects_password_mismatch(): void
    {
        $resp = $this->controller()->register($this->makeRequest([
            'phone' => '13800000001', 'code' => '123456',
            'password' => 'abc123', 'confirm_password' => 'abc124',
        ]));
        $this->assertSame('两次输入的密码不一致', $this->body($resp)['message']);
    }

    #[Test] public function register_rejects_short_password(): void
    {
        $resp = $this->controller()->register($this->makeRequest([
            'phone' => '13800000001', 'code' => '123456',
            'password' => '12345', 'confirm_password' => '12345',
        ]));
        $this->assertSame('密码长度不能少于6位', $this->body($resp)['message']);
    }

    // ── register：验证码校验逻辑（依赖 Redis） ──

    #[Test] public function register_rejects_wrong_sms_code(): void
    {
        if (!$this->redisAvailable()) {
            $this->markTestSkipped('Redis 不可用');
        }
        $phone = '13800000002';
        $this->trackKey("sms_code:{$phone}");
        Redis::setex("sms_code:{$phone}", 300, '123456');

        $resp = $this->controller()->register($this->makeRequest([
            'phone' => $phone, 'code' => '999999',
            'password' => 'abc123', 'confirm_password' => 'abc123',
        ]));
        $this->assertSame('验证码错误或已过期', $this->body($resp)['message']);
    }

    #[Test] public function register_rejects_missing_sms_code(): void
    {
        if (!$this->redisAvailable()) {
            $this->markTestSkipped('Redis 不可用');
        }
        $phone = '13800000003';
        $resp = $this->controller()->register($this->makeRequest([
            'phone' => $phone, 'code' => '123456',
            'password' => 'abc123', 'confirm_password' => 'abc123',
        ]));
        $this->assertSame('验证码错误或已过期', $this->body($resp)['message']);
    }

    #[Test] public function register_rejects_existing_phone(): void
    {
        if (!$this->redisAvailable()) {
            $this->markTestSkipped('Redis 不可用');
        }
        $phone = (string) User::query()->value('phone');
        if (empty($phone)) {
            $this->markTestSkipped('无现有用户可验证已注册分支');
        }
        $this->trackKey("sms_code:{$phone}");
        Redis::setex("sms_code:{$phone}", 300, '123456');

        $resp = $this->controller()->register($this->makeRequest([
            'phone' => $phone, 'code' => '123456',
            'password' => 'abc123', 'confirm_password' => 'abc123',
        ]));
        $this->assertSame('该手机号已注册', $this->body($resp)['message']);
    }

    // ── login：参数校验 / 锁定 / 失败限流 ──

    #[Test] public function login_requires_phone_and_password(): void
    {
        $resp = $this->controller()->login($this->makeRequest([]));
        $this->assertSame(400, $resp->getStatusCode());
        $this->assertSame('请输入手机号和密码', $this->body($resp)['message']);
    }

    #[Test] public function login_returns_429_when_account_locked(): void
    {
        if (!$this->redisAvailable()) {
            $this->markTestSkipped('Redis 不可用');
        }
        $phone = '13800000010';
        $this->trackKey("account_lock:{$phone}");
        Redis::setex("account_lock:{$phone}", 900, '1');

        $resp = $this->controller()->login($this->makeRequest([
            'phone' => $phone, 'password' => 'whatever',
        ]));
        $this->assertSame(429, $resp->getStatusCode());
        $this->assertSame('账号已被临时锁定，请15分钟后再试', $this->body($resp)['message']);
    }

    #[Test] public function login_failure_increments_redis_fail_counter(): void
    {
        if (!$this->redisAvailable()) {
            $this->markTestSkipped('Redis 不可用');
        }
        // 使用不存在的手机号，避免触碰真实用户数据
        $phone = '19900001001';
        $this->trackKey("login_fail:{$phone}");

        $resp = $this->controller()->login($this->makeRequest([
            'phone' => $phone, 'password' => 'wrong-pass',
        ]));
        $this->assertSame('手机号或密码错误', $this->body($resp)['message']);
        $this->assertSame(1, (int) Redis::get("login_fail:{$phone}"));
    }

    #[Test] public function login_locks_account_after_five_failures(): void
    {
        if (!$this->redisAvailable()) {
            $this->markTestSkipped('Redis 不可用');
        }
        $phone = '19900001002';
        $this->trackKey("login_fail:{$phone}");
        $this->trackKey("account_lock:{$phone}");

        $last = null;
        for ($i = 1; $i <= 5; $i++) {
            $last = $this->controller()->login($this->makeRequest([
                'phone' => $phone, 'password' => 'wrong-pass',
            ]));
        }
        // 第 5 次失败触发锁定
        $this->assertSame(429, $last->getStatusCode());
        $this->assertSame('账号已被临时锁定，请15分钟后再试', $this->body($last)['message']);
        $this->assertSame('1', Redis::get("account_lock:{$phone}"));
        // 失败计数已删除，锁定 key 已置位
        $this->assertTrue(empty(Redis::get("login_fail:{$phone}")));
    }

    // ── loginByCode：参数校验 / 验证码失败限流 ──

    #[Test] public function login_by_code_requires_fields(): void
    {
        $resp = $this->controller()->loginByCode($this->makeRequest([]));
        $this->assertSame('请输入手机号和验证码', $this->body($resp)['message']);
    }

    #[Test] public function login_by_code_wrong_code_increments_fail_counter(): void
    {
        if (!$this->redisAvailable()) {
            $this->markTestSkipped('Redis 不可用');
        }
        $phone = '19900001003';
        $this->trackKey("login_fail:{$phone}");
        $this->trackKey("sms_code:{$phone}");
        Redis::setex("sms_code:{$phone}", 300, '123456');

        $resp = $this->controller()->loginByCode($this->makeRequest([
            'phone' => $phone, 'code' => '000000',
        ]));
        $this->assertSame('验证码错误或已过期', $this->body($resp)['message']);
        $this->assertSame(1, (int) Redis::get("login_fail:{$phone}"));
    }

    #[Test] public function login_by_code_locks_after_five_failures(): void
    {
        if (!$this->redisAvailable()) {
            $this->markTestSkipped('Redis 不可用');
        }
        $phone = '19900001004';
        $this->trackKey("login_fail:{$phone}");
        $this->trackKey("account_lock:{$phone}");

        $last = null;
        for ($i = 1; $i <= 5; $i++) {
            $last = $this->controller()->loginByCode($this->makeRequest([
                'phone' => $phone, 'code' => '000000',
            ]));
        }
        $this->assertSame(429, $last->getStatusCode());
        $this->assertSame('账号已被临时锁定，请15分钟后再试', $this->body($last)['message']);
        $this->assertSame('1', Redis::get("account_lock:{$phone}"));
    }

    #[Test] public function login_by_code_returns_429_when_locked(): void
    {
        if (!$this->redisAvailable()) {
            $this->markTestSkipped('Redis 不可用');
        }
        $phone = '13800000011';
        $this->trackKey("account_lock:{$phone}");
        Redis::setex("account_lock:{$phone}", 900, '1');

        $resp = $this->controller()->loginByCode($this->makeRequest([
            'phone' => $phone, 'code' => '123456',
        ]));
        $this->assertSame(429, $resp->getStatusCode());
        $this->assertSame('账号已被临时锁定，请15分钟后再试', $this->body($resp)['message']);
    }

    // ── forgetPassword：参数校验分支 ──

    #[Test] public function forget_password_requires_complete_fields(): void
    {
        $resp = $this->controller()->forgetPassword($this->makeRequest([]));
        $this->assertSame('请填写完整信息', $this->body($resp)['message']);
    }

    #[Test] public function forget_password_rejects_password_mismatch(): void
    {
        $resp = $this->controller()->forgetPassword($this->makeRequest([
            'phone' => '13800000001', 'code' => '123456',
            'password' => 'abc123', 'confirm_password' => 'abc124',
        ]));
        $this->assertSame('两次输入的密码不一致', $this->body($resp)['message']);
    }

    #[Test] public function forget_password_rejects_short_password(): void
    {
        $resp = $this->controller()->forgetPassword($this->makeRequest([
            'phone' => '13800000001', 'code' => '123456',
            'password' => '123', 'confirm_password' => '123',
        ]));
        $this->assertSame('密码长度不能少于6位', $this->body($resp)['message']);
    }

    // ── switchRole / refresh / logout ──

    #[Test] public function switch_role_rejects_invalid_role(): void
    {
        $resp = $this->controller()->switchRole($this->makeRequest(['role' => 'admin']));
        $this->assertSame('无效的角色类型', $this->body($resp)['message']);
    }

    #[Test] public function refresh_requires_bearer_token(): void
    {
        $resp = $this->controller()->refresh($this->makeRequest([]));
        $this->assertSame(401, $resp->getStatusCode());
        $this->assertSame('缺少认证令牌', $this->body($resp)['message']);
    }

    #[Test] public function refresh_rejects_invalid_token(): void
    {
        $resp = $this->controller()->refresh($this->makeRequest([], ['Authorization' => 'Bearer invalid.token.here']));
        $this->assertSame(401, $resp->getStatusCode());
        $this->assertSame('令牌无效或已过期', $this->body($resp)['message']);
    }

    #[Test] public function refresh_ignores_non_bearer_authorization_header(): void
    {
        $resp = $this->controller()->refresh($this->makeRequest([], ['Authorization' => 'Basic dXNlcjpwYXNz']));
        $this->assertSame(401, $resp->getStatusCode());
        $this->assertSame('缺少认证令牌', $this->body($resp)['message']);
    }

    #[Test] public function logout_returns_success(): void
    {
        $resp = $this->controller()->logout($this->makeRequest([]));
        $this->assertSame(200, $resp->getStatusCode());
        $this->assertSame('已退出登录', $this->body($resp)['message']);
    }

    #[Test] public function refresh_with_valid_token_rotates_token(): void
    {
        if (!$this->redisAvailable()) {
            $this->markTestSkipped('Redis 不可用');
        }
        // 第 4 轮审计修复：refresh 之前取 $request->user_id（公开路由组恒为 null），
        // 导致 User::find(null) 恒 401、token 轮换不可用；修复后从 token 载荷解析 user_id。
        $phone = '1990' . str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
        $userId = User::generateId();
        \support\Db::beginTransaction();
        try {
            $user = User::create([
                'id' => $userId,
                'phone' => $phone,
                'password' => '',
                'user_type' => 'customer',
                'active_role' => 'customer',
                'referral_code' => User::generateReferralCode(),
                'status' => 1,
            ]);

            $token = $user->generateToken();
            $resp = $this->controller()->refresh($this->makeRequest([], ['Authorization' => 'Bearer ' . $token]));
            $body = $this->body($resp);

            $this->assertSame(200, $resp->getStatusCode());
            $this->assertNotEmpty($body['data']['token'] ?? '');
            $this->assertNotSame($token, $body['data']['token'] ?? '');
        } finally {
            \support\Db::rollBack();
        }
    }

    // ── maskPhone：手机号脱敏纯逻辑 ──

    #[Test] public function mask_phone_masks_middle_digits(): void
    {
        $ctl = $this->controller();
        $this->assertSame('138****5678', self::invokePrivate($ctl, 'maskPhone', ['13812345678']));
    }

    #[Test] public function mask_phone_returns_short_number_unchanged(): void
    {
        $ctl = $this->controller();
        $this->assertSame('12345', self::invokePrivate($ctl, 'maskPhone', ['12345']));
    }

    #[Test] public function mask_phone_handles_shortest_maskable_number(): void
    {
        // 生产实现：>=7 位即脱敏，保留前3后4（7 位时首尾各重叠 1 位）
        $ctl = $this->controller();
        $this->assertSame('123****4567', self::invokePrivate($ctl, 'maskPhone', ['1234567']));
    }
}
