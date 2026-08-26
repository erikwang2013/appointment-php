<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

/**
 * 用户积分转赠记录
 *
 * 转赠产生两条积分流水：发送方 type=consume source=points_transfer（负值）、
 * 接收方 type=earn source=points_transfer（正值）；本表记录转赠本身。
 */
class UserPointsTransfer extends Model
{
    protected $table = 'erik_user_points_transfer';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'from_user_id', 'to_user_id', 'points', 'status',
    ];

}
