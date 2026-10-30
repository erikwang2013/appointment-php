<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\common;

use app\model\Order;
use app\model\OrderStatusLog;
use app\model\TechnicianSchedule;
use app\model\UserPoints;
use support\Db;
use support\Log;
use support\Model;
use support\Redis;

/**
 * 待支付订单取消（定时器 / 拼团批量关闭共用）
 *
 * 统一 order_lock → 行锁状态复检 → 取消 + 退积分 → 释放技师时间槽 →
 * 站内通知 + 状态时间线；拿不到锁时跳过，交给该订单自己的支付/取消流程。
 */
class PendingOrderCancelService
{
    public static function cancel(Order $order, string $reason, string $logReason = '', string $actor = 'system'): bool
    {
        $lockKey = 'order_lock:' . $order->id;
        $lockToken = bin2hex(random_bytes(16));
        try {
            $acquired = (bool) Redis::connection()->set($lockKey, $lockToken, 'EX', 35, 'NX');
        } catch (\Throwable $e) {
            Log::warning('[PendingOrderCancel] order lock unavailable, order: ' . $order->id . ': ' . $e->getMessage());
            return false;
        }
        if (!$acquired) {
            return false;
        }

        try {
            return self::cancelLocked($order, $reason, $logReason, $actor);
        } finally {
            try {
                if ((string) Redis::connection()->get($lockKey) === $lockToken) {
                    Redis::connection()->del($lockKey);
                }
            } catch (\Throwable $e) {
                Log::warning('[PendingOrderCancel] release order lock failed, order: ' . $order->id . ': ' . $e->getMessage());
            }
        }
    }

    /**
     * 调用方已持有 order_lock 时执行取消（pay 懒判定等场景）
     */
    public static function cancelLocked(Order $order, string $reason, string $logReason = '', string $actor = 'system'): bool
    {
        try {
            $done = Db::transaction(function () use ($order, $reason): bool {
                $locked = Order::where('id', $order->id)
                    ->where('status', Order::STATUS_PENDING)
                    ->lockForUpdate()
                    ->first();
                if (!$locked) {
                    return false;
                }

                $locked->status = Order::STATUS_CANCELLED;
                $locked->cancel_reason = $reason;
                $locked->cancel_at = date('Y-m-d H:i:s');
                $locked->save();

                // 退还支付尝试中已预扣的积分抵扣（幂等；未支付订单不涉及券/次卡）
                UserPoints::refundOrderOffset($locked, true);

                self::releaseTechnicianLock($order);

                try {
                    Db::table('appointment_notification')->insert([
                        'id'         => Model::generateId(),
                        'user_id'    => $order->user_id,
                        'type'       => 'order_auto_cancel',
                        'title'      => '订单已自动取消',
                        'content'    => '您的订单 ' . ($order->order_no ?? '') . ' 已自动取消：' . $reason,
                        'is_read'    => 0,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('[PendingOrderCancel] notification failed: ' . $e->getMessage());
                }

                $ttlKey = 'order_ttl:' . $order->id;
                try {
                    if (Redis::exists($ttlKey)) {
                        Redis::del($ttlKey);
                    }
                } catch (\Throwable) {
                }

                return true;
            });
        } catch (\Throwable $e) {
            Log::error('[PendingOrderCancel] cancel failed, order: ' . $order->id . ': ' . $e->getMessage());
            return false;
        }

        if ($done) {
            try {
                OrderStatusLog::record(
                    (string) $order->id,
                    Order::STATUS_PENDING,
                    Order::STATUS_CANCELLED,
                    $logReason !== '' ? $logReason : $reason,
                    $actor
                );
            } catch (\Throwable $e) {
                Log::warning('[PendingOrderCancel] status log failed: ' . $e->getMessage());
            }
        }

        return $done;
    }

    /**
     * 释放技师排班时间槽与 Redis 技师锁（与 AutoCancelTimer 原实现一致）
     */
    private static function releaseTechnicianLock(Order $order): void
    {
        if (empty($order->technician_id) || empty($order->service_time)) {
            return;
        }

        try {
            $date = $order->service_time->format('Y-m-d');
            $timeSlot = $order->service_time->format('H:i:00');

            $schedule = TechnicianSchedule::where('technician_id', $order->technician_id)
                ->where('date', $date)
                ->lockForUpdate()
                ->first();
            if (!$schedule) {
                return;
            }

            $slots = $schedule->time_slots ?? [];
            if (!is_array($slots)) {
                return;
            }

            $match = substr($timeSlot, 0, 5);
            foreach ($slots as &$slot) {
                if (!is_array($slot)) {
                    continue;
                }
                $slotTime = (string) ($slot['start'] ?? $slot['time'] ?? '');
                if ($slotTime !== '' && ($slotTime === $match || $slotTime === $timeSlot)) {
                    $slot['status'] = 'available';
                    $slot['order_id'] = null;
                    break;
                }
            }
            unset($slot);

            $schedule->time_slots = $slots;
            $schedule->save();

            $timeSlotKey = date('YmdHi', strtotime($date . ' ' . $timeSlot));
            $lockKey = 'technician_lock:' . $order->technician_id . ':' . $timeSlotKey;
            // 仅释放本单用户持有的技师锁，避免误删另一订单刚重新获取的锁
            if ((string) Redis::get($lockKey) === (string) $order->user_id) {
                Redis::del($lockKey);
            }
        } catch (\Throwable $e) {
            Log::error('[PendingOrderCancel] release technician lock failed: ' . $e->getMessage());
        }
    }
}
