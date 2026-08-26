<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\PointsExchangeGoods;
use app\model\UserPointsExchange;
use support\Request;
use support\Response;

/**
 * 积分兑换商品管理（CRUD + 上下架 + 兑换记录列表）
 */
class PointsExchangeGoodsController extends BaseController
{
    /**
     * 兑换商品列表
     */
    public function index(Request $request): Response
    {
        $page    = (int) $request->input('page', 1);
        $limit   = (int) $request->input('limit', 15);
        $keyword = (string) $request->input('keyword', '');
        $status  = $request->input('status');

        $query = PointsExchangeGoods::query();
        if ($keyword !== '') {
            $query->where('name', 'like', "%{$keyword}%");
        }
        if ($status !== null && $status !== '') {
            $query->where('status', (int) $status);
        }

        $total = $query->count();
        $list  = $query->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->orderBy('sort', 'desc')
                       ->orderBy('id', 'desc')
                       ->get()
                       ->map(fn($g) => $g->toArray());

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 新增兑换商品
     */
    public function store(Request $request): Response
    {
        $validator = validator($request->all(), [
            'name'       => 'required|string|max:100',
            'type'       => 'required|string|in:coupon,gift_card,wallet',
            'points_cost'=> 'required|integer|min:0',
            'value'      => 'required|numeric|min:0',
            'stock'      => 'required|integer|min:0',
            'status'     => 'sometimes|integer|in:0,1',
            'sort'       => 'sometimes|integer',
        ]);
        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422);
        }

        $goods = new PointsExchangeGoods();
        $goods->id          = (string) $this->generateId();
        $goods->name        = (string) $request->input('name');
        $goods->type        = (string) $request->input('type');
        $goods->points_cost = (int) $request->input('points_cost');
        $goods->value       = $this->parseValue($goods->type, (string) $request->input('value'), $error);
        if ($error !== null) {
            return $this->fail($error, 422);
        }
        $goods->stock       = (int) $request->input('stock');
        $goods->status      = (int) $request->input('status', 1);
        $goods->sort        = (int) $request->input('sort', 0);
        $goods->save();

        return $this->success($goods->toArray(), '创建成功');
    }

    /**
     * 兑换商品详情
     */
    public function show(Request $request, string $hashid): Response
    {
        $id    = $this->decodeId($hashid);
        $goods = PointsExchangeGoods::find($id);
        if (!$goods) {
            return $this->fail('商品不存在', 404);
        }

        $data = $goods->toArray();
        $data['exchanged_count'] = UserPointsExchange::where('goods_id', $goods->id)->count();

        return $this->success($data);
    }

    /**
     * 更新兑换商品
     */
    public function update(Request $request, string $hashid): Response
    {
        $id    = $this->decodeId($hashid);
        $goods = PointsExchangeGoods::find($id);
        if (!$goods) {
            return $this->fail('商品不存在', 404);
        }

        if ($request->input('name') !== null) {
            $goods->name = (string) $request->input('name');
        }
        if ($request->input('type') !== null) {
            if (!in_array($request->input('type'), ['coupon', 'gift_card', 'wallet'], true)) {
                return $this->fail('商品类型无效', 422);
            }
            $goods->type = (string) $request->input('type');
        }
        if ($request->input('points_cost') !== null) {
            $goods->points_cost = max(0, (int) $request->input('points_cost'));
        }
        if ($request->input('value') !== null) {
            $value = $this->parseValue($goods->type, (string) $request->input('value'), $error);
            if ($error !== null) {
                return $this->fail($error, 422);
            }
            $goods->value = $value;
        }
        if ($request->input('stock') !== null) {
            $goods->stock = max(0, (int) $request->input('stock'));
        }
        if ($request->input('status') !== null) {
            $goods->status = (int) $request->input('status') ? 1 : 0;
        }
        if ($request->input('sort') !== null) {
            $goods->sort = (int) $request->input('sort');
        }
        $goods->save();

        return $this->success($goods->toArray(), '更新成功');
    }

    /**
     * 删除兑换商品
     */
    public function destroy(Request $request, string $hashid): Response
    {
        $id    = $this->decodeId($hashid);
        $goods = PointsExchangeGoods::find($id);
        if (!$goods) {
            return $this->fail('商品不存在', 404);
        }

        $adminId = $request->adminId ?? 0;
        $error   = $this->confirmPassword($adminId, $request->input('password', ''), $request);
        if ($error !== null) {
            return $this->fail($error, 422);
        }

        $goods->delete();
        return $this->success([], '删除成功');
    }

    /**
     * 解析商品 value（coupon=优惠券 hashid 解码；wallet/gift_card=金额元，保留两位小数）
     *
     * @param string       $type  商品类型
     * @param string       $input 原始输入
     * @param string|null  $error 失败原因（引用传出）
     */
    private function parseValue(string $type, string $input, ?string &$error): float|int
    {
        $error = null;
        if ($type === 'coupon') {
            $couponId = $this->decodeId($input);
            if ($couponId === null) {
                $error = '优惠券ID无效';
                return 0;
            }
            return $couponId;
        }
        $amount = round((float) $input, 2);
        if ($amount < 0) {
            $error = '金额不能为负';
            return 0;
        }
        return $amount;
    }

    /**
     * 上下架切换
     * POST /admin/points-exchange-goods/{id}/toggle-status
     */
    public function toggleStatus(Request $request, string $hashid): Response
    {
        $id    = $this->decodeId($hashid);
        $goods = PointsExchangeGoods::find($id);
        if (!$goods) {
            return $this->fail('商品不存在', 404);
        }

        $goods->status = $goods->status == 1 ? 0 : 1;
        $goods->save();

        return $this->success([
            'id'     => $goods->id,
            'status' => $goods->status,
        ], $goods->status == 1 ? '已上架' : '已下架');
    }

    /**
     * 兑换记录列表（可按商品筛选，分页）
     * GET /admin/points-exchange-goods/{id}/exchanges
     */
    public function exchanges(Request $request, string $hashid): Response
    {
        $page  = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 15);

        $goodsId = $this->decodeId($hashid);
        $goods   = PointsExchangeGoods::find($goodsId);
        if (!$goods) {
            return $this->fail('商品不存在', 404);
        }

        $total = UserPointsExchange::where('goods_id', $goods->id)->count();
        $list  = UserPointsExchange::with('user')
            ->where('goods_id', $goods->id)
            ->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->map(function ($record) {
                $row = $record->toArray();
                $row['result'] = json_decode((string) $record->result, true) ?: (object) [];
                $row['phone']  = $record->user ? $record->user->phone : '';
                unset($row['user']);
                return $row;
            });

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }
}
