<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\Banner;
use support\Request;
use support\Response;

class BannerController extends BaseController
{
    /**
     * Banner 列表
     */
    public function index(Request $request): Response
    {
        $page     = (int) $request->input('page', 1);
        $limit    = (int) $request->input('limit', 15);
        $position = $request->input('position', '');
        $status   = $request->input('status');

        $query = Banner::query();
        if ($position) {
            $query->where('position', $position);
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
                       ->map(fn($b) => $this->encodeIds($b->toArray()));

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 新增 Banner
     */
    public function store(Request $request): Response
    {
        $validator = validator($request->all(), [
            'image' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422);
        }

        $banner = new Banner();
        $banner->id         = (string) $this->generateId();
        $banner->position   = $request->input('position', '');
        $banner->image      = $request->input('image');
        $banner->jump_type  = $request->input('jump_type', 'none');
        $banner->jump_value = $request->input('jump_value', '');
        $banner->sort       = (int) $request->input('sort', 0);
        $banner->status     = (int) $request->input('status', 1);
        $banner->save();

        $this->clearSvcCache();
        return $this->success($this->encodeIds($banner->toArray()), '创建成功');
    }

    /**
     * Banner 详情
     */
    public function show(Request $request, string $hashid): Response
    {
        $id     = $this->decodeId($hashid);
        $banner = Banner::find($id);
        if (!$banner) {
            return $this->fail('Banner不存在', 404);
        }

        return $this->success($this->encodeIds($banner->toArray()));
    }

    /**
     * 更新 Banner
     */
    public function update(Request $request, string $hashid): Response
    {
        $id     = $this->decodeId($hashid);
        $banner = Banner::find($id);
        if (!$banner) {
            return $this->fail('Banner不存在', 404);
        }

        $fillable = ['position', 'image', 'jump_type', 'jump_value', 'sort', 'status'];
        foreach ($fillable as $field) {
            if ($request->input($field) !== null) {
                $value = $request->input($field);
                if (in_array($field, ['sort', 'status'])) {
                    $value = (int) $value;
                }
                $banner->{$field} = $value;
            }
        }
        $banner->save();

        $this->clearSvcCache();
        return $this->success($this->encodeIds($banner->toArray()), '更新成功');
    }

    /**
     * 删除 Banner
     */
    public function destroy(Request $request, string $hashid): Response
    {
        $id     = $this->decodeId($hashid);
        $banner = Banner::find($id);
        if (!$banner) {
            return $this->fail('Banner不存在', 404);
        }

        $adminId = $request->adminId ?? 0;
        $error   = $this->confirmPassword($adminId, $request->input('password', ''), $request);
        if ($error !== null) {
            return $this->fail($error, 422);
        }

        $banner->delete();
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
                $id     = $this->decodeId($item['id'] ?? '');
                $banner = Banner::find($id);
                if ($banner) {
                    $banner->sort = (int) ($item['sort'] ?? 0);
                    $banner->save();
                }
            } catch (\InvalidArgumentException $e) {
                continue;
            }
        }

        $this->clearSvcCache();
        return $this->success([], '排序更新成功');
    }
}
