<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Snowflake\Snowflake;
use support\Model;

/**
 * 技师考试作答记录模型
 *
 * 表: erik_exam_attempt
 */
class ExamAttempt extends Model
{
    protected $table = 'erik_exam_attempt';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'exam_id', 'technician_id', 'score', 'total_score',
        'passed', 'started_at', 'submitted_at',
        'created_at', 'updated_at',
    ];

    protected $casts = [
        'score'       => 'integer',
        'total_score' => 'integer',
        'passed'      => 'boolean',
        'started_at'  => 'datetime',
        'submitted_at' => 'datetime',
    ];

    // ── 关联关系 ──

    public function exam()
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    // ── 业务方法 ──

    /**
     * 判断是否已提交
     */
    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    /**
     * 判断考试是否超时
     *
     * @return bool
     */
    public function isTimeUp(): bool
    {
        if ($this->isSubmitted()) {
            return false; // 已提交的不算超时
        }

        $exam = $this->exam;
        if (!$exam || !$this->started_at) {
            return false;
        }

        $elapsedSeconds = time() - $this->started_at->getTimestamp();
        $timeLimitSeconds = ($exam->duration_minutes ?? 60) * 60;

        return $elapsedSeconds > $timeLimitSeconds;
    }

    // ── ID 生成 ──

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
