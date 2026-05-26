<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\common;

use support\Db;
use support\Log;

/**
 * 地图服务
 *
 * 支持高德地图和腾讯地图，提供逆地理编码、POI 搜索、距离计算、导航路线
 */
class MapService
{
    private string $provider;
    private string $apiKey;

    private const AMAP_GEOCODE_URL      = 'https://restapi.amap.com/v3/geocode/regeo';
    private const AMAP_SEARCH_URL       = 'https://restapi.amap.com/v3/place/text';
    private const AMAP_DIRECTION_URL    = 'https://restapi.amap.com/v3/direction/driving';
    private const TENCENT_GEOCODE_URL   = 'https://apis.map.qq.com/ws/geocoder/v1/';
    private const TENCENT_SEARCH_URL    = 'https://apis.map.qq.com/ws/place/v1/search';
    private const TENCENT_DIRECTION_URL = 'https://apis.map.qq.com/ws/direction/v1/driving/';

    public function __construct()
    {
        $configs = Db::table('erik_system_config')
            ->where('group', 'map_service')
            ->pluck('value', 'key')
            ->toArray();

        $this->provider = $configs['provider'] ?? 'amap';
        $this->apiKey   = $configs['api_key'] ?? '';
    }

    /**
     * 逆地理编码
     *
     * 将经纬度转换为结构化地址
     *
     * @param float $lat 纬度
     * @param float $lng 经度
     * @return array{success: bool, address: string, province: string, city: string, district: string, formatted: array}
     */
    public function geocode(float $lat, float $lng): array
    {
        if (empty($this->apiKey)) {
            return ['success' => false, 'address' => '', 'province' => '', 'city' => '', 'district' => '', 'formatted' => []];
        }

        try {
            return match ($this->provider) {
                'amap'    => $this->geocodeAmap($lat, $lng),
                'tencent' => $this->geocodeTencent($lat, $lng),
                default   => ['success' => false, 'address' => '', 'province' => '', 'city' => '', 'district' => '', 'formatted' => []],
            };
        } catch (\Throwable $e) {
            Log::error('[MapService] geocode error: ' . $e->getMessage());
            return ['success' => false, 'address' => '', 'province' => '', 'city' => '', 'district' => '', 'formatted' => []];
        }
    }

    /**
     * 搜索地点
     *
     * @param string $keyword 搜索关键词
     * @param string $city    城市名称（可选，默认全国）
     * @return array{success: bool, pois: array}
     */
    public function searchPlace(string $keyword, string $city = ''): array
    {
        if (empty($keyword)) {
            return ['success' => false, 'pois' => []];
        }

        try {
            return match ($this->provider) {
                'amap'    => $this->searchPlaceAmap($keyword, $city),
                'tencent' => $this->searchPlaceTencent($keyword, $city),
                default   => ['success' => false, 'pois' => []],
            };
        } catch (\Throwable $e) {
            Log::error('[MapService] searchPlace error: ' . $e->getMessage());
            return ['success' => false, 'pois' => []];
        }
    }

    /**
     * 计算驾车距离
     *
     * @param float $fromLat 起点纬度
     * @param float $fromLng 起点经度
     * @param float $toLat   终点纬度
     * @param float $toLng   终点经度
     * @return array{success: bool, distance: float, duration: int}
     */
    public function calculateDistance(float $fromLat, float $fromLng, float $toLat, float $toLng): array
    {
        try {
            return match ($this->provider) {
                'amap'    => $this->distanceAmap($fromLat, $fromLng, $toLat, $toLng),
                'tencent' => $this->distanceTencent($fromLat, $fromLng, $toLat, $toLng),
                default   => $this->haversineDistance($fromLat, $fromLng, $toLat, $toLng),
            };
        } catch (\Throwable $e) {
            Log::error('[MapService] calculateDistance error: ' . $e->getMessage());
            // 降级为 Haversine 近似距离
            return $this->haversineDistance($fromLat, $fromLng, $toLat, $toLng);
        }
    }

    /**
     * 获取导航路线 URL
     *
     * @param float $fromLat 起点纬度
     * @param float $fromLng 起点经度
     * @param float $toLat   终点纬度
     * @param float $toLng   终点经度
     * @return string 导航路线 URL
     */
    public function direction(float $fromLat, float $fromLng, float $toLat, float $toLng): string
    {
        if ($this->provider === 'tencent') {
            return "https://apis.map.qq.com/uri/v1/routeplan?type=drive&from=&fromcoord={$fromLat},{$fromLng}&to=&tocoord={$toLat},{$toLng}&referer=appointment";
        }

        // 默认高德
        return "https://uri.amap.com/navigation?from={$fromLng},{$fromLat},&to={$toLng},{$toLat},&mode=car";
    }

