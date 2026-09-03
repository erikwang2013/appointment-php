<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\common;

/**
 * 金额精确运算（bcmath 封装）
 *
 * 金额一律 string 精度运算，禁止浮点 + - * / 直接参与金额计算与比较。
 * 链式运算内部保留 scale=4 防半分之差累计，仅落库/落响应前 round(...,2)。
 * 对外类型契约：本类输出恒 string；还原 number 由模型 casts / 显式 (float) 位点负责。
 */
final class Money
{
    public static function add(string|int|float $a, string|int|float $b, int $scale = 4): string
    {
        return bcadd((string)$a, (string)$b, $scale);
    }

    public static function sub(string|int|float $a, string|int|float $b, int $scale = 4): string
    {
        return bcsub((string)$a, (string)$b, $scale);
    }

    public static function mul(string|int|float $a, string|int|float $b, int $scale = 4): string
    {
        return bcmul((string)$a, (string)$b, $scale);
    }

    /**
     * @throws \InvalidArgumentException 除数为 0 时抛出
     */
    public static function div(string|int|float $a, string|int|float $b, int $scale = 4): string
    {
        if ((float)$b == 0.0) {
            throw new \InvalidArgumentException('Money::div 除数为 0');
        }
        return bcdiv((string)$a, (string)$b, $scale);
    }

    /**
     * 金额舍入到 scale 位（半进位远离零，与 round() 默认一致；PHP 8.4 才有 bcround，此处自实现）
     */
    public static function round(string|int|float $n, int $scale = 2): string
    {
        $s = (string)$n;
        $half = $scale > 0 ? '0.' . str_repeat('0', $scale) . '5' : '0.5';
        return bcadd($s, ($s[0] === '-' ? '-' : '') . $half, $scale);
    }

    /**
     * 金额比较（替代一切 == / < / > 浮点比较），相等返回 0
     */
    public static function cmp(string|int|float $a, string|int|float $b, int $scale = 2): int
    {
        return bccomp((string)$a, (string)$b, $scale);
    }

    /**
     * 元转分（round 到分后 ×100 截断），替代 (int) round((float)$x * 100)
     */
    public static function toFen(string|int|float $y): int
    {
        return (int) bcmul(self::round((string)$y, 2), '100', 0);
    }
}
