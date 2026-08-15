<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

/**
 * APP 推送日志
 *
 * 记录 pushToUser 每次进入推送链路的请求（用户/标题/内容/自定义字段/
 * 状态/厂商），便于排查推送丢失与厂商对接问题。
 */
class PushLog extends Model
{
    protected $table = 'erik_push_log';

    public $timestamps = true;

    protected $fillable = [
        'user_id', 'title', 'content', 'payload', 'status', 'provider',
    ];

    protected $casts = [
        'user_id' => 'string',
        'payload' => 'array',
    ];
}
