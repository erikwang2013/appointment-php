<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\common\Money;
use app\model\TechnicianProfile;
use app\model\TechnicianEarning;
use support\Request;
use support\Response;

class CommissionController extends BaseController
{
    /**
     * 技师佣金配置列表
     */
    public function index(Request $request): Response
    {
        $page   = (int) $request->input('page', 1);
        $limit  = (int) $request->input('limit', 15);
        $uid    = $request->input('uid', '');
        $name   = $request->input('name', '');
        $status = $request->input('status');

        $query = TechnicianProfile::with('user');

        if ($uid) {
            $query->where('user_id', $uid);
        }
        if ($name) {
            $query->where('real_name', 'like', "%{$name}%");
        }
        if ($status !== null && $status !== '') {
            $query->where('status', (int) $status);
        }

        $total = $query->count();
        $list  = $query->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->orderBy('id', 'desc')
                       ->get()
                       ->map(function ($t) {
                           $data = $t->toArray();
                           // 附加佣金统计
                           $tId = $t->id;
                           $data['total_commission'] = TechnicianEarning::where('technician_id', $tId)
                               ->where('type', 'commission')->sum('amount');
                           $data['total_bonus'] = TechnicianEarning::where('technician_id', $tId)
                               ->where('type', 'bonus')->sum('amount');
                           $data['total_penalty'] = TechnicianEarning::where('technician_id', $tId)
                               ->where('type', 'penalty')->sum('amount');
                           $data['pending_amount'] = TechnicianEarning::where('technician_id', $tId)
                               ->where('status', 'pending')->sum('amount');
                           // balance = 佣金 + 奖金 - 罚金：SUM 为 DECIMAL string，三段链走 string 域
                           $data['balance'] = (float) Money::round(Money::sub(Money::add($data['total_commission'], $data['total_bonus']), $data['total_penalty']), 2);

                           if (isset($data['real_name'])) {
                               $data['real_name'] = mb_substr($data['real_name'], 0, 1) . '**';
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
     * 更新佣金率和结算周期
     */
    public function update(Request $request, string $hashid): Response
    {
        $id       = $this->decodeId($hashid);
        $profile  = TechnicianProfile::find($id);
        if (!$profile) {
            return $this->fail('技师不存在', 404);
        }

        if ($request->input('commission_rate') !== null) {
            $profile->commission_rate = (float) $request->input('commission_rate');
        }
        if ($request->input('settlement_cycle') !== null) {
            $profile->settlement_cycle = $request->input('settlement_cycle');
        }
        $profile->save();

        return $this->success($profile->toArray(), '佣金配置更新成功');
    }

    /**
     * 添加奖励
     */
    public function bonus(Request $request, string $hashid): Response
    {
        $id       = $this->decodeId($hashid);
        $profile  = TechnicianProfile::find($id);
        if (!$profile) {
            return $this->fail('技师不存在', 404);
        }

        $amount = (float) $request->input('amount', 0);
        $desc   = $request->input('description', '平台奖励');
        if ($amount <= 0) {
            return $this->fail('奖励金额必须大于0', 422);
        }

        $earning = new TechnicianEarning();
        $earning->id            = (string) $this->generateId();
        $earning->technician_id = $id;
        $earning->order_id      = '';
        $earning->type          = 'bonus';
        $earning->amount        = $amount;
        $earning->description   = $desc;
        $earning->status        = 'pending';
        $earning->save();

        return $this->success($earning->toArray(), '奖励添加成功');
    }

    /**
     * 添加罚金
     */
    public function penalty(Request $request, string $hashid): Response
    {
        $id       = $this->decodeId($hashid);
        $profile  = TechnicianProfile::find($id);
        if (!$profile) {
            return $this->fail('技师不存在', 404);
        }

        $amount = (float) $request->input('amount', 0);
        $desc   = $request->input('description', '平台罚金');
        if ($amount <= 0) {
            return $this->fail('罚金金额必须大于0', 422);
        }

        $earning = new TechnicianEarning();
        $earning->id            = (string) $this->generateId();
        $earning->technician_id = $id;
        $earning->order_id      = '';
        $earning->type          = 'penalty';
        $earning->amount        = $amount;
        $earning->description   = $desc;
        $earning->status        = 'pending';
        $earning->save();

        return $this->success($earning->toArray(), '罚金添加成功');
    }
}
