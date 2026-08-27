<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

/**
 * 常用发票抬头模型
 *
 * 用户维护的常用开票抬头库，申请开票时可通过 title_id 带入抬头信息。
 * uk_user_title (user_id, title_type, invoice_title) 保证同用户同类型同抬头唯一。
 */
class InvoiceTitle extends Model
{
    protected $table = 'appointment_invoice_title';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'user_id', 'title_type', 'invoice_title', 'tax_no', 'is_default',
    ];

    // ── 抬头类型常量 ──
    public const TITLE_TYPE_PERSONAL = 'personal';
    public const TITLE_TYPE_COMPANY  = 'company';
}
