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
use app\model\User;
use support\Request;
use support\Response;
use Erikwang2013\PosterPhp\Poster;

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
            // 清除旧的，写入新的
            TechnicianService::where('technician_id', $id)->delete();
            foreach ($serviceIds as $serviceId) {
                $ts = new TechnicianService();
                $ts->id = (string) $this->generateId();
                $ts->technician_id = $id;
                $ts->service_id = (string) $serviceId;
                $ts->save();
            }

            return $this->success([], '服务分配更新成功');
        }

        // GET: 获取已分配服务
        $list = TechnicianService::where('technician_id', $id)
            ->with('service')
            ->get()
            ->map(fn($ts) => $this->encodeIds($ts->toArray()));

        return $this->success(['list' => $list]);
    }
}
