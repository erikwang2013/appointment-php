<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\order\v1\controller;

use app\common\NotificationReminderService;
use app\common\WechatPayService;
use app\model\Order;
use app\model\OrderPayment;
use app\model\OrderStatusLog;
use app\model\Promotion;
use app\model\User;
use app\model\UserPoints;
use app\model\UserWallet;
use app\model\WalletTxn;
use support\Db;
use support\Log;
use Webman\Http\Request;

/**
 * 支付（pay/余额支付/积分抵扣 + 活动懒判定）
 *
 * 统一 per-order 互斥锁（order_lock）；拼团/秒杀存量订单支付时懒判定
 * 活动状态（过期自动取消并释放技师锁）。
 */
trait OrderPayTrait
{
    /**
     * 发起支付
     * POST /api/order/pay/{id}
     */
    public function pay(Request $request, string $id)
    {
        $id = $this->decodeId((string)$id);
        if ($id === null) {
            return $this->error('订单不存在', 404);
        }
        $userId = $request->user_id;

        // B1: 统一 per-order 互斥锁（pay/cancel/refund/支付回调/自动取消共用 order_lock）
        $order = Order::where('user_id', $userId)
            ->where('id', $id)
            ->first();

        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        $lockKey = 'order_lock:' . $order->id;
        $lockToken = $this->acquireLock($lockKey);
        if ($lockToken === null) {
            return $this->error('支付处理中，请稍后再试');
        }

        try {
            // 锁内重新读取订单并校验状态
            $order = Order::where('user_id', $userId)
                ->where('id', $id)
                ->first();
            if (!$order) {
                return $this->error('订单不存在', 404);
            }
            if ($order->status !== Order::STATUS_PENDING) {
                return $this->error('当前订单状态不可支付');
            }

            // 拼团订单懒判定：活动已关闭（到期未成团）→ 自动取消订单，拒绝支付
            if ((int) $order->promotion_id > 0 && $this->isGroupBuyClosed((int) $order->promotion_id)) {
                $order->status = Order::STATUS_CANCELLED;
                $order->cancel_reason = '拼团未成团自动取消';
                $order->cancel_at = now();
                $order->save();
                OrderStatusLog::record($order->id, Order::STATUS_PENDING, Order::STATUS_CANCELLED, '拼团未成团自动取消', 'system');
                $this->releaseTechnicianLock($order);
                return $this->error('拼团未成团，订单已自动取消', 422);
            }

            // 秒杀订单懒判定（存量 flash_sale 促销订单兼容）：活动已过期 → 自动取消订单并释放技师锁，拒绝支付
            if ((int) $order->promotion_id > 0 && $this->isFlashSaleClosed((int) $order->promotion_id)) {
                $order->status = Order::STATUS_CANCELLED;
                $order->cancel_reason = '秒杀活动已结束自动取消';
                $order->cancel_at = now();
                $order->save();
                OrderStatusLog::record($order->id, Order::STATUS_PENDING, Order::STATUS_CANCELLED, '秒杀活动已结束自动取消', 'system');
                $this->releaseTechnicianLock($order);
                return $this->error('秒杀活动已结束，订单已自动取消', 422);
            }

            // 积分抵扣（可选，use_points 缺省 0 走原逻辑）：余额校验 → 抵扣额计算 → 消费流水写入
            $pointsUsed   = 0;
            $pointsOffset = 0.0;
            $usePoints    = (int) $request->input('use_points', 0);
            if ($usePoints > 0) {
                try {
                    $offset = $this->applyPointsOffset($order, $usePoints);
                    $pointsUsed   = $offset['points_used'];
                    $pointsOffset = $offset['offset_amount'];
                } catch (\InvalidArgumentException $e) {
                    return $this->error($e->getMessage(), 422);
                }
            }

            // 支付渠道：wechat=微信支付（默认）/ balance=余额支付
            $payChannel = (string) $request->input('pay_channel', 'wechat');

            // 实际支付金额 = 订单应付 - 积分抵扣（未用积分时为应付原额，与原有行为一致）
            $payAmount = round((float) $order->paid_amount - $pointsOffset, 2);

            // 查找或创建支付记录
            $payment = OrderPayment::where('order_id', $order->id)->first();

            if (!$payment) {
                $payment = OrderPayment::create([
                    'id'         => OrderPayment::generateId(),
                    'order_id'   => $order->id,
                    'payment_no' => OrderPayment::generatePaymentNo(),
                    'pay_type'   => 'wechat',
                    'amount'     => $payAmount,
                    'status'     => OrderPayment::STATUS_PENDING,
                ]);
            } elseif ($payment->status === OrderPayment::STATUS_CLOSED || $payment->status === OrderPayment::STATUS_FAILED) {
                $payment->payment_no = OrderPayment::generatePaymentNo();
                $payment->amount = $payAmount;
                $payment->status = OrderPayment::STATUS_PENDING;
                $payment->save();
            }

            // 全额优惠订单（paid_amount=0）：无支付路径，直接标记支付成功（单一消费点）
            if ((float) $payment->amount <= 0) {
                // 零元直通交易号：FREE + payment_no（payment_no 全局唯一，规避 uk_transaction_id 唯一索引冲突）
                $freeResult = (new WechatPayService())->markOrderPaid($payment->payment_no, 'FREE' . $payment->payment_no, 0.0, 'wechat');
                if (empty($freeResult['success'])) {
                    Log::error('[OrderController] markOrderPaid failed (free order), order_no: ' . $order->order_no . ', error: ' . $freeResult['message']);
                    return $this->error('订单状态更新失败: ' . $freeResult['message']);
                }
                // 订阅消息：支付成功（非阻塞，失败不影响主流程）
                $this->notifySubscribeEvent($order, NotificationReminderService::SCENE_PAY);
                return $this->success([
                    'order_no'      => $order->order_no,
                    'payment_no'    => $payment->payment_no,
                    'amount'        => 0,
                    'status'        => Order::STATUS_PAID,
                    'points_offset' => $pointsOffset,
                    'points_used'   => $pointsUsed,
                ], '订单支付成功');
            }

            // 余额支付：无微信预下单，事务内钱包扣款 + 标记支付成功（order_lock 内串行，幂等）
            if ($payChannel === 'balance') {
                return $this->doBalancePay($order, $payment, $pointsUsed, $pointsOffset);
            }

            // 用户 openid（hidden 字段在服务层可读）
            $user = User::find($order->user_id);
            if (!$user || empty($user->wx_openid)) {
                return $this->error('用户微信信息缺失，无法发起支付');
            }

            // 商品描述取首条订单项名称
            $body = '预约服务';
            $firstItem = $order->items()->first();
            if ($firstItem && $firstItem->name) {
                $body = $firstItem->name;
            }

            // 微信统一下单（金额以元传入，服务内部转分）
            $payService = new WechatPayService();
            $result = $payService->unifiedOrder([
                'openid'       => $user->wx_openid,
                'total_fee'    => (float)$payment->amount,
                'out_trade_no' => $order->order_no,
                'body'         => $body,
                'trade_type'   => 'JSAPI',
            ]);

            if (!empty($result['error'])) {
                Log::error('[OrderController] unifiedOrder failed, order_no: ' . $order->order_no . ', error: ' . $result['error']);
                // payment 保持 pending，允许重试
                return $this->error('支付下单失败: ' . $result['error']);
            }

            return $this->success([
                'prepay_id'     => $result['prepay_id'],
                'sign_params'   => $result['sign_params'],
                'payment_no'    => $payment->payment_no,
                'amount'        => $payment->amount,
                'order_no'      => $order->order_no,
                'points_offset' => $pointsOffset,
                'points_used'   => $pointsUsed,
            ], '支付参数已生成');
        } finally {
            // 释放支付锁（token 校验，仅释放自己持有的锁）
            $this->releaseLock($lockKey, $lockToken);
        }
    }

