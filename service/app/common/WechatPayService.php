<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\common;

use support\Db;
use support\Log;

/**
 * 微信支付服务
 *
 * 封装微信支付 V2 API：统一下单、订单查询、退款、回调验证、企业付款到零钱
 */
class WechatPayService
{
    private string $appId;
    private string $mchId;
    private string $apiKey;
    private string $notifyUrl;
    private string $certPath;
    private string $keyPath;

    private const UNIFIED_ORDER_URL = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
    private const ORDER_QUERY_URL   = 'https://api.mch.weixin.qq.com/pay/orderquery';
    private const REFUND_URL        = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
    private const TRANSFER_URL      = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';

    public function __construct()
    {
        $configs = Db::table('erik_system_config')
            ->where('group', 'wechat_pay')
            ->pluck('value', 'key')
            ->toArray();

        $this->appId     = $configs['app_id'] ?? '';
        $this->mchId     = $configs['mch_id'] ?? '';
        $this->apiKey    = $configs['api_key'] ?? '';
        $this->notifyUrl = $configs['notify_url'] ?? '';
        $this->certPath  = $configs['cert_path'] ?? '';
        $this->keyPath   = $configs['key_path'] ?? '';
    }

    /**
     * 统一下单
     *
     * 创建微信 JSAPI/APP 预支付订单
     *
     * @param array $params [
     *   'openid'       => string,   // 用户 openid
     *   'total_fee'    => float,    // 金额（元），内部自动转换为分
     *   'out_trade_no' => string,   // 商户订单号
     *   'body'         => string,   // 商品描述
     *   'attach'       => string,   // 附加数据（可选）
     *   'trade_type'   => string,   // 交易类型: JSAPI/APP（默认 JSAPI）
     * ]
     * @return array{prepay_id: string, sign_params: array}|array{error: string}
     */
    public function unifiedOrder(array $params): array
    {
        $tradeType = $params['trade_type'] ?? 'JSAPI';

        $totalFee = (int) round(($params['total_fee'] ?? 0) * 100);

        $data = [
            'appid'            => $this->appId,
            'mch_id'           => $this->mchId,
            'nonce_str'        => $this->generateNonceStr(),
            'body'             => $params['body'] ?? '预约服务',
            'out_trade_no'     => $params['out_trade_no'],
            'total_fee'        => $totalFee,
            'spbill_create_ip' => $this->getClientIp(),
            'notify_url'       => $this->notifyUrl,
            'trade_type'       => $tradeType,
            'openid'           => $params['openid'] ?? '',
            'attach'           => $params['attach'] ?? '',
        ];

        $data['sign'] = $this->sign($data);

        $xml  = $this->arrayToXml($data);
        $resp = $this->postXml(self::UNIFIED_ORDER_URL, $xml);

        if (empty($resp)) {
            return ['error' => '微信支付接口无响应'];
        }

        $result = $this->xmlToArray($resp);

        if (($result['return_code'] ?? '') !== 'SUCCESS') {
            return ['error' => $result['return_msg'] ?? '统一下单失败'];
        }

        if (($result['result_code'] ?? '') !== 'SUCCESS') {
            return ['error' => $result['err_code_des'] ?? '统一下单失败'];
        }

        // 验证签名
        if (!$this->verifySign($result)) {
            return ['error' => '签名验证失败'];
        }

        $prepayId = $result['prepay_id'] ?? '';

        // 构造前端调起支付所需的签名参数
        $signParams = [
            'appId'     => $this->appId,
            'timeStamp' => (string) time(),
            'nonceStr'  => $this->generateNonceStr(),
            'package'   => 'prepay_id=' . $prepayId,
            'signType'  => 'MD5',
        ];
        $signParams['paySign'] = $this->sign($signParams);

        return [
            'prepay_id'   => $prepayId,
            'sign_params' => $signParams,
        ];
    }

    /**
     * 查询订单
     *
     * @param string $outTradeNo 商户订单号
     * @return array
     */
    public function queryOrder(string $outTradeNo): array
    {
        $data = [
            'appid'        => $this->appId,
            'mch_id'       => $this->mchId,
            'nonce_str'    => $this->generateNonceStr(),
            'out_trade_no' => $outTradeNo,
        ];

        $data['sign'] = $this->sign($data);

        $xml  = $this->arrayToXml($data);
        $resp = $this->postXml(self::ORDER_QUERY_URL, $xml);

        if (empty($resp)) {
            return ['error' => '微信支付查询无响应'];
        }

        $result = $this->xmlToArray($resp);

        if (($result['return_code'] ?? '') !== 'SUCCESS') {
            return ['error' => $result['return_msg'] ?? '查询失败'];
        }

        if (!$this->verifySign($result)) {
            return ['error' => '签名验证失败'];
        }

        return $result;
    }

