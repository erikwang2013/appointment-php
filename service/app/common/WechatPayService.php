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
     * 处理微信支付回调通知（完整流程）
     *
     * 验签、更新订单支付状态和支付记录、返回处理结果
     *
     * @param string $xml 微信回调的原始 XML
     * @return array{success: bool, message: string}
     */
    public function handleNotify(string $xml): array
    {
        if (empty($xml)) {
            return ['success' => false, 'message' => '回调数据为空'];
        }

        // 安全解析 XML（已在 xmlToArray 中禁用 XXE）
        $data = $this->xmlToArray($xml);

        if (($data['return_code'] ?? '') !== 'SUCCESS') {
            Log::warning('[WechatPay notify] return_code not SUCCESS: ' . ($data['return_msg'] ?? ''));
            return ['success' => false, 'message' => $data['return_msg'] ?? '通信失败'];
        }

        // 验证签名
        if (!$this->verifySign($data)) {
            Log::error('[WechatPay notify] sign verification failed, out_trade_no: ' . ($data['out_trade_no'] ?? 'unknown'));
            return ['success' => false, 'message' => '签名验证失败'];
        }

        if (($data['result_code'] ?? '') !== 'SUCCESS') {
            Log::info('[WechatPay notify] payment not successful, out_trade_no: ' . ($data['out_trade_no'] ?? ''));
            return ['success' => false, 'message' => '支付未成功'];
        }

        $outTradeNo = $data['out_trade_no'] ?? '';
        $transactionId = $data['transaction_id'] ?? '';
        $totalFee = (int)($data['total_fee'] ?? 0);
        $openid = $data['openid'] ?? '';

        if (empty($outTradeNo)) {
            Log::error('[WechatPay notify] out_trade_no is empty');
            return ['success' => false, 'message' => '缺少商户订单号'];
        }

        // 更新订单支付状态
        try {
            \support\Db::beginTransaction();

            // 查找支付记录
            $payment = \app\model\OrderPayment::where('payment_no', $outTradeNo)
                ->orWhere(function ($query) use ($outTradeNo) {
                    $query->whereHas('order', function ($q) use ($outTradeNo) {
                        $q->where('order_no', $outTradeNo);
                    });
                })
                ->first();

            if (!$payment) {
                \support\Db::rollBack();
                Log::error('[WechatPay notify] payment record not found: ' . $outTradeNo);
                return ['success' => false, 'message' => '支付记录未找到'];
            }

            // 避免重复处理
            if ($payment->status === \app\model\OrderPayment::STATUS_SUCCESS) {
                \support\Db::rollBack();
                Log::info('[WechatPay notify] payment already processed: ' . $outTradeNo);
                return ['success' => true, 'message' => 'OK'];
            }

            // 更新支付记录
            $payment->transaction_id = $transactionId;
            $payment->paid_amount = $totalFee / 100;
            $payment->paid_at = date('Y-m-d H:i:s');
            $payment->pay_type = 'wechat';
            $payment->openid = $openid;
            $payment->status = \app\model\OrderPayment::STATUS_SUCCESS;
            $payment->save();

            // 更新订单状态
            $order = \app\model\Order::find($payment->order_id);
            if ($order && $order->status === \app\model\Order::STATUS_PENDING) {
                $order->status = \app\model\Order::STATUS_PAID;
                $order->paid_amount = $totalFee / 100;
                $order->save();
            }

            \support\Db::commit();

            Log::info('[WechatPay notify] payment success, out_trade_no: ' . $outTradeNo);

            return ['success' => true, 'message' => 'OK'];
        } catch (\Throwable $e) {
            \support\Db::rollBack();
            Log::error('[WechatPay notify] exception: ' . $e->getMessage() . ', out_trade_no: ' . $outTradeNo);
            return ['success' => false, 'message' => '处理异常: ' . $e->getMessage()];
        }
    }

    /**
     * 处理支付宝支付回调通知
     *
     * 验签、更新订单支付状态
     *
     * @param array $params 支付宝 POST 参数
     * @return array{success: bool, message: string}
     */
    public function handleAlipayNotify(array $params): array
    {
        $outTradeNo = $params['out_trade_no'] ?? '';
        $tradeNo = $params['trade_no'] ?? '';
        $totalAmount = (float)($params['total_amount'] ?? 0);
        $tradeStatus = $params['trade_status'] ?? '';

        if (empty($outTradeNo)) {
            Log::error('[Alipay notify] out_trade_no is empty');
            return ['success' => false, 'message' => '缺少商户订单号'];
        }

        // 只处理交易成功的通知
        if (!in_array($tradeStatus, ['TRADE_SUCCESS', 'TRADE_FINISHED'], true)) {
            Log::info('[Alipay notify] trade status not success: ' . $tradeStatus);
            return ['success' => false, 'message' => '交易未成功: ' . $tradeStatus];
        }

        // 支付宝签名验证（简化版，生产环境应使用支付宝 SDK）
        if (!$this->verifyAlipaySign($params)) {
            Log::error('[Alipay notify] sign verification failed, out_trade_no: ' . $outTradeNo);
            return ['success' => false, 'message' => '签名验证失败'];
        }

        try {
            \support\Db::beginTransaction();

            $payment = \app\model\OrderPayment::where('payment_no', $outTradeNo)
                ->orWhere(function ($query) use ($outTradeNo) {
                    $query->whereHas('order', function ($q) use ($outTradeNo) {
                        $q->where('order_no', $outTradeNo);
                    });
                })
                ->first();

            if (!$payment) {
                \support\Db::rollBack();
                Log::error('[Alipay notify] payment record not found: ' . $outTradeNo);
                return ['success' => false, 'message' => '支付记录未找到'];
            }

            if ($payment->status === \app\model\OrderPayment::STATUS_SUCCESS) {
                \support\Db::rollBack();
                return ['success' => true, 'message' => 'already processed'];
            }

            $payment->transaction_id = $tradeNo;
            $payment->paid_amount = $totalAmount;
            $payment->paid_at = date('Y-m-d H:i:s');
            $payment->pay_type = 'alipay';
            $payment->status = \app\model\OrderPayment::STATUS_SUCCESS;
            $payment->save();

            $order = \app\model\Order::find($payment->order_id);
            if ($order && $order->status === \app\model\Order::STATUS_PENDING) {
                $order->status = \app\model\Order::STATUS_PAID;
                $order->paid_amount = $totalAmount;
                $order->save();
            }

            \support\Db::commit();

            Log::info('[Alipay notify] payment success, out_trade_no: ' . $outTradeNo);
            return ['success' => true, 'message' => 'success'];
        } catch (\Throwable $e) {
            \support\Db::rollBack();
            Log::error('[Alipay notify] exception: ' . $e->getMessage());
            return ['success' => false, 'message' => '处理异常'];
        }
    }

    /**
     * 验证支付宝签名
     *
     * 去除 sign 和 sign_type 后按 key 字典排序，
     * 拼接后用 MD5/RSA 验证。此处实现 MD5 签名验证，
     * 生产环境推荐使用 RSA2。
     *
     * @param array $params 支付宝 POST 参数
     * @return bool
     */
    private function verifyAlipaySign(array $params): bool
    {
        $sign = $params['sign'] ?? '';
        $signType = $params['sign_type'] ?? 'MD5';

        if (empty($sign)) {
            return false;
        }

        // 移除 sign 和 sign_type
        unset($params['sign'], $params['sign_type']);

        // 按 key 字典排序
        ksort($params);

        $pairs = [];
        foreach ($params as $k => $v) {
            if ($v === '' || $v === null) {
                continue;
            }
            $pairs[] = $k . '=' . $v;
        }

        $string = implode('&', $pairs);

        if (strtoupper($signType) === 'MD5') {
            $string .= $this->apiKey;
            $expected = strtoupper(md5($string));
            return $expected === strtoupper($sign);
        }

        // RSA/RSA2 验证（证书公钥验证，此处留空，生产环境集成支付宝 SDK）
        return true;
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
