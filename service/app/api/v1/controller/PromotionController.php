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

        if (!$promotion) {
            return $this->error('活动不存在或已结束');
        }

        // 惰性关闭：拼团到期未满员 → 关闭，避免状态残留
        $now = date('Y-m-d H:i:s');
        if ($promotion->status == 1
            && $promotion->type === Promotion::TYPE_GROUP_BUY
            && $promotion->end_at < $now
            && $promotion->participants_count < $promotion->min_people) {
            $promotion->status = 0;
            $promotion->save();
        }

        if ($promotion->status != 1) {
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

        $now = date('Y-m-d H:i:s');
        $promotion = Promotion::withCount('participants')->find($decodedId);

        if (!$promotion || $promotion->status != 1) {
            return $this->error('活动不存在或已结束');
        }

        // 惰性关闭：拼团到期未满员 → 关闭，已参与用户提示未成团
        if ($promotion->type === Promotion::TYPE_GROUP_BUY
            && $promotion->end_at < $now
            && $promotion->participants_count < $promotion->min_people) {
            $promotion->status = 0;
            $promotion->save();
            return $this->error('拼团已结束，未成团', 422);
        }

        // 已成团锁定：满员拼团拒绝新参与者
        if ($promotion->type === Promotion::TYPE_GROUP_BUY
            && $promotion->participants_count >= $promotion->min_people) {
            return $this->error('已成团，该活动已锁定', 422);
        }

        if ($now < $promotion->start_at || $now > $promotion->end_at) {
            return $this->error('活动不在有效时间内');
        }

        // 秒杀库存上限（无锁预检，并发下由下方 NX 锁内复验兜底）
        if ($promotion->type === Promotion::TYPE_FLASH_SALE
            && $promotion->max_people > 0
            && $promotion->participants_count >= $promotion->max_people) {
            return $this->error('已抢光', 422);
        }

        // 幂等：同一用户重复参与
        $existing = PromotionParticipant::where('promotion_id', $decodedId)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            return $this->error('您已参与该活动', 422);
        }

        // 并发防护：活动级 Redis NX 锁（30s TTL 兜底），锁内复验库存/幂等/成团，防秒杀超卖
        $lockKey = "promotion_join:{$decodedId}";
        $token = uniqid((string)$userId, true);
        if (!Redis::connection()->set($lockKey, $token, 'EX', 30, 'NX')) {
            return $this->error('参与人数过多，请稍后重试');
        }

        try {
            $freshCount = PromotionParticipant::where('promotion_id', $decodedId)->count();

            if ($promotion->type === Promotion::TYPE_FLASH_SALE
                && $promotion->max_people > 0
                && $freshCount >= $promotion->max_people) {
                return $this->error('已抢光', 422);
            }

            $existing = PromotionParticipant::where('promotion_id', $decodedId)
                ->where('user_id', $userId)
                ->first();

            if ($existing) {
                return $this->error('您已参与该活动', 422);
            }

            if ($promotion->type === Promotion::TYPE_GROUP_BUY
                && $freshCount >= $promotion->min_people) {
                return $this->error('已成团，该活动已锁定', 422);
            }

            $newCount = $freshCount + 1;
            $isLocked = $promotion->type === Promotion::TYPE_GROUP_BUY && $newCount >= $promotion->min_people;
            $participantStatus = $isLocked
                ? PromotionParticipant::STATUS_JOINED
                : PromotionParticipant::STATUS_PENDING;

            Db::beginTransaction();
            try {
                $participant = PromotionParticipant::create([
                    'id' => PromotionParticipant::generateId(),
                    'promotion_id' => $decodedId,
                    'user_id' => $userId,
                    'status' => $participantStatus,
                ]);

                // 达到最低人数：将全部 pending 参与者提升为 joined
                if ($isLocked) {
                    PromotionParticipant::where('promotion_id', $decodedId)
                        ->where('status', PromotionParticipant::STATUS_PENDING)
                        ->update(['status' => PromotionParticipant::STATUS_JOINED]);
                }

                Db::commit();
            } catch (\Throwable $e) {
                Db::rollBack();
                // M3: 内部异常详情仅记日志，对外返回通用文案
                Log::error('[PromotionController] join failed: ' . $e->getMessage());
                return $this->error('参与失败，请稍后重试');
            }

            $originalPrice = (float) ($promotion->service->price ?? 0);

            return $this->success([
                'participant' => $participant,
                'current_count' => $newCount,
                'min_people' => $promotion->min_people,
                'max_people' => $promotion->max_people,
                'is_locked' => $isLocked,
                'is_full' => $promotion->type === Promotion::TYPE_FLASH_SALE
                    && $promotion->max_people > 0
                    && $newCount >= $promotion->max_people,
                // 拼团折扣信息（下单时传 promotion_id 以拼团价结算）
                'discount_percent' => $promotion->discount_percent,
                'original_price' => $originalPrice,
                'group_price' => round($originalPrice * $promotion->discount_percent / 100, 2),
            ], '参与成功');
        } finally {
            // 仅持有者释放（token 校验），防止误删他人锁
            if ((string) Redis::get($lockKey) === $token) {
                Redis::del($lockKey);
            }
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
