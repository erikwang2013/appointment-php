<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

/**
 * 用户钱包模型（储值支付余额体系）
 *
 * 余额操作必须在 DB 事务内 lockForUpdate 钱包行，禁止事务外直接改余额。
 * 金额一律 DECIMAL(10,2)，比较时转分比对，避免浮点误差。
 */
class UserWallet extends Model
{
    protected $table = 'erik_user_wallet';

    protected $fillable = [
        'user_id', 'balance', 'total_recharge', 'total_consume',
        'pay_password', 'pay_password_set_at',
    ];

    protected $casts = [
        'balance'            => 'float',
        'total_recharge'     => 'float',
        'total_consume'      => 'float',
        'pay_password_set_at' => 'datetime',
    ];

    /**
     * 金额（元）转分比较值，禁止浮点直接比较
     */
    public static function toCents(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
