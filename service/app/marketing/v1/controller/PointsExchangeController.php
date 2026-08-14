<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\marketing\v1\controller;

use app\common\BaseController;
use app\model\Coupon;
use app\model\GiftCard;
use app\model\PointsExchangeGoods;
use app\model\UserCoupon;
use app\model\UserPoints;
use app\model\UserPointsExchange;
use app\model\UserWallet;
use app\model\WalletTxn;
use support\Db;
use support\Log;
use support\Redis;
use Webman\Http\Request;

/**
 * 积分兑换商城控制器
 *
 * GET  /api/marketing/points-exchange       上架商品列表（含剩余库存与已兑数）
 * POST /api/marketing/points-exchange/{id}  兑换商品
 *
 * 兑换规则（幂等 + 防超兑）：
 * 1. Redis NX 锁 points_exchange:{user}:{goods}（30s TTL）拒绝快速重复提交；
 * 2. 事务内商品行 lockForUpdate + status/stock 复验，防并发超兑；
 * 3. 积分余额 = SUM(earn) + SUM(consume/use)（balance 列仅单次增量快照），
 *    不足 422；扣减流水 type=consume source=exchange（负值，与
 *    points_offset 同模式，保证后续 SUM 口径一致）；
 * 4. 同用户同商品限兑换一次：uk_user_goods 唯一索引兜底并发重复。
 */
