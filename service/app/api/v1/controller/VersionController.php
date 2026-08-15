<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\model\AppVersion;
use Webman\Http\Request;

/**
 * APP 版本检查控制器（公开接口，无需登录，登录前即可检查更新）
 *
 * GET /api/app/version?platform=android  返回最新上架版本
 * platform 仅支持 android/ios，非法 422；无上架版本返回空对象。
 */
class VersionController extends BaseController
{
    private const PLATFORMS = ['android', 'ios'];

    /**
     * 最新上架版本（按更新时间/ID 取最新）
     * GET /api/app/version?platform=android
     */
    public function index(Request $request)
    {
        $platform = (string) $request->get('platform', '');
        if (!in_array($platform, self::PLATFORMS, true)) {
            return $this->error('platform 仅支持 android/ios', 422);
        }

        $version = AppVersion::where('platform', $platform)
            ->where('status', 1)
            ->orderBy('updated_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if (!$version) {
            return $this->success((object) [], '暂无新版本');
        }

        return $this->success([
            'id'           => (string) $version->id,
            'platform'     => (string) $version->platform,
            'version_code' => (string) $version->version_code,
            'version_name' => (string) $version->version_name,
            'force_update' => (int) $version->force_update,
            'changelog'    => (string) $version->changelog,
            'download_url' => (string) $version->download_url,
        ]);
    }
}
