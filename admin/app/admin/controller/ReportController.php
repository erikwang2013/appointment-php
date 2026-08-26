<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\Order;
use app\model\OrderPayment;
use app\model\OrderRefund;
use app\model\TechnicianProfile;
use Illuminate\Database\Capsule\Manager as DB;
use support\Redis;
use support\Request;
use support\Response;

/**
 * 数据报表（S7 管理端统计报表）
 *
 * 提供三类报表接口：
 * - orders：      订单统计（订单数/支付金额/退款金额/净营收 + 按天趋势）
 * - technicians： 技师绩效排名（按单量或营收，TOP 10）
 * - distribution：支付渠道分布 + 订单状态分布
 *
 * 聚合 SQL 使用 Eloquent groupBy + selectRaw；结果 Redis 缓存 5 分钟
 * （键 svc:admin_report:{type}:{start}:{end}，写操作 clearSvcCache 会一并失效）。
 */
class ReportController extends BaseController
{
    /** 营收口径：已支付状态的订单（与 DashboardController 一致） */
    private const PAID_STATUSES = ['paid', 'confirmed', 'serving', 'completed'];

    /** 订单状态展示名 */
    private const STATUS_LABELS = [
        'pending'   => '待支付',
        'paid'      => '已支付',
        'confirmed' => '已确认',
        'serving'   => '服务中',
        'completed' => '已完成',
        'cancelled' => '已取消',
        'refunding' => '退款中',
        'refunded'  => '已退款',
    ];

    /** 支付渠道展示名 */
    private const PAY_TYPE_LABELS = [
        'wechat'  => '微信支付',
        'alipay'  => '支付宝',
        'balance' => '余额支付',
    ];

    /**
     * 订单统计：时间范围内订单数/支付成功订单数/支付金额/退款金额/净营收 + 按天趋势
     *
     * @Apidoc\Title("订单统计报表")
     * @Apidoc\Group("report")
     * @Apidoc\Url("/admin/reports/orders")
     * @Apidoc\Param("start_date", type="string", desc="开始日期 Y-m-d，默认近7天")
     * @Apidoc\Param("end_date", type="string", desc="结束日期 Y-m-d，默认今天")
     * @Apidoc\Returned("summary", type="array", desc="汇总数字")
     * @Apidoc\Returned("list", type="array", desc="按天趋势数组")
     */
    public function orders(Request $request): Response
    {
        [$start, $end] = $this->parseRange($request, 7);

        $cached = $this->cacheGet('orders', $start, $end);
        if ($cached !== null) {
            return $this->success($cached);
        }

        // 汇总：订单总数 / 已支付订单数 / 支付金额（实付）
        $summary = [
            'total_orders'  => (int) Order::whereDate('created_at', '>=', $start)
                ->whereDate('created_at', '<=', $end)->count(),
            'paid_orders'   => (int) Order::whereIn('status', self::PAID_STATUSES)
                ->whereDate('created_at', '>=', $start)
                ->whereDate('created_at', '<=', $end)->count(),
            'payment_amount' => (float) Order::whereIn('status', self::PAID_STATUSES)
                ->whereDate('created_at', '>=', $start)
                ->whereDate('created_at', '<=', $end)->sum('paid_amount'),
            'refund_amount' => (float) OrderRefund::where('status', 'success')
                ->whereDate('refunded_at', '>=', $start)
                ->whereDate('refunded_at', '<=', $end)->sum('amount'),
        ];
        $summary['net_revenue'] = round($summary['payment_amount'] - $summary['refund_amount'], 2);
        $summary['payment_amount'] = round($summary['payment_amount'], 2);
        $summary['refund_amount'] = round($summary['refund_amount'], 2);

        // 按天趋势：订单数 / 支付金额 / 退款金额 / 净营收
        $dailyOrders = Order::whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, '
                . "SUM(CASE WHEN status IN ('" . implode("','", self::PAID_STATUSES) . "') THEN paid_amount ELSE 0 END) as paid_amount")
            ->groupBy('date')->get()->keyBy('date')->toArray();

