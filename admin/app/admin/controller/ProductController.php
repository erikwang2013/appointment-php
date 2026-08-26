<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\Product;
use support\Request;
use support\Response;

class ProductController extends BaseController
{
    /**
     * 商品列表
     */
    public function index(Request $request): Response
    {
        $page       = (int) $request->input('page', 1);
        $limit      = (int) $request->input('limit', 15);
        $keyword    = $request->input('keyword', '');
        $status     = $request->input('status');
        $type       = $request->input('type', '');

        $query = Product::query();
        if ($keyword) {
            $query->where('name', 'like', "%{$keyword}%");
        }
        if ($status !== null && $status !== '') {
            $query->where('status', (int) $status);
        }
        if ($type) {
            $query->where('type', $type);
        }

        $total = $query->count();
        $list  = $query->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->orderBy('sort', 'asc')
                       ->orderBy('id', 'desc')
                       ->get()
                       ->map(fn($p) => $p->toArray());

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 新增商品
     */
    public function store(Request $request): Response
    {
        $validator = validator($request->all(), [
            'name'  => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422);
        }

        $product = new Product();
        $product->id             = (string) $this->generateId();
        $product->category_id    = (string) $request->input('category_id', '0');
        $product->name           = $request->input('name');
        $product->cover_image    = $request->input('cover_image', '');
        $product->images         = $request->input('images', []);
        $product->price          = (float) $request->input('price');
        $product->original_price = (float) $request->input('original_price', $request->input('price'));
        $product->stock          = (int) $request->input('stock');
        $product->type           = $request->input('type', '');
        $product->sort           = (int) $request->input('sort', 0);
        $product->status         = (int) $request->input('status', 1);
        $product->save();

        return $this->success($product->toArray(), '创建成功');
    }

    /**
     * 商品详情
     */
    public function show(Request $request, string $hashid): Response
    {
        $id      = $this->decodeId($hashid);
        $product = Product::find($id);
        if (!$product) {
            return $this->fail('商品不存在', 404);
        }

        return $this->success($product->toArray());
    }

    /**
     * 更新商品
     */
    public function update(Request $request, string $hashid): Response
    {
        $id      = $this->decodeId($hashid);
        $product = Product::find($id);
        if (!$product) {
            return $this->fail('商品不存在', 404);
        }

        $fillable = ['category_id', 'name', 'cover_image', 'images',
                     'price', 'original_price', 'stock', 'type', 'sort', 'status'];
        foreach ($fillable as $field) {
            if ($request->input($field) !== null) {
                $value = $request->input($field);
                if (in_array($field, ['price', 'original_price'])) {
                    $value = (float) $value;
                } elseif (in_array($field, ['stock', 'sort', 'status'])) {
                    $value = (int) $value;
                }
                $product->{$field} = $value;
            }
        }
        $product->save();

        return $this->success($product->toArray(), '更新成功');
    }

    /**
     * 删除商品
     */
    public function destroy(Request $request, string $hashid): Response
    {
        $id      = $this->decodeId($hashid);
        $product = Product::find($id);
        if (!$product) {
            return $this->fail('商品不存在', 404);
        }

        $adminId = $request->adminId ?? 0;
        $error   = $this->confirmPassword($adminId, $request->input('password', ''), $request);
        if ($error !== null) {
            return $this->fail($error, 422);
        }

        $product->delete();
        return $this->success([], '删除成功');
    }

    /**
     * 状态切换
     */
    public function toggleStatus(Request $request, string $hashid): Response
    {
        $id      = $this->decodeId($hashid);
        $product = Product::find($id);
        if (!$product) {
            return $this->fail('商品不存在', 404);
        }

        $product->status = $product->status === 1 ? 0 : 1;
        $product->save();

        return $this->success(
            $product->toArray(),
            $product->status === 1 ? '已上架' : '已下架'
        );
    }

    /**
     * 库存管理
     */
    public function stock(Request $request, string $hashid): Response
    {
        $id      = $this->decodeId($hashid);
        $product = Product::find($id);
        if (!$product) {
            return $this->fail('商品不存在', 404);
        }

        $quantity = (int) $request->input('quantity', 0);
        $action   = $request->input('action', 'set'); // set | add | subtract

        switch ($action) {
            case 'add':
                $product->stock = $product->stock + $quantity;
                break;
            case 'subtract':
                $product->stock = max(0, $product->stock - $quantity);
                break;
            default:
                $product->stock = $quantity;
        }
        $product->save();

        return $this->success($product->toArray(), '库存更新成功');
    }
}
