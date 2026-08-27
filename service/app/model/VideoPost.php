<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Illuminate\Database\Eloquent\Builder;
use support\Model;

/**
 * 技师短视频模型
 *
 * @method static Builder byTechnician(string $technicianId) 按技师筛选
 * @method static Builder published()                       已发布
 */
class VideoPost extends Model
{
    protected $table = 'appointment_video_post';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'technician_id', 'title', 'video_url', 'cover_url',
        'duration', 'views', 'likes', 'status',
    ];

    protected $casts = [
        'duration' => 'integer',
        'views'    => 'integer',
        'likes'    => 'integer',
        'status'   => 'integer',
    ];

    // ── 状态常量 ──
    public const STATUS_PENDING  = 0;
    public const STATUS_PUBLISHED = 1;
    public const STATUS_REJECTED = 2;

    // ── 关联关系 ──

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    // ── 查询作用域 ──

    public function scopeByTechnician(Builder $query, string $technicianId): Builder
    {
        return $query->where('technician_id', $technicianId);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

}
