<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\OrderReview;
use app\model\TechnicianProfile;
use support\Request;
use support\Response;

/**
 * 评价管理控制器
 *
 * 说明：OrderReview.status 为整型审核状态（0=隐藏 / 1=可见，见 OrderReview::STATUS_*），
 * 表无 deleted_at 软删字段，故删除直接删行；隐藏走 status 置位，不改动 service 端逻辑。
 */
class ReviewController extends BaseController
{
    /**
     * 评价列表
     * 筛选: rating（评分）、status（0/1）、keyword（内容关键词）
     */
    public function index(Request $request): Response
    {
        $page    = (int) $request->input('page', 1);
        $limit   = (int) $request->input('limit', 15);
        $rating  = $request->input('rating');
        $status  = $request->input('status');
        $keyword = $request->input('keyword', '');

        $query = OrderReview::with(['user', 'order.technician']);

        if ($rating !== null && $rating !== '') {
            $query->where('rating', (int) $rating);
        }
        if ($status !== null && $status !== '') {
            $query->where('status', (int) $status);
        }
        if ($keyword !== '') {
            $query->where('content', 'like', "%{$keyword}%");
        }

        $total = $query->count();
        $list  = $query->orderBy('id', 'desc')
                       ->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->get()
                       ->map(function ($review) {
                           return $this->decorate($review);
                       });

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 评价详情（含技师档案与订单关联）
     */
    public function show(Request $request, string $hashid): Response
    {
        $id = $this->decodeId($hashid);
        $review = OrderReview::with(['user', 'order.technician'])->find($id);
        if (!$review) {
            return $this->fail('评价不存在', 404);
        }
        return $this->success($this->decorate($review));
    }

    /**
     * 审核操作: show（恢复可见）/ hide（隐藏）
     */
    public function audit(Request $request, string $hashid): Response
    {
        $id = $this->decodeId($hashid);
        $review = OrderReview::find($id);
        if (!$review) {
            return $this->fail('评价不存在', 404);
        }

        $action = $request->input('action', '');
        if (!in_array($action, ['show', 'hide'], true)) {
            return $this->fail('操作类型无效，支持 show / hide', 422);
        }

        $review->status = $action === 'show'
            ? OrderReview::STATUS_VISIBLE
            : OrderReview::STATUS_HIDDEN;
        $review->save();

        return $this->success($this->decorate($review), $action === 'show' ? '已恢复可见' : '已隐藏');
    }

    /**
     * 删除评价（表无软删字段，直接删行）
     */
    public function destroy(Request $request, string $hashid): Response
    {
        $id = $this->decodeId($hashid);
        $review = OrderReview::find($id);
        if (!$review) {
            return $this->fail('评价不存在', 404);
        }
        $review->delete();
        return $this->success([], '删除成功');
    }

    /**
     * 输出装饰：hashid 编码 + 补充技师档案信息
     */
    private function decorate(OrderReview $review): array
    {
        $data = $this->encodeIds(
            $review->toArray(),
            ['id', 'order_id', 'user_id', 'technician_id']
        );

        // 技师档案（技师侧 user 关联）
        if (!empty($data['technician_id'])) {
            $techId = $this->decodeId($data['technician_id']);
            $profile = TechnicianProfile::where('user_id', $techId)->first();
            $data['technician'] = $profile
                ? $this->encodeIds($profile->toArray(), ['id', 'user_id'])
                : null;
        }

        // 订单与技师用户信息（含 order_no、服务时间）
        if (!empty($data['order'])) {
            $data['order'] = $this->encodeIds($data['order'], ['id', 'user_id', 'technician_id', 'store_id']);
            if (!empty($data['order']['technician'])) {
                $data['order']['technician'] = $this->encodeIds(
                    $data['order']['technician'],
                    ['id']
                );
            }
        }
        if (!empty($data['user'])) {
            $data['user'] = $this->encodeIds($data['user'], ['id']);
        }

        return $data;
    }
}
