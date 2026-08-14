<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\technician\v1\controller;

use app\common\BaseController;
use app\common\ReferralRewardService;
use app\common\TierRatingService;
use app\model\Notification;
use app\model\Order;
use support\Db;
use support\Log;
use Webman\Http\Request;

/**
 * 技师工作台控制器
 *
 * 技师端接单流转：今日任务 → 开始服务(confirmed→serving) → 完成服务(serving→completed)，
 * 以及我的核销/完成记录分页查询。
 *
 * 与核销(verify-by-code)的关系：扫码核销已把订单从 paid/confirmed 推进到 serving，
 * 本控制器负责 serving 之后的完成闭环与任务看板展示。
 */
class WorkController extends BaseController
{
    /**
     * 今日任务列表
     * GET /api/technician/work/today
     *
     * 我的订单中：待服务(confirmed) + 服务中(serving)，
     * 限定服务时间为今日（或未填服务时间）的任务，关联服务名/用户/时间。
     */
    public function today(Request $request)
    {
        $technicianId = $request->technician_id;

        $firstItem = Db::table('erik_order_item')
            ->select('order_id')
            ->selectRaw('MIN(id) AS first_item_id')
            ->whereIn('order_id', function ($q) use ($technicianId) {
                $q->select('id')
                    ->from('erik_order')
                    ->where('technician_id', $technicianId);
            })
            ->groupBy('order_id');

        $tasks = Db::table('erik_order')
            ->select([
                'erik_order.id',
                'erik_order.order_no',
                'erik_order.user_id',
                'item.target_id as service_id',
                'item.name as service_name',
                'item.price',
                'erik_order.status',
                'erik_order.service_time',
                'erik_order.service_start_at',
                'erik_order.service_end_at',
                'erik_user.nickname',
                'erik_user.avatar',
            ])
            ->leftJoin('erik_user', 'erik_order.user_id', '=', 'erik_user.id')
            ->leftJoinSub($firstItem, 'first_item', function ($join) {
                $join->on('erik_order.id', '=', 'first_item.order_id');
            })
            ->leftJoin('erik_order_item as item', 'item.id', '=', 'first_item.first_item_id')
            ->where('erik_order.technician_id', $technicianId)
            ->whereIn('erik_order.status', [Order::STATUS_CONFIRMED, Order::STATUS_SERVING])
            ->where(function ($q) {
                // 今日任务：服务时间在今天（或未填写）的才上工作台
                $q->whereNull('erik_order.service_time')
                    ->orWhereDate('erik_order.service_time', date('Y-m-d'));
            })
            ->orderBy('erik_order.service_time', 'asc')
            ->get();

        return $this->success($tasks);
    }

    /**
     * 我的核销/完成记录（分页）
     * GET /api/technician/work/records
     *
     * 我的订单中已进入服务闭环（serving/completed）的记录，
     * 按服务结束时间倒序（未结束的进行中排最后）。
     */
    public function records(Request $request)
    {
        $technicianId = $request->technician_id;
        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 15);

        $firstItem = Db::table('erik_order_item')
            ->select('order_id')
            ->selectRaw('MIN(id) AS first_item_id')
            ->whereIn('order_id', function ($q) use ($technicianId) {
                $q->select('id')
                    ->from('erik_order')
                    ->where('technician_id', $technicianId);
            })
            ->groupBy('order_id');

        $records = Db::table('erik_order')
            ->select([
                'erik_order.id',
                'erik_order.order_no',
                'erik_order.user_id',
                'item.target_id as service_id',
                'item.name as service_name',
                'item.price',
                'erik_order.status',
                'erik_order.service_time',
                'erik_order.service_start_at',
                'erik_order.service_end_at',
                'erik_user.nickname',
                'erik_user.avatar',
            ])
            ->leftJoin('erik_user', 'erik_order.user_id', '=', 'erik_user.id')
            ->leftJoinSub($firstItem, 'first_item', function ($join) {
                $join->on('erik_order.id', '=', 'first_item.order_id');
            })
            ->leftJoin('erik_order_item as item', 'item.id', '=', 'first_item.first_item_id')
            ->where('erik_order.technician_id', $technicianId)
            ->whereIn('erik_order.status', [Order::STATUS_SERVING, Order::STATUS_COMPLETED])
            ->orderByRaw('erik_order.service_end_at IS NULL, erik_order.service_end_at DESC, erik_order.updated_at DESC')
            ->paginate($perPage, ['*'], 'page', $page);

