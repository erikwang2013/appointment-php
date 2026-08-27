<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

/**
 * 排队等待模型
 * 用户可选择技师/服务/时间加入等待队列
 */
class Waitlist extends Model
{
    protected $table = 'appointment_waitlist';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    // ── 排队状态常量 ──
    public const STATUS_WAITING   = 'waiting';
    public const STATUS_NOTIFIED  = 'notified';
    public const STATUS_EXPIRED   = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id', 'service_id', 'technician_id',
        'preferred_date', 'preferred_time', 'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

}
