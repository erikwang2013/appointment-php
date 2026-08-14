<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\common\TechnicianWithdrawalService;
use app\model\TechnicianWithdrawal;
use support\Request;
use support\Response;
use Erikwang2013\PosterPhp\Poster;

class RefundWorkflowController extends BaseController
{
    /**
     * 待审批退款列表
     * 根据当前登录用户的角色返回其审批层级内的退款申请
     */
    public function pending(Request $request): Response
    {
        $page       = (int) $request->input('page', 1);
        $limit      = (int) $request->input('limit', 15);
        $adminId    = $request->adminId ?? 0;

        // 判断当前审批人角色：store_approved_at为空 → 需要店长审批
        // finance_approved_at为空且 amount >= 500 → 需要财务审批
        $query = TechnicianWithdrawal::with('technician')
            ->where('status', 'pending');

        // 所有待处理的退款请求
        $total = $query->count();
        $list  = $query->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->orderBy('id', 'desc')
                       ->get()
                       ->map(function ($w) {
                           $data = $w->toArray();
                           if (isset($data['technician']['real_name'])) {
                               $data['technician']['real_name'] = mb_substr($data['technician']['real_name'], 0, 1) . '**';
                           }
                           if (!empty($data['account_no'])) {
                               $data['account_no'] = '****' . substr($data['account_no'], -4);
                           }

                           // 审批层级判断
                           $data['approval_level'] = $this->determineApprovalLevel($w);
                           return $this->encodeIds($data);
                       });

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 通过退款审批
     * 多级审批流:
     * - Level 1: 店长审批 (amount < 500 自动通过，直接标记完成)
     * - Level 2: 财务审批 (amount >= 500 需要 level 1 + level 2)
     */
    public function approve(Request $request, string $hashid): Response
    {
        Poster::verify($request);

        $id         = $this->decodeId($hashid);
        $withdrawal = TechnicianWithdrawal::find($id);
        if (!$withdrawal) {
            return $this->fail('退款记录不存在', 404);
        }

        if ($withdrawal->status !== 'pending') {
            return $this->fail('仅待审核状态的退款可审批', 422);
        }

        $now    = date('Y-m-d H:i:s');
        $amount = (float) $withdrawal->amount;

        if (empty($withdrawal->store_approved_at)) {
            // Level 1: 店长审批
            $withdrawal->store_approved_at = $now;

            if ($amount < 500) {
                // 小额自动完成
                $withdrawal->status = 'approved';
                $withdrawal->audited_at = $now;
            }
            $withdrawal->audit_remark = $request->input('remark', '');
            $withdrawal->save();

            if ($amount < 500) {
                // 小额自动完成：审批全部通过，发起微信转账（失败返回错误，提现记录已置 failed）
                $transfer = (new TechnicianWithdrawalService())->approveAndTransfer($withdrawal);
                if (!$transfer['success']) {
                    return $this->fail($transfer['message'], 500);
                }
                return $this->success(
                    $this->encodeIds($withdrawal->toArray()),
                    '店长审批通过，小额自动完成'
                );
            }
            return $this->success(
                $this->encodeIds($withdrawal->toArray()),
                '店长审批通过，等待财务审批'
            );
        }

        if (empty($withdrawal->finance_approved_at) && $amount >= 500) {
            // Level 2: 财务审批
            $withdrawal->finance_approved_at = $now;
            $withdrawal->status       = 'approved';
            $withdrawal->audited_at   = $now;
            $withdrawal->audit_remark = $request->input('remark', $withdrawal->audit_remark);
            $withdrawal->save();

            // 审批全部通过，发起微信转账（失败返回错误，提现记录已置 failed）
            $transfer = (new TechnicianWithdrawalService())->approveAndTransfer($withdrawal);
            if (!$transfer['success']) {
                return $this->fail($transfer['message'], 500);
            }

            return $this->success(
                $this->encodeIds($withdrawal->toArray()),
                '财务审批通过'
            );
        }

        return $this->fail('该退款已完成全部审批流程', 422);
    }

    /**
     * 驳回退款
     */
    public function reject(Request $request, string $hashid): Response
    {
        Poster::verify($request);

        $id         = $this->decodeId($hashid);
        $withdrawal = TechnicianWithdrawal::find($id);
        if (!$withdrawal) {
            return $this->fail('退款记录不存在', 404);
        }

        if ($withdrawal->status !== 'pending') {
            return $this->fail('仅待审核状态的退款可驳回', 422);
        }

        $reason = $request->input('reason', '');
        if (empty($reason)) {
            return $this->fail('驳回原因不能为空', 422);
        }

        $withdrawal->status        = 'rejected';
        $withdrawal->reject_reason = $reason;
        $withdrawal->audited_at    = date('Y-m-d H:i:s');
        $withdrawal->save();

        return $this->success(
            $this->encodeIds($withdrawal->toArray()),
            '已驳回退款申请'
        );
    }

    /**
     * 判断当前审批层级
     */
    private function determineApprovalLevel(TechnicianWithdrawal $w): string
    {
        $amount = (float) $w->amount;

        if (empty($w->store_approved_at)) {
            return 'level_1_store';
        }
        if ($amount >= 500 && empty($w->finance_approved_at)) {
            return 'level_2_finance';
        }
        return 'completed';
    }
}
