<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\admin\controller;

use app\model\VideoPost;
use support\Request;
use support\Response;

/**
 * 短视频审核控制器（管理端）
 *
 * 管理技师短视频的审核：待审列表、通过、拒绝
 */
class VideoAuditController extends BaseController
{
    /**
     * 待审核视频列表
     * GET /admin/video-audit?status=0&page=1
     */
    public function index(Request $request): Response
    {
        $page   = (int) $request->input('page', 1);
        $limit  = (int) $request->input('limit', 15);
        $status = $request->input('status');

        $query = VideoPost::query()->with('technician');

        if ($status !== null && $status !== '') {
            $query->where('status', (int) $status);
        }

        $query->orderBy('created_at', 'desc');

        $total = $query->count();
        $list  = $query->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->get()
                       ->map(fn($v) => $this->encodeIds($v->toArray()));

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 审核视频（通过/拒绝）
     * POST /admin/video-audit/{hashid}
     */
    public function audit(Request $request, string $hashid): Response
    {
        $id    = $this->decodeId($hashid);
        $video = VideoPost::find($id);

        if (!$video) {
            return $this->fail('视频不存在', 404);
        }

        $action = $request->input('action', 'approve');
        $reason = $request->input('reason', '');

        if ($action === 'approve') {
            $video->status = VideoPost::STATUS_PUBLISHED;
        } elseif ($action === 'reject') {
            $video->status = VideoPost::STATUS_REJECTED;
            if ($reason) {
                $video->reject_reason = $reason;
            }
        } else {
            return $this->fail('无效的审核操作', 422);
        }

        $video->save();

        return $this->success(
            $this->encodeIds($video->toArray()),
            $action === 'approve' ? '已通过审核' : '已拒绝'
        );
    }
}
