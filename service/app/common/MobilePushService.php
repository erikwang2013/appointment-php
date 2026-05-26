<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\common;

use app\model\UserDevice;
use support\Db;
use support\Log;

/**
 * 移动推送服务
 *
 * 支持 iOS (APNs via HTTP/2) 和 Android (FCM via HTTP v1) 推送。
 * 推送配置从 erik_system_config 表读取。
 *
 * 使用示例：
 *   $push = new MobilePushService();
 *   $push->send($userId, '订单提醒', '您的订单已确认');
 */
class MobilePushService
{
    // FCM HTTP v1 API 地址
    private const FCM_URL = 'https://fcm.googleapis.com/v1/projects/{project_id}/messages:send';

    // APNs HTTP/2 推送地址 (开发环境)
    private const APNS_DEV_URL = 'https://api.sandbox.push.apple.com/3/device/';

    // APNs HTTP/2 推送地址 (生产环境)
    private const APNS_PROD_URL = 'https://api.push.apple.com/3/device/';

    private string $fcmServerKey;
    private string $apnsKeyId;
    private string $apnsTeamId;
    private string $apnsBundleId;
    private bool $apnsProduction;
    private ?string $apnsPrivateKey = null;

    public function __construct()
    {
        $configs = Db::table('erik_system_config')
            ->where('group', 'push')
            ->pluck('value', 'key')
            ->toArray();

        $this->fcmServerKey   = $configs['fcm_server_key'] ?? '';
        $this->apnsKeyId      = $configs['apns_key_id'] ?? '';
        $this->apnsTeamId     = $configs['apns_team_id'] ?? '';
        $this->apnsBundleId   = $configs['apns_bundle_id'] ?? '';
        $this->apnsProduction = (bool)($configs['apns_production'] ?? true);
        $this->apnsPrivateKey = $configs['apns_private_key'] ?? null;
    }

    /**
     * 发送推送通知（自动检测设备类型）
     *
     * @param int    $userId 用户ID
     * @param string $title  推送标题
     * @param string $body   推送内容
     * @param array  $extra  附加数据（如 order_id）
     * @return bool
     */
    public function send(int $userId, string $title, string $body, array $extra = []): bool
    {
        if (empty($userId)) {
            return false;
        }

        $devices = UserDevice::where('user_id', (string)$userId)->get();

        if ($devices->isEmpty()) {
            return false;
        }

        $success = false;

        foreach ($devices as $device) {
            try {
                if ($device->platform === 'ios') {
                    $result = $this->sendIOS($device->device_token, $title, $body, $extra);
                } else {
                    $result = $this->sendAndroid($device->device_token, $title, $body, $extra);
                }
                if ($result) {
                    $success = true;
                }
            } catch (\Throwable $e) {
                Log::error('[MobilePushService] Push error for device '
                    . ($device->device_token ?? 'unknown') . ': ' . $e->getMessage());
            }
        }

        return $success;
    }

    /**
     * 发送 iOS 推送 (APNs HTTP/2)
     *
     * @param string $deviceToken 设备 token
     * @param string $title       推送标题
     * @param string $body        推送内容
     * @param array  $extra       附加数据
     * @return bool
     */
    public function sendIOS(string $deviceToken, string $title, string $body, array $extra = []): bool
    {
        if (empty($deviceToken) || empty($this->apnsKeyId)) {
            return false;
        }

        $url = ($this->apnsProduction ? self::APNS_PROD_URL : self::APNS_DEV_URL) . $deviceToken;

        $payload = json_encode([
            'aps' => [
                'alert' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'sound' => 'default',
                'badge' => 1,
                'data'  => $extra,
            ],
        ], JSON_UNESCAPED_UNICODE);

        if ($payload === false) {
            Log::error('[MobilePushService] APNs payload encode error: ' . json_last_error_msg());
            return false;
        }

        $jwt = $this->generateAPNsJwt();

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: bearer ' . $jwt,
                    'apns-topic: ' . $this->apnsBundleId,
                    'apns-push-type: alert',
                    'apns-priority: 10',
                ],
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2_0,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error    = curl_error($ch);
            curl_close($ch);

            if ($error) {
                Log::error('[MobilePushService] APNs cURL error: ' . $error);
                return false;
            }

