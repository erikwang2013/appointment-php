<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\order\v1\controller;

use app\common\BaseController;
use app\model\Order;
use app\model\OrderReview;
use Webman\Http\Request;

/**
 * 订单评价控制器
 * 用户提交评价、查看技师评价列表
 */
class ReviewController extends BaseController
{
    /**
     * 提交评价
     * POST /api/order/review/{order_id}
     */
    public function store(Request $request, string $order_id)
    {
        $userId = $request->user_id;

        $orderId = $this->decodeId($order_id);
        if (!$orderId) {
            return $this->error('订单ID无效');
        }

        $order = Order::where('user_id', $userId)
            ->where('id', $orderId)
            ->first();

        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        if ($order->status !== Order::STATUS_COMPLETED) {
            return $this->error('仅可评价已完成的订单');
        }

        // 检查是否已评价
        $existing = OrderReview::where('order_id', $orderId)->first();
        if ($existing) {
            return $this->error('该订单已评价');
        }

        $rating = (int)$request->input('rating', 0);
        if ($rating < 1 || $rating > 5) {
            return $this->error('评分必须在1-5之间');
        }

        $content = trim($request->input('content', ''));
        $images = $request->input('images', []);

        if (!is_array($images)) {
            $images = [];
        }

        $review = OrderReview::create([
            'id'             => OrderReview::generateId(),
            'order_id'       => $orderId,
            'user_id'        => $userId,
            'technician_id'  => $order->technician_id,
            'rating'         => $rating,
            'content'        => $content,
            'images'         => $images,
            'status'         => OrderReview::STATUS_VISIBLE,
        ]);

        return $this->success($review, '评价成功');
    }

    /**
     * 技师的评价列表（公开）
     * GET /api/order/reviews/technician/{tech_id}
     */
    public function byTechnician(Request $request, string $tech_id)
    {
        $techId = $this->decodeId($tech_id);
        if (!$techId) {
            return $this->error('技师ID无效');
        }

        $perPage = (int)$request->input('per_page', 15);
        $paginator = OrderReview::where('technician_id', $techId)
            ->where('status', OrderReview::STATUS_VISIBLE)
            ->with(['user:id,nickname,avatar'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->paginate($paginator);
    }
}
