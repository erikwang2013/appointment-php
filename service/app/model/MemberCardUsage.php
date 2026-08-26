<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

class MemberCardUsage extends Model
{
    protected $table = 'erik_member_card_usage';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'user_card_id', 'order_id', 'service_id', 'used_at',
        'status', // M3: active=有效 cancelled=已撤销（退款/取消归还）
    ];

}
