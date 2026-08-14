<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\process;

use app\admin\controller\ScheduledTaskController;
use support\Log;
use support\Request;
use Workerman\Timer;

/**
 * 定时任务自动调度进程
 *
 * 在 admin webman 进程内驱动 ScheduledTaskController 的三个任务（逻辑单一来源，
 * 不复制实现，直接实例化控制器调用；三个方法均不使用 $request 参数，传空请求即可）：
 * - autoSettle         每日 03:00：自动结算 3 天前已完成订单的技师收益
 * - expireCoupons      每小时整点：标记过期优惠券
 * - expireMemberCards  每小时整点：标记过期会员卡
 *
 * 调度模式与 AutoCancelTimer 一致（事件循环 Timer），首次延迟对齐到目标时刻后
 * 再注册周期定时器，避免服务重启后错过窗口。
 */
class AdminScheduleProcess
{
    private ScheduledTaskController $controller;

    public function __construct()
    {
        $this->controller = new ScheduledTaskController();

        // 每小时整点：过期券/过期卡
        $toNextHour = $this->secondsToNextHour();
        Timer::add($toNextHour, function (): void {
            $this->runHourlyTasks();
            Timer::add(3600, function (): void {
                $this->runHourlyTasks();
            });
        });

        // 每日 03:00：技师收益自动结算
        $toNextSettle = $this->secondsToNextDailyRun(3, 0);
        Timer::add($toNextSettle, function (): void {
            $this->runAutoSettle();
            Timer::add(86400, function (): void {
                $this->runAutoSettle();
            });
        });
    }

    /**
     * 每小时任务：过期优惠券 + 过期会员卡
     */
    private function runHourlyTasks(): void
    {
        $this->safeRun('expireCoupons', 'expire_coupons');
        $this->safeRun('expireMemberCards', 'expire_member_cards');
    }

    /**
     * 每日任务：技师收益自动结算
     */
    private function runAutoSettle(): void
    {
        $this->safeRun('autoSettle', 'auto_settle');
    }

    /**
     * 调用 ScheduledTaskController 方法并记录执行日志（异常不中断进程）
     */
    private function safeRun(string $method, string $taskName): void
    {
        try {
            $this->controller->{$method}(new Request(
                'GET /admin/scheduled-task HTTP/1.1' . "\r\n" . 'Host: localhost' . "\r\n\r\n"
            ));
            Log::info("[AdminScheduleProcess] {$taskName} 执行完成");
        } catch (\Throwable $e) {
            Log::error('[AdminScheduleProcess] ' . $taskName . ' 执行失败: ' . $e->getMessage()
                . "\n" . $e->getTraceAsString());
        }
    }

    /**
     * 距下一个整点的秒数（至少 1s）
     */
    private function secondsToNextHour(): int
    {
        $next = strtotime(date('Y-m-d H:00:00') . ' +1 hour');
        return max(1, $next - time());
    }

    /**
     * 距下一个 HH:mm 时刻的秒数；今日该时刻已过则取明日（至少 1s）
     */
    private function secondsToNextDailyRun(int $hour, int $minute): int
    {
        $candidate = strtotime(date('Y-m-d') . " {$hour}:{$minute}:00");
        if ($candidate <= time()) {
            $candidate = strtotime('+1 day', $candidate);
        }
        return max(1, $candidate - time());
    }
}
