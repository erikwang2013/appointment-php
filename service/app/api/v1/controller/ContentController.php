<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\model\Announcement;
use app\model\Banner;
use app\model\Faq;
use support\Redis;
use Webman\Http\Request;

/**
 * 内容控制器
 * 处理轮播图、公告、FAQ 等 CMS 内容
 */
class ContentController extends BaseController
{
    /**
     * 获取轮播图列表
     * GET /api/content/banners?position=home
     *
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function banners(Request $request)
    {
        $position = $request->input('position', 'home');

        // Redis 缓存 5 分钟（读多写少），管理端写操作按 svc:* 前缀失效
        $cacheKey = 'svc:content:banners:' . $position;
        $cached = Redis::get($cacheKey);
        if ($cached !== null && $cached !== false) {
            return json(json_decode($cached, true));
        }

        $banners = Banner::where('position', $position)
            ->where('status', 1)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        $response = $this->success($banners->toArray());
        Redis::setex($cacheKey, 300, $response->rawBody());
        return $response;
    }

    /**
     * 获取文章列表（公告或FAQ）
     * GET /api/content/articles?type=announcement&page=1
     *
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function articles(Request $request)
    {
        $type = $request->input('type', 'announcement');
        $page = (int)($request->input('page', 1));
        $perPage = (int)($request->input('per_page', 15));

        // Redis 缓存 5 分钟（读多写少，按参数哈希分键）
        $cacheKey = 'svc:content:articles:' . md5(json_encode([$type, $page, $perPage]));
        $cached = Redis::get($cacheKey);
        if ($cached !== null && $cached !== false) {
            return json(json_decode($cached, true));
        }

        if ($type === 'faq') {
            $query = Faq::where('status', 1)
                ->orderBy('sort')
                ->orderBy('id');
        } else {
            $query = Announcement::where('status', 1)
                ->orderBy('sort')
                ->orderBy('published_at', 'desc')
                ->orderBy('id', 'desc');
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $response = $this->paginate($paginator);
        Redis::setex($cacheKey, 300, $response->rawBody());
        return $response;
    }

    /**
     * 获取文章详情
     * GET /api/content/article/{id}
     *
     * @param mixed $id
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function articleDetail($id, Request $request)
    {
        $type = $request->input('type', 'announcement');
        $decodedId = $this->decodeId((string)$id);

        if ($decodedId === null) {
            return $this->error('文章不存在');
        }

        if ($type === 'faq') {
            $article = Faq::where('status', 1)->find($decodedId);
        } else {
            $article = Announcement::where('status', 1)->find($decodedId);
        }

        if (!$article) {
            return $this->error('文章不存在');
        }

        // 公告返回详细信息，FAQ 返回问答内容
        $data = $article->toArray();
        $data['type'] = $type;

        return $this->success($data);
    }
}
