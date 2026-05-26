<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\user\v1\controller;

use app\common\BaseController;
use app\model\UserDevice;
use support\Log;
use Webman\Http\Request;

/**
 * 用户设备控制器
 *
 * 管理推送通知设备 token 的注册与注销
 */
class DeviceController extends BaseController
{
    /**
     * 注册设备 token
     *
     * POST /api/user/device/register
     *
     * @param  Request $request
     * @return \Webman\Http\Response
     */
    public function register(Request $request)
    {
        $userId   = $request->user_id;
        $platform = trim($request->input('platform', ''));
        $token    = trim($request->input('device_token', ''));

        if (empty($platform) || empty($token)) {
            return $this->error('platform and device_token are required');
        }

        if (!in_array($platform, [UserDevice::PLATFORM_IOS, UserDevice::PLATFORM_ANDROID], true)) {
            return $this->error('Invalid platform, must be ios or android');
        }

        try {
            // Upsert: 同一用户同一 token 不重复插入
            $existing = UserDevice::where('user_id', $userId)
                ->where('device_token', $token)
                ->first();

            if ($existing) {
                // 更新平台（用户可能更换手机系统）
                $existing->platform = $platform;
                $existing->updated_at = date('Y-m-d H:i:s');
                $existing->save();
            } else {
                $device = new UserDevice();
                $device->id           = UserDevice::generateId();
                $device->user_id      = $userId;
                $device->platform     = $platform;
                $device->device_token = $token;
                $device->save();
            }

            return $this->success([
                'platform'     => $platform,
                'device_token' => substr($token, 0, 10) . '...',
            ], 'device_registered');
        } catch (\Throwable $e) {
            Log::error('[DeviceController] register error: ' . $e->getMessage());
            return $this->error('Failed to register device');
        }
    }

    /**
     * 注销设备 token
     *
     * POST /api/user/device/unregister
     *
     * 用户退出登录或关闭推送时调用
     *
     * @param  Request $request
     * @return \Webman\Http\Response
     */
    public function unregister(Request $request)
    {
        $userId = $request->user_id;
        $token  = trim($request->input('device_token', ''));

        if (empty($token)) {
            return $this->error('device_token is required');
        }

        try {
            $deleted = UserDevice::where('user_id', $userId)
                ->where('device_token', $token)
                ->delete();

            if ($deleted) {
                return $this->success(null, 'device_unregistered');
            }
            return $this->success(null, '设备未找到或已注销');
        } catch (\Throwable $e) {
            Log::error('[DeviceController] unregister error: ' . $e->getMessage());
            return $this->error('Failed to unregister device');
        }
    }
}
