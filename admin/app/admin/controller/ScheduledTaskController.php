<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\TechnicianEarning;
use app\model\UserCoupon;
use app\model\UserMemberCard;
use support\Redis;
use support\Request;
use support\Response;

class ScheduledTaskController extends BaseController
{
    private string $logPrefix = 'scheduled_task:';

    /**
     * 定时任务执行日志摘要
     */
    public function index(Request $request): Response
    {
        // M9: auto_cancel 已下线（service 端 AutoCancelTimer 统一驱动）
        $tasks = ['auto_settle', 'expire_coupons', 'expire_member_cards'];
        $logs  = [];

        foreach ($tasks as $task) {
            $lastRun = Redis::get($this->logPrefix . $task . ':last_run');
            $affected = Redis::get($this->logPrefix . $task . ':last_affected');
            $logs[] = [
                'task'             => $task,
                'last_run_at'      => $lastRun ?: '从未执行',
                'last_affected'    => $affected !== false ? (int) $affected : 0,
            ];
        }

        return $this->success(['tasks' => $logs]);
    }

    /**
     * 自动结算已完成订单的技师收益
     * 条件: order status=completed 且 service_end_at < 3天前
     *       对应的 technician_earnings status=pending
     */
    public function autoSettle(Request $request): Response
    {
        $threshold = date('Y-m-d H:i:s', strtotime('-3 days'));
        $now       = date('Y-m-d H:i:s');

        $settled = TechnicianEarning::where('status', 'pending')
            ->whereHas('order', function ($q) use ($threshold) {
                $q->where('status', 'completed')
                  ->where('service_end_at', '<', $threshold);
            })
            ->update([
                'status'     => 'settled',
                'settled_at' => $now,
            ]);

        $this->recordTaskRun('auto_settle', $settled);

        return $this->success([
            'task'         => 'auto_settle',
            'settled'      => $settled,
            'threshold'    => '3天前完成（<' . $threshold . '）',
            'executed_at'  => $now,
        ], "已结算 {$settled} 条技师收益");
    }

    /**
     * 过期优惠券标记
     * 条件: erik_user_coupon 中 status=available 且 coupon.end_at < now
     */
    public function expireCoupons(Request $request): Response
    {
        $now = date('Y-m-d H:i:s');

        $expired = UserCoupon::where('status', 'available')
            ->whereHas('coupon', function ($q) use ($now) {
                $q->where('end_at', '<', $now);
            })
            ->update(['status' => 'expired']);

        $this->recordTaskRun('expire_coupons', $expired);

        return $this->success([
            'task'        => 'expire_coupons',
            'expired'     => $expired,
            'executed_at' => $now,
        ], "已标记 {$expired} 张优惠券为已过期");
    }

    /**
     * 过期会员卡标记
     * 条件: erik_user_member_card 中 status=active 且 end_at < now
     */
    public function expireMemberCards(Request $request): Response
    {
        $now = date('Y-m-d H:i:s');

        $expired = UserMemberCard::where('status', 'active')
            ->where('end_at', '<', $now)
            ->update(['status' => 'expired']);

        $this->recordTaskRun('expire_member_cards', $expired);

        return $this->success([
            'task'        => 'expire_member_cards',
            'expired'     => $expired,
            'executed_at' => $now,
        ], "已标记 {$expired} 张会员卡为已过期");
    }

    /**
     * 记录定时任务执行日志到 Redis
     */
    private function recordTaskRun(string $task, int $affected): void
    {
        $now = date('Y-m-d H:i:s');
        Redis::set($this->logPrefix . $task . ':last_run', $now);
        Redis::set($this->logPrefix . $task . ':last_affected', (string) $affected);

        // 保留最近 30 条执行历史
        $historyKey = $this->logPrefix . $task . ':history';
        $history = json_decode(Redis::get($historyKey) ?: '[]', true);
        $history[] = [
            'run_at'    => $now,
            'affected'  => $affected,
        ];
        if (count($history) > 30) {
            $history = array_slice($history, -30);
        }
        Redis::set($historyKey, json_encode($history, JSON_UNESCAPED_UNICODE));
    }
}
