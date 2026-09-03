<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\wallet\v1\controller;

use app\common\BaseController;
use app\common\Money;
use app\model\Notification;
use app\model\User;
use app\model\UserWallet;
use app\model\WalletTransfer;
use app\model\WalletTxn;
use support\Db;
use support\Log;
use support\Redis;
use Webman\Http\Request;

/**
 * 用户间余额转账控制器
 *
 * POST /api/wallet/transfer           发起转账
 * GET  /api/wallet/transfers          转账记录分页（发出+收到）
 * GET  /api/wallet/transfers/{id}     转账记录详情
 *
 * 并发/幂等设计：
 * - Redis NX 锁 wallet_transfer:{from_user_id}（30s 兜底）串行化同一转出方
 *   的并发转账；锁内重读余额与单日限额。
 * - 事务内按双方 user_id 升序 lockForUpdate 钱包行（固定顺序防死锁），
 *   余额校验为行锁读，杜绝超扣。
 * - 可选 client_token：成功后 SETNX appointment:wallet_transfer_token:{token}（24h），
 *   同 token 重复提交直接拒绝，失败请求不落 token 可重试。
 */
class WalletTransferController extends BaseController
{
    /** 单笔最低（元） */
    private const MIN_AMOUNT = 1.00;
    /** 单笔最高（元） */
    private const MAX_AMOUNT = 1000.00;
    /** 单日累计上限（元） */
    private const DAILY_LIMIT = 5000.00;
    /** Redis 锁 TTL（秒） */
    private const LOCK_TTL = 30;
    /** client_token 幂等键 TTL（秒，24h） */
    private const TOKEN_TTL = 86400;

    /**
     * 发起转账
     *
     * body: { to_user_id: string(hashid), amount: number(元), remark?: string, client_token?: string }
     */
    public function transfer(Request $request)
    {
        $senderId = (string) $request->user_id;

        // ── 接收人校验（hashid 解码失败/用户不存在按 404 处理）──
        $toUserId = $this->decodeId((string) $request->input('to_user_id', ''));
        if ($toUserId === null) {
            return $this->error('接收人不存在', 404);
        }
        if (!User::find($toUserId)) {
            return $this->error('接收人不存在', 404);
        }
        if ((string) $toUserId === $senderId) {
            return $this->error('不能转账给自己', 422);
        }

        // ── 金额校验（转分比对，禁止浮点直接比较）──
        $amountCents = UserWallet::toCents((float) $request->input('amount', 0));
        if ($amountCents < (int) round(self::MIN_AMOUNT * 100) || $amountCents > (int) round(self::MAX_AMOUNT * 100)) {
            return $this->error('转账金额需在 1 ~ 1000 元之间', 422);
        }
        $amount = $amountCents / 100;

        $remark = trim((string) $request->input('remark', ''));
        if (mb_strlen($remark) > 100) {
            $remark = mb_substr($remark, 0, 100);
        }
        $clientToken = trim((string) $request->input('client_token', ''));

        // ── Redis 互斥锁：同一转出方串行化 ──
        $lockKey = 'wallet_transfer:' . $senderId;
        $lockToken = $this->acquireLock($lockKey);
        if ($lockToken === null) {
            return $this->error('操作处理中，请稍后再试');
        }

        try {
            // 幂等：同 client_token 已成功过则拒绝（失败请求不落 token，可重试）
            if ($clientToken !== '' && (string) (Redis::connection()->get('appointment:wallet_transfer_token:' . $clientToken) ?? '') !== '') {
                return $this->error('请勿重复提交', 422);
            }

            // 单日累计限额（锁内统计，防并发绕过）
            $todayCents = (int) Db::table('appointment_wallet_transfer')
                ->where('from_user_id', $senderId)
                ->where('status', WalletTransfer::STATUS_COMPLETED)
                ->where('created_at', '>=', date('Y-m-d 00:00:00'))
                ->sum(Db::raw('amount * 100'));
            if ($todayCents + $amountCents > (int) round(self::DAILY_LIMIT * 100)) {
                return $this->error('今日累计转账已达上限（5000 元）', 422);
            }

            $transfer = $this->doTransfer($senderId, (string) $toUserId, $amount, $amountCents, $remark);
            if ($transfer === null) {
                return $this->error('余额不足', 422);
            }

            if ($clientToken !== '') {
                Redis::connection()->set('appointment:wallet_transfer_token:' . $clientToken, (string) $transfer->id, 'EX', self::TOKEN_TTL, 'NX');
            }

            $wallet = UserWallet::where('user_id', $senderId)->first();
            return $this->success([
                'transfer_id' => $transfer->id,
                'to_user_id'  => $transfer->to_user_id,
                'amount'      => $transfer->amount,
                'balance'     => $wallet ? (float) $wallet->balance : 0.0,
            ], '转账成功');
        } catch (\Throwable $e) {
            Log::error('[WalletTransfer] transfer failed, from: ' . $senderId . ', to: ' . $toUserId . ': ' . $e->getMessage());
            return $this->error('转账失败，请稍后重试');
        } finally {
            $this->releaseLock($lockKey, $lockToken);
        }
    }

