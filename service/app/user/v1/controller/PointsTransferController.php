<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\user\v1\controller;

use app\common\BaseController;
use app\model\Notification;
use app\model\User;
use app\model\UserPoints;
use app\model\UserPointsTransfer;
use support\Db;
use support\Log;
use support\Redis;
use Webman\Http\Request;

/**
 * 用户积分转赠控制器
 *
 * POST /api/user/points/transfer  转赠积分给其他用户
 * GET  /api/user/points/transfers 转赠记录（发出 + 收到，分页）
 *
 * 规则（幂等 + 防刷）：
 * 1. Redis NX 锁 points_transfer:{userId}（30s TTL）拒绝快速重复提交；
 * 2. 积分余额 = SUM(earn) + SUM(consume/use) + SUM(expire)（balance 列仅单次
 *    增量快照，不可作为余额依据），不足 422；
 * 3. 单日累计转赠限额 DAILY_TRANSFER_LIMIT（10000 积分/日），防刷；
 * 4. 事务内双方最后一条积分流水 lockForUpdate（按用户 ID 升序加锁避免死锁），
 *    同用户并发转赠串行化，锁内复验余额/限额/接收人；
 * 5. 发送方扣减 type=consume source=points_transfer（负值），接收方
 *    type=earn source=points_transfer（正值，含过期时间），并站内通知接收方。
 */
class PointsTransferController extends BaseController
{
    /** 单日累计转赠限额（积分/日） */
    private const DAILY_TRANSFER_LIMIT = 10000;

    /**
     * 转赠积分
     * POST /api/user/points/transfer
     */
    public function transfer(Request $request)
    {
        $userId = (string) $request->user_id;
        $toUserId = $this->decodeId((string) $request->input('to_user_id', ''));
        $points = (int) $request->input('points', 0);

        if ($toUserId === null) {
            return $this->error('接收人不存在', 404);
        }
        if ((string) $toUserId === $userId) {
            return $this->error('不能转赠给自己', 422);
        }
        if ($points < 1) {
            return $this->error('转赠积分必须为正整数', 422);
        }
        if (!User::find($toUserId)) {
            return $this->error('接收人不存在', 404);
        }

        // 并发防护：Redis NX 锁（30s TTL 兜底），锁内事务复验余额/限额/接收人
        $lockKey = "points_transfer:{$userId}";
        if (!Redis::connection()->set($lockKey, $userId, 'EX', 30, 'NX')) {
            return $this->error('转赠处理中，请稍后再试');
        }

        try {
            return $this->doTransfer($userId, $toUserId, $points);
        } finally {
            Redis::connection()->del($lockKey);
        }
    }

    private function doTransfer(string $userId, int $toUserId, int $points)
    {
        $receiverId = (string) $toUserId;

        // 余额/限额预检（锁内再复验一次）
        if ($this->availablePoints($userId) < $points) {
            return $this->error('积分不足', 422);
        }
        if ($this->sentToday($userId) + $points > self::DAILY_TRANSFER_LIMIT) {
            return $this->error('已超出单日转赠限额', 422);
        }

        Db::beginTransaction();
        try {
            // 双方最后一条流水行锁（按用户 ID 升序加锁，避免并发互转死锁），
            // 同用户并发转赠串行化
            $ids = [$userId, $receiverId];
            sort($ids, SORT_STRING);
            foreach ($ids as $lockId) {
                $this->lockPointsRows($lockId);
            }

            // 锁内复验接收人/余额/限额（防止并发间隙穿透）
            if (!User::find($toUserId)) {
                Db::rollBack();
                return $this->error('接收人不存在', 404);
            }
            if ($this->availablePoints($userId) < $points) {
                Db::rollBack();
                return $this->error('积分不足', 422);
            }
            if ($this->sentToday($userId) + $points > self::DAILY_TRANSFER_LIMIT) {
                Db::rollBack();
                return $this->error('已超出单日转赠限额', 422);
            }

            // 发送方扣减：balance = 上一条余额快照 - 本次（已持行锁，读到即最新）
            $senderBalance = (int) ($this->lastBalanceRow($userId)?->balance ?? 0);
            UserPoints::create([
                'id'          => UserPoints::generateId(),
                'user_id'     => $userId,
                'type'        => 'consume',
                'points'      => -$points,
                'balance'     => $senderBalance - $points,
                'source'      => 'points_transfer',
                'description' => '积分转赠',
            ]);

            // 接收方入账：正值，与 earn 同语义（含过期时间，由 PointsExpiryTimer 处理）
            $receiverBalance = (int) ($this->lastBalanceRow($receiverId)?->balance ?? 0);
            UserPoints::create([
                'id'          => UserPoints::generateId(),
                'user_id'     => $receiverId,
                'type'        => 'earn',
                'points'      => $points,
                'balance'     => $receiverBalance + $points,
                'source'      => 'points_transfer',
                'description' => '收到积分转赠',
                'expires_at'  => UserPoints::expiryAt(),
            ]);

            $record = UserPointsTransfer::create([
                'id'           => UserPointsTransfer::generateId(),
                'from_user_id' => $userId,
                'to_user_id'   => $receiverId,
                'points'       => $points,
                'status'       => 'completed',
            ]);

            Db::commit();

            // 站内通知接收方（通知失败不影响转赠结果）
            $this->notifyReceiver($userId, $receiverId, $points);

            return $this->success([
                'transfer_id' => $record->id,
                'points'      => $points,
            ], '转赠成功');
        } catch (\Throwable $e) {
            Db::rollBack();
            Log::error('[PointsTransferController] transfer failed, from: ' . $userId . ', to: ' . $receiverId . ', error: ' . $e->getMessage());
            return $this->error('转赠失败，请稍后重试');
        }
    }

