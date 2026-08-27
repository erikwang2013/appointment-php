<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

class PlatformAgreement extends Model
{
    protected $table = 'appointment_platform_agreement';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'type', 'title', 'content', 'version', 'status', 'published_at',
    ];

    protected $casts = [
        'status' => 'integer',
        'published_at' => 'datetime',
    ];

}
