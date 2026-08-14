<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\order\v1\controller;

use app\common\BaseController;
use app\common\TierRatingService;
use app\model\Notification;
use app\model\Order;
use app\model\OrderReview;
use app\model\TechnicianProfile;
use support\Log;
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
        $existing = OrderReview::findByOrderId((string) $orderId);
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

        // 评价写入后懒判定技师等级（幂等：等级未变化不写日志不发通知）
        try {
            TierRatingService::evaluate((string) $order->technician_id);
        } catch (\Throwable $e) {
            Log::warning('[ReviewController] tier evaluate failed: ' . $e->getMessage());
        }

        return $this->success($review, '评价成功');
    }

    /**
     * 追评已完成的订单评价（只可追评一次）
     * POST /api/order/review/{order_id}/append
     *
     * body: { content: string(必填), images?: string 逗号分隔图片地址 }
     */
    public function append(Request $request, string $order_id)
    {
        $userId = $request->user_id;

        $orderId = $this->decodeId($order_id);
        if (!$orderId) {
            return $this->error('订单ID无效');
        }

        $review = OrderReview::findByOrderId((string) $orderId);
        // 评价不存在/非本人统一 404，避免泄露评价是否存在
        if (!$review || (string) $review->user_id !== (string) $userId) {
            return $this->error('评价不存在', 404);
        }

        $order = Order::find($orderId);
        if (!$order || $order->status !== Order::STATUS_COMPLETED) {
            return $this->error('仅可追评已完成的订单', 422);
        }

        // 只可追评一次
        if (!empty($review->append_content) || $review->append_at !== null) {
            return $this->error('该评价已追评，不可重复追评', 422);
        }

        $content = trim((string) $request->input('content', ''));
        if ($content === '') {
            return $this->error('追评内容不能为空', 422);
        }

        $appendImages = [];
        $images = $request->input('images', '');
        if (is_string($images) && trim($images) !== '') {
            $appendImages = array_values(array_filter(array_map(
                'trim',
                explode(',', $images)
            )));
        }

        $review->append_content = $content;
        $review->append_images = $appendImages ?: null;
        $review->append_at = now();
        $review->save();

        $this->notifyTechnician($review);

        return $this->success($review, '追评成功');
    }

    /**
     * 站内通知技师（type='review_append'，非阻塞：失败仅记日志，不影响追评主流程）
     */
    private function notifyTechnician(OrderReview $review): void
    {
        try {
            $profile = TechnicianProfile::find($review->technician_id);
            if (!$profile) {
                return;
            }
            Notification::create([
                'id'       => \support\Model::generateId(),
                'user_id'  => $profile->user_id,
                'type'     => 'review_append',
                'title'    => '用户追评了您的服务',
                'content'  => '追评内容：' . $review->append_content,
                'order_id' => $review->order_id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[ReviewController] notify technician failed: ' . $e->getMessage());
        }
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
