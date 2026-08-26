<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\OrderAftersale;
use InvalidArgumentException;
use support\Request;
use support\Response;

/**
 * 售后（退换货）管理控制器
 *
 * 列表（状态筛选 + uid/订单号搜索）+ 审核（approve/reject）。
 * 审核通过后仅状态流转（refund 类型不自动退款），
 * 退款沿用既有 POST /api/orders/{id}/refund 由商家另行操作。
 */
class AftersaleController extends BaseController
{
    /**
     * 售后列表
     * GET /admin/aftersales?page&limit&status&uid&order_no
     */
    public function index(Request $request): Response
    {
        $page    = (int) $request->input('page', 1);
        $limit   = (int) $request->input('limit', 15);
        $status  = (string) $request->input('status', '');
        $uid     = (string) $request->input('uid', '');
        $orderNo = (string) $request->input('order_no', '');

        $query = OrderAftersale::with(['order', 'order.items', 'user']);

        if ($status !== '') {
            $query->where('status', $status);
        }
        if ($uid !== '') {
            $query->where('user_id', $uid);
        }
        if ($orderNo !== '') {
            $query->whereHas('order', function ($q) use ($orderNo) {
                $q->where('order_no', 'like', "%{$orderNo}%");
            });
        }

        $total = $query->count();
        $list  = $query->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->orderBy('created_at', 'desc')
                       ->get()
                       ->map(fn($a) => $a->toArray());

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 售后审核
     * POST /admin/aftersales/{id}/review  body: {action: approve|reject, remark}
     *
     * approve：pending → approved（仅状态流转，refund 不自动退款）；
     * reject：pending → rejected，remark 必填。
     */
    public function review(Request $request, string $hashid): Response
    {
        try {
            $id = $this->decodeId($hashid);
        } catch (InvalidArgumentException) {
            return $this->fail('无效的售后ID', 422);
        }
        $aftersale = OrderAftersale::with('order')->find($id);
        if (!$aftersale) {
            return $this->fail('售后记录不存在', 404);
        }
        if ($aftersale->status !== OrderAftersale::STATUS_PENDING) {
            return $this->fail('仅待审核状态的售后可审核', 422);
        }

        $action = (string) $request->input('action', '');
        $remark = trim((string) $request->input('remark', ''));

        if ($action === 'approve') {
            $aftersale->status        = OrderAftersale::STATUS_APPROVED;
            $aftersale->review_remark = $remark;
            $aftersale->reviewed_at   = date('Y-m-d H:i:s');
            $aftersale->save();
            return $this->success($aftersale->toArray(), '审核通过');
        }

        if ($action === 'reject') {
            if ($remark === '') {
                return $this->fail('驳回必须填写备注', 422);
            }
            $aftersale->status        = OrderAftersale::STATUS_REJECTED;
            $aftersale->review_remark = $remark;
            $aftersale->reviewed_at   = date('Y-m-d H:i:s');
            $aftersale->save();
            return $this->success($aftersale->toArray(), '已驳回');
        }

        return $this->fail('无效的审核动作', 422);
    }
}
