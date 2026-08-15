<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Snowflake\Snowflake;
use support\Model;

class ProfitSharing extends Model
{
    protected $table = 'erik_profit_sharing';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    public const STATUS_PENDING  = 'pending';
    public const STATUS_SUCCESS  = 'success';
    public const STATUS_FAILED   = 'failed';
    public const STATUS_DISABLED = 'disabled';

    protected $fillable = [
        'user_id', 'order_id', 'sharing_no', 'amount', 'ratio',
        'status', 'response',
    ];

    protected $casts = [
        'amount'   => 'decimal:2',
        'ratio'    => 'decimal:4',
        'response' => 'json',
    ];

    /**
     * 生成 snowflake ID（admin 的 support\Model 为 vendor 版，无 generateId，须显式定义）
     */
    public static function generateId(): string
    {
        $snowflakeConfig = config('snowflake');
        $snowflake = new Snowflake(
            (int) ($snowflakeConfig['datacenter_id'] ?? 1),
            (int) ($snowflakeConfig['worker_id'] ?? 1)
        );
        return (string) $snowflake->id();
    }
}