    /**
     * 转赠记录（发出 + 收到，分页）
     * GET /api/user/points/transfers
     */
    public function records(Request $request)
    {
        $userId = (string) $request->user_id;
        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);

        $paginator = UserPointsTransfer::where('from_user_id', $userId)
            ->orWhere('to_user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        foreach ($paginator->getCollection() as $transfer) {
            // PDO 对 BIGINT 列可能返回 int，统一转字符串再比较
            $fromUserId = (string) $transfer->from_user_id;
            $toUserId = (string) $transfer->to_user_id;
            $isSender = $fromUserId === $userId;
            $counterpartyId = $isSender ? $toUserId : $fromUserId;
            $counterparty = User::find($counterpartyId);
            $transfer->direction = $isSender ? 'sent' : 'received';
            $transfer->nickname = $counterparty ? (string) $counterparty->nickname : '';
            $transfer->points = (int) $transfer->points;
        }

        return $this->paginate($paginator);
    }

    /** 当前可用积分 = SUM(earn) + SUM(consume/use/expire)（后三类为负值） */
    private function availablePoints(string $userId): int
    {
        $earned   = (int) UserPoints::where('user_id', $userId)->where('type', 'earn')->sum('points');
        $consumed = (int) UserPoints::where('user_id', $userId)->whereIn('type', ['consume', 'use'])->sum('points');
        $expired  = (int) UserPoints::where('user_id', $userId)->where('type', 'expire')->sum('points');
        return $earned + $consumed + $expired;
    }

    /** 今日已转赠积分（completed 记录） */
    private function sentToday(string $userId): int
    {
        return (int) UserPointsTransfer::where('from_user_id', $userId)
            ->where('status', 'completed')
            ->where('created_at', '>=', date('Y-m-d 00:00:00'))
            ->sum('points');
    }

    /** 锁定用户最后一条积分流水行（无流水则返回 null，锁空集合） */
    private function lockPointsRows(string $userId)
    {
        return UserPoints::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->lockForUpdate()
            ->first();
    }

    /** 读取用户最后一条积分流水（须已持有行锁） */
    private function lastBalanceRow(string $userId)
    {
        return UserPoints::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();
    }

    private function notifyReceiver(string $fromUserId, string $toUserId, int $points): void
    {
        $fromUser = User::find($fromUserId);
        try {
            Notification::create([
                'id'      => Notification::generateId(),
                'user_id' => $toUserId,
                'type'    => 'points_received',
                'title'   => '收到积分转赠',
                'content' => '用户 ' . ($fromUser ? (string) $fromUser->nickname : $fromUserId) . ' 转赠给您 ' . $points . ' 积分',
            ]);
        } catch (\Throwable $e) {
            Log::warning('[PointsTransferController] notifyReceiver failed: ' . $e->getMessage());
        }
    }
}
