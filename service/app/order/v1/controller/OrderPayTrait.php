<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\order\v1\controller;

use app\common\Money;
use app\common\NotificationReminderService;
use app\common\PendingOrderCancelService;
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
use support\Redis;
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
                // 当前 pay 已持有 order_lock，直接走统一取消流程（不再重复取锁）
                PendingOrderCancelService::cancelLocked($order, '拼团未成团自动取消', '拼团未成团自动取消');
                return $this->error('拼团未成团，订单已自动取消', 422);
            }

            // 秒杀订单懒判定（存量 flash_sale 促销订单兼容）：活动已过期 → 自动取消订单，拒绝支付
            if ((int) $order->promotion_id > 0 && $this->isFlashSaleClosed((int) $order->promotion_id)) {
                PendingOrderCancelService::cancelLocked($order, '秒杀活动已结束自动取消', '秒杀活动已结束自动取消');
                return $this->error('秒杀活动已结束，订单已自动取消', 422);
            }

            // 微信预支付标记：某次统一下单前锁定支付金额（2h，与微信 prepay 有效期一致）。
            // 之后不得再改金额，否则旧 prepay 与新 prepay/回调金额不一致，可能造成扣款无法入账。
            // Redis 为主缓存，DB payment.transaction_id='PREPAY:xx' 为持久兜底（Redis 重启/evict 后仍可回读）。
            $prepayKey = 'order_prepay:' . $order->id;
            $payment = OrderPayment::where('order_id', $order->id)->first();
            $prepayAmount = Redis::connection()->get($prepayKey);
            if (($prepayAmount === false || $prepayAmount === null || $prepayAmount === '') && $payment) {
                $txn = (string) $payment->transaction_id;
                if (str_starts_with($txn, 'PREPAY:')) {
                    $prepayAmount = substr($txn, 7);
                    try {
                        Redis::connection()->set($prepayKey, (string) $prepayAmount, 'EX', 7200);
                    } catch (\Throwable) {
                    }
                }
            }
            $hasPrepay = $prepayAmount !== false && $prepayAmount !== null && $prepayAmount !== '';

            // 积分抵扣（可选，use_points 缺省 0 走原逻辑）：余额校验 → 抵扣额计算 → 消费流水写入
            $pointsUsed   = 0;
            $pointsOffset = 0.0;
            $usePoints    = (int) $request->input('use_points', 0);
            // 支付重试可能不再携带 use_points：已有抵扣流水时必须沿用，否则会把实付金额改回原价
            $hasStoredOffset = UserPoints::where('order_id', $order->id)
                ->where('source', 'points_offset')
                ->where('type', 'consume')
                ->exists();

            // 无标记但涉及积分抵扣：先向微信核对是否已有旧 prepay（存量订单/旧版本遗留），
            // 有则锁定原金额并退还本次/历史积分，避免改金额后旧 prepay 仍可支付造成错账。
            if (!$hasPrepay && $payment && ($usePoints > 0 || $hasStoredOffset)) {
                $probe = new WechatPayService();
                if ($probe->isConfigured()) {
                    $query = $probe->queryOrder($order->order_no);
                    if (!empty($query['error'])) {
                        return $this->error('无法确认支付状态，请稍后重试', 422);
                    }
                    $resultCode = (string) ($query['result_code'] ?? '');
                    $tradeState = (string) ($query['trade_state'] ?? '');
                    $errCode = (string) ($query['err_code'] ?? '');

                    if ($resultCode === 'SUCCESS' && $tradeState === 'SUCCESS') {
                        $paidFee = (float) ($query['total_fee'] ?? 0) / 100;
                        if ($paidFee > 0) {
                            // 实付金额与当前支付记录不一致时，说明旧 prepay 未包含积分抵扣，退还积分并对齐金额
                            if (Money::toFen($paidFee) !== Money::toFen((float) $payment->amount)) {
                                if ($hasStoredOffset) {
                                    UserPoints::refundOrderOffset($order, true);
                                }
                                $payment->amount = $paidFee;
                            }
                            $payment->transaction_id = 'PREPAY:' . $paidFee;
                            $payment->save();
                            $probe->markOrderPaid(
                                $payment->payment_no,
                                (string) ($query['transaction_id'] ?? ''),
                                $paidFee,
                                'wechat'
                            );
                        }
                        return $this->error('订单已支付，请刷新订单状态', 422);
                    }

                    if ($resultCode === 'SUCCESS' && in_array($tradeState, ['NOTPAY', 'USERPAYING'], true)) {
                        $existingFee = (float) ($query['total_fee'] ?? 0) / 100;
                        if ($existingFee > 0) {
                            // 旧 prepay 金额与当前记录一致时保留积分抵扣；不一致时退积分并对齐到旧 prepay 金额
                            if (Money::toFen($existingFee) !== Money::toFen((float) $payment->amount)) {
                                if ($hasStoredOffset) {
                                    UserPoints::refundOrderOffset($order, true);
                                }
                                $payment->amount = $existingFee;
                            }
                            $payment->transaction_id = 'PREPAY:' . $existingFee;
                            $payment->save();
                            try {
                                Redis::connection()->set($prepayKey, (string) $existingFee, 'EX', 7200);
                            } catch (\Throwable) {
                            }
                            return $this->error('订单已有微信支付单，请重试支付', 422);
                        }
                    }

                    if ($resultCode === 'FAIL' && $errCode !== 'ORDERNOTEXIST') {
                        return $this->error('无法确认支付状态，请稍后重试', 422);
                    }
                }
            }

            // 拼团/秒杀订单创建时已禁止叠加优惠，支付时同样禁止新增积分抵扣（已存在的抵扣流水按重试沿用）
            if ((int) $order->promotion_id > 0 && $usePoints > 0 && !$hasStoredOffset) {
                return $this->error('拼团订单不支持积分抵扣', 422);
            }
            // 已发起微信支付后禁止追加积分抵扣：金额一旦变更，旧 prepay 仍可按原金额支付
            if ($hasPrepay && $usePoints > 0 && !$hasStoredOffset) {
                return $this->error('订单已发起支付，不能追加积分抵扣，请取消后重新下单', 422);
            }
            if ($usePoints > 0 || $hasStoredOffset) {
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
            $payAmount = (float) Money::round(Money::sub((string) $order->paid_amount, (string) $pointsOffset), 2);
            // 已发起微信支付：沿用首次下单金额，不再因积分/配置变化改写金额
            if ($hasPrepay) {
                $payAmount = (float) Money::round((string) $prepayAmount, 2);
                $pointsOffset = max(0.0, (float) Money::round(Money::sub((string) $order->paid_amount, (string) $payAmount), 2));
            }

            // 创建/更新支付记录（$payment 已在预支付标记段加载）
            if (!$payment) {
                $payment = OrderPayment::create([
                    'id'         => OrderPayment::generateId(),
                    'order_id'   => $order->id,
                    'payment_no' => OrderPayment::generatePaymentNo(),
                    'pay_type'   => 'wechat',
                    'amount'     => $payAmount,
                    'status'     => OrderPayment::STATUS_PENDING,
                ]);
            } else {
                $dirty = false;
                if ($payment->status === OrderPayment::STATUS_CLOSED || $payment->status === OrderPayment::STATUS_FAILED) {
                    $payment->payment_no = OrderPayment::generatePaymentNo();
                    $payment->status = OrderPayment::STATUS_PENDING;
                    $dirty = true;
                }
                // store 创建的 pending 记录金额为抵扣前应付：同步为积分抵扣后金额，避免微信按原价扣款
                if (Money::toFen((float) $payment->amount) !== Money::toFen($payAmount)) {
                    $payment->amount = $payAmount;
                    $dirty = true;
                }
                if ($dirty) {
                    $payment->save();
                }
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

            // 已发起微信支付时禁止切换余额：旧 prepay 仍可支付，避免同一订单双渠道重复扣款
            if ($payChannel === 'balance' && $hasPrepay) {
                return $this->error('订单已发起微信支付，请完成支付或取消后重试', 422);
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

            // 先写预支付标记再下单：DB 持久化 + Redis 缓存；即使进程在下单/返回间崩溃，后续重试也不会改金额
            $payment->transaction_id = 'PREPAY:' . $payment->amount;
            try {
                $payment->save();
            } catch (\Throwable $e) {
                Log::error('[OrderController] persist prepay marker failed, order_no: ' . $order->order_no . ': ' . $e->getMessage());
                return $this->error('支付通道繁忙，请稍后重试', 422);
            }
            try {
                Redis::connection()->set($prepayKey, (string) $payment->amount, 'EX', 7200);
            } catch (\Throwable $e) {
                // DB marker 已持久化，Redis 丢失可由 DB 回读
                Log::warning('[OrderController] cache prepay marker failed, order_no: ' . $order->order_no . ': ' . $e->getMessage());
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
                // 保留预支付标记：网络超时无法确认微信是否已建单，禁止后续改金额
                Log::error('[OrderController] unifiedOrder failed, order_no: ' . $order->order_no . ', error: ' . $result['error']);
                // payment 保持 pending，允许按同一金额重试
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

            // 余额增减走 string 域，落库前还原 number（值已 round 2 位，float 化无损）
            $wallet->balance = (float) Money::round(Money::sub((string) $wallet->balance, (string) $amount), 2);
            $wallet->total_consume = (float) Money::round(Money::add((string) $wallet->total_consume, (string) $amount), 2);
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

        // 幂等：本单已有抵扣流水（支付重试）时按流水返回，客户端改 use_points 也不重复扣减/改金额
        $existing = UserPoints::where('order_id', $order->id)
            ->where('source', 'points_offset')
            ->where('type', 'consume')
            ->first();
        if ($existing) {
            $pointsUsed = abs((int) $existing->points);
            return [
                'points_used'   => $pointsUsed,
                'offset_amount' => (float) Money::round(Money::div((string) $pointsUsed, (string) $rate), 2),
            ];
        }

        // 用户级积分消费锁：同一用户并发支付不同订单时，SUM 校验与写入必须串行，防超扣
        $spendLockKey = 'points_spend_lock:' . $order->user_id;
        $spendLockToken = bin2hex(random_bytes(16));
        try {
            $acquired = Redis::connection()->set($spendLockKey, $spendLockToken, 'EX', 10, 'NX');
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException('积分处理失败，请稍后重试', 422);
        }
        if (!$acquired) {
            throw new \InvalidArgumentException('积分处理中，请稍后重试', 422);
        }

        try {
            $earned   = (int) UserPoints::where('user_id', $order->user_id)->where('type', 'earn')->sum('points');
            $consumed = (int) UserPoints::where('user_id', $order->user_id)->whereIn('type', ['consume', 'use'])->sum('points');
            $expired  = (int) UserPoints::where('user_id', $order->user_id)->where('type', 'expire')->sum('points');
            $available = $earned + $consumed + $expired; // consume/use/expire 行为负值
            if ($available <= 0 || $usePoints > $available) {
                throw new \InvalidArgumentException('积分不足', 422);
            }

            $paidFen   = Money::toFen((float) $order->paid_amount);
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

            // 积分 = 抵扣元 × 兑换比例（分→元走 string 域，防浮点折损）
            $pointsUsed = (int) Money::round(Money::mul(Money::div((string) $offsetFen, '100', 2), (string) $rate), 0);

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

            return [
                'points_used'   => $pointsUsed,
                'offset_amount' => (float) Money::round(Money::div((string) $offsetFen, '100'), 2),
            ];
        } finally {
            try {
                if ((string) Redis::connection()->get($spendLockKey) === $spendLockToken) {
                    Redis::connection()->del($spendLockKey);
                }
            } catch (\Throwable) {
            }
        }
    }

    /**
     * 批量取消活动下未支付的拼团订单（拼团到期未成团懒判定时调用，幂等：仅 pending 单受影响）
     */
    private function cancelGroupBuyOrders(int $promotionId, string $reason): void
    {
        // 逐单走统一取消流程（order_lock + 行锁复检 + 退积分/释放排班/通知），
        // 拿不到锁的订单跳过，交给其自身的 pay/自动取消流程，避免与支付回调竞态。
        $orders = Order::where('promotion_id', $promotionId)
            ->where('status', Order::STATUS_PENDING)
            ->orderBy('id')
            ->get();

        foreach ($orders as $order) {
            PendingOrderCancelService::cancel($order, $reason, $reason);
        }
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
