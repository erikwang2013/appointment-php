<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

class TechnicianSchedule extends Model
{
    protected $table = 'appointment_technician_schedule';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'technician_id', 'date', 'time_slots', 'status',
    ];

    protected $casts = [
        'time_slots' => 'array',
        'status' => 'integer',
    ];

    public function technician()
    {
        return $this->belongsTo(TechnicianProfile::class, 'technician_id');
    }

}
