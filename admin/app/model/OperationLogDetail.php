<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\model;

use support\Model;

class OperationLogDetail extends Model
{
    protected $table = 'appointment_operation_log_detail';
    protected $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;
    protected $keyType = 'int';

    protected $fillable = ['log_id', 'snapshot_before', 'snapshot_after', 'response_body'];

    public function log()
    {
        return $this->belongsTo(OperationLog::class, 'log_id');
    }
}
