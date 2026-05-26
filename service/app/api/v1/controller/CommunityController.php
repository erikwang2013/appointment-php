<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\model\Comment;
use app\model\Notification;
use app\model\Post;
use Illuminate\Support\Facades\Redis;
use support\Db;
use Webman\Http\Request;

/**
 * 社区圈子控制器
 *
 * 处理帖子列表、详情、创建、点赞、评论等操作
 */
class CommunityController extends BaseController
{
    /**
     * 帖子列表
     * GET /api/community?sort=newest|hot&page=1
     */
    public function index(Request $request)
    {
        $sort = $request->input('sort', 'newest');

        $query = Post::where('status', Post::STATUS_NORMAL)
            ->with('user');

        // 置顶优先
        $query->orderBy('is_pinned', 'desc');

        // 排序
        if ($sort === 'hot') {
            $query->orderBy('comments_count', 'desc')
                  ->orderBy('likes', 'desc');
        }
        $query->orderBy('created_at', 'desc');

        $perPage   = (int)$request->input('per_page', 15);
        $paginator = $query->paginate($perPage);

        // 追加当前用户是否已点赞
        $userId = $request->user_id ?? null;
        if ($userId) {
            $paginator->getCollection()->transform(function ($post) use ($userId) {
                $liked = Redis::connection()->sismember("post_likes:{$post->id}", $userId);
                $post->is_liked = (bool)$liked;
                return $post;
            });
        }

        return $this->paginate($paginator);
    }

    /**
     * 帖子详情（含评论树）
     * GET /api/community/detail/{id}
     */
    public function show(Request $request, string $id)
    {
        $decodedId = $this->decodeId($id);
        if ($decodedId === null) {
            return $this->error('帖子不存在', 404);
        }

        $post = Post::where('status', Post::STATUS_NORMAL)
            ->with('user')
            ->find($decodedId);

        if (!$post) {
            return $this->error('帖子不存在', 404);
        }

        // 加载评论树（父评论 + 子评论）
        $comments = Comment::where('post_id', $post->id)
            ->where('parent_id', '0')
            ->with(['user', 'children.user'])
            ->orderBy('created_at', 'asc')
            ->get();

        $post->comments_tree = $comments;

        // 判断当前用户是否已点赞
        $userId = $request->user_id ?? null;
        if ($userId) {
            $liked = Redis::connection()->sismember("post_likes:{$post->id}", $userId);
            $post->is_liked = (bool)$liked;
        }

        return $this->success($post);
    }

    /**
     * 创建帖子
     * POST /api/community
     */
    public function store(Request $request)
    {
        $userId = $request->user_id;

        $title   = $request->input('title', '');
        $content = $request->input('content', '');
        $images  = $request->input('images', []);

        if (empty($content) && empty($images)) {
            return $this->error('请填写帖子内容或上传图片');
        }

        $post = Post::create([
            'id'      => Post::generateId(),
            'user_id' => $userId,
            'title'   => $title,
            'content' => $content,
            'images'  => $images,
            'status'  => Post::STATUS_NORMAL,
        ]);

        $post->load('user');

        return $this->success($post, '发布成功');
    }

    /**
     * 点赞/取消点赞帖子
     * POST /api/community/like/{id}
     */
    public function like(Request $request, string $id)
    {
        $userId = $request->user_id;

        $decodedId = $this->decodeId($id);
        if ($decodedId === null) {
            return $this->error('帖子不存在', 404);
        }

        $post = Post::where('status', Post::STATUS_NORMAL)->find($decodedId);
        if (!$post) {
            return $this->error('帖子不存在', 404);
        }

        $likeKey = "post_likes:{$post->id}";
        $liked   = Redis::connection()->sismember($likeKey, $userId);

        if ($liked) {
            Redis::connection()->srem($likeKey, $userId);
            $post->decrement('likes');
            $isLiked = false;
        } else {
            Redis::connection()->sadd($likeKey, $userId);
            $post->increment('likes');
            $isLiked = true;

            // 点赞通知（不通知自己）
            if ($post->user_id !== $userId) {
                $this->createNotification(
                    $post->user_id,
                    'community',
                    '赞了你的帖子',
                    '有人赞了你的帖子',
                    $post->id
                );
            }
        }

        return $this->success([
            'is_liked' => $isLiked,
            'likes'    => $post->likes,
        ], $isLiked ? '点赞成功' : '已取消点赞');
    }

    /**
     * 发表评论
     * POST /api/community/comment
     */
    public function comment(Request $request)
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

        // 评论通知（不通知自己）
        if ($post->user_id !== $userId) {
            $this->createNotification(
                $post->user_id,
                'community',
                '评论了你的帖子',
                mb_substr($content, 0, 100),
                $post->id
            );
        }

        // 如果是回复，通知被回复的人
        if ($parentId !== '0') {
            $parentComment = Comment::find($parentId);
            if ($parentComment && $parentComment->user_id !== $userId) {
                $this->createNotification(
                    $parentComment->user_id,
                    'community',
                    '回复了你的评论',
                    mb_substr($content, 0, 100),
                    $post->id
                );
            }
        }

        $comment->load('user');

        return $this->success($comment, '评论成功');
    }

    /**
     * 我的帖子
     * GET /api/community/my-posts
     */
    public function myPosts(Request $request)
    {
        $userId = $request->user_id;

        $perPage   = (int)$request->input('per_page', 15);
        $paginator = Post::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->paginate($paginator);
    }

    /**
     * 创建通知
     */
    private function createNotification(string $userId, string $type, string $title, string $content, string $relatedId): void
    {
        try {
            Notification::create([
                'id'       => Notification::generateId(),
                'user_id'  => $userId,
                'type'     => $type,
                'title'    => $title,
                'content'  => $content,
                'order_id' => $relatedId,
            ]);
        } catch (\Throwable $e) {
            // 通知创建失败不影响主流程
        }
    }
}
