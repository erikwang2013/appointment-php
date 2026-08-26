<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\Feedback;
use support\Request;
use support\Response;

class FeedbackController extends BaseController
{
    /**
     * 反馈列表
     * 搜索: phone / status
     */
    public function index(Request $request): Response
    {
        $page   = (int) $request->input('page', 1);
        $limit  = (int) $request->input('limit', 15);
        $phone  = $request->input('phone', '');
        $status = $request->input('status');

        $query = Feedback::with('user');

        if ($phone) {
            $query->whereHas('user', function ($q) use ($phone) {
                $q->where('phone', 'like', "%{$phone}%");
            });
        }
        if ($status !== null && $status !== '') {
            $query->where('status', (int) $status);
        }

        $total = $query->count();
        $list  = $query->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->orderBy('id', 'desc')
                       ->get()
                       ->map(function ($fb) {
                           $data = $fb->toArray();
                           if (isset($data['user']['phone'])) {
                               $data['user']['phone'] = preg_replace('/^(\d{3})\d+(\d{4})$/', '$1****$2', $data['user']['phone']);
                           }
                           return $data;
                       });

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 反馈详情
     */
    public function show(Request $request, string $hashid): Response
    {
        $id       = $this->decodeId($hashid);
        $feedback = Feedback::with('user')->find($id);
        if (!$feedback) {
            return $this->fail('反馈不存在', 404);
        }

        $data = $feedback->toArray();
        if (isset($data['user']['phone'])) {
            $data['user']['phone'] = preg_replace('/^(\d{3})\d+(\d{4})$/', '$1****$2', $data['user']['phone']);
        }

        return $this->success($data);
    }

    /**
     * 管理员回复反馈
     */
    public function reply(Request $request, string $hashid): Response
    {
        $id       = $this->decodeId($hashid);
        $feedback = Feedback::find($id);
        if (!$feedback) {
            return $this->fail('反馈不存在', 404);
        }

        $reply = $request->input('reply', '');
        if (empty($reply)) {
            return $this->fail('回复内容不能为空', 422);
        }

        $feedback->handler_reply = $reply;
        $feedback->status        = 1; // 已处理
        $feedback->handled_by    = $request->adminId ?? 0;
        $feedback->handled_at    = date('Y-m-d H:i:s');
        $feedback->save();

        return $this->success($feedback->toArray(), '回复成功');
    }
}