    /**
     * 申请退款
     *
     * @param string $outTradeNo  商户订单号
     * @param string $outRefundNo 商户退款单号
     * @param float  $totalFee    订单总金额（元）
     * @param float  $refundFee   退款金额（元）
     * @return array
     */
    public function refund(string $outTradeNo, string $outRefundNo, float $totalFee, float $refundFee): array
    {
        $totalFeeFen  = (int) round($totalFee * 100);
        $refundFeeFen = (int) round($refundFee * 100);

        $data = [
            'appid'         => $this->appId,
            'mch_id'        => $this->mchId,
            'nonce_str'     => $this->generateNonceStr(),
            'out_trade_no'  => $outTradeNo,
            'out_refund_no' => $outRefundNo,
            'total_fee'     => $totalFeeFen,
            'refund_fee'    => $refundFeeFen,
        ];

        $data['sign'] = $this->sign($data);

        $xml  = $this->arrayToXml($data);

        try {
            $resp = $this->postXmlWithCert(self::REFUND_URL, $xml);
        } catch (\Throwable $e) {
            Log::error('[WechatPay refund] cert request failed: ' . $e->getMessage());
            return ['error' => '退款请求失败: ' . $e->getMessage()];
        }

        if (empty($resp)) {
            return ['error' => '微信退款接口无响应'];
        }

        $result = $this->xmlToArray($resp);

        if (($result['return_code'] ?? '') !== 'SUCCESS') {
            return ['error' => $result['return_msg'] ?? '退款失败'];
        }

        if (($result['result_code'] ?? '') !== 'SUCCESS') {
            return ['error' => $result['err_code_des'] ?? '退款失败'];
        }

        if (!$this->verifySign($result)) {
            return ['error' => '退款响应签名验证失败'];
        }

        return [
            'refund_id' => $result['refund_id'] ?? '',
            'result'    => $result,
        ];
    }

    /**
     * 验证支付结果通知
     *
     * 验签并返回解码后的数据数组
     *
     * @param string $xml 微信回调的 XML 内容
     * @return array{verified: bool, data: array, error: string}
     */
    public function verifyNotify(string $xml): array
    {
        if (empty($xml)) {
            return ['verified' => false, 'data' => [], 'error' => '回调数据为空'];
        }

        $data = $this->xmlToArray($xml);

        if (($data['return_code'] ?? '') !== 'SUCCESS') {
            return ['verified' => false, 'data' => $data, 'error' => '回调状态异常'];
        }

        if (!$this->verifySign($data)) {
            return ['verified' => false, 'data' => $data, 'error' => '签名验证失败'];
        }

        return ['verified' => true, 'data' => $data, 'error' => ''];
    }

    /**
     * 企业付款到零钱（技师提现）
     *
     * @param string $openid         用户 openid
     * @param string $partnerTradeNo 商户转账单号
     * @param float  $amount         提现金额（元）
     * @param string $desc           转账说明
     * @return array
     */
    public function transferToWallet(string $openid, string $partnerTradeNo, float $amount, string $desc = '技师提现'): array
    {
        $amountFen = (int) round($amount * 100);

        $data = [
            'mch_appid'        => $this->appId,
            'mchid'            => $this->mchId,
            'nonce_str'        => $this->generateNonceStr(),
            'partner_trade_no' => $partnerTradeNo,
            'openid'           => $openid,
            'check_name'       => 'NO_CHECK',
            'amount'           => $amountFen,
            'desc'             => $desc,
            'spbill_create_ip' => $this->getClientIp(),
        ];

        $data['sign'] = $this->sign($data);

        $xml = $this->arrayToXml($data);

        try {
            $resp = $this->postXmlWithCert(self::TRANSFER_URL, $xml);
        } catch (\Throwable $e) {
            Log::error('[WechatPay transfer] cert request failed: ' . $e->getMessage());
            return ['error' => '转账请求失败: ' . $e->getMessage()];
        }

        if (empty($resp)) {
            return ['error' => '微信转账接口无响应'];
        }

        $result = $this->xmlToArray($resp);

        if (($result['return_code'] ?? '') !== 'SUCCESS') {
            return ['error' => $result['return_msg'] ?? '转账失败'];
        }

        if (($result['result_code'] ?? '') !== 'SUCCESS') {
            return ['error' => $result['err_code_des'] ?? '转账失败'];
        }

        return [
            'payment_no' => $result['payment_no'] ?? '',
            'result'     => $result,
        ];
    }

