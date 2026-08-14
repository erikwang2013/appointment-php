<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

/**
 * 用户成长值流水表
 *
 * value 列存单次增量（正数），balance 列是单次增量快照，
 * 真实累计成长值 = SUM(value)（与 erik_user_points 同模式）。
 * 类型：consume=消费（每实付 1 元 1 点）/ signin=签到 / review=评价。
 */
class UserGrowth extends Model
{
    protected $table = 'erik_user_growth';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'type', 'value', 'balance',
    ];

    public const TYPE_CONSUME = 'consume';
    public const TYPE_SIGNIN  = 'signin';
    public const TYPE_REVIEW  = 'review';

    public const VALUE_SIGNIN = 10;
    public const VALUE_REVIEW = 20;

    /** 类型白名单（records 过滤用） */
    public const TYPES = [
        self::TYPE_CONSUME,
        self::TYPE_SIGNIN,
        self::TYPE_REVIEW,
    ];

    /**
     * 用户累计成长值
     */
    public static function totalFor(int|string $userId): int
    {
        return (int) self::where('user_id', $userId)->sum('value');
    }

    /**
     * 入账一条成长值（幂等由调用方业务保证：签到/评价/支付成功均只触发一次）
     */
    public static function add(int|string $userId, string $type, int $value): void
    {
        self::create([
            'id'      => self::generateId(),
            'user_id' => $userId,
            'type'    => $type,
            'value'   => $value,
            'balance' => $value,
        ]);
    }
}
