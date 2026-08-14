<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\TechnicianProfile;
use app\model\TechnicianSchedule;
use app\model\TechnicianService;
use app\model\TechnicianEarning;
use app\model\TechnicianTierConfig;
use app\model\User;
use app\model\SystemConfig;
use app\model\Service;
use app\model\ServiceCategory;
use support\Request;
use support\Response;
use Erikwang2013\PosterPhp\Poster;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TechnicianController extends BaseController
{
    /**
     * 技师列表
     * 搜索: uid / phone / name / region / reg_date
     */
    public function index(Request $request): Response
    {
        $page  = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 15);
        $uid   = $request->input('uid', '');
        $phone = $request->input('phone', '');
        $name  = $request->input('name', '');
        $region = $request->input('region', '');
        $regDateStart = $request->input('reg_date_start', '');
        $regDateEnd   = $request->input('reg_date_end', '');
        $status = $request->input('status');

        $query = TechnicianProfile::with('user')->withCount(['schedules', 'services']);

        if ($uid) {
            $query->where('user_id', $uid);
        }
        if ($name) {
            $query->where('real_name', 'like', "%{$name}%");
        }
        if ($status !== null && $status !== '') {
            $query->where('status', (int) $status);
        }
        if ($phone || $region || $regDateStart || $regDateEnd) {
            $query->whereHas('user', function ($q) use ($phone, $region, $regDateStart, $regDateEnd) {
                if ($phone) {
                    $q->where('phone', 'like', "%{$phone}%");
                }
                if ($region) {
                    $q->where('region', 'like', "%{$region}%");
                }
                if ($regDateStart) {
                    $q->whereDate('created_at', '>=', $regDateStart);
                }
                if ($regDateEnd) {
                    $q->whereDate('created_at', '<=', $regDateEnd);
                }
            });
        }

        $total = $query->count();
        $list  = $query->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->orderBy('id', 'desc')
                       ->get()
                       ->map(function ($t) {
                           $data = $t->toArray();
                           if (isset($data['real_name']) && !empty($data['real_name'])) {
                               $data['real_name'] = mb_substr($data['real_name'], 0, 1) . '**';
                           }
                           return $this->encodeIds($data);
                       });

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 技师详情: 基本资料 + 排班 + 服务 + 收益汇总
     */
    public function show(Request $request, string $hashid): Response
    {
        $id = $this->decodeId($hashid);
        $profile = TechnicianProfile::with(['user', 'schedules', 'services.service', 'earnings'])->find($id);
        if (!$profile) {
            return $this->fail('技师不存在', 404);
        }

        $data = $profile->toArray();
        // 收益汇总
        $earningsSummary = [
            'total_commission' => TechnicianEarning::where('technician_id', $id)
                ->where('type', 'commission')->sum('amount'),
            'total_bonus' => TechnicianEarning::where('technician_id', $id)
                ->where('type', 'bonus')->sum('amount'),
            'total_penalty' => TechnicianEarning::where('technician_id', $id)
                ->where('type', 'penalty')->sum('amount'),
            'pending_settlement' => TechnicianEarning::where('technician_id', $id)
                ->where('status', 'pending')->sum('amount'),
        ];
        $data['earnings_summary'] = $earningsSummary;

        if (isset($data['real_name']) && !empty($data['real_name'])) {
            $data['real_name'] = mb_substr($data['real_name'], 0, 1) . '**';
        }

        // 技师等级评估
        $tierData = $this->getTechnicianTier($profile);
        $data['current_tier'] = $tierData['current'];
        $data['next_tier'] = $tierData['next'];
        $data['tier_progress'] = $tierData['progress'];

        return $this->success($this->encodeIds($data));
    }

    /**
     * 编辑技师资料
     */
    public function update(Request $request, string $hashid): Response
    {
        $id = $this->decodeId($hashid);
        $profile = TechnicianProfile::find($id);
        if (!$profile) {
            return $this->fail('技师不存在', 404);
        }

        if ($request->has('intro')) {
            $profile->intro = $request->input('intro');
        }
        if ($request->has('avatar')) {
            $profile->avatar = $request->input('avatar');
        }
        $profile->save();

        $this->clearSvcCache();
        return $this->success($this->encodeIds($profile->toArray()), '更新成功');
    }

    /**
     * 审核操作: approve 或 reject
     */
    public function audit(Request $request, string $hashid): Response
    {
        // poster-php 验证
        Poster::verify($request);

        $id = $this->decodeId($hashid);
        $profile = TechnicianProfile::find($id);
        if (!$profile) {
            return $this->fail('技师不存在', 404);
        }

        $action = $request->input('action', '');
        $remark = $request->input('remark', '');

        if (!in_array($action, ['approve', 'reject'], true)) {
            return $this->fail('操作类型无效，支持 approve / reject', 422);
        }

        $profile->status = $action === 'approve' ? 1 : 2;
        $profile->audit_remark = $remark;
        $profile->audited_at = date('Y-m-d H:i:s');
        $profile->save();

        $this->clearSvcCache();
        return $this->success(
            $this->encodeIds($profile->toArray()),
            $action === 'approve' ? '审核通过' : '已驳回'
        );
    }

    /**
     * 删除技师
     */
    public function destroy(Request $request, string $hashid): Response
    {
        Poster::verify($request);

        $id = $this->decodeId($hashid);
        $profile = TechnicianProfile::find($id);
        if (!$profile) {
            return $this->fail('技师不存在', 404);
        }

        $profile->delete();
        $this->clearSvcCache();
        return $this->success([], '删除成功');
    }

    /**
     * CSV 导出技师列表
     */
    public function export(Request $request): Response
    {
        $query = TechnicianProfile::with('user');
        $list = $query->orderBy('id', 'desc')->limit(50000)->get();

        $filename = 'technicians_' . date('YmdHis') . '.csv';
        $tmpDir = runtime_path() . '/tmp/';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }
        $tmpFile = $tmpDir . $filename;

        $fp = fopen($tmpFile, 'w');
        // BOM for Excel UTF-8 compatibility
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, [
            'ID', '用户ID', '姓名', '性别', '简介', '评分',
            '接单数', '收藏数', '状态', '审核备注', '审核时间', '创建时间',
        ]);

        foreach ($list as $t) {
            fputcsv($fp, [
                $t->id,
                $t->user_id,
                $t->real_name,
                $t->gender,
                $t->intro,
                $t->rating,
                $t->order_count,
                $t->favorite_count,
                $t->status,
                $t->audit_remark,
                $t->audited_at,
                $t->created_at,
            ]);
        }
        fclose($fp);

        return response()->download($tmpFile, $filename);
    }

    /**
     * 获取/设置技师排班
     */
    public function schedules(Request $request, string $hashid): Response
    {
        $id = $this->decodeId($hashid);
        $profile = TechnicianProfile::find($id);
        if (!$profile) {
            return $this->fail('技师不存在', 404);
        }

        if ($request->method() === 'POST') {
            // 设置排班: { date, time_slots: [...] }
            $schedule = new TechnicianSchedule();
            $schedule->id = (string) $this->generateId();
            $schedule->technician_id = $id;
            $schedule->date = $request->input('date');
            $schedule->time_slots = $request->input('time_slots', []);
            $schedule->status = 1;
            $schedule->save();

            $this->clearSvcCache();
            return $this->success($this->encodeIds($schedule->toArray()), '排班创建成功');
        }

        // GET: 获取排班列表
        $date = $request->input('date', date('Y-m-d'));
        $list = TechnicianSchedule::where('technician_id', $id)
            ->where('date', '>=', $date)
            ->orderBy('date')
            ->limit(30)
            ->get()
            ->map(fn($s) => $this->encodeIds($s->toArray()));

        return $this->success(['list' => $list]);
    }

    /**
     * 获取/设置技师服务
     */
    public function services(Request $request, string $hashid): Response
    {
        $id = $this->decodeId($hashid);
        $profile = TechnicianProfile::find($id);
        if (!$profile) {
            return $this->fail('技师不存在', 404);
        }

        if ($request->method() === 'POST') {
            // 批量设置: { service_ids: [...] }
            $serviceIds = $request->input('service_ids', []);

            // 性别限制校验
            $restrictions = $this->getGenderRestrictions();
            $gender = (int) $profile->gender;
            $genderKey = match ($gender) { 1 => 'male', 2 => 'female', default => '' };
            $restrictedIds = $restrictions[$genderKey] ?? [];

            if (!empty($restrictedIds) && !empty($serviceIds)) {
                $conflictIds = array_intersect(
                    array_map('strval', $serviceIds),
                    array_map('strval', $restrictedIds)
                );
                if (!empty($conflictIds)) {
                    $conflictServices = Service::whereIn('id', $conflictIds)->pluck('name')->toArray();
                    return $this->fail(
                        '以下服务因性别限制无法分配给该技师: ' . implode('、', $conflictServices),
                        422
                    );
                }
            }

            // 清除旧的，写入新的
            TechnicianService::where('technician_id', $id)->delete();
            foreach ($serviceIds as $serviceId) {
                $ts = new TechnicianService();
                $ts->id = (string) $this->generateId();
                $ts->technician_id = $id;
                $ts->service_id = (string) $serviceId;
                $ts->save();
            }

            $this->clearSvcCache();
            return $this->success([], '服务分配更新成功');
        }

        // GET: 获取已分配服务
        $list = TechnicianService::where('technician_id', $id)
            ->with('service')
            ->get()
            ->map(fn($ts) => $this->encodeIds($ts->toArray()));

        return $this->success(['list' => $list]);
    }

    // ────────────────────────────────────────────────
    // 2. 技师性别限制 — 服务分配校验
    // ────────────────────────────────────────────────

    /**
     * 获取某技师的服务限制列表
     * 返回该技师因性别而受限的服务项目
     */
    public function serviceRestrictions(Request $request, string $hashid): Response
    {
        $id = $this->decodeId($hashid);
        $profile = TechnicianProfile::find($id);
        if (!$profile) {
            return $this->fail('技师不存在', 404);
        }

        // 获取全局性别限制配置
        $restrictions = $this->getGenderRestrictions();
        $gender = (int) $profile->gender;

        // 性别映射: 0=未知 1=男 2=女
        $genderKey = match ($gender) {
            1 => 'male',
            2 => 'female',
            default => 'unknown',
        };

        $restrictedServiceIds = $restrictions[$genderKey] ?? [];
        $restrictedServices = [];
        if (!empty($restrictedServiceIds)) {
            $restrictedServices = Service::whereIn('id', $restrictedServiceIds)
                ->with('category')
                ->get()
                ->map(fn($s) => $this->encodeIds($s->toArray()));
        }

        return $this->success([
            'technician_id'       => $hashid,
            'gender'              => $gender,
            'gender_label'        => match ($gender) { 1 => '男', 2 => '女', default => '未知' },
            'restricted_services' => $restrictedServices,
        ]);
    }

    /**
     * 管理员更新全局性别限制配置
     * 存储到 erik_system_config，key: gender_service_restrictions
     * 格式: {"male": ["svc_id1", "svc_id2"], "female": ["svc_id3"]}
     */
    public function updateRestrictions(Request $request): Response
    {
        $male   = $request->input('male', []);
        $female = $request->input('female', []);

        $value = json_encode([
            'male'   => $male,
            'female' => $female,
        ], JSON_UNESCAPED_UNICODE);

        $config = SystemConfig::where('group', 'technician')
            ->where('key', 'gender_service_restrictions')
            ->first();

        if ($config) {
            $config->value = $value;
            $config->save();
        } else {
            $config = new SystemConfig();
            $config->id = $this->generateId();
            $config->group = 'technician';
            $config->key = 'gender_service_restrictions';
            $config->value = $value;
            $config->type = 'json';
            $config->description = '技师性别服务限制配置';
            $config->save();
        }

        $this->clearSvcCache();
        return $this->success($request->all(), '性别限制更新成功');
    }

    /**
     * 重写 services() 的 POST 逻辑, 添加性别限制校验
     * 用新方法覆盖原有 POST 块 — 保持 GET 不变
     */

    // ────────────────────────────────────────────────
    // 3. 排班导出
    // ────────────────────────────────────────────────

    /**
     * 导出技师排班为 Excel
     * 参数: date_start, date_end, technician_ids(可选)
     * 列: 技师姓名, 日期, 时间段, 状态
     */
    public function exportSchedules(Request $request): Response
    {
        $dateStart = $request->input('date_start', date('Y-m-01'));
        $dateEnd   = $request->input('date_end', date('Y-m-t'));
        $techIds   = $request->input('technician_ids', []);

        $query = TechnicianSchedule::with('technician')
            ->where('date', '>=', $dateStart)
            ->where('date', '<=', $dateEnd);

        if (!empty($techIds)) {
            $query->whereIn('technician_id', (array) $techIds);
        }

        $schedules = $query->orderBy('date')->orderBy('technician_id')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('技师排班');

        // 表头
        $headers = ['技师姓名', '日期', '时间段', '状态'];
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1677FF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $dataStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        $colIndex = 'A';
        foreach ($headers as $header) {
            $cell = $sheet->getCell($colIndex . '1');
            $cell->setValue($header);
            $sheet->getStyle($colIndex . '1')->applyFromArray($headerStyle);
            $sheet->getColumnDimension($colIndex)->setAutoSize(true);
            $colIndex++;
        }

        $row = 2;
        foreach ($schedules as $s) {
            $techName = $s->technician->real_name ?? ('技师#' . $s->technician_id);
            $timeSlots = is_array($s->time_slots) ? implode(', ', $s->time_slots) : $s->time_slots;
            $statusLabel = match ((int) $s->status) {
                1 => '正常',
                2 => '休息',
                3 => '请假',
                default => '未知',
            };

            $sheet->getCell('A' . $row)->setValue($techName);
            $sheet->getCell('B' . $row)->setValue($s->date);
            $sheet->getCell('C' . $row)->setValue($timeSlots);
            $sheet->getCell('D' . $row)->setValue($statusLabel);

            $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray($dataStyle);
            $row++;
        }

        $sheet->freezePane('A2');

        $filename = 'schedules_' . date('YmdHis') . '.xlsx';
        $tmpFile = runtime_path() . '/tmp/' . $filename;

        $dir = dirname($tmpFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpFile);

        return response()->download($tmpFile, $filename);
    }

    /**
     * 导出考勤记录
     * 列: 技师姓名, 日期, 签到时间, 签退时间, 考勤状态, 卫生照片, 备注
     * 考勤数据从 erik_technician_schedule 扩展字段中提取
     */
    public function exportAttendance(Request $request): Response
    {
        $dateStart = $request->input('date_start', date('Y-m-01'));
        $dateEnd   = $request->input('date_end', date('Y-m-t'));
        $techIds   = $request->input('technician_ids', []);

        $query = TechnicianSchedule::with('technician')
            ->where('date', '>=', $dateStart)
            ->where('date', '<=', $dateEnd);

        if (!empty($techIds)) {
            $query->whereIn('technician_id', (array) $techIds);
        }

        $schedules = $query->orderBy('date')->orderBy('technician_id')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('技师考勤');

        $headers = ['技师姓名', '日期', '签到时间', '签退时间', '考勤状态', '卫生照片', '备注'];
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1677FF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $dataStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        $colIndex = 'A';
        foreach ($headers as $header) {
            $cell = $sheet->getCell($colIndex . '1');
            $cell->setValue($header);
            $sheet->getStyle($colIndex . '1')->applyFromArray($headerStyle);
            $sheet->getColumnDimension($colIndex)->setAutoSize(true);
            $colIndex++;
        }

        $row = 2;
        foreach ($schedules as $s) {
            $techName = $s->technician->real_name ?? ('技师#' . $s->technician_id);
            $checkIn  = $s->check_in_at ?? '';
            $checkOut = $s->check_out_at ?? '';
            $statusLabel = match ((int) $s->status) {
                1 => '出勤',
                2 => '休息',
                3 => '请假',
                4 => '迟到',
                5 => '早退',
                default => '未知',
            };
            $hygienePhoto = $s->hygiene_photo ?? '';
            $remark = $s->remark ?? '';

            $sheet->getCell('A' . $row)->setValue($techName);
            $sheet->getCell('B' . $row)->setValue($s->date);
            $sheet->getCell('C' . $row)->setValue($checkIn);
            $sheet->getCell('D' . $row)->setValue($checkOut);
            $sheet->getCell('E' . $row)->setValue($statusLabel);
            $sheet->getCell('F' . $row)->setValue($hygienePhoto);
            $sheet->getCell('G' . $row)->setValue($remark);

            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($dataStyle);
            $row++;
        }

        $sheet->freezePane('A2');

        $filename = 'attendance_' . date('YmdHis') . '.xlsx';
        $tmpFile = runtime_path() . '/tmp/' . $filename;

        $dir = dirname($tmpFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpFile);

        return response()->download($tmpFile, $filename);
    }

    // ────────────────────────────────────────────────
    // 性别限制辅助方法
    // ────────────────────────────────────────────────

    /**
     * 从 erik_system_config 获取性别服务限制
     */
    private function getGenderRestrictions(): array
    {
        $config = SystemConfig::where('group', 'technician')
            ->where('key', 'gender_service_restrictions')
            ->first();

        if ($config && !empty($config->value)) {
            $data = json_decode($config->value, true);
            if (is_array($data)) {
                return $data;
            }
        }

        return ['male' => [], 'female' => []];
    }

    /**
     * 计算技师当前等级及升级进度
     */
    private function getTechnicianTier(TechnicianProfile $profile): array
    {
        $tiers = TechnicianTierConfig::orderBy('sort', 'desc')->get();

        $currentTier = null;
        $nextTier = null;

        // 从高到低匹配当前等级
        foreach ($tiers as $tier) {
            if ($profile->order_count >= $tier->min_orders
                && (float) $profile->rating >= (float) $tier->min_rating
            ) {
                $currentTier = $tier;
                break;
            }
        }

        // 如果没有匹配到，设为最低等级
        if (!$currentTier && $tiers->isNotEmpty()) {
            $currentTier = $tiers->last();
        }

        // 查找下一等级
        $nextTierSlugs = ['junior' => 'senior', 'senior' => 'expert'];
        if ($currentTier && isset($nextTierSlugs[$currentTier->slug])) {
            $nextSlug = $nextTierSlugs[$currentTier->slug];
            $nextTier = $tiers->firstWhere('slug', $nextSlug);
        }

        // 计算升级进度
        $progress = null;
        if ($currentTier && $nextTier) {
            $orderProgress = $nextTier->min_orders > 0
                ? min(100, round(($profile->order_count / $nextTier->min_orders) * 100, 1))
                : 100;
            $ratingProgress = $nextTier->min_rating > 0
                ? min(100, round(((float) $profile->rating / $nextTier->min_rating) * 100, 1))
                : 100;
            $progress = [
                'orders_needed'    => max(0, $nextTier->min_orders - $profile->order_count),
                'rating_needed'    => max(0, round((float) $nextTier->min_rating - (float) $profile->rating, 1)),
                'order_progress'   => $orderProgress,
                'rating_progress'  => $ratingProgress,
                'overall_progress' => round(($orderProgress + $ratingProgress) / 2, 1),
            ];
        }

        return [
            'current'  => $currentTier ? [
                'name'              => $currentTier->name,
                'slug'              => $currentTier->slug,
                'commission_rate'   => (float) $currentTier->commission_rate,
                'price_multiplier'  => (float) $currentTier->price_multiplier,
            ] : null,
            'next'     => $nextTier ? [
                'name'              => $nextTier->name,
                'slug'              => $nextTier->slug,
                'commission_rate'   => (float) $nextTier->commission_rate,
                'price_multiplier'  => (float) $nextTier->price_multiplier,
            ] : null,
            'progress' => $progress,
        ];
    }
}
