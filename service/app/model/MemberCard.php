<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

class MemberCard extends Model
{
    protected $table = 'erik_member_card';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'name', 'type', 'price', 'duration_days',
        'total_times', 'services', 'status',
    ];

    protected $casts = [
        'services' => 'array',
    ];

}
