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
     * 成功响应 — data 自动递归编码 id/*_id 字段（与 service 端 BaseController 行为一致）
     */
    protected function success($data = [], string $message = 'success', int $code = 0): Response
    {
        return json(['code' => $code, 'message' => $message, 'data' => $this->encodeIds($data)]);
    }

    /**
     * 失败响应 — 与 service 端 error() 对齐，不自动编码
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
     * 递归编码数据中的 id/*_id 字段（与 service 端 BaseController::encodeIds 同规则）
     * 支持数组、Eloquent 模型/集合、普通对象；已编码字符串与 0/负数/非数字不变
     * @param mixed $data 需要编码的数据
     * @return mixed 编码后的数据
     */
    protected function encodeIds(mixed $data): mixed
    {
        if (is_array($data)) {
            if (array_keys($data) !== range(0, count($data) - 1)) {
                // 关联数组
                $result = [];
                foreach ($data as $key => $value) {
                    if ($this->shouldEncode($key) && is_numeric($value) && $value > 0) {
                        $result[$key] = HashidsService::encode((int) $value);
                    } elseif (is_array($value) || is_object($value)) {
                        $result[$key] = $this->encodeIds($value);
                    } else {
                        $result[$key] = $value;
                    }
                }
                return $result;
            }
            // 索引数组，递归处理每个元素
            return array_map(fn($item) => $this->encodeIds($item), $data);
        }

        if (is_object($data)) {
            // Eloquent 模型/集合: 走 toArray() 保证拿到真实数据
            if (method_exists($data, 'toArray')) {
                return $this->encodeIds($data->toArray());
            }
            $result = [];
            foreach (get_object_vars($data) as $key => $value) {
                if ($this->shouldEncode($key) && is_numeric($value) && $value > 0) {
                    $result[$key] = HashidsService::encode((int) $value);
                } elseif (is_array($value) || is_object($value)) {
                    $result[$key] = $this->encodeIds($value);
                } else {
                    $result[$key] = $value;
                }
            }
            return (object) $result;
        }

        return $data;
    }

    /**
     * 判断字段名是否为 ID 字段（id 或以 _id 结尾）
     */
    private function shouldEncode(int|string $key): bool
    {
        return is_string($key) && ($key === 'id' || str_ends_with($key, '_id'));
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
        // P5: Redis::keys('svc:*') 为 O(N) 全量扫描，会阻塞 Redis 单线程；
        // 改为 SCAN 游标迭代删除（增量、不阻塞）。
        // 客户端为 phpredis，经 Illuminate PhpRedisConnection::scan 包装后返回
        // [下一游标, 键列表]，迭代完成（游标回 0 且本批无键）时返回 false。
        $cursor = null;
        do {
            $result = Redis::connection()->scan($cursor, ['match' => 'svc:*', 'count' => 100]);
            if ($result === false) {
                break; // 迭代完成
            }
            [$cursor, $keys] = $result;
            foreach ($keys as $key) {
                Redis::del($key);
            }
        } while ($cursor !== 0);
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
