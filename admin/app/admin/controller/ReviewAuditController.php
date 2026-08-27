<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\admin\controller;

use app\model\OrderReview;
use InvalidArgumentException;
use support\Request;
use support\Response;

/**
 * 评价图片审核控制器（管理端）
 *
 * 仅面向带图评价（images 非空 JSON 数组）的审核：列表、隐藏、恢复。
 * 与通用评价管理 ReviewController（全部评价列表/删除）职责分离。
 */
class ReviewAuditController extends BaseController
{
    /**
     * 带图评价列表
     * GET /admin/review-audit?status=0&page=1&limit=15
     */
    public function index(Request $request): Response
    {
        $page   = (int) $request->input('page', 1);
        $limit  = (int) $request->input('limit', 15);
        $status = $request->input('status');

        $query = OrderReview::query()
            ->leftJoin('appointment_technician_profile as tp', 'tp.id', 'appointment_order_review.technician_id')
            ->leftJoin('appointment_user as u', 'u.id', 'appointment_order_review.user_id')
            ->whereNotNull('appointment_order_review.images')
            ->whereRaw('JSON_LENGTH(appointment_order_review.images) > 0')
            ->select('appointment_order_review.*', 'u.nickname as user_nickname', 'tp.real_name as technician_name');

        if ($status !== null && $status !== '') {
            $query->where('appointment_order_review.status', (int) $status);
        }

        $total = $query->count();
        $list  = $query->orderBy('appointment_order_review.created_at', 'desc')
                       ->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->get()
                       ->map(fn ($review) => $this->decorate($review));

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 隐藏评价（仅 visible 可隐藏）
     * POST /admin/review-audit/{id}/hide
     */
    public function hide(Request $request, string $hashid): Response
    {
        return $this->setStatus($hashid, OrderReview::STATUS_HIDDEN);
    }

    /**
     * 恢复评价（仅 hidden 可恢复）
     * POST /admin/review-audit/{id}/restore
     */
    public function restore(Request $request, string $hashid): Response
    {
        return $this->setStatus($hashid, OrderReview::STATUS_VISIBLE);
    }

    /**
     * 状态流转：hide → hidden，restore → visible；状态重复变更返回 422
     */
    private function setStatus(string $hashid, int $target): Response
    {
        try {
            $id = $this->decodeId($hashid);
        } catch (InvalidArgumentException) {
            return $this->fail('无效的ID', 422);
        }

        $review = OrderReview::find($id);
        if (!$review) {
            return $this->fail('评价不存在', 404);
        }

        if ((int) $review->status === $target) {
            return $this->fail(
                $target === OrderReview::STATUS_HIDDEN ? '评价已是隐藏状态' : '评价已是可见状态',
                422
            );
        }

        $review->status = $target;
        $review->save();

        $message = $target === OrderReview::STATUS_HIDDEN ? '评价已隐藏' : '评价已恢复可见';
        return $this->success($this->decorate($review), $message);
    }

    /**
     * 输出装饰：ID 编码为 hashid
     */
    private function decorate(OrderReview $review): array
    {
        return $review->toArray();
    }
}
