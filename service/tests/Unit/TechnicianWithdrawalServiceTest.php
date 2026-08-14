<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\common\TechnicianWithdrawalService;
use app\common\WechatPayService;
use app\model\TechnicianProfile;
use app\model\TechnicianWithdrawal;
use app\model\User;

/**
 * TechnicianWithdrawalService 单元测试（本次返工新增：审核通过后转账）
 *
 * 覆盖 approveAndTransfer 全分支：
 *   - 成功：置 completed + completed_at + audit_remark 写 payment_no
 *   - 失败：置 failed + audit_remark 记录错误
 *   - 落库失败（独立小事务异常）：返回失败语义，DB 行保持原状
 *   - 守卫：技师未绑定微信 / 提现金额无效
 *
 * 策略：WechatPayService 用 PHPUnit createMock 注入（构造器可选参数，BC 兼容），
 * 微信 IO 全程不发起真实请求；落库走测试 DB（bootstrap.php 已建 Eloquent Capsule），
 * 用例自造数据并 tearDown 清理。
 */
class TechnicianWithdrawalServiceTest extends TestCase
{
    /** @var string[] 用例创建的提现单号 */
    private array $withdrawalNos = [];

    /** @var int[] 用例创建的技师档案 ID */
    private array $technicianIds = [];

    /** @var int[] 用例创建的用户 ID */
    private array $userIds = [];

    protected function tearDown(): void
    {
        foreach ($this->withdrawalNos as $no) {
            TechnicianWithdrawal::where('withdrawal_no', $no)->delete();
        }
        foreach ($this->technicianIds as $id) {
            TechnicianProfile::where('id', $id)->forceDelete();
        }
        foreach ($this->userIds as $id) {
            User::where('id', $id)->forceDelete();
        }
        $this->withdrawalNos = [];
        $this->technicianIds = [];
        $this->userIds = [];
    }

    /** 造用户（含微信 openid，返回模型） */
    private function makeUser(string $openid): User
    {
        $user = User::create([
            'phone'    => '199' . substr((string) random_int(10000000, 99999999), 0, 8),
            'wx_openid' => $openid,
            'user_type' => 'user',
            'status'    => 1, // erik_user.status 为 tinyint
        ]);
        $this->userIds[] = $user->id;
        return $user;
    }

    /** 造技师档案（返回模型） */
    private function makeTechnician(User $user): TechnicianProfile
    {
        $tp = TechnicianProfile::create([
            'user_id' => $user->id,
            'real_name' => '测试技师',
            'status'    => 'active',
            'audited_at' => date('Y-m-d H:i:s'),
        ]);
        $this->technicianIds[] = $tp->id;
        return $tp;
    }

    /** 造提现单（返回模型；decimal 列用字符串写入，避免 brick/math 浮点弃用提示） */
    private function makeWithdrawal(TechnicianProfile $technician, float $actualAmount = 95.0): TechnicianWithdrawal
    {
        $w = TechnicianWithdrawal::create([
            'technician_id' => $technician->id,
            'withdrawal_no' => 'WD_TEST_' . uniqid(),
            'amount'        => '100.00',
            'actual_amount' => number_format($actualAmount, 2, '.', ''),
            'status'        => 'pending',
            'audit_remark'  => '',
        ]);
        $this->withdrawalNos[] = $w->withdrawal_no;
        return $w;
    }

    // ── 成功分支 ──

    #[Test] public function approve_and_transfer_success_marks_completed_with_payment_no(): void
    {
        $user = $this->makeUser('oX_withdrawal_success');
        $technician = $this->makeTechnician($user);
        $w = $this->makeWithdrawal($technician, 95.0);

        $mock = $this->createMock(WechatPayService::class);
        $mock->expects($this->once())
            ->method('transferToWallet')
            ->with($user->wx_openid, $w->withdrawal_no, 95.0, '技师提现')
            ->willReturn(['payment_no' => 'WXPAYNO_TEST123', 'result' => []]);

        $result = (new TechnicianWithdrawalService($mock))->approveAndTransfer($w);

        $this->assertTrue($result['success']);
        $this->assertSame('转账成功', $result['message']);

        $row = TechnicianWithdrawal::find($w->id);
        $this->assertSame('completed', $row->status);
        $this->assertNotNull($row->completed_at);
        $this->assertStringContainsString('payment_no:WXPAYNO_TEST123', $row->audit_remark);
    }

