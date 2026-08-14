<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

/**
 * 钱包流水模型
 *
 * 金额一律正数，方向由 type 表达：recharge=充值（+）、consume=消费（-）、refund=退款（+）。
 * balance_after 记录变动后余额，作为审计快照。
 */
class WalletTxn extends Model
{
    protected $table = 'erik_wallet_txn';

    // ── 流水类型常量 ──
    public const TYPE_RECHARGE  = 'recharge';
    public const TYPE_CONSUME   = 'consume';
    public const TYPE_REFUND    = 'refund';
    public const TYPE_GIFT_CARD      = 'gift_card';
    public const TYPE_POINTS_EXCHANGE = 'points_exchange';
    public const TYPE_REFERRAL_REWARD = 'referral_reward';
    public const TYPE_TRANSFER_OUT    = 'transfer_out';
    public const TYPE_TRANSFER_IN     = 'transfer_in';

    public const TYPE_TEXT = [
        self::TYPE_RECHARGE       => '充值',
        self::TYPE_CONSUME        => '消费',
        self::TYPE_REFUND         => '退款',
        self::TYPE_GIFT_CARD      => '礼品卡',
        self::TYPE_POINTS_EXCHANGE => '积分兑换',
        self::TYPE_REFERRAL_REWARD => '推荐返佣',
        self::TYPE_TRANSFER_OUT   => '余额转出',
        self::TYPE_TRANSFER_IN    => '余额转入',
    ];

    public $timestamps = false; // 表仅 created_at（DB 默认 CURRENT_TIMESTAMP）

    protected $fillable = [
        'user_id', 'type', 'amount', 'balance_after',
        'order_id', 'recharge_id', 'remark',
    ];

    protected $casts = [
        'amount'        => 'float',
        'balance_after' => 'float',
    ];
}
