<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

/**
 * 服务记录模型
 * 记录服务前/后照片、备注信息
 */
class ServiceRecord extends Model
{
    protected $table = 'appointment_service_record';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'order_id', 'technician_id', 'before_photos', 'after_photos', 'notes',
    ];

    protected $casts = [
        'before_photos' => 'array',
        'after_photos' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

}