            if ($httpCode === 200) {
                return true;
            }

            // 410 = device token is no longer active, should remove from DB
            if ($httpCode === 410) {
                $this->removeInvalidDevice($deviceToken);
            }

            Log::warning('[MobilePushService] APNs HTTP ' . $httpCode . ': ' . ($response ?: ''));
            return false;
        } catch (\Throwable $e) {
            Log::error('[MobilePushService] APNs exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 发送 Android 推送 (FCM HTTP v1)
     *
     * @param string $deviceToken 设备 token
     * @param string $title       推送标题
     * @param string $body        推送内容
     * @param array  $extra       附加数据
     * @return bool
     */
    public function sendAndroid(string $deviceToken, string $title, string $body, array $extra = []): bool
    {
        if (empty($deviceToken) || empty($this->fcmServerKey)) {
            return false;
        }

        // 从 server key 中提取 project_id（格式：AIza... 或 projects/<id>）
        // 如果 server_key 是完整路径则直接使用
        $url = str_replace('{project_id}', $this->fcmServerKey, self::FCM_URL);

        // 当 server_key 是 API 密钥时，使用旧版 HTTP v1 API 兼容方式
        // 使用 OAuth 2.0 access token 模式，或直接用 server key
        $payload = json_encode([
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'data'  => array_combine(
                    array_map(fn($k) => (string)$k, array_keys($extra)),
                    array_map(fn($v) => (string)$v, array_values($extra))
                ) ?: new \stdClass(),
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'channel_id' => 'default',
                        'sound'      => 'default',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1,
                        ],
                    ],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE);

        if ($payload === false) {
            Log::error('[MobilePushService] FCM payload encode error: ' . json_last_error_msg());
            return false;
        }

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->fcmServerKey,
                ],
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error    = curl_error($ch);
            curl_close($ch);

            if ($error) {
                Log::error('[MobilePushService] FCM cURL error: ' . $error);
                return false;
            }

            if ($httpCode === 200) {
                $result = json_decode($response, true);
                if (is_array($result) && isset($result['name'])) {
                    return true;
                }
                return true; // 即使解析失败，200 也算成功
            }

            // 404 = unregistered device token
            if ($httpCode === 404) {
                $this->removeInvalidDevice($deviceToken);
            }

            Log::warning('[MobilePushService] FCM HTTP ' . $httpCode . ': ' . ($response ?: ''));
            return false;
        } catch (\Throwable $e) {
            Log::error('[MobilePushService] FCM exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 生成 APNs JWT 认证令牌
     *
     * Uses ES256 JWT signed with the APNs private key.
     *
     * @return string
     */
    private function generateAPNsJwt(): string
    {
        $header = [
            'alg' => 'ES256',
            'kid' => $this->apnsKeyId,
        ];

        $payload = [
            'iss' => $this->apnsTeamId,
            'iat' => time(),
        ];

        $headerB64  = $this->urlsafeBase64Encode(json_encode($header));
        $payloadB64 = $this->urlsafeBase64Encode(json_encode($payload));
        $signingInput = $headerB64 . '.' . $payloadB64;

        $privateKey = $this->apnsPrivateKey;
        if (empty($privateKey)) {
            Log::warning('[MobilePushService] APNs private key not configured');
            return $headerB64 . '.' . $payloadB64 . '.';
        }

        $signature = '';
        if (function_exists('openssl_sign')) {
            openssl_sign($signingInput, $signature, $privateKey, 'sha256');
        } else {
            Log::warning('[MobilePushService] openssl_sign not available for APNs JWT');
        }

        return $signingInput . '.' . $this->urlsafeBase64Encode($signature);
    }

    /**
     * URL 安全的 Base64 编码
     */
    private function urlsafeBase64Encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * 移除无效的设备注册（token 失效）
     *
     * @param string $token
     */
    private function removeInvalidDevice(string $token): void
    {
        try {
            UserDevice::where('device_token', $token)->delete();
            Log::info('[MobilePushService] Removed invalid device token: ' . substr($token, 0, 10) . '...');
        } catch (\Throwable $e) {
            Log::error('[MobilePushService] Failed to remove device: ' . $e->getMessage());
        }
    }
}
