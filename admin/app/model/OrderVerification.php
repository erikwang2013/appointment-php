<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Snowflake\Snowflake;
use support\Model;

/**
 * 订单核销记录模型（admin 端只读展示）
 */
class OrderVerification extends Model
{
    protected $table = 'erik_order_verification';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'id', 'order_id', 'code', 'verified_by',
        'verify_type', 'location', 'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    // ── 核销类型常量 ──
    public const VERIFY_TYPE_SCAN = 'scan';
    public const VERIFY_TYPE_SELF = 'self';

    /**
     * 所属订单
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

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
