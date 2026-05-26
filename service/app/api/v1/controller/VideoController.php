<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\model\VideoPost;
use Illuminate\Support\Facades\Redis;
use Webman\Http\Request;

/**
 * 短视频控制器
 *
 * 处理技师短视频的浏览、点赞等操作
 */
class VideoController extends BaseController
{
    /**
     * 短视频列表（分页）
     * GET /api/video/list?technician_id=&sort=newest|popular&page=1
     */
    public function index(Request $request)
    {
        $technicianId = $request->input('technician_id');
        $sort         = $request->input('sort', 'newest');

        if ($technicianId) {
            $technicianId = $this->decodeId($technicianId);
        }

        $query = VideoPost::published();

        if ($technicianId) {
            $query->byTechnician((string)$technicianId);
        }

        $query->orderBy(
            $sort === 'popular' ? 'likes' : 'created_at',
            'desc'
        );

        $perPage   = (int)$request->input('per_page', 15);
        $paginator = $query->paginate($perPage);

        // 追加当前用户是否已点赞
        $userId = $request->user_id ?? null;
        if ($userId) {
            $paginator->getCollection()->transform(function ($video) use ($userId) {
                $liked = Redis::connection()->sismember("video_likes:{$video->id}", $userId);
                $video->is_liked = (bool)$liked;
                return $video;
            });
        }

        return $this->paginate($paginator);
    }

    /**
     * 短视频详情
     * GET /api/video/detail/{id}
     */
    public function show(Request $request, string $id)
    {
        $decodedId = $this->decodeId($id);
        if ($decodedId === null) {
            return $this->error('视频不存在', 404);
        }

        $video = VideoPost::published()->find($decodedId);
        if (!$video) {
            return $this->error('视频不存在', 404);
        }

        // 递增播放量
        $video->increment('views');

        // 判断当前用户是否已点赞
        $userId = $request->user_id ?? null;
        if ($userId) {
            $liked = Redis::connection()->sismember("video_likes:{$video->id}", $userId);
            $video->is_liked = (bool)$liked;
        }

        return $this->success($video);
    }

    /**
     * 点赞/取消点赞
     * POST /api/video/like/{id}
     */
    public function like(Request $request, string $id)
    {
        $userId = $request->user_id;

        $decodedId = $this->decodeId($id);
        if ($decodedId === null) {
            return $this->error('视频不存在', 404);
        }

        $video = VideoPost::published()->find($decodedId);
        if (!$video) {
            return $this->error('视频不存在', 404);
        }

        $likeKey = "video_likes:{$video->id}";
        $liked   = Redis::connection()->sismember($likeKey, $userId);

        if ($liked) {
            // 取消点赞
            Redis::connection()->srem($likeKey, $userId);
            $video->decrement('likes');
            $isLiked = false;
        } else {
            // 点赞
            Redis::connection()->sadd($likeKey, $userId);
            $video->increment('likes');
            $isLiked = true;
        }

        return $this->success([
            'is_liked' => $isLiked,
            'likes'    => $video->likes,
        ], $isLiked ? '点赞成功' : '已取消点赞');
    }
}
