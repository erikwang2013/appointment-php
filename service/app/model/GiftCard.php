<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Snowflake\Snowflake;
use support\Model;

class GiftCard extends Model
{
    protected $table = 'erik_gift_card';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'code', 'type', 'amount', 'gift_name',
        'status', 'used_by', 'used_at',
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
