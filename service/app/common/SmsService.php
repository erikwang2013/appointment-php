<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\common;

use support\Db;
use support\Log;

/**
 * 短信服务
 *
 * 支持阿里云和腾讯云短信平台，提供验证码发送、通知发送和群发
 */
class SmsService
{
    private string $provider;
    private string $accessKey;
    private string $secretKey;
    private string $signName;
    private string $templateCode;

    // 阿里云短信 API 域名
    private const ALIYUN_HOST = 'dysmsapi.aliyuncs.com';
    private const ALIYUN_API_VERSION = '2017-05-25';

    // 腾讯云短信 API 域名
    private const TENCENT_HOST = 'sms.tencentcloudapi.com';

    public function __construct()
    {
        $configs = Db::table('appointment_system_config')
            ->where('group', 'sms')
            ->pluck('value', 'key')
            ->toArray();

        $this->provider     = $configs['provider'] ?? 'aliyun';
        $this->accessKey    = $configs['access_key'] ?? '';
        $this->secretKey    = $configs['secret_key'] ?? '';
        $this->signName     = $configs['sign_name'] ?? '';
        $this->templateCode = $configs['template_code'] ?? '';
    }

    /**
     * 发送验证码短信
     *
     * @param string $phone 手机号
     * @param string $code  验证码
     * @return array{success: bool, message: string}
     */
    public function send(string $phone, string $code): array
    {
        return $this->sendNotification($phone, $this->templateCode, ['code' => $code]);
    }

    /**
     * 发送通知短信
     *
     * @param string $phone      手机号
     * @param string $templateId 模板 ID
     * @param array  $params     模板参数
     * @return array{success: bool, message: string}
     */
    public function sendNotification(string $phone, string $templateId, array $params = []): array
    {
        if (empty($phone) || empty($templateId)) {
            return ['success' => false, 'message' => '手机号或模板ID不能为空'];
        }

        try {
            return match ($this->provider) {
                'aliyun'  => $this->sendAliyun($phone, $templateId, $params),
                'tencent' => $this->sendTencent($phone, $templateId, $params),
                default   => ['success' => false, 'message' => '不支持的短信服务商: ' . $this->provider],
            };
        } catch (\Throwable $e) {
            Log::error('[SmsService] send error: ' . $e->getMessage());
            return ['success' => false, 'message' => '短信发送失败: ' . $e->getMessage()];
        }
    }

    /**
     * 批量发送短信
     *
     * @param array  $phones     手机号列表
     * @param string $templateId 模板 ID
     * @param array  $params     模板参数
     * @return array{success: int, failed: int, results: array}
     */
    public function sendBatch(array $phones, string $templateId, array $params = []): array
    {
        $success = 0;
        $failed  = 0;
        $results = [];

        foreach ($phones as $phone) {
            $result = $this->sendNotification($phone, $templateId, $params);
            $results[$phone] = $result;

            if ($result['success']) {
                $success++;
            } else {
                $failed++;
            }
        }

        return [
            'success' => $success,
            'failed'  => $failed,
            'results' => $results,
        ];
    }

    // ── 阿里云短信 ──

    /**
     * 阿里云短信发送
     *
     * 使用阿里云 API 签名 V3 规范
     */
    private function sendAliyun(string $phone, string $templateId, array $params): array
    {
        $templateParam = !empty($params) ? json_encode($params, JSON_UNESCAPED_UNICODE) : '';

        $queryParams = [
            'PhoneNumbers'     => $phone,
            'SignName'         => $this->signName,
            'TemplateCode'     => $templateId,
            'TemplateParam'    => $templateParam,
            'AccessKeyId'      => $this->accessKey,
            'SignatureMethod'  => 'HMAC-SHA1',
            'SignatureNonce'   => $this->generateNonce(),
            'SignatureVersion' => '1.0',
            'Timestamp'        => gmdate('Y-m-d\TH:i:s\Z'),
            'Format'           => 'JSON',
            'Action'           => 'SendSms',
            'Version'          => self::ALIYUN_API_VERSION,
        ];

        // 按字典序排序
        ksort($queryParams);

        // 构造签名字符串: GET&%2F&<URL编码的queryString>
        $queryString = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
        $stringToSign = 'GET&%2F&' . urlencode($queryString);
        $signature    = base64_encode(hash_hmac('sha1', $stringToSign, $this->secretKey . '&', true));

        $queryParams['Signature'] = $signature;

        $url = 'https://' . self::ALIYUN_HOST . '/?' . http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

        $response = $this->httpGet($url);

        if (empty($response)) {
            return ['success' => false, 'message' => '阿里云短信接口无响应'];
        }

        $result = json_decode($response, true);

        if (!is_array($result)) {
            return ['success' => false, 'message' => '阿里云短信响应解析失败'];
        }

        $code = $result['Code'] ?? '';

        if ($code === 'OK') {
            return ['success' => true, 'message' => '短信发送成功'];
        }

        $message = $result['Message'] ?? '未知错误';

        // 限流处理
        if (in_array($code, ['isv.BUSINESS_LIMIT_CONTROL', 'isv.DAY_LIMIT_CONTROL'], true)) {
            Log::warning('[SmsService aliyun] rate limited: ' . $message);
        }

        return ['success' => false, 'message' => '阿里云短信发送失败: ' . $message];
    }

