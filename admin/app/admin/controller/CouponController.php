<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\Coupon;
use support\Request;
use support\Response;

class CouponController extends BaseController
{
    /**
     * 优惠券列表
     */
    public function index(Request $request): Response
    {
        $page    = (int) $request->input('page', 1);
        $limit   = (int) $request->input('limit', 15);
        $keyword = $request->input('keyword', '');
        $status  = $request->input('status');

        $query = Coupon::query();
        if ($keyword) {
            $query->where('name', 'like', "%{$keyword}%");
        }
        if ($status !== null && $status !== '') {
            $query->where('status', (int) $status);
        }

        $total = $query->count();
        $list  = $query->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->orderBy('id', 'desc')
                       ->get()
                       ->map(fn($c) => $this->encodeIds($c->toArray()));

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 新增优惠券
     */
    public function store(Request $request): Response
    {
        $validator = validator($request->all(), [
            'name'      => 'required|string|max:100',
            'type'      => 'required|string|in:fixed,percent',
            'amount'    => 'required|numeric|min:0',
            'total_qty' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422);
        }

        $totalQty = (int) $request->input('total_qty');

        $coupon = new Coupon();
        $coupon->id         = (string) $this->generateId();
        $coupon->name       = $request->input('name');
        $coupon->type       = $request->input('type');
        $coupon->amount     = (float) $request->input('amount');
        $coupon->min_amount = (float) $request->input('min_amount', 0);
        $coupon->total_qty  = $totalQty;
        $coupon->remain_qty = $totalQty;
        $coupon->start_at   = $request->input('start_at', date('Y-m-d H:i:s'));
        $coupon->end_at     = $request->input('end_at');
        $coupon->status     = (int) $request->input('status', 1);
        $coupon->save();

        return $this->success($this->encodeIds($coupon->toArray()), '创建成功');
    }

    /**
     * 优惠券详情
     */
    public function show(Request $request, string $hashid): Response
    {
        $id     = $this->decodeId($hashid);
        $coupon = Coupon::find($id);
        if (!$coupon) {
            return $this->fail('优惠券不存在', 404);
        }

        $data = $coupon->toArray();
        $data['used_qty'] = $coupon->total_qty - $coupon->remain_qty;

        return $this->success($this->encodeIds($data));
    }

    /**
     * 更新优惠券
     */
    public function update(Request $request, string $hashid): Response
    {
        $id     = $this->decodeId($hashid);
        $coupon = Coupon::find($id);
        if (!$coupon) {
            return $this->fail('优惠券不存在', 404);
        }

        if ($request->input('name') !== null) {
            $coupon->name = $request->input('name');
        }
        if ($request->input('type') !== null) {
            $coupon->type = $request->input('type');
        }
        if ($request->input('amount') !== null) {
            $coupon->amount = (float) $request->input('amount');
        }
        if ($request->input('min_amount') !== null) {
            $coupon->min_amount = (float) $request->input('min_amount');
        }
        if ($request->input('total_qty') !== null) {
            $diff = (int) $request->input('total_qty') - $coupon->total_qty;
            $coupon->total_qty  = (int) $request->input('total_qty');
            $coupon->remain_qty = max(0, $coupon->remain_qty + $diff);
        }
        if ($request->input('start_at') !== null) {
            $coupon->start_at = $request->input('start_at');
        }
        if ($request->input('end_at') !== null) {
            $coupon->end_at = $request->input('end_at');
        }
        if ($request->input('status') !== null) {
            $coupon->status = (int) $request->input('status');
        }
        $coupon->save();

        return $this->success($this->encodeIds($coupon->toArray()), '更新成功');
    }

    /**
     * 删除优惠券
     */
    public function destroy(Request $request, string $hashid): Response
    {
        $id     = $this->decodeId($hashid);
        $coupon = Coupon::find($id);
        if (!$coupon) {
            return $this->fail('优惠券不存在', 404);
        }

        $adminId = $request->adminId ?? 0;
        $error   = $this->confirmPassword($adminId, $request->input('password', ''), $request);
        if ($error !== null) {
            return $this->fail($error, 422);
        }

        $coupon->delete();
        return $this->success([], '删除成功');
    }
}
