<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\technician\v1\controller;

use app\common\BaseController;
use app\model\TechnicianAttendance;
use Webman\Http\Request;

/**
 * 技师考勤控制器
 * 签到、签退、清洁照片上传
 */
class AttendanceController extends BaseController
{
    /**
     * 签到
     * POST /api/technician/attendance/check-in
     */
    public function checkIn(Request $request)
    {
        $technicianId = $request->technician_id;
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');

        $attendance = TechnicianAttendance::where('technician_id', $technicianId)
            ->where('date', $today)
            ->first();

        if ($attendance) {
            // 已存在记录，更新签到时间
            if ($attendance->check_in_at) {
                return $this->error('今日已签到，请勿重复操作');
            }
            $attendance->check_in_at = $now;
            $attendance->save();
        } else {
            $attendance = TechnicianAttendance::create([
                'id' => TechnicianAttendance::generateId(),
                'technician_id' => $technicianId,
                'date' => $today,
                'check_in_at' => $now,
                'status' => 'normal',
            ]);
        }

        return $this->success($attendance, '签到成功');
    }

    /**
     * 签退
     * POST /api/technician/attendance/check-out
     */
    public function checkOut(Request $request)
    {
        $technicianId = $request->technician_id;
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');

        $attendance = TechnicianAttendance::where('technician_id', $technicianId)
            ->where('date', $today)
            ->first();

        if (!$attendance) {
            return $this->error('今日尚未签到，请先签到');
        }

        if ($attendance->check_out_at) {
            return $this->error('今日已签退，请勿重复操作');
        }

        $attendance->check_out_at = $now;
        $attendance->save();

        return $this->success($attendance, '签退成功');
    }

    /**
     * 上传清洁照片
     * POST /api/technician/attendance/upload-clean
     */
    public function uploadClean(Request $request)
    {
        $technicianId = $request->technician_id;
        $today = date('Y-m-d');
        $photoUrl = trim($request->input('photo_url', ''));

        if (empty($photoUrl)) {
            return $this->error('请提供清洁照片地址');
        }

        $attendance = TechnicianAttendance::where('technician_id', $technicianId)
            ->where('date', $today)
            ->first();

        if (!$attendance) {
            // 创建新的考勤记录并保存清洁照片
            $attendance = TechnicianAttendance::create([
                'id' => TechnicianAttendance::generateId(),
                'technician_id' => $technicianId,
                'date' => $today,
                'clean_photo' => $photoUrl,
                'status' => 'normal',
            ]);
        } else {
            $attendance->clean_photo = $photoUrl;
            $attendance->save();
        }

        return $this->success($attendance, '清洁照片上传成功');
    }
}
