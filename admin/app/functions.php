<?php

declare(strict_types=1);
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

use Webman\Validation\Validator;

if (!function_exists('validator')) {
    /**
     * 创建验证器（webman/validation 兼容函数）
     * @param array $data 待验证数据
     * @param array|null $rules 验证规则
     * @param array|null $messages 自定义错误消息
     * @param array|null $attributes 字段别名
     * @return Validator
     */
    function validator(array $data, ?array $rules = null, ?array $messages = null, ?array $attributes = null): Validator
    {
        return Validator::make($data, $rules, $messages, $attributes);
    }
}
