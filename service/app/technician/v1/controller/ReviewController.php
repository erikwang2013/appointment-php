<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\technician\v1\controller;

use app\common\BaseController;
use app\model\OrderReview;
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
        $technicianId = $request->technician_id;

        $orderId = $this->decodeId($order_id);
        if (!$orderId) {
            return $this->error('订单ID无效');
        }

        $review = OrderReview::where('order_id', $orderId)->first();
        if (!$review) {
            return $this->error('评价不存在', 404);
        }

        if ($review->technician_id !== $technicianId) {
            return $this->error('无权回复此评价', 403);
        }

        if (!empty($review->reply)) {
            return $this->error('已回复过此评价，不可重复回复');
        }

        $content = trim($request->input('reply', ''));
        if (empty($content)) {
            return $this->error('回复内容不能为空');
        }

        $review->reply = $content;
        $review->replied_at = now();
        $review->save();

        return $this->success($review, '回复成功');
    }
}
