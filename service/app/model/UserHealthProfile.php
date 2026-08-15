<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

/**
 * 用户健康档案与服务偏好
 *
 * 一人一份：user_id 表级 UNIQUE（uk_user），见
 * database/migrations/2026_08_15_000504_user_health_profile.sql
 * id 由 \support\Model::generateId()（snowflake）生成
 */
class UserHealthProfile extends Model
{
    protected $table = 'erik_user_health_profile';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'user_id', 'allergies', 'chronic_diseases',
        'preferred_technician_id', 'preferred_time', 'notes',
    ];

    protected $casts = [
        'preferred_technician_id' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
