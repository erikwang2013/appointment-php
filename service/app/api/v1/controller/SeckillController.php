<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\model\Order;
use app\model\SeckillActivity;
use app\order\v1\controller\OrderController;
use support\Log;
use support\Redis;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 秒杀控制器（用户端）
 *
 * GET  /api/seckill          进行中活动列表（含已售量）
 * GET  /api/seckill/{id}     活动详情（含剩余库存）
 * POST /api/seckill/{id}/buy 抢购：Redis NX 锁 + client_token 幂等（SETNX 24h）、
 *                            未开始/已结束/售罄 422；
 *                            下单复用 OrderController::store 秒杀通道——库存由 store 事务内
 *                            行锁（lockForUpdate）扣减，失败整体回滚，无需本控制器回补。
 */
class SeckillController extends BaseController
{
    private const BUY_LOCK_TTL = 30;   // 活动级 Redis NX 锁 TTL（秒），兜底释放
    private const TOKEN_TTL = 86400;   // client_token 幂等键 TTL（24 小时）

    /**
     * 进行中秒杀活动列表（start_at<=now<=end_at 且 status=1），含已售量
     */
    public function index(Request $request): Response
    {
        $now = date('Y-m-d H:i:s');

        $activities = SeckillActivity::where('status', 1)
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->orderBy('start_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($activities->isEmpty()) {
            return $this->success(['list' => []]);
        }

        $ids = $activities->pluck('id')->all();
        // 已售量仅统计有效订单（支付成功/待服务/服务中/已完成）；库存在下单时即扣减（store 事务内行锁），
        // 未支付订单不计入已售量，故 sold 与 remaining_stock 口径不同属预期
        $sold = Order::whereIn('seckill_id', $ids)
            ->whereNotNull('seckill_id')
            ->whereIn('status', [Order::STATUS_PAID, Order::STATUS_CONFIRMED, Order::STATUS_SERVING, Order::STATUS_COMPLETED])
            ->groupBy('seckill_id')
            ->selectRaw('seckill_id, COUNT(*) AS sold')
            ->pluck('sold', 'seckill_id');

        $list = $activities->map(function (SeckillActivity $a) use ($sold) {
            $data = $a->toArray();
            $data['sold'] = (int)($sold[$a->id] ?? 0);
            $data['remaining_stock'] = max(0, (int)$a->stock);
            return $data;
        });

        return $this->success(['list' => $list]);
    }

    /**
     * 秒杀活动详情（含剩余库存与状态标记）
     */
    public function show(Request $request, string $id): Response
    {
        $decodedId = $this->decodeId($id);
        if ($decodedId === null) {
            return $this->error('秒杀活动不存在', 422);
        }

        $activity = SeckillActivity::find($decodedId);
        if (!$activity || $activity->status != 1) {
            return $this->error('秒杀活动不存在或已结束', 422);
        }

        $now = date('Y-m-d H:i:s');
        $data = $activity->toArray();
        $data['sold'] = Order::where('seckill_id', $activity->id)
            ->whereIn('status', [Order::STATUS_PAID, Order::STATUS_CONFIRMED, Order::STATUS_SERVING, Order::STATUS_COMPLETED])
            ->count();
        $data['remaining_stock'] = max(0, (int)$activity->stock);
        $data['state'] = $now < $activity->start_at ? 'not_started'
            : ($now > $activity->end_at ? 'ended' : 'ongoing');

        return $this->success($data);
    }

    /**
     * 抢购下单：锁 + 幂等 + 行锁扣库存，复用订单创建通道
     */
    public function buy(Request $request, string $id): Response
    {
        $userId = $request->user_id;
        $decodedId = $this->decodeId($id);
        if ($decodedId === null) {
            return $this->error('秒杀活动不存在', 422);
        }

        $clientToken = (string)$request->input('client_token', '');
        if (strlen($clientToken) < 8 || strlen($clientToken) > 64) {
            return $this->error('client_token 无效', 422);
        }

        // 下单入参预检（items/技师/时间），避免扣库存后才因缺参失败
        $items = $request->input('items', []);
        if (empty($items) || !is_array($items)) {
            return $this->error('请选择服务', 422);
        }
        $technicianId = $request->input('technician_id');
        $serviceTime = $request->input('service_time');
        if (!$technicianId || !$serviceTime) {
            return $this->error('预约订单需要选择技师和服务时间', 422);
        }

        $now = date('Y-m-d H:i:s');
        $activity = SeckillActivity::find($decodedId);
        if (!$activity || $activity->status != 1) {
            return $this->error('秒杀活动不存在或已结束', 422);
        }
        if ($now < $activity->start_at) {
            return $this->error('秒杀未开始', 422);
        }
        if ($now > $activity->end_at) {
            return $this->error('秒杀已结束', 422);
        }
        if ((int)$activity->stock <= 0) {
            return $this->error('已售罄', 422);
        }

        // client_token 幂等：同用户同活动同 token 24h 内只允许提交一次
        $tokenKey = "seckill_token:{$decodedId}:{$userId}:" . md5($clientToken);
        if (!Redis::connection()->set($tokenKey, '1', 'EX', self::TOKEN_TTL, 'NX')) {
            return $this->error('请勿重复提交', 422);
        }

        // 活动级 Redis NX 锁（30s TTL 兜底），锁内行锁复验库存，防并发超卖
        $lockKey = "seckill_buy:{$decodedId}";
        $lockToken = uniqid((string)$userId, true);
        if (!Redis::connection()->set($lockKey, $lockToken, 'EX', self::BUY_LOCK_TTL, 'NX')) {
            return $this->error('抢购人数过多，请稍后重试', 422);
        }

        try {
            // 复用订单创建通道：注入秒杀参数，走 OrderController::store 秒杀分支结算。
            // 库存扣减统一在 store 事务内行锁完成（失败整体回滚，任何退出路径都不丢库存）
            $request->setPost([
                'items'         => $items,
                'order_type'    => 'appointment',
                'technician_id' => $technicianId,
                'store_id'      => $request->input('store_id'),
                'service_time'  => $serviceTime,
                'remark'        => (string)$request->input('remark', ''),
                'seckill_id'    => $id,
            ]);

            $orderResponse = (new OrderController())->store($request);
            $orderBody = json_decode($orderResponse->rawBody(), true) ?: [];
            if (($orderBody['code'] ?? -1) !== 0) {
                // 下单失败（store 已回滚，库存未扣）：清除幂等键，允许更换 token 重试
                Redis::del($tokenKey);
            }
            return $orderResponse;
        } catch (\Throwable $e) {
            // store 异常同样已回滚，仅需释放幂等键
            Redis::del($tokenKey);
            Log::error('[SeckillController] buy failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->error('抢购失败，请稍后重试');
        } finally {
            // 仅持有者释放，防止误删他人锁
            if ((string) Redis::get($lockKey) === $lockToken) {
                Redis::del($lockKey);
            }
        }
    }
}
