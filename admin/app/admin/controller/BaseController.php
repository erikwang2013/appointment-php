<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\common\HashidsService;
use app\common\SnowflakeService;
use app\model\AdminUser;
use support\Redis;
use support\Request;
use support\Response;

/**
 * 管理端基础控制器
 * 提供统一响应格式、ID编解码、snowflake ID 生成
 */
class BaseController
{
    /**
     * 成功响应
     */
    protected function success($data = [], string $message = 'success', int $code = 0): Response
    {
        return json(['code' => $code, 'message' => $message, 'data' => $data]);
    }

    /**
     * 失败响应
     */
    protected function fail(string $message = 'fail', int $code = 500, $data = []): Response
    {
        return json(['code' => $code, 'message' => $message, 'data' => $data]);
    }

    /**
     * 将模型 ID 编码为 hashid 字符串
     */
    protected function encodeId(int $id): string
    {
        return HashidsService::encode($id);
    }

    /**
     * 将 hashid 字符串解码为原始 ID
     */
    protected function decodeId(string $hashid): int
    {
        return HashidsService::decode($hashid);
    }

    /**
     * 批量编码数组中的 ID 字段
     */
    protected function encodeIds(array $data, array $idFields = ['id']): array
    {
        return HashidsService::encodeIds($data, $idFields);
    }

    /**
     * 生成新的 snowflake ID
     */
    protected function generateId(): int
    {
        return SnowflakeService::generate();
    }

    /**
     * 清除 service 端读缓存（svc: 前缀）
     * 管理端对缓存数据（服务/分类/轮播/公告/FAQ/技师/套餐/活动等）写操作后调用，
     * 保证读多写少的接口缓存及时失效
     */
    protected function clearSvcCache(): void
    {
        foreach (Redis::keys('svc:*') as $key) {
            Redis::del($key);
        }
    }

    /**
     * 二次确认 — 验证当前登录用户密码
     * 敏感操作（删除、导出等）调用此方法确认身份
     *
     * @param int $adminId 当前登录用户 ID
     * @param string $password 用户输入的密码
     * @return string|null 错误消息，null 表示验证通过
     */
    protected function confirmPassword(int $adminId, string $password, Request $request): ?string
    {
        if (empty($password)) {
            return trans('messages.password_confirm_required');
        }

        $admin = AdminUser::find($adminId);
        if (!$admin || !password_verify($password, $admin->password)) {
            return trans('messages.password_confirm_failed');
        }

        return null; // 验证通过
    }
}
