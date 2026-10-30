<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Db;
use support\Model;

class UserPoints extends Model
{
    protected $table = 'appointment_user_points';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'user_id', 'type', 'points', 'balance',
        'source', 'order_id', 'description', 'expires_at',
    ];

    /**
     * 积分有效期（天）：appointment_system_config (group=points, key=expiry_days)，缺省 365
     */
    public static function expiryDays(): int
    {
        try {
            $value = Db::table('appointment_system_config')
                ->where('group', 'points')
                ->where('key', 'expiry_days')
                ->value('value');
        } catch (\Throwable) {
            $value = null;
        }
        if ($value === null) {
            return 365;
        }
        return max(0, (int) $value);
    }

    /**
     * earn 类型积分到期时间（now + expiryDays()）；expiry_days<=0 视为永不过期（返回 null）
     */
    public static function expiryAt(): ?string
    {
        $days = self::expiryDays();
        if ($days <= 0) {
            return null;
        }
        return date('Y-m-d H:i:s', time() + $days * 86400);
    }

    /**
     * 订单取消/退款退还积分抵扣（points_offset 消费流水的对称回补）
     *
     * 规则：取消全额退还；退款按比例退还（floor(原扣点 × 退款金额/实付)）；
     * 原扣点取该订单 source=points_offset + type=consume 流水合计（未抵现则跳过）。
     * 幂等：同 order_id + source=points_refund 的回补流水已存在则不重复回补。
     * balance 为逐条快照：上一条余额 + 本次回补（锁最后一条流水防同用户并发串行）。
     * 用户取消、平台取消、自动取消与退款补偿共用本方法（调用方负责事务）。
     */
    public static function refundOrderOffset(Order $order, bool $fullRefund, ?OrderRefund $refund = null): void
    {
        // 原抵扣积分合计（points_offset 消费流水存负值；未抵现则无需回补）
        $consumed = (int) self::where('order_id', $order->id)
            ->where('source', 'points_offset')
            ->where('type', 'consume')
            ->sum('points');
        if ($consumed >= 0) {
            return;
        }

        // 幂等：同订单的回补流水已存在则不重复回补
        $exists = self::where('order_id', $order->id)
            ->where('source', 'points_refund')
            ->exists();
        if ($exists) {
            return;
        }

        if ($fullRefund) {
            $points = -$consumed;
        } else {
            $paid = (float) $order->paid_amount;
            $refundAmount = (float) ($refund->amount ?? 0);
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
        $lastBalance = (int) (self::where('user_id', $order->user_id)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->lockForUpdate()
            ->value('balance') ?? 0);

        self::create([
            'id'          => self::generateId(),
            'user_id'     => $order->user_id,
            'type'        => 'earn',
            'points'      => $points,
            'balance'     => $lastBalance + $points,
            'source'      => 'points_refund',
            'order_id'    => $order->id,
            'description' => $fullRefund
                ? '订单取消退还积分（订单 ' . $order->order_no . '）'
                : '订单退款退还积分（退款单 ' . $refund->refund_no . '）',
            'expires_at'  => self::expiryAt(),
        ]);
    }

}
