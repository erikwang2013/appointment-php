<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\Moment;
use support\Request;
use support\Response;

class MomentController extends BaseController
{
    /**
     * 动态列表
     */
    public function index(Request $request): Response
    {
        $page    = (int) $request->input('page', 1);
        $limit   = (int) $request->input('limit', 15);
        $keyword = $request->input('keyword', '');
        $status  = $request->input('status');

        $query = Moment::query();
        if ($keyword) {
            $query->where('content', 'like', "%{$keyword}%");
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
                       ->map(fn($m) => $this->encodeIds($m->toArray()));

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 动态详情
     */
    public function show(Request $request, string $hashid): Response
    {
        $id     = $this->decodeId($hashid);
        $moment = Moment::find($id);
        if (!$moment) {
            return $this->fail('动态不存在', 404);
        }

        return $this->success($this->encodeIds($moment->toArray()));
    }

    /**
     * 审核: approve (status=1) 或 reject (status=2)
     */
    public function audit(Request $request, string $hashid): Response
    {

        $id     = $this->decodeId($hashid);
        $moment = Moment::find($id);
        if (!$moment) {
            return $this->fail('动态不存在', 404);
        }

        $action = $request->input('action', '');
        if (!in_array($action, ['approve', 'reject'], true)) {
            return $this->fail('操作类型无效，支持 approve / reject', 422);
        }

        $moment->status = $action === 'approve' ? 1 : 2;
        if ($action === 'approve') {
            $moment->published_at = date('Y-m-d H:i:s');
        }
        $moment->save();

        return $this->success(
            $this->encodeIds($moment->toArray()),
            $action === 'approve' ? '审核通过' : '已驳回'
        );
    }

    /**
     * 删除动态
     */
    public function destroy(Request $request, string $hashid): Response
    {

        $id     = $this->decodeId($hashid);
        $moment = Moment::find($id);
        if (!$moment) {
            return $this->fail('动态不存在', 404);
        }

        $moment->delete();
        return $this->success([], '删除成功');
    }
}
