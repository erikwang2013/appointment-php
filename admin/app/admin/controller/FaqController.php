<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\Faq;
use support\Request;
use support\Response;

class FaqController extends BaseController
{
    /**
     * FAQ 列表
     */
    public function index(Request $request): Response
    {
        $page    = (int) $request->input('page', 1);
        $limit   = (int) $request->input('limit', 15);
        $keyword = $request->input('keyword', '');
        $status  = $request->input('status');

        $query = Faq::query();
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
                       ->map(fn($f) => $this->encodeIds($f->toArray()));

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 新增 FAQ
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

        $faq = new Faq();
        $faq->id      = (string) $this->generateId();
        $faq->title   = $request->input('title');
        $faq->content = $request->input('content');
        $faq->sort    = (int) $request->input('sort', 0);
        $faq->status  = (int) $request->input('status', 1);
        $faq->save();

        return $this->success($this->encodeIds($faq->toArray()), '创建成功');
    }

    /**
     * FAQ 详情
     */
    public function show(Request $request, string $hashid): Response
    {
        $id  = $this->decodeId($hashid);
        $faq = Faq::find($id);
        if (!$faq) {
            return $this->fail('FAQ不存在', 404);
        }

        return $this->success($this->encodeIds($faq->toArray()));
    }

    /**
     * 更新 FAQ
     */
    public function update(Request $request, string $hashid): Response
    {
        $id  = $this->decodeId($hashid);
        $faq = Faq::find($id);
        if (!$faq) {
            return $this->fail('FAQ不存在', 404);
        }

        $fillable = ['title', 'content', 'sort', 'status'];
        foreach ($fillable as $field) {
            if ($request->has($field)) {
                $value = $request->input($field);
                if (in_array($field, ['sort', 'status'])) {
                    $value = (int) $value;
                }
                $faq->{$field} = $value;
            }
        }
        $faq->save();

        return $this->success($this->encodeIds($faq->toArray()), '更新成功');
    }

    /**
     * 删除 FAQ
     */
    public function destroy(Request $request, string $hashid): Response
    {
        $id  = $this->decodeId($hashid);
        $faq = Faq::find($id);
        if (!$faq) {
            return $this->fail('FAQ不存在', 404);
        }

        $adminId = $request->adminId ?? 0;
        $error   = $this->confirmPassword($adminId, $request->input('password', ''), $request);
        if ($error !== null) {
            return $this->fail($error, 422);
        }

        $faq->delete();
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
                $id  = $this->decodeId($item['id'] ?? '');
                $faq = Faq::find($id);
                if ($faq) {
                    $faq->sort = (int) ($item['sort'] ?? 0);
                    $faq->save();
                }
            } catch (\InvalidArgumentException $e) {
                continue;
            }
        }

        return $this->success([], '排序更新成功');
    }
}
