<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

class Banner extends Model
{
    protected $table = 'appointment_banner';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'position', 'image', 'jump_type', 'jump_value',
        'sort', 'status',
    ];

    protected $casts = [
        'sort' => 'integer',
        'status' => 'integer',
    ];

}
