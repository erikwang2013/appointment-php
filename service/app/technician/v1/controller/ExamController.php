<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\technician\v1\controller;

use app\common\BaseController;
use app\model\Exam;
use app\model\ExamAttempt;
use app\model\ExamQuestion;
use support\Log;
use Webman\Http\Request;

/**
 * 技师考试控制器
 *
 * 技师端：查看可用考试、开始考试、提交答案、自动评分
 */
class ExamController extends BaseController
{
    /**
     * 获取技师可用的考试列表
     *
     * GET /api/technician/exams
     *
     * @param  Request $request
     * @return \Webman\Http\Response
     */
    public function index(Request $request)
    {
        $technicianId = $request->user_id;

        $exams = Exam::where('status', Exam::STATUS_PUBLISHED)
            ->with(['questions:id,exam_id'])
            ->get()
            ->map(function (Exam $exam) use ($technicianId): array {
                // 获取上次尝试记录
                $lastAttempt = ExamAttempt::where('exam_id', $exam->id)
                    ->where('technician_id', $technicianId)
                    ->orderBy('created_at', 'desc')
                    ->first();

                $canRetake = true;
                if ($lastAttempt && $lastAttempt->isSubmitted()) {
                    $canRetake = !$lastAttempt->passed; // 通过的不能再考
                }

                return [
                    'id'               => $exam->id,
                    'title'            => $exam->title,
                    'passing_score'    => $exam->passing_score,
                    'duration_minutes' => $exam->duration_minutes,
                    'total_questions'  => $exam->questions->count(),
                    'total_score'      => $exam->totalScore(),
                    'last_score'       => $lastAttempt?->score,
                    'last_passed'      => $lastAttempt?->passed,
                    'can_retake'       => $canRetake,
                ];
            });

        return $this->success($exams);
    }

    /**
     * 开始考试
     *
     * POST /api/technician/exam/start/{exam_id}
     *
     * 返回考题列表（不含答案），创建作答记录
     *
     * @param  string  $examId
     * @param  Request $request
     * @return \Webman\Http\Response
     */
    public function start(string $examId, Request $request)
    {
        $examId = $this->decodeId($examId);
        $examId = (string)$examId;
        $technicianId = $request->user_id;

        $exam = Exam::where('status', Exam::STATUS_PUBLISHED)->find($examId);
        if (!$exam) {
            return $this->error('exam_not_found', 404);
        }

        // 检查是否已通过
        $hasPassed = ExamAttempt::where('exam_id', $examId)
            ->where('technician_id', $technicianId)
            ->where('passed', true)
            ->exists();

        if ($hasPassed) {
            return $this->error('exam_already_attempted');
        }

        // 创建作答记录
        try {
            $attempt = new ExamAttempt();
            $attempt->id            = ExamAttempt::generateId();
            $attempt->exam_id       = $examId;
            $attempt->technician_id = $technicianId;
            $attempt->started_at    = date('Y-m-d H:i:s');
            $attempt->total_score   = $exam->totalScore();
            $attempt->score         = 0;
            $attempt->passed        = false;
            $attempt->save();

            // 返回考题（不含答案）
            $questions = ExamQuestion::where('exam_id', $examId)
                ->orderBy('id')
                ->get()
                ->map(fn(ExamQuestion $q): array => [
                    'id'      => $q->id,
                    'content' => $q->content,
                    'type'    => $q->type,
                    'options' => $q->options,
                    'score'   => $q->score,
                ]);

            return $this->success([
                'attempt_id'       => $attempt->id,
                'exam_title'       => $exam->title,
                'duration_minutes' => $exam->duration_minutes,
                'passing_score'    => $exam->passing_score,
                'total_score'      => $exam->totalScore(),
                'questions'        => $questions,
            ], 'exam_started');
        } catch (\Throwable $e) {
            Log::error('[ExamController] start error: ' . $e->getMessage());
            return $this->error('开始考试失败，请稍后再试');
        }
    }

    /**
     * 提交答案并自动评分
     *
     * POST /api/technician/exam/submit/{attempt_id}
     *
     * Body: { "answers": { "question_id_1": ["A"], "question_id_2": ["B","C"], ... } }
     *
     * @param  string  $attemptId
     * @param  Request $request
     * @return \Webman\Http\Response
     */
    public function submit(string $attemptId, Request $request)
    {
        $attemptId = $this->decodeId($attemptId);
        $technicianId = $request->user_id;
        $answers      = $request->input('answers', []);

        if (!is_array($answers) || empty($answers)) {
            return $this->error('answers is required and must be an array');
        }

        $attempt = ExamAttempt::where('id', $attemptId)
            ->where('technician_id', $technicianId)
            ->first();

        if (!$attempt) {
            return $this->error('作答记录不存在', 404);
        }

        if ($attempt->isSubmitted()) {
            return $this->error('答卷已提交，请勿重复提交');
        }

        // 检查超时
        if ($attempt->isTimeUp()) {
            $attempt->submitted_at = date('Y-m-d H:i:s');
            $attempt->passed       = false;
            $attempt->save();
            return $this->error('exam_time_up');
        }

        try {
            // 获取考题及正确答案
            $questions = ExamQuestion::where('exam_id', $attempt->exam_id)
                ->get()
                ->keyBy('id');

            $totalScore = 0;

            foreach ($answers as $questionId => $userAnswer) {
                if (!isset($questions[$questionId])) {
                    continue;
                }

                $question     = $questions[$questionId];
                $correctAnswer = $question->answer;

                if (!is_array($correctAnswer)) {
                    continue;
                }

                $userAnswerArr = is_array($userAnswer) ? $userAnswer : [$userAnswer];

                // 自动评分：答案完全匹配即得分
                sort($userAnswerArr);
                sort($correctAnswer);

                if ($userAnswerArr === $correctAnswer) {
                    $totalScore += (int)$question->score;
                }
            }

            // 判断通过
            $exam           = $attempt->exam;
            $passingScore   = $exam->passing_score ?? 60;
            $passed         = $totalScore >= $passingScore;

            $attempt->score        = $totalScore;
            $attempt->total_score  = $questions->sum('score');
            $attempt->passed       = $passed;
            $attempt->submitted_at = date('Y-m-d H:i:s');
            $attempt->save();

            return $this->success([
                'score'         => $attempt->score,
                'total_score'   => $attempt->total_score,
                'passed'        => $attempt->passed,
                'passing_score' => $passingScore,
                'submitted_at'  => $attempt->submitted_at,
            ], $passed ? 'exam_passed' : 'exam_failed');
        } catch (\Throwable $e) {
            Log::error('[ExamController] submit error: ' . $e->getMessage());
            return $this->error('提交答卷失败，请稍后再试');
        }
    }
}
