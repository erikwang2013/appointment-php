<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\Order;
use app\model\OrderVerification;
use app\model\Store;
use support\Request;
use support\Response;

class StoreController extends BaseController
{
    /**
     * 门店列表
     */
    public function index(Request $request): Response
    {
        $page    = (int) $request->input('page', 1);
        $limit   = (int) $request->input('limit', 15);
        $keyword = $request->input('keyword', '');
        $status  = $request->input('status');

        $query = Store::query();
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('address', 'like', "%{$keyword}%");
            });
        }
        if ($status !== null && $status !== '') {
            $query->where('status', (int) $status);
        }

        $total = $query->count();
        $list  = $query->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->orderBy('id', 'desc')
                       ->get()
                       ->map(fn($s) => $this->encodeIds($s->toArray()));

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 店长工作台概览（按门店）
     * GET /admin/stores/workbench-overview?store_id={hashid}
     *
     * 今日口径与 service 端 StoreManagerController::overview 一致：
     * 今日订单数按 created_at 今日；今日营收按 completed 且 updated_at 今日；
     * 进行中 = pending/paid/confirmed/serving；技师数 = 本店订单去重 technician_id；
     * 核销数 = 本店订单今日核销记录数。
     */
    public function workbenchOverview(Request $request): Response
    {
        $storeHashid = (string) $request->input('store_id', '');
        if ($storeHashid === '') {
            return $this->fail('请选择门店', 422);
        }
        $storeId = $this->decodeId($storeHashid);
        $store = Store::find($storeId);
        if (!$store) {
            return $this->fail('门店不存在', 404);
        }

        $todayStart = date('Y-m-d 00:00:00');

        $todayOrders = Order::where('store_id', $storeId)
            ->where('created_at', '>=', $todayStart)
            ->count();

        $todayRevenue = (float) Order::where('store_id', $storeId)
            ->where('status', Order::STATUS_COMPLETED)
            ->where('updated_at', '>=', $todayStart)
            ->sum('paid_amount');

        $ongoingOrders = Order::where('store_id', $storeId)
            ->whereIn('status', [
                Order::STATUS_PENDING,
                Order::STATUS_PAID,
                Order::STATUS_CONFIRMED,
                Order::STATUS_SERVING,
            ])
            ->count();

        $technicianCount = Order::where('store_id', $storeId)
            ->where('technician_id', '>', 0)
            ->distinct()
            ->count('technician_id');

        $verificationCount = OrderVerification::where('verified_at', '>=', $todayStart)
            ->whereIn('order_id', function ($q) use ($storeId) {
                $q->select('id')->from('erik_order')->where('store_id', $storeId);
            })
            ->count();

        return $this->success([
            'store_name'         => $store->name,
            'today_orders'       => $todayOrders,
            'today_revenue'      => $todayRevenue,
            'ongoing_orders'     => $ongoingOrders,
            'technician_count'   => $technicianCount,
            'verification_count' => $verificationCount,
        ]);
    }

    /**
     * 新增门店
     */
    public function store(Request $request): Response
    {
        $validator = validator($request->all(), [
            'name'    => 'required|string|max:100',
            'address' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422);
        }

        $store = new Store();
        $store->id       = (string) $this->generateId();
        $store->name     = $request->input('name');
        $store->address  = $request->input('address');
        $store->lat      = $request->input('lat', 0);
        $store->lng      = $request->input('lng', 0);
        $store->phone    = $request->input('phone', '');
        $store->business_hours = $request->input('business_hours', []);
        $store->images   = $request->input('images', []);
        $store->status   = (int) $request->input('status', 1);
        $store->save();
        $this->clearSvcCache(); // 门店数据变更，失效 LBS 附近门店缓存（svc:lbs:stores:*）

        return $this->success($this->encodeIds($store->toArray()), '创建成功');
    }

    /**
     * 门店详情
     */
    public function show(Request $request, string $hashid): Response
    {
        $id    = $this->decodeId($hashid);
        $store = Store::find($id);
        if (!$store) {
            return $this->fail('门店不存在', 404);
        }

        return $this->success($this->encodeIds($store->toArray()));
    }

    /**
     * 更新门店
     */
    public function update(Request $request, string $hashid): Response
    {
        $id    = $this->decodeId($hashid);
        $store = Store::find($id);
        if (!$store) {
            return $this->fail('门店不存在', 404);
        }

        if ($request->has('name')) {
            $store->name = $request->input('name');
        }
        if ($request->has('address')) {
            $store->address = $request->input('address');
        }
        if ($request->has('lat')) {
            $store->lat = (float) $request->input('lat');
        }
        if ($request->has('lng')) {
            $store->lng = (float) $request->input('lng');
        }
        if ($request->has('phone')) {
            $store->phone = $request->input('phone');
        }
        if ($request->has('business_hours')) {
            $store->business_hours = $request->input('business_hours', []);
        }
        if ($request->has('images')) {
            $store->images = $request->input('images', []);
        }
        if ($request->has('status')) {
            $store->status = (int) $request->input('status');
        }
        $store->save();
        $this->clearSvcCache(); // 门店数据变更，失效 LBS 附近门店缓存（svc:lbs:stores:*）

        return $this->success($this->encodeIds($store->toArray()), '更新成功');
    }

    /**
     * 删除门店
     */
    public function destroy(Request $request, string $hashid): Response
    {
        $id    = $this->decodeId($hashid);
        $store = Store::find($id);
        if (!$store) {
            return $this->fail('门店不存在', 404);
        }

        $adminId = $request->adminId ?? 0;
        $error   = $this->confirmPassword($adminId, $request->input('password', ''), $request);
        if ($error !== null) {
            return $this->fail($error, 422);
        }

        $store->delete();
        $this->clearSvcCache(); // 门店数据变更，失效 LBS 附近门店缓存（svc:lbs:stores:*）
        return $this->success([], '删除成功');
    }

    /**
     * 状态切换 (0/1)
     */
    public function toggleStatus(Request $request, string $hashid): Response
    {
        $id    = $this->decodeId($hashid);
        $store = Store::find($id);
        if (!$store) {
            return $this->fail('门店不存在', 404);
        }

        $store->status = $store->status === 1 ? 0 : 1;
        $store->save();
        $this->clearSvcCache(); // 门店状态变更，失效 LBS 附近门店缓存（svc:lbs:stores:*）

        return $this->success(
            $this->encodeIds($store->toArray()),
            $store->status === 1 ? '已启用' : '已禁用'
        );
    }
}
