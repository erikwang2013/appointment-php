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

        // P1: 汇总聚合合并 —— pending/settled/withdrawn 三态合并为一次 GROUP BY status
        // 聚合（走 idx_tech_status 复合索引），替代原先 4 次独立 SUM 全量扫描
        $summary = TechnicianEarning::where('technician_id', $technicianId)
            ->selectRaw('status, SUM(amount) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // 待结算 (status=pending)
        $pendingSettlement = (float)($summary['pending'] ?? 0);

        // 已结算 - 已提现 = 可用余额
        $settledTotal = (float)($summary['settled'] ?? 0);
        $withdrawnTotal = (float)($summary['withdrawn'] ?? 0);

        $balance = $settledTotal - $withdrawnTotal;

        // 今日收入 (type=commission, status in pending/settled, 当日)
        // 聚合条件含 type + 日期维度，无法并入上述 status 分组，独立一次 SUM
        $todayIncome = TechnicianEarning::where('technician_id', $technicianId)
            ->where('type', 'commission')
            ->whereIn('status', ['pending', 'settled'])
            ->whereDate('created_at', $today)
            ->sum('amount');

        // 收益明细
        $earnings = TechnicianEarning::where('technician_id', $technicianId)
            ->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return json([
            'code' => 0,
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
