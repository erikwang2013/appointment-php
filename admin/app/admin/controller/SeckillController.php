<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\admin\controller;

use app\model\Order;
use app\model\SeckillActivity;
use app\model\Service;
use support\Request;
use support\Response;

/**
 * 秒杀活动管理控制器
 *
 * 活动 CRUD + 上下架 + 秒杀订单列表（复用订单查询，按 seckill_id 过滤）。
 */
class SeckillController extends BaseController
{
    /**
     * 秒杀活动列表（支持 name/status 过滤）
     */
    public function index(Request $request): Response
    {
        $page   = (int) $request->input('page', 1);
        $limit  = (int) $request->input('limit', 15);
        $name   = (string) $request->input('name', '');
        $status = $request->input('status');

        $query = SeckillActivity::with('service')->orderBy('id', 'desc');

        if ($name !== '') {
            $query->where('name', 'like', "%{$name}%");
        }
        if ($status !== null && $status !== '') {
            $query->where('status', (int) $status);
        }

        $total = $query->count();
        $list  = $query->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->get()
                       ->map(fn($a) => $a->toArray());

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 新增秒杀活动
     */
    public function store(Request $request): Response
    {
        $validator = validator($request->all(), [
            'name'           => 'required|string|max:100',
            'service_id'     => 'required|string',
            'seckill_price'  => 'required|numeric|min:0.01',
            'original_price' => 'required|numeric|min:0.01',
            'stock'          => 'required|integer|min:0|max:999999',
            'start_at'       => 'required|date_format:Y-m-d H:i:s',
            'end_at'         => 'required|date_format:Y-m-d H:i:s|after:start_at',
            'status'         => 'integer|in:0,1',
        ]);
        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422);
        }

        $seckillPrice  = (float) $request->input('seckill_price');
        $originalPrice = (float) $request->input('original_price');
        if ($originalPrice < $seckillPrice) {
            return $this->fail('原价不能低于秒杀价', 422);
        }

        $serviceId = $this->decodeOrFail($request->input('service_id'));
        if ($serviceId === null || !Service::find($serviceId)) {
            return $this->fail('服务不存在', 422);
        }

        $activity = new SeckillActivity();
        $activity->id             = SeckillActivity::generateId();
        $activity->name           = $request->input('name');
        $activity->service_id     = $serviceId;
        $activity->seckill_price  = $seckillPrice;
        $activity->original_price = $originalPrice;
        $activity->stock          = (int) $request->input('stock');
        $activity->start_at       = $request->input('start_at');
        $activity->end_at         = $request->input('end_at');
        $activity->status         = (int) $request->input('status', 0);
        $activity->save();

        return $this->success($activity->toArray(), '创建成功');
    }

    /**
     * 秒杀活动详情
     */
    public function show(Request $request, string $hashid): Response
    {
        $activity = $this->findActivity($hashid);
        if ($activity === null) {
            return $this->fail('秒杀活动不存在', 404);
        }

        return $this->success($activity->toArray());
    }

    /**
     * 编辑秒杀活动
     */
    public function update(Request $request, string $hashid): Response
    {
        $activity = $this->findActivity($hashid);
        if ($activity === null) {
            return $this->fail('秒杀活动不存在', 404);
        }

        if ($request->input('name') !== null) {
            $name = (string) $request->input('name');
            if ($name === '' || mb_strlen($name) > 100) {
                return $this->fail('活动名称无效', 422);
            }
            $activity->name = $name;
        }
        if ($request->input('service_id') !== null) {
            $serviceId = $this->decodeOrFail($request->input('service_id'));
            if ($serviceId === null || !Service::find($serviceId)) {
                return $this->fail('服务不存在', 422);
            }
            $activity->service_id = $serviceId;
        }
        if ($request->input('seckill_price') !== null) {
            $seckillPrice = (float) $request->input('seckill_price');
            if ($seckillPrice <= 0) {
                return $this->fail('秒杀价无效', 422);
            }
            $activity->seckill_price = $seckillPrice;
        }
        if ($request->input('original_price') !== null) {
            $originalPrice = (float) $request->input('original_price');
            if ($originalPrice <= 0 || $originalPrice < (float) $activity->seckill_price) {
                return $this->fail('原价不能低于秒杀价', 422);
            }
            $activity->original_price = $originalPrice;
        }
        if ($request->input('stock') !== null) {
            $stock = (int) $request->input('stock');
            if ($stock < 0 || $stock > 999999) {
                return $this->fail('库存无效', 422);
            }
            $activity->stock = $stock;
        }
        if ($request->input('start_at') !== null) {
            if (!strtotime((string) $request->input('start_at'))) {
                return $this->fail('开始时间无效', 422);
            }
            $activity->start_at = $request->input('start_at');
        }
        if ($request->input('end_at') !== null) {
            if (!strtotime((string) $request->input('end_at'))) {
                return $this->fail('结束时间无效', 422);
            }
            $activity->end_at = $request->input('end_at');
        }
        if ($request->input('status') !== null) {
            $status = (int) $request->input('status');
            if (!in_array($status, [0, 1], true)) {
                return $this->fail('状态无效', 422);
            }
            $activity->status = $status;
        }
        $activity->save();

        return $this->success($activity->toArray(), '更新成功');
    }

    /**
     * 删除秒杀活动
     */
    public function destroy(Request $request, string $hashid): Response
    {
        $activity = $this->findActivity($hashid);
        if ($activity === null) {
            return $this->fail('秒杀活动不存在', 404);
        }
        $activity->delete();

        return $this->success([], '删除成功');
    }

    /**
     * 上下架（1 上架 / 0 下架）
     */
    public function toggleStatus(Request $request, string $hashid): Response
    {
        $activity = $this->findActivity($hashid);
        if ($activity === null) {
            return $this->fail('秒杀活动不存在', 404);
        }
        $activity->status = $activity->status == 1 ? 0 : 1;
        $activity->save();

        return $this->success($activity->toArray(), '状态更新成功');
    }

    /**
     * 秒杀订单列表（复用订单查询，按 seckill_id 过滤）
     */
    public function orders(Request $request, string $hashid): Response
    {
        $activity = $this->findActivity($hashid);
        if ($activity === null) {
            return $this->fail('秒杀活动不存在', 404);
        }

        $page   = (int) $request->input('page', 1);
        $limit  = (int) $request->input('limit', 15);
        $status = $request->input('status', '');
        $orderNo = (string) $request->input('order_no', '');

        $query = Order::with(['user', 'technician', 'items', 'payment'])
            ->where('seckill_id', $activity->id);

        if ($status !== '') {
            $query->where('status', $status);
        }
        if ($orderNo !== '') {
            $query->where('order_no', 'like', "%{$orderNo}%");
        }

        $total = $query->count();
        $list  = $query->orderBy('created_at', 'desc')
                       ->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->get()
                       ->map(fn($o) => $o->toArray());

        return $this->success([
            'list'     => $list,
            'total'    => $total,
            'page'     => $page,
            'limit'    => $limit,
            'activity' => $activity->toArray(),
        ]);
    }

    /**
     * 解码 hashid 并查找活动；无效/不存在返回 null
     */
    private function findActivity(string $hashid): ?SeckillActivity
    {
        $id = $this->decodeOrFail($hashid);
        if ($id === null) {
            return null;
        }
        return SeckillActivity::find($id);
    }

    /**
     * 解码 hashid，无效返回 null（不抛异常）
     */
    private function decodeOrFail(string $hashid): ?int
    {
        try {
            return $this->decodeId($hashid);
        } catch (\Throwable) {
            return null;
        }
    }
}
