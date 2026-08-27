<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\model\Order;
use app\model\OrderVerification;
use app\model\TechnicianProfile;
use app\model\TechnicianSchedule;
use app\model\User;
use support\Db;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 店长工作台控制器
 *
 * 所有接口按当前登录用户 store_id 强制隔离：
 * 无门店（store_id 为空/0）一律 403，查询全部附加 where('store_id', 本人门店)。
 *
 * 今日口径：订单数按 created_at 今日；营收按 status=completed 且 updated_at 今日
 * （订单完成时 updated_at 被状态流转刷新，作为完成时间戳）；
 * 核销数按 verified_at 今日。
 */
class StoreManagerController extends BaseController
{
    /** 进行中 = 已下单/已支付/待服务/服务中（未完成未取消） */
    private const ONGOING_STATUSES = [
        Order::STATUS_PENDING,
        Order::STATUS_PAID,
        Order::STATUS_CONFIRMED,
        Order::STATUS_SERVING,
    ];

    /**
     * 解析当前登录用户的门店 ID；无门店权限返回 null
     */
    private function storeId(Request $request): ?int
    {
        $user = User::find($request->user_id);
        if (!$user) {
            return null;
        }
        $storeId = (int) $user->store_id;
        return $storeId > 0 ? $storeId : null;
    }

    /**
     * 本店概览
     * GET /api/store-manager/overview
     * 今日订单数 / 今日营收 / 进行中订单 / 技师数 / 今日核销数
     */
    public function overview(Request $request): Response
    {
        $storeId = $this->storeId($request);
        if ($storeId === null) {
            return $this->error('无门店权限', 403);
        }
        $todayStart = date('Y-m-d 00:00:00');

        $todayOrders = Order::where('store_id', $storeId)
            ->where('created_at', '>=', $todayStart)
            ->count();

        $todayRevenue = (float) Order::where('store_id', $storeId)
            ->where('status', Order::STATUS_COMPLETED)
            ->where('updated_at', '>=', $todayStart)
            ->sum('paid_amount');

        $ongoingOrders = Order::where('store_id', $storeId)
            ->whereIn('status', self::ONGOING_STATUSES)
            ->count();

        $technicianCount = Order::where('store_id', $storeId)
            ->where('technician_id', '>', 0)
            ->distinct()
            ->count('technician_id');

        $verificationCount = OrderVerification::where('verified_at', '>=', $todayStart)
            ->whereIn('order_id', function ($q) use ($storeId) {
                $q->select('id')->from('appointment_order')->where('store_id', $storeId);
            })
            ->count();

        return $this->success([
            'today_orders'       => $todayOrders,
            'today_revenue'      => $todayRevenue,
            'ongoing_orders'     => $ongoingOrders,
            'technician_count'   => $technicianCount,
            'verification_count' => $verificationCount,
        ]);
    }

    /**
     * 本店订单列表（status 筛选 + 分页）
     * GET /api/store-manager/orders?status=completed&page=1&limit=15
     */
    public function orders(Request $request): Response
    {
        $storeId = $this->storeId($request);
        if ($storeId === null) {
            return $this->error('无门店权限', 403);
        }
        $status = (string) $request->input('status', '');
        $limit = (int) $request->input('limit', 15);

        $query = Order::where('store_id', $storeId);
        if ($status !== '' && in_array($status, [
            Order::STATUS_PENDING,
            Order::STATUS_PAID,
            Order::STATUS_CONFIRMED,
            Order::STATUS_SERVING,
            Order::STATUS_COMPLETED,
            Order::STATUS_CANCELLED,
            Order::STATUS_REFUNDING,
            Order::STATUS_REFUNDED,
        ], true)) {
            $query->where('status', $status);
        }

        return $this->paginate(
            $query->orderByDesc('created_at')->paginate($limit)
        );
    }

    /**
     * 本店技师列表（含今日排班）
     * GET /api/store-manager/technicians
     * 技师口径：本店订单中出现过的技师档案（schema 无技师-门店直接关联，以订单归属为准）
     */
    public function technicians(Request $request): Response
    {
        $storeId = $this->storeId($request);
        if ($storeId === null) {
            return $this->error('无门店权限', 403);
        }

        $technicianUserIds = Order::where('store_id', $storeId)
            ->where('technician_id', '>', 0)
            ->distinct()
            ->pluck('technician_id')
            ->all();

        if (!$technicianUserIds) {
            return $this->success([]);
        }

        $profiles = TechnicianProfile::whereIn('user_id', $technicianUserIds)
            ->orderByDesc('id')
            ->get();

        $profileIds = $profiles->pluck('id')->all();
        $schedules = TechnicianSchedule::whereIn('technician_id', $profileIds)
            ->where('date', date('Y-m-d'))
            ->get()
            ->keyBy('technician_id');

        $list = $profiles->map(function (TechnicianProfile $profile) use ($schedules) {
            $schedule = $schedules->get($profile->id);
            return [
                'id'             => $profile->id,
                'user_id'        => $profile->user_id,
                'real_name'      => $profile->real_name,
                'avatar'         => $profile->avatar,
                'rating'         => (float) $profile->rating,
                'status'         => $profile->status,
                'today_schedule' => $schedule ? $schedule->toArray() : null,
            ];
        });

        return $this->success($list);
    }

    /**
     * 本店营收统计（近 7 天按日聚合）
     * GET /api/store-manager/revenue
     * 输出含今天在内的 7 天，无数据日期补零
     */
    public function revenue(Request $request): Response
    {
        $storeId = $this->storeId($request);
        if ($storeId === null) {
            return $this->error('无门店权限', 403);
        }

        $startDate = date('Y-m-d', strtotime('-6 days'));

        $rows = Db::table('appointment_order')
            ->selectRaw('DATE(updated_at) AS date, COUNT(*) AS order_count, COALESCE(SUM(paid_amount), 0) AS revenue')
            ->where('store_id', $storeId)
            ->where('status', Order::STATUS_COMPLETED)
            ->where('updated_at', '>=', $startDate . ' 00:00:00')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $row = $rows->get($date);
            $days[] = [
                'date'        => $date,
                'order_count' => (int) ($row->order_count ?? 0),
                'revenue'     => (float) ($row->revenue ?? 0),
            ];
        }

        return $this->success($days);
    }
}
