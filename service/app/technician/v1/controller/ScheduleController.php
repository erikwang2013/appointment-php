<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\technician\v1\controller;

use app\common\BaseController;
use app\model\TechnicianSchedule;
use Webman\Http\Request;

/**
 * 技师排班控制器
 * 查看/设置技师的可用时间段（单日 upsert + 日期段批量，均带重叠冲突检测）
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
     * 更新/创建技师排班（按日期 upsert，整日时间段整体替换）
     * PUT /api/technician/schedule
     * body: { date, time_slots: [{start,end},...], status?: 0|1 }
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

        if (!is_array($timeSlots) || !$this->validateTimeSlots($timeSlots)) {
            return $this->error('时间段格式不正确，需要 [{start,end}, ...]');
        }

        $conflict = $this->findConflict($timeSlots);
        if ($conflict !== null) {
            return $this->error('与已有排班时间冲突：' . $conflict, 422);
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

    /**
     * 批量排班（日期段 × 时间段，逐日创建）
     * POST /api/technician/schedule/batch
     * body: { start_date, end_date, time_slots: [{start,end},...], weekdays?: [1-7], status?: 0|1 }
     * 日期段最多 7 天；某天已有排班时跳过该天并在响应中说明。
     */
    public function batch(Request $request)
    {
        $technicianId = $request->technician_id;
        $startDate = $request->input('start_date', '');
        $endDate = $request->input('end_date', '');
        $timeSlots = $request->input('time_slots', []);
        $weekdays = $request->input('weekdays', []);
        $status = (int)$request->input('status', 1);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$startDate)
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$endDate)
        ) {
            return $this->error('日期格式不正确');
        }
        if ($startDate > $endDate) {
            return $this->error('开始日期不能晚于结束日期');
        }

        // 防滥用：单次批量最多 7 天
        $dayCount = (int)((strtotime($endDate) - strtotime($startDate)) / 86400) + 1;
        if ($dayCount > 7) {
            return $this->error('排班日期范围最多支持 7 天', 422);
        }

        if (!is_array($timeSlots) || !$this->validateTimeSlots($timeSlots)) {
            return $this->error('时间段格式不正确，需要 [{start,end}, ...]');
        }
        $conflict = $this->findConflict($timeSlots);
        if ($conflict !== null) {
            return $this->error('与已有排班时间冲突：' . $conflict, 422);
        }
        if (!is_array($weekdays) || !$this->validateWeekdays($weekdays)) {
            return $this->error('weekdays 需为 1-7 的数组');
        }

        $created = [];
        $skipped = [];
        for ($i = 0; $i < $dayCount; $i++) {
            $date = date('Y-m-d', strtotime($startDate) + $i * 86400);
            if ($weekdays !== [] && !in_array((int)date('N', strtotime($date)), $weekdays, true)) {
                continue;
            }
            if (TechnicianSchedule::where('technician_id', $technicianId)->where('date', $date)->exists()) {
                $skipped[] = ['date' => $date, 'reason' => '该日期已有排班'];
                continue;
            }
            TechnicianSchedule::create([
                'id' => TechnicianSchedule::generateId(),
                'technician_id' => $technicianId,
                'date' => $date,
                'time_slots' => $timeSlots,
                'status' => $status,
            ]);
            $created[] = $date;
        }

        $message = sprintf('批量排班完成：成功 %d 天，跳过 %d 天', count($created), count($skipped));
        return $this->success(['created' => $created, 'skipped' => $skipped], $message);
    }

    // ────────────────────────────────────────────────
    // 私有辅助
    // ────────────────────────────────────────────────

    /**
     * 校验 time_slots 格式: [{start, end}, ...]，end 必须晚于 start
     */
    private function validateTimeSlots(array $timeSlots): bool
    {
        foreach ($timeSlots as $slot) {
            if (!is_array($slot)
                || empty($slot['start'])
                || empty($slot['end'])
                || !preg_match('/^\d{1,2}:\d{2}$/', (string)$slot['start'])
                || !preg_match('/^\d{1,2}:\d{2}$/', (string)$slot['end'])
            ) {
                return false;
            }
            if (strtotime((string)$slot['end']) <= strtotime((string)$slot['start'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * 查找时间段两两重叠：start < 对方 end && 对方 start < end 即冲突（边界相接不算）
     * 返回冲突时间段 "HH:MM-HH:MM"，无冲突返回 null
     */
    private function findConflict(array $timeSlots): ?string
    {
        $count = count($timeSlots);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $a = $timeSlots[$i];
                $b = $timeSlots[$j];
                if (strtotime((string)$a['start']) < strtotime((string)$b['end'])
                    && strtotime((string)$b['start']) < strtotime((string)$a['end'])
                ) {
                    return $a['start'] . '-' . $a['end'];
                }
            }
        }
        return null;
    }

    /**
     * 校验 weekdays 为 1-7（周一至周日）数组
     */
    private function validateWeekdays(array $weekdays): bool
    {
        foreach ($weekdays as $day) {
            if ((int)$day < 1 || (int)$day > 7) {
                return false;
            }
        }
        return true;
    }
}
