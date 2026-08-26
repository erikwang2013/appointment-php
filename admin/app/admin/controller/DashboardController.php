<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\AdminUser;
use app\common\EncryptionService;
use app\model\OperationLog;
use app\model\Order;
use app\model\OrderItem;
use app\model\OrderReview;
use app\model\Store;
use app\model\TechnicianProfile;
use app\model\TechnicianWithdrawal;
use Illuminate\Database\Capsule\Manager as DB;
use support\Redis;
use support\Request;
use support\Response;

class DashboardController extends BaseController
{
    /**
     * @Apidoc\Title("仪表盘数据")
     * @Apidoc\Group("dashboard")
     * @Apidoc\Url("/admin/dashboard")
     * @Apidoc\Desc("聚合统计/趋势/分布/最近操作，Redis缓存5分钟")
     * @Apidoc\Returned("stats", type="array", desc="统计卡片数据")
     * @Apidoc\Returned("trends", type="array", desc="趋势图数据")
     * @Apidoc\Returned("distribution", type="array", desc="分布数据")
     * @Apidoc\Returned("recent_logs", type="array", desc="最近操作日志")
     */
    public function index(Request $request): Response
    {
        // Redis 缓存 5 分钟，避免每次请求跑 5+ 条 SQL
        $cacheKey = 'dashboard:data';
        $cached = Redis::get($cacheKey);
        if ($cached) {
            return $this->success(json_decode($cached, true));
        }

        $today = date('Y-m-d');
        $startOfRange = date('Y-m-d', strtotime('-29 days'));

        $data = [
            'stats' => $this->getStats($today),
            'trends' => $this->getTrends($startOfRange),
            'distribution' => $this->getDistribution(),
            'recent_logs' => $this->getRecentLogs(),
            'store_comparison' => $this->getStoreComparison($request),
            'service_ranking' => $this->getServiceRanking(),
            'technician_ranking' => $this->getTechnicianRanking(),
            'peak_hours' => $this->getPeakHours(),
        ];

        Redis::setex($cacheKey, 300, json_encode($data, JSON_UNESCAPED_UNICODE));

        return $this->success($data);
    }

    private function getStats(string $today): array
    {
        $totalUsers = AdminUser::count();
        $todayNew = AdminUser::whereDate('created_at', $today)->count();
        $todayActive = AdminUser::whereDate('last_login_at', $today)->count();
        $todayLogs = OperationLog::whereDate('created_at', $today)->count();

        // Phase 7: 业务统计
        $todayAppointments = Order::where('order_type', 'appointment')
            ->whereDate('created_at', $today)->count();
        $pendingWithdrawals = TechnicianWithdrawal::where('status', 'pending')->count();
        $newTechnicians = TechnicianProfile::where('status', 0)->count();

        return [
            [
                'label' => '用户总数',
                'value' => (string) $totalUsers,
                'icon' => 'people',
                'color' => '#1677FF',
                'trend' => $this->calcTrend(AdminUser::class),
            ],
            [
                'label' => '今日新增',
                'value' => (string) $todayNew,
                'icon' => 'person_add',
                'color' => '#52C41A',
            ],
            [
                'label' => '活跃用户',
                'value' => (string) $todayActive,
                'icon' => 'bolt',
                'color' => '#FA8C16',
            ],
            [
                'label' => '操作日志',
                'value' => (string) $todayLogs,
                'icon' => 'description',
                'color' => '#722ED1',
            ],
            [
                'label' => '今日预约',
                'value' => (string) $todayAppointments,
                'icon' => 'event',
                'color' => '#EB2F96',
            ],
            [
                'label' => '待审核提现',
                'value' => (string) $pendingWithdrawals,
                'icon' => 'account_balance_wallet',
                'color' => '#FA541C',
            ],
            [
                'label' => '待审技师',
                'value' => (string) $newTechnicians,
                'icon' => 'engineering',
                'color' => '#13C2C2',
            ],
        ];
    }

    private function getTrends(string $startOfRange): array
    {
        $dates = [];
        $userGrowth = [];
        $logCounts = [];
        $appointmentsByDay = [];
        $revenueByDay = [];
        $newUsersByDay = [];

        // 生成日期序列
        for ($i = 29; $i >= 0; $i--) {
            $dates[] = date('Y-m-d', strtotime("+{$i} days", strtotime($startOfRange)));
        }

        // 一次查询获取用户每日新增数，PHP 内累加
        $dailyNewUsers = AdminUser::whereDate('created_at', '>=', $startOfRange)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $cumulative = AdminUser::whereDate('created_at', '<', $startOfRange)->count();
        foreach ($dates as $date) {
            $cumulative += $dailyNewUsers[$date] ?? 0;
            $userGrowth[] = $cumulative;
            $newUsersByDay[] = $dailyNewUsers[$date] ?? 0;
        }

        // 一次查询获取操作日志每日数量
        $dailyLogs = OperationLog::whereDate('created_at', '>=', $startOfRange)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        foreach ($dates as $date) {
            $logCounts[] = $dailyLogs[$date] ?? 0;
        }

        // Phase 7: 每日预约趋势
        $dailyAppointments = Order::where('order_type', 'appointment')
            ->whereDate('created_at', '>=', $startOfRange)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        foreach ($dates as $date) {
            $appointmentsByDay[] = $dailyAppointments[$date] ?? 0;
        }

        // Phase 7: 每日收入趋势（已支付金额）
        $dailyRevenue = Order::whereIn('status', ['paid', 'confirmed', 'serving', 'completed'])
            ->whereDate('created_at', '>=', $startOfRange)
            ->selectRaw('DATE(created_at) as date, SUM(paid_amount) as total')
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        foreach ($dates as $date) {
            $revenueByDay[] = round((float) ($dailyRevenue[$date] ?? 0), 2);
        }

        return [
            'dates' => $dates,
            'series' => [
                ['name' => '累计用户', 'data' => $userGrowth, 'color' => '#1677FF'],
                ['name' => '操作日志', 'data' => $logCounts, 'color' => '#52C41A'],
                ['name' => '每日预约', 'data' => $appointmentsByDay, 'color' => '#EB2F96'],
                ['name' => '每日收入', 'data' => $revenueByDay, 'color' => '#13C2C2'],
                ['name' => '每日新用户', 'data' => $newUsersByDay, 'color' => '#2F54EB'],
            ],
        ];
    }