    /**
     * 转账记录分页（发出+收到）
     *
     * GET /api/wallet/transfers?per_page=
     */
    public function transfers(Request $request)
    {
        $userId = (string) $request->user_id;

        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);
        $paginator = WalletTransfer::where('from_user_id', $userId)
            ->orWhere('to_user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        foreach ($paginator->getCollection() as $item) {
            $item->direction = (string) $item->from_user_id === $userId ? 'out' : 'in';
        }

        return $this->paginate($paginator);
    }

    /**
     * 转账记录详情（仅转出方/接收方可看）
     *
     * GET /api/wallet/transfers/{id}
     */
    public function show(Request $request, string $id)
    {
        $id = $this->decodeId((string) $id);
        if ($id === null) {
            return $this->error('转账记录不存在', 404);
        }
        $userId = (string) $request->user_id;

        $transfer = WalletTransfer::where('id', $id)
            ->where(function ($q) use ($userId) {
                $q->where('from_user_id', $userId)->orWhere('to_user_id', $userId);
            })
            ->first();
        if (!$transfer) {
            return $this->error('转账记录不存在', 404);
        }

        $transfer->direction = (string) $transfer->from_user_id === $userId ? 'out' : 'in';
        return $this->success($transfer);
    }

    /**
     * 事务内执行转账（双方钱包行锁 + 双流水 + 转账记录 + 接收方通知）
     *
     * @return WalletTransfer|null 余额不足返回 null
     */
    private function doTransfer(string $senderId, string $receiverId, float $amount, int $amountCents, string $remark): ?WalletTransfer
    {
        Db::beginTransaction();
        try {
            // 固定顺序锁双方钱包（按 user_id 升序，防交叉转账死锁）
            $lockOrder = [$senderId, $receiverId];
            sort($lockOrder);
            $wallets = [];
            foreach ($lockOrder as $uid) {
                $wallets[$uid] = $this->lockWallet($uid);
            }

            $senderWallet = $wallets[$senderId];
            if (UserWallet::toCents((float) $senderWallet->balance) < $amountCents) {
                Db::rollBack();
                return null;
            }

            $receiverWallet = $wallets[$receiverId];
            // 余额增减走 string 域，落库前还原 number（值已 round 2 位，float 化无损）
            $senderWallet->balance   = (float) Money::round(Money::sub((string) $senderWallet->balance, (string) $amount), 2);
            $receiverWallet->balance = (float) Money::round(Money::add((string) $receiverWallet->balance, (string) $amount), 2);
            $senderWallet->save();
            $receiverWallet->save();

            WalletTxn::create([
                'user_id'       => $senderId,
                'type'          => WalletTxn::TYPE_TRANSFER_OUT,
                'amount'        => $amount,
                'balance_after' => (float) $senderWallet->balance,
                'remark'        => '余额转出' . ($remark !== '' ? '（' . $remark . '）' : ''),
            ]);
            WalletTxn::create([
                'user_id'       => $receiverId,
                'type'          => WalletTxn::TYPE_TRANSFER_IN,
                'amount'        => $amount,
                'balance_after' => (float) $receiverWallet->balance,
                'remark'        => '余额转入' . ($remark !== '' ? '（' . $remark . '）' : ''),
            ]);

            $transfer = WalletTransfer::create([
                'from_user_id' => $senderId,
                'to_user_id'   => $receiverId,
                'amount'       => $amount,
                'status'       => WalletTransfer::STATUS_COMPLETED,
                'remark'       => $remark,
            ]);

            // 站内通知与入账同事务；写入失败只记日志，不阻塞主流程
            try {
                Notification::create([
                    'id'      => Notification::generateId(),
                    'user_id' => $receiverId,
                    'type'    => 'balance_received',
                    'title'   => '余额到账',
                    'content' => '您收到一笔 ' . number_format($amount, 2, '.', '') . ' 元的余额转账',
                    'is_read' => 0,
                ]);
            } catch (\Throwable $e) {
                Log::warning('[WalletTransfer] notification write failed, transfer: ' . $transfer->id . ': ' . $e->getMessage());
            }

            Db::commit();
            return $transfer;
        } catch (\Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }

    /**
     * 行锁读取用户钱包，缺失则创建（并发撞唯一键时重读等待）
     */
    private function lockWallet(string $userId): UserWallet
    {
        $wallet = UserWallet::where('user_id', $userId)->lockForUpdate()->first();
        if ($wallet) {
            return $wallet;
        }
        try {
            return UserWallet::create([
                'user_id'        => $userId,
                'balance'        => 0.00,
                'total_recharge' => 0.00,
                'total_consume'  => 0.00,
            ]);
        } catch (\Throwable) {
            $wallet = UserWallet::where('user_id', $userId)->lockForUpdate()->first();
            if (!$wallet) {
                throw new \RuntimeException('钱包初始化失败: ' . $userId);
            }
            return $wallet;
        }
    }

    /**
     * Redis NX 分布式锁
     */
    private function acquireLock(string $key, int $expireSeconds = self::LOCK_TTL): ?string
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
}
