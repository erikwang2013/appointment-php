<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Snowflake\Snowflake;
use support\Model;

/**
 * 订单改期记录模型
 *
 * 每次改期落一条记录：old → new 服务时间/技师，保留审计轨迹
 * （created_at 由 DB 默认 CURRENT_TIMESTAMP 写入，模型不自动维护时间戳）
 */
class OrderReschedule extends Model
{
    protected $table = 'erik_order_reschedule';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'order_id', 'old_service_time', 'new_service_time',
        'old_technician_id', 'new_technician_id', 'reason', 'created_at',
    ];

    protected $casts = [
        'old_service_time' => 'datetime',
        'new_service_time' => 'datetime',
        'created_at'       => 'datetime',
    ];

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
