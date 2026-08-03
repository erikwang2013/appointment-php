<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\Notification;
use app\model\User;
use app\model\Order;
use support\Request;
use support\Log;
use support\Response;

class BatchMessageController extends BaseController
{
    /**
     * 发送批量通知
     * 接受: target(user_ids[] / all_users / segment), type, title, content
     */
    public function send(Request $request): Response
    {
        $validator = validator($request->all(), [
            'target'  => 'required|string|in:user_ids,all_users,segment',
            'type'    => 'required|string|in:system,order',
            'title'   => 'required|string|max:200',
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422);
        }

        $target    = $request->input('target');
        $type      = $request->input('type');
        $title     = $request->input('title');
        $content   = $request->input('content');
        $userIds   = [];

        switch ($target) {
            case 'user_ids':
                $rawIds = $request->input('user_ids', []);
                if (empty($rawIds)) {
                    return $this->fail('请选择目标用户', 422);
                }
                foreach ($rawIds as $hashid) {
                    try {
                        $userIds[] = (string) $this->decodeId($hashid);
                    } catch (\InvalidArgumentException $e) {
                        // 跳过无效ID
                    }
                }
                break;

            case 'all_users':
                $segmentType = $request->input('segment_type', 'all');
                $query = User::where('status', 1);
                if ($segmentType === 'customer') {
                    $query->where('user_type', 'customer');
                } elseif ($segmentType === 'technician') {
                    $query->where('user_type', 'technician');
                }
                $userIds = $query->pluck('id')->toArray();
                break;

            case 'segment':
                $segmentName = $request->input('segment_name', '');
                $now     = date('Y-m-d H:i:s');
                $monthAgo = date('Y-m-d H:i:s', strtotime('-30 days'));
                $weekAgo  = date('Y-m-d H:i:s', strtotime('-7 days'));

                switch ($segmentName) {
                    case 'high_value':
                        $threshold = (float) $request->input('threshold', 5000);
                        $userIds = Order::whereIn('status', ['paid', 'confirmed', 'serving', 'completed'])
                            ->selectRaw('user_id, SUM(paid_amount) as total_spent')
                            ->groupBy('user_id')
                            ->having('total_spent', '>=', $threshold)
                            ->pluck('user_id')
                            ->toArray();
                        break;

                    case 'regular':
                        $userIds = Order::where('created_at', '>=', $monthAgo)
                            ->selectRaw('user_id, COUNT(*) as order_count')
                            ->groupBy('user_id')
                            ->having('order_count', '>', 3)
                            ->pluck('user_id')
                            ->toArray();
                        break;

                    case 'dormant':
                        $activeUserIds = Order::where('created_at', '>=', $monthAgo)
                            ->pluck('user_id')->unique()->toArray();
                        $userIds = User::where('status', 1)
                            ->where('user_type', 'customer')
                            ->whereNotIn('id', $activeUserIds)
                            ->where('created_at', '<', $monthAgo)
                            ->pluck('id')
                            ->toArray();
                        break;

                    case 'new':
                        $userIds = User::where('status', 1)
                            ->where('user_type', 'customer')
                            ->where('created_at', '>=', $weekAgo)
                            ->pluck('id')
                            ->toArray();
                        break;

                    default:
                        return $this->fail('无效的分群名称，支持: high_value/regular/dormant/new', 422);
                }
                break;
        }

        if (empty($userIds)) {
            return $this->fail('没有匹配到任何目标用户', 404);
        }

        $batchId = date('YmdHis') . str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);
        $now     = date('Y-m-d H:i:s');
        $created = 0;

        // 批量创建通知记录
        foreach ($userIds as $uid) {
            $notification = new Notification();
            $notification->id          = (string) $this->generateId();
            $notification->user_id     = (string) $uid;
            $notification->type        = $type;
            $notification->title       = $title . ' [批次:' . $batchId . ']';
            $notification->content     = $content;
            $notification->is_read     = 0;
            $notification->created_at  = $now;
            $notification->save();
            $created++;
        }

        $this->dispatchNotifications($userIds, $type, $title, $content, $batchId);

        return $this->success([
            'batch_id'      => $batchId,
            'target'        => $target,
            'type'          => $type,
            'target_count'  => count($userIds),
            'created_count' => $created,
            'executed_at'   => $now,
        ], '批量消息已发送（通知记录已创建，推送通道待接入）');
    }

    /**
     * 消息模板列表
     */
    public function templates(Request $request): Response
    {
        // 预设模板列表（后续可扩展为数据库模板表）
        $templates = [
            [
                'id'       => 'tpl_001',
                'name'     => '新用户欢迎',
                'type'     => 'system',
                'title'    => '欢迎加入',
                'preview'  => '尊敬的用户，欢迎加入我们的服务，首次下单享专属优惠！',
            ],
            [
                'id'       => 'tpl_002',
                'name'     => '订单提醒',
                'type'     => 'order',
                'title'    => '您有新的订单待确认',
                'preview'  => '您的预约订单已生成，请及时确认服务时间。',
            ],
            [
                'id'       => 'tpl_003',
                'name'     => '促销活动',
                'type'     => 'system',
                'title'    => '限时优惠活动',
                'preview'  => '限时优惠进行中，全场服务8折起，快来选购吧！',
            ],
            [
                'id'       => 'tpl_004',
                'name'     => '沉睡唤醒',
                'type'     => 'system',
                'title'    => '好久不见，我们想您了',
                'preview'  => '您已有一段时间没来了，赠送您一张专属优惠券，欢迎回来！',
            ],
        ];

        return $this->success(['templates' => $templates]);
    }

    /**
     * 发送历史
     */
    public function history(Request $request): Response
    {
        $page   = (int) $request->input('page', 1);
        $limit  = (int) $request->input('limit', 15);
        $type   = $request->input('type', '');

        // 按批次聚合查询最近通知
        $query = Notification::query();
        if ($type) {
            $query->where('type', $type);
        }

        // 按 title 中的批次号分组聚合
        $total = $query->selectRaw(
            "SUBSTRING_INDEX(SUBSTRING_INDEX(title, '[批次:', -1), ']', 1) as batch_id"
        )
            ->where('title', 'like', '%[批次:%')
            ->groupBy('batch_id')
            ->get()
            ->count();

        $batches = $query
            ->selectRaw(
                "SUBSTRING_INDEX(SUBSTRING_INDEX(title, '[批次:', -1), ']', 1) as batch_id, " .
                "type, " .
                "REPLACE(SUBSTRING_INDEX(title, ' [批次:', 1), '', '') as original_title, " .
                "COUNT(*) as send_count, " .
                "MAX(created_at) as last_sent_at"
            )
            ->where('title', 'like', '%[批次:%')
            ->groupBy('batch_id', 'type', 'original_title')
            ->orderBy('last_sent_at', 'desc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->toArray();

        return $this->success([
            'list'  => $batches,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }
}
