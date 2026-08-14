<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\process;

use app\model\Notification;
use app\model\UserPoints;
use support\Db;
use support\Log;
use Workerman\Timer;

/**
 * 积分过期定时器
 *
 * 每 60 秒扫描一次已到期未扣减的 earn 流水（expires_at < now 且尚无对应 expire 扣减行），
 * 每笔写入一条 type=expire 扣减行（负数、source=expiry、order_id 记录原 earn 流水 id），
 * 并按用户聚合发站内通知「X 积分已过期」。
 *
 * 防重机制（三层）：
 * 1. 每笔 earn 流水至多一条 expire 扣减行——expire 行 order_id 指向原 earn 流水 id，
 *    写入前在事务内对原 earn 行 lockForUpdate + exists 复验，并发进程在行锁上串行化；
 * 2. 扫描按 id 游标递增分页（BATCH_SIZE 一批），同一进程不重复扫到同一行；
 * 3. 通知仅在扣减行实际写入的扫描轮次产生，重复扫描不会产生重复通知。
 */
class PointsExpiryTimer
{
    /** 扫描间隔（秒） */
    private const SCAN_INTERVAL = 60;

    /** 每批扫描行数 */
    private const BATCH_SIZE = 100;

    public function __construct()
    {
        Timer::add(self::SCAN_INTERVAL, function (): void {
            $this->scanAndExpire();
        });
    }

    /**
     * 扫描已过期 earn 流水并扣减（幂等，可重复调用）
     */
    public function scanAndExpire(): void
    {
        try {
            $cursor = 0;
            $expiredByUser = [];

            while (true) {
                $rows = UserPoints::where('type', 'earn')
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '<', date('Y-m-d H:i:s'))
                    ->where('id', '>', $cursor)
                    ->orderBy('id')
                    ->limit(self::BATCH_SIZE)
                    ->get();

                if ($rows->isEmpty()) {
                    break;
                }

                foreach ($rows as $row) {
                    $points = $this->expireRow($row);
                    if ($points > 0) {
                        $userId = (string) $row->user_id;
                        $expiredByUser[$userId] = ($expiredByUser[$userId] ?? 0) + $points;
                    }
                    $cursor = max($cursor, (int) $row->id);
                }

                if ($rows->count() < self::BATCH_SIZE) {
                    break; // 最后一批
                }
            }

            foreach ($expiredByUser as $userId => $points) {
                $this->notifyExpiry((string) $userId, $points);
            }
        } catch (\Throwable $e) {
            Log::error('[PointsExpiryTimer] Scan error: ' . $e->getMessage()
                . "\n" . $e->getTraceAsString());
        }
    }

    /**
     * 过期单笔 earn 流水（事务内锁行 + 幂等复验）
     *
     * @return int 实际扣减积分（重复扫描返回 0）
     */
    private function expireRow(UserPoints $row): int
    {
        return (int) Db::transaction(function () use ($row): int {
            $locked = UserPoints::where('id', $row->id)
                ->where('type', 'earn')
                ->lockForUpdate()
                ->first();
            if (!$locked) {
                return 0; // 行不存在（理论不可达）
            }

            // 幂等：同原流水已有 expire 扣减行则跳过（并发进程在此行锁上串行）
            $exists = UserPoints::where('order_id', $row->id)
                ->where('type', 'expire')
                ->exists();
            if ($exists) {
                return 0;
            }

            // balance 快照累加：上一条余额 - 本次过期（锁最后一条流水防同用户并发串行）
            $lastBalance = (int) (UserPoints::where('user_id', $row->user_id)
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->value('balance') ?? 0);

            UserPoints::create([
                'id'          => UserPoints::generateId(),
                'user_id'     => $row->user_id,
                'type'        => 'expire',
                'points'      => -$row->points,
                'balance'     => $lastBalance - $row->points,
                'source'      => 'expiry',
                'order_id'    => $row->id,
                'description' => '积分过期扣减（原流水 ' . $row->id . '）',
            ]);

            return (int) $row->points;
        });
    }

    /**
     * 站内通知：X 积分已过期
     */
    private function notifyExpiry(string $userId, int $points): void
    {
        try {
            Db::table('erik_notification')->insert([
                'id'         => Notification::generateId(),
                'user_id'    => $userId,
                'type'       => 'points_expiry',
                'title'      => '积分过期提醒',
                'content'    => '您有 ' . $points . ' 积分已过期。',
                'is_read'    => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            Log::warning('[PointsExpiryTimer] Failed to insert notification: ' . $e->getMessage());
        }
    }
}
