<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\common\TechnicianWithdrawalService;
use app\common\WechatPayService;
use app\model\TechnicianEarning;
use app\model\TechnicianProfile;
use app\model\TechnicianWithdrawal;
use app\model\User;
use support\Redis;

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

    /** @var string[] 用例创建的收益流水 ID */
    private array $earningIds = [];

    /** @var string[] 用例写入的 Redis 锁 key（提现互斥） */
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
        $this->redisKeys = [];
        $this->earningIds = [];
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

    /** 造 settled 收益流水（createdAt 可显式指定以控制核销顺序） */
    private function makeSettledEarning(TechnicianProfile $technician, float $amount, ?string $createdAt = null): TechnicianEarning
    {
        $e = TechnicianEarning::create([
            'id'            => TechnicianEarning::generateId(),
            'technician_id' => $technician->id,
            'order_id'      => 0,
            'type'          => 'commission',
            'amount'        => number_format($amount, 2, '.', ''),
            'status'        => 'settled',
        ]);
        if ($createdAt !== null) {
            $e->created_at = $createdAt;
            $e->save();
        }
        $this->earningIds[] = $e->id;
        return $e;
    }

    /** 查询技师下指定状态的收益合计 */
    private function earningSum(TechnicianProfile $technician, string $status): float
    {
        return (float) TechnicianEarning::where('technician_id', $technician->id)
            ->where('status', $status)
            ->sum('amount');
    }

    // ── 成功分支 ──

    #[Test] public function approve_and_transfer_success_marks_completed_with_payment_no(): void
    {
        $user = $this->makeUser('oX_withdrawal_success');
        $technician = $this->makeTechnician($user);
        $w = $this->makeWithdrawal($technician, 95.0);
        // 余额复核：补足 settled 收益（100 ≥ 提现额 100），通过可提现余额校验
        $this->makeSettledEarning($technician, 100.0);

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

        // 余额复核：补足 settled 收益（100 ≥ 提现额 100），通过可提现余额校验
        $this->makeSettledEarning($technician, 100.0);
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

        // 超长 payment_no → audit_remark 超列长
        // 余额复核：补足 settled 收益（100 ≥ 提现额 100），通过可提现余额校验
        $this->makeSettledEarning($technician, 100.0);
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

        // 超长错误信息 → audit_remark 超列长
        // 余额复核：补足 settled 收益（100 ≥ 提现额 100），通过可提现余额校验
        $this->makeSettledEarning($technician, 100.0);
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

    // ── P2: 核销语义（按 actual_amount，created_at 顺序，批量 UPDATE）──

    #[Test] public function mark_completed_writes_off_earnings_by_actual_amount_in_created_at_order(): void
    {
        $user = $this->makeUser('oX_writeoff_order');
        $technician = $this->makeTechnician($user);
        $w = $this->makeWithdrawal($technician, 60.0); // 提现 100，实际到账 60

        // settled 收益 100：先入账 60，后入账 40
        $older = $this->makeSettledEarning($technician, 60.0, '2026-08-01 10:00:00');
        $newer = $this->makeSettledEarning($technician, 40.0, '2026-08-01 10:00:01');

        $mock = $this->createMock(WechatPayService::class);
        $mock->method('transferToWallet')->willReturn(['payment_no' => 'WXPAYNO_WO1', 'result' => []]);

        $result = (new TechnicianWithdrawalService($mock))->approveAndTransfer($w);
        $this->assertTrue($result['success']);

        // 核销额度 = actual_amount 60：仅最早一条 60 被核销，后一条 40 保持 settled
        $this->assertSame('withdrawn', TechnicianEarning::find($older->id)->status);
        $this->assertSame('settled', TechnicianEarning::find($newer->id)->status);
        $this->assertEqualsWithDelta(60.0, $this->earningSum($technician, 'withdrawn'), 0.01);
    }

    #[Test] public function mark_completed_marks_last_record_whole_when_remaining_overflows(): void
    {
        $user = $this->makeUser('oX_writeoff_whole');
        $technician = $this->makeTechnician($user);
        $w = $this->makeWithdrawal($technician, 95.0); // 实际到账 95

        // settled 收益 100：50 + 50，核销以记录为单位，最后一条整条标记
        $this->makeSettledEarning($technician, 50.0, '2026-08-01 10:00:00');
        $this->makeSettledEarning($technician, 50.0, '2026-08-01 10:00:01');

        $mock = $this->createMock(WechatPayService::class);
        $mock->method('transferToWallet')->willReturn(['payment_no' => 'WXPAYNO_WO2', 'result' => []]);

        $result = (new TechnicianWithdrawalService($mock))->approveAndTransfer($w);
        $this->assertTrue($result['success']);

        // 50 核销后剩余 45，第二条 50 整条标记（标记以记录为单位，与既有语义一致）
        $this->assertEqualsWithDelta(100.0, $this->earningSum($technician, 'withdrawn'), 0.01);
    }

    // ── P2: 并发防护 / 幂等 ──

    #[Test] public function approve_and_transfer_completed_twice_transfers_and_writes_off_only_once(): void
    {
        $user = $this->makeUser('oX_idempotent');
        $technician = $this->makeTechnician($user);
        $w = $this->makeWithdrawal($technician, 60.0);
        $e1 = $this->makeSettledEarning($technician, 60.0, '2026-08-01 10:00:00');
        $e2 = $this->makeSettledEarning($technician, 40.0, '2026-08-01 10:00:01');

        // 第一次：正常转账成功
        $mock1 = $this->createMock(WechatPayService::class);
        $mock1->expects($this->once())->method('transferToWallet')->willReturn(['payment_no' => 'WXPAYNO_IDEM', 'result' => []]);
        $result1 = (new TechnicianWithdrawalService($mock1))->approveAndTransfer($w);
        $this->assertTrue($result1['success']);
        $this->assertSame('completed', TechnicianWithdrawal::find($w->id)->status);
        $this->assertSame('withdrawn', TechnicianEarning::find($e1->id)->status);

        // 第二次（重复 complete）：状态复验命中 completed → 幂等成功，不再打款/核销
        $mock2 = $this->createMock(WechatPayService::class);
        $mock2->expects($this->never())->method('transferToWallet');
        $result2 = (new TechnicianWithdrawalService($mock2))->approveAndTransfer($w);
        $this->assertTrue($result2['success']);
        $this->assertSame('转账成功', $result2['message']);

        // 核销未重复：第二条收益仍未核销
        $this->assertSame('settled', TechnicianEarning::find($e2->id)->status);
        $this->assertEqualsWithDelta(60.0, $this->earningSum($technician, 'withdrawn'), 0.01);
    }

    #[Test] public function approve_and_transfer_rejected_while_mutex_lock_held(): void
    {
        $user = $this->makeUser('oX_mutex');
        $technician = $this->makeTechnician($user);
        $w = $this->makeWithdrawal($technician);

        // 模拟另一请求已持有互斥锁（Redis NX EX）
        $lockKey = 'withdrawal_lock:' . $w->id;
        Redis::set($lockKey, 'other-token', 'EX', 60);
        $this->redisKeys[] = $lockKey;

        $mock = $this->createMock(WechatPayService::class);
        $mock->expects($this->never())->method('transferToWallet');

        $result = (new TechnicianWithdrawalService($mock))->approveAndTransfer($w);

        $this->assertFalse($result['success']);
        $this->assertSame('该提现正在处理中，请稍后重试', $result['message']);
        $this->assertSame('pending', TechnicianWithdrawal::find($w->id)->status);
    }

    // ── P2: 并发审批防双打款（在途申请占用余额）──

    #[Test] public function approve_and_transfer_blocks_when_inflight_withdrawal_reserved(): void
    {
        $user = $this->makeUser('oX_inflight');
        $technician = $this->makeTechnician($user);
        // settled 收益 100；同技师另一笔在途申请（pending）已占用 60 → 可提现 = 40
        $this->makeSettledEarning($technician, 100.0);
        $this->makeWithdrawal($technician, 60.0);

        // 本笔提现额 100 > 可提现 40：并发审批下必须拒绝，杜绝双打款
        $w = $this->makeWithdrawal($technician, 50.0);

        $mock = $this->createMock(WechatPayService::class);
        $mock->expects($this->never())->method('transferToWallet');

        $result = (new TechnicianWithdrawalService($mock))->approveAndTransfer($w);

        $this->assertFalse($result['success']);
        $this->assertSame('可提现余额不足，无法转账', $result['message']);
        $this->assertSame('pending', TechnicianWithdrawal::find($w->id)->status);
    }
}
