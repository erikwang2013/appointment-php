<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\technician\v1\controller\WithdrawController;
use app\model\TechnicianEarning;
use app\model\TechnicianProfile;
use app\model\TechnicianWithdrawal;
use app\model\User;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 技师提现控制器测试
 *
 * 控制器第一道校验是"仅每月 20 号可申请提现"（硬门禁，非业务可绕过）：
 * - 非 20 号：断言门禁拒绝且不落库（全年可跑）
 * - 20 号当天：跑通成功路径（settled 收益充足 → 创建 pending 单 + 1% 手续费）、
 *   余额不足拒绝、金额低于 10 拒绝、账户信息缺失拒绝
 * 非 20 号时后四者 markTestSkipped。
 */
class WithdrawControllerTest extends TestCase
{
    /** @var int[] 用例技师档案 ID */
    private array $technicianIds = [];

    /** @var string[] 用例用户 ID */
    private array $userIds = [];

    /** @var string[] 用例收益流水 ID */
    private array $earningIds = [];

    /** @var string[] 用例提现单号 */
    private array $withdrawalNos = [];

    protected function tearDown(): void
    {
        foreach ($this->earningIds as $id) {
            TechnicianEarning::where('id', $id)->delete();
        }
        foreach ($this->withdrawalNos as $no) {
            TechnicianWithdrawal::where('withdrawal_no', $no)->delete();
        }
        foreach ($this->technicianIds as $id) {
            TechnicianProfile::where('id', $id)->forceDelete();
        }
        foreach ($this->userIds as $id) {
            User::where('id', $id)->forceDelete();
        }
        $this->earningIds = [];
        $this->withdrawalNos = [];
        $this->technicianIds = [];
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

    private function makeTechnician(): TechnicianProfile
    {
        $user = User::create([
            'phone'    => '194' . substr((string) random_int(10000000, 99999999), 0, 8),
            'wx_openid' => '',
            'user_type' => 'user',
            'status'    => 1,
        ]);
        $this->userIds[] = $user->id;
        $tp = TechnicianProfile::create([
            'user_id'   => $user->id,
            'real_name' => '测试技师',
            'status'    => 'active',
            'audited_at' => date('Y-m-d H:i:s'),
        ]);
        $this->technicianIds[] = $tp->id;
        return $tp;
    }

    private function makeSettledEarning(TechnicianProfile $technician, float $amount): TechnicianEarning
    {
        $e = TechnicianEarning::create([
            'id'            => TechnicianEarning::generateId(),
            'technician_id' => $technician->id,
            'order_id'      => 0,
            'type'          => 'commission',
            'amount'        => number_format($amount, 2, '.', ''),
            'status'        => 'settled',
        ]);
        $this->earningIds[] = $e->id;
        return $e;
    }

    private function store(string $technicianId, array $post = []): array
    {
        $request = $this->makeRequest($post);
        $request->technician_id = $technicianId;
        return $this->body((new WithdrawController())->store($request));
    }

    #[Test] public function store_rejects_outside_20th_without_side_effects(): void
    {
        if ((int) date('d') === 20) {
            $this->markTestSkipped('今天是 20 号，门禁放行');
        }
        $technician = $this->makeTechnician();
        $this->makeSettledEarning($technician, 200.0);

        $resp = $this->store($technician->id, [
            'amount' => '50', 'account_type' => 'wechat',
            'account_name' => '测试技师', 'account_no' => 'wx_123',
        ]);

        $this->assertSame(400, $resp['code']);
        $this->assertStringContainsString('仅每月20号可申请提现', (string) $resp['message']);
        $this->assertSame(0, TechnicianWithdrawal::where('technician_id', $technician->id)->count(), '门禁拒绝不落库');
    }

    #[Test] public function store_success_creates_pending_withdrawal_with_fee(): void
    {
        if ((int) date('d') !== 20) {
            $this->markTestSkipped('仅每月 20 号可申请提现');
        }
        $technician = $this->makeTechnician();
        $this->makeSettledEarning($technician, 200.0);

        $resp = $this->store($technician->id, [
            'amount' => '50', 'account_type' => 'wechat',
            'account_name' => '测试技师', 'account_no' => 'wx_123',
        ]);

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame(50.0, (float) $resp['data']['amount']);
        $this->assertSame(0.5, (float) $resp['data']['commission_fee'], '手续费 1%');
        $this->assertSame(49.5, (float) $resp['data']['actual_amount']);
        $this->assertSame('pending', $resp['data']['status']);
        $this->withdrawalNos[] = $resp['data']['withdrawal_no'];
    }

    #[Test] public function store_rejects_insufficient_balance(): void
    {
        if ((int) date('d') !== 20) {
            $this->markTestSkipped('仅每月 20 号可申请提现');
        }
        $technician = $this->makeTechnician();
        $this->makeSettledEarning($technician, 10.0);

        $resp = $this->store($technician->id, [
            'amount' => '50', 'account_name' => '测试技师', 'account_no' => 'wx_123',
        ]);

        $this->assertSame(400, $resp['code']);
        $this->assertStringContainsString('可提现余额不足', (string) $resp['message']);
        $this->assertSame(0, TechnicianWithdrawal::where('technician_id', $technician->id)->count());
    }

    #[Test] public function store_rejects_amount_below_minimum(): void
    {
        if ((int) date('d') !== 20) {
            $this->markTestSkipped('仅每月 20 号可申请提现');
        }
        $technician = $this->makeTechnician();
        $this->makeSettledEarning($technician, 100.0);

        $resp = $this->store($technician->id, [
            'amount' => '9.99', 'account_name' => '测试技师', 'account_no' => 'wx_123',
        ]);

        $this->assertSame(400, $resp['code']);
        $this->assertStringContainsString('提现金额不能低于10元', (string) $resp['message']);
    }

    #[Test] public function store_rejects_missing_account_info(): void
    {
        if ((int) date('d') !== 20) {
            $this->markTestSkipped('仅每月 20 号可申请提现');
        }
        $technician = $this->makeTechnician();
        $this->makeSettledEarning($technician, 100.0);

        $resp = $this->store($technician->id, ['amount' => '50', 'account_name' => '', 'account_no' => '']);

        $this->assertSame(400, $resp['code']);
        $this->assertStringContainsString('请填写收款账户信息', (string) $resp['message']);
    }
}
