<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\Order;
use app\model\TechnicianProfile;
use app\model\TechnicianSchedule;
use support\Request;
use support\Response;

/**
 * 技师排班管理控制器（管理端）
 *
 * 提供排班维护（增删改查 + 设为休息）与「预约占位闭环」：
 * 列表接口联查 erik_order 当日已预约时段（占位），管理员排班时可见该技师
 * 已被占用的订单，避免与既有预约冲突。
 *
 * 契约（与技师端 ScheduleController 保持一致）：
 *   - time_slots 格式: [{"start":"09:00","end":"12:00"}, ...]
 *   - status: 0=休息 1=可预约
 *   - UNIQUE(technician_id, date) 单日单条，upsert 幂等
 */
class TechnicianScheduleController extends BaseController
{
    /**
     * 排班列表（联查当日预约占用）
     * GET /admin/schedules?date_start=&date_end=&technician_id=&page=&limit=
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $page          = max(1, (int) $request->input('page', 1));
        $limit         = min(100, max(1, (int) $request->input('limit', 15)));
        $dateStart     = $request->input('date_start', '');
        $dateEnd       = $request->input('date_end', '');
        $technicianId  = $request->input('technician_id', '');

        $query = TechnicianSchedule::with('technician');

        if ($dateStart) {
            $query->where('date', '>=', $dateStart);
        }
        if ($dateEnd) {
            $query->where('date', '<=', $dateEnd);
        }
        if ($technicianId !== '') {
            try {
                $query->where('technician_id', $this->decodeId($technicianId));
            } catch (\InvalidArgumentException) {
                return $this->fail('无效的技师ID', 422);
            }
        }

        $total = (clone $query)->count();
        $list  = $query->orderBy('date', 'desc')
                       ->orderBy('technician_id')
                       ->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->get();

        // ── 预约占位闭环：批量联查当日有效订单（避免 N+1）──
        // 有效状态排除已取消/已退款（退款中仍占位，防止档期重复售卖）
        $bookingsByKey = $this->loadBookings($list);

        $rows = $list->map(function (TechnicianSchedule $schedule) use ($bookingsByKey) {
            $data = $schedule->toArray();
            $key  = (string) $schedule->technician_id . '|' . $schedule->date;
            $data['technician_name'] = $schedule->technician->real_name ?? ('技师#' . $schedule->technician_id);
            $data['bookings']        = $bookingsByKey[$key] ?? [];
            return $data;
        });

        return $this->success([
            'list'  => $rows,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 导出排班为 CSV（UTF-8 BOM，Excel 直接打开）
     * GET /admin/technician-schedule/export?start_date=&end_date=&technician_id=
     * 列: 技师ID/技师姓名/日期/时间段明细（time_slots JSON 解析为 "09:00-12:00, 14:00-18:00"）
     * start_date/end_date 必填且跨度 ≤31 天；technician_id 可选（hashid）
     *
     * @param Request $request
     * @return Response
     */
    public function export(Request $request): Response
    {
        $startDate   = (string) $request->input('start_date', '');
        $endDate     = (string) $request->input('end_date', '');
        $techHashid  = (string) $request->input('technician_id', '');

        if (!$this->isValidDate($startDate) || !$this->isValidDate($endDate)) {
            return $this->fail('日期格式不正确，需要 YYYY-MM-DD', 422);
        }
        if (strtotime($endDate) < strtotime($startDate)) {
            return $this->fail('结束日期不能早于开始日期', 422);
        }
        if ((strtotime($endDate) - strtotime($startDate)) / 86400 > 31) {
            return $this->fail('日期跨度不能超过31天', 422);
        }

        $query = TechnicianSchedule::with('technician')
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate);
        if ($techHashid !== '') {
            try {
                $query->where('technician_id', $this->decodeId($techHashid));
            } catch (\InvalidArgumentException) {
                return $this->fail('无效的技师ID', 422);
            }
        }
        $list = $query->orderBy('date')->orderBy('technician_id')->get();

