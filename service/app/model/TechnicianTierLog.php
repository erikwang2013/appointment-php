<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

/**
 * 技师等级变更日志
 */
class TechnicianTierLog extends Model
{
    protected $table = 'appointment_technician_tier_log';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'id', 'technician_id', 'old_tier_id', 'new_tier_id', 'reason',
    ];

    protected $casts = [
        'technician_id' => 'string',
        'old_tier_id'   => 'string',
        'new_tier_id'   => 'string',
    ];

}
