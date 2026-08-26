<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\Service;
use support\Request;
use support\Response;

class ServiceController extends BaseController
{
    /**
     * 服务列表
     */
    public function index(Request $request): Response
    {
        $page       = (int) $request->input('page', 1);
        $limit      = (int) $request->input('limit', 15);
        $keyword    = $request->input('keyword', '');
        $categoryId = $request->input('category_id', '');
        $status     = $request->input('status');

        $query = Service::with('category');
        if ($keyword) {
            $query->where('name', 'like', "%{$keyword}%");
        }
        if ($categoryId) {
            try {
                $query->where('category_id', $this->decodeId($categoryId));
            } catch (\InvalidArgumentException) {
                return $this->fail('无效的分类ID', 422);
            }
        }
        if ($status !== null && $status !== '') {
            $query->where('status', (int) $status);
        }

        $total = $query->count();
        $list  = $query->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->orderBy('sort', 'asc')
                       ->orderBy('id', 'desc')
                       ->get()
                       ->map(fn($s) => $s->toArray());

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 新增服务
     */
    public function store(Request $request): Response
    {
        $validator = validator($request->all(), [
            'category_id' => 'required|string',
            'name'        => 'required|string|max:100',
            'price'       => 'required|numeric|min:0',
            'duration'    => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422);
        }

        try {
            $categoryId = $this->decodeId($request->input('category_id'));
        } catch (\InvalidArgumentException) {
            return $this->fail('无效的分类ID', 422);
        }

        $service = new Service();
        $service->id             = (string) $this->generateId();
        $service->category_id    = $categoryId;
        $service->name           = $request->input('name');
        $service->description    = $request->input('description', '');
        $service->cover_image    = $request->input('cover_image', '');
        $service->images         = $request->input('images', []);
        $service->price          = (float) $request->input('price');
        $service->original_price = (float) $request->input('original_price', $request->input('price'));
        $service->duration       = (int) $request->input('duration');
        $service->specs          = $request->input('specs', []);
        $service->sort           = (int) $request->input('sort', 0);
        $service->status         = (int) $request->input('status', 1);
        $service->save();

        $this->clearSvcCache();
        return $this->success($service->toArray(), '创建成功');
    }

    /**
     * 服务详情
     */
    public function show(Request $request, string $hashid): Response
    {
        $id      = $this->decodeId($hashid);
        $service = Service::with('category')->find($id);
        if (!$service) {
            return $this->fail('服务不存在', 404);
        }

        return $this->success($service->toArray());
    }

    /**
     * 更新服务
     */
    public function update(Request $request, string $hashid): Response
    {
        $id      = $this->decodeId($hashid);
        $service = Service::find($id);
        if (!$service) {
            return $this->fail('服务不存在', 404);
        }

        $fillable = ['category_id', 'name', 'description', 'cover_image', 'images',
                     'price', 'original_price', 'duration', 'specs', 'sort', 'status'];
        foreach ($fillable as $field) {
            if ($request->input($field) !== null) {
                $value = $request->input($field);
                if ($field === 'category_id') {
                    try {
                        $value = $this->decodeId($value);
                    } catch (\InvalidArgumentException) {
                        return $this->fail('无效的分类ID', 422);
                    }
                } elseif (in_array($field, ['price', 'original_price'])) {
                    $value = (float) $value;
                } elseif (in_array($field, ['duration', 'sort', 'status'])) {
                    $value = (int) $value;
                }
                $service->{$field} = $value;
            }
        }
        $service->save();

        $this->clearSvcCache();
        return $this->success($service->toArray(), '更新成功');
    }

    /**
     * 删除服务
     */
    public function destroy(Request $request, string $hashid): Response
    {
        $id      = $this->decodeId($hashid);
        $service = Service::find($id);
        if (!$service) {
            return $this->fail('服务不存在', 404);
        }

        $adminId = $request->adminId ?? 0;
        $error   = $this->confirmPassword($adminId, $request->input('password', ''), $request);
        if ($error !== null) {
            return $this->fail($error, 422);
        }

        $service->delete();
        $this->clearSvcCache();
        return $this->success([], '删除成功');
    }
}
