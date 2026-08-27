<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

class TechnicianEarning extends Model
{
    protected $table = 'appointment_technician_earnings';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'technician_id', 'order_id', 'type', 'amount',
        'description', 'status', 'settled_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function technician()
    {
        return $this->belongsTo(TechnicianProfile::class, 'technician_id');
    }

    /**
     * 关联订单（admin ScheduledTaskController::autoSettle 的 whereHas 依赖）
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

}
