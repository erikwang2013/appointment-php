<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\TrainingCourse;
use app\model\TrainingProgress;
use app\model\TechnicianProfile;
use support\Request;
use support\Log;
use support\Response;

class TrainingController extends BaseController
{
    /**
     * 课程列表
     * 筛选: type/status
     */
    public function index(Request $request): Response
    {
        $page   = (int) $request->input('page', 1);
        $limit  = (int) $request->input('limit', 15);
        $type   = $request->input('type', '');
        $status = $request->input('status');
        $keyword = $request->input('keyword', '');

        $query = TrainingCourse::query();
        if ($type) {
            $query->where('type', $type);
        }
        if ($status !== null && $status !== '') {
            $query->where('status', (int) $status);
        }
        if ($keyword) {
            $query->where('title', 'like', "%{$keyword}%");
        }

        $total = $query->count();
        $list  = $query->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->orderBy('sort', 'asc')
                       ->orderBy('id', 'desc')
                       ->get()
                       ->map(fn($c) => $c->toArray());

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 创建课程
     */
    public function store(Request $request): Response
    {
        $validator = validator($request->all(), [
            'title'            => 'required|string|max:200',
            'type'             => 'required|string|in:video,article',
            'duration_minutes' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422);
        }

        $course = new TrainingCourse();
        $course->id               = (string) $this->generateId();
        $course->title            = $request->input('title');
        $course->type             = $request->input('type');
        $course->url              = $request->input('url', '');
        $course->content          = $request->input('content', '');
        $course->duration_minutes = (int) $request->input('duration_minutes', 0);
        $course->sort             = (int) $request->input('sort', 0);
        $course->status           = (int) $request->input('status', 1);
        $course->save();

        return $this->success($course->toArray(), '课程创建成功');
    }

    /**
     * 课程详情
     */
    public function show(Request $request, string $hashid): Response
    {
        $id     = $this->decodeId($hashid);
        $course = TrainingCourse::find($id);
        if (!$course) {
            return $this->fail('课程不存在', 404);
        }

        $data = $course->toArray();
        // 统计学习人数
        $data['learner_count'] = TrainingProgress::where('course_id', $id)->count();
        $data['completed_count'] = TrainingProgress::where('course_id', $id)
            ->where('status', 'completed')->count();

        return $this->success($data);
    }

    /**
     * 更新课程
     */
    public function update(Request $request, string $hashid): Response
    {
        $id     = $this->decodeId($hashid);
        $course = TrainingCourse::find($id);
        if (!$course) {
            return $this->fail('课程不存在', 404);
        }

        if ($request->input('title') !== null) {
            $course->title = $request->input('title');
        }
        if ($request->input('type') !== null) {
            $course->type = $request->input('type');
        }
        if ($request->input('url') !== null) {
            $course->url = $request->input('url');
        }
        if ($request->input('content') !== null) {
            $course->content = $request->input('content');
        }
        if ($request->input('duration_minutes') !== null) {
            $course->duration_minutes = (int) $request->input('duration_minutes');
        }
        if ($request->input('sort') !== null) {
            $course->sort = (int) $request->input('sort');
        }
        if ($request->input('status') !== null) {
            $course->status = (int) $request->input('status');
        }
        $course->save();

        return $this->success($course->toArray(), '课程更新成功');
    }

    /**
     * 删除课程
     */
    public function destroy(Request $request, string $hashid): Response
    {
        $id     = $this->decodeId($hashid);
        $course = TrainingCourse::find($id);
        if (!$course) {
            return $this->fail('课程不存在', 404);
        }

        $adminId = $request->adminId ?? 0;
        $error   = $this->confirmPassword($adminId, $request->input('password', ''), $request);
        if ($error !== null) {
            return $this->fail($error, 422);
        }

        $course->delete();
        return $this->success([], '课程已删除');
    }

    /**
     * 查看技师学习进度
     */
    public function progress(Request $request, string $hashid): Response
    {
        $technicianId = $this->decodeId($hashid);
        $profile = TechnicianProfile::find($technicianId);
        if (!$profile) {
            return $this->fail('技师不存在', 404);
        }

        // 获取所有课程，关联该技师的学习进度
        $courses = TrainingCourse::where('status', 1)
            ->orderBy('sort')
            ->get()
            ->map(function ($course) use ($technicianId) {
                $progress = TrainingProgress::where('technician_id', $technicianId)
                    ->where('course_id', $course->id)
                    ->first();

                $data = $course->toArray();
                $data['progress'] = $progress ? $progress->progress : 0;
                $data['learning_status'] = $progress ? $progress->status : 'not_started';
                $data['completed_at'] = $progress ? $progress->completed_at : null;
                return $data;
            });

        $overallProgress = TrainingProgress::where('technician_id', $technicianId)
            ->where('status', 'completed')
            ->count();
        $totalCourses = TrainingCourse::where('status', 1)->count();
        $completionRate = $totalCourses > 0
            ? round(($overallProgress / $totalCourses) * 100, 1)
            : 0;

        return $this->success([
            'technician_id'    => $technicianId,
            'technician_name'  => $profile->real_name,
            'courses'          => $courses,
            'completed_count'  => $overallProgress,
            'total_courses'    => $totalCourses,
            'completion_rate'  => $completionRate,
        ]);
    }

    /**
     * 发送学习提醒通知
     */
    public function remind(Request $request, string $hashid): Response
    {
        $technicianId = $this->decodeId($hashid);
        $profile = TechnicianProfile::find($technicianId);
        if (!$profile) {
            return $this->fail('技师不存在', 404);
        }

        // 获取尚未完成的课程列表
        $pendingCourses = TrainingCourse::where('status', 1)
            ->whereNotIn('id', function ($query) use ($technicianId) {
                $query->select('course_id')
                    ->from('appointment_training_progress')
                    ->where('technician_id', $technicianId)
                    ->where('status', 'completed');
            })
            ->pluck('title')
            ->toArray();

        $this->dispatchTrainingReminder($technicianId, $pendingCourses);

        return $this->success([
            'technician_id'   => $technicianId,
            'pending_courses' => $pendingCourses,
            'pending_count'   => count($pendingCourses),
            'reminded_at'     => date('Y-m-d H:i:s'),
            'message'         => count($pendingCourses) > 0
                ? '已向技师发送学习提醒（待接入推送通道）'
                : '该技师已完成全部课程',
        ]);
    }
}