    // ── 高德地图实现 ──

    /**
     * 高德逆地理编码
     */
    private function geocodeAmap(float $lat, float $lng): array
    {
        $url = self::AMAP_GEOCODE_URL . '?' . http_build_query([
            'key'      => $this->apiKey,
            'location' => "{$lng},{$lat}",
            'output'   => 'json',
        ]);

        $response = $this->httpGet($url);

        if (empty($response)) {
            return $this->emptyGeoResult();
        }

        $data = json_decode($response, true);

        if (!is_array($data) || ($data['status'] ?? '') !== '1') {
            return $this->emptyGeoResult();
        }

        $regeocode   = $data['regeocode'] ?? [];
        $addressComp = $regeocode['addressComponent'] ?? [];

        return [
            'success'   => true,
            'address'   => $regeocode['formatted_address'] ?? '',
            'province'  => $addressComp['province'] ?? '',
            'city'      => $addressComp['city'] ?: ($addressComp['province'] ?? ''),
            'district'  => $addressComp['district'] ?? '',
            'formatted' => $regeocode,
        ];
    }

    /**
     * 高德地点搜索
     */
    private function searchPlaceAmap(string $keyword, string $city): array
    {
        $params = [
            'key'      => $this->apiKey,
            'keywords' => $keyword,
            'output'   => 'json',
        ];

        if (!empty($city)) {
            $params['city'] = $city;
        }

        $url      = self::AMAP_SEARCH_URL . '?' . http_build_query($params);
        $response = $this->httpGet($url);

        if (empty($response)) {
            return ['success' => false, 'pois' => []];
        }

        $data = json_decode($response, true);

        if (!is_array($data) || ($data['status'] ?? '') !== '1') {
            return ['success' => false, 'pois' => []];
        }

        $pois = [];
        foreach ($data['pois'] ?? [] as $poi) {
            $location = explode(',', $poi['location'] ?? '0,0');
            $pois[] = [
                'name'     => $poi['name'] ?? '',
                'address'  => $poi['address'] ?? '',
                'city'     => $poi['cityname'] ?? '',
                'district' => $poi['adname'] ?? '',
                'lng'      => (float) ($location[0] ?? 0),
                'lat'      => (float) ($location[1] ?? 0),
            ];
        }

        return ['success' => true, 'pois' => $pois];
    }

    /**
     * 高德驾车距离
     */
    private function distanceAmap(float $fromLat, float $fromLng, float $toLat, float $toLng): array
    {
        $url = self::AMAP_DIRECTION_URL . '?' . http_build_query([
            'key'      => $this->apiKey,
            'origin'   => "{$fromLng},{$fromLat}",
            'destination' => "{$toLng},{$toLat}",
            'output'   => 'json',
        ]);

        $response = $this->httpGet($url);

        if (empty($response)) {
            return $this->haversineDistance($fromLat, $fromLng, $toLat, $toLng);
        }

        $data = json_decode($response, true);

        if (!is_array($data) || ($data['status'] ?? '') !== '1') {
            return $this->haversineDistance($fromLat, $fromLng, $toLat, $toLng);
        }

        $route = $data['route']['paths'][0] ?? [];
        $distance = (float) ($route['distance'] ?? 0);
        $duration = (int) ($route['duration'] ?? 0);

        return ['success' => true, 'distance' => $distance, 'duration' => $duration];
    }

    // ── 腾讯地图实现 ──

    /**
     * 腾讯逆地理编码
     */
    private function geocodeTencent(float $lat, float $lng): array
    {
        $url = self::TENCENT_GEOCODE_URL . '?' . http_build_query([
            'key'      => $this->apiKey,
            'location' => "{$lat},{$lng}",
        ]);

        $response = $this->httpGet($url);

        if (empty($response)) {
            return $this->emptyGeoResult();
        }

        $data = json_decode($response, true);

        if (!is_array($data) || ($data['status'] ?? -1) !== 0) {
            return $this->emptyGeoResult();
        }

        $result      = $data['result'] ?? [];
        $addressComp = $result['address_component'] ?? [];
        $adInfo      = $result['ad_info'] ?? [];

        return [
            'success'   => true,
            'address'   => $result['address'] ?? '',
            'province'  => $addressComp['province'] ?? '',
            'city'      => $addressComp['city'] ?? $adInfo['city'] ?? '',
            'district'  => $addressComp['district'] ?? $adInfo['district'] ?? '',
            'formatted' => $result,
        ];
    }

