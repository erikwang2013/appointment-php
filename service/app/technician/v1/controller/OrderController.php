<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\technician\v1\controller;

use app\common\BaseController;
use support\Db;
use Webman\Http\Request;

/**
 * 技师订单控制器
 * 查看属于当前技师的服务订单
 */
class OrderController extends BaseController
{
    /**
     * 获取技师订单列表
     * GET /api/technician/orders
     */
    public function index(Request $request)
    {
        $technicianId = $request->technician_id;
        $status = $request->input('status', '');
        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 15);

        $query = Db::table('erik_order')
            ->select([
                'erik_order.id',
                'erik_order.order_no',
                'erik_order.user_id',
                'erik_order.service_id',
                'erik_order.service_name',
                'erik_order.price',
                'erik_order.status',
                'erik_order.service_time',
                'erik_order.service_date',
                'erik_order.created_at',
                'erik_user.nickname',
                'erik_user.avatar',
            ])
            ->leftJoin('erik_user', 'erik_order.user_id', '=', 'erik_user.id')
            ->where('erik_order.technician_id', $technicianId);

        if ($status) {
            $query->where('erik_order.status', $status);
        }

        $query->orderBy('erik_order.id', 'desc');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return $this->paginate($paginator);
    }
}
