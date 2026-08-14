<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\user\v1\controller;

use app\common\BaseController;
use app\common\NotificationReminderService;
use app\model\UserNotifySetting;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 消息偏好设置控制器
 *
 * GET /api/user/notify-settings  返回全部类型开关（未设置的行默认开）
 * PUT /api/user/notify-settings  批量设置 [{type, switch}]，upsert
 * system 类型不可关闭，恒为 1。
 */
class NotifySettingController extends BaseController
{
    /** 全部通知类型（顺序即展示顺序） */
    private const TYPES = [
        NotificationReminderService::NOTIFY_TYPE_SERVICE_REMINDER,
        NotificationReminderService::NOTIFY_TYPE_CARD_EXPIRY,
        NotificationReminderService::NOTIFY_TYPE_POINTS_EXPIRY,
        NotificationReminderService::NOTIFY_TYPE_MARKETING,
        NotificationReminderService::NOTIFY_TYPE_SYSTEM,
    ];

    public function index(Request $request): Response
    {
        $userId = (string) $request->user_id;
        $rows   = UserNotifySetting::where('user_id', $userId)->get()->keyBy('type');

        $result = [];
        foreach (self::TYPES as $type) {
            $switch = isset($rows[$type]) ? (int) $rows[$type]->switch : 1;
            if ($type === NotificationReminderService::NOTIFY_TYPE_SYSTEM) {
                $switch = 1; // 系统消息不可关闭
            }
            $result[] = ['type' => $type, 'switch' => $switch];
        }

        return $this->success($result);
    }

    public function update(Request $request): Response
    {
        $userId = (string) $request->user_id;

        $settings = $request->input('settings');
        if (!is_array($settings)) {
            $settings = $request->post(); // 兼容直接传数组 body
        }
        if (!is_array($settings) || empty($settings)) {
            return $this->error('settings 不能为空');
        }

        foreach ($settings as $item) {
            $type   = (string) ($item['type'] ?? '');
            $switch = $item['switch'] ?? null;

            if (!in_array($type, self::TYPES, true)) {
                return $this->error('无效的通知类型: ' . $type);
            }
            if (!in_array($switch, [0, 1], true)) {
                return $this->error('switch 必须为 0 或 1');
            }
            if ($type === NotificationReminderService::NOTIFY_TYPE_SYSTEM) {
                $switch = 1; // 系统消息不可关闭，强制开启
            }

            UserNotifySetting::updateOrCreate(
                ['user_id' => $userId, 'type' => $type],
                ['switch' => $switch]
            );
        }

        return $this->index($request);
    }
}
