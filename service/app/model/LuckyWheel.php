<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Snowflake\Snowflake;
use support\Model;

/**
 * 幸运转盘奖品模型
 *
 * cost_points 为单次抽奖消耗积分；weight 权重（0=不可中奖）；
 * stock -1=不限量；prize_type: points 积分返还 / coupon 优惠券 /
 * balance 余额 / none 谢谢参与。
 */
class LuckyWheel extends Model
{
    protected $table = 'erik_lucky_wheel';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'name', 'cost_points', 'prize_type', 'prize_value',
        'weight', 'stock', 'sort', 'status',
    ];

    protected $casts = [
        'prize_value' => 'float',
        'weight'      => 'int',
        'stock'       => 'int',
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
