<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\common;

use app\model\PushLog;
use support\Db;
use support\Log;

/**
 * APP 推送服务（厂商占位层，配置驱动）
 *
 * 系统通知链：站内通知（appointment_notification）+ 微信订阅消息（SCENE_*）+ APP 推送（本服务）。
 * 本服务对接极光（jpush）/ 个推（getui）/ UniPush 等厂商，配置读 appointment_system_config
 * group=push：
 * - push.enabled：总开关，0=关闭（pushToUser 静默降级返回 false 仅记日志），1=启用；
 * - push.provider：厂商标识（jpush/getui/placeholder），空表示未配置凭据。
 *
 * 无凭据时（本环境）仅构造推送请求结构并写 appointment_push_log 便于排查，
 * 不实际调用厂商 SDK——真实对接在 pushToUser 的 TODO 处按 provider 分发。
 * 调用方约定：try/catch 包裹本服务调用，失败仅记日志，绝不影响主流程。
 */
class AppPushService
{
    private const CONFIG_GROUP = 'push';

    /** 日志状态：已发送 */
    public const STATUS_SENT = 'sent';
    /** 日志状态：跳过（未启用/参数非法） */
    public const STATUS_SKIPPED = 'skipped';

    /**
     * 推送总开关是否启用
     */
    public static function isEnabled(): bool
    {
        try {
            return (int) (self::config()['enabled'] ?? 0) === 1;
        } catch (\Throwable $e) {
            Log::warning('[AppPush] config read failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 向用户推送 APP 通知
     *
     * 未启用（push.enabled != 1）→ 记降级日志返回 false，不写记录；
     * 启用 → 构造推送请求结构（平台/标题/内容/自定义字段）记日志、写
     * appointment_push_log 并返回 true。
     * status 语义：有厂商凭据（provider 非空且非 placeholder）→ sent（已发送）；
     * 凭据缺失占位 → skipped（仅构造与记录，未实际发送）。
     *
     * TODO: 真实厂商 SDK 对接（本环境无推送凭据，不实际发送）。按 provider 分发：
     *   jpush → 极光 push（JPush::pushToUser）；getui → 个推 push；
     *   placeholder/空 → 仅构造与记录（status=skipped）。SDK 调用失败应抛出
     *   或返回 false，由调用方 try/catch 兜底，不影响主流程。
     *
     * @param int    $userId  目标用户 ID
     * @param string $title   推送标题
     * @param string $content 推送内容
     * @param array  $payload 自定义字段（透传业务数据，如 order_id/order_no/type）
     * @return bool 是否已进入推送链路（未启用或参数非法返回 false）
     */
    public static function pushToUser(int $userId, string $title, string $content, array $payload = []): bool
    {
        if (!self::isEnabled()) {
            Log::info('[AppPush] push.enabled 未启用，跳过 APP 推送 user=' . $userId . ' title=' . $title);
            return false;
        }

        // 系统边界校验：非法参数直接拒绝（不抛异常，调用方无需感知）
        if ($userId <= 0 || $title === '' || $content === '') {
            Log::warning('[AppPush] 非法推送参数 user=' . $userId . ' title=' . $title);
            return false;
        }

        $provider = (string) (self::config()['provider'] ?? '');

        // 推送请求结构（厂商 SDK 对接时映射为各厂商请求体）
        $structure = [
            'platform' => 'app',
            'provider' => $provider,
            'title'    => $title,
            'content'  => $content,
            'payload'  => $payload,
            'sent_at'  => date('Y-m-d H:i:s'),
        ];
        Log::info('[AppPush] 推送 user=' . $userId . ' ' . json_encode($structure, JSON_UNESCAPED_UNICODE));

        // 无厂商凭据（placeholder/空）→ 未实际发送，status=skipped；有凭据走真实链路时才是 sent
        $status = in_array($provider, ['', 'placeholder'], true) ? self::STATUS_SKIPPED : self::STATUS_SENT;

        // 记录落库失败不影响推送结果（推送链路已走完，仅排查日志缺失）
        try {
            PushLog::create([
                'user_id'  => (string) $userId,
                'title'    => $title,
                'content'  => $content,
                'payload'  => $payload,
                'status'   => $status,
                'provider' => $provider,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[AppPush] push log persist failed: ' . $e->getMessage()
                . ', user=' . $userId . ' title=' . $title);
        }

        return true;
    }

    /**
     * 读取推送配置（group=push 的 key => value）
     */
    private static function config(): array
    {
        return Db::table('appointment_system_config')
            ->where('group', self::CONFIG_GROUP)
            ->pluck('value', 'key')
            ->toArray();
    }
}