        $filename = 'schedules_' . date('YmdHis') . '.csv';
        $tmpDir   = runtime_path() . '/tmp/';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }
        $tmpFile = $tmpDir . $filename;

        $fp = fopen($tmpFile, 'w');
        // BOM for Excel UTF-8 compatibility
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, ['技师ID', '技师姓名', '日期', '时间段明细']);

        foreach ($list as $schedule) {
            $slots = [];
            foreach ((array) $schedule->time_slots as $slot) {
                if (is_array($slot) && isset($slot['start'], $slot['end'])) {
                    $slots[] = $slot['start'] . '-' . $slot['end'];
                }
            }
            fputcsv($fp, [
                $schedule->technician_id,
                $schedule->technician->real_name ?? ('技师#' . $schedule->technician_id),
                $schedule->date,
                implode(', ', $slots),
            ]);
        }
        fclose($fp);

        return response()->download($tmpFile, $filename);
    }

    /**
     * 创建/更新排班（单日单条 upsert，UNIQUE(technician_id,date) 1062 冲突幂等）
     * POST /admin/schedules
     * body: { technician_id, date, time_slots: [{start,end}], status?: 0|1 }
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request): Response
    {
        $technicianHashid = $request->input('technician_id', '');
        $date             = $request->input('date', '');
        $timeSlots        = $request->input('time_slots', []);
        $status           = (int) $request->input('status', 1);

        if ($technicianHashid === '') {
            return $this->fail('请选择技师', 422);
        }
        try {
            $technicianId = $this->decodeId($technicianHashid);
        } catch (\InvalidArgumentException) {
            return $this->fail('无效的技师ID', 422);
        }
        if (!TechnicianProfile::find($technicianId)) {
            return $this->fail('技师不存在', 404);
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date)) {
            return $this->fail('日期格式不正确', 422);
        }
        if (!is_array($timeSlots) || !$this->validateTimeSlots($timeSlots)) {
            return $this->fail('时间段格式不正确，需要 [{start,end}, ...]', 422);
        }
        $conflict = $this->findOverlap($timeSlots);
        if ($conflict !== null) {
            return $this->fail('与已有排班时间冲突：' . $conflict, 422);
        }
        if (!in_array($status, [0, 1], true)) {
            return $this->fail('状态无效，支持 0=休息 / 1=可预约', 422);
        }

        // 先查后插（常规路径）；并发下唯一键冲突由下方 1062 兜底幂等
        $schedule = TechnicianSchedule::where('technician_id', $technicianId)
            ->where('date', $date)
            ->first();

        if (!$schedule) {
            try {
                $schedule = new TechnicianSchedule();
                $schedule->id            = TechnicianSchedule::generateId();
                $schedule->technician_id = $technicianId;
                $schedule->date          = $date;
                $schedule->time_slots    = $timeSlots;
                $schedule->status        = $status;
                $schedule->save();
            } catch (\Throwable $e) {
                // 1062 唯一键冲突（uk_tech_date）→ 幂等：读取并发写入的行后走更新路径
                if (!$this->isDuplicateEntry($e)) {
                    throw $e;
                }
                $schedule = TechnicianSchedule::where('technician_id', $technicianId)
                    ->where('date', $date)
                    ->first();
                if (!$schedule) {
                    return $this->fail('排班保存失败', 500);
                }
            }
        }

        // 已存在行（含 1062 兜底读到的并发行）→ 更新时段与状态；新建行跳过
        if (!$schedule->wasRecentlyCreated) {
            $schedule->time_slots = $timeSlots;
            $schedule->status     = $status;
            $schedule->save();
        }

        $this->clearSvcCache();
        return $this->success($schedule->toArray(), '排班保存成功');
    }

    /**
     * 删除排班（仅删除排班行，不影响订单）
     * DELETE /admin/schedules/{id}
     *
     * @param Request $request
     * @param string $hashid
     * @return Response
     */
    public function destroy(Request $request, string $hashid): Response
    {
        try {
            $id = $this->decodeId($hashid);
        } catch (\InvalidArgumentException) {
            return $this->fail('无效的排班ID', 422);
        }
        $schedule = TechnicianSchedule::find($id);
        if (!$schedule) {
            return $this->fail('排班不存在', 404);
        }

        $schedule->delete();
        $this->clearSvcCache();
        return $this->success([], '排班删除成功');
    }

    /**
     * 设为休息（status=0，保留排班行与时间段）
     * PUT /admin/schedules/{id}/rest
     *
     * @param Request $request
     * @param string $hashid
     * @return Response
     */
    public function setRest(Request $request, string $hashid): Response
    {
        try {
            $id = $this->decodeId($hashid);
        } catch (\InvalidArgumentException) {
            return $this->fail('无效的排班ID', 422);
        }
        $schedule = TechnicianSchedule::find($id);
        if (!$schedule) {
            return $this->fail('排班不存在', 404);
        }

        $schedule->status = 0;
        $schedule->save();

        $this->clearSvcCache();
        return $this->success($schedule->toArray(), '已设为休息');
    }

    // ────────────────────────────────────────────────
    // 私有辅助
    // ────────────────────────────────────────────────

    /**
     * 校验 YYYY-MM-DD 格式且为真实存在的日期
     */
    private function isValidDate(string $date): bool
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m)) {
            return false;
        }
        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }

    /**
     * 校验 time_slots 格式: [{start, end}, ...]
     */
    private function validateTimeSlots(array $timeSlots): bool
    {
        foreach ($timeSlots as $slot) {
            if (!is_array($slot)
                || empty($slot['start'])
                || empty($slot['end'])
                || !preg_match('/^\d{1,2}:\d{2}$/', (string) $slot['start'])
                || !preg_match('/^\d{1,2}:\d{2}$/', (string) $slot['end'])
            ) {
                return false;
            }
            if (strtotime((string) $slot['end']) <= strtotime((string) $slot['start'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * 查找时间段两两重叠：start < 对方 end && 对方 start < end 即冲突（边界相接不算）
     * 返回冲突时间段 "HH:MM-HH:MM"，无冲突返回 null
     */
    private function findOverlap(array $timeSlots): ?string
    {
        $count = count($timeSlots);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $a = $timeSlots[$i];
                $b = $timeSlots[$j];
                if (strtotime((string) $a['start']) < strtotime((string) $b['end'])
                    && strtotime((string) $b['start']) < strtotime((string) $a['end'])
                ) {
                    return $a['start'] . '-' . $a['end'];
                }
            }
        }
        return null;
    }

    /**
     * 判断异常是否为 MySQL 唯一键冲突 (1062)
     */
    private function isDuplicateEntry(\Throwable $e): bool
    {
        $sqlState = method_exists($e, 'getSQLState') ? $e->getSQLState() : '';
        $driverCode = method_exists($e, 'getDriverCode') ? $e->getDriverCode() : 0;
        return $sqlState === '23000' || (int) $driverCode === 1062 || str_contains($e->getMessage(), '1062');
    }

    /**
     * 批量加载 (technician_id, date) 当日有效预约占用
     * 返回 key 为 "technician_id|date" 的 bookings 映射
     *
     * @param iterable $schedules 当前页排班
     * @return array<string, array>
     */
    private function loadBookings(iterable $schedules): array
    {
        $pairs = [];
        foreach ($schedules as $schedule) {
            $key = (string) $schedule->technician_id . '|' . $schedule->date;
            $pairs[$key] = [(int) $schedule->technician_id, $schedule->date];
        }
        if (empty($pairs)) {
            return [];
        }

        $technicianIds = array_unique(array_column($pairs, 0));
        $dates         = array_unique(array_column($pairs, 1));
        $orders = Order::with('user')
            ->where('order_type', Order::ORDER_TYPE_APPOINTMENT)
            ->whereIn('technician_id', $technicianIds)
            ->whereNotIn('status', [
                Order::STATUS_CANCELLED,
                Order::STATUS_REFUNDED,
            ])
            ->whereBetween('service_time', [
                min($dates) . ' 00:00:00',
                max($dates) . ' 23:59:59',
            ])
            ->orderBy('service_time')
            ->get();

        $bookings = [];
        foreach ($orders as $order) {
            $key = (string) $order->technician_id . '|' . $order->service_time->format('Y-m-d');
            $user = $order->user;
            $bookings[$key][] = [
                'order_id'     => (int) $order->id,
                'order_no'     => $order->order_no,
                'user_name'    => $user->real_name ?? $user->nickname ?? ('用户#' . $order->user_id),
                'service_time' => $order->service_time->format('Y-m-d H:i'),
                'status'       => $order->status,
            ];
        }
        return $bookings;
    }
}
