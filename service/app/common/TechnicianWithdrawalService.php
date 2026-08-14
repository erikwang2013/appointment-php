<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\common;

use app\model\TechnicianWithdrawal;
use support\Db;
use support\Log;

/**
 * 技师提现服务
 *
 * 提现审批全部通过后，调用微信企业付款（转账到零钱）完成打款。
 * 微信 IO 一律在事务外执行，落库使用独立小事务，避免长时间占用数据库连接。
 *
 * 注意：本类同时被 service 端与 admin 端引用（admin 通过 config/autoload.php 加载同一实现）。
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
    }

    /**
     * 独立小事务落库：转账成功
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
