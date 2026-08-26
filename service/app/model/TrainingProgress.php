<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

class TrainingProgress extends Model
{
    protected $table = 'erik_training_progress';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'technician_id', 'course_id', 'progress',
        'completed_at', 'status',
    ];

    protected $casts = [
        'progress' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(TrainingCourse::class, 'course_id');
    }

    public function technician()
    {
        return $this->belongsTo(TechnicianProfile::class, 'technician_id');
    }

}
