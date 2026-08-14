<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\technician\v1\controller;

use app\common\BaseController;
use app\common\TierRatingService;
use app\model\TechnicianProfile;
use app\model\User;
use support\Log;
use Webman\Http\Request;

/**
 * 技师个人资料控制器
 * 查看/编辑技师档案
 */
class ProfileController extends BaseController
{
    /**
     * 获取技师档案
     * GET /api/technician/profile
     */
    public function show(Request $request)
    {
        $technicianId = $request->technician_id;

        $profile = TechnicianProfile::find($technicianId);
        if (!$profile) {
            return $this->error('技师档案不存在', 404);
        }

        // 懒判定：查看资料时顺带评定等级（幂等：等级未变化不写日志不发通知）
        try {
            TierRatingService::evaluate($technicianId);
        } catch (\Throwable $e) {
            Log::warning('[ProfileController] tier evaluate failed: ' . $e->getMessage());
        }
        $profile = TechnicianProfile::find($technicianId);
        $profile->load('user');

        $data = $profile->toArray();
        if ($profile->user) {
            $data['user'] = [
                'nickname' => $profile->user->nickname,
                'avatar' => $profile->user->avatar,
                'phone' => $profile->user->phone,
            ];
        }

        return $this->success($data);
    }

    /**
     * 更新技师档案
     * PUT /api/technician/profile
     */
    public function update(Request $request)
    {
        $technicianId = $request->technician_id;

        $profile = TechnicianProfile::find($technicianId);
        if (!$profile) {
            return $this->error('技师档案不存在', 404);
        }

        $data = [];

        $fields = ['real_name', 'gender', 'id_card', 'id_card_front', 'id_card_back', 'avatar', 'intro'];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $data[$field] = trim($request->input($field, ''));
            }
        }

        if (empty($data)) {
            return $this->error('没有需要更新的信息');
        }

        $profile->fill($data);

        // 首次完整提交档案时，设为待审核状态
        if ($profile->status === 'pending' || empty($profile->status)) {
            $hasRequired = !empty($profile->real_name)
                && !empty($profile->gender)
                && !empty($profile->id_card);

            if ($hasRequired && $profile->status !== 'approved') {
                $profile->status = 'pending';
            }
        }

        $profile->save();

        return $this->success($profile, '资料更新成功');
    }

    /**
     * 更新技师主页自定义信息
     * PUT /api/technician/profile/homepage
     */
    public function updateHomepage(Request $request)
    {
        $technicianId = $request->technician_id;

        $profile = TechnicianProfile::find($technicianId);
        if (!$profile) {
            return $this->error('技师档案不存在', 404);
        }

        $data = [];

        $fields = ['cover_image', 'video_url', 'intro', 'certificates'];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $value = $request->input($field);
                if ($field === 'certificates' && is_array($value)) {
                    $data[$field] = $value;
                } else {
                    $data[$field] = trim((string)$value);
                }
            }
        }

        if (empty($data)) {
            return $this->error('没有需要更新的信息');
        }

        $profile->fill($data);
        $profile->save();

        return $this->success($profile, '主页信息更新成功');
    }
}
