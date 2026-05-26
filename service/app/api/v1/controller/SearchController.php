<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\model\Product;
use app\model\Service;
use Webman\Http\Request;

/**
 * 搜索控制器
 * 统一搜索服务项目和商品
 */
class SearchController extends BaseController
{
    /**
     * 统一搜索
     * GET /api/search?keyword=xxx&type=service&page=1
     *
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function index(Request $request)
    {
        $keyword = $request->input('keyword', '');
        $type = $request->input('type', 'all');
        $page = (int)($request->input('page', 1));
        $perPage = (int)($request->input('per_page', 15));

        if (empty($keyword)) {
            return $this->error('请输入搜索关键词');
        }

        $results = [];
        $totalService = 0;
        $totalProduct = 0;

        // 按类型决定搜索范围
        $searchService = in_array($type, ['all', 'service']);
        $searchProduct = in_array($type, ['all', 'product']);

        try {
            // ES 搜索
            if ($searchService) {
                $serviceResult = Service::search($keyword)
                    ->where('status', 1)
                    ->paginate($perPage, 'page', $page);

                $serviceItems = $serviceResult->getCollection()->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'type' => 'service',
                        'name' => $item->name,
                        'description' => $item->description,
                        'cover_image' => $item->cover_image,
                        'price' => $item->price,
                        'original_price' => $item->original_price,
                        'sales_volume' => $item->sales_volume,
                        '_score' => $item->_score ?? null,
                    ];
                })->toArray();

                $totalService = $serviceResult->total();
                $results = array_merge($results, $serviceItems);
            }

            if ($searchProduct) {
                $productResult = Product::search($keyword)
                    ->where('status', 1)
                    ->paginate($perPage, 'page', $page);

                $productItems = $productResult->getCollection()->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'type' => 'product',
                        'name' => $item->name,
                        'cover_image' => $item->cover_image,
                        'price' => $item->price,
                        'original_price' => $item->original_price,
                        'stock' => $item->stock,
                        'sales_volume' => $item->sales_volume,
                        '_score' => $item->_score ?? null,
                    ];
                })->toArray();

                $totalProduct = $productResult->total();
                $results = array_merge($results, $productItems);
            }

            // 按相关性分数排序
            usort($results, function ($a, $b) {
                $scoreA = $a['_score'] ?? 0;
                $scoreB = $b['_score'] ?? 0;
                return $scoreB <=> $scoreA;
            });

            $total = $totalService + $totalProduct;

            return $this->success($results, 'success');
        } catch (\Throwable $e) {
            // ES 不可用时回退到数据库 LIKE 查询
            return $this->fallbackSearch($keyword, $type, $page, $perPage);
        }
    }

    /**
     * 数据库回退搜索
     */
    private function fallbackSearch(string $keyword, string $type, int $page, int $perPage): \Webman\Http\Response
    {
        $results = [];

        $searchService = in_array($type, ['all', 'service']);
        $searchProduct = in_array($type, ['all', 'product']);

        if ($searchService) {
            $services = Service::where('status', 1)
                ->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                      ->orWhere('description', 'like', "%{$keyword}%");
                })
                ->orderBy('sales_volume', 'desc')
                ->orderBy('id', 'desc')
                ->get();

            foreach ($services as $item) {
                $results[] = [
                    'id' => $item->id,
                    'type' => 'service',
                    'name' => $item->name,
                    'description' => $item->description,
                    'cover_image' => $item->cover_image,
                    'price' => $item->price,
                    'original_price' => $item->original_price,
                    'sales_volume' => $item->sales_volume,
                ];
            }
        }

        if ($searchProduct) {
            $products = Product::where('status', 1)
                ->where('name', 'like', "%{$keyword}%")
                ->orderBy('sales_volume', 'desc')
                ->orderBy('id', 'desc')
                ->get();

            foreach ($products as $item) {
                $results[] = [
                    'id' => $item->id,
                    'type' => 'product',
                    'name' => $item->name,
                    'cover_image' => $item->cover_image,
                    'price' => $item->price,
                    'original_price' => $item->original_price,
                    'stock' => $item->stock,
                    'sales_volume' => $item->sales_volume,
                ];
            }
        }

        // 分页切片
        $total = count($results);
        $offset = ($page - 1) * $perPage;
        $paged = array_slice($results, $offset, $perPage);

        return json([
            'code' => 200,
            'message' => 'success',
            'data' => $this->encodeIds($paged),
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => max(1, (int)ceil($total / $perPage)),
                'has_more' => ($offset + $perPage) < $total,
            ],
        ]);
    }
}
