<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\model\Store;
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

        // Haversine 公式计算距离，过滤半径内的门店
        $stores = Store::selectRaw(
            '*, (6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) * 1000 AS distance',
            [$lat, $lng, $lat]
        )
        ->where('status', 1)
        ->having('distance', '<=', $radius)
        ->orderBy('distance')
        ->limit(20)
        ->get();

        return $this->success($stores->toArray());
    }

    /**
     * 逆地理编码（占位接口）
     * GET /api/lbs/geocode?lat=xxx&lng=xxx
     *
     * 实际逆地理编码需要对接第三方地图服务
     * 当前返回坐标确认信息
     *
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function geocode(Request $request)
    {
        $lat = $request->input('lat');
        $lng = $request->input('lng');

        if (empty($lat) || empty($lng)) {
            return $this->error('请提供经纬度坐标');
        }

        if (!is_numeric($lat) || !is_numeric($lng)) {
            return $this->error('经纬度格式不正确');
        }

        return $this->success([
            'lat' => (float)$lat,
            'lng' => (float)$lng,
            'message' => '逆地理编码需要对接第三方地图服务，当前返回坐标确认',
        ]);
    }
}
