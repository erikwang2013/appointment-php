<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\common\NotificationReminderService;
use app\common\WechatPayService;
use app\model\Notification;
use app\model\Order;
use app\model\OrderPayment;
use app\model\UserWallet;
use app\model\WalletRecharge;
use app\model\WalletTxn;
use support\Db;
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

        $outTradeNo = $this->extractOutTradeNo($xml);

        // 余额充值回调分支：充值单号以 'R' 开头（与订单号体系区分），
        // 无订单参与，走充值单行锁 + 钱包行锁的幂等入账，不进 order_lock。
        if (str_starts_with($outTradeNo, 'R')) {
            return $this->handleRechargeNotify($xml, $outTradeNo);
        }

        // B2: 解析商户订单号定位订单，锁内处理回调（与取消/退款/支付互斥）
        $orderId = $this->findOrderIdByTradeNo($outTradeNo);
        $lockKey = $orderId !== null ? 'order_lock:' . $orderId : '';
        $lockToken = $lockKey !== '' ? $this->acquireLock($lockKey) : null;
        if ($lockKey !== '' && $lockToken === null) {
            Log::warning('[PaymentNotify] WeChat notify skipped, order lock busy: ' . $orderId);
            return $this->xmlResponse(false, 'processing');
        }

        try {
            $service = new WechatPayService();
            $result = $service->handleNotify($xml);

            // 订阅消息：支付成功（非阻塞，失败不影响回调主流程与响应）
            if (!empty($result['success']) && $orderId !== null) {
                $this->notifyPaySubscribe((string) $orderId);
            }

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
     * 余额充值回调（微信异步通知，out_trade_no 为 'R' 前缀充值单号）
     *
     * 幂等方案：充值单行 lockForUpdate + status 复验，已 paid 直接返回成功；
     * 入账事务：钱包行 lockForUpdate（不存在则创建）→ balance/total_recharge 累加
     * → 充值单置 paid/paid_at → 写流水(recharge, balance_after)。全部单事务原子提交。
     * 金额强比对：回调 total_fee 必须与充值单金额一致（转分比对）。
     *
     * @param string $xml        原始回调 XML
     * @param string $outTradeNo 商户充值单号
     * @return Response
     */
    private function handleRechargeNotify(string $xml, string $outTradeNo): Response
    {
        $service = new WechatPayService();
        $verified = $service->verifyNotify($xml);
        if (!$verified['verified']) {
            Log::warning('[PaymentNotify] recharge notify verify failed, order_no: ' . $outTradeNo . ', error: ' . $verified['error']);
            return $this->xmlResponse(false, $verified['error'] ?: 'verify failed');
        }

        $data = $verified['data'];
        if (($data['result_code'] ?? '') !== 'SUCCESS') {
            Log::info('[PaymentNotify] recharge notify not success, order_no: ' . $outTradeNo);
            return $this->xmlResponse(false, 'payment not success');
        }

        $totalFee = (int) ($data['total_fee'] ?? 0);

        Db::beginTransaction();
        try {
            // 充值单行锁 + 状态复验（幂等：已 paid 直接返回成功，防重复入账）
            $recharge = WalletRecharge::where('order_no', $outTradeNo)->lockForUpdate()->first();
            if (!$recharge) {
                Db::rollBack();
                Log::error('[PaymentNotify] recharge not found, order_no: ' . $outTradeNo);
                return $this->xmlResponse(false, 'recharge not found');
            }
            if ($recharge->status === WalletRecharge::STATUS_PAID) {
                Db::rollBack();
                Log::info('[PaymentNotify] recharge already paid, order_no: ' . $outTradeNo);
                return $this->xmlResponse(true, 'OK');
            }
            if ($recharge->status !== WalletRecharge::STATUS_PENDING) {
                Db::rollBack();
                Log::warning('[PaymentNotify] recharge status not pending, order_no: ' . $outTradeNo . ', status: ' . $recharge->status);
                return $this->xmlResponse(false, 'recharge status invalid');
            }
            // 金额强比对（转分，防浮点误差与跨单错配）
            if (UserWallet::toCents((float) $recharge->amount) !== $totalFee) {
                Db::rollBack();
                Log::error('[PaymentNotify] recharge amount mismatch, order_no: ' . $outTradeNo
                    . ', callback total_fee: ' . $totalFee . ', recharge amount: ' . $recharge->amount);
                return $this->xmlResponse(false, 'amount mismatch');
            }

            // 钱包行锁（不存在则创建；并发首充由 uk_user_id 唯一约束兜底，冲突整体回滚由微信重试）
            $wallet = UserWallet::where('user_id', $recharge->user_id)->lockForUpdate()->first();
            if (!$wallet) {
                $wallet = UserWallet::create([
                    'user_id'         => $recharge->user_id,
                    'balance'         => 0.00,
                    'total_recharge'  => 0.00,
                    'total_consume'   => 0.00,
                ]);
            }

            $wallet->balance = round((float) $wallet->balance + (float) $recharge->amount, 2);
            $wallet->total_recharge = round((float) $wallet->total_recharge + (float) $recharge->amount, 2);
            $wallet->save();

            $recharge->status = WalletRecharge::STATUS_PAID;
            $recharge->paid_at = date('Y-m-d H:i:s');
            $recharge->save();

            WalletTxn::create([
                'user_id'      => $recharge->user_id,
                'type'         => WalletTxn::TYPE_RECHARGE,
                'amount'       => (float) $recharge->amount,
                'balance_after' => (float) $wallet->balance,
                'recharge_id'  => $recharge->id,
                'remark'       => '余额充值',
            ]);

            // 站内通知与入账同事务原子提交：重复回调走行锁+status 复验早退，仅首次入账写通知；
            // 通知写入失败只记日志，不阻塞主流程（幂等已由充值单状态保证）
            try {
                Notification::create([
                    'id'         => Notification::generateId(),
                    'user_id'    => (string) $recharge->user_id,
                    'type'       => 'wallet_recharge',
                    'title'      => '充值到账',
                    'content'    => '您已成功充值 ¥' . number_format((float) $recharge->amount, 2, '.', ''),
                    'is_read'    => 0,
                ]);
            } catch (\Throwable $e) {
                Log::warning('[PaymentNotify] recharge notification write failed, order_no: ' . $outTradeNo . ': ' . $e->getMessage());
            }

            Db::commit();

            Log::info('[PaymentNotify] recharge paid, order_no: ' . $outTradeNo . ', amount: ' . $recharge->amount);
            return $this->xmlResponse(true, 'OK');
        } catch (\Throwable $e) {
            Db::rollBack();
            Log::error('[PaymentNotify] recharge notify exception, order_no: ' . $outTradeNo . ': ' . $e->getMessage());
            return $this->xmlResponse(false, 'process error');
        }
    }

    /**
     * 支付成功订阅消息通知（非阻塞，失败不影响主流程）
     *
     * 微信回调可能重复投递：幂等由 NotificationReminderService 承担——站内通知行
     * （订单号 + 「订单支付成功」标题）find-or-create + push_sent_at 去重，
     * 同订单只推一次；此处仅需订单已置 PAID 才触发。
     */
    private function notifyPaySubscribe(string $orderId): void
    {
        try {
            $order = Order::find($orderId);
            if (!$order || $order->status !== Order::STATUS_PAID) {
                return;
            }
            (new NotificationReminderService())->sendSubscribeForOrderEvent(
                $order,
                NotificationReminderService::SCENE_PAY
            );
        } catch (\Throwable $e) {
            Log::warning('[PaymentNotify] notifyPaySubscribe failed: ' . $e->getMessage());
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
