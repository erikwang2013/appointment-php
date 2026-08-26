<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\admin\controller;

use app\model\AppVersion;
use support\Request;
use support\Response;

/**
 * APP 版本管理控制器
 *
 * 版本 CRUD：platform 枚举 android/ios；force_update 1=强制更新 0=非强制；
 * status 1=上架 0=下架（仅上架版本对用户端可见）。
 */
class VersionController extends BaseController
{
    private const PLATFORMS = ['android', 'ios'];

    /**
     * 版本列表（按更新时间倒序，支持 platform/status 过滤）
     */
    public function index(Request $request): Response
    {
        $page     = (int) $request->input('page', 1);
        $limit    = (int) $request->input('limit', 15);
        $platform = $request->input('platform');
        $status   = $request->input('status');

        $query = AppVersion::query();
        if ($platform !== null && $platform !== '') {
            $query->where('platform', (string) $platform);
        }
        if ($status !== null && $status !== '') {
            $query->where('status', (int) $status);
        }

        $total = $query->count();
        $list  = $query->orderBy('updated_at', 'desc')
                       ->orderBy('id', 'desc')
                       ->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->get()
                       ->map(fn($v) => $v->toArray());

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 新增版本
     */
    public function store(Request $request): Response
    {
        $validator = validator($request->all(), [
            'platform'     => 'required|string|in:android,ios',
            'version_code' => 'required|string|max:32',
            'version_name' => 'required|string|max:64',
            'force_update' => 'integer|in:0,1',
            'changelog'    => 'string',
            'download_url' => 'string|max:500',
            'status'       => 'integer|in:0,1',
        ]);
        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422);
        }

        $version = new AppVersion();
        $version->id           = AppVersion::generateId();
        $version->platform     = $request->input('platform');
        $version->version_code = (string) $request->input('version_code');
        $version->version_name = (string) $request->input('version_name');
        $version->force_update = (int) $request->input('force_update', 0);
        $version->changelog    = (string) $request->input('changelog', '');
        $version->download_url = (string) $request->input('download_url', '');
        $version->status       = (int) $request->input('status', 1);
        $version->save();

        return $this->success($version->toArray(), '创建成功');
    }

    /**
     * 编辑版本
     */
    public function update(Request $request, string $hashid): Response
    {
        $id      = $this->decodeId($hashid);
        $version = AppVersion::find($id);
        if (!$version) {
            return $this->fail('版本不存在', 404);
        }

        if ($request->input('platform') !== null) {
            $platform = (string) $request->input('platform');
            if (!in_array($platform, self::PLATFORMS, true)) {
                return $this->fail('platform 仅支持 android/ios', 422);
            }
            $version->platform = $platform;
        }
        if ($request->input('version_code') !== null) {
            $version->version_code = (string) $request->input('version_code');
        }
        if ($request->input('version_name') !== null) {
            $version->version_name = (string) $request->input('version_name');
        }
        if ($request->input('force_update') !== null) {
            $version->force_update = (int) $request->input('force_update');
        }
        if ($request->input('changelog') !== null) {
            $version->changelog = (string) $request->input('changelog');
        }
        if ($request->input('download_url') !== null) {
            $version->download_url = (string) $request->input('download_url');
        }
        if ($request->input('status') !== null) {
            $version->status = (int) $request->input('status');
        }
        $version->save();

        return $this->success($version->toArray(), '更新成功');
    }

    /**
     * 删除版本
     */
    public function destroy(Request $request, string $hashid): Response
    {
        $id      = $this->decodeId($hashid);
        $version = AppVersion::find($id);
        if (!$version) {
            return $this->fail('版本不存在', 404);
        }

        $version->delete();
        return $this->success([], '删除成功');
    }
}
