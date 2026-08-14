<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Snowflake\Snowflake;
use support\Model;

/**
 * 电子发票模型（管理端）
 *
 * 用户对已完成订单/已支付充值申请开票，管理端审核开票（issued）/ 驳回（rejected）。
 * uk_order_type (order_id, order_type) 保证一单仅可申请一次。
 */
class Invoice extends Model
{
    protected $table = 'erik_invoice';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'user_id', 'order_id', 'order_type', 'title_type',
        'invoice_title', 'tax_no', 'amount', 'email',
        'status', 'issued_no', 'issued_at', 'remark',
    ];

    protected $casts = [
        'amount'    => 'decimal:2',
        'issued_at' => 'datetime',
    ];

    // ── 发票状态常量 ──
    public const STATUS_PENDING  = 'pending';
    public const STATUS_ISSUED   = 'issued';
    public const STATUS_REJECTED = 'rejected';

    // ── 业务单类型常量 ──
    public const ORDER_TYPE_SERVICE  = 'service';
    public const ORDER_TYPE_RECHARGE = 'recharge';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 生成 snowflake ID
     *
     * admin 端 support\Model 被 composer 解析到 vendor/webman/database 的
     * 版本（无 generateId），须在模型内显式定义（同 TechnicianWithdrawal）。
     */
    public static function generateId(): string
    {
        $snowflakeConfig = config('snowflake');
        $snowflake = new Snowflake(
            (int)($snowflakeConfig['datacenter_id'] ?? 1),
            (int)($snowflakeConfig['worker_id'] ?? 1)
        );
        return (string) $snowflake->id();
    }
}
