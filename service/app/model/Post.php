<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Snowflake\Snowflake;
use Illuminate\Database\Eloquent\Builder;
use support\Model;

/**
 * 社区帖子模型
 *
 * @method static Builder pinned()  置顶帖子
 */
class Post extends Model
{
    protected $table = 'erik_community_post';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'user_id', 'title', 'content', 'images',
        'likes', 'comments_count', 'status', 'is_pinned',
    ];

    protected $casts = [
        'images'         => 'array',
        'likes'          => 'integer',
        'comments_count' => 'integer',
        'status'         => 'integer',
        'is_pinned'      => 'integer',
    ];

    // ── 状态常量 ──
    public const STATUS_NORMAL = 1;
    public const STATUS_HIDDEN = 0;

    // ── 关联关系 ──

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'post_id');
    }

    // ── 查询作用域 ──

    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', 1);
    }

    // ── ID 生成 ──

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
