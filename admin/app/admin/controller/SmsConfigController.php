<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\SystemConfig;
use support\Log;
use support\Request;
use support\Response;

/**
 * 短信配置管理
 *
 * 管理短信服务商配置（阿里云/腾讯云）、签名、模板等
 */
class SmsConfigController extends BaseController
{
    /**
     * 获取当前短信配置
     *
     * @Apidoc\Title("获取短信配置")
     * @Apidoc\Group("sms-config")
     * @Apidoc\Url("/admin/sms-config")
     * @Apidoc\Desc("获取当前短信服务商配置信息")
     * @Apidoc\Returned("provider", type="string", desc="短信服务商: aliyun/tencent")
     * @Apidoc\Returned("access_key", type="string", desc="AccessKey")
     * @Apidoc\Returned("secret_key", type="string", desc="SecretKey（脱敏显示）")
     * @Apidoc\Returned("sign_name", type="string", desc="短信签名")
     * @Apidoc\Returned("template_code", type="string", desc="默认模板ID")
     */
    public function show(Request $request): Response
    {
        $configs = SystemConfig::where('group', 'sms')->pluck('value', 'key')->toArray();

        $secretKey = $configs['secret_key'] ?? '';

        $data = [
            'provider'      => $configs['provider'] ?? 'aliyun',
            'access_key'    => $configs['access_key'] ?? '',
            'secret_key'    => $this->maskSensitive($secretKey),
            'sign_name'     => $configs['sign_name'] ?? '',
            'template_code' => $configs['template_code'] ?? '',
        ];

        return $this->success($data);
    }

    /**
     * 更新短信配置
     *
     * @Apidoc\Title("更新短信配置")
     * @Apidoc\Group("sms-config")
     * @Apidoc\Method("PUT")
     * @Apidoc\Url("/admin/sms-config")
     * @Apidoc\Desc("更新短信服务商配置")
     * @Apidoc\Param("provider", type="string", require=false, desc="短信服务商: aliyun/tencent")
     * @Apidoc\Param("access_key", type="string", require=false, desc="AccessKey/AppId")
     * @Apidoc\Param("secret_key", type="string", require=false, desc="SecretKey")
     * @Apidoc\Param("sign_name", type="string", require=false, desc="短信签名")
     * @Apidoc\Param("template_code", type="string", require=false, desc="默认模板ID（验证码类）")
     */
    public function update(Request $request): Response
    {
        $fields = [
            'provider'      => $request->input('provider'),
            'access_key'    => $request->input('access_key'),
            'secret_key'    => $request->input('secret_key'),
            'sign_name'     => $request->input('sign_name'),
            'template_code' => $request->input('template_code'),
        ];

        // 验证 provider 值
        $provider = $fields['provider'];
        if ($provider !== null && !in_array($provider, ['aliyun', 'tencent'], true)) {
            return $this->fail('不支持的短信服务商，可选: aliyun, tencent', 422);
        }

        foreach ($fields as $key => $value) {
            if ($value === null) {
                continue;
            }

            $config = SystemConfig::where('group', 'sms')
                ->where('key', $key)
                ->first();

            if ($config) {
                $config->value = (string) $value;
                $config->save();
            } else {
                $config = new SystemConfig();
                $config->id          = $this->generateId();
                $config->group       = 'sms';
                $config->key         = $key;
                $config->value       = (string) $value;
                $config->type        = 'string';
                $config->description = $this->fieldDescription($key);
                $config->save();
            }
        }

        return $this->success([], '短信配置更新成功');
    }

    /**
     * 发送测试短信
     *
     * @Apidoc\Title("发送测试短信")
     * @Apidoc\Group("sms-config")
     * @Apidoc\Method("POST")
     * @Apidoc\Url("/admin/sms-config/test")
     * @Apidoc\Desc("向指定手机号发送测试短信，验证配置是否正确")
     * @Apidoc\Param("phone", type="string", require=true, desc="测试手机号")
     */
    public function test(Request $request): Response
    {
        $phone = $request->input('phone', '');

        if (empty($phone)) {
            return $this->fail('请输入测试手机号', 422);
        }

        // 校验手机号格式
        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return $this->fail('手机号格式不正确', 422);
        }