        return $this->paginate($records);
    }

    /**
     * 开始服务
     * POST /api/technician/work/{id}/start
     *
     * 状态机：confirmed → serving（记录 service_start_at）。
     * 守卫：订单属于当前技师(403)；状态非 confirmed 报 422；
     * 幂等：已是 serving 直接返回成功（防重复点击/并发）。
     * 并发：行锁 lockForUpdate + 锁内状态复查。
     */
    public function start(Request $request, ?string $id)
    {
        $orderId = $this->decodeId($id);
        if ($orderId === null) {
            return $this->error('无效的订单ID', 422);
        }

        $order = Order::find($orderId);
        if (!$order) {
            return $this->error('订单不存在', 404);
        }
        if ((string)$order->technician_id !== (string)$request->technician_id) {
            return $this->error('无权操作该订单', 403);
        }
        // 幂等（锁外快速路径）：已开始服务直接返回成功
        if ($order->status === Order::STATUS_SERVING) {
            return $this->success($order, '服务已在进行中');
        }
        if ($order->status !== Order::STATUS_CONFIRMED) {
            return $this->error('当前订单状态不可开始服务', 422);
        }

        try {
            Db::beginTransaction();
            // 行锁 + 锁内状态守卫（防并发重复开始/竞态）
            $locked = Order::where('id', $orderId)->lockForUpdate()->first();
            if ($locked->status !== Order::STATUS_CONFIRMED) {
                Db::rollBack();
                return $this->error('当前订单状态不可开始服务', 422);
            }
            $locked->status = Order::STATUS_SERVING;
            $locked->service_start_at = now();
            $locked->save();
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollBack();
            Log::error('[WorkController] start failed: ' . $e->getMessage());
            return $this->error('操作失败，请稍后重试');
        }

        $this->notifyUser($order, '服务已开始', '您的订单 ' . $order->order_no . ' 已开始服务，技师正在为您服务。');

        return $this->success($order, '开始服务成功');
    }

    /**
     * 完成服务
     * POST /api/technician/work/{id}/complete
     *
     * 状态机：serving → completed（记录 service_end_at）。
     * 守卫：订单属于当前技师(403)；状态非 serving 报 422（completed 幂等返回成功）。
     * 并发：行锁 lockForUpdate + 锁内状态复查。
     */
    public function complete(Request $request, ?string $id)
    {
        $orderId = $this->decodeId($id);
        if ($orderId === null) {
            return $this->error('无效的订单ID', 422);
        }

        $order = Order::find($orderId);
        if (!$order) {
            return $this->error('订单不存在', 404);
        }
        if ((string)$order->technician_id !== (string)$request->technician_id) {
            return $this->error('无权操作该订单', 403);
        }
        // 幂等（锁外快速路径）：已完成直接返回成功
        if ($order->status === Order::STATUS_COMPLETED) {
            return $this->success($order, '服务已完成');
        }
        if ($order->status !== Order::STATUS_SERVING) {
            return $this->error('当前订单状态不可完成服务', 422);
        }

        try {
            Db::beginTransaction();
            // 行锁 + 锁内状态守卫（防并发重复完成/竞态）
            $locked = Order::where('id', $orderId)->lockForUpdate()->first();
            if ($locked->status !== Order::STATUS_SERVING) {
                Db::rollBack();
                return $this->error('当前订单状态不可完成服务', 422);
            }
            $locked->status = Order::STATUS_COMPLETED;
            $locked->service_end_at = now();
            $locked->save();
            // 分销返佣：首单完成发放（同事务、幂等，失败整体回滚可重试）
            ReferralRewardService::handleOrderCompleted($locked);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollBack();
            Log::error('[WorkController] complete failed: ' . $e->getMessage());
            return $this->error('操作失败，请稍后重试');
        }

        $this->notifyUser($order, '服务已完成', '您的订单 ' . $order->order_no . ' 已完成，感谢您的光临，欢迎评价本次服务。');

        // 完成订单后懒判定技师等级（幂等：等级未变化不写日志不发通知）
        try {
            TierRatingService::evaluate((string) $order->technician_id);
        } catch (\Throwable $e) {
            Log::warning('[WorkController] tier evaluate failed: ' . $e->getMessage());
        }

        return $this->success($order, '完成服务成功');
    }

    /**
     * 站内消息通知用户（type='order'，非阻塞：失败仅记日志，不影响主流程）
     */
    private function notifyUser(Order $order, string $title, string $content): void
    {
        try {
            Notification::create([
                'id'       => Notification::generateId(),
                'user_id'  => $order->user_id,
                'type'     => 'order',
                'title'    => $title,
                'content'  => $content,
                'order_id' => $order->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[WorkController] notifyUser failed: ' . $e->getMessage());
        }
    }
}
