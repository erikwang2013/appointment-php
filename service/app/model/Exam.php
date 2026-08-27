<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

/**
 * 技师考试模型
 *
 * 表: appointment_exam
 */
class Exam extends Model
{
    protected $table = 'appointment_exam';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'title', 'course_id', 'passing_score',
        'duration_minutes', 'status',
        'created_at', 'updated_at',
    ];

    protected $casts = [
        'passing_score'    => 'integer',
        'duration_minutes' => 'integer',
    ];

    // ── 状态常量 ──
    public const STATUS_DRAFT   = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_CLOSED  = 'closed';

    // ── 关联关系 ──

    public function questions()
    {
        return $this->hasMany(ExamQuestion::class, 'exam_id');
    }

    public function attempts()
    {
        return $this->hasMany(ExamAttempt::class, 'exam_id');
    }

    public function course()
    {
        return $this->belongsTo(TrainingCourse::class, 'course_id');
    }

    // ── 业务方法 ──

    /**
     * 判断考题是否发布
     */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * 获取总题数
     */
    public function totalQuestions(): int
    {
        return $this->questions()->count();
    }

    /**
     * 获取总分
     */
    public function totalScore(): int
    {
        return (int)$this->questions()->sum('score');
    }

    // ── ID 生成 ──

}
