<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\model\Service;
use app\model\Share;
use app\model\TechnicianProfile;
use Webman\Http\Request;

/**
 * 分享控制器
 * 生成分享卡片数据、追踪分享转化
 */
class ShareController extends BaseController
{
    /**
     * 生成服务分享卡片
     * GET /api/share/service/{id}
     */
    public function service(Request $request, string $id)
    {
        $serviceId = $this->decodeId($id);
        if (!$serviceId) {
            return $this->error('服务ID无效');
        }

        $service = Service::find($serviceId);
        if (!$service) {
            return $this->error('服务不存在', 404);
        }

        $sharerId = $request->user_id;

        $data = [
            'title'       => $service->name,
            'description' => $service->description ?? '',
            'image_url'   => $service->cover_image ?? '',
            'share_path'  => "/pages/service/detail?id={$id}",
            'invite_code' => $sharerId ? $this->generateInviteCode($sharerId) : '',
        ];

        return $this->success($data);
    }

    /**
     * 生成技师分享卡片
     * GET /api/share/technician/{id}
     */
    public function technician(Request $request, string $id)
    {
        $techId = $this->decodeId($id);
        if (!$techId) {
            return $this->error('技师ID无效');
        }

        $profile = TechnicianProfile::find($techId);
        if (!$profile) {
            return $this->error('技师不存在', 404);
        }

        $sharerId = $request->user_id;

        $data = [
            'title'       => $profile->real_name ?? '专业技师',
            'description' => $profile->intro ?? '',
            'image_url'   => $profile->avatar ?? '',
            'share_path'  => "/pages/technician/detail?id={$id}",
            'invite_code' => $sharerId ? $this->generateInviteCode($sharerId) : '',
        ];

        return $this->success($data);
    }

    /**
     * 追踪分享点击/转化
     * POST /api/share/track
     */
    public function track(Request $request)
    {
        $sharerId = $request->user_id;

        $shareType = $request->input('share_type', '');
        $targetId  = $this->decodeId($request->input('target_id', ''));
        $platform  = $request->input('platform', 'wechat');
        $converted = $request->input('converted', false);

        if (!in_array($shareType, [Share::SHARE_TYPE_SERVICE, Share::SHARE_TYPE_TECHNICIAN], true)) {
            return $this->error('无效的分享类型');
        }

        if (!$targetId) {
            return $this->error('目标ID无效');
        }

        if (!in_array($platform, [Share::PLATFORM_WECHAT, Share::PLATFORM_TIMELINE], true)) {
            $platform = Share::PLATFORM_WECHAT;
        }

        $share = Share::create([
            'id'         => Share::generateId(),
            'sharer_id'  => $sharerId,
            'share_type' => $shareType,
            'target_id'  => $targetId,
            'platform'   => $platform,
            'clicked_at' => now(),
            'converted_at' => $converted ? now() : null,
        ]);

        return $this->success($share, '追踪记录成功');
    }

    /**
     * 生成邀请码（用于追踪分享来源）
     */
    private function generateInviteCode(string $userId): string
    {
        return substr(md5($userId . config('app.key', '')), 0, 8);
    }
}
