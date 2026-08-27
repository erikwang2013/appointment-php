<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\admin\controller;

use app\model\ProfitSharing;
use support\Request;
use support\Response;

/**
 * 微信分账记录管理（列表 / 状态筛选 / 订单号 / 技师名）
 */
class ProfitSharingController extends BaseController
{
    /**
     * 分账记录列表
     *
     * 联 appointment_order（订单号）、appointment_user（技师昵称/姓名），
     * 支持 status / order_no / technician_name 筛选，ID hashid 编码输出。
     */
    public function index(Request $request): Response
    {
        $page           = (int) $request->input('page', 1);
        $limit          = (int) $request->input('limit', 15);
        $status         = $request->input('status', '');
        $orderNo        = $request->input('order_no', '');
        $technicianName = $request->input('technician_name', '');

        $query = ProfitSharing::leftJoin('appointment_order', 'appointment_profit_sharing.order_id', '=', 'appointment_order.id')
            ->leftJoin('appointment_user', 'appointment_profit_sharing.user_id', '=', 'appointment_user.id')
            ->select(
                'appointment_profit_sharing.*',
                'appointment_order.order_no',
                'appointment_user.nickname',
                'appointment_user.real_name'
            );

        if ($status) {
            $query->where('appointment_profit_sharing.status', $status);
        }
        if ($orderNo) {
            $query->where('appointment_order.order_no', 'like', "%{$orderNo}%");
        }
        if ($technicianName) {
            $query->where(function ($q) use ($technicianName) {
                $q->where('appointment_user.nickname', 'like', "%{$technicianName}%")
                    ->orWhere('appointment_user.real_name', 'like', "%{$technicianName}%");
            });
        }

        $total = $query->count();
        $list  = $query->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderByDesc('appointment_profit_sharing.id')
            ->get()
            ->map(fn ($row) => $row->toArray())
            ->values();

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }
}
