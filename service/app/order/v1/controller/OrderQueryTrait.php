<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\order\v1\controller;

use app\model\Order;
use app\model\OrderRefund;
use Webman\Http\Request;

/**
 * 订单查询（列表/详情/物流 + 响应组装辅助）
 */
trait OrderQueryTrait
{
    /**
     * 用户订单列表
     * GET /api/order/list
     */
    public function index(Request $request)
    {
        $userId = $request->user_id;
        $status = $request->input('status', '');

        $query = Order::where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        if (!empty($status)) {
            $query->where('status', $status);
        }

        $perPage = (int)$request->input('per_page', 15);
        $paginator = $query->paginate($perPage);

        // 退款信息预取（N+1 防护）：按订单批量取最近一条退款记录
        $orders = $paginator->getCollection();
        if ($orders->isNotEmpty()) {
            $refunds = OrderRefund::whereIn('order_id', $orders->pluck('id'))
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy('order_id')
                ->map->first();
            foreach ($orders as $order) {
                $this->appendRefundInfo($order, $refunds->get($order->id));
            }
        }

        return $this->paginate($paginator);
    }

    /**
     * 订单详情
     * GET /api/order/detail/{id}
     */
    public function show(Request $request, string $id)
    {
        $id = $this->decodeId((string)$id);
        if ($id === null) {
            return $this->error('订单不存在', 404);
        }
        $userId = $request->user_id;

        $order = Order::with(['items', 'payment', 'technician', 'store', 'verification', 'review'])
            ->where('user_id', $userId)
            ->where('id', $id)
            ->first();

        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        // 追加退款比例信息
        $order->refund_ratio = $order->calcRefundRatio();
        $order->is_refundable = $order->isRefundable();

        // 追加退款信息（单订单退款取最近一条）
        $refund = OrderRefund::where('order_id', $order->id)
            ->orderBy('created_at', 'desc')
            ->first();
        $this->appendRefundInfo($order, $refund);

        return $this->success($order);
    }

    /**
     * 物流详情
     * GET /api/order/logistics/{id}
     *
     * 仅本人商品订单且已录入物流信息时返回；非本人订单 / 非商品订单 /
     * 未录入物流信息一律 404。物流信息由 admin 发货接口写入 order.remark：
     * {shipping_company, tracking_no, shipped_at}，无独立轨迹明细表。
     */
    public function logistics(Request $request, string $id)
    {
        $id = $this->decodeId((string) $id);
        if ($id === null) {
            return $this->error('订单不存在', 404);
        }
        $userId = $request->user_id;

        $order = Order::with('items')
            ->where('user_id', $userId)
            ->where('id', $id)
            ->first();

        if (!$order) {
            return $this->error('订单不存在', 404);
        }
        if ($order->order_type !== Order::ORDER_TYPE_PRODUCT) {
            return $this->error('该订单暂无物流信息', 404);
        }

        $ship = $this->parseShippingInfo($order);
        if ($ship === null) {
            return $this->error('该订单暂无物流信息', 404);
        }

        return $this->success([
            'order_no'         => $order->order_no,
            'status'           => $order->status,
            'items'            => $order->items
                ->map(fn ($item) => [
                    'name'        => $item->name,
                    'cover_image' => $item->cover_image,
                    'quantity'    => $item->quantity,
                    'price'       => $item->price,
                    'spec_info'   => $item->spec_info,
                ])
                ->values()
                ->all(),
            'receiver'         => $this->parseReceiver($order),
            'shipping_company' => $ship['shipping_company'],
            'tracking_no'      => $ship['tracking_no'],
            'shipped_at'       => $ship['shipped_at'],
            'traces'           => [],
        ]);
    }

    /**
     * 从 order.remark 解析物流信息，缺失任一关键字段视为未发货
     */
    private function parseShippingInfo(Order $order): ?array
    {
        $remark = json_decode((string) $order->remark, true);
        if (!is_array($remark)) {
            return null;
        }
        $ship = [];
        foreach (['shipping_company', 'tracking_no', 'shipped_at'] as $key) {
            $value = $remark[$key] ?? null;
            if ($value === null || $value === '') {
                return null;
            }
            $ship[$key] = (string) $value;
        }
        return $ship;
    }

    /**
     * 收货信息：当前下单流程未快照收货地址，remark 中无则返回 null
     * （预留 receiver_* 键，后续写入即可透出）
     */
    private function parseReceiver(Order $order): ?array
    {
        $remark = json_decode((string) $order->remark, true);
        if (!is_array($remark)) {
            return null;
        }
        $receiver = [];
        foreach (['receiver_name', 'receiver_phone', 'receiver_address'] as $key) {
            $value = $remark[$key] ?? null;
            if ($value === null || $value === '') {
                return null;
            }
            $receiver[$key] = $key === 'receiver_phone'
                ? $this->maskPhone((string) $value)
                : (string) $value;
        }
        return $receiver;
    }

    /**
     * 手机号脱敏：138****1234
     */
    private function maskPhone(string $phone): string
    {
        if (strlen($phone) < 7) {
            return $phone;
        }
        return substr($phone, 0, 3) . '****' . substr($phone, -4);
    }

    /**
     * 追加退款信息到订单响应（show/index 共用，契约字段：refund_status/refund_amount/refunded_at）
     *
     * 无退款记录时三个字段均为 null，保证响应形状一致。
     */
    private function appendRefundInfo(Order $order, ?OrderRefund $refund): void
    {
        $order->refund_status = $refund ? $refund->status : null;
        $order->refund_amount = $refund ? (float) $refund->amount : null;
        $order->refunded_at   = $refund && $refund->refunded_at ? $refund->refunded_at->format('Y-m-d H:i:s') : null;
    }
}
