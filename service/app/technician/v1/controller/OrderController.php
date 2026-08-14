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

        // 服务字段（service_id/service_name/price）在 erik_order_item 明细表中，
        // 通过子查询取每个订单最早的一条明细（MIN(id)，snowflake 按时间递增）关联；
        // erik_order 表无 service_date 列，由 service_time 推导日期
        $firstItem = Db::table('erik_order_item')
            ->select('order_id')
            ->selectRaw('MIN(id) AS first_item_id')
            ->groupBy('order_id');

        $query = Db::table('erik_order')
            ->select([
                'erik_order.id',
                'erik_order.order_no',
                'erik_order.user_id',
                'item.target_id as service_id',
                'item.name as service_name',
                'item.price',
                'erik_order.status',
                'erik_order.service_time',
                'erik_order.created_at',
                'erik_user.nickname',
                'erik_user.avatar',
            ])
            ->selectRaw("DATE_FORMAT(erik_order.service_time, '%Y-%m-%d') AS service_date")
            ->leftJoin('erik_user', 'erik_order.user_id', '=', 'erik_user.id')
            ->leftJoinSub($firstItem, 'first_item', function ($join) {
                $join->on('first_item.order_id', '=', 'erik_order.id');
            })
            ->leftJoin('erik_order_item as item', 'item.id', '=', 'first_item.first_item_id')
            ->where('erik_order.technician_id', $technicianId);

        if ($status) {
            $query->where('erik_order.status', $status);
        }

        $query->orderBy('erik_order.id', 'desc');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return $this->paginate($paginator);
    }
}
