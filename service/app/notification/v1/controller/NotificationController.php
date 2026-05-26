<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\notification\v1\controller;

use app\common\BaseController;
use app\model\Notification;
use Webman\Http\Request;

/**
 * 通知控制器
 * 处理用户消息通知、已读标记
 */
class NotificationController extends BaseController
{
    /**
     * 获取用户通知列表
     * GET /api/notification?type=order&page=1
     *
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function index(Request $request)
    {
        $userId = $request->user_id;
        $type = $request->input('type');
        $page = (int)($request->input('page', 1));
        $perPage = (int)($request->input('per_page', 15));

        $query = Notification::where(function ($q) use ($userId) {
            $q->where('user_id', 0)
              ->orWhere('user_id', $userId);
        });

        if ($type && in_array($type, ['order', 'system'])) {
            $query->where('type', $type);
        }

        $query->orderBy('is_read', 'asc')
            ->orderBy('id', 'desc');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return $this->paginate($paginator);
    }

    /**
     * 标记单条通知为已读
     * PUT /api/notification/read/{id}
     *
     * @param mixed $id
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function read($id, Request $request)
    {
        $userId = $request->user_id;
        $decodedId = $this->decodeId((string)$id);

        if ($decodedId === null) {
            return $this->error('通知不存在');
        }

        $notification = Notification::where(function ($q) use ($userId) {
            $q->where('user_id', 0)
              ->orWhere('user_id', $userId);
        })->find($decodedId);

        if (!$notification) {
            return $this->error('通知不存在');
        }

        if (!$notification->is_read) {
            $notification->is_read = 1;
            $notification->read_at = now()->format('Y-m-d H:i:s');
            $notification->save();
        }

        return $this->success($notification, '已标记为已读');
    }

    /**
     * 标记所有未读通知为已读
     * PUT /api/notification/read-all
     *
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function readAll(Request $request)
    {
        $userId = $request->user_id;

        $updated = Notification::where('is_read', 0)
            ->where(function ($q) use ($userId) {
                $q->where('user_id', 0)
                  ->orWhere('user_id', $userId);
            })
            ->update([
                'is_read' => 1,
                'read_at' => now()->format('Y-m-d H:i:s'),
            ]);

        return $this->success([
            'updated_count' => $updated,
        ], '全部标记为已读');
    }
}