    // ── 失败分支 ──

    #[Test] public function approve_and_transfer_failure_marks_failed_with_error(): void
    {
        $user = $this->makeUser('oX_withdrawal_fail');
        $technician = $this->makeTechnician($user);
        $w = $this->makeWithdrawal($technician);

        $stub = $this->createStub(WechatPayService::class);
        $stub->method('transferToWallet')->willReturn(['error' => '余额不足']);

        $result = (new TechnicianWithdrawalService($stub))->approveAndTransfer($w);

        $this->assertFalse($result['success']);
        $this->assertSame('转账失败: 余额不足', $result['message']);

        $row = TechnicianWithdrawal::find($w->id);
        $this->assertSame('failed', $row->status);
        $this->assertStringContainsString('转账失败: 余额不足', $row->audit_remark);
    }

    // ── 落库失败分支 ──

    #[Test] public function approve_and_transfer_persist_failure_on_success_path_returns_failure_semantics(): void
    {
        $user = $this->makeUser('oX_withdrawal_persist');
        $technician = $this->makeTechnician($user);
        $w = $this->makeWithdrawal($technician);

        // 超长 payment_no → audit_remark 超列长(varchar 255) → 落库抛 DB 异常 → markCompleted 返回 false
        $stub = $this->createStub(WechatPayService::class);
        $stub->method('transferToWallet')->willReturn(['payment_no' => str_repeat('P', 300), 'result' => []]);

        $result = (new TechnicianWithdrawalService($stub))->approveAndTransfer($w);

        $this->assertFalse($result['success']);
        $this->assertSame('转账成功但状态落库失败，请人工核对', $result['message']);

        // 独立小事务回滚：DB 行保持原状（pending）
        $row = TechnicianWithdrawal::find($w->id);
        $this->assertSame('pending', $row->status);
        $this->assertNull($row->completed_at);
        $this->assertSame('', $row->audit_remark);
    }

    #[Test] public function approve_and_transfer_persist_failure_on_failed_path_returns_failure_semantics(): void
    {
        $user = $this->makeUser('oX_withdrawal_persist2');
        $technician = $this->makeTechnician($user);
        $w = $this->makeWithdrawal($technician);

        // 超长错误信息 → audit_remark 超列长 → markFailed 返回 false
        $stub = $this->createStub(WechatPayService::class);
        $stub->method('transferToWallet')->willReturn(['error' => str_repeat('E', 300)]);

        $result = (new TechnicianWithdrawalService($stub))->approveAndTransfer($w);

        $this->assertFalse($result['success']);
        $this->assertSame('转账失败，且状态落库失败，请人工核对', $result['message']);

        $row = TechnicianWithdrawal::find($w->id);
        $this->assertSame('pending', $row->status);
        $this->assertSame('', $row->audit_remark);
    }

    // ── 守卫分支 ──

    #[Test] public function approve_and_transfer_rejects_missing_openid(): void
    {
        $user = $this->makeUser('');
        $technician = $this->makeTechnician($user);
        $w = $this->makeWithdrawal($technician);

        // 未绑定微信：不触发转账
        $mock = $this->createMock(WechatPayService::class);
        $mock->expects($this->never())->method('transferToWallet');

        $result = (new TechnicianWithdrawalService($mock))->approveAndTransfer($w);

        $this->assertFalse($result['success']);
        $this->assertSame('技师未绑定微信，无法转账', $result['message']);
    }

    #[Test] public function approve_and_transfer_rejects_invalid_amount(): void
    {
        $user = $this->makeUser('oX_withdrawal_amount');
        $technician = $this->makeTechnician($user);
        $w = $this->makeWithdrawal($technician, 0.0);

        $mock = $this->createMock(WechatPayService::class);
        $mock->expects($this->never())->method('transferToWallet');

        $result = (new TechnicianWithdrawalService($mock))->approveAndTransfer($w);

        $this->assertFalse($result['success']);
        $this->assertSame('提现金额无效', $result['message']);
    }
}
