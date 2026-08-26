<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\common;

use support\Container;
use Webman\Http\Response;

/**
 * 基础控制器
 * 提供统一的响应封装、ID 编解码、分页处理等功能
 * 所有业务控制器继承此类
 */
class BaseController
{
    /**
     * 返回成功响应
     * @param mixed $data 响应数据，会自动编码其中的 id 字段
     * @param string $message 提示信息
     * @param int $code 业务码（成功 0，与小程序/admin Flutter 端约定一致）
     * @return Response
     */
    protected function success(mixed $data = null, string $message = 'success', int $code = 0): Response
    {
        if ($data !== null) {
            $data = $this->encodeIds($data);
        }

        return json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * 返回错误响应
     * @param string $message 错误提示信息
     * @param int $code 错误码
     * @param mixed $data 附加数据
     * @return Response
     */
    protected function error(string $message = 'error', int $code = 400, mixed $data = null): Response
    {
        return json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ])->withStatus($code >= 100 && $code < 600 ? $code : 400);
    }

    /**
     * 返回分页数据
     * @param mixed $paginator illuminate/pagination 分页器实例
     * @return Response
     */
    protected function paginate(mixed $paginator): Response
    {
        $items = $paginator->items();

        return json([
            'code' => 0,
            'message' => 'success',
            'data' => $this->encodeIds($items),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    /**
     * 递归编码数据中的 id/*_id 字段
     * 使用 Hashids 将内部 BIGINT ID 编码为短字符串
     * @param mixed $data 需要编码的数据（支持数组、对象、标量）
     * @return mixed 编码后的数据
     */
    protected function encodeIds(mixed $data): mixed
    {
        if (is_array($data)) {
            // 判断是否为关联数组
            if ($this->isAssoc($data)) {
                $result = [];
                foreach ($data as $key => $value) {
                    // 对 id 和以 _id 结尾的字段进行编码
                    if ($this->shouldEncode($key) && is_numeric($value) && $value > 0) {
                        $result[$key] = Container::get('hashids')->encode((int) $value);
                    } elseif (is_array($value) || is_object($value)) {
                        $result[$key] = $this->encodeIds($value);
                    } else {
                        $result[$key] = $value;
                    }
                }
                return $result;
            } else {
                // 索引数组，递归处理每个元素
                return array_map(fn($item) => $this->encodeIds($item), $data);
            }
        }

        if (is_object($data)) {
            // Eloquent 模型/集合: get_object_vars 只能取到 public 属性（如 incrementing/exists），
            // 会丢失真实数据，必须走 toArray()
            if (method_exists($data, 'toArray')) {
                return $this->encodeIds($data->toArray());
            }

            $result = [];
            foreach (get_object_vars($data) as $key => $value) {
                if ($this->shouldEncode($key) && is_numeric($value) && $value > 0) {
                    $result[$key] = Container::get('hashids')->encode((int) $value);
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
     * 递归解码数据中的 id/*_id 字段
     * 使用 Hashids 将短字符串 ID 还原为 BIGINT
     * @param mixed $data 需要解码的数据
     * @return mixed 解码后的数据
     */
    protected function decodeIds(mixed $data): mixed
    {
        if (is_array($data)) {
            if ($this->isAssoc($data)) {
                $result = [];
                foreach ($data as $key => $value) {
                    if ($this->shouldEncode($key) && is_string($value)) {
                        $result[$key] = $this->decodeId($value);
                    } elseif (is_array($value) || is_object($value)) {
                        $result[$key] = $this->decodeIds($value);
                    } else {
                        $result[$key] = $value;
                    }
                }
                return $result;
            } else {
                return array_map(fn($item) => $this->decodeIds($item), $data);
            }
        }

        if (is_object($data)) {
            $result = [];
            foreach (get_object_vars($data) as $key => $value) {
                if ($this->shouldEncode($key) && is_string($value)) {
                    $result[$key] = $this->decodeId($value);
                } elseif (is_array($value) || is_object($value)) {
                    $result[$key] = $this->decodeIds($value);
                } else {
                    $result[$key] = $value;
                }
            }
            return (object) $result;
        }

        return $data;
    }

    /**
     * 解码单个 hashid 字符串
     * @param string $hashid hashids 编码的字符串
     * @return int|null 返回解码后的整数 ID，解码失败返回 null
     */
    protected function decodeId(?string $hashid): ?int
    {
        if ($hashid === null || $hashid === '') {
            return null;
        }

        $result = Container::get('hashids')->decode($hashid);

        if (!empty($result)) {
            return (int) $result[0];
        }

        // 兼容原始数字 ID（内部调用/测试直传裸 ID），哈希解不出且为纯数字时透传
        if (ctype_digit($hashid)) {
            return (int) $hashid;
        }

        return null;
    }

    /**
     * 判断字段名是否需要编解码
     * id 或以 _id 结尾的字段视为 ID 字段
     * @param string $key 字段名
     * @return bool
     */
    private function shouldEncode(string $key): bool
    {
        return $key === 'id' || str_ends_with($key, '_id');
    }

    /**
     * 判断数组是否为关联数组
     * @param array $arr
     * @return bool
     */
    private function isAssoc(array $arr): bool
    {
        if (empty($arr)) {
            return false;
        }

        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
