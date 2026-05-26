<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\Order;
use app\model\OrderReview;
use app\model\OrderRefund;
use support\Request;
use support\Response;
use Erikwang2013\PosterPhp\Poster;

class MallOrderController extends BaseController
{
    /**
     * 商品订单列表
     * 搜索: order_no / uid / status / date
     */
    public function index(Request $request): Response
    {
        $page         = (int) $request->input('page', 1);
        $limit        = (int) $request->input('limit', 15);
        $orderNo      = $request->input('order_no', '');
        $uid          = $request->input('uid', '');
        $status       = $request->input('status', '');
        $dateStart    = $request->input('date_start', '');
        $dateEnd      = $request->input('date_end', '');

        $query = Order::where('order_type', 'product')->with(['user', 'items', 'payment']);

        if ($orderNo) {
            $query->where('order_no', 'like', "%{$orderNo}%");
        }
        if ($uid) {
            $query->where('user_id', $uid);
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($dateStart) {
            $query->whereDate('created_at', '>=', $dateStart);
        }
        if ($dateEnd) {
            $query->whereDate('created_at', '<=', $dateEnd);
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
     * 订单详情（含商品项 + 配送信息）
     */
    public function show(Request $request, string $hashid): Response
    {
        $id    = $this->decodeId($hashid);
        $order = Order::with(['user', 'items', 'payment', 'review', 'verification'])->find($id);
        if (!$order) {
            return $this->fail('订单不存在', 404);
        }

        return $this->success($this->encodeIds($order->toArray()));
    }

    /**
     * 录入配送信息
     */
    public function ship(Request $request, string $hashid): Response
    {
        $id    = $this->decodeId($hashid);
        $order = Order::find($id);
        if (!$order) {
            return $this->fail('订单不存在', 404);
        }

        if ($order->order_type !== 'product') {
            return $this->fail('仅商品订单支持发货操作', 422);
        }

        $company    = $request->input('shipping_company', '');
        $trackingNo = $request->input('tracking_no', '');

        if (empty($company) || empty($trackingNo)) {
            return $this->fail('快递公司和运单号不能为空', 422);
        }

        // 将配送信息存入 remark 或 verification 表
        $order->remark = json_encode([
            'shipping_company' => $company,
            'tracking_no'      => $trackingNo,
            'shipped_at'       => date('Y-m-d H:i:s'),
        ], JSON_UNESCAPED_UNICODE);
        $order->status = 'serving'; // 配送中
        $order->save();

        return $this->success($this->encodeIds($order->toArray()), '发货成功');
    }

    /**
     * 售后列表（退款/退货申请）
     */
    public function afterSales(Request $request): Response
    {
        $page    = (int) $request->input('page', 1);
        $limit   = (int) $request->input('limit', 15);
        $status  = $request->input('status', '');

        $query = OrderRefund::with(['order', 'order.user', 'payment']);
        if ($status) {
            $query->where('status', $status);
        }

        $total = $query->count();
        $list  = $query->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->orderBy('id', 'desc')
                       ->get()
                       ->map(fn($r) => $this->encodeIds($r->toArray()));

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 评价审核
     */
    public function reviewAudit(Request $request): Response
    {
        Poster::verify($request);

        $reviewId = $request->input('review_id', '');
        $action   = $request->input('action', ''); // approve / reject

        if (empty($reviewId)) {
            return $this->fail('评价ID不能为空', 422);
        }
        if (!in_array($action, ['approve', 'reject'], true)) {
            return $this->fail('操作类型无效', 422);
        }

        try {
            $id     = $this->decodeId($reviewId);
        } catch (\InvalidArgumentException $e) {
            return $this->fail('无效的评价ID', 422);
        }

        $review = OrderReview::find($id);
        if (!$review) {
            return $this->fail('评价不存在', 404);
        }

        $review->status = $action === 'approve'
            ? OrderReview::STATUS_VISIBLE
            : OrderReview::STATUS_HIDDEN;
        $review->save();

        return $this->success(
            $this->encodeIds($review->toArray()),
            $action === 'approve' ? '审核通过' : '已驳回'
        );
    }
}
