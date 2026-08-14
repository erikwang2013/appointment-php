<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\model\OrderReview;
use app\model\Service;
use app\model\TechnicianProfile;
use app\model\TechnicianSchedule;
use app\model\TechnicianService;
use support\Redis;
use Webman\Http\Request;

/**
 * 技师查询控制器
 * 公开接口，无需认证，供用户浏览和筛选技师
 */
class TechnicianController extends BaseController
{
    /**
     * 技师列表（分页，支持筛选和距离排序）
     * GET /api/technician/list
     *
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function list(Request $request)
    {
        $lat = $request->input('lat');
        $lng = $request->input('lng');
        $serviceId = $this->decodeId($request->input('service_id'));
        $gender = $request->input('gender');
        $page = (int)($request->input('page', 1));
        $perPage = (int)($request->input('per_page', 15));

        // Redis 缓存 5 分钟（读多写少，按参数哈希分键），管理端写操作按 svc:* 前缀失效
        $cacheKey = 'svc:technician:list:' . md5(json_encode([$lat, $lng, $serviceId, $gender, $page, $perPage]));
        $cached = Redis::get($cacheKey);
        if ($cached !== null && $cached !== false) {
            return json(json_decode($cached, true));
        }

        $query = TechnicianProfile::where('status', 'approved')
            ->with('user');

        // 按服务筛选：通过 erik_technician_service 关联
        if ($serviceId) {
            $technicianIds = TechnicianService::where('service_id', $serviceId)
                ->pluck('technician_id')
                ->toArray();
            $query->whereIn('id', $technicianIds);
        }

        // 按性别筛选
        if (!is_null($gender) && $gender !== '') {
            $query->where('gender', (int)$gender);
        }

        // 距离排序（Haversine 公式）
        // 优先使用技师档案中存储的 lat/lng 坐标
        if ($lat && $lng) {
            $lat = (float)$lat;
            $lng = (float)$lng;
            $query->selectRaw(
                "erik_technician_profile.*, "
                . "(6371 * acos(cos(radians(?)) * cos(radians(COALESCE(erik_technician_profile.lat, 0))) "
                . "* cos(radians(COALESCE(erik_technician_profile.lng, 0)) - radians(?)) "
                . "+ sin(radians(?)) * sin(radians(COALESCE(erik_technician_profile.lat, 0))))) AS distance",
                [$lat, $lng, $lat]
            )
            ->orderBy('distance');
        } else {
            $query->orderBy('rating', 'desc')
                  ->orderBy('order_count', 'desc');
        }

        $paginator = $query->paginate($perPage, ['erik_technician_profile.*'], 'page', $page);

        // P3: 一次 whereIn 预取本页技师的近7天排班并内存组装最早时段，
        // 替代原先 map() 内每项 getEarliestSlot() 一次查询（每页15人 = 16 次查询 → 2 次）
        $earliestMap = $this->prefetchEarliestSlots($paginator->getCollection()->pluck('id')->all());

        // 格式化输出
        $items = $paginator->getCollection()->map(function ($profile) use ($earliestMap) {
            $earliestSlot = $earliestMap[$profile->id] ?? null;

            return [
                'id' => $profile->id,
                'name' => $profile->user->nickname ?? '',
                'avatar' => $profile->avatar ?? ($profile->user->avatar ?? ''),
                'rating' => $profile->rating,
                'order_count' => $profile->order_count,
                'favorite_count' => $profile->favorite_count,
                'distance' => $profile->distance ?? null,
                'earliest_available' => $earliestSlot,
                'is_available' => !empty($earliestSlot),
            ];
        });

        $paginator->setCollection($items);

        $response = $this->paginate($paginator);
        Redis::setex($cacheKey, 300, $response->rawBody());
        return $response;
    }

    /**
     * 技师详情
     * GET /api/technician/detail/{id}
     *
     * @param mixed $id
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function detail($id, Request $request)
    {
        $decodedId = $this->decodeId((string)$id);

        if ($decodedId === null) {
            return $this->error('技师不存在');
        }

        // Redis 缓存 5 分钟（读多写少），管理端技师/排班写操作按 svc:* 前缀失效
        $cacheKey = 'svc:technician:show:' . $decodedId;
        $cached = Redis::get($cacheKey);
        if ($cached !== null && $cached !== false) {
            return json(json_decode($cached, true));
        }

        $profile = TechnicianProfile::where('status', 'approved')
            ->with('user')
            ->find($decodedId);

        if (!$profile) {
            return $this->error('技师不存在');
        }

        // 技师提供的服务
        $techServices = TechnicianService::where('technician_id', $profile->id)
            ->with('service')
            ->get()
            ->map(function ($ts) {
                if (!$ts->service) return null;
                return [
                    'id' => $ts->service->id,
                    'name' => $ts->service->name,
                    'price' => $ts->service->price,
                    'duration' => $ts->service->duration,
                ];
            })
            ->filter()
            ->values();

        // 最近评价（10条）
        $reviews = OrderReview::where('technician_id', $profile->user_id)
            ->where('status', OrderReview::STATUS_VISIBLE)
            ->with(['user' => function ($query) {
                $query->select('id', 'avatar', 'nickname');
            }])
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'user' => $review->user ? [
                        'id' => $review->user->id,
                        'avatar' => $review->user->avatar,
                        'nickname' => $review->user->nickname,
                    ] : null,
                    'rating' => $review->rating,
                    'content' => $review->content,
                    'images' => $review->images,
                    'created_at' => $review->created_at,
                ];
            });

        // 近7天排班
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+6 days'));
        $schedules = TechnicianSchedule::where('technician_id', $profile->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', 1)
            ->orderBy('date')
            ->get()
            ->map(function ($schedule) {
                return [
                    'date' => $schedule->date,
                    'time_slots' => $schedule->time_slots,
                ];
            });

        $response = $this->success([
            'id' => $profile->id,
            'name' => $profile->user->nickname ?? '',
            'avatar' => $profile->avatar ?? ($profile->user->avatar ?? ''),
            'gender' => $profile->gender,
            'intro' => $profile->intro,
            'rating' => $profile->rating,
            'order_count' => $profile->order_count,
            'favorite_count' => $profile->favorite_count,
            'services' => $techServices,
            'reviews' => $reviews,
            'schedule' => $schedules,
        ]);
        Redis::setex($cacheKey, 300, $response->rawBody());
        return $response;
    }

    /**
     * 技师日程
     * GET /api/technician/schedule/{id}
     *
     * @param mixed $id
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function schedule($id, Request $request)
    {
        $decodedId = $this->decodeId((string)$id);

        if ($decodedId === null) {
            return $this->error('技师不存在');
        }

        $date = $request->input('date', date('Y-m-d'));

        $schedules = TechnicianSchedule::where('technician_id', $decodedId)
            ->where('date', $date)
            ->where('status', 1)
            ->get()
            ->map(function ($schedule) {
                return [
                    'date' => $schedule->date,
                    'time_slots' => array_map(function ($slot) {
                        $slot['status'] = $slot['status'] ?? 'available';
                        return $slot;
                    }, $schedule->time_slots ?? []),
                ];
            });

        return $this->success($schedules);
    }

    /**
     * 获取技师最早可用时段（单技师查询，供详情等场景使用）
     *
     * @param int|string $technicianId
     * @return string|null
     */
    private function getEarliestSlot(int|string $technicianId): ?string
    {
        $today = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+6 days'));

        $schedule = TechnicianSchedule::where('technician_id', $technicianId)
            ->where('status', 1)
            ->whereBetween('date', [$today, $endDate])
            ->orderBy('date')
            ->first();

        return $schedule ? $this->earliestSlotOfSchedule($schedule) : null;
    }

