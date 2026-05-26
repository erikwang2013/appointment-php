<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\user\v1\controller;

use app\common\BaseController;
use app\model\TechnicianProfile;
use app\model\Service;
use app\model\UserFavorite;
use Illuminate\Support\Facades\Redis;
use Webman\Http\Request;

/**
 * 收藏提醒控制器
 * 管理提醒偏好设置、轮询收藏技师上线/服务促销
 */
class FavoriteAlertController extends BaseController
{
    /**
     * 获取/设置提醒偏好
     * GET  /api/user/alert/preferences  → 获取
     * POST /api/user/alert/preferences  → 设置
     */
    public function preferences(Request $request)
    {
        $userId = $request->user_id;
        $key = "user_alert_prefs:{$userId}";

        if ($request->method() === 'GET') {
            $raw = Redis::connection()->get($key);
            $prefs = $raw ? json_decode($raw, true) : [
                'technician_online' => true,
                'service_promo'     => true,
            ];

            return $this->success($prefs);
        }

        // POST: 更新偏好
        $prefs = [
            'technician_online' => (bool)$request->input('technician_online', true),
            'service_promo'     => (bool)$request->input('service_promo', true),
        ];

        Redis::connection()->set($key, json_encode($prefs, JSON_UNESCAPED_UNICODE));

        return $this->success($prefs, '偏好设置已保存');
    }

    /**
     * 客户端轮询检查提醒
     * GET /api/user/alert/check
     *
     * 返回收藏技师上线/服务促销变化
     */
    public function check(Request $request)
    {
        $userId = $request->user_id;

        $raw = Redis::connection()->get("user_alert_prefs:{$userId}");
        $prefs = $raw ? json_decode($raw, true) : ['technician_online' => true, 'service_promo' => true];

        $alerts = [];

        // ── 技师上线检查 ──
        if (!empty($prefs['technician_online'])) {
            $favoriteTechIds = UserFavorite::where('user_id', $userId)
                ->where('target_type', 'technician')
                ->pluck('target_id')
                ->toArray();

            if (!empty($favoriteTechIds)) {
                // 获取缓存的技师在线状态
                $cachedOnline    = Redis::connection()->get("tech_online_cache") ?: '{}';
                $cachedOnlineArr = json_decode($cachedOnline, true);

                // 获取当前在线技师
                $onlineTechs = TechnicianProfile::whereIn('id', $favoriteTechIds)
                    ->where('status', 'approved')
                    ->pluck('id')
                    ->toArray();

                // 对比缓存，找出新上线的
                $newlyOnline = [];
                foreach ($onlineTechs as $tid) {
                    if (!isset($cachedOnlineArr[$tid]) || !$cachedOnlineArr[$tid]) {
                        $profile = TechnicianProfile::find($tid);
                        if ($profile) {
                            $newlyOnline[] = [
                                'technician_id' => $tid,
                                'name'          => $profile->real_name ?? '',
                                'avatar'        => $profile->avatar ?? '',
                            ];
                        }
                    }
                }

                if (!empty($newlyOnline)) {
                    $alerts['technicians_online'] = $newlyOnline;
                }
            }
        }

        // ── 服务促销检查 ──
        if (!empty($prefs['service_promo'])) {
            $favoriteServiceIds = UserFavorite::where('user_id', $userId)
                ->where('target_type', 'service')
                ->pluck('target_id')
                ->toArray();

            if (!empty($favoriteServiceIds)) {
                $cachedPromo    = Redis::connection()->get("service_promo_cache") ?: '{}';
                $cachedPromoArr = json_decode($cachedPromo, true);

                // 找出有促销（original_price > price）的服务
                $promoServices = Service::whereIn('id', $favoriteServiceIds)
                    ->where('status', 1)
                    ->whereRaw('original_price > price')
                    ->get();

                $newlyPromo = [];
                foreach ($promoServices as $svc) {
                    $currentHash = md5($svc->id . $svc->price . $svc->original_price);
                    if (!isset($cachedPromoArr[$svc->id]) || $cachedPromoArr[$svc->id] !== $currentHash) {
                        $newlyPromo[] = [
                            'service_id'      => $svc->id,
                            'name'            => $svc->name,
                            'price'           => $svc->price,
                            'original_price'  => $svc->original_price,
                            'cover_image'     => $svc->cover_image ?? '',
                        ];
                    }
                }

                if (!empty($newlyPromo)) {
                    $alerts['services_promo'] = $newlyPromo;
                }
            }
        }

        return $this->success([
            'alerts'  => $alerts,
            'has_new' => !empty($alerts),
        ]);
    }
}
