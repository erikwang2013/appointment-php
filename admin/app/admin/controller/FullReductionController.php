<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\admin\controller;

use app\model\FullReductionActivity;
use support\Request;
use support\Response;

/**
 * 满减活动控制器（满 X 减 Y 营销活动）
 *
 * 支持 CRUD 与上下架；满减对标准订单在券/次卡优惠后、等级折扣前生效。
 */
class FullReductionController extends BaseController
{
    /**
     * 满减活动列表
     */
    public function index(Request $request): Response
    {
        $page   = (int) $request->input('page', 1);
        $limit  = (int) $request->input('limit', 15);
        $status = $request->input('status');

        $query = FullReductionActivity::query();
        if ($status !== null && $status !== '') {
            $query->where('status', (int) $status);
        }

        $total = $query->count();
        $list  = $query->orderBy('created_at', 'desc')
                       ->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->get()
                       ->map(fn($a) => $this->encodeIds($a->toArray()));

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 新增满减活动
     */
    public function store(Request $request): Response
    {
        $validator = validator($request->all(), [
            'title'     => 'required|string|max:100',
            'threshold' => 'required|numeric|min:0.01',
            'reduction' => 'required|numeric|min:0.01',
            'start_at'  => 'required|date_format:Y-m-d H:i:s',
            'end_at'    => 'required|date_format:Y-m-d H:i:s|after:start_at',
        ]);
        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422);
        }

        $activity = new FullReductionActivity();
        $activity->id        = FullReductionActivity::generateId();
        $activity->title     = $request->input('title');
        $activity->threshold = (float) $request->input('threshold');
        $activity->reduction = (float) $request->input('reduction');
        $activity->status    = (int) $request->input('status', 0);
        $activity->start_at  = $request->input('start_at');
        $activity->end_at    = $request->input('end_at');
        $activity->save();

        return $this->success($this->encodeIds($activity->toArray()), '创建成功');
    }

    /**
     * 编辑满减活动
     */
    public function update(Request $request, string $hashid): Response
    {
        $id       = $this->decodeId($hashid);
        $activity = FullReductionActivity::find($id);
        if (!$activity) {
            return $this->fail('满减活动不存在', 404);
        }

        if ($request->input('title') !== null) {
            $activity->title = $request->input('title');
        }
        if ($request->input('threshold') !== null) {
            $activity->threshold = (float) $request->input('threshold');
        }
        if ($request->input('reduction') !== null) {
            $activity->reduction = (float) $request->input('reduction');
        }
        if ($request->input('status') !== null) {
            $activity->status = (int) $request->input('status');
        }
        if ($request->input('start_at') !== null) {
            $activity->start_at = $request->input('start_at');
        }
        if ($request->input('end_at') !== null) {
            $activity->end_at = $request->input('end_at');
        }
        $activity->save();

        return $this->success($this->encodeIds($activity->toArray()), '更新成功');
    }

    /**
     * 删除满减活动
     */
    public function destroy(Request $request, string $hashid): Response
    {
        $id       = $this->decodeId($hashid);
        $activity = FullReductionActivity::find($id);
        if (!$activity) {
            return $this->fail('满减活动不存在', 404);
        }

        $adminId = $request->adminId ?? 0;
        $error   = $this->confirmPassword($adminId, $request->input('password', ''), $request);
        if ($error !== null) {
            return $this->fail($error, 422);
        }

        $activity->delete();
        return $this->success([], '删除成功');
    }

    /**
     * 满减活动上下架
     */
    public function toggleStatus(Request $request, string $hashid): Response
    {
        $id       = $this->decodeId($hashid);
        $activity = FullReductionActivity::find($id);
        if (!$activity) {
            return $this->fail('满减活动不存在', 404);
        }

        $status = (int) $request->input('status', $activity->status === 1 ? 0 : 1);
        if (!in_array($status, [0, 1], true)) {
            return $this->fail('状态值非法', 422);
        }
        $activity->status = $status;
        $activity->save();

        return $this->success($this->encodeIds($activity->toArray()), $status === 1 ? '已上架' : '已下架');
    }
}