class PointsExchangeController extends BaseController
{
    /**
     * 上架商品列表
     * GET /api/marketing/points-exchange
     */
    public function index(Request $request)
    {
        $goodsList = PointsExchangeGoods::where('status', 1)
            ->orderBy('sort', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $exchanged = UserPointsExchange::select('goods_id', Db::raw('count(*) as cnt'))
            ->groupBy('goods_id')
            ->pluck('cnt', 'goods_id');

        $result = [];
        foreach ($goodsList as $goods) {
            $row = $goods->toArray();
            $row['exchanged_count'] = (int) ($exchanged[$goods->id] ?? 0);
            $result[] = $row;
        }

        return $this->success($result);
    }

    /**
     * 兑换商品
     * POST /api/marketing/points-exchange/{id}
     */
    public function exchange(Request $request, string $id)
    {
        $userId = $request->user_id;
        $goodsId = $this->decodeId($id);
        if ($goodsId === null) {
            return $this->error('商品不存在', 404);
        }

        // 并发防护：Redis NX 锁（30s TTL 兜底），锁内事务复验库存/幂等
        $lockKey = "points_exchange:{$userId}:{$goodsId}";
        if (!Redis::connection()->set($lockKey, (string) $userId, 'EX', 30, 'NX')) {
            return $this->error('兑换处理中，请稍后再试');
        }

        try {
            return $this->doExchange($userId, $goodsId);
        } finally {
            Redis::connection()->del($lockKey);
        }
    }

    private function doExchange(string $userId, int $goodsId)
    {
        $goods = PointsExchangeGoods::find($goodsId);
        if (!$goods) {
            return $this->error('商品不存在', 404);
        }

        // 幂等预检：同用户同商品限兑换一次
        if (UserPointsExchange::where('user_id', $userId)->where('goods_id', $goodsId)->exists()) {
            return $this->error('您已兑换过该商品');
        }

        // 积分余额 SUM 聚合（balance 列仅是单次增量快照，不可作为余额依据）
        $earned    = (int) UserPoints::where('user_id', $userId)->where('type', 'earn')->sum('points');
        $consumed  = (int) UserPoints::where('user_id', $userId)->whereIn('type', ['consume', 'use'])->sum('points');
        $expired   = (int) UserPoints::where('user_id', $userId)->where('type', 'expire')->sum('points');
        $available = $earned + $consumed + $expired; // consume/use/expire 行为负值
        if ($available < $goods->points_cost) {
            return $this->error('积分不足', 422);
        }

        Db::beginTransaction();
        try {
            // 商品行锁 + 状态/库存复验（并发防超兑，锁内再校验一次）
            $goods = PointsExchangeGoods::where('id', $goodsId)->lockForUpdate()->first();
            if (!$goods || $goods->status != 1) {
                Db::rollBack();
                return $this->error('商品已下架', 422);
            }
            if ($goods->stock <= 0) {
                Db::rollBack();
                return $this->error('商品已兑完', 422);
            }

            // 锁内幂等复验（并发双击由 uk_user_goods 唯一索引兜底）
            if (UserPointsExchange::where('user_id', $userId)->where('goods_id', $goodsId)->exists()) {
                Db::rollBack();
                return $this->error('您已兑换过该商品');
            }

            // 扣减商品库存
            $goods->decrement('stock');

            // 扣减积分：balance = 上一条余额 - 本次扣减（锁最后一条流水防同用户并发串行）
            $lastBalance = (int) (UserPoints::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->value('balance') ?? 0);
            UserPoints::create([
                'id'          => UserPoints::generateId(),
                'user_id'     => $userId,
                'type'        => 'consume',
                'points'      => -$goods->points_cost,
                'balance'     => $lastBalance - $goods->points_cost,
                'source'      => 'exchange',
                'description' => '积分兑换 ' . $goods->name,
            ]);

            // 按类型发放兑换结果（优惠券/钱包/礼品卡，事务内原子）
            $result = $this->grant($userId, $goods);

            $record = UserPointsExchange::create([
                'id'          => UserPointsExchange::generateId(),
                'user_id'     => $userId,
                'goods_id'    => $goods->id,
                'goods_name'  => $goods->name,
                'points_cost' => $goods->points_cost,
                'result'      => json_encode($result, JSON_UNESCAPED_UNICODE),
            ]);

            Db::commit();

            return $this->success([
                'exchange_id' => $record->id,
                'goods_id'    => $goods->id,
                'goods_name'  => $goods->name,
                'points_cost' => $goods->points_cost,
                'result'      => $result,
            ], '兑换成功');
        } catch (\Illuminate\Database\QueryException $e) {
            Db::rollBack();
            // uk_user_goods 唯一键冲突：并发重复兑换，幂等返回已兑换
            if (($e->errorInfo[1] ?? null) === 1062) {
                return $this->error('您已兑换过该商品');
            }
            Log::error('[PointsExchangeController] exchange failed, user: ' . $userId . ', goods: ' . $goodsId . ', error: ' . $e->getMessage());
            return $this->error('兑换失败，请稍后重试');
        } catch (\Throwable $e) {
            Db::rollBack();
            Log::error('[PointsExchangeController] exchange failed, user: ' . $userId . ', goods: ' . $goodsId . ', error: ' . $e->getMessage());
            return $this->error('兑换失败，请稍后重试');
        }
    }

    /**
     * 发放兑换结果（必须在事务内调用）
     *
     * coupon    → 给用户发券（UserCoupon available，不与优惠券自身 remain_qty 耦合，
     *              兑换库存由商品表 stock 独立管控）
     * wallet    → 钱包入账（钱包行 lockForUpdate + WalletTxn type=points_exchange）
     * gift_card → 生成未使用现金礼品卡，返回卡密，用户走现有 /gift-cards/redeem 兑现
     *
     * @return array 兑换结果快照
     * @throws \RuntimeException 类型无效或关联券不存在（随事务整体回滚）
     */
    private function grant(string $userId, PointsExchangeGoods $goods): array
    {
        switch ($goods->type) {
            case 'coupon':
                $coupon = Coupon::find((int) $goods->value);
                if (!$coupon) {
                    throw new \RuntimeException('兑换优惠券不存在');
                }
                $userCoupon = new UserCoupon();
                $userCoupon->id = UserCoupon::generateId();
                $userCoupon->user_id = $userId;
                $userCoupon->coupon_id = $coupon->id;
                $userCoupon->status = 'available';
                $userCoupon->received_at = date('Y-m-d H:i:s');
                $userCoupon->save();
                return [
                    'type'           => 'coupon',
                    'user_coupon_id' => $userCoupon->id,
                    'coupon_id'      => $coupon->id,
                    'name'           => $coupon->name,
                ];

            case 'wallet':
                $wallet = UserWallet::where('user_id', $userId)->lockForUpdate()->first();
                if (!$wallet) {
                    $wallet = UserWallet::create([
                        'user_id'        => $userId,
                        'balance'        => 0.00,
                        'total_recharge' => 0.00,
                        'total_consume'  => 0.00,
                    ]);
                }
                $amount = (float) $goods->value;
                $wallet->balance = round((float) $wallet->balance + $amount, 2);
                $wallet->save();
                WalletTxn::create([
                    'user_id'       => $userId,
                    'type'          => WalletTxn::TYPE_POINTS_EXCHANGE,
                    'amount'        => $amount,
                    'balance_after' => (float) $wallet->balance,
                    'remark'        => '积分兑换 ' . $goods->name,
                ]);
                return [
                    'type'          => 'wallet',
                    'amount'        => $amount,
                    'balance_after' => (float) $wallet->balance,
                ];

            case 'gift_card':
                $code = strtoupper(substr(md5(uniqid((string) random_int(0, 99999), true)), 0, 12));
                $card = new GiftCard();
                $card->id = GiftCard::generateId();
                $card->code = $code;
                $card->type = 'cash';
                $card->amount = (float) $goods->value;
                $card->gift_name = $goods->name;
                $card->status = 'unused';
                $card->save();
                return [
                    'type'   => 'gift_card',
                    'code'   => $code,
                    'amount' => (float) $goods->value,
                ];

            default:
                throw new \RuntimeException('商品类型无效');
        }
    }
}
