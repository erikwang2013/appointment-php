<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\common;

use Illuminate\Support\Facades\Redis;
use support\Db;
use support\Log;

/**
 * 微信模板消息服务
 *
 * 发送小程序订阅消息 / 公众号模板消息通知
 */
class WechatTemplateMessageService
{
    private string $appId;
    private string $appSecret;
    private array $templateIds;

    private const ACCESS_TOKEN_URL = 'https://api.weixin.qq.com/cgi-bin/token';
    private const TEMPLATE_SEND_URL = 'https://api.weixin.qq.com/cgi-bin/message/template/send';
    private const SUBSCRIBE_SEND_URL = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send';

    private const REDIS_ACCESS_TOKEN_KEY = 'wechat:access_token';
    private const ACCESS_TOKEN_TTL = 7200; // 2 小时

    public function __construct()
    {
        $configs = Db::table('erik_system_config')
            ->where('group', 'wechat_app')
            ->pluck('value', 'key')
            ->toArray();

        $this->appId     = $configs['app_id'] ?? '';
        $this->appSecret = $configs['app_secret'] ?? '';

        $templateIdsJson = $configs['template_ids'] ?? '{}';
        $decoded = json_decode($templateIdsJson, true);
        $this->templateIds = is_array($decoded) ? $decoded : [];
    }

    /**
     * 订单确认通知
     *
     * @param string $openid 用户 openid
     * @param array  $data   [
     *   'order_no'      => string,  // 订单号
     *   'service_name'  => string,  // 服务名称
     *   'service_time'  => string,  // 服务时间
     *   'technician'    => string,  // 技师名称
     *   'store'         => string,  // 门店名称
     *   'remark'        => string,  // 备注（可选）
     * ]
     * @return array{success: bool, message: string}
     */
    public function sendOrderConfirm(string $openid, array $data): array
    {
        $templateId = $this->templateIds['order_confirm'] ?? '';

        $templateData = [
            'first'    => ['value' => '您的订单已确认'],
            'keyword1' => ['value' => $data['order_no'] ?? ''],
            'keyword2' => ['value' => $data['service_name'] ?? ''],
            'keyword3' => ['value' => $data['service_time'] ?? ''],
            'keyword4' => ['value' => $data['technician'] ?? ''],
            'keyword5' => ['value' => $data['store'] ?? ''],
            'remark'   => ['value' => $data['remark'] ?? '感谢您的预约'],
        ];

        return $this->sendTemplateMessage($openid, $templateId, $templateData);
    }

    /**
     * 服务即将开始提醒（30 分钟前）
     *
     * @param string $openid 用户 openid
     * @param array  $data   [
     *   'order_no'      => string,
     *   'service_name'  => string,
     *   'service_time'  => string,
     *   'store'         => string,
     *   'technician'    => string,
     * ]
     * @return array{success: bool, message: string}
     */
    public function sendServiceReminder(string $openid, array $data): array
    {
        $templateId = $this->templateIds['service_reminder'] ?? '';

        $templateData = [
            'first'    => ['value' => '您的服务即将开始，请提前到达'],
            'keyword1' => ['value' => $data['order_no'] ?? ''],
            'keyword2' => ['value' => $data['service_name'] ?? ''],
            'keyword3' => ['value' => $data['service_time'] ?? ''],
            'keyword4' => ['value' => $data['store'] ?? ''],
            'keyword5' => ['value' => $data['technician'] ?? ''],
            'remark'   => ['value' => '请提前10分钟到达，如需取消请联系客服'],
        ];

        return $this->sendTemplateMessage($openid, $templateId, $templateData);
    }

    /**
     * 退款完成通知
     *
     * @param string $openid 用户 openid
     * @param array  $data   [
     *   'order_no'     => string,
     *   'refund_no'    => string,
     *   'refund_amount'=> string,
     *   'reason'       => string,
     * ]
     * @return array{success: bool, message: string}
     */
    public function sendRefundNotify(string $openid, array $data): array
    {
        $templateId = $this->templateIds['refund_notify'] ?? '';

        $templateData = [
            'first'    => ['value' => '您的退款已处理'],
            'keyword1' => ['value' => $data['order_no'] ?? ''],
            'keyword2' => ['value' => $data['refund_no'] ?? ''],
            'keyword3' => ['value' => $data['refund_amount'] ?? ''],
            'keyword4' => ['value' => $data['reason'] ?? '用户申请退款'],
            'remark'   => ['value' => '退款将在1-3个工作日到账，请留意'],
        ];

        return $this->sendTemplateMessage($openid, $templateId, $templateData);
    }

    /**
     * 技师分配通知
     *
     * @param string $openid 用户 openid
     * @param array  $data   [
     *   'order_no'      => string,
     *   'technician'    => string,
     *   'technician_level' => string,
     *   'service_time'  => string,
     *   'service_name'  => string,
     * ]
     * @return array{success: bool, message: string}
     */
    public function sendTechnicianAssigned(string $openid, array $data): array
    {
        $templateId = $this->templateIds['technician_assigned'] ?? '';

        $templateData = [
            'first'    => ['value' => '已为您分配服务技师'],
            'keyword1' => ['value' => $data['order_no'] ?? ''],
            'keyword2' => ['value' => $data['technician'] ?? ''],
            'keyword3' => ['value' => $data['technician_level'] ?? ''],
            'keyword4' => ['value' => $data['service_time'] ?? ''],
            'keyword5' => ['value' => $data['service_name'] ?? ''],
            'remark'   => ['value' => '技师将按时为您服务，请保持手机畅通'],
        ];

        return $this->sendTemplateMessage($openid, $templateId, $templateData);
    }

