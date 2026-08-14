<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

/**
 * 成长等级档位表
 *
 * level 从 1 起，min_growth 递增；benefits 为 JSON 权益
 * （如 {"discount_rate":0.95,"points_multiplier":1.2}）。
 * 种子 5 档：青铜 0 / 白银 100 / 黄金 500 / 铂金 2000 / 钻石 5000。
 */
class GrowthLevel extends Model
{
    protected $table = 'erik_growth_level';

    public $timestamps = false;

    protected $fillable = [
        'level', 'name', 'min_growth', 'benefits',
    ];

    protected $casts = [
        'min_growth' => 'int',
        'benefits'   => 'array',
    ];

    /**
     * 全部等级（升序）
     */
    public static function allLevels(): array
    {
        return self::orderBy('min_growth')->get()->all();
    }
}
