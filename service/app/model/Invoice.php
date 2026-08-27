<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

/**
 * 电子发票模型
 *
 * 用户对已完成订单（service）/ 已支付充值（recharge）申请开票，
 * 管理端审核开票（issued）/ 驳回（rejected）。
 * uk_order_type (order_id, order_type) 保证一单仅可申请一次。
 */
class Invoice extends Model
{
    protected $table = 'appointment_invoice';
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

    // ── 抬头类型常量 ──
    public const TITLE_TYPE_PERSONAL = 'personal';
    public const TITLE_TYPE_COMPANY  = 'company';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
