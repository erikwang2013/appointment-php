<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\model\Product;
use app\model\Service;
use app\model\ServiceCategory;
use app\model\Store;
use Webman\Http\Request;

/**
 * 服务控制器
 * 处理服务分类、服务项目、商品、门店查询
 */
class ServiceController extends BaseController
{
    /**
     * 获取服务分类树
     * GET /api/service/categories
     *
     * @return \Webman\Http\Response
     */
    public function categories()
    {
        $categories = ServiceCategory::where('status', 1)
            ->where('parent_id', 0)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        $categories->load(['children' => function ($query) {
            $query->where('status', 1)->orderBy('sort')->orderBy('id');
        }]);

        return $this->success($categories->toArray());
    }

    /**
     * 服务项目列表（分页，支持关键词搜索）
     * GET /api/service/items
     *
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function items(Request $request)
    {
        $categoryId = $request->input('category_id');
        $page = (int)($request->input('page', 1));
        $perPage = (int)($request->input('per_page', 15));
        $sort = $request->input('sort', 'newest');
        $keyword = $request->input('keyword', '');

        if (!empty($keyword)) {
            return $this->searchServices($keyword, $categoryId, $page, $perPage, $sort);
        }

        $query = Service::where('status', 1);

        if ($categoryId) {
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
     * 商品列表（分页，支持关键词搜索）
     * GET /api/service/products
     *
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function products(Request $request)
    {
        $categoryId = $request->input('category_id');
        $page = (int)($request->input('page', 1));
        $perPage = (int)($request->input('per_page', 15));
        $sort = $request->input('sort', 'newest');
        $keyword = $request->input('keyword', '');

        if (!empty($keyword)) {
            return $this->searchProducts($keyword, $categoryId, $page, $perPage, $sort);
        }

        $query = Product::where('status', 1);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $query = match ($sort) {
            'sales' => $query->orderBy('sales_volume', 'desc'),
            'price' => $query->orderBy('price', 'asc'),
            default => $query->orderBy('sort', 'desc')->orderBy('id', 'desc'),
        };

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return $this->paginate($paginator);
    }

    /**
     * 门店列表
     * GET /api/service/stores
     *
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function stores(Request $request)
    {
        $lat = $request->input('lat');
        $lng = $request->input('lng');

        $query = Store::where('status', 1);

        if ($lat && $lng) {
            // 使用 Haversine 公式按距离排序
            $query->selectRaw(
                '*, (6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) AS distance',
                [$lat, $lng, $lat]
            )->orderBy('distance');
        } else {
            $query->orderBy('id');
        }

        $stores = $query->get();

        return $this->success($stores->toArray());
    }

    /**
     * 服务详情
     * GET /api/service/detail/{id}
     *
     * @param mixed $id
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function detail($id, Request $request)
    {
        $decodedId = $this->decodeId((string)$id);

        if ($decodedId === null) {
            return $this->error('服务不存在');
        }

        $service = Service::where('status', 1)->find($decodedId);

        if (!$service) {
            return $this->error('服务不存在');
        }

        $service->load('category');

        // 获取评价列表（限10条，带用户信息）
        $reviews = $service->reviews()
            ->where('status', 1)
            ->with(['user' => function ($query) {
                $query->select('id', 'avatar', 'nickname');
            }])
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        // 同分类相关服务（排除自身，限6条）
        $relatedServices = Service::where('category_id', $service->category_id)
            ->where('id', '!=', $service->id)
            ->where('status', 1)
            ->orderBy('sales_volume', 'desc')
            ->limit(6)
            ->get();

        $data = $service->toArray();
        $data['reviews'] = $reviews->toArray();
        $data['related_services'] = $relatedServices->toArray();

        return $this->success($data);
    }

    /**
     * 通过 ES 搜索服务
     */
    private function searchServices(string $keyword, ?string $categoryId, int $page, int $perPage, string $sort): \Webman\Http\Response
    {
        try {
            $builder = Service::search($keyword);

            if ($categoryId) {
                $builder->where('category_id', $categoryId);
            }

            $builder->where('status', 1);

            $paginator = $builder->paginate($perPage, 'page', $page);

            // 加载分类名称
            $services = $paginator->getCollection();
            $services->load('category');

            return $this->paginate($paginator);
        } catch (\Throwable $e) {
            // ES 不可用时回退到 Eloquent LIKE 查询
            $query = Service::where('status', 1)
                ->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                      ->orWhere('description', 'like', "%{$keyword}%");
                });

            if ($categoryId) {
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
    }

    /**
     * 通过 ES 搜索商品
     */
    private function searchProducts(string $keyword, ?string $categoryId, int $page, int $perPage, string $sort): \Webman\Http\Response
    {
        try {
            $builder = Product::search($keyword);

            if ($categoryId) {
                $builder->where('category_id', $categoryId);
            }

            $builder->where('status', 1);

            $paginator = $builder->paginate($perPage, 'page', $page);

            return $this->paginate($paginator);
        } catch (\Throwable $e) {
            // ES 不可用时回退到 Eloquent LIKE 查询
            $query = Product::where('status', 1)
                ->where('name', 'like', "%{$keyword}%");

            if ($categoryId) {
                $query->where('category_id', $categoryId);
            }

            $query = match ($sort) {
                'sales' => $query->orderBy('sales_volume', 'desc'),
                'price' => $query->orderBy('price', 'asc'),
                default => $query->orderBy('sort', 'desc')->orderBy('id', 'desc'),
            };

            $paginator = $query->paginate($perPage, ['*'], 'page', $page);

            return $this->paginate($paginator);
        }
    }
}
