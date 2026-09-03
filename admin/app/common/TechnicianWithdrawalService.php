<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\common;

use app\model\TechnicianEarning;
use app\model\TechnicianWithdrawal;
use support\Db;
use support\Log;
use support\Redis;

/**
 * 技师提现服务（admin 端自包含副本）
 *
 * 提现审批全部通过后，调用微信企业付款（转账到零钱）完成打款。
 * 微信 IO 一律在事务外执行，落库使用独立小事务，避免长时间占用数据库连接。
 *
 * 注意：本副本与 service/app/common/TechnicianWithdrawalService.php 保持同实现，
 * 供 service 目录未挂载（config/autoload.php is_file 防护跳过）时兜底；
 * 已挂载时 autoload.php 优先加载 service 版，本副本不会重复定义。
 */
class TechnicianWithdrawalService
{
    private ?WechatPayService $payService;

    /**
     * @param WechatPayService|null $payService 可注入（测试 mock 用）；默认自行实例化
     */
    public function __construct(?WechatPayService $payService = null)
    {
        $this->payService = $payService;
    }

    /**
     * 审核通过后转账
     *
     * 成功：置 completed + completed_at，微信 payment_no 写入 audit_remark（免 DDL）；
     * 失败：置 failed，audit_remark 记录错误信息。
     *
     * @param TechnicianWithdrawal $w
     * @return array{success: bool, message: string}
     */
    public function approveAndTransfer(TechnicianWithdrawal $w): array
    {
        // P2: 并发防护 —— 参考 order_lock 模式（Redis NX EX + 状态复验），
        // 同一笔提现同时只允许一个转账流程执行，杜绝并发双打款 + 重复核销。
        // 锁获取语义：
        //  - NX 失败（锁已被占用）→ 直接拒绝，提示正在处理中；
        //  - Redis 异常 → 降级跳过互斥（可用性优先），但仍走 DB 状态复验与事务核销。
        $lockKey  = 'withdrawal_lock:' . $w->id;
        $token    = bin2hex(random_bytes(16));
        $locked   = false;
        try {
            $locked = (bool) Redis::connection()->set($lockKey, $token, 'EX', 60, 'NX');
        } catch (\Throwable $e) {
            Log::warning('[Withdrawal] lock unavailable, skip mutex, withdrawal_no: ' . $w->withdrawal_no . ', error: ' . $e->getMessage());
        }
        if (!$locked) {
            return ['success' => false, 'message' => '该提现正在处理中，请稍后重试'];
        }

        try {
            // 状态复验：以 DB 最新状态为准
            $fresh = TechnicianWithdrawal::find($w->id);
            if ($fresh && $fresh->status === 'completed') {
                // 幂等：已完成（转账 + 核销均落库），不再重复打款/核销
                return ['success' => true, 'message' => '转账成功'];
            }
            if ($fresh && !in_array($fresh->status, ['pending', 'approved'], true)) {
                return ['success' => false, 'message' => '提现状态已变化，无法转账'];
            }
            $w = $fresh ?? $w;

            // 技师微信 openid：TechnicianWithdrawal → TechnicianProfile → User
            $user = $w->technician ? $w->technician->user : null;
            if (!$user || empty($user->wx_openid)) {
                Log::error('[Withdrawal] technician openid missing, withdrawal_no: ' . $w->withdrawal_no);
                return ['success' => false, 'message' => '技师未绑定微信，无法转账'];
            }

            $amount = (float) $w->actual_amount;
            if ($amount <= 0) {
                Log::error('[Withdrawal] invalid actual_amount, withdrawal_no: ' . $w->withdrawal_no);
                return ['success' => false, 'message' => '提现金额无效'];
            }

            // 余额复核（防超提）：settled 与已核销 withdrawn 的差额 ≥ 本次提现额，不足拒绝。
            // 口径与申请侧一致（sum(settled) - sum(withdrawn)），转账前复验兜底并发/历史数据
            $summary = TechnicianEarning::where('technician_id', $w->technician_id)
                ->whereIn('status', ['settled', 'withdrawn'])
                ->selectRaw('status, SUM(amount) AS total')
                ->groupBy('status')
                ->pluck('total', 'status');
            // 余额/在途走 string 域（SUM 结果即 DECIMAL string），比较用 bccomp 防浮点丢分
            $available = (string)($summary['settled'] ?? 0);
            // 同技师其他在途申请（pending/approved）仍占用余额：并发审批下两笔同时通过只会都拒绝，
            // 不会双打款；口径与申请侧预留一致（sum(settled) - sum(withdrawn) - 在途）
            $inFlight = (string) TechnicianWithdrawal::where('technician_id', $w->technician_id)
                ->where('id', '!=', $w->id)
                ->whereIn('status', ['pending', 'approved'])
                ->sum('amount');
            if (Money::cmp(Money::sub($available, $inFlight), $w->amount) < 0) {
                Log::warning('[Withdrawal] insufficient balance, withdrawal_no: ' . $w->withdrawal_no
                    . ', available: ' . $available . ', in-flight: ' . $inFlight . ', amount: ' . $w->amount);
                return ['success' => false, 'message' => '可提现余额不足，无法转账'];
            }

            // 微信 IO 在事务外执行
            $payService = $this->payService ?? new WechatPayService();
            $result = $payService->transferToWallet($user->wx_openid, $w->withdrawal_no, $amount, '技师提现');

            if (!empty($result['error'])) {
                Log::error('[Withdrawal] transfer failed, withdrawal_no: ' . $w->withdrawal_no . ', error: ' . $result['error']);
                if (!$this->markFailed($w, $result['error'])) {
                    Log::error('[Withdrawal] mark failed persist error, withdrawal_no: ' . $w->withdrawal_no);
                    return ['success' => false, 'message' => '转账失败，且状态落库失败，请人工核对'];
                }
                return ['success' => false, 'message' => '转账失败: ' . $result['error']];
            }

            if (!$this->markCompleted($w, (string) ($result['payment_no'] ?? ''))) {
                Log::error('[Withdrawal] mark completed persist error, withdrawal_no: ' . $w->withdrawal_no);
                return ['success' => false, 'message' => '转账成功但状态落库失败，请人工核对'];
            }

            return ['success' => true, 'message' => '转账成功'];
        } finally {
            $this->releaseWithdrawalLock($lockKey, $token);
        }
    }

