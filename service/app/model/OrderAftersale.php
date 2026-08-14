<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Snowflake\Snowflake;
use support\Model;

/**
 * 售后（退换货）申请模型
 */
class OrderAftersale extends Model
{
    protected $table = 'erik_order_aftersale';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'id', 'aftersale_no', 'order_id', 'user_id',
        'type', 'reason', 'status', 'refund_amount',
        'review_remark', 'reviewed_at',
    ];

    protected $casts = [
        'refund_amount' => 'float',
        'reviewed_at' => 'datetime',
    ];

    // ── 售后类型常量 ──
    public const TYPE_REFUND = 'refund';
    public const TYPE_EXCHANGE = 'exchange';

    // ── 售后状态常量 ──
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_COMPLETED = 'completed';

    /**
     * 所属订单
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * 生成售后单号
     * 格式: AS + YmdHis + 4位随机数字
     */
    public static function generateAftersaleNo(): string
    {
        return 'AS' . date('YmdHis') . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
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
