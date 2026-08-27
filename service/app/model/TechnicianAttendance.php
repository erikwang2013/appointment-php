<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

class TechnicianAttendance extends Model
{
    protected $table = 'appointment_technician_attendance';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'technician_id', 'date', 'check_in_at', 'check_out_at',
        'clean_photo', 'status', 'remark',
    ];

    protected $casts = [
        'technician_id' => 'integer',
    ];

    public function technician()
    {
        return $this->belongsTo(TechnicianProfile::class, 'technician_id');
    }
}
