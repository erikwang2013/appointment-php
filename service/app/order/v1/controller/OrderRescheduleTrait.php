<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\order\v1\controller;

use app\common\NotificationReminderService;
use app\model\Order;
use app\model\OrderReschedule;
use support\Db;
use support\Log;
use support\Redis;
use Webman\Http\Request;

/**
 * 预约改期（reschedule）
 *
 * 仅预约订单 pending/paid/confirmed 可改期，距原服务开始 ≥ 6 小时；
 * 新时段技师锁 SETNX（EX 兜底）+ 排班冲突 DB 校验；B1 order_lock 串行化。
 */
trait OrderRescheduleTrait
{
    /**
     * 改期规则：距原服务开始 ≥ 6 小时（21600 秒）方可改期。
     * 与 Order::calcRefundRatio 全额退款窗口一致——临近服务的时段变更风险高，仅允许提前改期。
     */
    private const RESCHEDULE_MIN_LEAD_SECONDS = 21600;

    /** 改期新时段技师锁 TTL（秒），与 store 下单技师锁一致（EX 兜底释放） */
    private const TECHNICIAN_LOCK_TTL = 180;

    /**
     * 预约改期
     * POST /api/order/reschedule/{id}
     *
     * body: { new_service_time: "Y-m-d H:i[:s]", reason?: string }
     *
     * 规则（RESCHEDULE_MIN_LEAD_SECONDS）：
     * - 仅预约订单、状态 pending/paid/confirmed 可改期（serving/completed/cancelled/refunding/refunded 拒绝 422）
     * - 距原服务开始 ≥ 6 小时方可改期（临近时段拒绝，与 calcRefundRatio 全额退款窗口一致）
     * - 新时段同技师冲突校验复用 store 的 B2 DB 校验（排除本单）：同技师同新时间已有
     *   pending/paid 订单则 422
     * - 新时段技师锁 Redis SETNX（EX 180s 兜底）防并发改期/超卖：并发改期只有一笔能拿到
     *   新时段锁；成功后原时段锁释放、新时段锁由本单继续持有
     *
     * 并发：B1 order_lock（与 pay/cancel/refund/支付回调/自动取消同一互斥族）串行化
     * 同订单状态变更，事务内行锁重读兜底。
     */
    public function reschedule(Request $request, string $id)
    {
        $id = $this->decodeId((string)$id);
        if ($id === null) {
            return $this->error('订单不存在', 404);
        }
        $userId = $request->user_id;

        // 入参校验（不依赖锁）
        $newServiceTime = $request->input('new_service_time', '');
        if ($newServiceTime === '') {
            return $this->error('请选择新的服务时间', 422);
        }
        if (strtotime($newServiceTime) === false) {
            return $this->error('服务时间格式不正确', 422);
        }
        $reason = (string) $request->input('reason', '');

        // 订单归属校验（非本人按不存在处理，404）
        $order = Order::where('user_id', $userId)
            ->where('id', $id)
            ->first();
        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        // B1: 统一 per-order 互斥锁（与 pay/cancel/refund/支付回调/自动取消共用 order_lock）
        $lockKey = 'order_lock:' . $order->id;
        $lockToken = $this->acquireLock($lockKey);
        if ($lockToken === null) {
            return $this->error('操作处理中，请稍后再试');
        }

        try {
            // 锁内重新读取订单并校验状态（防并发状态变更）
            $order = Order::where('user_id', $userId)
                ->where('id', $id)
                ->first();
            if (!$order) {
                return $this->error('订单不存在', 404);
            }
            if ($order->order_type !== Order::ORDER_TYPE_APPOINTMENT) {
                return $this->error('当前订单不可改期', 422);
            }
            if (!in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_PAID, Order::STATUS_CONFIRMED], true)) {
                return $this->error('当前订单状态不可改期', 422);
            }

