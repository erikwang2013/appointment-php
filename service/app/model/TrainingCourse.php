<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

class TrainingCourse extends Model
{
    protected $table = 'erik_training_course';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'title', 'type', 'url', 'content',
        'duration_minutes', 'sort', 'status',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
    ];

}
