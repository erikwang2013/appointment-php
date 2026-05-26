<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\admin\controller;

use app\model\Comment;
use app\model\Post;
use support\Db;
use support\Request;
use support\Response;

/**
 * 社区审核控制器（管理端）
 *
 * 管理社区帖子：列表、置顶/取消置顶、隐藏、删除
 */
class CommunityModerationController extends BaseController
{
    /**
     * 社区帖子列表（含状态筛选）
     * GET /admin/community-moderation?status=1&page=1
     */
    public function index(Request $request): Response
    {
        $page   = (int) $request->input('page', 1);
        $limit  = (int) $request->input('limit', 15);
        $status = $request->input('status');
        $keyword = $request->input('keyword', '');

        $query = Post::query()->with('user');

        if ($status !== null && $status !== '') {
            $query->where('status', (int) $status);
        }

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('content', 'like', "%{$keyword}%");
            });
        }

        $query->orderBy('is_pinned', 'desc')
              ->orderBy('created_at', 'desc');

        $total = $query->count();
        $list  = $query->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->get()
                       ->map(fn($p) => $this->encodeIds($p->toArray()));

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 置顶帖子
     * POST /admin/community-moderation/pin/{hashid}
     */
    public function pin(Request $request, string $hashid): Response
    {
        $id   = $this->decodeId($hashid);
        $post = Post::find($id);

        if (!$post) {
            return $this->fail('帖子不存在', 404);
        }

        $post->is_pinned = 1;
        $post->save();

        return $this->success($this->encodeIds($post->toArray()), '已置顶');
    }

    /**
     * 取消置顶
     * POST /admin/community-moderation/unpin/{hashid}
     */
    public function unpin(Request $request, string $hashid): Response
    {
        $id   = $this->decodeId($hashid);
        $post = Post::find($id);

        if (!$post) {
            return $this->fail('帖子不存在', 404);
        }

        $post->is_pinned = 0;
        $post->save();

        return $this->success($this->encodeIds($post->toArray()), '已取消置顶');
    }

    /**
     * 隐藏帖子
     * POST /admin/community-moderation/hide/{hashid}
     */
    public function hide(Request $request, string $hashid): Response
    {
        $id   = $this->decodeId($hashid);
        $post = Post::find($id);

        if (!$post) {
            return $this->fail('帖子不存在', 404);
        }

        $post->status = Post::STATUS_HIDDEN;
        $post->save();

        return $this->success($this->encodeIds($post->toArray()), '已隐藏');
    }

    /**
     * 删除帖子（含所有评论）
     * DELETE /admin/community-moderation/{hashid}
     */
    public function destroy(Request $request, string $hashid): Response
    {
        $id   = $this->decodeId($hashid);
        $post = Post::find($id);

        if (!$post) {
            return $this->fail('帖子不存在', 404);
        }

        $adminId = $request->adminId ?? 0;
        $error   = $this->confirmPassword($adminId, $request->input('password', ''), $request);
        if ($error !== null) {
            return $this->fail($error, 422);
        }

        Db::beginTransaction();
        try {
            // 删除帖子下所有评论
            Comment::where('post_id', $post->id)->delete();
            $post->delete();
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollBack();
            return $this->fail('删除失败: ' . $e->getMessage());
        }

        return $this->success([], '已删除');
    }
}