    /**
     * 余额支付（在 order_lock 内执行，调用方已校验订单 pending）
     *
     * 单事务原子完成：钱包行 lockForUpdate → 余额充足校验（不足 422 余额不足）
     * → balance 扣减 + total_consume 累加 → 写流水(consume, balance_after)
     * → 调 markOrderPaid('balance')（嵌套事务=savepoint，单一消费点：
     * 支付记录 success/pay_type=balance + 原子消费券/次卡 + 订单置 PAID）。
     * 任一步失败整体回滚，绝无「扣款成功但订单未支付」。
     * 幂等：order_lock 串行 + markOrderPaid 状态复验（已支付直接成功）。
     */
    private function doBalancePay(Order $order, OrderPayment $payment, int $pointsUsed = 0, float $pointsOffset = 0.0)
    {
        $amount = (float) $payment->amount;
        $payService = new WechatPayService();

        Db::beginTransaction();
        try {
            // 钱包行锁（不存在则创建；余额扣减与订单支付同事务）
            $wallet = UserWallet::where('user_id', $order->user_id)->lockForUpdate()->first();
            if (!$wallet) {
                $wallet = UserWallet::create([
                    'user_id'        => $order->user_id,
                    'balance'        => 0.00,
                    'total_recharge' => 0.00,
                    'total_consume'  => 0.00,
                ]);
            }

            // 余额充足校验（转分比对，防浮点误差）
            if (UserWallet::toCents((float) $wallet->balance) < UserWallet::toCents($amount)) {
                throw new \InvalidArgumentException('余额不足');
            }

            $wallet->balance = round((float) $wallet->balance - $amount, 2);
            $wallet->total_consume = round((float) $wallet->total_consume + $amount, 2);
            $wallet->save();

            WalletTxn::create([
                'user_id'       => $order->user_id,
                'type'          => WalletTxn::TYPE_CONSUME,
                'amount'        => $amount,
                'balance_after' => (float) $wallet->balance,
                'order_id'      => $order->id,
                'remark'        => '余额支付订单 ' . $order->order_no,
            ]);

            // 单一消费点（嵌套事务）：支付记录置 success(pay_type=balance) + 消费券/次卡 + 订单置 PAID
            $result = $payService->markOrderPaid(
                $payment->payment_no,
                'BALANCE' . $payment->payment_no,
                $amount,
                'balance'
            );
            if (empty($result['success'])) {
                throw new \RuntimeException($result['message'] ?? '订单状态更新失败');
            }

            Db::commit();
        } catch (\InvalidArgumentException $e) {
            Db::rollBack();
            // 余额不足等业务校验文案直接透出（422）
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            Db::rollBack();
            // M3: 内部异常详情仅记日志，对外返回通用文案
            Log::error('[OrderController] balance pay failed, order_no: ' . $order->order_no . ': ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->error('余额支付失败，请稍后重试');
        }

        // 订阅消息：支付成功（非阻塞，失败不影响主流程）
        $this->notifySubscribeEvent($order, NotificationReminderService::SCENE_PAY);

        // WebSocket 实时推送已由 markOrderPaid 内部完成（订单上下文一致），此处不重复推送
        return $this->success([
            'order_no'      => $order->order_no,
            'payment_no'    => $payment->payment_no,
            'amount'        => $amount,
            'status'        => Order::STATUS_PAID,
            'points_offset' => $pointsOffset,
            'points_used'   => $pointsUsed,
        ], '余额支付成功');
    }

    /**
     * 积分抵扣（pay 内调用，order_lock 串行执行）
     *
     * 抵扣规则：points_rate 积分 = 1 元，抵扣金额 = floor(use_points / rate) 元；
     * 抵扣后应付不得低于 0.01 元，超出订单应付的抵扣按应付满减（不浪费用户积分）。
     * 可用积分 = SUM(earn) + SUM(consume/use)——balance 列仅是单次增量快照，不可作为余额依据；
     * consume/use 行 points 存负值，故直接累加即得净余额。
     * 消费流水在微信预支付前写入（幂等：同订单同来源已存在则不重复扣，支付重试安全）。
     *
     * @return array{points_used: int, offset_amount: float}
     * @throws \InvalidArgumentException 积分不足（code 422）
     */
    private function applyPointsOffset(Order $order, int $usePoints): array
    {
        $rate = (int) config('app.points_rate', 100);
        if ($rate <= 0) {
            $rate = 100; // 配置异常兜底
        }

        $earned   = (int) UserPoints::where('user_id', $order->user_id)->where('type', 'earn')->sum('points');
        $consumed = (int) UserPoints::where('user_id', $order->user_id)->whereIn('type', ['consume', 'use'])->sum('points');
        $expired  = (int) UserPoints::where('user_id', $order->user_id)->where('type', 'expire')->sum('points');
        $available = $earned + $consumed + $expired; // consume/use/expire 行为负值
        if ($available <= 0 || $usePoints > $available) {
            throw new \InvalidArgumentException('积分不足', 422);
        }

        $paidFen   = (int) round((float) $order->paid_amount * 100);
        $offsetFen = (int) floor($usePoints / $rate) * 100;
        if ($offsetFen <= 0) {
            throw new \InvalidArgumentException('积分不足', 422);
        }
        // 抵扣后金额 >= 0.01：超出应付部分按应付满减（剩余 1 分）
        $capFen = max(0, $paidFen - 1);
        if ($offsetFen > $capFen) {
            $offsetFen = $capFen;
        }
        if ($offsetFen <= 0) {
            throw new \InvalidArgumentException('积分不足', 422);
        }

        $pointsUsed = (int) round($offsetFen / 100 * $rate);

        // 幂等：同订单 points_offset 流水已存在（支付重试）则不重复扣减
        $exists = UserPoints::where('order_id', $order->id)
            ->where('source', 'points_offset')
            ->exists();
        if (!$exists) {
            // balance = 上一条余额 - 本次扣减（快照累加，锁最后一条流水防同用户并发串行）
            $lastBalance = (int) (UserPoints::where('user_id', $order->user_id)
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->value('balance') ?? 0);

            UserPoints::create([
                'id'          => UserPoints::generateId(),
                'user_id'     => $order->user_id,
                'type'        => 'consume',
                'points'      => -$pointsUsed,
                'balance'     => $lastBalance - $pointsUsed,
                'source'      => 'points_offset',
                'order_id'    => $order->id,
                'description' => '积分抵扣订单 ' . $order->order_no,
            ]);
        }

        return [
            'points_used'   => $pointsUsed,
            'offset_amount' => round($offsetFen / 100, 2),
        ];
    }

    /**
     * 批量取消活动下未支付的拼团订单（拼团到期未成团懒判定时调用，幂等：仅 pending 单受影响）
     */
    private function cancelGroupBuyOrders(int $promotionId, string $reason): void
    {
        Order::where('promotion_id', $promotionId)
            ->where('status', Order::STATUS_PENDING)
            ->update([
                'status'        => Order::STATUS_CANCELLED,
                'cancel_reason' => $reason,
                'cancel_at'     => now(),
            ]);
    }

    /**
     * 拼团活动是否已关闭（懒判定：到期未满员则关闭活动并取消其未支付订单）
     */
    private function isGroupBuyClosed(int $promotionId): bool
    {
        $promotion = Promotion::withCount('participants')->find($promotionId);
        if (!$promotion) {
            return true;
        }
        if ($promotion->status != 1) {
            return true;
        }
        if ($promotion->type === Promotion::TYPE_GROUP_BUY
            && $promotion->end_at < date('Y-m-d H:i:s')
            && $promotion->participants_count < $promotion->min_people) {
            $promotion->status = 0;
            $promotion->save();
            $this->cancelGroupBuyOrders($promotionId, '拼团未成团自动取消');
            return true;
        }
        return false;
    }

    /**
     * 秒杀活动是否已结束（懒判定：过期则关闭活动并取消其未支付订单，与 isGroupBuyClosed 同模式）
     *
     * 旧 flash_sale 促销通道已下线，本判定仅兼容存量促销订单（新秒杀订单无 promotion_id）。
     */
    private function isFlashSaleClosed(int $promotionId): bool
    {
        $promotion = Promotion::find($promotionId);
        if (!$promotion) {
            return true;
        }
        if ($promotion->status != 1) {
            return true;
        }
        if ($promotion->type === Promotion::TYPE_FLASH_SALE
            && $promotion->end_at < date('Y-m-d H:i:s')) {
            $promotion->status = 0;
            $promotion->save();
            $this->cancelGroupBuyOrders($promotionId, '秒杀活动已结束自动取消');
            return true;
        }
        return false;
    }
}
