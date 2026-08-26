<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\order\v1\controller;

use app\model\MemberCardUsage;
use app\model\Order;
use app\model\OrderPayment;
use app\model\OrderRefund;
use app\model\OrderStatusLog;
use app\model\UserCoupon;
use app\model\UserMemberCard;
use app\model\UserPoints;
use app\model\UserWallet;
use app\model\WalletTxn;
use support\Db;
use support\Log;

/**
 * 退款补偿扫描 + 优惠/积分归还（doCancel/doRefund/补偿共用）
 *
 * 补偿扫描幂等补写「微信已退款但落库失败」的滞留单；归还券/次卡与积分
 * 回扣/回补均为幂等操作，主路径与补偿路径并发安全。
 */
trait OrderCompensateTrait
{
    /**
     * B4: 退款补偿扫描阈值（秒）——退款单 pending 超过该时长仍未被推进，视为「微信已退款但落库失败」，
     * 由 completeRefundCompensation() 幂等补写（微信退款接口为同步返回，正常场景 10 分钟内必然落库）。
     */
    private const REFUND_COMPENSATE_AFTER = 600;

    /**
     * B4: 退款补偿（幂等，周期扫描入口）——处理「微信已退款但落库失败」的滞留单
     *
     * 扫描：退款单 status=pending 且创建超过 REFUND_COMPENSATE_AFTER 秒，关联订单处于
     * refunding（doRefund 落库失败）或 cancelled（doCancel 落库失败）状态。
     * 处理：补写退款单 success + refunded_at；全额退款归还券/次卡；refunding 单置 refunded，
     * cancelled 单保持终态 cancelled（不覆盖状态）。
     * 幂等：仅 status=pending 的退款单可被补写，重复扫描不产生副作用。
     */
    public function completeRefundCompensation(): void
    {
        $threshold = date('Y-m-d H:i:s', time() - self::REFUND_COMPENSATE_AFTER);

        try {
            $records = OrderRefund::where('status', OrderRefund::STATUS_PENDING)
                ->where('created_at', '<', $threshold)
                ->limit(50)
                ->get();

            foreach ($records as $record) {
                try {
                    $this->completeOneRefundCompensation($record);
                } catch (\Throwable $e) {
                    Log::error('[OrderController] completeRefundCompensation item failed, refund: '
                        . $record->id . ': ' . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            Log::error('[OrderController] completeRefundCompensation scan error: ' . $e->getMessage());
        }
    }

    /**
     * B4: 单条退款补偿（幂等）
     *
     * @param OrderRefund $refundRecord 待补偿退款单
     * @return bool 是否完成补偿
     */
    private function completeOneRefundCompensation(OrderRefund $refundRecord): bool
    {
        try {
            Db::beginTransaction();

            // 行锁 + 状态复验：仅 pending 退款单可被补写（防并发重复补偿）
            $locked = OrderRefund::where('id', $refundRecord->id)
                ->where('status', OrderRefund::STATUS_PENDING)
                ->lockForUpdate()
                ->first();
            $order = $locked ? Order::where('id', $locked->order_id)->lockForUpdate()->first() : null;

            if (!$locked || !$order) {
                Db::rollBack();
                return false;
            }
            if (!in_array($order->status, [Order::STATUS_REFUNDING, Order::STATUS_CANCELLED], true)) {
                Db::rollBack();
                return false;
            }

            // 余额渠道退款补偿：同步回充余额 + 写流水（幂等——仅 pending 退款单可被补写，
            // 与 refundToBalance/doCancel 行锁互斥，重复扫描不重复入账）
            $payment = $locked->payment_id ? OrderPayment::find($locked->payment_id) : null;
            if ($payment && $payment->pay_type === 'balance') {
                $wallet = UserWallet::where('user_id', $order->user_id)->lockForUpdate()->first();
                if (!$wallet) {
                    Db::rollBack();
                    return false;
                }
                $wallet->balance = round((float) $wallet->balance + (float) $locked->amount, 2);
                $wallet->save();
                WalletTxn::create([
                    'user_id'       => $order->user_id,
                    'type'          => WalletTxn::TYPE_REFUND,
                    'amount'        => (float) $locked->amount,
                    'balance_after' => (float) $wallet->balance,
                    'order_id'      => $order->id,
                    'remark'        => '订单退款补偿 ' . $order->order_no,
                ]);
            }

            $locked->status = OrderRefund::STATUS_SUCCESS;
            $locked->refunded_at = now();
            $locked->save();

            if ($this->shouldRestoreBenefits((float) $locked->ratio)) {
                $this->restoreCouponAndCard($order);
            }

            // 积分回扣（幂等）：主路径落库失败由补偿补写时，回扣在此一并补写
            $this->clawbackOrderPoints($order, $locked);
            // 积分抵扣回补（幂等）：取消单全额、退款单按比例，与主路径口径一致
            $this->refundOffsetPoints($order, $locked, $order->status === Order::STATUS_CANCELLED);

            // refunding → refunded；cancelled 保持终态
            if ($order->status === Order::STATUS_REFUNDING) {
                $order->status = Order::STATUS_REFUNDED;
                $order->save();
            }

            Db::commit();

            // 状态时间线：refunding → refunded（补偿路径）
            if ($order->status === Order::STATUS_REFUNDED) {
                OrderStatusLog::record($order->id, Order::STATUS_REFUNDING, Order::STATUS_REFUNDED, '退款补偿完成', 'system');
            }

            // 站内通知：退款已到账（幂等：同订单同标题去重，主路径并发时不会双写）
            $this->writeRefundNotification($order, $locked);

            // R22 APP 推送：退款已到账（未启用时静默降级，失败不影响主流程）
            try {
                \app\common\AppPushService::pushToUser((int) $order->user_id, '退款已到账',
                    '您的订单 ' . $order->order_no . ' 已退款 ¥' . number_format((float) $locked->amount, 2) . '，款项将原路退回。',
                    ['type' => 'order_refund', 'order_id' => (string) $order->id, 'order_no' => $order->order_no, 'refund_amount' => (float) $locked->amount]);
            } catch (\Throwable $e) {
                Log::warning('[AppPush] refund compensation push failed: ' . $e->getMessage());
            }

            Log::info('[OrderController] refund compensation done, order_no: ' . $order->order_no
                . ', refund_id: ' . $locked->id);
            return true;
        } catch (\Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }

    /**
     * M3: 归还订单使用的优惠券与次卡次数（仅终态为 cancelled/refunded 时调用，幂等）
     *
     * - 优惠券：used → available（条件更新，防并发重复归还）
     * - 次卡：按使用记录数扣回 used_times，used_up 恢复 active，使用记录软置 cancelled
     */
    private function restoreCouponAndCard(Order $order): void
    {
        // 优惠券归还（幂等：仅 status=used 时可置回 available）
        if ((int)$order->user_coupon_id > 0) {
            UserCoupon::where('id', $order->user_coupon_id)
                ->where('status', 'used')
                ->update(['status' => 'available', 'used_at' => null]);
        }

        $this->restoreMemberCardTimes($order);
    }

    /**
     * M3: 次卡次数归还（consume 的逆操作）
     *
     * 订单的 member_card_usage_id 列在支付后回写为首条使用记录 ID（非卡片 ID），
     * 因此按 order_id 查使用记录获取卡片与扣回次数。
     */
    private function restoreMemberCardTimes(Order $order): void
    {
        if ((int)$order->member_card_usage_id <= 0) {
            return;
        }

        $usages = MemberCardUsage::where('order_id', $order->id)
            ->where('status', 'active')
            ->get();
        if ($usages->isEmpty()) {
            return;
        }

        $count  = $usages->count();
        $cardId = (int)$usages->first()->user_card_id;

        // 原子扣回（防并发重复归还）
        UserMemberCard::where('id', $cardId)
            ->whereRaw('used_times - ? >= 0', [$count])
            ->decrement('used_times', $count);

        // used_up → active 恢复
        $card = UserMemberCard::find($cardId);
        if ($card && $card->status === 'used_up' && (int)$card->used_times < (int)$card->total_times) {
            $card->status = 'active';
            $card->save();
        }

        // 使用记录软置 cancelled（保留审计轨迹）
        MemberCardUsage::whereIn('id', $usages->pluck('id'))
            ->update(['status' => 'cancelled']);
    }

    /**
     * 订单退款回扣积分（退款事务内调用，失败随退款整体回滚——与 rewardOrderPoints 对称）
     *
     * 规则：回扣 = floor(已返积分 × 本次退款金额 / 实付金额)，与 calcRefundAmount 同口径；
     * 已返积分取该订单 source=order + type=earn 流水合计（未核销未返积分则为 0，直接跳过）。
     * 幂等：同 order_id + source=order + type=use 的回扣流水已存在则不重复回扣
     * （当前退款流程每订单至多一条成功退款单，订单 refunded 后不可再退，键唯一；
     * 并发/补偿场景下与主路径行锁互斥，重复执行不产生第二笔回扣）。
     * balance 为逐条快照：上一条余额 - 本次回扣（同 rewardOrderPoints 锁定最后一条流水防并发串行）。
     */
    private function clawbackOrderPoints(Order $order, OrderRefund $refundRecord): void
    {
        // 已返积分合计（未返积分则无需回扣）
        $earned = (int) UserPoints::where('order_id', $order->id)
            ->where('source', 'order')
            ->where('type', 'earn')
            ->sum('points');
        if ($earned <= 0) {
            return;
        }

        // 幂等：同订单的回扣流水已存在则不重复回扣
        $exists = UserPoints::where('order_id', $order->id)
            ->where('source', 'order')
            ->where('type', 'use')
            ->exists();
        if ($exists) {
            return;
        }

        $paid = (float) $order->paid_amount;
        $refundAmount = (float) $refundRecord->amount;
        if ($paid <= 0 || $refundAmount <= 0) {
            return;
        }

        // 按退款金额比例回扣（向下取整，至多回扣已返积分）
        $points = (int) floor($earned * $refundAmount / $paid);
        if ($points <= 0) {
            return;
        }

        // balance = 上一条余额 - 本次回扣（快照累加，锁最后一条流水防同用户并发串行）
        $lastBalance = (int) (UserPoints::where('user_id', $order->user_id)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->lockForUpdate()
            ->value('balance') ?? 0);

        UserPoints::create([
            'id'          => UserPoints::generateId(),
            'user_id'     => $order->user_id,
            'type'        => 'use',
            'points'      => -$points,
            'balance'     => $lastBalance - $points,
            'source'      => 'order',
            'order_id'    => $order->id,
            'description' => '订单退款回扣积分（退款单 ' . $refundRecord->refund_no . '）',
        ]);
    }

    /**
     * 订单取消/退款退还积分抵扣（points_offset 消费流水的对称回补，与 clawbackOrderPoints 并列）
     *
     * 规则：取消全额退还；退款按比例退还（floor(原扣点 × 退款金额/实付)，
     * 与 clawbackOrderPoints 取整口径一致）；原扣点取该订单 source=points_offset + type=consume
     * 流水合计（未用积分抵现则为 0，直接跳过）。
     * 幂等：同 order_id + source=points_refund 的回补流水已存在则不重复回补
     * （订单终态后不可重复取消/退款；补偿扫描与主路径行锁互斥）。
     * balance 为逐条快照：上一条余额 + 本次回补（同 rewardOrderPoints 锁定最后一条流水防并发串行）。
     */
    private function refundOffsetPoints(Order $order, ?OrderRefund $refundRecord, bool $fullRefund = false): void
    {
        // 原抵扣积分合计（points_offset 消费流水存负值；未抵现则无需回补）
        $consumed = (int) UserPoints::where('order_id', $order->id)
            ->where('source', 'points_offset')
            ->where('type', 'consume')
            ->sum('points');
        if ($consumed >= 0) {
            return;
        }

        // 幂等：同订单的回补流水已存在则不重复回补
        $exists = UserPoints::where('order_id', $order->id)
            ->where('source', 'points_refund')
            ->exists();
        if ($exists) {
            return;
        }

        if ($fullRefund) {
            $points = -$consumed;
        } else {
            $paid = (float) $order->paid_amount;
            $refundAmount = (float) ($refundRecord->amount ?? 0);
            if ($paid <= 0 || $refundAmount <= 0) {
                return;
            }
            // 按退款金额比例回补（向下取整，至多回补原抵扣积分，与 clawbackOrderPoints 同口径）
            $points = (int) floor(-$consumed * $refundAmount / $paid);
            if ($points <= 0) {
                return;
            }
        }

        // balance = 上一条余额 + 本次回补（快照累加，锁最后一条流水防同用户并发串行）
        $lastBalance = (int) (UserPoints::where('user_id', $order->user_id)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->lockForUpdate()
            ->value('balance') ?? 0);

        UserPoints::create([
            'id'          => UserPoints::generateId(),
            'user_id'     => $order->user_id,
            'type'        => 'earn',
            'points'      => $points,
            'balance'     => $lastBalance + $points,
            'source'      => 'points_refund',
            'order_id'    => $order->id,
            'description' => $fullRefund
                ? '订单取消退还积分（订单 ' . $order->order_no . '）'
                : '订单退款退还积分（退款单 ' . $refundRecord->refund_no . '）',
            'expires_at'  => UserPoints::expiryAt(),
        ]);
    }
}
