<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\model\Comment;
use app\model\Post;
use Illuminate\Support\Facades\Redis;
use support\Db;
use Webman\Http\Request;

/**
 * 社区评论控制器
 *
 * 处理评论的增删查操作
 */
class CommunityCommentController extends BaseController
{
    /**
     * 帖子评论列表（嵌套树结构）
     * GET /api/community/comment/list/{post_id}
     */
    public function index(Request $request, string $postId)
    {
        $decodedPostId = $this->decodeId($postId);
        if ($decodedPostId === null) {
            return $this->error('帖子不存在', 404);
        }

        $post = Post::where('status', Post::STATUS_NORMAL)->find($decodedPostId);
        if (!$post) {
            return $this->error('帖子不存在', 404);
        }

        // 只获取一级评论，子评论通过 children 关联加载
        $parentId = $request->input('parent_id', '0');
        $perPage  = (int)$request->input('per_page', 20);

        $query = Comment::where('post_id', $post->id)
            ->where('parent_id', $parentId)
            ->with(['user', 'children.user'])
            ->orderBy('created_at', 'asc');

        $paginator = $query->paginate($perPage);

        return $this->paginate($paginator);
    }

    /**
     * 添加评论
     * POST /api/community/comment
     */
    public function store(Request $request)
    {
        $userId   = $request->user_id;
        $postId   = $this->decodeId($request->input('post_id', ''));
        $parentId = $request->input('parent_id', '0');
        $content  = $request->input('content', '');

        if ($postId === null) {
            return $this->error('帖子不存在', 404);
        }

        if (empty(trim($content))) {
            return $this->error('请输入评论内容');
        }

        $post = Post::where('status', Post::STATUS_NORMAL)->find($postId);
        if (!$post) {
            return $this->error('帖子不存在', 404);
        }

        // 如果是回复评论，验证父评论存在且属于同一帖子
        if ($parentId && $parentId !== '0') {
            $parentDecoded = $this->decodeId($parentId);
            if ($parentDecoded === null) {
                return $this->error('父评论不存在', 404);
            }
            $parentId = (string)$parentDecoded;

            $parent = Comment::where('post_id', $postId)
                ->where('id', $parentId)
                ->first();
            if (!$parent) {
                return $this->error('父评论不存在', 404);
            }
        } else {
            $parentId = '0';
        }

        $comment = Comment::create([
            'id'        => Comment::generateId(),
            'post_id'   => $post->id,
            'user_id'   => $userId,
            'parent_id' => $parentId,
            'content'   => $content,
        ]);

        // 递增帖子评论数
        $post->increment('comments_count');

        $comment->load('user');

        return $this->success($comment, '评论成功');
    }

    /**
     * 删除自己的评论
     * DELETE /api/community/comment/{id}
     */
    public function destroy(Request $request, string $id)
    {
        $userId = $request->user_id;

        $decodedId = $this->decodeId($id);
        if ($decodedId === null) {
            return $this->error('评论不存在', 404);
        }

        $comment = Comment::where('user_id', $userId)->find($decodedId);
        if (!$comment) {
            return $this->error('评论不存在或无权操作', 404);
        }

        $postId = $comment->post_id;

        Db::beginTransaction();
        try {
            // 删除该评论及其下的所有子评论
            $deletedCount = Comment::where('parent_id', $comment->id)->delete();
            $comment->delete();

            // 更新帖子评论数
            $remaining = Comment::where('post_id', $postId)->count();
            Post::where('id', $postId)->update(['comments_count' => $remaining]);

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollBack();
            return $this->error('删除失败: ' . $e->getMessage());
        }

        return $this->success(null, '评论已删除');
    }
}
