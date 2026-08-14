<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\order\v1\controller;

use app\common\BaseController;
use support\Redis;
use Webman\Http\Request;

/**
 * 购物车控制器（Redis 存储，键 cart:{user_id}）
 *
 * GET    /api/order/cart  读取购物车
 * POST   /api/order/cart  整体覆盖保存购物车（body: {items:[{id,name,price,cover_image,quantity}]}）
 * DELETE /api/order/cart  清空购物车
 *
 * 契约：code=0 成功，data 为购物车条目数组；条目字段固定为
 * id/name/price/cover_image/quantity，均由本控制器规范化后回写
 */
class CartController extends BaseController
{
    private function cartKey(int $userId): string
    {
        return "cart:{$userId}";
    }

    /**
     * 读取购物车
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function index(Request $request)
    {
        $userId = (int)$request->user_id;
        $raw = Redis::get($this->cartKey($userId));
        $items = $raw ? json_decode($raw, true) : [];
        if (!is_array($items)) {
            $items = [];
        }

        return $this->success($this->normalize($items));
    }

    /**
     * 保存购物车（整体覆盖）
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function store(Request $request)
    {
        $userId = (int)$request->user_id;
        $items = $request->input('items', []);

        if (!is_array($items)) {
            return $this->error('购物车数据格式错误');
        }

        $items = $this->normalize($items);
        Redis::set($this->cartKey($userId), json_encode($items, JSON_UNESCAPED_UNICODE));

        return $this->success($items, '购物车已保存');
    }

    /**
     * 清空购物车
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function destroy(Request $request)
    {
        Redis::del($this->cartKey((int)$request->user_id));

        return $this->success([], '购物车已清空');
    }

    /**
     * 规范化购物车条目：只透传约定字段，丢弃脏数据
     * @param array $items
     * @return array
     */
    private function normalize(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $quantity = max(1, (int)($item['quantity'] ?? 1));
            $result[] = [
                'id'          => (string)($item['id'] ?? ''),
                'name'        => (string)($item['name'] ?? ''),
                'price'       => (float)($item['price'] ?? 0),
                'cover_image' => (string)($item['cover_image'] ?? ''),
                'quantity'    => $quantity,
            ];
        }

        return $result;
    }
}
