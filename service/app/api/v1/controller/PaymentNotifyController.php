<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\common\WechatPayService;
use support\Log;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 支付回调控制器
 *
 * 处理微信支付和支付宝的异步通知回调。
 * 注意: 回调接口不经过 Auth 中间件，由支付平台直接调用。
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

        $service = new WechatPayService();
        $result = $service->handleNotify($xml);

        return $this->xmlResponse($result['success'], $result['message'] ?? '');
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
        $params = $request->post();

        Log::info('[PaymentNotify] Alipay notify received, params: ' . json_encode($params, JSON_UNESCAPED_UNICODE));

        if (empty($params)) {
            Log::warning('[PaymentNotify] Alipay notify params empty');
            return response('fail');
        }

        $service = new WechatPayService();
        $result = $service->handleAlipayNotify($params);

        if ($result['success']) {
            return response('success');
        }

        return response('fail');
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
