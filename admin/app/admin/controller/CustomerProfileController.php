<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\common\Money;
use app\model\User;
use app\model\Order;
use app\model\OrderItem;
use app\model\OrderReview;
use app\model\UserMemberCard;
use app\model\UserPoints;
use support\Db;
use support\Request;
use support\Response;

class CustomerProfileController extends BaseController
{
    /**
     * 客户 360 画像
     */
    public function show(Request $request, string $hashid): Response
    {
        $userId = $this->decodeId($hashid);
        $user   = User::find($userId);
        if (!$user) {
            return $this->fail('用户不存在', 404);
        }

        // 基础信息
        $basicInfo = $user->toArray();
        unset($basicInfo['password'], $basicInfo['wx_openid'], $basicInfo['wx_unionid']);
        if (!empty($basicInfo['phone'])) {
            $basicInfo['phone'] = preg_replace('/^(\d{3})\d+(\d{4})$/', '$1****$2', $basicInfo['phone']);
        }

        // 订单统计
        $totalOrders = Order::where('user_id', $userId)->count();
        $totalSpent  = (float) Order::where('user_id', $userId)
            ->whereIn('status', ['paid', 'confirmed', 'serving', 'completed'])
            ->sum('paid_amount');
        $avgOrderValue = $totalOrders > 0 ? (float) Money::round(Money::div((string) $totalSpent, (string) $totalOrders), 2) : 0;

        // 最常购买的服务 Top 5
        $topServices = OrderItem::whereIn('order_id', function ($q) use ($userId) {
            $q->select('id')->from('appointment_order')->where('user_id', $userId);
        })
            ->where('target_type', 'service')
            ->selectRaw('target_id, name, SUM(quantity) as total_qty, SUM(price * quantity) as total_amount')
            ->groupBy('target_id', 'name')
            ->orderBy('total_qty', 'desc')
            ->limit(5)
            ->get()
            ->toArray();

        // 常选技师 Top 3
        $topTechnicians = OrderReview::where('user_id', $userId)
            ->selectRaw('technician_id, COUNT(*) as order_count, AVG(rating) as avg_rating')
            ->groupBy('technician_id')
            ->orderBy('order_count', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($row) {
                $tech = \app\model\TechnicianProfile::find($row->technician_id);
                return [
                    'technician_id'   => (int) $row->technician_id,
                    'technician_name' => $tech ? mb_substr($tech->real_name, 0, 1) . '**' : '未知',
                    'order_count'     => $row->order_count,
                    'avg_rating'      => round((float) $row->avg_rating, 1),
                ];
            })
            ->toArray();

        // 偏好时段（下单高峰时段）
        $peakHours = Order::where('user_id', $userId)
            ->whereNotNull('service_time')
            ->selectRaw('HOUR(service_time) as hour, COUNT(*) as count')
            ->groupBy(DB::raw('HOUR(service_time)'))
            ->orderBy('count', 'desc')
            ->limit(3)
            ->get()
            ->map(fn($r) => [
                'hour'  => sprintf('%02d:00-%02d:00', $r->hour, ($r->hour + 1) % 24),
                'count' => $r->count,
            ])
            ->toArray();

        // 会员卡状态
        $memberCards = UserMemberCard::where('user_id', $userId)
            ->with('card')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($mc) => $mc->toArray())
            ->toArray();

        $activeMemberCards = UserMemberCard::where('user_id', $userId)
            ->where('status', 'active')
            ->count();

        // 积分余额
        $pointsBalance = (int) UserPoints::where('user_id', $userId)
            ->sum('points');

        // 最近订单
        $lastOrder = Order::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->first();
        $lastOrderDate = $lastOrder ? $lastOrder->created_at : null;

        // 回头客判断
        $isReturningCustomer = Order::where('user_id', $userId)->count() > 1;

        return $this->success([
            'user_id'              => (int) $userId,
            'basic'                => $basicInfo,
            'total_orders'         => $totalOrders,
            'total_spent'          => round($totalSpent, 2),
            'avg_order_value'      => $avgOrderValue,
            'top_services'         => $topServices,
            'top_technicians'      => $topTechnicians,
            'peak_hours'           => $peakHours,
            'member_cards'         => $memberCards,
            'active_member_cards'  => $activeMemberCards,
            'points_balance'       => $pointsBalance,
            'last_order_date'      => $lastOrderDate ? $lastOrderDate->format('Y-m-d H:i:s') : null,
            'is_returning'         => $isReturningCustomer,
        ]);
    }

    /**
     * 客户分群
     * - high_value: 累计消费 > threshold
     * - regular: 月均订单 > 3
     * - dormant: 超30天未下单
     * - new: 注册 < 7天
     */
    public function segments(Request $request): Response
    {
        $threshold = (float) $request->input('threshold', 5000);
        $now       = date('Y-m-d H:i:s');
        $monthAgo  = date('Y-m-d H:i:s', strtotime('-30 days'));
        $weekAgo   = date('Y-m-d H:i:s', strtotime('-7 days'));

        // 高价值客户
        $highValueUserIds = Order::whereIn('status', ['paid', 'confirmed', 'serving', 'completed'])
            ->selectRaw('user_id, SUM(paid_amount) as total_spent')
            ->groupBy('user_id')
            ->having('total_spent', '>=', $threshold)
            ->pluck('user_id')
            ->toArray();
        $highValue = User::whereIn('id', $highValueUserIds)
            ->where('status', 1)
            ->count();

        // 活跃客户（近30天订单>3）
        $regularUserIds = Order::where('created_at', '>=', $monthAgo)
            ->selectRaw('user_id, COUNT(*) as order_count')
            ->groupBy('user_id')
            ->having('order_count', '>', 3)
            ->pluck('user_id')
            ->toArray();
        $regular = User::whereIn('id', $regularUserIds)
            ->where('status', 1)
            ->count();

        // 沉睡客户（超30天无订单）
        $activeUserIds = Order::where('created_at', '>=', $monthAgo)
            ->pluck('user_id')
            ->unique()
            ->toArray();
        $dormant = User::where('status', 1)
            ->where('user_type', 'customer')
            ->whereNotIn('id', $activeUserIds)
            ->where('created_at', '<', $monthAgo)
            ->count();

        // 新客（注册<7天）
        $newCustomers = User::where('status', 1)
            ->where('user_type', 'customer')
            ->where('created_at', '>=', $weekAgo)
            ->count();

        return $this->success([
            'segments' => [
                [
                    'name'        => '高价值客户',
                    'key'         => 'high_value',
                    'description' => "累计消费 >= ¥{$threshold}",
                    'count'       => $highValue,
                ],
                [
                    'name'        => '活跃客户',
                    'key'         => 'regular',
                    'description' => '近30天订单 > 3',
                    'count'       => $regular,
                ],
                [
                    'name'        => '沉睡客户',
                    'key'         => 'dormant',
                    'description' => '超30天未下单',
                    'count'       => $dormant,
                ],
                [
                    'name'        => '新客户',
                    'key'         => 'new',
                    'description' => '注册 < 7天',
                    'count'       => $newCustomers,
                ],
            ],
            'total_customers' => User::where('status', 1)
                ->where('user_type', 'customer')
                ->count(),
        ]);
    }
}
