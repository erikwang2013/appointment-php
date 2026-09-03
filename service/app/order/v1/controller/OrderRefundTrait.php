<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\order\v1\controller;

use app\common\Money;
use app\common\NotificationReminderService;
use app\common\WechatPayService;
use app\model\Order;
use app\model\OrderPayment;
use app\model\OrderRefund;
use app\model\OrderStatusLog;
use app\model\UserWallet;
use app\model\WalletTxn;
use support\Db;
use support\Log;
use Webman\Http\Request;

/**
 * 申请退款（refund + doRefund 两段式 + 余额退款原子回充）
 *
 * 阶段一事务内建退款单(pending) + 订单置 refunding；阶段二事务外按渠道退款
 * （balance 原子回充 / wechat 微信退款 + 落库/补偿）。余额退款核心 creditRefundToWallet
 * 与 doCancel 共用。
 */
trait OrderRefundTrait
{
    /**
     * 申请退款
     * POST /api/order/refund/{id}
     */
    public function refund(Request $request, string $id)
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
            // 锁内重新读取订单并校验状态
            $order = Order::where('user_id', $userId)
                ->where('id', $id)
                ->first();
            if (!$order) {
                return $this->error('订单不存在', 404);
            }

            // M8: 核销即开始服务则不可退
            if ($order->status === Order::STATUS_SERVING) {
                return $this->error('服务已开始，不可退款');
            }

            if (!$order->isRefundable()) {
                return $this->error('当前订单状态不可退款');
            }

            $ratio = $order->calcRefundRatio();
            if ($ratio <= 0) {
                return $this->error('当前订单不支持退款');
            }

