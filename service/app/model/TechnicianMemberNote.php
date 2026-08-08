<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Encryptable\Encryptable;
use Erikwang2013\Snowflake\Snowflake;
use support\Model;

class TechnicianMemberNote extends Model
{
    protected $table = 'erik_technician_member_note';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'technician_id', 'user_id', 'order_id', 'content',
    ];

    protected $casts = [
        'content' => Encryptable::class,
    ];

    public function technician()
    {
        return $this->belongsTo(TechnicianProfile::class, 'technician_id');
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
