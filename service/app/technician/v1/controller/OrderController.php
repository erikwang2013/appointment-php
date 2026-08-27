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

        // 服务字段（service_id/service_name/price）在 appointment_order_item 明细表中，
        // 通过子查询取每个订单最早的一条明细（MIN(id)，snowflake 按时间递增）关联；
        // appointment_order 表无 service_date 列，由 service_time 推导日期
        // 子查询限定在当前技师的订单范围内（走 appointment_order_item.idx_order_id），
        // 避免对全表做 MIN(id) GROUP BY 扫描
        $firstItem = Db::table('appointment_order_item')
            ->select('order_id')
            ->selectRaw('MIN(id) AS first_item_id')
            ->whereIn('order_id', function ($q) use ($technicianId) {
                $q->select('id')
                    ->from('appointment_order')
                    ->where('technician_id', $technicianId);
            })
            ->groupBy('order_id');

        $query = Db::table('appointment_order')
            ->select([
                'appointment_order.id',
                'appointment_order.order_no',
                'appointment_order.user_id',
                'item.target_id as service_id',
                'item.name as service_name',
                'item.price',
                'appointment_order.status',
                'appointment_order.service_time',
                'appointment_order.created_at',
                'appointment_user.nickname',
                'appointment_user.avatar',
            ])
            ->selectRaw("DATE_FORMAT(appointment_order.service_time, '%Y-%m-%d') AS service_date")
            ->leftJoin('appointment_user', 'appointment_order.user_id', '=', 'appointment_user.id')
            ->leftJoinSub($firstItem, 'first_item', function ($join) {
                $join->on('first_item.order_id', '=', 'appointment_order.id');
            })
            ->leftJoin('appointment_order_item as item', 'item.id', '=', 'first_item.first_item_id')
            ->where('appointment_order.technician_id', $technicianId);

        if ($status) {
            $query->where('appointment_order.status', $status);
        }

        $query->orderBy('appointment_order.id', 'desc');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return $this->paginate($paginator);
    }
}
