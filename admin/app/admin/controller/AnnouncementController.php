<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\Announcement;
use support\Request;
use support\Response;

class AnnouncementController extends BaseController
{
    /**
     * 公告列表
     */
    public function index(Request $request): Response
    {
        $page    = (int) $request->input('page', 1);
        $limit   = (int) $request->input('limit', 15);
        $keyword = $request->input('keyword', '');
        $status  = $request->input('status');

        $query = Announcement::query();
        if ($keyword) {
            $query->where('title', 'like', "%{$keyword}%");
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
                       ->map(fn($a) => $a->toArray());

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 新增公告
     */
    public function store(Request $request): Response
    {
        $validator = validator($request->all(), [
            'title'   => 'required|string|max:200',
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422);
        }

        $announcement = new Announcement();
        $announcement->id      = (string) $this->generateId();
        $announcement->title   = $request->input('title');
        $announcement->content = $request->input('content');
        $announcement->sort    = (int) $request->input('sort', 0);
        $announcement->status  = (int) $request->input('status', 0);
        $announcement->save();

        $this->clearSvcCache();
        return $this->success($announcement->toArray(), '创建成功');
    }

    /**
     * 公告详情
     */
    public function show(Request $request, string $hashid): Response
    {
        $id           = $this->decodeId($hashid);
        $announcement = Announcement::find($id);
        if (!$announcement) {
            return $this->fail('公告不存在', 404);
        }

        return $this->success($announcement->toArray());
    }

    /**
     * 更新公告
     */
    public function update(Request $request, string $hashid): Response
    {
        $id           = $this->decodeId($hashid);
        $announcement = Announcement::find($id);
        if (!$announcement) {
            return $this->fail('公告不存在', 404);
        }

        $fillable = ['title', 'content', 'sort', 'status'];
        foreach ($fillable as $field) {
            if ($request->input($field) !== null) {
                $value = $request->input($field);
                if (in_array($field, ['sort', 'status'])) {
                    $value = (int) $value;
                }
                $announcement->{$field} = $value;
            }
        }
        $announcement->save();

        $this->clearSvcCache();
        return $this->success($announcement->toArray(), '更新成功');
    }

    /**
     * 删除公告
     */
    public function destroy(Request $request, string $hashid): Response
    {
        $id           = $this->decodeId($hashid);
        $announcement = Announcement::find($id);
        if (!$announcement) {
            return $this->fail('公告不存在', 404);
        }

        $adminId = $request->adminId ?? 0;
        $error   = $this->confirmPassword($adminId, $request->input('password', ''), $request);
        if ($error !== null) {
            return $this->fail($error, 422);
        }

        $announcement->delete();
        $this->clearSvcCache();
        return $this->success([], '删除成功');
    }

    /**
     * 发布/取消发布
     */
    public function publish(Request $request, string $hashid): Response
    {
        $id           = $this->decodeId($hashid);
        $announcement = Announcement::find($id);
        if (!$announcement) {
            return $this->fail('公告不存在', 404);
        }

        $action = $request->input('action', 'publish');
        if ($action === 'publish') {
            $announcement->status       = 1;
            $announcement->published_at = date('Y-m-d H:i:s');
        } else {
            $announcement->status = 0;
            $announcement->published_at = null;
        }
        $announcement->save();

        $this->clearSvcCache();
        return $this->success(
            $announcement->toArray(),
            $action === 'publish' ? '已发布' : '已取消发布'
        );
    }
}
