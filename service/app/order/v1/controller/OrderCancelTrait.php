<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\order\v1\controller;

use app\common\WechatPayService;
use app\model\Order;
use app\model\OrderPayment;
use app\model\OrderRefund;
use app\model\OrderStatusLog;
use support\Db;
use support\Log;
use Webman\Http\Request;

/**
 * 取消订单（cancel + doCancel 两段式）
 *
 * 阶段一事务内建退款单(pending) + 订单置 cancelled；阶段二事务外按渠道退款
 * （balance 原子回充 / wechat 微信退款 + 落库/补偿）。
 */
trait OrderCancelTrait
{
    /**
     * 取消订单
     * POST /api/order/cancel/{id}
     */
    public function cancel(Request $request, string $id)
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
            return $this->error('操作处理中，请稍后再试');
        }

        try {
            // 锁内重新读取订单并校验状态（防并发：支付回调/自动取消与取消同锁互斥）
            $order = Order::where('user_id', $userId)
                ->where('id', $id)
                ->first();
            if (!$order) {
                return $this->error('订单不存在', 404);
            }
            if (!in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_PAID], true)) {
                return $this->error('当前订单状态不可取消');
            }
            return $this->doCancel($request, $order);
        } finally {
            $this->releaseLock($lockKey, $lockToken);
        }
    }

    /**
     * 取消订单（在 order_lock 内执行）
     *
     * 阶段一：事务内建退款单(pending) + 订单置 cancelled 并提交；
     * 阶段二：事务外调微信退款；失败回滚订单为 paid（可重试），成功订单置 refunded。
     * 优惠归还（M3）：仅订单终态为 cancelled/refunded 时归还券/次卡（退款失败回滚则保持已消费）。
     */
    private function doCancel(Request $request, Order $order)
    {
        $cancelReason = $request->input('cancel_reason', '');

        // B1.3: cancel 前查微信——订单存在 pending 支付记录时，先确认微信侧未支付；
        // 若微信侧已支付（回调未达），先落库对齐为 paid，再走退款路径而非置 cancelled。
        $payment = $order->payment()->first();
        if ($payment && $payment->status === OrderPayment::STATUS_PENDING) {
            $queryResult = (new WechatPayService())->queryOrder($order->order_no);
            if (!empty($queryResult['error'])) {
                return $this->error('无法确认支付状态，请稍后再试');
            }
            $tradeState = (string)($queryResult['trade_state'] ?? '');
            if ($tradeState === 'SUCCESS') {
                // 微信侧已支付：标记支付成功（幂等单一消费点），订单对齐为 paid 后走退款路径
                $mark = (new WechatPayService())->markOrderPaid(
                    $payment->payment_no,
                    (string)($queryResult['transaction_id'] ?? ''),
                    (float)($queryResult['total_fee'] ?? 0) / 100,
                    'wechat'
                );
                if (empty($mark['success'])) {
                    return $this->error('支付状态同步失败，请稍后再试');
                }
                $order = $order->fresh();
                if (!$order) {
                    return $this->error('订单状态异常，请稍后再试');
                }
            } elseif (!in_array($tradeState, ['NOTPAY', 'CLOSED', 'REVOKED', 'USERPAYING', 'PAYERROR'], true)) {
                return $this->error('支付状态异常，请稍后再试');
            }
        }

        // 阶段一：事务内建退款单(pending) + 订单置 cancelled 并提交；微信 IO 一律事务外
        $refundRecord = null;
        $refundAmount = 0.00;

        Db::beginTransaction();
        try {
            // 已支付的订单需计算退款（B3: 与 doRefund 共用 calcRefundAmount，保证比例口径一致）
            $fromStatus = $order->status;
            if ($order->status === Order::STATUS_PAID) {
                $ratio = $order->calcRefundRatio();
                $refundAmount = $this->calcRefundAmount($order, $ratio);

                if ($refundAmount > 0) {
                    $payment = $order->payment()->first();
                    $refundRecord = OrderRefund::create([
                        'id'         => OrderRefund::generateId(),
                        'order_id'   => $order->id,
                        'payment_id' => $payment->id ?? null,
                        'refund_no'  => OrderRefund::generateRefundNo(),
                        'amount'     => $refundAmount,
                        'ratio'      => $ratio,
                        'reason'     => $cancelReason ?: '用户取消订单',
                        'status'     => OrderRefund::STATUS_PENDING,
                    ]);
                }
            }

            $order->status = Order::STATUS_CANCELLED;
            $order->cancel_reason = $cancelReason;
            $order->cancel_at = now();
            $order->save();

            Db::commit();

            // 状态时间线：→ cancelled（from_status 在变更前捕获，失败仅记日志）
            OrderStatusLog::record($order->id, $fromStatus, Order::STATUS_CANCELLED, $cancelReason ?: '用户取消订单', 'user');

            // 站内通知：退款申请已受理（幂等：同订单同标题去重）
            if ($refundRecord) {
                $this->writeRefundNotification($order, $refundRecord);
            }
        } catch (\Throwable $e) {
            Db::rollBack();
            // M3: 内部异常详情仅记日志，对外返回通用文案
            Log::error('[OrderController] order cancel failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->error('取消订单失败，请稍后重试');
        }

        // 阶段二：按支付渠道退款 —— balance 渠道无微信 IO，事务内原子回充余额；
        // wechat 渠道保持现有两段式（事务外调微信退款 + 落库/补偿）
        if ($refundRecord) {
            $orderPayment = OrderPayment::where('order_id', $order->id)->first();
            if ($orderPayment && $orderPayment->pay_type === 'balance') {
                // 余额支付订单取消：回充余额（幂等：已补偿单直接跳过，通知走尾部去重）
                try {
                    $this->creditRefundToWallet($order, $refundRecord, $refundAmount, true);
                } catch (\Throwable $e) {
                    Log::error('[OrderController] balance refund failed on cancel, order_no: ' . $order->order_no . ': ' . $e->getMessage());
                    return $this->error('退款处理失败请重试');
                }
            } else {
                $payService = new WechatPayService();
                $result = $payService->refund(
                    $order->order_no,
                    $refundRecord->refund_no,
                    (float)$order->paid_amount,
                    (float)$refundAmount
                );

                if (!empty($result['error'])) {
                    Log::error('[OrderController] refund failed on cancel, order_no: ' . $order->order_no . ', error: ' . $result['error']);
                    // 小事务：退款单置 failed，订单回滚 paid（可重试），清空取消标记
                    Db::beginTransaction();
                    try {
                        $refundRecord->status = OrderRefund::STATUS_FAILED;
                        $refundRecord->save();
                        $order->status = Order::STATUS_PAID;
                        $order->cancel_reason = ''; // erik_order.cancel_reason 为 NOT NULL，置空串而非 null
                        $order->cancel_at = null;
                        $order->save();
                        Db::commit();

                        // 状态时间线：cancelled → paid（退款失败回滚）
                        OrderStatusLog::record($order->id, Order::STATUS_CANCELLED, Order::STATUS_PAID, '退款失败，订单恢复', 'user');
                    } catch (\Throwable $e2) {
                        Db::rollBack();
                        Log::error('[OrderController] refund rollback persist failed: ' . $e2->getMessage());
                    }
                    return $this->error('退款处理失败请重试');
                }

                // 小事务：退款单置 success + refunded_at，订单 refunded
                // B3: 仅全额退款（ratio>=1.0）归还券/次卡，部分退款不归还（与 doRefund 对齐）
                Db::beginTransaction();
                try {
                    $refundRecord->status = OrderRefund::STATUS_SUCCESS;
                    $refundRecord->refunded_at = now();
                    $refundRecord->save();
                    $order->status = Order::STATUS_REFUNDED;
                    $order->save();
                    if ($this->shouldRestoreBenefits($ratio)) {
                        $this->restoreCouponAndCard($order);
                    }
                    // 积分抵扣回补（同事务，失败随退款回滚）：取消全额退还抵现积分，幂等
                    $this->refundOffsetPoints($order, $refundRecord, true);
                    Db::commit();

                    // 状态时间线：cancelled → refunded（取消退款成功）
                    OrderStatusLog::record($order->id, Order::STATUS_CANCELLED, Order::STATUS_REFUNDED, '取消订单退款成功', 'user');
                } catch (\Throwable $e2) {
                    Db::rollBack();
                    Log::error('[OrderController] refund success persist failed: ' . $e2->getMessage());
                    // B4: 微信侧已退款但落库失败 → 立即幂等补偿（补写退款单+归还券/次卡）；
                    // 仍失败则保持可被 AutoCancelTimer 周期扫描兜底，绝不静默卡死
                    $compensated = false;
                    try {
                        $compensated = $this->completeOneRefundCompensation($refundRecord);
                    } catch (\Throwable $e3) {
                        Log::error('[OrderController] refund compensation retry failed: ' . $e3->getMessage());
                    }
                    if (!$compensated) {
                        return $this->error('退款处理失败请重试');
                    }
                }
            }
        } else {
            // 无退款路径的取消（未支付/全额优惠零元/比例=0）为终态 cancelled：归还券/次卡
            Db::beginTransaction();
            try {
                $this->restoreCouponAndCard($order);
                // 积分抵扣回补（同事务）：无退款单的取消（比例=0）同样全额退还抵现积分，幂等
                $this->refundOffsetPoints($order, null, true);
                Db::commit();
            } catch (\Throwable $e2) {
                Db::rollBack();
                Log::error('[OrderController] restore benefits on cancel failed: ' . $e2->getMessage());
            }
        }

        // 站内通知：退款已到账（直接成功或补偿成功均落此；补偿路径由 completeOneRefundCompensation 幂等补写）
        if ($refundRecord) {
            $this->writeRefundNotification($order, $refundRecord->fresh() ?: $refundRecord);
        }

        // R22 APP 推送：退款已到账（未启用时静默降级，失败不影响主流程）
        if ($refundRecord) {
            try {
                \app\common\AppPushService::pushToUser((int) $order->user_id, '退款已到账',
                    '您的订单 ' . $order->order_no . ' 已退款 ¥' . number_format((float) $refundRecord->amount, 2) . '，款项将原路退回。',
                    ['type' => 'order_refund', 'order_id' => (string) $order->id, 'order_no' => $order->order_no]);
            } catch (\Throwable $e) {
                Log::warning('[AppPush] refund push failed: ' . $e->getMessage());
            }
        }

        // 释放技师锁
        $this->releaseTechnicianLock($order);

        // WebSocket 实时推送
        $this->pushOrderUpdate($order);

        return $this->success(null, '订单已取消');
    }
}
