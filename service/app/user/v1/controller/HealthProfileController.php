<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\user\v1\controller;

use app\common\BaseController;
use app\model\User;
use app\model\UserHealthProfile;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 用户健康档案与服务偏好控制器
 *
 * GET    /api/health-profile  我的档案（无档案返回空结构 + set:false）
 * PUT    /api/health-profile  创建/更新（upsert，字段全可选，只更提供的字段）
 * DELETE /api/health-profile  清空档案（删除本行）
 */
class HealthProfileController extends BaseController
{
    /** 可写字段（顺序即展示顺序），preferred_technician_id 单独校验存在性 */
    private const FIELDS = [
        'allergies'         => ['max' => 500],
        'chronic_diseases'  => ['max' => 500],
        'preferred_technician_id' => null,
        'preferred_time'    => ['max' => 50],
        'notes'             => ['max' => 500],
    ];

    public function show(Request $request): Response
    {
        $userId  = (string) $request->user_id;
        $profile = UserHealthProfile::where('user_id', $userId)->first();

        if (!$profile) {
            return $this->success([
                'set'      => false,
                'allergies' => '',
                'chronic_diseases' => '',
                'preferred_technician_id' => null,
                'preferred_time' => '',
                'notes' => '',
            ]);
        }

        return $this->success([
            'set'                    => true,
            'allergies'              => (string) $profile->allergies,
            'chronic_diseases'       => (string) $profile->chronic_diseases,
            'preferred_technician_id' => $profile->preferred_technician_id
                ? (string) $profile->preferred_technician_id
                : null,
            'preferred_time'         => (string) $profile->preferred_time,
            'notes'                  => (string) $profile->notes,
        ]);
    }

    public function upsert(Request $request): Response
    {
        $userId = (string) $request->user_id;

        $input = $request->all();
        if (!is_array($input)) {
            $input = [];
        }

        $attrs = [];
        foreach (self::FIELDS as $field => $rule) {
            if (!array_key_exists($field, $input)) {
                continue; // 只更提供的字段
            }
            $value = $input[$field];

            if ($field === 'preferred_technician_id') {
                if ($value === null || $value === '') {
                    $attrs[$field] = null;
                    continue;
                }
                if (!preg_match('/^\d{1,20}$/', (string) $value)) {
                    return $this->error('preferred_technician_id 格式不正确', 422);
                }
                if (!User::where('id', (string) $value)
                    ->where('user_type', 'technician')
                    ->exists()) {
                    return $this->error('偏好技师不存在', 422);
                }
                $attrs[$field] = (string) $value;
                continue;
            }

            $text = (string) $value;
            if (mb_strlen($text) > $rule['max']) {
                return $this->error($field . ' 长度不能超过 ' . $rule['max'] . ' 字符', 422);
            }
            $attrs[$field] = $text === '' ? null : $text;
        }

        if (empty($attrs)) {
            return $this->error('没有可更新的字段', 422);
        }

        $profile = UserHealthProfile::updateOrCreate(
            ['user_id' => $userId],
            $attrs
        );

        $profile->refresh();
        return $this->success([
            'set'                    => true,
            'allergies'              => (string) $profile->allergies,
            'chronic_diseases'       => (string) $profile->chronic_diseases,
            'preferred_technician_id' => $profile->preferred_technician_id
                ? (string) $profile->preferred_technician_id
                : null,
            'preferred_time'         => (string) $profile->preferred_time,
            'notes'                  => (string) $profile->notes,
        ]);
    }

    public function destroy(Request $request): Response
    {
        $userId = (string) $request->user_id;

        UserHealthProfile::where('user_id', $userId)->delete();

        return $this->success(null, '已清空健康档案');
    }
}
