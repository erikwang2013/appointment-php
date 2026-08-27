<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\model\PlatformAgreement;
use support\Db;
use Webman\Http\Request;

/**
 * 公共配置控制器
 * 处理协议、系统配置、区域等公共数据
 */
class CommonController extends BaseController
{
    /**
     * 获取公共配置
     * GET /api/common/config
     *
     * 返回各类协议最新版本、关于我们、版本号等
     *
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function config(Request $request)
    {
        // 获取各类协议最新版本
        $agreementTypes = ['user_agreement', 'privacy_policy', 'service_agreement'];
        $agreements = [];

        foreach ($agreementTypes as $type) {
            $agreement = PlatformAgreement::where('type', $type)
                ->where('status', 1)
                ->orderBy('version', 'desc')
                ->orderBy('published_at', 'desc')
                ->first();

            $agreements[$type] = $agreement ? $agreement->toArray() : null;
        }

        // 获取系统配置中的关于信息
        $aboutConfig = Db::table('appointment_system_config')
            ->whereIn('key', ['about_us', 'contact_phone', 'app_version'])
            ->pluck('value', 'key')
            ->toArray();

        return $this->success([
            'agreements' => $agreements,
            'about_us' => $aboutConfig['about_us'] ?? '',
            'contact_phone' => $aboutConfig['contact_phone'] ?? '',
            'version' => $aboutConfig['app_version'] ?? '1.0.0',
        ]);
    }

    /**
     * 获取区域列表（占位接口）
     * GET /api/common/area
     *
     * 城市/区域列表，后续可从系统配置或数据库填充
     *
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function area(Request $request)
    {
        // 从系统配置获取区域数据
        $areaConfig = Db::table('appointment_system_config')
            ->where('key', 'area_list')
            ->value('value');

        $areas = [];
        if ($areaConfig) {
            $decoded = json_decode($areaConfig, true);
            if (is_array($decoded)) {
                $areas = $decoded;
            }
        }

        return $this->success($areas);
    }
}
