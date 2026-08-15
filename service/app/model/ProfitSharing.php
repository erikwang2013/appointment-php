<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

class ProfitSharing extends Model
{
    protected $table = 'erik_profit_sharing';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    public const STATUS_PENDING  = 'pending';
    public const STATUS_SUCCESS  = 'success';
    public const STATUS_FAILED   = 'failed';
    public const STATUS_DISABLED = 'disabled';

    protected $fillable = [
        'user_id', 'order_id', 'sharing_no', 'amount', 'ratio',
        'status', 'response',
    ];

    protected $casts = [
        'amount'   => 'decimal:2',
        'ratio'    => 'decimal:4',
        'response' => 'json',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
