<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\Order;
use app\model\OrderPayment;
use support\Request;
use support\Response;
use Erikwang2013\PosterPhp\Poster;

class AppointmentOrderController extends BaseController
{
    /**
     * 预约订单列表
     * 搜索: order_no / payment_no / type / uid / status / technician / date
     */
    public function index(Request $request): Response
    {
        $page        = (int) $request->input('page', 1);
        $limit       = (int) $request->input('limit', 15);
        $orderNo     = $request->input('order_no', '');
        $paymentNo   = $request->input('payment_no', '');
        $orderType   = $request->input('order_type', '');
        $uid         = $request->input('uid', '');
        $status      = $request->input('status', '');
        $technicianId = $request->input('technician_id', '');
        $dateStart   = $request->input('date_start', '');
        $dateEnd     = $request->input('date_end', '');

        $query = Order::with(['user', 'technician', 'store', 'items', 'payment']);

        if ($orderNo) {
            $query->where('order_no', 'like', "%{$orderNo}%");
        }
        if ($uid) {
            $query->where('user_id', $uid);
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($orderType) {
            $query->where('order_type', $orderType);
        }
        if ($technicianId) {
            $query->where('technician_id', $technicianId);
        }
        if ($dateStart) {
            $query->whereDate('created_at', '>=', $dateStart);
        }
        if ($dateEnd) {
            $query->whereDate('created_at', '<=', $dateEnd);
        }
        if ($paymentNo) {
            $query->whereHas('payment', function ($q) use ($paymentNo) {
                $q->where('payment_no', 'like', "%{$paymentNo}%");
            });
        }

        $total = $query->count();
        $list  = $query->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->orderBy('id', 'desc')
                       ->get()
                       ->map(fn($o) => $this->encodeIds($o->toArray()));

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 订单详情（完整）
     */
    public function show(Request $request, string $hashid): Response
    {
        $id    = $this->decodeId($hashid);
        $order = Order::with([
            'user', 'technician', 'store',
            'items', 'payment', 'review', 'verification',
        ])->find($id);
        if (!$order) {
            return $this->fail('订单不存在', 404);
        }

        return $this->success($this->encodeIds($order->toArray()));
    }

    /**
     * 平台取消订单
     */
    public function cancel(Request $request, string $hashid): Response
    {
        Poster::verify($request);

        $id    = $this->decodeId($hashid);
        $order = Order::find($id);
        if (!$order) {
            return $this->fail('订单不存在', 404);
        }

        if ($order->status === 'cancelled' || $order->status === 'refunded') {
            return $this->fail('订单已取消或已退款', 422);
        }

        $reason = $request->input('cancel_reason', '平台取消');
        $order->status        = 'cancelled';
        $order->cancel_reason = $reason;
        $order->cancel_at     = date('Y-m-d H:i:s');
        $order->save();

        return $this->success($this->encodeIds($order->toArray()), '订单已取消');
    }

    /**
     * 确认订单完成
     */
    public function complete(Request $request, string $hashid): Response
    {
        $id    = $this->decodeId($hashid);
        $order = Order::find($id);
        if (!$order) {
            return $this->fail('订单不存在', 404);
        }

        if (!in_array($order->status, ['serving', 'paid', 'confirmed'], true)) {
            return $this->fail('当前状态不可完成', 422);
        }

        $order->status          = 'completed';
        $order->service_end_at   = date('Y-m-d H:i:s');
        $order->save();

        return $this->success($this->encodeIds($order->toArray()), '订单已完成');
    }
}
