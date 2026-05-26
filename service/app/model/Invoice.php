<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Snowflake\Snowflake;
use support\Model;

/**
 * 发票模型
 */
class Invoice extends Model
{
    protected $table = 'erik_invoice';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'id', 'user_id', 'order_id', 'type', 'title',
        'tax_no', 'email', 'amount', 'status', 'issued_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'issued_at' => 'datetime',
    ];

    // ── 发票类型常量 ──
    public const TYPE_PERSONAL = 'personal';
    public const TYPE_COMPANY  = 'company';

    // ── 状态常量 ──
    public const STATUS_PENDING = 'pending';
    public const STATUS_ISSUED  = 'issued';

    /**
     * 所属用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 关联订单
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
