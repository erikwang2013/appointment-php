<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\order\v1\controller;

use app\model\Order;
use support\Redis;

/**
 * 分布式锁工具（per-order 互斥锁 + 技师时间槽锁）
 *
 * 统一 B1 order_lock：pay/cancel/refund/支付回调/自动取消/核销共用；
 * 技师时间槽锁与 store 下单同款 SETNX，EX 兜底释放，释放校验持有者。
 */
trait OrderLockTrait
{
    /**
     * 获取 Redis 分布式锁（NX + 随机 token）
     *
     * token 用于释放时校验，防止超时后误删他人锁。
     *
     * @param string $key           锁 key
     * @param int    $expireSeconds 过期秒数（默认 35s，覆盖微信 HTTP 30s 超时）
     * @return string|null 持有 token，拿不到锁返回 null
     */
    private function acquireLock(string $key, int $expireSeconds = 35): ?string
    {
        $token = bin2hex(random_bytes(16));
        $ok = Redis::connection()->set($key, $token, 'EX', $expireSeconds, 'NX');
        return $ok ? $token : null;
    }

    /**
     * 释放 Redis 分布式锁（仅当持有者 token 匹配时删除）
     */
    private function releaseLock(string $key, ?string $token): void
    {
        if ($token === null) {
            return;
        }
        $redis = Redis::connection();
        if ((string) ($redis->get($key) ?? '') === $token) {
            $redis->del($key);
        }
    }

    /**
     * 释放技师时间槽锁定
     *
     * B2: 校验持有者 token（锁值为下单用户 ID，仅归属匹配时删除，防误删他人锁）
     */
    private function releaseTechnicianLock(Order $order): void
    {
        $this->releaseTechnicianSlotLock($order->technician_id, $order->service_time, (string) $order->user_id);
    }

    /**
     * 释放指定技师时间槽锁定（改期后释放原时段时复用；与 releaseTechnicianLock 同校验口径）
     *
     * @param mixed $serviceTime \DateTimeInterface|string 服务时间
     */
    private function releaseTechnicianSlotLock($technicianId, $serviceTime, string $userId): void
    {
        if (!$technicianId || !$serviceTime) {
            return;
        }

        $timeSlot = date('YmdHi', $serviceTime instanceof \DateTimeInterface
            ? $serviceTime->getTimestamp()
            : strtotime((string) $serviceTime));
        $lockKey = "technician_lock:{$technicianId}:{$timeSlot}";

        $redis = Redis::connection();
        if ((string)($redis->get($lockKey) ?? '') === $userId) {
            $redis->del($lockKey);
        }
    }
}
