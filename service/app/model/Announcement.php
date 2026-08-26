<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

class Announcement extends Model
{
    protected $table = 'erik_announcement';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'title', 'content', 'sort', 'status', 'published_at',
    ];

    protected $casts = [
        'sort' => 'integer',
        'status' => 'integer',
        'published_at' => 'datetime',
    ];

}
