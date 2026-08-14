<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\model\Order;
use app\model\TechnicianProfile;
use app\model\TechnicianSchedule;
use Webman\Http\Request;

/**
 * 预约月历控制器（用户端公开接口，无需认证）
 * 按技师+月份返回每日可约时段，已约订单时段自动排除
 */
class CalendarController extends BaseController
{
    /**
     * 技师当月月历
     * GET /api/calendar/technician/{id}?month=2026-08
     */
    public function month(string $id, Request $request)
    {
        $technicianId = $this->decodeId($id);
        if ($technicianId === null || !$this->technicianExists($technicianId)) {
            return $this->error('技师不存在', 404);
        }

        $month = $request->input('month', '');
        [$year, $monthNum] = $this->validateMonth($month);
        if ($year === null) {
            return $this->error('月份格式不正确', 422);
        }

        $start = sprintf('%04d-%02d-01', $year, $monthNum);
        $daysInMonth = (int) date('t', strtotime($start));

        $schedules = TechnicianSchedule::where('technician_id', $technicianId)
            ->where('status', 1)
            ->whereBetween('date', [$start, date('Y-m-d', strtotime($start . ' +' . ($daysInMonth - 1) . ' days'))])
            ->get()
            ->keyBy('date');

        $bookedByDate = $this->bookedByDate($technicianId, $start, $daysInMonth);

        $days = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $monthNum, $d);
            $slots = $this->availableSlots($schedules->get($dateStr), $bookedByDate[$dateStr] ?? []);
            $days[] = [
                'date' => $dateStr,
                'available' => count($slots) > 0,
                'slots' => $slots,
                'booked' => count($bookedByDate[$dateStr] ?? []),
            ];
        }

        return $this->success($days);
    }

    /**
     * 技师单日可约时段明细
     * GET /api/calendar/technician/{id}/day?date=2026-08-01
     */
    public function day(string $id, Request $request)
    {
        $technicianId = $this->decodeId($id);
        if ($technicianId === null || !$this->technicianExists($technicianId)) {
            return $this->error('技师不存在', 404);
        }

        $date = $request->input('date', '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !checkdate((int)substr($date, 5, 2), (int)substr($date, 8, 2), (int)substr($date, 0, 4))) {
            return $this->error('日期格式不正确', 422);
        }

        $schedule = TechnicianSchedule::where('technician_id', $technicianId)
            ->where('date', $date)
            ->where('status', 1)
            ->first();

        $bookedTimes = $this->bookedTimes($technicianId, $date, $date);
        $slots = $this->availableSlots($schedule, $bookedTimes);

        return $this->success([
            'date' => $date,
            'available' => count($slots) > 0,
            'slots' => $slots,
            'booked' => count($bookedTimes),
        ]);
    }

    /** 校验 Y-m 月份格式，返回 [year, month] 或 [null, null] */
    private function validateMonth(string $month): array
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            return [null, null];
        }
        [$year, $monthNum] = array_map('intval', explode('-', $month));
        if ($year < 2000 || $year > 2100 || $monthNum < 1 || $monthNum > 12) {
            return [null, null];
        }
        return [$year, $monthNum];
    }

    private function technicianExists(int $technicianId): bool
    {
        return TechnicianProfile::where('id', $technicianId)
            ->where('status', 'approved')
            ->exists();
    }

    /** 查询某技师一段时间内的有效预约，按日期分组为 bookedTimes 结构 */
    private function bookedByDate(int $technicianId, string $start, int $daysInMonth): array
    {
        $end = date('Y-m-d', strtotime($start . ' +' . ($daysInMonth - 1) . ' days'));
        $orders = $this->bookedOrders($technicianId, $start, $end);

        $result = [];
        foreach ($orders as $order) {
            $dateStr = $order->service_time->format('Y-m-d');
            $result[$dateStr][] = $order->service_time->format('H:i:s');
        }
        return $result;
    }

    private function bookedTimes(int $technicianId, string $start, string $end): array
    {
        return $this->bookedOrders($technicianId, $start, $end)
            ->map(fn($order) => $order->service_time->format('H:i:s'))
            ->all();
    }

    private function bookedOrders(int $technicianId, string $start, string $end)
    {
        return Order::where('technician_id', $technicianId)
            ->where('order_type', Order::ORDER_TYPE_APPOINTMENT)
            ->whereIn('status', [Order::STATUS_PAID, Order::STATUS_CONFIRMED, Order::STATUS_SERVING])
            ->whereBetween('service_time', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->get();
    }

    /** 排班时间段展开为可约槽位，剔除已被预约占用的槽位 */
    private function availableSlots(?TechnicianSchedule $schedule, array $bookedTimes): array
    {
        if (!$schedule || empty($schedule->time_slots)) {
            return [];
        }

        $slots = [];
        foreach ($schedule->time_slots as $range) {
            if (empty($range['start']) || empty($range['end'])) {
                continue;
            }
            $start = strtotime($range['start']);
            $end = strtotime($range['end']);
            if ($start === false || $end === false || $end <= $start) {
                continue;
            }
            for ($t = $start; $t + 3600 <= $end; $t += 3600) {
                $slot = date('H:i', $t);
                if ($this->isBooked($slot, $bookedTimes)) {
                    continue;
                }
                $slots[] = $slot;
            }
        }

        return array_values(array_unique($slots));
    }

    /** 槽位是否被已约订单占用（预约时间落在槽位 [start, start+1h) 内） */
    private function isBooked(string $slot, array $bookedTimes): bool
    {
        if (empty($bookedTimes)) {
            return false;
        }
        $slotStart = strtotime($slot);
        $slotEnd = $slotStart + 3600;
        foreach ($bookedTimes as $time) {
            $ts = strtotime($time);
            if ($ts !== false && $ts >= $slotStart && $ts < $slotEnd) {
                return true;
            }
        }
        return false;
    }
}
