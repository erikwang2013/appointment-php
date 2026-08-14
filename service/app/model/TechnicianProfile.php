<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Encryptable\Encryptable;
use Erikwang2013\Snowflake\Snowflake;
use Illuminate\Database\Eloquent\SoftDeletes;
use support\Model;

class TechnicianProfile extends Model
{
    use SoftDeletes;

    protected $table = 'erik_technician_profile';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'user_id', 'real_name', 'gender', 'id_card',
        'id_card_front', 'id_card_back', 'avatar', 'intro',
        'cover_image', 'video_url', 'certificates',
        'rating', 'order_count', 'favorite_count', 'tier_id',
        'status', 'audit_remark', 'audited_at',
    ];

    protected $casts = [
        // real_name 必须明文：admin 按技师姓名 LIKE 搜索（TechnicianController 等 3 处）
        'id_card' => Encryptable::class,
        'rating' => 'decimal:1',
        'order_count' => 'integer',
        'favorite_count' => 'integer',
        'tier_id' => 'integer',
        'gender' => 'integer',
        'certificates' => 'array',
    ];

    protected $hidden = ['id_card', 'id_card_front', 'id_card_back', 'deleted_at'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
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
