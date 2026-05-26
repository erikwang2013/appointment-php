<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\TechnicianWithdrawal;
use support\Request;
use support\Response;
use Erikwang2013\PosterPhp\Poster;

class WithdrawalController extends BaseController
{
    /**
     * 提现申请列表
     * 搜索: finance_no / technician / date
     */
    public function index(Request $request): Response
    {
        $page          = (int) $request->input('page', 1);
        $limit         = (int) $request->input('limit', 15);
        $financeNo     = $request->input('finance_no', '');
        $technicianId  = $request->input('technician_id', '');
        $technicianName = $request->input('technician_name', '');
        $status        = $request->input('status', '');
        $dateStart     = $request->input('date_start', '');
        $dateEnd       = $request->input('date_end', '');

        $query = TechnicianWithdrawal::with('technician');

        if ($financeNo) {
            $query->where('withdrawal_no', 'like', "%{$financeNo}%");
        }
        if ($technicianId) {
            $query->where('technician_id', $technicianId);
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
        if ($technicianName) {
            $query->whereHas('technician', function ($q) use ($technicianName) {
                $q->where('real_name', 'like', "%{$technicianName}%");
            });
        }

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
     * 提现详情 + 技师信息 + 账户信息
     */
    public function show(Request $request, string $hashid): Response
    {
        $id         = $this->decodeId($hashid);
        $withdrawal = TechnicianWithdrawal::with(['technician', 'technician.user'])->find($id);
        if (!$withdrawal) {
            return $this->fail('提现记录不存在', 404);
        }

        $data = $withdrawal->toArray();
        if (isset($data['technician']['real_name'])) {
            $data['technician']['real_name'] = mb_substr($data['technician']['real_name'], 0, 1) . '**';
        }
        // 脱敏账户信息
        if (!empty($data['account_no'])) {
            $data['account_no'] = '****' . substr($data['account_no'], -4);
        }

        return $this->success($this->encodeIds($data));
    }

    /**
     * 审核通过（多级审批流）
     * - Level 1: 店长审批 (store_approved_at)
     * - 小额（<500元）自动完成，不进入Level 2
     * - Level 2: 财务审批 (finance_approved_at)，大额（>=500元）须两级
     */
    public function approve(Request $request, string $hashid): Response
    {
        Poster::verify($request);

        $id         = $this->decodeId($hashid);
        $withdrawal = TechnicianWithdrawal::find($id);
        if (!$withdrawal) {
            return $this->fail('提现记录不存在', 404);
        }

        if ($withdrawal->status !== 'pending') {
            return $this->fail('仅待审核状态的提现可审核', 422);
        }

        $now    = date('Y-m-d H:i:s');
        $amount = (float) $withdrawal->amount;

        if (empty($withdrawal->store_approved_at)) {
            // Level 1: 店长审批
            $withdrawal->store_approved_at = $now;

            if ($amount < 500) {
                // 小额自动完成
                $withdrawal->status      = 'approved';
                $withdrawal->audited_at   = $now;
            }
            $withdrawal->audit_remark = $request->input('remark', $withdrawal->audit_remark);
            $withdrawal->save();

            if ($amount < 500) {
                return $this->success($this->encodeIds($withdrawal->toArray()), '店长审批通过，小额自动完成');
            }
            return $this->success($this->encodeIds($withdrawal->toArray()), '店长审批通过，等待财务审批');
        }

        if (empty($withdrawal->finance_approved_at) && $amount >= 500) {
            // Level 2: 财务审批
            $withdrawal->finance_approved_at = $now;
            $withdrawal->status       = 'approved';
            $withdrawal->audited_at   = $now;
            $withdrawal->audit_remark = $request->input('remark', $withdrawal->audit_remark);
            $withdrawal->save();

            return $this->success($this->encodeIds($withdrawal->toArray()), '财务审批通过');
        }

        return $this->fail('该提现已完成全部审批流程', 422);
    }

    /**
     * 驳回
     */
    public function reject(Request $request, string $hashid): Response
    {
        Poster::verify($request);

        $id         = $this->decodeId($hashid);
        $withdrawal = TechnicianWithdrawal::find($id);
        if (!$withdrawal) {
            return $this->fail('提现记录不存在', 404);
        }

        if ($withdrawal->status !== 'pending') {
            return $this->fail('仅待审核状态的提现可驳回', 422);
        }

        $remark = $request->input('remark', '');
        if (empty($remark)) {
            return $this->fail('驳回备注不能为空', 422);
        }

        $withdrawal->status       = 'rejected';
        $withdrawal->audit_remark = $remark;
        $withdrawal->audited_at   = date('Y-m-d H:i:s');
        $withdrawal->save();

        return $this->success($this->encodeIds($withdrawal->toArray()), '已驳回');
    }

    /**
     * 标记为已完成（款项已到账）
     */
    public function complete(Request $request, string $hashid): Response
    {
        Poster::verify($request);

        $id         = $this->decodeId($hashid);
        $withdrawal = TechnicianWithdrawal::find($id);
        if (!$withdrawal) {
            return $this->fail('提现记录不存在', 404);
        }

        if ($withdrawal->status !== 'approved') {
            return $this->fail('仅已审核通过的提现可标记完成', 422);
        }

        $withdrawal->status       = 'completed';
        $withdrawal->completed_at = date('Y-m-d H:i:s');
        $withdrawal->save();

        return $this->success($this->encodeIds($withdrawal->toArray()), '已标记完成');
    }
}
