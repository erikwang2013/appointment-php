<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

/**
 * 用户间余额转账记录模型
 *
 * 转账即时完成（completed），金额 DECIMAL(25,2)，比较一律转分。
 */
class WalletTransfer extends Model
{
    protected $table = 'appointment_wallet_transfer';

    public const STATUS_COMPLETED = 'completed';

    public $timestamps = false; // 表仅 created_at（DB 默认 CURRENT_TIMESTAMP）

    protected $fillable = [
        'from_user_id', 'to_user_id', 'amount', 'status', 'remark',
    ];

    protected $casts = [
        'amount' => 'float',
    ];
}
