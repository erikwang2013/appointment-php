<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
declare(strict_types=1);

// 全局辅助函数

use support\Db;

/**
 * 当前时间（Laravel 兼容 helper）
 *
 * 本运行时不加载 illuminate/foundation，全局 now() 缺失，
 * 此处补标准实现，供订单取消/退款/核销等流程使用。
 * @return Carbon\Carbon
 */
function now(?string $modifier = null): Carbon\Carbon
{
    return Carbon\Carbon::now($modifier);
}

/**
 * 生成订单号
 * 格式: YmdHis + 4位随机数字，例如 202605261530451234
 */
function generate_order_no(): string
{
    return date('YmdHis') . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * 生成财务流水号
 * @param string $prefix 前缀，默认 'FN'
 * @return string 格式: 前缀 + YmdHis + 4位随机数字，例如 FN202605261530451234
 */
function generate_finance_no(string $prefix = 'FN'): string
{
    return $prefix . date('YmdHis') . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
}
