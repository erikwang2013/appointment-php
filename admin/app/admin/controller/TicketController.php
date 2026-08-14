<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\Notification;
use app\model\Ticket;
use InvalidArgumentException;
use support\Log;
use support\Request;
use support\Response;

/**
 * 客服工单管理控制器
 *
 * 工单列表（status/user_id 筛选）+ 回复（回复内容必填，status 可选
 * processing/resolved，默认 processing）。回复成功后给用户发站内通知
 * type=ticket_reply，通知失败仅告警不阻塞主流程。
 * 用户端提交见 service/app/user/v1/controller/TicketController。
 */
class TicketController extends BaseController
{
    /**
     * 工单列表
     * GET /admin/tickets?page&limit&status&user_id
     */
    public function index(Request $request): Response
    {
        $page   = (int) $request->input('page', 1);
        $limit  = (int) $request->input('limit', 15);
        $status = (string) $request->input('status', '');
        $userId = (string) $request->input('user_id', '');

        $query = Ticket::with('user');

        if ($status !== '') {
            $query->where('status', $status);
        }
        if ($userId !== '') {
            $query->where('user_id', $userId);
        }

        $total = $query->count();
        $list  = $query->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->orderBy('created_at', 'desc')
                       ->get()
                       ->map(fn($t) => $this->encodeIds($t->toArray(), ['id', 'user_id', 'admin_id']));

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 满意度统计
     * GET /admin/tickets/satisfaction
     * 返回工单总数/已评分数/平均分（1 位小数）/评分分布（1-5 星各数量）。
     */
    public function satisfaction(Request $request): Response
    {
        $total = Ticket::count();
        $rated = Ticket::whereNotNull('rating')->count();

        $avg = Ticket::whereNotNull('rating')->avg('rating');

        $counts = Ticket::whereNotNull('rating')
            ->selectRaw('rating, COUNT(*) AS cnt')
            ->groupBy('rating')
            ->pluck('cnt', 'rating');
        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = (int) ($counts[$i] ?? 0);
        }

        return $this->success([
            'total'        => $total,
            'rated_count'  => $rated,
            'unrated_count'=> $total - $rated,
            'average'      => number_format((float) $avg, 1),
            'distribution' => $distribution,
        ]);
    }

    /**
     * 回复工单
     * POST /admin/tickets/{id}/reply  body: {reply_content, status?}
     */
    public function reply(Request $request, string $hashid): Response
    {
        try {
            $id = $this->decodeId($hashid);
        } catch (InvalidArgumentException) {
            return $this->fail('无效的工单ID', 422);
        }

        $ticket = Ticket::find($id);
        if (!$ticket) {
            return $this->fail('工单不存在', 404);
        }

        if (in_array($ticket->status, ['resolved', 'closed'], true)) {
            return $this->fail('该工单已结束，无法回复', 422);
        }

        $replyContent = trim((string) $request->input('reply_content', ''));
        if ($replyContent === '') {
            return $this->fail('请填写回复内容', 422);
        }

        $status = (string) $request->input('status', 'processing');
        if (!in_array($status, ['processing', 'resolved'], true)) {
            return $this->fail('无效的工单状态', 422);
        }

        $ticket->reply_content = $replyContent;
        $ticket->status        = $status;
        $ticket->admin_id      = (string) ($request->adminId ?? 0);
        $ticket->replied_at    = date('Y-m-d H:i:s');
        $ticket->save();

        // 站内通知用户；失败仅告警，不阻塞回复
        try {
            $notification = new Notification();
            $notification->id         = (string) $this->generateId();
            $notification->user_id    = (string) $ticket->user_id;
            $notification->type       = 'ticket_reply';
            $notification->title      = '客服工单回复';
            $notification->content    = '您的工单已回复，请查看';
            $notification->is_read    = 0;
            $notification->save();
        } catch (\Throwable $e) {
            Log::warning('[TicketController] notify ticket reply failed: ' . $e->getMessage());
        }

        return $this->success(
            $this->encodeIds($ticket->toArray(), ['id', 'user_id', 'admin_id']),
            '回复成功'
        );
    }
}