            return $this->doRefund($request, $order, $ratio);
        } finally {
            $this->releaseLock($lockKey, $lockToken);
        }
    }

    /**
     * 申请退款（在 refund_lock 内执行）
     *
     * 阶段一：事务内建退款单(pending) + 订单置 refunding 并提交；
     * 阶段二：事务外调微信退款；失败回滚订单为 paid（可重试），成功订单置 refunded。
     */
    private function doRefund(Request $request, Order $order, float $ratio)
    {
        $reason = $request->input('reason', '');

        $refundAmount = $this->calcRefundAmount($order, $ratio);

        // 阶段一：事务内建退款单(pending) + 订单置 refunding 并提交；微信 IO 一律事务外
        $refundRecord = null;

        Db::beginTransaction();
        try {
            $payment = $order->payment()->first();

            $refundRecord = OrderRefund::create([
                'id'         => OrderRefund::generateId(),
                'order_id'   => $order->id,
                'payment_id' => $payment->id ?? null,
                'refund_no'  => OrderRefund::generateRefundNo(),
                'amount'     => $refundAmount,
                'ratio'      => $ratio,
                'reason'     => $reason,
                'status'     => OrderRefund::STATUS_PENDING,
            ]);

            $order->status = Order::STATUS_REFUNDING;
            $order->save();

            Db::commit();

            // 状态时间线：→ refunding
            OrderStatusLog::record($order->id, Order::STATUS_PAID, Order::STATUS_REFUNDING, $reason ?: '用户申请退款', 'user');

            // 站内通知：退款申请已受理（幂等：同订单同标题去重）
            $this->writeRefundNotification($order, $refundRecord);
        } catch (\Throwable $e) {
            Db::rollBack();
            // M3: 内部异常详情仅记日志，对外返回通用文案
            Log::error('[OrderController] order refund apply failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->error('申请退款失败，请稍后重试');
        }

        // 阶段二：按支付渠道退款 —— balance 渠道无微信 IO，单事务原子回充；
        // wechat 渠道保持现有两段式（事务外调微信退款 + 落库/补偿）
        $orderPayment = OrderPayment::where('order_id', $order->id)->first();
        if ($orderPayment && $orderPayment->pay_type === 'balance') {
            return $this->refundToBalance($order, $refundRecord, $refundAmount, $ratio);
        }

        // 阶段二（微信渠道）：事务外调微信退款
        $payService = new WechatPayService();
        $result = $payService->refund(
            $order->order_no,
            $refundRecord->refund_no,
            (float)$order->paid_amount,
            (float)$refundAmount
        );

        if (!empty($result['error'])) {
            Log::error('[OrderController] refund failed, order_no: ' . $order->order_no . ', error: ' . $result['error']);
            // 小事务：退款单置 failed，订单回滚 paid（可重试），避免订单永久卡 REFUNDING
            Db::beginTransaction();
            try {
                $refundRecord->status = OrderRefund::STATUS_FAILED;
                $refundRecord->save();
                $order->status = Order::STATUS_PAID;
                $order->save();
                Db::commit();

                // 状态时间线：refunding → paid（退款失败回滚）
                OrderStatusLog::record($order->id, Order::STATUS_REFUNDING, Order::STATUS_PAID, '退款失败，订单恢复', 'user');
            } catch (\Throwable $e2) {
                Db::rollBack();
                Log::error('[OrderController] refund failed persist error: ' . $e2->getMessage());
            }
            return $this->error('退款处理失败请重试');
        }

        // 小事务：退款单置 success + refunded_at，订单 refunded；全额退款时归还券/次卡（M3/B3）
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
            // 积分回扣（同事务，失败随退款回滚）：按退款比例回扣已返积分，幂等
            $this->clawbackOrderPoints($order, $refundRecord);
            // 积分抵扣回补（同事务，失败随退款回滚）：按退款比例退还抵现积分，幂等
            $this->refundOffsetPoints($order, $refundRecord);
            Db::commit();

            // 状态时间线：refunding → refunded
            OrderStatusLog::record($order->id, Order::STATUS_REFUNDING, Order::STATUS_REFUNDED, '退款成功', 'user');
        } catch (\Throwable $e2) {
            Db::rollBack();
            Log::error('[OrderController] refund success persist failed: ' . $e2->getMessage());
            // B4: 微信侧已退款但落库失败 → 立即幂等补偿；仍失败由定时器兜底，避免永久卡 REFUNDING
            $compensated = false;
            try {
                $compensated = $this->completeOneRefundCompensation($refundRecord);
            } catch (\Throwable $e3) {
                Log::error('[OrderController] refund compensation retry failed: ' . $e3->getMessage());
            }
            if ($compensated) {
                return $this->success([
                    'refund_amount' => $refundAmount,
                    'ratio'         => $ratio,
                ], '退款成功');
            }
            return $this->error('退款处理失败请重试');
        }

        // 站内通知：退款已到账（补偿成功路径已由 completeOneRefundCompensation 幂等补写，此处去重）
        $this->writeRefundNotification($order, $refundRecord);

        // 释放技师锁
        $this->releaseTechnicianLock($order);

        // 发送退款通知模板消息（非阻塞，失败不影响主流程）
        $this->sendRefundNotifyTemplate((string) $order->user_id, $order, $refundAmount, $reason);

        // 订阅消息：退款到账（非阻塞，失败不影响主流程）
        $this->notifySubscribeEvent($order, NotificationReminderService::SCENE_REFUND, [
            'refund_amount' => $refundAmount,
            'refund_reason' => $reason,
        ]);

        // R22 APP 推送：退款已到账（未启用时静默降级，失败不影响主流程）
        try {
            \app\common\AppPushService::pushToUser((int) $order->user_id, '退款已到账',
                '您的订单 ' . $order->order_no . ' 已退款 ¥' . number_format((float) $refundAmount, 2) . '，款项将原路退回。',
                ['type' => 'order_refund', 'order_id' => (string) $order->id, 'order_no' => $order->order_no, 'refund_amount' => (float) $refundAmount]);
        } catch (\Throwable $e) {
            Log::warning('[AppPush] refund push failed: ' . $e->getMessage());
        }

        // WebSocket 实时推送
        $this->pushOrderUpdate($order);

        return $this->success([
            'refund_amount' => $refundAmount,
            'ratio'         => $ratio,
        ], '退款成功');
    }

    /**
     * 余额支付订单退款（无微信 IO，单事务原子完成）
     *
     * 内部复用 creditRefundToWallet（退款单行锁幂等 + 钱包回充 + 落库）。
     * 失败仅可能为 DB 异常，退款单保持 pending 由补偿扫描幂等兜底
     * （补偿侧对 balance 渠道同样回充余额，见 completeOneRefundCompensation）。
     */
    private function refundToBalance(Order $order, OrderRefund $refundRecord, float $refundAmount, float $ratio)
    {
        try {
            $credited = $this->creditRefundToWallet($order, $refundRecord, $refundAmount);
        } catch (\Throwable $e) {
            Log::error('[OrderController] balance refund failed, order_no: ' . $order->order_no . ': ' . $e->getMessage());
            return $this->error('退款处理失败请重试');
        }

        // 已被补偿处理完成（幂等），直接返回成功
        if (!$credited) {
            return $this->success([
                'refund_amount' => $refundAmount,
                'ratio'         => $ratio,
            ], '退款成功');
        }

        // 站内通知：退款已到账（幂等：同订单同标题去重）
        $this->writeRefundNotification($order, $refundRecord->fresh() ?: $refundRecord);

        // 释放技师锁
        $this->releaseTechnicianLock($order);

        // 发送退款通知模板消息（非阻塞，失败不影响主流程）
        $this->sendRefundNotifyTemplate((string) $order->user_id, $order, $refundAmount, $refundRecord->reason);

        // 订阅消息：退款到账（非阻塞，失败不影响主流程）
        $this->notifySubscribeEvent($order, NotificationReminderService::SCENE_REFUND, [
            'refund_amount' => $refundAmount,
            'refund_reason' => $refundRecord->reason,
        ]);

        // R22 APP 推送：退款已到账（未启用时静默降级，失败不影响主流程）
        try {
            \app\common\AppPushService::pushToUser((int) $order->user_id, '退款已到账',
                '您的订单 ' . $order->order_no . ' 已退款 ¥' . number_format((float) $refundAmount, 2) . '，款项将原路退回。',
                ['type' => 'order_refund', 'order_id' => (string) $order->id, 'order_no' => $order->order_no, 'refund_amount' => (float) $refundAmount]);
        } catch (\Throwable $e) {
            Log::warning('[AppPush] refund push failed: ' . $e->getMessage());
        }

        // WebSocket 实时推送
        $this->pushOrderUpdate($order);

        return $this->success([
            'refund_amount' => $refundAmount,
            'ratio'         => $ratio,
        ], '退款成功');
    }

    /**
     * 余额退款核心（doRefund/doCancel 共用，单事务原子完成）
     *
     * 退款单行锁 + status 复验（幂等：防与补偿 completeOneRefundCompensation 并发双处理）
     * → 钱包行 lockForUpdate → balance 回充 + 写流水(refund, balance_after)
     * → 退款单置 success/refunded_at → 订单置 refunded → 全额退款归还券/次卡
     * → 积分回扣 + 积分抵扣回补（幂等）。
     *
     * @param bool $fullOffsetRefund 积分抵现回补是否全额（true=取消，false=退款按比例）
     * @return bool true=本次完成入账；false=退款单已被补偿处理（幂等跳过）
     */
    private function creditRefundToWallet(Order $order, OrderRefund $refundRecord, float $refundAmount, bool $fullOffsetRefund = false): bool
    {
        Db::beginTransaction();
        try {
            $locked = OrderRefund::where('id', $refundRecord->id)
                ->where('status', OrderRefund::STATUS_PENDING)
                ->lockForUpdate()
                ->first();
            if (!$locked) {
                Db::rollBack();
                return false;
            }

            $wallet = UserWallet::where('user_id', $order->user_id)->lockForUpdate()->first();
            if (!$wallet) {
                throw new \RuntimeException('用户钱包不存在');
            }
            $wallet->balance = (float) Money::round(Money::add((string) $wallet->balance, (string) $refundAmount), 2);
            $wallet->save();

            WalletTxn::create([
                'user_id'       => $order->user_id,
                'type'          => WalletTxn::TYPE_REFUND,
                'amount'        => $refundAmount,
                'balance_after' => (float) $wallet->balance,
                'order_id'      => $order->id,
                'remark'        => '订单退款 ' . $order->order_no,
            ]);

            $locked->status = OrderRefund::STATUS_SUCCESS;
            $locked->refunded_at = now();
            $locked->save();

            $refundFromStatus = $order->status;
            $order->status = Order::STATUS_REFUNDED;
            $order->save();
            if ($this->shouldRestoreBenefits((float) $locked->ratio)) {
                $this->restoreCouponAndCard($order);
            }

            // 积分回扣（同事务，失败随退款回滚）：按退款比例回扣已返积分，幂等
            $this->clawbackOrderPoints($order, $locked);
            // 积分抵扣回补（同事务，失败随退款回滚）：取消全额/退款按比例退还抵现积分，幂等
            $this->refundOffsetPoints($order, $locked, $fullOffsetRefund);

            Db::commit();

            // 状态时间线：→ refunded（余额退款）
            OrderStatusLog::record($order->id, $refundFromStatus, Order::STATUS_REFUNDED, '余额退款成功', 'user');
            return true;
        } catch (\Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }

    /**
     * B3: 计算退款金额（元）——doCancel/doRefund 共用，保证比例口径一致
     */
    private function calcRefundAmount(Order $order, float $ratio): float
    {
        // 退款额 = 实付 × 比例，string 域乘法后再舍入，防 float ratio 链式误差
        return (float) Money::round(Money::mul((string) $order->paid_amount, (string) $ratio), 2);
    }

    /**
     * B3: 是否归还优惠（券/次卡）——仅全额退款（比例 >= 1.0）归还，部分退款不归还（与 doRefund 对齐）。
     * 全额优惠零元单（无退款单路径）走 doCancel 的 else 分支直接归还。
     */
    private function shouldRestoreBenefits(float $ratio): bool
    {
        return Money::cmp((string) $ratio, '1.00') >= 0;
    }
}
