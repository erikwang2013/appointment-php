<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\technician\v1\controller;

use app\common\BaseController;
use app\model\TechnicianSchedule;
use Webman\Http\Request;

/**
 * 技师排班控制器
 * 查看/设置技师的可用时间段
 */
class ScheduleController extends BaseController
{
    /**
     * 获取技师排班列表
     * GET /api/technician/schedule
     */
    public function index(Request $request)
    {
        $technicianId = $request->technician_id;
        $startDate = $request->input('start_date', '');
        $endDate = $request->input('end_date', '');

        $query = TechnicianSchedule::where('technician_id', $technicianId);

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        $schedules = $query->orderBy('date')->get();

        return $this->success($schedules);
    }

    /**
     * 更新/创建技师排班（按日期 upsert）
     * PUT /api/technician/schedule
     */
    public function update(Request $request)
    {
        $technicianId = $request->technician_id;
        $date = $request->input('date', '');
        $timeSlots = $request->input('time_slots', []);
        $status = (int)$request->input('status', 1);

        if (empty($date)) {
            return $this->error('请选择日期');
        }

        if (!is_array($timeSlots)) {
            return $this->error('时间段格式不正确');
        }

        // 校验 time_slots 格式: [{start, end}, ...]
        foreach ($timeSlots as $slot) {
            if (empty($slot['start']) || empty($slot['end'])) {
                return $this->error('时间段格式不正确，需要 start 和 end');
            }
        }

        $schedule = TechnicianSchedule::where('technician_id', $technicianId)
            ->where('date', $date)
            ->first();

        if ($schedule) {
            $schedule->time_slots = $timeSlots;
            $schedule->status = $status;
            $schedule->save();
        } else {
            $schedule = TechnicianSchedule::create([
                'id' => TechnicianSchedule::generateId(),
                'technician_id' => $technicianId,
                'date' => $date,
                'time_slots' => $timeSlots,
                'status' => $status,
            ]);
        }

        return $this->success($schedule, '排班更新成功');
    }
}
