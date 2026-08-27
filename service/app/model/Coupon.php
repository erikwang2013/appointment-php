<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

class Coupon extends Model
{
    protected $table = 'appointment_coupon';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'name', 'type', 'amount', 'min_amount',
        'total_qty', 'remain_qty', 'start_at', 'end_at',
        'status', 'created_by',
    ];

}
