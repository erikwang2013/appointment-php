<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Snowflake\Snowflake;
use support\Model;

/**
 * 幸运转盘抽奖记录模型（表仅 created_at，关闭 Eloquent 自动写时间戳）
 */
class WheelRecord extends Model
{
    protected $table = 'erik_wheel_record';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'wheel_id', 'prize_type', 'prize_value',
        'result', 'client_token',
    ];

    protected $casts = [
        'prize_value' => 'float',
    ];

    /**
     * 生成 snowflake ID
     */
    public static function generateId(): string
    {
        $snowflakeConfig = config('snowflake');
        $snowflake = new Snowflake(
            (int)($snowflakeConfig['datacenter_id'] ?? 1),
            (int)($snowflakeConfig['worker_id'] ?? 1)
        );
        return (string)$snowflake->id();
    }
}