    private function getDistribution(): array
    {
        return [
            'user_status' => [
                ['name' => '启用', 'value' => AdminUser::where('status', 1)->count()],
                ['name' => '禁用', 'value' => AdminUser::where('status', 0)->count()],
            ],
        ];
    }

    private function getRecentLogs(): array
    {
        return OperationLog::with('user')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($log) {
                $data = $log->toArray();
                $data['user_name'] = $log->user->username ?? '系统';
                unset($data['user'], $data['user_id']);
                return $data;
            })
            ->toArray();
    }

    private function calcTrend(string $modelClass): ?float
    {
        $today = $modelClass::whereDate('created_at', date('Y-m-d'))->count();
        $yesterday = $modelClass::whereDate('created_at', date('Y-m-d', strtotime('-1 day')))->count();

        if ($yesterday === 0) {
            return $today > 0 ? 100.0 : 0.0;
        }
        return round(($today - $yesterday) / $yesterday * 100, 1);
    }

    /**
     * 门店对比：近7天各门店营收/订单数
     */
    private function getStoreComparison(Request $request): array
    {
        $range = $request->input('range', 7);
        $start = date('Y-m-d', strtotime("-{$range} days"));

        $stores = Store::where('status', 1)->get();

        $storeData = [];
        foreach ($stores as $store) {
            $revenue = (float) Order::where('store_id', $store->id)
                ->whereIn('status', ['paid', 'confirmed', 'serving', 'completed'])
                ->whereDate('created_at', '>=', $start)
                ->sum('paid_amount');

            $orderCount = Order::where('store_id', $store->id)
                ->whereDate('created_at', '>=', $start)
                ->count();

            $storeData[] = [
                'store_id'    => (int) $store->id,
                'store_name'  => $store->name,
                'revenue'     => round($revenue, 2),
                'order_count' => $orderCount,
            ];
        }

        // 按营收降序
        usort($storeData, fn($a, $b) => $b['revenue'] <=> $a['revenue']);

        return [
            'range'  => "近{$range}天",
            'stores' => $storeData,
        ];
    }

    /**
     * 服务排行榜：Top 10 按销售额
     */
    private function getServiceRanking(): array
    {
        $ranking = OrderItem::where('target_type', 'service')
            ->selectRaw('target_id, name, SUM(quantity) as total_qty, SUM(price * quantity) as total_revenue')
            ->groupBy('target_id', 'name')
            ->orderBy('total_revenue', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'service_id'    => (int) $r->target_id,
                'service_name'  => $r->name,
                'total_qty'     => (int) $r->total_qty,
                'total_revenue' => round((float) $r->total_revenue, 2),
            ])
            ->toArray();

        return $ranking;
    }

    /**
     * 技师排行榜：Top 10 按评分 + 接单数
     */
    private function getTechnicianRanking(): array
    {
        $ranking = TechnicianProfile::where('status', 1)
            ->orderBy('rating', 'desc')
            ->orderBy('order_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($t) {
                return [
                    'technician_id'   => (int) $t->id,
                    'technician_name' => mb_substr($t->real_name, 0, 1) . '**',
                    'rating'          => (float) $t->rating,
                    'order_count'     => $t->order_count,
                    'favorite_count'  => $t->favorite_count,
                ];
            })
            ->toArray();

        return $ranking;
    }

    /**
     * 时段分布：近30天每小时订单分布
     */
    private function getPeakHours(): array
    {
        $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));

        $hourlyData = Order::whereDate('created_at', '>=', $thirtyDaysAgo)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderBy('hour')
            ->get()
            ->keyBy('hour')
            ->toArray();

        $hours = [];
        for ($h = 0; $h < 24; $h++) {
            $hours[] = [
                'hour'  => sprintf('%02d:00', $h),
                'count' => isset($hourlyData[$h]) ? (int) $hourlyData[$h]['count'] : 0,
            ];
        }

        // 找出峰值小时
        $peakHour = null;
        $peakCount = 0;
        foreach ($hours as $item) {
            if ($item['count'] > $peakCount) {
                $peakCount = $item['count'];
                $peakHour = $item['hour'];
            }
        }

        return [
            'range'       => '近30天',
            'hours'       => $hours,
            'peak_hour'   => $peakHour,
            'peak_count'  => $peakCount,
        ];
    }
}
