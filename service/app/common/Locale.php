<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\common;

use support\Log;

/**
 * 多语言翻译助手
 *
 * 根据 Accept-Language 请求头自动识别用户语言偏好，
 * 从 resource/translations/ 目录加载对应翻译文件。
 *
 * 使用示例：
 *   Locale::trans('login_success');          // 登录成功 / Login successful
 *   Locale::trans('order_not_found', [], $request); // 按请求头切换语言
 */
class Locale
{
    /**
     * 当前语言（zh_CN / en）
     */
    private static string $locale = 'zh_CN';

    /**
     * 已加载的翻译数据
     * @var array<string, array<string, string>>
     */
    private static array $translations = [];

    /**
     * 当前请求的语言（线程级缓存）
     */
    private static ?string $requestLocale = null;

    /**
     * 从请求中检测语言并设置
     *
     * @param object|null $request webman Request 对象
     */
    public static function detect(?object $request = null): void
    {
        if ($request !== null) {
            $acceptLang = $request->header('Accept-Language', '');
            if (!empty($acceptLang)) {
                // 解析 Accept-Language: zh-CN,zh;q=0.9,en;q=0.8
                $locale = self::parseAcceptLanguage($acceptLang);
                if ($locale !== null) {
                    self::$requestLocale = $locale;
                    return;
                }
            }
        }
        self::$requestLocale = config('translation.locale', 'zh_CN');
    }

    /**
     * 获取翻译文本
     *
     * @param string $key     翻译键名
     * @param array  $replace 占位替换（暂未实现格式化）
     * @param object|null $request 请求对象，用于检测语言
     * @return string
     */
    public static function trans(string $key, array $replace = [], ?object $request = null): string
    {
        $locale = self::resolveLocale($request);
        self::loadMessages($locale);

        $message = self::$translations[$locale][$key] ?? null;

        if ($message === null) {
            // 回退到默认语言
            $fallback = config('translation.fallback_locale', ['zh_CN']);
            foreach ((array)$fallback as $fallbackLocale) {
                if ($fallbackLocale === $locale) {
                    continue;
                }
                self::loadMessages($fallbackLocale);
                $message = self::$translations[$fallbackLocale][$key] ?? null;
                if ($message !== null) {
                    break;
                }
            }
        }

        return $message ?? $key;
    }

    /**
     * 翻译并替换占位符
     *
     * @param string $key
     * @param array  $replace  ['key' => 'value']
     * @param object|null $request
     * @return string
     */
    public static function __(string $key, array $replace = [], ?object $request = null): string
    {
        $message = self::trans($key, $replace, $request);
        foreach ($replace as $search => $value) {
            $message = str_replace(':' . $search, (string)$value, $message);
        }
        return $message;
    }

    /**
     * 获取当前语言
     *
     * @param object|null $request
     * @return string
     */
    public static function getLocale(?object $request = null): string
    {
        return self::resolveLocale($request);
    }

    /**
     * 加载指定语言的翻译文件
     *
     * @param string $locale
     */
    private static function loadMessages(string $locale): void
    {
        if (isset(self::$translations[$locale])) {
            return;
        }

        $path = base_path() . '/resource/translations/' . $locale . '/messages.php';
        if (file_exists($path)) {
            $messages = include $path;
            if (is_array($messages)) {
                self::$translations[$locale] = $messages;
                return;
            }
        }

        self::$translations[$locale] = [];
        Log::warning('[Locale] Translation file not found: ' . $path);
    }

    /**
     * 解析 Accept-Language 头
     *
     * @param string $acceptLang
     * @return string|null
     */
    private static function parseAcceptLanguage(string $acceptLang): ?string
    {
        // 提取第一个语言标签
        $parts = explode(',', $acceptLang);
        $first = trim($parts[0]);
        // 去除质量因子
        $first = explode(';', $first)[0];
        $first = trim($first);

        // 标准化格式：zh-CN -> zh_CN, en-US -> en
        $locale = str_replace('-', '_', $first);

        // 匹配支持的语言
        return match ($locale) {
            'zh_CN', 'zh_Hans', 'zh' => 'zh_CN',
            'en', 'en_US', 'en_GB'  => 'en',
            default                 => null,
        };
    }

    /**
     * 解析当前应使用的语言
     *
     * @param object|null $request
     * @return string
     */
    private static function resolveLocale(?object $request): string
    {
        if ($request !== null) {
            self::detect($request);
        }
        return self::$requestLocale ?? self::$locale;
    }
}
