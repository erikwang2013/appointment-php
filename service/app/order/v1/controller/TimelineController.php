<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\order\v1\controller;

use app\common\BaseController;
use app\model\Order;
use app\model\OrderStatusLog;
use Webman\Http\Request;

/**
 * 订单状态时间线控制器
 */
class TimelineController extends BaseController
{
    /**
     * 订单状态时间线（倒序：最新在前，仅本人可见）
     * GET /api/order/{id}/timeline
     */
    public function show(Request $request, string $id)
    {
        $id = $this->decodeId((string)$id);
        if ($id === null) {
            return $this->error('订单不存在', 404);
        }

        $order = Order::where('user_id', $request->user_id)
            ->where('id', $id)
            ->first();
        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        $timeline = OrderStatusLog::where('order_id', $id)
            ->orderByDesc('id')
            ->get();

        return $this->success($this->encodeIds($timeline->toArray()));
    }
}
