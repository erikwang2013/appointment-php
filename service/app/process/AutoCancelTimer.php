<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\process;

use app\model\Order;
use app\model\TechnicianSchedule;
use support\Db;
use support\Log;
use support\Redis;
use Workerman\Timer;
use Workerman\Worker;

/**
 * 订单自动取消定时器
 *
 * 每 30 秒扫描一次：
 * - 状态为 pending 且创建超过 30 分钟的订单
 * - 自动取消并释放技师排班锁定
 * - 可选：写入通知表供用户查看
 */
class AutoCancelTimer
{
    /**
     * 订单未支付自动取消时间（秒），默认 30 分钟
     */
    private const PENDING_TIMEOUT = 1800;

    /**
     * 定时器间隔（秒）
     */
    private const SCAN_INTERVAL = 30;

    /**
     * 构造函数 — 注册定时器
     */
    public function __construct()
    {
        Timer::add(self::SCAN_INTERVAL, function (): void {
            $this->scanAndCancel();
        });
    }

    /**
     * 扫描待支付订单并自动取消
     */
    public function scanAndCancel(): void
    {
        $cutoffTime = date('Y-m-d H:i:s', time() - self::PENDING_TIMEOUT);

        try {
            $orders = Order::where('status', Order::STATUS_PENDING)
                ->where('created_at', '<', $cutoffTime)
                ->limit(50)
                ->get();

            if ($orders->isEmpty()) {
                return;
            }

            $cancelledCount = 0;

            foreach ($orders as $order) {
                try {
                    $cancelled = $this->cancelOrder($order);
                    if ($cancelled) {
                        $cancelledCount++;
                    }
                } catch (\Throwable $e) {
                    Log::error('[AutoCancelTimer] Failed to cancel order '
                        . ($order->id ?? 'unknown') . ': ' . $e->getMessage());
                }
            }

            if ($cancelledCount > 0) {
                Log::info('[AutoCancelTimer] Auto-cancelled ' . $cancelledCount . ' pending orders');
            }
        } catch (\Throwable $e) {
            Log::error('[AutoCancelTimer] Scan error: ' . $e->getMessage()
                . "\n" . $e->getTraceAsString());
        }
    }

    /**
     * 取消单个订单
     *
     * @param Order $order
     * @return bool
     */
    private function cancelOrder(Order $order): bool
    {
        return Db::transaction(function () use ($order): bool {
            // 重新查询并加锁，避免并发问题
            $locked = Order::where('id', $order->id)
                ->where('status', Order::STATUS_PENDING)
                ->lockForUpdate()
                ->first();

            if (!$locked) {
                return false; // 已被其他进程处理
            }

            // 更新订单状态
            $locked->status = Order::STATUS_CANCELLED;
            $locked->cancel_reason = 'auto_cancel: unpaid timeout';
            $locked->cancel_at = date('Y-m-d H:i:s');
            $locked->save();

            // 释放技师排班锁定
            if (!empty($order->technician_id) && !empty($order->service_time)) {
                $this->releaseTechnicianLock(
                    (string)$order->technician_id,
                    $order->service_time->format('Y-m-d'),
                    $order->service_time->format('H:i:00')
                );
            }

            // 写入通知
            try {
                Db::table('erik_notification')->insert([
                    'id'         => \Erikwang2013\Snowflake\Snowflake::generate(),
                    'user_id'    => $order->user_id,
                    'type'       => 'order_auto_cancel',
                    'title'      => '订单已自动取消',
                    'content'    => '您的订单 ' . ($order->order_no ?? '') . ' 因超时未支付已被系统自动取消。',
                    'is_read'    => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e) {
                Log::warning('[AutoCancelTimer] Failed to insert notification: ' . $e->getMessage());
            }

            // 清除订单相关的 TTL 键（Keyspace 通知备用）
            $ttlKey = 'order_ttl:' . $order->id;
            if (Redis::exists($ttlKey)) {
                Redis::del($ttlKey);
            }

            return true;
        });
    }

    /**
     * 释放技师排班锁定
     *
     * 从 schedule 的时间槽中移除原预约，恢复可用状态
     *
     * @param string $technicianId 技师 ID
     * @param string $date         Y-m-d 格式日期
     * @param string $timeSlot     H:i:00 格式时间
     */
    private function releaseTechnicianLock(string $technicianId, string $date, string $timeSlot): void
    {
        try {
            $schedule = TechnicianSchedule::where('technician_id', $technicianId)
                ->where('date', $date)
                ->first();

            if (!$schedule) {
                return;
            }

            $slots = $schedule->time_slots ?? [];
            if (!is_array($slots)) {
                return;
            }

            // 恢复该时间槽为可用
            foreach ($slots as &$slot) {
                if (isset($slot['time']) && $slot['time'] === $timeSlot) {
                    $slot['status'] = 0;      // 0 = 可用
                    $slot['order_id'] = null;
                    break;
                }
            }
            unset($slot);

            $schedule->time_slots = $slots;
            $schedule->save();

            // 清除 Redis 技师锁
            $lockKey = "tech_lock:{$technicianId}:{$date}:{$timeSlot}";
            if (Redis::exists($lockKey)) {
                Redis::del($lockKey);
            }
        } catch (\Throwable $e) {
            Log::error('[AutoCancelTimer] Failed to release technician lock: ' . $e->getMessage());
        }
    }
}
