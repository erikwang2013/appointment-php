<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\common\MapService;
use app\model\Store;
use support\Redis;
use Webman\Http\Request;

/**
 * LBS 控制器
 * 处理附近门店查询、逆地理编码等位置相关服务
 */
class LbsController extends BaseController
{
    /**
     * 查找附近门店
     * GET /api/lbs/nearby-stores?lat=xxx&lng=xxx&radius=5000
     *
     * 使用 Haversine 公式过滤半径内的门店，并通过 MapService 计算实际驾车距离
     *
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function nearbyStores(Request $request)
    {
        $lat = $request->input('lat');
        $lng = $request->input('lng');
        $radius = (int)($request->input('radius', 5000));

        if (empty($lat) || empty($lng)) {
            return $this->error('请提供经纬度坐标');
        }

        if (!is_numeric($lat) || !is_numeric($lng)) {
            return $this->error('经纬度格式不正确');
        }

        $lat = (float)$lat;
        $lng = (float)$lng;

        // Redis 缓存 5 分钟（读多写少，按坐标+半径哈希分键），管理端写操作按 svc:* 前缀失效
        $cacheKey = 'svc:lbs:stores:' . md5(json_encode([$lat, $lng, $radius]));
        $cached = Redis::get($cacheKey);
        if ($cached !== null && $cached !== false) {
            return json(json_decode($cached, true));
        }

        // bbox 预筛：1° 纬度 ≈ 111km，先按经纬度范围粗筛，避免 Haversine 全表扫描
        $latRange = $radius / 111000;
        $lngRange = $radius / (111000 * cos(deg2rad($lat)));

        // Haversine 公式计算精确距离，过滤半径内的门店
        $stores = Store::selectRaw(
            '*, (6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) * 1000 AS distance',
            [$lat, $lng, $lat]
        )
        ->where('status', 1)
        ->whereBetween('lat', [$lat - $latRange, $lat + $latRange])
        ->whereBetween('lng', [$lng - $lngRange, $lng + $lngRange])
        ->having('distance', '<=', $radius)
        ->orderBy('distance')
        ->limit(20)
        ->get();

        // 使用 MapService 计算前 5 个门店的实际驾车距离
        $storesArray = $stores->toArray();
        try {
            $mapService = new MapService();
            foreach ($storesArray as $i => &$store) {
                // 最多为前 5 个门店计算驾车距离（避免过多 API 调用）
                if ($i < 5) {
                    $drivingResult = $mapService->calculateDistance(
                        $lat, $lng,
                        (float)($store['lat'] ?? 0), (float)($store['lng'] ?? 0)
                    );
                    $store['driving_distance'] = $drivingResult['distance'] ?? $store['distance'];
                    $store['driving_duration'] = $drivingResult['duration'] ?? 0;
                    $store['route_url'] = $mapService->direction(
                        $lat, $lng,
                        (float)($store['lat'] ?? 0), (float)($store['lng'] ?? 0)
                    );
                }
            }
        } catch (\Throwable $e) {
            // MapService 不可用时静默降级，保持 Haversine 距离
        }

        $response = $this->success($storesArray);
        Redis::setex($cacheKey, 300, $response->rawBody());
        return $response;
    }

    /**
     * 逆地理编码
     * GET /api/lbs/geocode?lat=xxx&lng=xxx
     *
     * 使用 MapService 将经纬度转换为结构化地址
     *
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function geocode(Request $request): ?\Webman\Http\Response
    {
        $lat = $request->input('lat');
        $lng = $request->input('lng');

        if (empty($lat) || empty($lng)) {
            return $this->error('请提供经纬度坐标');
        }

        if (!is_numeric($lat) || !is_numeric($lng)) {
            return $this->error('经纬度格式不正确');
        }

        $lat = (float)$lat;
        $lng = (float)$lng;

        $mapService = new MapService();
        $result = $mapService->geocode($lat, $lng);

        if ($result['success']) {
            return $this->success([
                'lat'       => $lat,
                'lng'       => $lng,
                'address'   => $result['address'],
                'province'  => $result['province'],
                'city'      => $result['city'],
                'district'  => $result['district'],
                'formatted' => $result['formatted'],
            ]);
        }

        // 返回降级结果（坐标确认 + 提示信息）
        return $this->success([
            'lat'     => $lat,
            'lng'     => $lng,
            'message' => '逆地理编码服务暂不可用，请检查地图配置',
        ]);
    }
}
