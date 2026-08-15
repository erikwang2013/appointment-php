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
     * 联 erik_order（订单号）、erik_user（技师昵称/姓名），
     * 支持 status / order_no / technician_name 筛选，ID hashid 编码输出。
     */
    public function index(Request $request): Response
    {
        $page           = (int) $request->input('page', 1);
        $limit          = (int) $request->input('limit', 15);
        $status         = $request->input('status', '');
        $orderNo        = $request->input('order_no', '');
        $technicianName = $request->input('technician_name', '');

        $query = ProfitSharing::leftJoin('erik_order', 'erik_profit_sharing.order_id', '=', 'erik_order.id')
            ->leftJoin('erik_user', 'erik_profit_sharing.user_id', '=', 'erik_user.id')
            ->select(
                'erik_profit_sharing.*',
                'erik_order.order_no',
                'erik_user.nickname',
                'erik_user.real_name'
            );

        if ($status) {
            $query->where('erik_profit_sharing.status', $status);
        }
        if ($orderNo) {
            $query->where('erik_order.order_no', 'like', "%{$orderNo}%");
        }
        if ($technicianName) {
            $query->where(function ($q) use ($technicianName) {
                $q->where('erik_user.nickname', 'like', "%{$technicianName}%")
                    ->orWhere('erik_user.real_name', 'like', "%{$technicianName}%");
            });
        }

        $total = $query->count();
        $list  = $query->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderByDesc('erik_profit_sharing.id')
            ->get()
            ->map(fn ($row) => $this->encodeIds($row->toArray(), ['id', 'order_id', 'user_id']))
            ->values();

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }
}
