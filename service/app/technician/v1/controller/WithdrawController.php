<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\technician\v1\controller;

use app\common\BaseController;
use app\common\Money;
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
        // 入口 normalize 到 2dp（防 9.995 类长尾金额穿透校验链）
        $amount = (float) Money::round($request->input('amount', 0), 2);
        $accountType = $request->input('account_type', 'wechat');
        $accountName = trim($request->input('account_name', ''));
        $accountNo = trim($request->input('account_no', ''));

        // 校验：当前日期是否为门禁日（默认每月 20 号，config('withdraw.gate_day') 可覆盖）
        $gateDay = (int)config('withdraw.gate_day', 20);
        if ((int)date('d') !== $gateDay) {
            return $this->error("仅每月{$gateDay}号可申请提现");
        }

        // 校验金额
        $minAmount = 10.00;
        if (Money::cmp($amount, $minAmount) < 0) {
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

        $settledTotal = (string)($summary['settled'] ?? 0);
        $withdrawnTotal = (string)($summary['withdrawn'] ?? 0);

        // 在途提现预留（pending/approved 未打款仍占用可提余额），防多笔申请叠加超提
        $pendingTotal = (string) TechnicianWithdrawal::where('technician_id', $technicianId)
            ->whereIn('status', ['pending', 'approved'])
            ->sum('amount');

        // 三段减法链走 string 域精度，避免浮点逐级丢分
        $balance = Money::sub(Money::sub($settledTotal, $withdrawnTotal), $pendingTotal);

        if (Money::cmp($amount, $balance) > 0) {
            return $this->error('可提现余额不足');
        }

        // 计算手续费（示例：1%）
        $commissionFee = Money::round(Money::mul((string)$amount, '0.01'), 2);
        $actualAmount = Money::round(Money::sub((string)$amount, $commissionFee), 2);

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
