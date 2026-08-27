<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

class Moment extends Model
{
    protected $table = 'appointment_moment';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'content', 'images', 'sort', 'status', 'published_at',
    ];

    protected $casts = [
        'images' => 'array',
        'sort' => 'integer',
        'status' => 'integer',
        'published_at' => 'datetime',
    ];

}
