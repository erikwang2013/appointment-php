<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\TechnicianWithdrawal;
use support\Request;
use support\Response;

class WithdrawalAccountController extends BaseController
{
    /**
     * 提现账户列表（从提现记录中汇总去重）
     */
    public function index(Request $request): Response
    {
        $page          = (int) $request->input('page', 1);
        $limit         = (int) $request->input('limit', 15);
        $technicianId  = $request->input('technician_id', '');
        $accountType   = $request->input('account_type', '');

        $query = TechnicianWithdrawal::with('technician')
            ->select('technician_id', 'account_type', 'account_name', 'account_no')
            ->groupBy('technician_id', 'account_type', 'account_name', 'account_no');

        if ($technicianId) {
            $query->where('technician_id', $technicianId);
        }
        if ($accountType) {
            $query->where('account_type', $accountType);
        }

        $total = $query->count();
        $list  = $query->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->orderBy('technician_id')
                       ->get()
                       ->map(function ($item) {
                           $data = $item->toArray();
                           if (!empty($data['account_no'])) {
                               $data['account_no'] = '****' . substr($data['account_no'], -4);
                           }
                           if (!empty($data['account_name'])) {
                               $data['account_name'] = mb_substr($data['account_name'], 0, 1) . '**';
                           }
                           return $data;
                       });

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 删除提现账户记录
     */
    public function destroy(Request $request, string $hashid): Response
    {
        $id = $this->decodeId($hashid);

        // 删除该技术人员的所有提现账户相关信息
        $withdrawal = TechnicianWithdrawal::find($id);
        if (!$withdrawal) {
            return $this->fail('记录不存在', 404);
        }

        $adminId = $request->adminId ?? 0;
        $error   = $this->confirmPassword($adminId, $request->input('password', ''), $request);
        if ($error !== null) {
            return $this->fail($error, 422);
        }

        $withdrawal->delete();
        return $this->success([], '删除成功');
    }
}
