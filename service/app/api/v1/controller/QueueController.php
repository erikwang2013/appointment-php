<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\model\QueueNumber;
use app\model\Store;
use support\Log;
use Webman\Http\Request;

/**
 * 门店排队叫号控制器
 *
 * 提供用户取号、查看状态、门店大屏展示等功能
 */
class QueueController extends BaseController
{
    /**
     * 取号
     *
     * POST /api/queue/take
     * 用户到店后取号排队
     *
     * @param  Request $request
     * @return \Webman\Http\Response
     */
    public function take(Request $request)
    {
        $userId  = $request->user_id;
        $storeId = trim($request->input('store_id', ''));

        if (empty($storeId)) {
            return $this->error('store_id is required');
        }

        // 验证门店存在
        $store = Store::find($storeId);
        if (!$store) {
            return $this->error('store_not_found', 404);
        }

        // 检查是否已在排队中
        $existing = QueueNumber::where('user_id', $userId)
            ->where('store_id', $storeId)
            ->whereDate('created_at', date('Y-m-d'))
            ->where('status', QueueNumber::STATUS_WAITING)
            ->first();

        if ($existing) {
            $waitTime = QueueNumber::estimateWaitTime($storeId);
            return $this->success([
                'queue_id'      => $existing->id,
                'number'        => $existing->number,
                'status'        => $existing->status,
                'wait_minutes'  => $waitTime,
                'created_at'    => $existing->created_at,
            ], 'queue_already_waiting');
        }

        // 生成排号
        try {
            $number = QueueNumber::generateTodayNumber($storeId);

            $queue = new QueueNumber();
            $queue->id       = QueueNumber::generateId();
            $queue->store_id = $storeId;
            $queue->user_id  = $userId;
            $queue->number   = $number;
            $queue->status   = QueueNumber::STATUS_WAITING;
            $queue->save();

            $waitTime = QueueNumber::estimateWaitTime($storeId);

            return $this->success([
                'queue_id'     => $queue->id,
                'number'       => $number,
                'status'       => QueueNumber::STATUS_WAITING,
                'wait_minutes' => $waitTime,
                'created_at'   => $queue->created_at,
            ], 'queue_number_taken');
        } catch (\Throwable $e) {
            Log::error('[QueueController] take error: ' . $e->getMessage());
            return $this->error('取号失败，请稍后再试');
        }
    }

    /**
     * 获取当前用户的排队状态
     *
     * GET /api/queue/current
     *
     * @param  Request $request
     * @return \Webman\Http\Response
     */
    public function current(Request $request)
    {
        $userId = $request->user_id;

        $queue = QueueNumber::where('user_id', $userId)
            ->whereDate('created_at', date('Y-m-d'))
            ->whereIn('status', [QueueNumber::STATUS_WAITING, QueueNumber::STATUS_CALLED, QueueNumber::STATUS_SERVING])
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$queue) {
            return $this->success(null, '当前没有排队记录');
        }

        // 获取当前叫号
        $currentNumber = QueueNumber::getCurrentNumber($queue->store_id);
        $waitTime      = QueueNumber::estimateWaitTime($queue->store_id);

        // 计算前面还有多少人
        $aheadCount = QueueNumber::where('store_id', $queue->store_id)
            ->whereDate('created_at', date('Y-m-d'))
            ->where('status', QueueNumber::STATUS_WAITING)
            ->where('number', '<', $queue->number)
            ->count();

        return $this->success([
            'queue_id'        => $queue->id,
            'store_id'        => $queue->store_id,
            'number'          => $queue->number,
            'status'          => $queue->status,
            'current_number'  => $currentNumber,
            'ahead_count'     => $aheadCount,
            'wait_minutes'    => $waitTime,
            'created_at'      => $queue->created_at,
            'called_at'       => $queue->called_at,
        ]);
    }

    /**
     * 获取门店排队列表（大屏展示）
     *
     * GET /api/queue/store/{store_id}
     *
     * @param  string  $storeId
     * @param  Request $request
     * @return \Webman\Http\Response
     */
    public function storeQueue(string $storeId, Request $request)
    {
        if (empty($storeId)) {
            return $this->error('store_id is required');
        }

        $store = Store::find($storeId);
        if (!$store) {
            return $this->error('store_not_found', 404);
        }

        $todayDate = date('Y-m-d');

        // 当前叫号
        $currentNumber = QueueNumber::getCurrentNumber($storeId);

        // 等待中的队列（按号牌排序）
        $waiting = QueueNumber::where('store_id', $storeId)
            ->whereDate('created_at', $todayDate)
            ->where('status', QueueNumber::STATUS_WAITING)
            ->with('user:id,nickname,avatar')
            ->orderBy('number')
            ->get()
            ->map(fn($q) => [
                'id'         => $q->id,
                'number'     => $q->number,
                'nickname'   => $q->user->nickname ?? '',
                'avatar'     => $q->user->avatar ?? '',
                'created_at' => $q->created_at,
            ]);

        // 已完成/已叫号的记录
        $history = QueueNumber::where('store_id', $storeId)
            ->whereDate('created_at', $todayDate)
            ->whereIn('status', [QueueNumber::STATUS_CALLED, QueueNumber::STATUS_SERVING, QueueNumber::STATUS_COMPLETED])
            ->orderBy('called_at', 'desc')
            ->limit(20)
            ->get()
            ->map(fn($q) => [
                'id'        => $q->id,
                'number'    => $q->number,
                'status'    => $q->status,
                'called_at' => $q->called_at,
            ]);

        return $this->success([
            'store_id'       => $storeId,
            'store_name'     => $store->name ?? '',
            'current_number' => $currentNumber,
            'waiting'        => $waiting,
            'history'        => $history,
            'total_waiting'  => $waiting->count(),
            'wait_minutes'   => QueueNumber::estimateWaitTime($storeId),
        ]);
    }
}
