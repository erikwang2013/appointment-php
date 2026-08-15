<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\model\Announcement;
use app\model\Banner;
use app\model\Service;
use app\model\ServiceCategory;
use app\model\Store;
use app\model\TechnicianProfile;
use app\model\TechnicianService;
use support\Redis;
use Webman\Http\Request;

/**
 * 游客模式控制器
 * 未登录只读浏览：首页聚合 / 服务列表与详情 / 门店 / 技师
 * 全部查询只读，不产生任何写操作
 */
class GuestController extends BaseController
{
    private const MAX_PER_PAGE = 50;

    /**
     * 首页聚合（轮播图 / 公告 / 服务分类 / 热门服务）
     * GET /api/guest/home
     */
    public function home()
    {
        // Redis 缓存 5 分钟（读多写少），管理端写操作按 svc:* 前缀失效
        $cacheKey = 'svc:guest:home';
        $cached = Redis::get($cacheKey);
        if ($cached !== null && $cached !== false) {
            return json(json_decode($cached, true));
        }

        $banners = Banner::where('position', 'home')
            ->where('status', 1)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        $announcements = Announcement::where('status', 1)
            ->orderBy('sort')
            ->orderBy('published_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        $categories = ServiceCategory::where('status', 1)
            ->where('parent_id', 0)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();
        $categories->load(['children' => function ($query) {
            $query->where('status', 1)->orderBy('sort')->orderBy('id');
        }]);

        $hotServices = Service::where('status', 1)
            ->orderBy('sales_volume', 'desc')
            ->orderBy('id', 'desc')
            ->limit(8)
            ->get();

        $response = $this->success([
            'banners' => $banners->toArray(),
            'announcements' => $announcements->toArray(),
            'categories' => $categories->toArray(),
            'hot_services' => $hotServices->toArray(),
        ]);
        Redis::setex($cacheKey, 300, $response->rawBody());
        return $response;
    }

    /**
     * 服务列表（分类筛选 / 分页）
     * GET /api/guest/services?category_id={hashid}&page=1&per_page=15&sort=newest|sales|price
     */
    public function services(Request $request)
    {
        $categoryId = $this->decodeId($request->input('category_id'));
        $page = $this->normalizePage($request->input('page', 1));
        $perPage = $this->normalizePerPage($request->input('per_page', 15));
        $sort = $request->input('sort', 'newest');

        $query = Service::where('status', 1);

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        $query = match ($sort) {
            'sales' => $query->orderBy('sales_volume', 'desc'),
            'price' => $query->orderBy('price', 'asc'),
            default => $query->orderBy('sort', 'desc')->orderBy('id', 'desc'),
        };

        $paginator = $query->with('category')->paginate($perPage, ['*'], 'page', $page);

        return $this->paginate($paginator);
    }

    /**
     * 服务详情（hashid 解码，无效 id 与不存在均返回 404，不泄露资源是否存在）
     * GET /api/guest/services/{id}
     */
    public function serviceDetail($id)
    {
        $decodedId = $this->decodeId((string)$id);

        if ($decodedId === null) {
            return $this->error('服务不存在', 404);
        }

        $service = Service::where('status', 1)->find($decodedId);

        if (!$service) {
            return $this->error('服务不存在', 404);
        }

        $service->load('category');

        return $this->success($service->toArray());
    }

    /**
     * 门店列表（含坐标 / 状态）
     * GET /api/guest/stores
     */
    public function stores()
    {
        $stores = Store::where('status', 1)->orderBy('id')->get();

        return $this->success($stores->toArray());
    }

    /**
     * 技师列表（含评分，可选 service_id 筛选）
     * GET /api/guest/technicians?service_id={hashid}&page=1&per_page=15
     */
    public function technicians(Request $request)
    {
        $serviceId = $this->decodeId($request->input('service_id'));
        $page = $this->normalizePage($request->input('page', 1));
        $perPage = $this->normalizePerPage($request->input('per_page', 15));

        $query = TechnicianProfile::where('status', 'approved');

        if ($serviceId !== null) {
            $technicianIds = TechnicianService::where('service_id', $serviceId)
                ->pluck('technician_id')
                ->toArray();
            $query->whereIn('id', $technicianIds);
        }

        $query->orderBy('rating', 'desc')->orderBy('order_count', 'desc');

        $paginator = $query->with('user')->paginate($perPage, ['*'], 'page', $page);

        $items = $paginator->getCollection()->map(function ($profile) {
            return [
                'id' => $profile->id,
                'name' => $profile->user->nickname ?? '',
                'avatar' => $profile->avatar ?? ($profile->user->avatar ?? ''),
                'rating' => $profile->rating,
                'order_count' => $profile->order_count,
            ];
        });

        $paginator->setCollection($items);

        return $this->paginate($paginator);
    }

    private function normalizePage(mixed $value): int
    {
        return max(1, (int)$value);
    }

    private function normalizePerPage(mixed $value): int
    {
        return min(self::MAX_PER_PAGE, max(1, (int)$value));
    }
}