            $oldServiceTime = $order->service_time;
            if (!$oldServiceTime) {
                return $this->error('当前订单不可改期', 422);
            }
            if (($oldServiceTime->getTimestamp() - time()) < self::RESCHEDULE_MIN_LEAD_SECONDS) {
                return $this->error('距原服务开始不足 6 小时，无法改期', 422);
            }

            // 新时段技师锁（防并发改期/超卖；store 下单同款 SETNX，EX 兜底释放）
            $timeSlot = date('YmdHi', strtotime($newServiceTime));
            $newLockKey = "technician_lock:{$order->technician_id}:{$timeSlot}";
            $acquired = Redis::connection()->set($newLockKey, $userId, 'EX', self::TECHNICIAN_LOCK_TTL, 'NX');
            if (!$acquired) {
                return $this->error('该时段技师已被他人锁定，请选择其他时间段', 422);
            }

            $oldTechnicianId = $order->technician_id;

            Db::beginTransaction();
            try {
                // 行锁重读（order_lock 外的冗余防护）：状态/时间以锁内最新为准
                $locked = Order::where('id', $order->id)->lockForUpdate()->first();
                if (!$locked || !in_array($locked->status, [Order::STATUS_PENDING, Order::STATUS_PAID, Order::STATUS_CONFIRMED], true)) {
                    throw new \InvalidArgumentException('当前订单状态不可改期', 422);
                }
                $lockedOldTime = $locked->service_time;
                if (!$lockedOldTime || ($lockedOldTime->getTimestamp() - time()) < self::RESCHEDULE_MIN_LEAD_SECONDS) {
                    throw new \InvalidArgumentException('距原服务开始不足 6 小时，无法改期', 422);
                }

                // B2: 新时段排班冲突 DB 校验（防超卖兜底）——同技师同新时间已有
                // pending/paid 订单则拒绝（排除本单自身）
                $conflict = Order::where('technician_id', $locked->technician_id)
                    ->where('service_time', $newServiceTime)
                    ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_PAID])
                    ->where('id', '!=', $locked->id)
                    ->exists();
                if ($conflict) {
                    throw new \InvalidArgumentException('该时段技师已被预约，请选择其他时间段', 422);
                }

                $locked->service_time = $newServiceTime;
                $locked->save();

                OrderReschedule::create([
                    'id'                => OrderReschedule::generateId(),
                    'order_id'          => $locked->id,
                    'old_service_time'  => $lockedOldTime->format('Y-m-d H:i:s'),
                    'new_service_time'  => $newServiceTime,
                    'old_technician_id' => $oldTechnicianId,
                    'new_technician_id' => $oldTechnicianId,
                    'reason'            => $reason,
                    'created_at'        => date('Y-m-d H:i:s'),
                ]);

                Db::commit();
            } catch (\Throwable $e) {
                Db::rollBack();
                // 释放新时段技师锁（事务失败则本单未占用新时段）
                Redis::connection()->del($newLockKey);
                if ($e instanceof \InvalidArgumentException) {
                    return $this->error($e->getMessage(), 422);
                }
                Log::error('[OrderController] order reschedule failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                return $this->error('改期失败，请稍后重试');
            }

            // 释放原时段技师锁（改期后本单占用新时段，原时段归还）
            $this->releaseTechnicianSlotLock($oldTechnicianId, $oldServiceTime, (string) $userId);

            // 订阅消息 + 站内通知（非阻塞，失败不影响主流程；模板未配置时降级仅站内通知）
            $this->notifySubscribeEvent($order->fresh() ?: $order, NotificationReminderService::SCENE_RESCHEDULE, [
                'old_service_time' => $oldServiceTime->format('Y-m-d H:i'),
            ]);

            // WebSocket 实时推送
            $this->pushOrderUpdate($order->fresh() ?: $order);

            return $this->success($order->fresh() ?: $order, '改期成功');
        } finally {
            $this->releaseLock($lockKey, $lockToken);
        }
    }
}
