<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

class UserPointsExchange extends Model
{
    protected $table = 'appointment_user_points_exchange';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false; // 表仅 created_at（DB 默认 CURRENT_TIMESTAMP）

    protected $fillable = [
        'user_id', 'goods_id', 'goods_name', 'points_cost', 'result',
    ];

}
