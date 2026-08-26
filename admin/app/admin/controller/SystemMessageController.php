<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\SystemConfig;
use app\model\Notification;
use app\model\User;
use support\Request;
use support\Response;

class SystemMessageController extends BaseController
{
    /**
     * 消息模板列表
     */
    public function index(Request $request): Response
    {
        $configs = SystemConfig::where('group', 'message_template')
            ->orderBy('key')
            ->get()
            ->map(fn($c) => $c->toArray());

        return $this->success(['list' => $configs]);
    }

    /**
     * 编辑消息模板
     */
    public function update(Request $request, string $hashid): Response
    {
        $id     = $this->decodeId($hashid);
        $config = SystemConfig::find($id);
        if (!$config) {
            return $this->fail('模板不存在', 404);
        }

        if ($request->input('value') !== null) {
            $config->value = $request->input('value');
        }
        if ($request->input('description') !== null) {
            $config->description = $request->input('description');
        }
        $config->save();

        return $this->success($config->toArray(), '模板更新成功');
    }

    /**
     * 发送消息给用户（通知广播）
     */
    public function send(Request $request): Response
    {
        $validator = validator($request->all(), [
            'title'   => 'required|string|max:200',
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422);
        }

        $targetType = $request->input('target_type', 'all'); // all | user_ids
        $userIds    = $request->input('user_ids', []);
        $title      = $request->input('title');
        $content    = $request->input('content');
        $type       = $request->input('type', 'system'); // system | promotion | announcement

        $query = User::query();
        if ($targetType === 'user_ids' && !empty($userIds)) {
            $query->whereIn('id', $userIds);
        }

        $users = $query->get();
        $count = 0;
        foreach ($users as $user) {
            $notification = new Notification();
            $notification->id       = (string) $this->generateId();
            $notification->user_id  = $user->id;
            $notification->type     = $type;
            $notification->title    = $title;
            $notification->content  = $content;
            $notification->is_read  = 0;
            $notification->save();
            $count++;
        }

        return $this->success(['sent_count' => $count], "已发送 {$count} 条通知");
    }
}