    /**
     * 获取/缓存微信 access_token
     *
     * @return string access_token 字符串，失败返回空字符串
     */
    public function getAccessToken(): string
    {
        // 从 Redis 获取缓存
        try {
            $cached = Redis::connection()->get(self::REDIS_ACCESS_TOKEN_KEY);
            if ($cached && is_string($cached)) {
                return $cached;
            }
        } catch (\Throwable $e) {
            Log::warning('[WechatTemplateMsg] Redis get failed: ' . $e->getMessage());
        }

        if (empty($this->appId) || empty($this->appSecret)) {
            Log::error('[WechatTemplateMsg] app_id or app_secret not configured');
            return '';
        }

        $url = self::ACCESS_TOKEN_URL . '?' . http_build_query([
            'grant_type' => 'client_credential',
            'appid'      => $this->appId,
            'secret'     => $this->appSecret,
        ]);

        try {
            $response = $this->httpGet($url);

            if (empty($response)) {
                Log::error('[WechatTemplateMsg] access_token request empty');
                return '';
            }

            $data = json_decode($response, true);

            if (!is_array($data)) {
                Log::error('[WechatTemplateMsg] access_token response parse failed');
                return '';
            }

            $accessToken = $data['access_token'] ?? '';
            $expiresIn   = (int) ($data['expires_in'] ?? self::ACCESS_TOKEN_TTL);

            if (empty($accessToken)) {
                Log::error('[WechatTemplateMsg] get access_token failed: ' . ($data['errmsg'] ?? 'unknown'));
                return '';
            }

            // 缓存到 Redis，提前 5 分钟过期
            $ttl = max($expiresIn - 300, 60);
            try {
                Redis::connection()->set(self::REDIS_ACCESS_TOKEN_KEY, $accessToken, 'EX', $ttl);
            } catch (\Throwable $e) {
                Log::warning('[WechatTemplateMsg] Redis set failed: ' . $e->getMessage());
            }

            return $accessToken;
        } catch (\Throwable $e) {
            Log::error('[WechatTemplateMsg] getAccessToken exception: ' . $e->getMessage());
            return '';
        }
    }

    // ── 内部方法 ──

    /**
     * 发送模板消息
     *
     * @param string $openid      用户 openid
     * @param string $templateId  模板 ID
     * @param array  $data        模板数据
     * @param string $url         跳转链接（可选）
     * @param string $miniprogram 小程序参数（可选）
     * @return array{success: bool, message: string}
     */
    private function sendTemplateMessage(
        string $openid,
        string $templateId,
        array  $data,
        string $url = '',
        string $miniprogram = ''
    ): array {
        if (empty($openid)) {
            return ['success' => false, 'message' => 'openid 不能为空'];
        }

        if (empty($templateId)) {
            Log::warning('[WechatTemplateMsg] template_id not configured for this message type');
            return ['success' => false, 'message' => '模板ID未配置'];
        }

        $accessToken = $this->getAccessToken();

        if (empty($accessToken)) {
            return ['success' => false, 'message' => '获取 access_token 失败'];
        }

        $sendUrl = self::TEMPLATE_SEND_URL . '?access_token=' . $accessToken;

        $postData = [
            'touser'      => $openid,
            'template_id' => $templateId,
            'data'        => $data,
        ];

        if (!empty($url)) {
            $postData['url'] = $url;
        }

        if (!empty($miniprogram)) {
            $miniData = json_decode($miniprogram, true);
            if (is_array($miniData)) {
                $postData['miniprogram'] = $miniData;
            }
        }

        try {
            $body     = json_encode($postData, JSON_UNESCAPED_UNICODE);
            $response = $this->httpPost($sendUrl, $body);

            if (empty($response)) {
                Log::error('[WechatTemplateMsg] template send response empty');
                return ['success' => false, 'message' => '微信接口无响应'];
            }

            $result = json_decode($response, true);

            if (!is_array($result)) {
                return ['success' => false, 'message' => '响应解析失败'];
            }

            $errcode = $result['errcode'] ?? -1;
            $errmsg  = $result['errmsg'] ?? '';

            if ($errcode === 0) {
                return ['success' => true, 'message' => '发送成功'];
            }

            Log::error("[WechatTemplateMsg] send failed: errcode={$errcode}, errmsg={$errmsg}");

            return ['success' => false, 'message' => "发送失败: {$errmsg} ({$errcode})"];
        } catch (\Throwable $e) {
            Log::error('[WechatTemplateMsg] template send exception: ' . $e->getMessage());
            return ['success' => false, 'message' => '发送异常: ' . $e->getMessage()];
        }
    }

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
                Log::error('[WechatTemplateMsg] cURL GET error: ' . $error);
                return '';
            }

            return is_string($response) ? $response : '';
        } catch (\Throwable $e) {
            Log::error('[WechatTemplateMsg] httpGet exception: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * HTTP POST 请求
     */
    private function httpPost(string $url, string $body): string
    {
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($body),
                ],
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);

            $response = curl_exec($ch);
            $error    = curl_error($ch);
            curl_close($ch);

            if ($error) {
                Log::error('[WechatTemplateMsg] cURL POST error: ' . $error);
                return '';
            }

            return is_string($response) ? $response : '';
        } catch (\Throwable $e) {
            Log::error('[WechatTemplateMsg] httpPost exception: ' . $e->getMessage());
            return '';
        }
    }
}
