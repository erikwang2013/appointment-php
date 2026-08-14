<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\technician\v1\controller;

use app\common\BaseController;
use app\model\Notification;
use app\model\OrderReview;
use support\Log;
use Webman\Http\Request;

/**
 * 技师评价控制器
 * 技师回复用户评价
 */
class ReviewController extends BaseController
{
    /**
     * 技师回复评价
     * POST /api/technician/review/reply/{order_id}
     *
     * @param string $order_id 订单ID (hashid)
     */
    public function reply(Request $request, string $order_id)
    {
        $technicianId = (string) $request->technician_id;

        $orderId = $this->decodeId($order_id);
        if (!$orderId) {
            return $this->error('订单ID无效');
        }

        $review = OrderReview::findByOrderId((string) $orderId);
        // 评价不存在/非本人统一 404，避免泄露评价是否存在
        if (!$review || (string) $review->technician_id !== $technicianId) {
            return $this->error('评价不存在', 404);
        }

        // 回复幂等：已有回复直接拒绝，不做覆盖
        if (!empty($review->reply)) {
            return $this->error('已回复过此评价，不可重复回复', 422);
        }

        $content = trim($request->input('reply', ''));
        if (empty($content)) {
            return $this->error('回复内容不能为空', 422);
        }

        $review->reply = $content;
        $review->replied_at = now();
        $review->save();

        $this->notifyUser($review);

        return $this->success($review, '回复成功');
    }

    /**
     * 站内通知用户（type='review_reply'，非阻塞：失败仅记日志，不影响回复主流程）
     */
    private function notifyUser(OrderReview $review): void
    {
        try {
            Notification::create([
                'id'       => \support\Model::generateId(),
                'user_id'  => $review->user_id,
                'type'     => 'review_reply',
                'title'    => '技师回复了您的评价',
                'content'  => '技师回复：' . $review->reply,
                'order_id' => $review->order_id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[ReviewController] notify user failed: ' . $e->getMessage());
        }
    }
}
