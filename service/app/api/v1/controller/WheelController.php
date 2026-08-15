<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\model\LuckyWheel;
use app\model\UserPoints;
use app\model\UserWallet;
use app\model\WalletTxn;
use app\model\WheelRecord;
use support\Db;
use support\Log;
use support\Redis;
use Webman\Http\Request;

/**
 * 积分幸运转盘控制器
 *
 * GET  /api/wheel/prizes  转盘奖品列表（仅上架，隐藏权重/库存，防博弈）
 * POST /api/wheel/spin    抽奖一次（扣积分 → 按权重随机 → 发放）
 * GET  /api/wheel/records 我的抽奖记录（分页）
 *
 * 规则（与 PointsTransferController 一致的并发/幂等策略）：
 * 1. Redis NX 锁 lucky_wheel:{userId}（30s TTL）拒绝快速重复提交；
 * 2. 积分余额 = SUM(earn) + SUM(consume/use) + SUM(expire)，不足 422；
 * 3. 事务内最后一条积分流水 lockForUpdate（同用户并发抽奖串行化），
 *    锁内复验余额与 client_token 幂等；奖品行锁复验状态/权重/库存防超抽；
 * 4. 权重=0 奖品不可中；随机用 random_int（安全随机）；
 * 5. 发放：points → erik_user_points earn 流水（含过期时间）；balance →
 *    erik_user_wallet 余额 + erik_wallet_txn 流水（lucky_wheel）；coupon →
 *    仅记录中奖（转盘奖品无优惠券模板 ID，发放需人工补发，响应标记
 *    prize.status=pending）；none 仅记录不发放；
 * 6. 幂等：可选 client_token，同用户同令牌仅抽一次（唯一索引兜底）。
 */
class WheelController extends BaseController
{
    /** 可中奖奖品类型（none 即谢谢参与，仅记录） */
    private const WIN_TYPES = ['points', 'coupon', 'balance'];

    /**
     * 转盘奖品列表（仅上架，按 sort/id 排序）
     * GET /api/wheel/prizes
     */
    public function prizes(Request $request)
    {
        $list = LuckyWheel::where('status', 1)
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function (LuckyWheel $prize) {
                return [
                    'id'          => (string) $prize->id,
                    'name'        => (string) $prize->name,
                    'cost_points' => (int) $prize->cost_points,
                    'prize_type'  => (string) $prize->prize_type,
                    'prize_value' => (float) $prize->prize_value,
                ];
            });

