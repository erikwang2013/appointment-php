<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\admin\controller;

use app\model\LuckyWheel;
use app\model\WheelRecord;
use support\Request;
use support\Response;

/**
 * 幸运转盘奖品控制器
 *
 * 奖品 CRUD + 上下架 + 抽奖记录查看。
 * prize_type: points 积分返还 / coupon 优惠券 / balance 余额 / none 谢谢参与；
 * weight 0=不可中奖；stock -1=不限量。
 */
class LuckyWheelController extends BaseController
{
    private const PRIZE_TYPES = ['points', 'coupon', 'balance', 'none'];

    /**
     * 奖品列表（按 sort/id 排序，支持 status 过滤）
     */
    public function index(Request $request): Response
    {
        $page   = (int) $request->input('page', 1);
        $limit  = (int) $request->input('limit', 15);
        $status = $request->input('status');

        $query = LuckyWheel::query();
        if ($status !== null && $status !== '') {
            $query->where('status', (int) $status);
        }

        $total = $query->count();
        $list  = $query->orderBy('sort', 'asc')
                       ->orderBy('id', 'asc')
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
     * 新增奖品
     */
    public function store(Request $request): Response
    {
        $validator = validator($request->all(), [
            'name'        => 'required|string|max:100',
            'cost_points' => 'required|integer|min:1',
            'prize_type'  => 'required|string|in:points,coupon,balance,none',
            'prize_value' => 'required|numeric|min:0',
            'weight'      => 'required|integer|min:0',
            'stock'       => 'required|integer|min:-1',
            'sort'        => 'integer|min:0',
            'status'      => 'integer|in:0,1',
        ]);
        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422);
        }

        $prize = new LuckyWheel();
        $prize->id          = LuckyWheel::generateId();
        $prize->name        = $request->input('name');
        $prize->cost_points = (int) $request->input('cost_points');
        $prize->prize_type  = $request->input('prize_type');
        $prize->prize_value = (float) $request->input('prize_value');
        $prize->weight      = (int) $request->input('weight');
        $prize->stock       = (int) $request->input('stock', -1);
        $prize->sort        = (int) $request->input('sort', 0);
        $prize->status      = (int) $request->input('status', 0);
        $prize->save();

        return $this->success($this->encodeIds($prize->toArray()), '创建成功');
    }

    /**
     * 编辑奖品
     */
    public function update(Request $request, string $hashid): Response
    {
        $id    = $this->decodeId($hashid);
        $prize = LuckyWheel::find($id);
        if (!$prize) {
            return $this->fail('奖品不存在', 404);
        }

        if ($request->input('name') !== null) {
            $prize->name = $request->input('name');
        }
        if ($request->input('cost_points') !== null) {
            $prize->cost_points = (int) $request->input('cost_points');
        }
        if ($request->input('prize_type') !== null) {
            $type = $request->input('prize_type');
            if (!in_array($type, self::PRIZE_TYPES, true)) {
                return $this->fail('奖品类型非法', 422);
            }
            $prize->prize_type = $type;
        }
        if ($request->input('prize_value') !== null) {
            $prize->prize_value = (float) $request->input('prize_value');
        }
        if ($request->input('weight') !== null) {
            $prize->weight = (int) $request->input('weight');
        }
        if ($request->input('stock') !== null) {
            $prize->stock = (int) $request->input('stock');
        }
        if ($request->input('sort') !== null) {
            $prize->sort = (int) $request->input('sort');
        }
        if ($request->input('status') !== null) {
            $prize->status = (int) $request->input('status');
        }
        $prize->save();

        return $this->success($this->encodeIds($prize->toArray()), '更新成功');
    }

    /**
     * 删除奖品
     */
    public function destroy(Request $request, string $hashid): Response
    {
        $id    = $this->decodeId($hashid);
        $prize = LuckyWheel::find($id);
        if (!$prize) {
            return $this->fail('奖品不存在', 404);
        }

        $prize->delete();
        return $this->success([], '删除成功');
    }

    /**
     * 上下架
     */
    public function toggleStatus(Request $request, string $hashid): Response
    {
        $id    = $this->decodeId($hashid);
        $prize = LuckyWheel::find($id);
        if (!$prize) {
            return $this->fail('奖品不存在', 404);
        }

        $status = (int) $request->input('status', $prize->status === 1 ? 0 : 1);
        if (!in_array($status, [0, 1], true)) {
            return $this->fail('状态值非法', 422);
        }
        $prize->status = $status;
        $prize->save();

        return $this->success($this->encodeIds($prize->toArray()), $status === 1 ? '已上架' : '已下架');
    }

    /**
     * 抽奖记录（分页，含奖品名）
     * GET /admin/lucky-wheel/records
     */
    public function records(Request $request): Response
    {
        $page  = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 15);

        $query = WheelRecord::query();
        $total = $query->count();
        $list  = $query->orderBy('created_at', 'desc')
                       ->orderBy('id', 'desc')
                       ->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->get()
                       ->map(function (WheelRecord $record) {
                           return [
                               'id'          => (string) $record->id,
                               'user_id'     => (string) $record->user_id,
                               'prize_name'  => (string) (LuckyWheel::find($record->wheel_id)?->name ?? ''),
                               'prize_type'  => (string) $record->prize_type,
                               'prize_value' => (float) $record->prize_value,
                               'result'      => (string) $record->result,
                               'created_at'  => (string) $record->created_at,
                           ];
                       });

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }
}