    /**
     * 释放提现互斥锁（仅当持有者 token 匹配时删除，防误删他人锁）
     */
    private function releaseWithdrawalLock(string $lockKey, string $token): void
    {
        try {
            $redis = Redis::connection();
            if ((string) ($redis->get($lockKey) ?? '') === $token) {
                $redis->del($lockKey);
            }
        } catch (\Throwable $e) {
            Log::warning('[Withdrawal] release lock failed, key: ' . $lockKey . ', error: ' . $e->getMessage());
        }
    }

    /**
     * 独立小事务落库：转账成功
     *
     * M2: 同事务内把该技师已 settled 的收益按 created_at 顺序原子标记 withdrawn，
     * 累计至本次实际到账金额 actual_amount（手续费部分不属于技师，不计入核销额度；
     * 跨记录时最后一条整条标记，因标记以记录为单位；核销额度 ≤ 可提现余额，故不会超扣）。
     * 可提现余额口径 = sum(settled) - sum(withdrawn)，此处标记后余额随之扣减，杜绝无限重复提现。
     *
     * P2: 一次性取满足条件的收益行并 lockForUpdate 行锁（并发下防止同一批收益被重复核销），
     * 再以单条 UPDATE ... WHERE id IN 批量落库，替代原先全量 get + 逐行 save（无索引 filesort + N 次写）。
     *
     * @return bool 落库成功返回 true，DB 异常返回 false（供调用方对账）
     */
    private function markCompleted(TechnicianWithdrawal $w, string $paymentNo): bool
    {
        try {
            Db::beginTransaction();
            $w->status       = 'completed';
            $w->completed_at = date('Y-m-d H:i:s');
            if ($paymentNo !== '') {
                $w->audit_remark = 'payment_no:' . $paymentNo . ($w->audit_remark ? '; ' . $w->audit_remark : '');
            }
            $w->save();

            // 扣减可提现余额：按 created_at 顺序核销 settled 收益为 withdrawn，累计至本次实际到账金额
            $remaining = (string) $w->actual_amount;
            if (Money::cmp($remaining, '0') > 0) {
                $earnings = TechnicianEarning::where('technician_id', $w->technician_id)
                    ->where('status', 'settled')
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $ids = [];
                foreach ($earnings as $earning) {
                    if (Money::cmp($remaining, '0') <= 0) {
                        break;
                    }
                    $ids[] = $earning->id;
                    $remaining = Money::sub($remaining, (string) $earning->amount);
                }
                if ($ids) {
                    TechnicianEarning::whereIn('id', $ids)
                        ->update(['status' => 'withdrawn']);
                }
            }

            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollBack();
            Log::error('[Withdrawal] mark completed failed, withdrawal_no: ' . $w->withdrawal_no . ', error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 独立小事务落库：转账失败
     *
     * @return bool 落库成功返回 true，DB 异常返回 false（供调用方对账）
     */
    private function markFailed(TechnicianWithdrawal $w, string $error): bool
    {
        try {
            Db::beginTransaction();
            $w->status       = 'failed';
            $w->audit_remark = '转账失败: ' . $error . ($w->audit_remark ? '; ' . $w->audit_remark : '');
            $w->save();
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollBack();
            Log::error('[Withdrawal] mark failed failed, withdrawal_no: ' . $w->withdrawal_no . ', error: ' . $e->getMessage());
            return false;
        }
    }
}
