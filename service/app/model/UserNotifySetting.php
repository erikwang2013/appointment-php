<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

/**
 * 用户消息偏好设置（erik_user_notify_setting）
 *
 * 每用户每类型至多一行（user_id + type 唯一，见 uk_user_type）；
 * 未插入行视为默认开。类型常量见 NotificationReminderService::NOTIFY_TYPE_*。
 */
class UserNotifySetting extends Model
{
    protected $table = 'erik_user_notify_setting';
    public $timestamps = true;

    protected $fillable = ['user_id', 'type', 'switch'];

    protected $casts = [
        'user_id' => 'string',
        'switch'  => 'integer',
    ];
}