    /**
     * 腾讯地点搜索
     */
    private function searchPlaceTencent(string $keyword, string $city): array
    {
        $params = [
            'key'     => $this->apiKey,
            'keyword' => $keyword,
        ];

        if (!empty($city)) {
            $params['boundary'] = 'region(' . urlencode($city) . ',0)';
        }

        $url      = self::TENCENT_SEARCH_URL . '?' . http_build_query($params);
        $response = $this->httpGet($url);

        if (empty($response)) {
            return ['success' => false, 'pois' => []];
        }

        $data = json_decode($response, true);

        if (!is_array($data) || ($data['status'] ?? -1) !== 0) {
            return ['success' => false, 'pois' => []];
        }

        $pois = [];
        foreach ($data['data'] ?? [] as $poi) {
            $pois[] = [
                'name'     => $poi['title'] ?? '',
                'address'  => $poi['address'] ?? '',
                'city'     => $poi['location']['city'] ?? '',
                'district' => $poi['location']['district'] ?? '',
                'lng'      => (float) ($poi['location']['lng'] ?? 0),
                'lat'      => (float) ($poi['location']['lat'] ?? 0),
            ];
        }

        return ['success' => true, 'pois' => $pois];
    }

    /**
     * 腾讯驾车距离
     */
    private function distanceTencent(float $fromLat, float $fromLng, float $toLat, float $toLng): array
    {
        $url = self::TENCENT_DIRECTION_URL . '?' . http_build_query([
            'key'      => $this->apiKey,
            'from'     => "{$fromLat},{$fromLng}",
            'to'       => "{$toLat},{$toLng}",
        ]);

        $response = $this->httpGet($url);

        if (empty($response)) {
            return $this->haversineDistance($fromLat, $fromLng, $toLat, $toLng);
        }

        $data = json_decode($response, true);

        if (!is_array($data) || ($data['status'] ?? -1) !== 0) {
            return $this->haversineDistance($fromLat, $fromLng, $toLat, $toLng);
        }

        $route    = $data['result']['routes'][0] ?? [];
        $distance = (float) ($route['distance'] ?? 0);
        $duration = (int) ($route['duration'] ?? 0);

        return ['success' => true, 'distance' => $distance, 'duration' => $duration];
    }

    // ── Haversine 降级 ──

    /**
     * Haversine 公式计算球面距离（米）
     *
     * API 不可用时降级使用的近似距离
     */
    private function haversineDistance(float $fromLat, float $fromLng, float $toLat, float $toLng): array
    {
        $earthRadius = 6371000; // 地球半径（米）

        $latFrom = deg2rad($fromLat);
        $lngFrom = deg2rad($fromLng);
        $latTo   = deg2rad($toLat);
        $lngTo   = deg2rad($toLng);

        $deltaLat = $latTo - $latFrom;
        $deltaLng = $lngTo - $lngFrom;

        $a = sin($deltaLat / 2) * sin($deltaLat / 2)
           + cos($latFrom) * cos($latTo)
           * sin($deltaLng / 2) * sin($deltaLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = round($earthRadius * $c, 2);

        // 估算驾车时间：平均车速 40km/h
        $duration = (int) round($distance / 11.11);

        return ['success' => true, 'distance' => $distance, 'duration' => $duration];
    }

    // ── 工具方法 ──

    /**
     * 空逆地理编码结果
     */
    private function emptyGeoResult(): array
    {
        return [
            'success'   => false,
            'address'   => '',
            'province'  => '',
            'city'      => '',
            'district'  => '',
            'formatted' => [],
        ];
    }

    /**
     * HTTP GET 请求
     */
    private function httpGet(string $url): string
    {
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);

            $response = curl_exec($ch);
            $error    = curl_error($ch);
            curl_close($ch);

            if ($error) {
                Log::error('[MapService] cURL error: ' . $error);
                return '';
            }

            return is_string($response) ? $response : '';
        } catch (\Throwable $e) {
            Log::error('[MapService] httpGet exception: ' . $e->getMessage());
            return '';
        }
    }
}
