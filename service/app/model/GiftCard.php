<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

class GiftCard extends Model
{
    protected $table = 'appointment_gift_card';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'code', 'type', 'amount', 'gift_name',
        'status', 'used_by', 'used_at',
    ];

}
