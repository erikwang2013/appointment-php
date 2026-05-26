<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Snowflake\Snowflake;
use support\Model;

/**
 * 订单退款记录模型
 */
class OrderRefund extends Model
{
    protected $table = 'erik_order_refund';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'id', 'order_id', 'payment_id', 'refund_no',
        'amount', 'ratio', 'reason', 'status', 'refunded_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'ratio' => 'float',
        'refunded_at' => 'datetime',
    ];

    // ── 退款状态常量 ──
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED  = 'failed';

    /**
     * 所属订单
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * 关联支付记录
     */
    public function payment()
    {
        return $this->belongsTo(OrderPayment::class, 'payment_id');
    }

    /**
     * 生成退款流水号
     * 格式: REF + YmdHis + 4位随机数字
     */
    public static function generateRefundNo(): string
    {
        return 'REF' . date('YmdHis') . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
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
