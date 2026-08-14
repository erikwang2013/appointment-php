<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

/**
 * 钱包充值单模型
 *
 * order_no 以 'R' 开头（R + YmdHis + 4位随机数），与订单号体系区分，
 * 微信支付回调按 out_trade_no 前缀 'R' 识别充值单（复用统一 notify_url）。
 */
class WalletRecharge extends Model
{
    protected $table = 'erik_wallet_recharge';

    // ── 充值单状态常量 ──
    public const STATUS_PENDING  = 'pending';
    public const STATUS_PAID     = 'paid';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_FAILED   = 'failed';

    protected $fillable = [
        'user_id', 'order_no', 'amount', 'status', 'pay_channel', 'paid_at',
    ];

    protected $casts = [
        'amount'   => 'float',
        'paid_at'  => 'datetime',
    ];

    /**
     * 生成充值单号：R + YmdHis + 4位随机数字（与订单号 generate_order_no 同构，前缀区分）
     */
    public static function generateOrderNo(): string
    {
        return 'R' . date('YmdHis') . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }
}