    // ── 内部工具方法 ──

    /**
     * 生成随机字符串
     */
    private function generateNonceStr(int $length = 32): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str   = '';
        $max   = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[random_int(0, $max)];
        }
        return $str;
    }

    /**
     * MD5 签名
     *
     * 按字典序排序参数，拼接 key=value&key=value&key=<apiKey>，MD5 后转大写
     */
    private function sign(array $data): string
    {
        // 移除 sign 字段本身
        unset($data['sign']);

        // 按字典序排序
        ksort($data);

        $pairs = [];
        foreach ($data as $k => $v) {
            if ($v === '' || $v === null) {
                continue;
            }
            $pairs[] = $k . '=' . $v;
        }

        $string = implode('&', $pairs) . '&key=' . $this->apiKey;

        return strtoupper(md5($string));
    }

    /**
     * 验证签名
     */
    private function verifySign(array $data): bool
    {
        if (empty($data['sign'])) {
            return false;
        }

        $expectedSign = $this->sign($data);

        return strtoupper($data['sign']) === strtoupper($expectedSign);
    }

    /**
     * 数组转 XML（不含根节点声明）
     */
    private function arrayToXml(array $data): string
    {
        $xml = '<xml>';
        foreach ($data as $k => $v) {
            if (is_numeric($v)) {
                $xml .= "<{$k}>{$v}</{$k}>";
            } else {
                $xml .= "<{$k}><![CDATA[{$v}]]></{$k}>";
            }
        }
        $xml .= '</xml>';

        return $xml;
    }

    /**
     * XML 转数组
     */
    private function xmlToArray(string $xml): array
    {
        if (empty($xml)) {
            return [];
        }

        // 禁止外部实体，防止 XXE 攻击
        libxml_disable_entity_loader(true);

        $parsed = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($parsed === false) {
            return [];
        }

        $json = json_encode($parsed, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return [];
        }

        return json_decode($json, true) ?: [];
    }

    /**
     * 发送 XML 请求（无证书）
     */
    private function postXml(string $url, string $xml): string
    {
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $xml,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: text/xml',
                    'Content-Length: ' . strlen($xml),
                ],
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error    = curl_error($ch);
            curl_close($ch);

            if ($error) {
                Log::error('[WechatPay] cURL error: ' . $error);
                return '';
            }

            if ($httpCode !== 200) {
                Log::error('[WechatPay] HTTP ' . $httpCode . ' from ' . $url);
                return '';
            }

            return is_string($response) ? $response : '';
        } catch (\Throwable $e) {
            Log::error('[WechatPay] postXml exception: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * 发送 XML 请求（带证书）
     */
    private function postXmlWithCert(string $url, string $xml): string
    {
        if (empty($this->certPath) || empty($this->keyPath)) {
            throw new \RuntimeException('微信支付证书路径未配置');
        }

        if (!file_exists($this->certPath)) {
            throw new \RuntimeException('微信支付证书文件不存在: ' . $this->certPath);
        }

        if (!file_exists($this->keyPath)) {
            throw new \RuntimeException('微信支付证书密钥文件不存在: ' . $this->keyPath);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $xml,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: text/xml',
                'Content-Length: ' . strlen($xml),
            ],
            CURLOPT_SSLCERTTYPE    => 'PEM',
            CURLOPT_SSLCERT        => $this->certPath,
            CURLOPT_SSLKEYTYPE     => 'PEM',
            CURLOPT_SSLKEY         => $this->keyPath,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException('cURL error: ' . $error);
        }

        if ($httpCode !== 200) {
            throw new \RuntimeException('HTTP ' . $httpCode . ' from ' . $url);
        }

        return is_string($response) ? $response : '';
    }

    /**
     * 获取客户端 IP
     */
    private function getClientIp(): string
    {
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = trim(explode(',', $_SERVER[$header])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '127.0.0.1';
    }
}
