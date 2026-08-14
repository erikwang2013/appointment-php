<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\Order;
use app\model\OrderPayment;
use support\Redis;
use support\Request;
use support\Response;

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
        $storeId     = $request->input('store_id', '');
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
        if ($storeId !== '') {
            $query->where('store_id', $this->decodeId($storeId));
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
     *
     * B5: 与用户侧 doCancel 同款互斥——先拿 order_lock:{id}（NX EX 35s + token 校验释放），
     * 锁内重查订单状态再取消，防与支付回调/用户取消/自动取消并发竞态。
     */
    public function cancel(Request $request, string $hashid): Response
    {

        $id    = $this->decodeId($hashid);
        $order = Order::find($id);
        if (!$order) {
            return $this->fail('订单不存在', 404);
        }

        $lockKey    = 'order_lock:' . $order->id;
        $lockToken  = $this->acquireLock($lockKey);
        if ($lockToken === null) {
            return $this->fail('操作处理中，请稍后再试', 422);
        }

        try {
            // 锁内重查订单并校验状态（防并发：支付回调/自动取消可能刚改变状态）
            $order = Order::find($id);
            if (!$order) {
                return $this->fail('订单不存在', 404);
            }

            if ($order->status === 'cancelled' || $order->status === 'refunded') {
                return $this->fail('订单已取消或已退款', 422);
            }

            // M5/B5: 已支付及以上状态拒绝直接取消（资金安全：直接置 cancelled 会导致已扣款无退款路径）。
            // FREE 单（全额优惠零元）已 paid 同样拒绝，须走用户侧退款流程。
            if (in_array($order->status, ['paid', 'confirmed', 'serving', 'completed', 'refunding'], true)) {
                return $this->fail('已支付订单请走退款流程', 422);
            }

            $reason = $request->input('cancel_reason', '平台取消');
            $order->status        = 'cancelled';
            $order->cancel_reason = $reason;
            $order->cancel_at     = date('Y-m-d H:i:s');
            $order->save();

            return $this->success($this->encodeIds($order->toArray()), '订单已取消');
        } finally {
            $this->releaseLock($lockKey, $lockToken);
        }
    }

    /**
     * 获取 Redis 分布式锁（NX + 随机 token，与 service 端 OrderController 同款封装）
     *
     * @param string $key           锁 key
     * @param int    $expireSeconds 过期秒数（默认 35s，覆盖微信 HTTP 30s 超时）
     * @return string|null 持有 token，拿不到锁返回 null
     */
    private function acquireLock(string $key, int $expireSeconds = 35): ?string
    {
        $token = bin2hex(random_bytes(16));
        $ok = Redis::connection()->set($key, $token, 'EX', $expireSeconds, 'NX');
        return $ok ? $token : null;
    }

    /**
     * 释放 Redis 分布式锁（仅当持有者 token 匹配时删除）
     */
    private function releaseLock(string $key, ?string $token): void
    {
        if ($token === null) {
            return;
        }
        $redis = Redis::connection();
        if ((string) ($redis->get($key) ?? '') === $token) {
            $redis->del($key);
        }
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
