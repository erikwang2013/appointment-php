<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\order\v1\controller;

use app\common\BaseController;
use app\model\Waitlist;
use support\Db;
use Webman\Http\Request;

/**
 * 排队等待控制器
 * 用户加入等待队列，当目标时段释放时收到通知
 */
class WaitlistController extends BaseController
{
    /**
     * 加入等待队列
     * POST /api/order/waitlist
     *
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function store(Request $request)
    {
        $userId = $request->user_id;
        $serviceId = $this->decodeId($request->input('service_id'));
        $technicianId = $this->decodeId($request->input('technician_id'));
        $preferredDate = $request->input('preferred_date', '');
        $preferredTime = $request->input('preferred_time', '');

        if (!$serviceId && !$technicianId) {
            return $this->error('请选择服务或技师');
        }

        if (empty($preferredDate)) {
            return $this->error('请选择期望日期');
        }

        // 检查是否已加入相同条件的等待
        $exists = Waitlist::where('user_id', $userId)
            ->where('status', Waitlist::STATUS_WAITING)
            ->where(function ($query) use ($serviceId, $technicianId, $preferredDate, $preferredTime) {
                if ($serviceId) {
                    $query->where('service_id', $serviceId);
                }
                if ($technicianId) {
                    $query->where('technician_id', $technicianId);
                }
                $query->where('preferred_date', $preferredDate);
                if ($preferredTime) {
                    $query->where('preferred_time', $preferredTime);
                }
            })
            ->exists();

        if ($exists) {
            return $this->error('您已加入该时段等待队列');
        }

        $waitlist = Waitlist::create([
            'id' => Waitlist::generateId(),
            'user_id' => $userId,
            'service_id' => $serviceId,
            'technician_id' => $technicianId,
            'preferred_date' => $preferredDate,
            'preferred_time' => $preferredTime ?: null,
            'status' => Waitlist::STATUS_WAITING,
        ]);

        return $this->success($waitlist, '已加入等待队列');
    }

    /**
     * 我的等待列表
     * GET /api/order/waitlist
     *
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function index(Request $request)
    {
        $userId = $request->user_id;

        $waitlists = Waitlist::where('user_id', $userId)
            ->with(['service' => function ($query) {
                $query->select('id', 'name', 'cover_image');
            }, 'technician' => function ($query) {
                $query->select('id', 'avatar', 'nickname');
            }])
            ->orderBy('status', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($waitlists);
    }

    /**
     * 取消等待
     * POST /api/order/waitlist/cancel/{id}
     *
     * @param mixed $id
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function cancel($id, Request $request)
    {
        $userId = $request->user_id;
        $decodedId = $this->decodeId((string)$id);

        if ($decodedId === null) {
            return $this->error('记录不存在');
        }

        $waitlist = Waitlist::where('user_id', $userId)
            ->where('status', Waitlist::STATUS_WAITING)
            ->find($decodedId);

        if (!$waitlist) {
            return $this->error('等待记录不存在或已失效');
        }

        $waitlist->status = Waitlist::STATUS_CANCELLED;
        $waitlist->save();

        return $this->success(null, '已取消等待');
    }
}
