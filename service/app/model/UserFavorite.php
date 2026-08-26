<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

class UserFavorite extends Model
{
    protected $table = 'erik_user_favorite';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'user_id', 'target_type', 'target_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