        // 读取当前配置
        $configs = SystemConfig::where('group', 'sms')->pluck('value', 'key')->toArray();
        $provider  = $configs['provider'] ?? 'aliyun';
        $accessKey = $configs['access_key'] ?? '';
        $secretKey = $configs['secret_key'] ?? '';
        $signName  = $configs['sign_name'] ?? '';
        $templateId = $configs['template_code'] ?? '';

        if (empty($accessKey) || empty($secretKey) || empty($signName) || empty($templateId)) {
            return $this->fail('短信配置不完整，请先完成配置', 422);
        }

        // 生成随机验证码
        $testCode = (string) random_int(100000, 999999);

        // 根据服务商选择发送方式
        try {
            $result = match ($provider) {
                'aliyun'  => $this->sendAliyunTest($phone, $testCode, $accessKey, $secretKey, $signName, $templateId),
                'tencent' => $this->sendTencentTest($phone, $testCode, $accessKey, $secretKey, $signName, $templateId),
                default   => ['success' => false, 'message' => '不支持的服务商: ' . $provider],
            };

            if ($result['success']) {
                return $this->success([
                    'phone'     => $phone,
                    'test_code' => $testCode,
                ], '测试短信发送成功');
            }

            return $this->fail('测试短信发送失败: ' . ($result['message'] ?? '未知错误'), 500);
        } catch (\Throwable $e) {
            // M3: 内部异常详情仅记日志，对外返回通用文案
            Log::error('[SmsConfigController] test sms send failed: ' . $e->getMessage());
            return $this->fail('测试短信发送异常，请稍后重试', 500);
        }
    }

    /**
     * 阿里云短信测试发送
     */
    private function sendAliyunTest(
        string $phone,
        string $code,
        string $accessKey,
        string $secretKey,
        string $signName,
        string $templateId
    ): array {
        $queryParams = [
            'PhoneNumbers'     => $phone,
            'SignName'         => $signName,
            'TemplateCode'     => $templateId,
            'TemplateParam'    => json_encode(['code' => $code], JSON_UNESCAPED_UNICODE),
            'AccessKeyId'      => $accessKey,
            'SignatureMethod'  => 'HMAC-SHA1',
            'SignatureNonce'   => bin2hex(random_bytes(16)),
            'SignatureVersion' => '1.0',
            'Timestamp'        => gmdate('Y-m-d\TH:i:s\Z'),
            'Format'           => 'JSON',
            'Action'           => 'SendSms',
            'Version'          => '2017-05-25',
        ];

        ksort($queryParams);
        $queryString  = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
        $stringToSign = 'GET&%2F&' . urlencode($queryString);
        $signature    = base64_encode(hash_hmac('sha1', $stringToSign, $secretKey . '&', true));
        $queryParams['Signature'] = $signature;

        $url = 'https://dysmsapi.aliyuncs.com/?' . http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

        $response = $this->sendHttpGet($url);
        if (empty($response)) {
            return ['success' => false, 'message' => '阿里云短信接口无响应'];
        }

        $result = json_decode($response, true);
        if (!is_array($result)) {
            return ['success' => false, 'message' => '响应解析失败'];
        }

        if (($result['Code'] ?? '') === 'OK') {
            return ['success' => true, 'message' => '发送成功'];
        }

        return ['success' => false, 'message' => $result['Message'] ?? '发送失败'];
    }

    /**
     * 腾讯云短信测试发送
     */
    private function sendTencentTest(
        string $phone,
        string $code,
        string $accessKey,
        string $secretKey,
        string $signName,
        string $templateId
    ): array {
        $service   = 'sms';
        $host      = 'sms.tencentcloudapi.com';
        $timestamp = (string) time();
        $date      = gmdate('Y-m-d');

        if (!str_starts_with($phone, '+86')) {
            $phone = '+86' . $phone;
        }

        $payload = json_encode([
            'PhoneNumberSet'   => [$phone],
            'SmsSdkAppId'      => $accessKey,
            'TemplateId'       => $templateId,
            'SignName'         => $signName,
            'TemplateParamSet' => [$code],
        ], JSON_UNESCAPED_UNICODE);

        $hashedPayload           = hash('sha256', $payload);
        $canonicalRequest        = "POST\n/\n\ncontent-type:application/json\nhost:{$host}\n\ncontent-type;host\n{$hashedPayload}";
        $hashedCanonical         = hash('sha256', $canonicalRequest);
        $stringToSign            = "TC3-HMAC-SHA256\n{$timestamp}\n{$date}/{$service}/tc3_request\n{$hashedCanonical}";

        $secretDate    = hash_hmac('sha256', $date, 'TC3' . $secretKey, true);
        $secretService = hash_hmac('sha256', $service, $secretDate, true);
        $secretSigning = hash_hmac('sha256', 'tc3_request', $secretService, true);
        $signature     = hash_hmac('sha256', $stringToSign, $secretSigning);

        $authorization = "TC3-HMAC-SHA256 Credential={$accessKey}/{$date}/{$service}/tc3_request,"
                       . "SignedHeaders=content-type;host,Signature={$signature}";

        $headers = [
            'Content-Type: application/json',
            'Host: ' . $host,
            'X-TC-Action: SendSms',
            'X-TC-Version: 2021-01-11',
            'X-TC-Timestamp: ' . $timestamp,
            'X-TC-Region: ap-guangzhou',
            'Authorization: ' . $authorization,
        ];

        $response = $this->sendHttpPost('https://' . $host, $payload, $headers);
        if (empty($response)) {
            return ['success' => false, 'message' => '腾讯云短信接口无响应'];
        }

        $result = json_decode($response, true);
        if (!is_array($result)) {
            return ['success' => false, 'message' => '响应解析失败'];
        }

        $respData = $result['Response'] ?? [];
        if (!empty($respData['Error'])) {
            return ['success' => false, 'message' => $respData['Error']['Message'] ?? '发送失败'];
        }

        $sendStatus = $respData['SendStatusSet'] ?? [];
        if (!empty($sendStatus) && ($sendStatus[0]['Code'] ?? '') === 'Ok') {
            return ['success' => true, 'message' => '发送成功'];
        }

        return ['success' => false, 'message' => $sendStatus[0]['Message'] ?? '发送失败'];
    }

    /**
     * 敏感数据脱敏显示
     */
    private function maskSensitive(string $value): string
    {
        if (empty($value) || strlen($value) <= 6) {
            return str_repeat('*', max(strlen($value), 4));
        }

        return substr($value, 0, 3) . str_repeat('*', strlen($value) - 6) . substr($value, -3);
    }

    /**
     * 字段说明
     */
    private function fieldDescription(string $key): string
    {
        return match ($key) {
            'provider'      => '短信服务商: aliyun=阿里云 tencent=腾讯云',
            'access_key'    => 'AccessKey（阿里云）/ SmsSdkAppId（腾讯云）',
            'secret_key'    => 'SecretKey',
            'sign_name'     => '短信签名',
            'template_code' => '默认验证码模板ID',
            default         => '短信配置',
        };
    }

    /**
     * HTTP GET 请求
     */
    private function sendHttpGet(string $url): string
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
            curl_close($ch);
            return is_string($response) ? $response : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * HTTP POST 请求
     */
    private function sendHttpPost(string $url, string $body, array $headers = []): string
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
            curl_close($ch);
            return is_string($response) ? $response : '';
        } catch (\Throwable $e) {
            return '';
        }
    }
}
