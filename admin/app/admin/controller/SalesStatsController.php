<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\Order;
use app\model\OrderPayment;
use support\Redis;
use support\Request;
use support\Response;

class SalesStatsController extends BaseController
{
    /**
     * 销售统计
     * 按日期范围统计：每日合计 / 按门店 / 按服务
     */
    public function index(Request $request): Response
    {
        $dateStart = $request->input('date_start', date('Y-m-d', strtotime('-30 days')));
        $dateEnd   = $request->input('date_end', date('Y-m-d'));
        $cacheKey  = "sales_stats:{$dateStart}_{$dateEnd}";
        $cached    = Redis::get($cacheKey);
        if ($cached) {
            return $this->success(json_decode($cached, true));
        }

        // 每日销售统计
        $dailyTotals = Order::whereBetween('created_at', [$dateStart . ' 00:00:00', $dateEnd . ' 23:59:59'])
            ->whereIn('status', ['paid', 'confirmed', 'serving', 'completed'])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as order_count, SUM(paid_amount) as total_amount')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();

        // 按门店统计（预约订单）
        $byStore = Order::whereBetween('created_at', [$dateStart . ' 00:00:00', $dateEnd . ' 23:59:59'])
            ->whereIn('status', ['paid', 'confirmed', 'serving', 'completed'])
            ->whereNotNull('store_id')
            ->with('store')
            ->selectRaw('store_id, COUNT(*) as order_count, SUM(paid_amount) as total_amount')
            ->groupBy('store_id')
            ->get()
            ->map(function ($item) {
                $data = $item->toArray();
                $data['store_name'] = $item->store->name ?? '未知门店';
                unset($data['store']);
                return $data;
            })
            ->toArray();

        // 按服务类型统计
        $byService = Order::whereBetween('created_at', [$dateStart . ' 00:00:00', $dateEnd . ' 23:59:59'])
            ->whereIn('status', ['paid', 'confirmed', 'serving', 'completed'])
            ->selectRaw('order_type, COUNT(*) as order_count, SUM(paid_amount) as total_amount')
            ->groupBy('order_type')
            ->get()
            ->toArray();

        // 汇总
        $summary = Order::whereBetween('created_at', [$dateStart . ' 00:00:00', $dateEnd . ' 23:59:59'])
            ->whereIn('status', ['paid', 'confirmed', 'serving', 'completed'])
            ->selectRaw('COUNT(*) as total_orders, SUM(paid_amount) as total_revenue, AVG(paid_amount) as avg_order_value')
            ->first()
            ->toArray();

        $data = [
            'date_start'   => $dateStart,
            'date_end'     => $dateEnd,
            'summary'      => $summary,
            'daily_totals' => $dailyTotals,
            'by_store'     => $byStore,
            'by_service'   => $byService,
        ];

        Redis::setex($cacheKey, 300, json_encode($data, JSON_UNESCAPED_UNICODE));

        return $this->success($data);
    }
}
