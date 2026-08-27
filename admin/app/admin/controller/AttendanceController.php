<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\TechnicianAttendance;
use support\Db;
use support\Request;
use support\Response;

/**
 * 技师考勤管理控制器
 *
 * 考勤列表（按月筛选 + 技师姓名搜索 + 分页，join 技师姓名，ID hashid 编码）
 * 与考勤统计（按技师分组：当月打卡天数 / 总工时 / 平均工时）。
 */
class AttendanceController extends BaseController
{
    /**
     * 考勤列表
     * GET /admin/attendance?date=YYYY-MM&name=&page=&limit=
     */
    public function index(Request $request): Response
    {
        $date = (string)$request->input('date', date('Y-m'));
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $date)) {
            return $this->fail('月份格式错误，应为 YYYY-MM', 422);
        }
        $name = trim((string)$request->input('name', ''));
        $page = max(1, (int)$request->input('page', 1));
        $limit = min(100, max(1, (int)$request->input('limit', 15)));

        $query = TechnicianAttendance::query()
            ->leftJoin('appointment_technician_profile as tp', 'appointment_technician_attendance.technician_id', '=', 'tp.id')
            ->select([
                'appointment_technician_attendance.id',
                'appointment_technician_attendance.technician_id',
                'appointment_technician_attendance.date',
                'appointment_technician_attendance.check_in_at',
                'appointment_technician_attendance.check_out_at',
                'appointment_technician_attendance.status',
                'appointment_technician_attendance.remark',
                'tp.real_name',
            ])
            ->where('appointment_technician_attendance.date', 'like', $date . '%');
        if ($name !== '') {
            $query->where('tp.real_name', 'like', "%{$name}%");
        }

        $total = $query->count();
        $list = $query->orderBy('appointment_technician_attendance.date', 'desc')
            ->orderBy('appointment_technician_attendance.id', 'desc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->map(fn($row) => $row->toArray());

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 考勤统计
     * GET /admin/attendance/stats?date=YYYY-MM
     * 按技师分组：当月打卡天数、总工时、平均工时（小时，1 位小数）。
     */
    public function stats(Request $request): Response
    {
        $date = (string)$request->input('date', date('Y-m'));
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $date)) {
            return $this->fail('月份格式错误，应为 YYYY-MM', 422);
        }

        $rows = Db::table('appointment_technician_attendance as a')
            ->leftJoin('appointment_technician_profile as tp', 'a.technician_id', '=', 'tp.id')
            ->select('a.technician_id', 'tp.real_name')
            ->selectRaw('COUNT(*) AS work_days')
            ->selectRaw('SUM(TIMESTAMPDIFF(MINUTE, a.check_in_at, a.check_out_at)) AS total_minutes')
            ->where('a.date', 'like', $date . '%')
            ->groupBy('a.technician_id', 'tp.real_name')
            ->orderByDesc('work_days')
            ->get();

        $list = $rows->map(function ($row) {
            $minutes = (int)$row->total_minutes;
            $workDays = (int)$row->work_days;
            return [
                'technician_id' => $row->technician_id,
                'real_name'     => $row->real_name,
                'work_days'     => $workDays,
                'total_hours'   => round($minutes / 60, 1),
                'avg_hours'     => $workDays > 0 ? round($minutes / 60 / $workDays, 1) : 0.0,
            ];
        });

        return $this->success(['date' => $date, 'list' => $list]);
    }
}