    // ── 腾讯云短信 ──

    /**
     * 腾讯云短信发送
     *
     * 使用腾讯云 API 签名 V3 规范
     */
    private function sendTencent(string $phone, string $templateId, array $params): array
    {
        $service    = 'sms';
        $action     = 'SendSms';
        $version    = '2021-01-11';
        $region     = 'ap-guangzhou';
        $timestamp  = (string) time();
        $date       = gmdate('Y-m-d');

        // 规范化手机号格式（E.164）
        if (!str_starts_with($phone, '+86')) {
            $phone = '+86' . $phone;
        }

        $payload = json_encode([
            'PhoneNumberSet'   => [$phone],
            'SmsSdkAppId'      => $this->accessKey,
            'TemplateId'       => $templateId,
            'SignName'         => $this->signName,
            'TemplateParamSet' => array_values($params),
        ], JSON_UNESCAPED_UNICODE);

        // 构造签名
        $hashedPayload        = hash('sha256', $payload);
        $canonicalRequest     = "POST\n/\n\ncontent-type:application/json\nhost:" . self::TENCENT_HOST . "\n\ncontent-type;host\n{$hashedPayload}";
        $hashedCanonicalRequest = hash('sha256', $canonicalRequest);
        $stringToSign         = "TC3-HMAC-SHA256\n{$timestamp}\n{$date}/{$service}/tc3_request\n{$hashedCanonicalRequest}";

        $secretDate    = hash_hmac('sha256', $date, 'TC3' . $this->secretKey, true);
        $secretService = hash_hmac('sha256', $service, $secretDate, true);
        $secretSigning = hash_hmac('sha256', 'tc3_request', $secretService, true);
        $signature     = hash_hmac('sha256', $stringToSign, $secretSigning);

        $authorization = "TC3-HMAC-SHA256 Credential={$this->accessKey}/{$date}/{$service}/tc3_request,"
                       . "SignedHeaders=content-type;host,Signature={$signature}";

        $headers = [
            'Content-Type: application/json',
            'Host: ' . self::TENCENT_HOST,
            'X-TC-Action: ' . $action,
            'X-TC-Version: ' . $version,
            'X-TC-Timestamp: ' . $timestamp,
            'X-TC-Region: ' . $region,
            'Authorization: ' . $authorization,
        ];

        $response = $this->httpPost('https://' . self::TENCENT_HOST, $payload, $headers);

        if (empty($response)) {
            return ['success' => false, 'message' => '腾讯云短信接口无响应'];
        }

        $result = json_decode($response, true);

        if (!is_array($result)) {
            return ['success' => false, 'message' => '腾讯云短信响应解析失败'];
        }

        $responseData = $result['Response'] ?? [];

        if (!empty($responseData['Error'])) {
            $errorCode = $responseData['Error']['Code'] ?? '';
            $errorMsg  = $responseData['Error']['Message'] ?? '未知错误';

            // 限流处理
            if (str_contains($errorCode, 'LimitExceeded')) {
                Log::warning('[SmsService tencent] rate limited: ' . $errorMsg);
            }

            return ['success' => false, 'message' => '腾讯云短信发送失败: ' . $errorMsg];
        }

        $sendStatusSet = $responseData['SendStatusSet'] ?? [];
        if (!empty($sendStatusSet)) {
            $status = $sendStatusSet[0];
            if (($status['Code'] ?? '') === 'Ok') {
                return ['success' => true, 'message' => '短信发送成功'];
            }
            return ['success' => false, 'message' => '短信发送失败: ' . ($status['Message'] ?? '未知')];
        }

        return ['success' => true, 'message' => '短信发送成功'];
    }

    // ── HTTP 请求工具 ──

    /**
     * HTTP GET 请求
     */
    private function httpGet(string $url): string
    {
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);

            $response = curl_exec($ch);
            $error    = curl_error($ch);
            curl_close($ch);

            if ($error) {
                Log::error('[SmsService] cURL GET error: ' . $error);
                return '';
            }

            return is_string($response) ? $response : '';
        } catch (\Throwable $e) {
            Log::error('[SmsService] httpGet exception: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * HTTP POST 请求
     */
    private function httpPost(string $url, string $body, array $headers = []): string
    {
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);

            $response = curl_exec($ch);
            $error    = curl_error($ch);
            curl_close($ch);

            if ($error) {
                Log::error('[SmsService] cURL POST error: ' . $error);
                return '';
            }

            return is_string($response) ? $response : '';
        } catch (\Throwable $e) {
            Log::error('[SmsService] httpPost exception: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * 生成随机串
     */
    private function generateNonce(): string
    {
        return bin2hex(random_bytes(16));
    }
}
