<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Encryptable\Encryptable;
use Illuminate\Database\Eloquent\SoftDeletes;
use support\Model;

class Store extends Model
{
    use SoftDeletes;

    protected $table = 'appointment_store';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'name', 'address', 'lat', 'lng', 'phone',
        'business_hours', 'images', 'status',
    ];

    protected $casts = [
        'phone' => Encryptable::class,
        'lat' => 'float',
        'lng' => 'float',
        'business_hours' => 'array',
        'images' => 'array',
        'status' => 'integer',
    ];

    protected $hidden = ['deleted_at'];

}
