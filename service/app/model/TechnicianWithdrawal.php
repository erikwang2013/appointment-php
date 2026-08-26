<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Encryptable\Encryptable;
use support\Model;

class TechnicianWithdrawal extends Model
{
    protected $table = 'erik_technician_withdrawal';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'technician_id', 'withdrawal_no', 'amount', 'actual_amount',
        'commission_fee', 'account_type', 'account_name', 'account_no',
        'status', 'audit_remark', 'audited_at', 'completed_at',
    ];

    protected $casts = [
        'account_name' => Encryptable::class,
        'account_no' => Encryptable::class,
        'amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'commission_fee' => 'decimal:2',
    ];

    protected $hidden = ['account_name', 'account_no'];

    public function technician()
    {
        return $this->belongsTo(TechnicianProfile::class, 'technician_id');
    }

    /**
     * 生成提现单号: WD + YmdHis + 4位随机数
     */
    public static function generateWithdrawalNo(): string
    {
        return 'WD' . date('YmdHis') . str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

}
