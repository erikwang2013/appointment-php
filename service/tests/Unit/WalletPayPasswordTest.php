<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\model\UserWallet;
use app\wallet\v1\controller\WalletController;
use support\Model;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 钱包支付密码测试（R23 第 3 项）
 *
 * 覆盖：首次设置成功（password_hash 存储）、重复设置需旧密码（错误 422、
 * 正确可修改）、verify 正确/错误、check 未设置状态、6 位数字校验。
 * 基建与 WalletTest 一致：真实 DB + tearDown 清理。
 */
class WalletPayPasswordTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理钱包 */
    private array $userIds = [];

    protected function tearDown(): void
    {
        foreach ($this->userIds as $uid) {
            UserWallet::where('user_id', $uid)->delete();
        }
        $this->userIds = [];
    }

    private function makeRequest(array $post = []): Request
    {
        $body = http_build_query($post);
        $head = "Host: localhost\r\n"
            . "Content-Type: application/x-www-form-urlencoded\r\n"
            . "Content-Length: " . strlen($body) . "\r\n";
        return new Request("POST / HTTP/1.1\r\n" . $head . "\r\n" . $body);
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    private function newUserId(): string
    {
        $uid = Model::generateId();
        $this->userIds[] = $uid;
        return $uid;
    }

    private function authRequest(string $userId, array $post = []): Request
    {
        $request = $this->makeRequest($post);
        $request->user_id = $userId;
        return $request;
    }

    private function wallet(string $userId): ?UserWallet
    {
        return UserWallet::where('user_id', $userId)->first();
    }

    // ── 首次设置 ──

    #[Test] public function set_first_time_stores_hashed_password(): void
    {
        $userId = $this->newUserId();

        $resp = $this->body((new WalletController())->setPayPassword(
            $this->authRequest($userId, ['password' => '123456', 'confirm' => '123456'])
        ));

        $this->assertSame(0, $resp['code']);
        $this->assertTrue($resp['data']['set']);

        $wallet = $this->wallet($userId);
        $this->assertNotNull($wallet);
        $this->assertNotSame('123456', $wallet->pay_password, '不得明文存储');
        $this->assertTrue(password_verify('123456', (string) $wallet->pay_password));
        $this->assertNotNull($wallet->pay_password_set_at);
    }

    #[Test] public function set_rejects_non_six_digit_password(): void
    {
        $userId = $this->newUserId();
        $ctl = new WalletController();

        foreach (['12345', '1234567', '12345a', 'abcdef', '', '12 345'] as $invalid) {
            $resp = $this->body($ctl->setPayPassword(
                $this->authRequest($userId, ['password' => $invalid, 'confirm' => $invalid])
            ));
            $this->assertSame(422, $resp['code'], "password={$invalid} 应被拒绝");
            $this->assertStringContainsString('6 位数字', (string) $resp['message']);
        }
        $this->assertNull($this->wallet($userId), '校验失败不得创建/写库');

        // confirm 不一致
        $resp = $this->body($ctl->setPayPassword(
            $this->authRequest($userId, ['password' => '123456', 'confirm' => '654321'])
        ));
        $this->assertSame(422, $resp['code']);
        $this->assertStringContainsString('不一致', (string) $resp['message']);
    }

    // ── 重复设置（修改）──

    #[Test] public function set_again_requires_correct_old_password(): void
    {
        $userId = $this->newUserId();
        $ctl = new WalletController();
        $ctl->setPayPassword($this->authRequest($userId, ['password' => '123456', 'confirm' => '123456']));

        // 未传旧密码
        $resp = $this->body($ctl->setPayPassword(
            $this->authRequest($userId, ['password' => '654321', 'confirm' => '654321'])
        ));
        $this->assertSame(422, $resp['code']);
        $this->assertStringContainsString('原支付密码', (string) $resp['message']);

        // 旧密码错误
        $resp = $this->body($ctl->setPayPassword(
            $this->authRequest($userId, ['password' => '654321', 'confirm' => '654321', 'pay_password' => '000000'])
        ));
        $this->assertSame(422, $resp['code']);
        $this->assertStringContainsString('原支付密码错误', (string) $resp['message']);

        // 原密码未被篡改
        $this->assertTrue(password_verify('123456', (string) $this->wallet($userId)->pay_password));
    }

    #[Test] public function set_again_with_correct_old_password_updates_hash(): void
    {
        $userId = $this->newUserId();
        $ctl = new WalletController();
        $ctl->setPayPassword($this->authRequest($userId, ['password' => '123456', 'confirm' => '123456']));
        $firstHash = (string) $this->wallet($userId)->pay_password;

        $resp = $this->body($ctl->setPayPassword(
            $this->authRequest($userId, ['password' => '654321', 'confirm' => '654321', 'pay_password' => '123456'])
        ));

        $this->assertSame(0, $resp['code']);

        $wallet = $this->wallet($userId);
        $this->assertTrue(password_verify('654321', (string) $wallet->pay_password));
        $this->assertFalse(password_verify('123456', (string) $wallet->pay_password));
        // password_hash 每次随机盐，哈希必不同
        $this->assertNotSame($firstHash, (string) $wallet->pay_password);
    }

    // ── 验证 ──

    #[Test] public function verify_accepts_correct_and_rejects_wrong_password(): void
    {
        $userId = $this->newUserId();
        $ctl = new WalletController();
        $ctl->setPayPassword($this->authRequest($userId, ['password' => '123456', 'confirm' => '123456']));

        $ok = $this->body($ctl->verifyPayPassword($this->authRequest($userId, ['pay_password' => '123456'])));
        $this->assertSame(0, $ok['code']);
        $this->assertTrue($ok['data']['valid']);

        $bad = $this->body($ctl->verifyPayPassword($this->authRequest($userId, ['pay_password' => '000000'])));
        $this->assertSame(422, $bad['code']);
        $this->assertStringContainsString('支付密码错误', (string) $bad['message']);
    }

    #[Test] public function verify_returns_422_when_not_set(): void
    {
        $userId = $this->newUserId();

        $resp = $this->body((new WalletController())->verifyPayPassword(
            $this->authRequest($userId, ['pay_password' => '123456'])
        ));

        $this->assertSame(422, $resp['code']);
        $this->assertStringContainsString('请先设置支付密码', (string) $resp['message']);
    }

    // ── 状态查询 ──

    #[Test] public function check_reflects_set_state(): void
    {
        $userId = $this->newUserId();
        $ctl = new WalletController();

        $before = $this->body($ctl->checkPayPassword($this->authRequest($userId)));
        $this->assertSame(0, $before['code']);
        $this->assertFalse($before['data']['set']);

        $ctl->setPayPassword($this->authRequest($userId, ['password' => '123456', 'confirm' => '123456']));

        $after = $this->body($ctl->checkPayPassword($this->authRequest($userId)));
        $this->assertTrue($after['data']['set']);
    }
}
