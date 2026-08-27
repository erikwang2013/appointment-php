<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

/**
 * 签到记录模型
 * 跟踪用户每日签到、奖励积分、连续签到天数
 */
class CheckIn extends Model
{
    protected $table = 'appointment_check_in';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'user_id', 'date', 'points_awarded', 'consecutive_days',
    ];

    protected $casts = [
        'points_awarded' => 'integer',
        'consecutive_days' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
