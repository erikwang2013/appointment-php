<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\user\v1\controller;

use app\common\BaseController;
use app\model\Ticket;
use Webman\Http\Request;

/**
 * 客服工单控制器（用户端）
 *
 * 用户提交工单（pending）、查看我的工单列表/详情、关闭进行中工单。
 * 管理端处理回复见 admin/app/admin/controller/TicketController。
 */
class TicketController extends BaseController
{
    private const CATEGORIES = ['service', 'refund', 'technician', 'other'];

    private const STATUS_TEXT = [
        'pending'    => '待处理',
        'processing' => '处理中',
        'resolved'   => '已解决',
        'closed'     => '已关闭',
    ];

    /**
     * 提交客服工单
     * POST /api/tickets  body: {category, description, images?}
     */
    public function store(Request $request)
    {
        $userId = $request->user_id;

        $category = (string) $request->input('category', '');
        if (!in_array($category, self::CATEGORIES, true)) {
            return $this->error('工单分类无效', 422);
        }

        $description = trim((string) $request->input('description', ''));
        if ($description === '') {
            return $this->error('请填写问题描述', 422);
        }
        if (mb_strlen($description) > 2000) {
            return $this->error('问题描述不能超过2000个字符', 422);
        }

        $images = $request->input('images', []);
        if (!is_array($images)) {
            return $this->error('图片格式无效', 422);
        }
        if (count($images) > 9) {
            return $this->error('最多上传9张图片', 422);
        }

        $ticket = Ticket::create([
            'id'          => Ticket::generateId(),
            'user_id'     => $userId,
            'category'    => $category,
            'description' => $description,
            'images'      => !empty($images) ? array_values($images) : null,
            'status'      => 'pending',
        ]);

        return $this->success($ticket, '工单提交成功');
    }

    /**
     * 我的工单列表
     * GET /api/tickets?status=&page=&limit=
     */
    public function index(Request $request)
    {
        $userId = $request->user_id;
        $status = (string) $request->input('status', '');
        $page   = max(1, (int) $request->input('page', 1));
        $limit  = (int) $request->input('limit', 15);
        if ($limit < 1 || $limit > 100) {
            $limit = 15;
        }

        $query = Ticket::where('user_id', $userId);
        if ($status !== '') {
            $query->where('status', $status);
        }

        $tickets = $query->orderBy('created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

        return $this->paginate($tickets);
    }

    /**
     * 工单详情
     * GET /api/tickets/{id}
     */
    public function show(Request $request, ?string $id)
    {
        $userId   = $request->user_id;
        $ticketId = $this->decodeId($id);
        if ($ticketId === null) {
            return $this->error('工单不存在', 404);
        }

        $ticket = Ticket::where('user_id', $userId)->where('id', $ticketId)->first();
        if (!$ticket) {
            return $this->error('工单不存在', 404);
        }

        $data = $ticket->toArray();
        $data['status_text'] = self::STATUS_TEXT[$ticket->status] ?? $ticket->status;

        return $this->success($data);
    }

    /**
     * 关闭工单
     * POST /api/tickets/{id}/close
     * 仅本人可关闭；pending/processing 可关，resolved/closed 不可关。
     */
    public function close(Request $request, ?string $id)
    {
        $userId   = $request->user_id;
        $ticketId = $this->decodeId($id);
        if ($ticketId === null) {
            return $this->error('工单不存在', 404);
        }

        $ticket = Ticket::where('user_id', $userId)->where('id', $ticketId)->first();
        if (!$ticket) {
            return $this->error('工单不存在', 404);
        }

        if (!in_array($ticket->status, ['pending', 'processing'], true)) {
            return $this->error('当前状态不可关闭', 422);
        }

        $ticket->status = 'closed';
        $ticket->save();

        $data = $ticket->toArray();
        $data['status_text'] = self::STATUS_TEXT[$ticket->status] ?? $ticket->status;

        return $this->success($data, '工单已关闭');
    }
}