        return $this->success(['list' => $list]);
    }

    /**
     * 抽奖一次
     * POST /api/wheel/spin
     */
    public function spin(Request $request)
    {
        $userId = (string) $request->user_id;
        $clientToken = trim((string) $request->input('client_token', ''));

        // 幂等预检：同请求令牌已抽过则直接返回上次结果
        if ($clientToken !== '') {
            $existing = WheelRecord::where('user_id', $userId)
                ->where('client_token', $clientToken)
                ->first();
            if ($existing) {
                return $this->success($this->decorate($existing), '重复请求，返回上次抽奖结果');
            }
        }

        // 并发防护：Redis NX 锁（30s TTL 兜底），锁内事务复验
        $lockKey = "lucky_wheel:{$userId}";
        if (!Redis::connection()->set($lockKey, $userId, 'EX', 30, 'NX')) {
            return $this->error('抽奖处理中，请稍后再试');
        }

        try {
            return $this->doSpin($userId, $clientToken);
        } finally {
            Redis::connection()->del($lockKey);
        }
    }

    private function doSpin(string $userId, string $clientToken)
    {
        // 可中奖池：上架 + 权重>0 + 库存（-1 不限，否则需剩余）
        $prizes = LuckyWheel::where('status', 1)
            ->where('weight', '>', 0)
            ->where(function ($q) {
                $q->where('stock', -1)->orWhere('stock', '>', 0);
            })
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($prizes->isEmpty()) {
            return $this->error('奖品已抽完，请稍后再试', 422);
        }
        // 单次消耗积分取奖品列表首项（同一转盘奖品应配置一致的单次消耗）
        $cost = (int) $prizes->first()->cost_points;
        if ($cost < 1) {
            return $this->error('转盘配置错误，请联系客服', 500);
        }
        if ($this->availablePoints($userId) < $cost) {
            return $this->error('积分不足', 422);
        }

        Db::beginTransaction();
        try {
            // 用户最后一条积分流水行锁（同用户并发抽奖串行化）
            $lastBalance = (int) (UserPoints::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->value('balance') ?? 0);

            // 锁内复验余额
            if ($this->availablePoints($userId) < $cost) {
                Db::rollBack();
                return $this->error('积分不足', 422);
            }

            // 锁内幂等复验（防 Redis 锁过期后的并发重复，唯一索引兜底）
            if ($clientToken !== '') {
                $dup = WheelRecord::where('user_id', $userId)
                    ->where('client_token', $clientToken)
                    ->lockForUpdate()
                    ->first();
                if ($dup) {
                    Db::rollBack();
                    return $this->success($this->decorate($dup), '重复请求，返回上次抽奖结果');
                }
            }

            // 按权重随机抽奖（random_int 安全随机；权重=0 已排除）
            $drawn = $this->draw($prizes);
            if ($drawn === null) {
                Db::rollBack();
                return $this->error('奖品已抽完，请稍后再试', 422);
            }

            // 奖品行锁复验（有限库存并发防超抽）
            $prize = LuckyWheel::where('id', $drawn->id)->lockForUpdate()->first();
            if (!$prize || (int) $prize->status !== 1 || (int) $prize->weight <= 0) {
                Db::rollBack();
                return $this->error('奖品已下架', 422);
            }
            if ((int) $prize->stock !== -1 && (int) $prize->stock <= 0) {
                Db::rollBack();
                return $this->error('该奖品已抽完', 422);
            }
            if ((int) $prize->stock > 0) {
                $prize->decrement('stock');
            }

            // 扣减积分：balance = 上一条余额快照 - 本次
            UserPoints::create([
                'id'          => UserPoints::generateId(),
                'user_id'     => $userId,
                'type'        => 'consume',
                'points'      => -$cost,
                'balance'     => $lastBalance - $cost,
                'source'      => 'lucky_wheel',
                'description' => '幸运转盘抽奖',
            ]);

            // 发放奖励（none 仅记录不发放）
            $result = 'lose';
            $grant = null;
            if (in_array($prize->prize_type, self::WIN_TYPES, true)) {
                $result = 'win';
                $grant = $this->grant($userId, $prize);
            }

            $record = WheelRecord::create([
                'id'           => WheelRecord::generateId(),
                'user_id'      => $userId,
                'wheel_id'     => $prize->id,
                'prize_type'   => (string) $prize->prize_type,
                'prize_value'  => (float) $prize->prize_value,
                'result'       => $result,
                'client_token' => $clientToken !== '' ? $clientToken : null,
            ]);

            Db::commit();

            return $this->success([
                'id'          => (string) $record->id,
                'prize_id'    => (string) $prize->id,
                'name'        => (string) $prize->name,
                'prize_type'  => (string) $prize->prize_type,
                'prize_value' => (float) $prize->prize_value,
                'result'      => $result,
                'cost_points' => $cost,
                'grant'       => $grant,
            ], $result === 'win' ? '恭喜中奖' : '谢谢参与');
        } catch (\Throwable $e) {
            Db::rollBack();
            Log::error('[WheelController] spin failed, user: ' . $userId . ', error: ' . $e->getMessage());
            return $this->error('抽奖失败，请稍后重试');
        }
    }

    /**
     * 我的抽奖记录（分页）
     * GET /api/wheel/records
     */
    public function records(Request $request)
    {
        $userId = (string) $request->user_id;
        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);

        $paginator = WheelRecord::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        foreach ($paginator->getCollection() as $record) {
            $record->prize_name = (string) (LuckyWheel::find($record->wheel_id)?->name ?? '');
            $record->prize_value = (float) $record->prize_value;
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

    /** 按权重随机抽取一个奖品（权重总和=0 返回 null；须先过滤权重=0） */
    private function draw($prizes): ?LuckyWheel
    {
        $total = 0;
        foreach ($prizes as $prize) {
            $total += (int) $prize->weight;
        }
        if ($total <= 0) {
            return null;
        }
        $point = random_int(1, $total);
        foreach ($prizes as $prize) {
            $point -= (int) $prize->weight;
            if ($point <= 0) {
                return $prize;
            }
        }
        return null;
    }

    /**
     * 发放中奖奖励（须在事务内调用）
     * points → earn 流水（含过期时间）；balance → 钱包余额 + 流水；
     * coupon → 仅标记待发放（转盘奖品只有面额无优惠券模板 ID，
     * 需人工补发，此处不落 UserCoupon）
     */
    private function grant(string $userId, LuckyWheel $prize): array
    {
        $value = (float) $prize->prize_value;

        if ($prize->prize_type === 'points') {
            $points = max(1, (int) round($value));
            $balance = (int) (UserPoints::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->value('balance') ?? 0);
            UserPoints::create([
                'id'          => UserPoints::generateId(),
                'user_id'     => $userId,
                'type'        => 'earn',
                'points'      => $points,
                'balance'     => $balance + $points,
                'source'      => 'lucky_wheel',
                'description' => '幸运转盘中奖：' . $prize->name,
                'expires_at'  => UserPoints::expiryAt(),
            ]);
            return ['type' => 'points', 'points' => $points, 'status' => 'granted'];
        }

        if ($prize->prize_type === 'balance') {
            // 钱包行锁（不存在则创建；余额入账与流水原子）
            $wallet = UserWallet::where('user_id', $userId)->lockForUpdate()->first();
            if (!$wallet) {
                $wallet = UserWallet::create([
                    'user_id'        => $userId,
                    'balance'        => 0.00,
                    'total_recharge' => 0.00,
                    'total_consume'  => 0.00,
                ]);
            }
            $wallet->balance = round((float) $wallet->balance + $value, 2);
            $wallet->save();

            WalletTxn::create([
                'user_id'       => $userId,
                'type'          => WalletTxn::TYPE_LUCKY_WHEEL,
                'amount'        => $value,
                'balance_after' => (float) $wallet->balance,
                'remark'        => '幸运转盘中奖：' . $prize->name,
            ]);
            return ['type' => 'balance', 'amount' => $value, 'status' => 'granted'];
        }

        if ($prize->prize_type === 'coupon') {
            return ['type' => 'coupon', 'amount' => $value, 'status' => 'pending', 'note' => '优惠券待人工发放'];
        }

        return ['type' => 'none'];
    }

    /** 组装抽奖记录返回（含奖品名） */
    private function decorate(WheelRecord $record): array
    {
        return [
            'id'          => (string) $record->id,
            'prize_id'    => (string) $record->wheel_id,
            'name'        => (string) (LuckyWheel::find($record->wheel_id)?->name ?? ''),
            'prize_type'  => (string) $record->prize_type,
            'prize_value' => (float) $record->prize_value,
            'result'      => (string) $record->result,
            'created_at'  => (string) $record->created_at,
        ];
    }
}