        $dailyRefunds = OrderRefund::where('status', 'success')
            ->whereDate('refunded_at', '>=', $start)
            ->whereDate('refunded_at', '<=', $end)
            ->selectRaw('DATE(refunded_at) as date, SUM(amount) as amount')
            ->groupBy('date')->get()->keyBy('date')->toArray();

        $trend = [];
        $cursor = strtotime($start);
        $endTs = strtotime($end);
        while ($cursor <= $endTs) {
            $date = date('Y-m-d', $cursor);
            $paid = round((float) ($dailyOrders[$date]['paid_amount'] ?? 0), 2);
            $refund = round((float) ($dailyRefunds[$date]['amount'] ?? 0), 2);
            $trend[] = [
                'date'          => $date,
                'order_count'   => (int) ($dailyOrders[$date]['count'] ?? 0),
                'payment_amount' => $paid,
                'refund_amount' => $refund,
                'net_revenue'   => round($paid - $refund, 2),
            ];
            $cursor = strtotime('+1 day', $cursor);
        }

        $data = [
            'start_date' => $start,
            'end_date'   => $end,
            'summary'    => $summary,
            'total'      => count($trend),
            'list'       => $trend,
        ];

        $this->cacheSet('orders', $start, $end, $data);

        return $this->success($data);
    }

    /**
     * 技师绩效排名：按订单数或营收 TOP 10（技师 hashid/姓名/单量/营收/平均评分）
     *
     * @Apidoc\Title("技师绩效报表")
     * @Apidoc\Group("report")
     * @Apidoc\Url("/admin/reports/technicians")
     * @Apidoc\Param("sort_by", type="string", desc="order_count=按单量 revenue=按营收，默认 revenue")
     */
    public function technicians(Request $request): Response
    {
        [$start, $end] = $this->parseRange($request, 30);
        $sortBy = in_array($request->input('sort_by', 'revenue'), ['order_count', 'revenue'], true)
            ? (string) $request->input('sort_by') : 'revenue';

        $cached = $this->cacheGet('technicians', $start, $end, $sortBy);
        if ($cached !== null) {
            return $this->success($cached);
        }

        // 按技师聚合：单量（范围内全部订单）+ 营收（已支付订单 paid_amount）
        $rows = Order::where('technician_id', '>', 0)
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->selectRaw('technician_id, COUNT(*) as order_count, '
                . "SUM(CASE WHEN status IN ('" . implode("','", self::PAID_STATUSES) . "') THEN paid_amount ELSE 0 END) as revenue")
            ->groupBy('technician_id')
            ->get()->keyBy('technician_id');

        $profiles = TechnicianProfile::whereIn('id', $rows->keys())
            ->select(['id', 'real_name', 'rating'])
            ->get()->keyBy('id');

        $list = [];
        foreach ($rows as $techId => $row) {
            $profile = $profiles->get($techId);
            if (!$profile) {
                continue; // 技师档案已删除，跳过
            }
            $list[] = [
                'technician_id'   => (int) $techId,
                'technician_name' => mb_substr($profile->real_name, 0, 1) . '**',
                'order_count'     => (int) $row->order_count,
                'revenue'         => round((float) $row->revenue, 2),
                'rating'          => (float) $profile->rating,
            ];
        }
        usort($list, fn($a, $b) => $sortBy === 'revenue' ? ($b['revenue'] <=> $a['revenue']) : ($b['order_count'] <=> $a['order_count']));
        $list = array_slice($list, 0, 10);

        $data = [
            'start_date' => $start,
            'end_date'   => $end,
            'sort_by'    => $sortBy,
            'total'      => count($list),
            'list'       => array_values($list),
        ];

        $this->cacheSet('technicians', $start, $end, $data, $sortBy);

        return $this->success($data);
    }

    /**
     * 渠道/类型分布：支付渠道分布（wechat/alipay/balance）+ 订单状态分布
     *
     * @Apidoc\Title("分布报表")
     * @Apidoc\Group("report")
     * @Apidoc\Url("/admin/reports/distribution")
     */
    public function distribution(Request $request): Response
    {
        [$start, $end] = $this->parseRange($request, 30);

        $cached = $this->cacheGet('distribution', $start, $end);
        if ($cached !== null) {
            return $this->success($cached);
        }

        // 支付渠道：order_payment 成功记录按 pay_type 分组
        $payTypes = OrderPayment::where('status', 'success')
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->selectRaw('pay_type, COUNT(*) as count, SUM(amount) as amount')
            ->groupBy('pay_type')->get()->keyBy('pay_type');

        // 余额支付：钱包消费流水（type=consume）按订单去重
        $balanceOrders = DB::table('erik_wallet_txn')
            ->where('type', 'consume')
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->distinct()->count('order_id');
        $balanceAmount = (float) DB::table('erik_wallet_txn')
            ->where('type', 'consume')
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->sum('amount');

        $payTypeList = [];
        foreach (array_keys(self::PAY_TYPE_LABELS) as $type) {
            $row = $payTypes->get($type);
            if ($type === 'balance') {
                $payTypeList[] = [
                    'name'   => self::PAY_TYPE_LABELS[$type],
                    'type'   => $type,
                    'count'  => (int) $balanceOrders,
                    'amount' => round($balanceAmount, 2),
                ];
                continue;
            }
            $payTypeList[] = [
                'name'   => self::PAY_TYPE_LABELS[$type] ?? $type,
                'type'   => $type,
                'count'  => (int) ($row->count ?? 0),
                'amount' => round((float) ($row->amount ?? 0), 2),
            ];
        }

        // 订单状态分布
        $statusRows = Order::whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')->get();

        $statusList = [];
        foreach ($statusRows as $row) {
            $statusList[] = [
                'name'  => self::STATUS_LABELS[$row->status] ?? $row->status,
                'status' => $row->status,
                'count' => (int) $row->count,
            ];
        }
        usort($statusList, fn($a, $b) => $b['count'] <=> $a['count']);

        $data = [
            'start_date' => $start,
            'end_date'   => $end,
            'pay_type'   => $payTypeList,
            'status'     => $statusList,
        ];

        $this->cacheSet('distribution', $start, $end, $data);

        return $this->success($data);
    }

    /**
     * 解析时间范围：默认近 $defaultDays 天（含今天），限制最长 92 天
     *
     * @return array{0: string, 1: string} [start_date, end_date]
     */
    private function parseRange(Request $request, int $defaultDays): array
    {
        $end = (string) $request->input('end_date', date('Y-m-d'));
        $start = (string) $request->input('start_date', date('Y-m-d', strtotime('-' . ($defaultDays - 1) . ' days')));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) {
            $start = date('Y-m-d', strtotime('-' . ($defaultDays - 1) . ' days'));
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
            $end = date('Y-m-d');
        }
        if (strtotime($end) < strtotime($start)) {
            [$start, $end] = [$end, $start];
        }
        $maxStart = date('Y-m-d', strtotime('-92 days', strtotime($end)));
        if (strtotime($start) < strtotime($maxStart)) {
            $start = $maxStart;
        }

        return [$start, $end];
    }

    private function cacheKey(string $type, string $start, string $end, ?string $suffix = null): string
    {
        return "svc:admin_report:{$type}:{$start}:{$end}" . ($suffix ? ":{$suffix}" : '');
    }

    private function cacheGet(string $type, string $start, string $end, ?string $suffix = null): ?array
    {
        $cached = Redis::get($this->cacheKey($type, $start, $end, $suffix));
        return $cached ? json_decode($cached, true) : null;
    }

    private function cacheSet(string $type, string $start, string $end, array $data, ?string $suffix = null): void
    {
        Redis::setex($this->cacheKey($type, $start, $end, $suffix), 300, json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}
