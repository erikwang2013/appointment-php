<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\common\WechatPayService;
use app\model\Order;
use app\model\OrderPayment;
use support\Log;
use support\Redis;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 支付回调控制器
 *
 * 处理微信支付和支付宝的异步通知回调。
 * 注意: 回调接口不经过 Auth 中间件，由支付平台直接调用。
 *
 * B2: 回调处理统一进 order_lock:{id}（NX EX 35s + 随机 token 校验释放），
 * 与用户侧 pay/cancel/refund/核销及 AutoCancelTimer 自动取消互斥，防并发竞态。
 */
class PaymentNotifyController extends BaseController
{
    /**
     * 微信支付回调通知
     *
     * POST /payment/wechat-notify
     *
     * 微信服务器 POST XML 格式数据到该地址，
     * 验签通过后更新订单支付状态，返回 XML 格式响应给微信。
     *
     * @param Request $request
     * @return Response
     */
    public function wechatNotify(Request $request): Response
    {
        $xml = $request->rawBody();

        Log::info('[PaymentNotify] WeChat notify received, length: ' . strlen($xml));

        if (empty($xml)) {
            Log::warning('[PaymentNotify] WeChat notify body is empty');
            return $this->xmlResponse(false, 'body is empty');
        }

        // B2: 解析商户订单号定位订单，锁内处理回调（与取消/退款/支付互斥）
        $orderId = $this->findOrderIdByTradeNo($this->extractOutTradeNo($xml));
        $lockKey = $orderId !== null ? 'order_lock:' . $orderId : '';
        $lockToken = $lockKey !== '' ? $this->acquireLock($lockKey) : null;
        if ($lockKey !== '' && $lockToken === null) {
            Log::warning('[PaymentNotify] WeChat notify skipped, order lock busy: ' . $orderId);
            return $this->xmlResponse(false, 'processing');
        }

        try {
            $service = new WechatPayService();
            $result = $service->handleNotify($xml);

            return $this->xmlResponse($result['success'], $result['message'] ?? '');
        } finally {
            $this->releaseLock($lockKey, $lockToken);
        }
    }

    /**
     * 支付宝回调通知
     *
     * POST /payment/alipay-notify
     *
     * 支付宝服务器 POST 表单数据到该地址，
     * 验签通过后更新订单支付状态。
     *
     * @param Request $request
     * @return Response
     */
    public function alipayNotify(Request $request): Response
    {
        // B1: 来源 IP 白名单（.env ALIPAY_NOTIFY_IP_WHITELIST 逗号分隔，留空放行）
        $whitelist = trim((string) (config('alipay.notify_ip_whitelist') ?? ''));
        if ($whitelist !== '') {
            $clientIp = $request->getRemoteIp();
            $allowed = array_map('trim', explode(',', $whitelist));
            if (!in_array($clientIp, $allowed, true)) {
                Log::warning('[PaymentNotify] Alipay notify rejected by IP whitelist, ip: ' . $clientIp);
                return response('fail');
            }
        }

        $params = $request->post();

        Log::info('[PaymentNotify] Alipay notify received, params: ' . json_encode($params, JSON_UNESCAPED_UNICODE));

        if (empty($params)) {
            Log::warning('[PaymentNotify] Alipay notify params empty');
            return response('fail');
        }

        // B2: 锁内处理回调（与取消/退款/支付互斥）
        $orderId = $this->findOrderIdByTradeNo((string) ($params['out_trade_no'] ?? ''));
        $lockKey = $orderId !== null ? 'order_lock:' . $orderId : '';
        $lockToken = $lockKey !== '' ? $this->acquireLock($lockKey) : null;
        if ($lockKey !== '' && $lockToken === null) {
            Log::warning('[PaymentNotify] Alipay notify skipped, order lock busy: ' . $orderId);
            return response('fail');
        }

        try {
            $service = new WechatPayService();
            $result = $service->handleAlipayNotify($params);

            if ($result['success']) {
                return response('success');
            }

            return response('fail');
        } finally {
            $this->releaseLock($lockKey, $lockToken);
        }
    }

    /**
     * 从微信回调 XML 中提取商户订单号（out_trade_no）
     *
     * @param string $xml 原始回调 XML
     * @return string
     */
    private function extractOutTradeNo(string $xml): string
    {
        if (empty($xml)) {
            return '';
        }
        // LIBXML_NONET 禁止外部实体网络加载（防 XXE）；libxml_disable_entity_loader 已弃用不用
        $parsed = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
        if ($parsed === false) {
            return '';
        }
        $outTradeNo = (string) ($parsed->out_trade_no ?? '');
        return trim($outTradeNo);
    }

    /**
     * 按商户订单号（支付记录 payment_no 或订单 order_no）定位订单 ID
     *
     * @param string $outTradeNo 商户订单号
     * @return string|null 订单 ID，找不到返回 null
     */
    private function findOrderIdByTradeNo(string $outTradeNo): ?string
    {
        if ($outTradeNo === '') {
            return null;
        }

        $payment = OrderPayment::where('payment_no', $outTradeNo)->first();
        if ($payment) {
            return (string) $payment->order_id;
        }

        $order = Order::where('order_no', $outTradeNo)->first();
        return $order ? (string) $order->id : null;
    }

    /**
     * 获取 Redis 分布式锁（NX + 随机 token，与 OrderController 同款封装）
     *
     * token 用于释放时校验，防止超时后误删他人锁。
     *
     * @param string $key           锁 key
     * @param int    $expireSeconds 过期秒数（默认 35s，覆盖微信 HTTP 30s 超时）
     * @return string|null 持有 token，拿不到锁返回 null
     */
    private function acquireLock(string $key, int $expireSeconds = 35): ?string
    {
        if ($key === '') {
            return null;
        }
        $token = bin2hex(random_bytes(16));
        $ok = Redis::connection()->set($key, $token, 'EX', $expireSeconds, 'NX');
        return $ok ? $token : null;
    }

    /**
     * 释放 Redis 分布式锁（仅当持有者 token 匹配时删除）
     */
    private function releaseLock(string $key, ?string $token): void
    {
        if ($key === '' || $token === null) {
            return;
        }
        $redis = Redis::connection();
        if ((string) ($redis->get($key) ?? '') === $token) {
            $redis->del($key);
        }
    }

    /**
     * 构造微信支付 XML 响应
     *
     * @param bool $success 是否成功
     * @param string $message 消息
     * @return Response
     */
    private function xmlResponse(bool $success, string $message = ''): Response
    {
        $code = $success ? 'SUCCESS' : 'FAIL';
        $xml = sprintf(
            '<xml><return_code><![CDATA[%s]]></return_code><return_msg><![CDATA[%s]]></return_msg></xml>',
            $code,
            $message
        );

        return response($xml, 200, ['Content-Type' => 'text/xml; charset=utf-8']);
    }
}
