<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\technician\v1\controller;

use app\common\BaseController;
use app\model\TechnicianEarning;
use app\model\TechnicianWithdrawal;
use Webman\Http\Request;

/**
 * 技师提现控制器
 * 申请收益提现
 */
class WithdrawController extends BaseController
{
    /**
     * 申请提现
     * POST /api/technician/withdraw
     */
    public function store(Request $request)
    {
        $technicianId = $request->technician_id;
        $amount = (float)$request->input('amount', 0);
        $accountType = $request->input('account_type', 'wechat');
        $accountName = trim($request->input('account_name', ''));
        $accountNo = trim($request->input('account_no', ''));

        // 校验：当前日期是否为20号
        $currentDay = (int)date('d');
        if ($currentDay !== 20) {
            return $this->error('仅每月20号可申请提现');
        }

        // 校验金额
        $minAmount = 10.00;
        if ($amount < $minAmount) {
            return $this->error("提现金额不能低于{$minAmount}元");
        }

        if (empty($accountName) || empty($accountNo)) {
            return $this->error('请填写收款账户信息');
        }

        // 计算可用余额（P1: settled/withdrawn 合并为一次 GROUP BY status 聚合，走 idx_tech_status）
        $summary = TechnicianEarning::where('technician_id', $technicianId)
            ->whereIn('status', ['settled', 'withdrawn'])
            ->selectRaw('status, SUM(amount) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $settledTotal = (float)($summary['settled'] ?? 0);
        $withdrawnTotal = (float)($summary['withdrawn'] ?? 0);

        // 在途提现预留（pending/approved 未打款仍占用可提余额），防多笔申请叠加超提
        $pendingTotal = (float) TechnicianWithdrawal::where('technician_id', $technicianId)
            ->whereIn('status', ['pending', 'approved'])
            ->sum('amount');

        $balance = $settledTotal - $withdrawnTotal - $pendingTotal;

        if ($amount > $balance) {
            return $this->error('可提现余额不足');
        }

        // 计算手续费（示例：1%）
        $commissionFee = round($amount * 0.01, 2);
        $actualAmount = round($amount - $commissionFee, 2);

        // 创建提现记录
        $withdrawal = TechnicianWithdrawal::create([
            'id' => TechnicianWithdrawal::generateId(),
            'technician_id' => $technicianId,
            'withdrawal_no' => TechnicianWithdrawal::generateWithdrawalNo(),
            'amount' => $amount,
            'actual_amount' => $actualAmount,
            'commission_fee' => $commissionFee,
            'account_type' => $accountType,
            'account_name' => $accountName,
            'account_no' => $accountNo,
            'status' => 'pending',
        ]);

        return $this->success($withdrawal, '提现申请已提交，等待审核');
    }
}
