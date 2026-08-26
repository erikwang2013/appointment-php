<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Encryptable\Encryptable;
use support\Model;

class UserAddress extends Model
{
    protected $table = 'erik_user_address';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'user_id', 'contact_name', 'contact_phone', 'province',
        'city', 'district', 'detail', 'lat', 'lng', 'is_default',
    ];

    protected $casts = [
        'contact_phone' => Encryptable::class,
        'is_default' => 'integer',
        'lat' => 'float',
        'lng' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