    /**
     * P3: 批量预取多个技师的近7天排班，内存组装每技师最早可用时段。
     * 与原 getEarliestSlot 语义一致：仅取每技师日期最早的排班计算（首个排班无可用时段即视为不可约）。
     *
     * @param array $technicianIds
     * @return array<string, string> technician_id => 最早时段（date + start）
     */
    private function prefetchEarliestSlots(array $technicianIds): array
    {
        $map = [];
        if (empty($technicianIds)) {
            return $map;
        }

        $today = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+6 days'));

        $schedules = TechnicianSchedule::whereIn('technician_id', $technicianIds)
            ->where('status', 1)
            ->whereBetween('date', [$today, $endDate])
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        foreach ($schedules as $schedule) {
            $technicianId = $schedule->technician_id;
            if (isset($map[$technicianId])) {
                continue; // 仅首个（日期最早）排班参与计算，与 first() 语义一致
            }
            $map[$technicianId] = $this->earliestSlotOfSchedule($schedule);
        }

        return $map;
    }

    /**
     * 从单条排班记录计算最早可用时段
     *
     * @param TechnicianSchedule|null $schedule
     * @return string|null
     */
    private function earliestSlotOfSchedule(?TechnicianSchedule $schedule): ?string
    {
        if (!$schedule || empty($schedule->time_slots)) {
            return null;
        }

        $slots = is_array($schedule->time_slots) ? $schedule->time_slots : [];
        foreach ($slots as $slot) {
            $slotStatus = $slot['status'] ?? 'available';
            if ($slotStatus === 'available') {
                return $schedule->date . ' ' . ($slot['start'] ?? '');
            }
        }

        return null;
    }
}
