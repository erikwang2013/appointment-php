<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\technician\v1\controller;

use app\common\BaseController;
use app\model\TechnicianEarning;
use Webman\Http\Request;

/**
 * 技师收益控制器
 * 查看技师收益汇总与明细
 */
class EarningController extends BaseController
{
    /**
     * 技师收益汇总与明细
     * GET /api/technician/earnings
     */
    public function index(Request $request)
    {
        $technicianId = $request->technician_id;
        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 15);

        $today = date('Y-m-d');

        // 今日收入 (type=commission, status in pending/settled)
        $todayIncome = TechnicianEarning::where('technician_id', $technicianId)
            ->where('type', 'commission')
            ->whereIn('status', ['pending', 'settled'])
            ->whereDate('created_at', $today)
            ->sum('amount');

        // 待结算 (status=pending)
        $pendingSettlement = TechnicianEarning::where('technician_id', $technicianId)
            ->where('status', 'pending')
            ->sum('amount');

        // 已结算 - 已提现 = 可用余额
        $settledTotal = TechnicianEarning::where('technician_id', $technicianId)
            ->where('status', 'settled')
            ->sum('amount');

        $withdrawnTotal = TechnicianEarning::where('technician_id', $technicianId)
            ->where('status', 'withdrawn')
            ->sum('amount');

        $balance = (float)$settledTotal - (float)$withdrawnTotal;

        // 收益明细
        $earnings = TechnicianEarning::where('technician_id', $technicianId)
            ->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'summary' => [
                    'today_income' => (float)$todayIncome,
                    'pending_settlement' => (float)$pendingSettlement,
                    'balance' => round($balance, 2),
                ],
                'earnings' => $this->encodeIds($earnings->items()),
                'meta' => [
                    'current_page' => $earnings->currentPage(),
                    'per_page' => $earnings->perPage(),
                    'total' => $earnings->total(),
                    'last_page' => $earnings->lastPage(),
                    'has_more' => $earnings->hasMorePages(),
                ],
            ],
        ]);
    }
}
