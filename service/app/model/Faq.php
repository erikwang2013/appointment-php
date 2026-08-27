<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

class Faq extends Model
{
    protected $table = 'appointment_faq';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'title', 'content', 'sort', 'status',
    ];

    protected $casts = [
        'sort' => 'integer',
        'status' => 'integer',
    ];

}
