<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\TechnicianTierConfig;
use app\model\TechnicianProfile;
use support\Db;
use support\Request;
use support\Response;

class TechnicianTierController extends BaseController
{
    /**
     * 等级配置列表
     */
    public function index(Request $request): Response
    {
        $list = TechnicianTierConfig::orderBy('sort', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(fn($t) => $t->toArray());

        return $this->success(['list' => $list]);
    }

    /**
     * 更新等级配置
     */
    public function update(Request $request, string $hashid): Response
    {
        $id   = $this->decodeId($hashid);
        $tier = TechnicianTierConfig::find($id);
        if (!$tier) {
            return $this->fail('等级配置不存在', 404);
        }

        if ($request->input('name') !== null) {
            $tier->name = $request->input('name');
        }
        if ($request->input('min_orders') !== null) {
            $tier->min_orders = (int) $request->input('min_orders');
        }
        if ($request->input('min_rating') !== null) {
            $tier->min_rating = (float) $request->input('min_rating');
        }
        if ($request->input('commission_rate') !== null) {
            $tier->commission_rate = (float) $request->input('commission_rate');
        }
        if ($request->input('price_multiplier') !== null) {
            $tier->price_multiplier = (float) $request->input('price_multiplier');
        }
        if ($request->input('sort') !== null) {
            $tier->sort = (int) $request->input('sort');
        }
        $tier->save();

        return $this->success($tier->toArray(), '等级配置已更新');
    }

    /**
     * 自动评估并分配技师等级
     * 基于当前数据（接单数 + 评分）计算应属等级
     */
    public function assign(Request $request): Response
    {
        $tiers = TechnicianTierConfig::orderBy('sort', 'desc')
            ->get();

        $technicians = TechnicianProfile::where('status', 1)
            ->get();

        $results = [];
        foreach ($technicians as $tech) {
            $assignedTier = null;
            // 从高到低匹配等级（满足最高等级条件即归入该等级）
            foreach ($tiers as $tier) {
                if ($tech->order_count >= $tier->min_orders
                    && (float) $tech->rating >= (float) $tier->min_rating
                ) {
                    $assignedTier = $tier;
                    break;
                }
            }

            // 如果没有匹配，设为最低等级
            if (!$assignedTier && $tiers->isNotEmpty()) {
                $assignedTier = $tiers->last();
            }

            $results[] = [
                'technician_id'   => (int) $tech->id,
                'technician_name' => mb_substr($tech->real_name, 0, 1) . '**',
                'current_orders'  => $tech->order_count,
                'current_rating'  => (float) $tech->rating,
                'assigned_tier'   => $assignedTier ? $assignedTier->slug : 'junior',
                'tier_name'       => $assignedTier ? $assignedTier->name : '初级技师',
                'commission_rate' => $assignedTier ? (float) $assignedTier->commission_rate : 30.00,
            ];
        }

        $summary = [
            'junior' => count(array_filter($results, fn($r) => $r['assigned_tier'] === 'junior')),
            'senior' => count(array_filter($results, fn($r) => $r['assigned_tier'] === 'senior')),
            'expert' => count(array_filter($results, fn($r) => $r['assigned_tier'] === 'expert')),
        ];

        return $this->success([
            'assignments' => $results,
            'summary'     => $summary,
            'evaluated_at' => date('Y-m-d H:i:s'),
        ], '等级评估完成（仅供参考，未自动写入）');
    }

    /**
     * 等级变更日志（分页）
     * GET /admin/technician-tiers/logs
     * 来源：service 端 TierRatingService 自动评定写 erik_technician_tier_log。
     */
    public function logs(Request $request): Response
    {
        $page  = (int) $request->input('page', 1);
        $limit = (int) $request->input('per_page', 15);
        if ($limit < 1 || $limit > 100) {
            $limit = 15;
        }

        $query = Db::table('erik_technician_tier_log as log')
            ->leftJoin('erik_technician_profile as p', 'p.id', '=', 'log.technician_id')
            ->leftJoin('erik_technician_tier_config as old_t', 'old_t.id', '=', 'log.old_tier_id')
            ->leftJoin('erik_technician_tier_config as new_t', 'new_t.id', '=', 'log.new_tier_id');

        $total = (clone $query)->count();

        $list = $query->select([
                'log.id',
                'log.technician_id',
                'log.old_tier_id',
                'log.new_tier_id',
                'log.reason',
                'log.created_at',
                'p.real_name',
                'old_t.name as old_tier_name',
                'new_t.name as new_tier_name',
            ])
            ->orderBy('log.id', 'desc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->map(fn($row) => $row);

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'per_page' => $limit,
        ]);
    }
}
