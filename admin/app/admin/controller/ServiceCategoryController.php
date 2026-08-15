<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\ServiceCategory;
use support\Request;
use support\Response;

class ServiceCategoryController extends BaseController
{
    /**
     * 分类列表（树形结构）
     */
    public function index(Request $request): Response
    {
        $keyword = $request->input('keyword', '');
        $status  = $request->input('status');

        $query = ServiceCategory::with(['parent', 'children']);
        if ($keyword) {
            $query->where('name', 'like', "%{$keyword}%");
        }
        if ($status !== null && $status !== '') {
            $query->where('status', (int) $status);
        }

        $all = $query->orderBy('sort', 'asc')
                     ->orderBy('id', 'asc')
                     ->get()
                     ->map(fn($c) => $this->encodeIds($c->toArray()));

        // 构建树形结构
        $tree = $this->buildTree($all->toArray());

        return $this->success(['list' => $tree]);
    }

    /**
     * 构建树
     */
    private function buildTree(array $items, string $parentId = '0'): array
    {
        $tree = [];
        foreach ($items as $item) {
            if (($item['parent_id'] ?? '0') === $parentId) {
                $item['children'] = $this->buildTree($items, (string) $item['id']);
                $tree[] = $item;
            }
        }
        return $tree;
    }

    /**
     * 新增分类
     */
    public function store(Request $request): Response
    {
        $validator = validator($request->all(), [
            'name' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422);
        }

        $category = new ServiceCategory();
        $category->id        = (string) $this->generateId();
        $category->name      = $request->input('name');
        $category->icon      = $request->input('icon', '');
        $category->parent_id = (string) $request->input('parent_id', '0');
        $category->sort      = (int) $request->input('sort', 0);
        $category->status    = (int) $request->input('status', 1);
        $category->save();

        $this->clearSvcCache();
        return $this->success($this->encodeIds($category->toArray()), '创建成功');
    }

    /**
     * 分类详情
     */
    public function show(Request $request, string $hashid): Response
    {
        $id       = $this->decodeId($hashid);
        $category = ServiceCategory::with(['parent', 'children', 'services'])->find($id);
        if (!$category) {
            return $this->fail('分类不存在', 404);
        }

        return $this->success($this->encodeIds($category->toArray()));
    }

    /**
     * 更新分类
     */
    public function update(Request $request, string $hashid): Response
    {
        $id       = $this->decodeId($hashid);
        $category = ServiceCategory::find($id);
        if (!$category) {
            return $this->fail('分类不存在', 404);
        }

        if ($request->input('name') !== null) {
            $category->name = $request->input('name');
        }
        if ($request->input('icon') !== null) {
            $category->icon = $request->input('icon');
        }
        if ($request->input('parent_id') !== null) {
            $parentId = (string) $request->input('parent_id');
            if ($parentId === (string) $id) {
                return $this->fail('不能将自己设为父级', 422);
            }
            $category->parent_id = $parentId;
        }
        if ($request->input('sort') !== null) {
            $category->sort = (int) $request->input('sort');
        }
        if ($request->input('status') !== null) {
            $category->status = (int) $request->input('status');
        }
        $category->save();

        $this->clearSvcCache();
        return $this->success($this->encodeIds($category->toArray()), '更新成功');
    }

    /**
     * 删除分类
     */
    public function destroy(Request $request, string $hashid): Response
    {
        $id       = $this->decodeId($hashid);
        $category = ServiceCategory::find($id);
        if (!$category) {
            return $this->fail('分类不存在', 404);
        }

        $childCount = ServiceCategory::where('parent_id', $id)->count();
        if ($childCount > 0) {
            return $this->fail('该分类下存在子分类，请先删除子分类', 422);
        }

        $adminId = $request->adminId ?? 0;
        $error   = $this->confirmPassword($adminId, $request->input('password', ''), $request);
        if ($error !== null) {
            return $this->fail($error, 422);
        }

        $category->delete();
        $this->clearSvcCache();
        return $this->success([], '删除成功');
    }

    /**
     * 批量排序
     */
    public function sortAll(Request $request): Response
    {
        $sorts = $request->input('sorts', []);
        if (empty($sorts) || !is_array($sorts)) {
            return $this->fail('排序数据不能为空', 422);
        }

        foreach ($sorts as $item) {
            try {
                $id         = $this->decodeId($item['id'] ?? '');
                $category   = ServiceCategory::find($id);
                if ($category) {
                    $category->sort = (int) ($item['sort'] ?? 0);
                    $category->save();
                }
            } catch (\InvalidArgumentException $e) {
                continue;
            }
        }

        $this->clearSvcCache();
        return $this->success([], '排序更新成功');
    }
}
