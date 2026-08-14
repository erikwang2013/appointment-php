<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\model\Promotion;
use app\model\PromotionParticipant;
use support\Db;
use support\Log;
use support\Redis;
use Webman\Http\Request;

/**
 * 促销活动控制器
 * 处理团购、秒杀活动
 */
class PromotionController extends BaseController
{
    /**
     * 活跃促销列表
     * GET /api/promotions
     *
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function index(Request $request)
    {
        $type = $request->input('type', '');
        $now = date('Y-m-d H:i:s');

        // Redis 缓存 5 分钟（读多写少；活动上下架最多延迟 5 分钟可见）
        $cacheKey = 'svc:promotion:index:' . md5($type);
        $cached = Redis::get($cacheKey);
        if ($cached !== null && $cached !== false) {
            return json(json_decode($cached, true));
        }

        $query = Promotion::where('status', 1)
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->with('service')
            ->withCount('participants');

        if ($type && in_array($type, [Promotion::TYPE_GROUP_BUY, Promotion::TYPE_FLASH_SALE])) {
            $query->where('type', $type);
        }

        $promotions = $query->orderBy('start_at', 'desc')->get();

        $response = $this->success($promotions);
        Redis::setex($cacheKey, 300, $response->rawBody());
        return $response;
    }

    /**
     * 促销详情（含当前参与人数）
     * GET /api/promotions/{id}
     *
     * @param mixed $id
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function show($id, Request $request)
    {
        $decodedId = $this->decodeId((string)$id);

        if ($decodedId === null) {
            return $this->error('活动不存在');
        }

        $promotion = Promotion::with('service')
            ->withCount('participants')
            ->find($decodedId);

        if (!$promotion || $promotion->status != 1) {
            return $this->error('活动不存在或已结束');
        }

        $data = $promotion->toArray();
        $data['discounted_price'] = round(
            ($promotion->service->price ?? 0) * (1 - $promotion->discount_percent / 100),
            2
        );

        return $this->success($data);
    }

    /**
     * 参与团购 / 秒杀
     * POST /api/promotions/join/{id}
     *
     * @param mixed $id
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function join($id, Request $request)
    {
        $userId = $request->user_id;
        $decodedId = $this->decodeId((string)$id);

        if ($decodedId === null) {
            return $this->error('活动不存在');
        }

        $promotion = Promotion::withCount('participants')->find($decodedId);

        if (!$promotion || $promotion->status != 1) {
            return $this->error('活动不存在或已结束');
        }

        $now = date('Y-m-d H:i:s');
        if ($now < $promotion->start_at || $now > $promotion->end_at) {
            return $this->error('活动不在有效时间内');
        }

        // 检查是否已参与
        $existing = PromotionParticipant::where('promotion_id', $decodedId)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            return $this->error('您已参与该活动');
        }

        // 检查是否达到最大参与人数
        if ($promotion->max_people > 0 && $promotion->participants_count >= $promotion->max_people) {
            return $this->error('活动已满员');
        }

        $newCount = $promotion->participants_count + 1;

        // 判断是否成团：团购模式下达到最低人数
        if ($promotion->type === Promotion::TYPE_GROUP_BUY && $newCount >= $promotion->min_people) {
            $participantStatus = PromotionParticipant::STATUS_JOINED;
        } else {
            $participantStatus = PromotionParticipant::STATUS_PENDING;
        }

        Db::beginTransaction();
        try {
            $participant = PromotionParticipant::create([
                'id' => PromotionParticipant::generateId(),
                'promotion_id' => $decodedId,
                'user_id' => $userId,
                'status' => $participantStatus,
            ]);

            // 如果达到最低人数，更新所有参与者的状态
            if ($promotion->type === Promotion::TYPE_GROUP_BUY && $newCount >= $promotion->min_people) {
                PromotionParticipant::where('promotion_id', $decodedId)
                    ->where('status', PromotionParticipant::STATUS_PENDING)
                    ->update(['status' => PromotionParticipant::STATUS_JOINED]);
            }

            Db::commit();

            return $this->success([
                'participant' => $participant,
                'current_count' => $newCount,
                'min_people' => $promotion->min_people,
                'is_locked' => $promotion->type === Promotion::TYPE_GROUP_BUY && $newCount >= $promotion->min_people,
            ], '参与成功');
        } catch (\Throwable $e) {
            Db::rollBack();
            // M3: 内部异常详情仅记日志，对外返回通用文案
            Log::error('[PromotionController] join failed: ' . $e->getMessage());
            return $this->error('参与失败，请稍后重试');
        }
    }

    /**
     * 参与人员列表
     * GET /api/promotions/{id}/participants
     *
     * @param mixed $id
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function participants($id, Request $request)
    {
        $decodedId = $this->decodeId((string)$id);

        if ($decodedId === null) {
            return $this->error('活动不存在');
        }

        $participants = PromotionParticipant::where('promotion_id', $decodedId)
            ->with(['user' => function ($query) {
                $query->select('id', 'avatar', 'nickname');
            }])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'user' => $p->user ? [
                        'id' => $p->user->id,
                        'avatar' => $p->user->avatar,
                        'nickname' => $p->user->nickname,
                    ] : null,
                    'status' => $p->status,
                    'created_at' => $p->created_at,
                ];
            });

        $total = PromotionParticipant::where('promotion_id', $decodedId)->count();

        return $this->success([
            'participants' => $participants,
            'total' => $total,
        ]);
    }
}
