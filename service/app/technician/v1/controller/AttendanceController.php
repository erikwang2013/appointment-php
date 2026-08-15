<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\technician\v1\controller;

use app\common\BaseController;
use app\model\TechnicianAttendance;
use support\Db;
use support\Log;
use Webman\Http\Request;

/**
 * 技师考勤打卡控制器
 *
 * 上下班打卡闭环：check-in 上班（当日一条记录）→ check-out 下班（补签退时间），
 * 以及我的考勤月列表 + 出勤天数/总工时汇总。
 * 身份来源：TechnicianAuth 中间件已解析 $request->technician_id（技师档案ID）。
 */
class AttendanceController extends BaseController
{
    /** 超过该时间上班打卡标记迟到 */
    private const LATE_AFTER = '10:00:00';

    /**
     * 上班打卡
     * POST /api/technician/attendance/check-in
     * 当日已打卡 422；唯一索引 uk_technician_date 兜底并发重复。
     */
    public function checkIn(Request $request)
    {
        $technicianId = $request->technician_id;
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');

        if (TechnicianAttendance::where('technician_id', $technicianId)->where('date', $today)->exists()) {
            return $this->error('今日已打卡，请勿重复操作', 422);
        }

        try {
            $attendance = TechnicianAttendance::create([
                'id'             => TechnicianAttendance::generateId(),
                'technician_id'  => $technicianId,
                'date'           => $today,
                'check_in_at'    => $now,
                'status'         => 'normal',
            ]);
        } catch (\Throwable $e) {
            // 并发重复打卡触发唯一索引冲突
            Log::warning('[AttendanceController] check-in duplicate: ' . $e->getMessage());
            return $this->error('今日已打卡，请勿重复操作', 422);
        }

        return $this->success($attendance, '上班打卡成功');
    }

    /**
     * 下班打卡
     * POST /api/technician/attendance/check-out
     * 守卫：当日未上班 422「请先上班打卡」；已下班 422。
     * 并发：行锁 + 锁内状态复查；check_in_at 晚于 10:00 标记迟到。
     */
    public function checkOut(Request $request)
    {
        $technicianId = $request->technician_id;
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');

        $attendance = TechnicianAttendance::where('technician_id', $technicianId)->where('date', $today)->first();
        if (!$attendance || !$attendance->check_in_at) {
            return $this->error('请先上班打卡', 422);
        }
        if ($attendance->check_out_at) {
            return $this->error('今日已下班打卡，请勿重复操作', 422);
        }

        try {
            Db::beginTransaction();
            $locked = TechnicianAttendance::where('id', $attendance->id)->lockForUpdate()->first();
            if (!$locked || !$locked->check_in_at || $locked->check_out_at) {
                Db::rollBack();
                return $this->error('请先上班打卡', 422);
            }
            $locked->check_out_at = $now;
            if (substr((string)$locked->check_in_at, 11) > self::LATE_AFTER) {
                $locked->status = 'late';
            }
            $locked->save();
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollBack();
            Log::error('[AttendanceController] check-out failed: ' . $e->getMessage());
            return $this->error('操作失败，请稍后重试');
        }

        return $this->success($locked, '下班打卡成功');
    }

    /**
     * 我的考勤
     * GET /api/technician/attendance?month=YYYY-MM（缺省当月）
     * 返回当月记录列表 + 出勤天数/总工时/平均工时汇总。
     */
    public function index(Request $request)
    {
        $technicianId = $request->technician_id;
        $month = (string)$request->input('month', date('Y-m'));
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            return $this->error('月份格式错误，应为 YYYY-MM', 422);
        }

        $list = TechnicianAttendance::where('technician_id', $technicianId)
            ->where('date', 'like', $month . '%')
            ->orderBy('date', 'desc')
            ->get();

        $workDays = 0;
        $totalMinutes = 0;
        foreach ($list as $item) {
            if ($item->check_in_at) {
                $workDays++;
            }
            if ($item->check_in_at && $item->check_out_at) {
                $totalMinutes += max(0, (strtotime((string)$item->check_out_at) - strtotime((string)$item->check_in_at)) / 60);
            }
        }
        $totalHours = round($totalMinutes / 60, 1);

        return $this->success([
            'month'   => $month,
            'list'    => $list,
            'summary' => [
                'work_days'   => $workDays,
                'total_hours' => $totalHours,
                'avg_hours'   => $workDays > 0 ? round($totalHours / $workDays, 1) : 0.0,
            ],
        ]);
    }
}
