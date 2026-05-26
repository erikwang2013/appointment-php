<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\Snowflake\Snowflake;
use support\Model;

/**
 * 考试题目模型
 *
 * 表: erik_exam_question
 */
class ExamQuestion extends Model
{
    protected $table = 'erik_exam_question';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'exam_id', 'content', 'type',
        'options', 'answer', 'score',
        'created_at', 'updated_at',
    ];

    protected $casts = [
        'options' => 'array',
        'answer'  => 'array',
        'score'   => 'integer',
    ];

    // ── 题型常量 ──
    public const TYPE_SINGLE    = 'single';
    public const TYPE_MULTI     = 'multi';
    public const TYPE_TRUE_FALSE = 'truefalse';

    // ── 关联关系 ──

    public function exam()
    {
        return $this->belongsTo(Exam::class, 'exam_id');
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
