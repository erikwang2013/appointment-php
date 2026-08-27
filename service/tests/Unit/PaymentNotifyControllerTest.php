<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\api\v1\controller\PaymentNotifyController;
use app\model\Order;
use app\model\OrderPayment;
use app\model\OrderRefund;
use support\Db;
use support\Redis;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * B2: 支付回调 order_lock 互斥测试
 *
 * 回调处理（wechat/alipay notify）统一进 order_lock:{id}（NX EX 35s + token 校验释放），
 * 与用户侧 pay/cancel/refund/核销及 AutoCancelTimer 自动取消互斥，防并发竞态。
 * Redis 不可用时 skip（与 AuthControllerTest 同策略）。
 */
class PaymentNotifyControllerTest extends TestCase
{
    /** @var int[] 用例创建的订单 ID，tearDown 统一清理 */
    private array $orderIds = [];

    /** @var string[] 用例创建的 Redis key，tearDown 统一清理 */
    private array $redisKeys = [];

    protected function tearDown(): void
    {
        foreach ($this->orderIds as $id) {
            Db::table('appointment_notification')->where('order_id', $id)->delete();
            OrderRefund::where('order_id', $id)->delete();
            OrderPayment::where('order_id', $id)->delete();
            Order::where('id', $id)->delete();
        }
        foreach ($this->redisKeys as $key) {
            try {
                Redis::del($key);
            } catch (\Throwable) {
                // Redis 不可用时静默
            }
        }
        $this->orderIds = [];
        $this->redisKeys = [];
    }

    private function redisAvailable(): bool
    {
        try {
            $probe = 'test:probe:' . uniqid();
            $this->redisKeys[] = $probe;
            Redis::setex($probe, 5, '1');
            return Redis::get($probe) === '1';
        } catch (\Throwable) {
            return false;
        }
    }

    private function trackRedisKey(string $key): void
    {
        if (!in_array($key, $this->redisKeys, true)) {
            $this->redisKeys[] = $key;
        }
    }

    /** 构造携带原生 XML body 的 POST 请求（rawBody() 取 buffer 中 \r\n\r\n 之后的内容） */
    private function makeNotifyRequest(string $xml): Request
    {
        $head = "POST /payment/wechat-notify HTTP/1.1\r\n"
            . "Host: localhost\r\n"
            . "Content-Type: text/xml\r\n"
            . "Content-Length: " . strlen($xml) . "\r\n";
        return new Request($head . "\r\n" . $xml);
    }

    /** 独立实现微信 MD5 签名（与生产同算法） */
    private static function wechatSign(array $data, string $apiKey): string
    {
        unset($data['sign']);
        ksort($data);
        $pairs = [];
        foreach ($data as $k => $v) {
            if ($v === '' || $v === null) {
                continue;
            }
            $pairs[] = $k . '=' . $v;
        }
        return strtoupper(md5(implode('&', $pairs) . '&key=' . $apiKey));
    }

    /** 造 pending 订单 + 待支付记录（返回订单） */
    private function makePendingOrder(): Order
    {
        $order = Order::create([
            'order_no'        => 'ORD_NOTIFY_' . uniqid(),
            'user_id'         => (string) (9900000000000000 + random_int(1, 999999)),
            'technician_id'   => (string) (9900000000000000 + random_int(1, 999999)),
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => 100.0,
            'discount_amount' => 0.0,
            'paid_amount'     => 100.0,
            'status'          => Order::STATUS_PENDING,
            'service_time'    => date('Y-m-d H:i:s', time() + 86400),
        ]);
        $this->orderIds[] = $order->id;

        OrderPayment::create([
            'order_id'   => $order->id,
            'payment_no' => 'PAYNOTIFY_' . uniqid(),
            'pay_type'   => 'wechat',
            'amount'     => 100.0,
            'status'     => OrderPayment::STATUS_PENDING,
        ]);

        return $order;
    }

    /** 构造带签名的微信回调 XML（用测试环境 DB 中的微信 apiKey 签名） */
    private function buildSignedNotifyXml(string $outTradeNo): string
    {
        $configs = Db::table('appointment_system_config')
            ->where('group', 'wechat_pay')
            ->pluck('value', 'key')
            ->toArray();
        $apiKey = (string) ($configs['api_key'] ?? '');

        // 注意：配置值为空串时用固定占位（空 CDATA 会被 simplexml 解析为空数组破坏验签）
        $data = [
            'appid'          => (string) (($configs['app_id'] ?? '') ?: 'wx_test'),
            'mch_id'         => (string) (($configs['mch_id'] ?? '') ?: '1900000001'),
            'out_trade_no'   => $outTradeNo,
            'transaction_id' => 'TX_' . uniqid(),
            'total_fee'      => 10000,
            'result_code'    => 'SUCCESS',
            'return_code'    => 'SUCCESS',
        ];
        $data['sign'] = $apiKey !== '' ? self::wechatSign($data, $apiKey) : 'INVALID_SIGN_NO_KEY';

        $xml = '<xml>';
        foreach ($data as $k => $v) {
            $xml .= '<' . $k . '><![CDATA[' . $v . ']]></' . $k . '>';
        }
        $xml .= '</xml>';
        return $xml;
    }

    private function xmlBody(Response $response): array
    {
        $parsed = simplexml_load_string($response->rawBody(), 'SimpleXMLElement', LIBXML_NOCDATA);
        return $parsed === false ? [] : (array) $parsed;
    }

    #[Test] public function wechat_notify_skips_when_order_lock_busy(): void
    {
        if (!$this->redisAvailable()) {
            $this->markTestSkipped('Redis 不可用，跳过 order_lock 测试');
        }

        $order = $this->makePendingOrder();
        $payment = $order->payment()->first();
        $lockKey = 'order_lock:' . $order->id;
        $this->trackRedisKey($lockKey);

        // 预置他人持有的锁（token 不匹配场景）
        Redis::connection()->set($lockKey, 'OTHER_TOKEN', 'EX', 30);
        $this->assertSame('OTHER_TOKEN', (string) Redis::get($lockKey));

        $xml = $this->buildSignedNotifyXml($payment->payment_no);
        $resp = (new PaymentNotifyController())->wechatNotify($this->makeNotifyRequest($xml));

        // 锁被占用 → 返回 FAIL/processing，且不得删除他人锁
        $body = $this->xmlBody($resp);
        $this->assertSame('FAIL', $body['return_code']);
        $this->assertSame('processing', $body['return_msg']);
        $this->assertSame('OTHER_TOKEN', (string) Redis::get($lockKey));

        // 订单状态不得被处理（回调被跳过）
        $this->assertSame(Order::STATUS_PENDING, Order::find($order->id)->status);
    }

    #[Test] public function wechat_notify_releases_order_lock_after_processing(): void
    {
        if (!$this->redisAvailable()) {
            $this->markTestSkipped('Redis 不可用，跳过 order_lock 测试');
        }

        $order = $this->makePendingOrder();
        $payment = $order->payment()->first();
        $lockKey = 'order_lock:' . $order->id;
        $this->trackRedisKey($lockKey);

        $xml = $this->buildSignedNotifyXml($payment->payment_no);
        $resp = (new PaymentNotifyController())->wechatNotify($this->makeNotifyRequest($xml));

        // 处理完成（无论成功/失败）后锁必须释放
        $this->assertNull(Redis::get($lockKey));

        // 环境配置了微信 apiKey 时验签通过，完整走 markOrderPaid：订单置 PAID
        $apiKey = (string) Db::table('appointment_system_config')
            ->where('group', 'wechat_pay')
            ->where('key', 'api_key')
            ->value('value');
        if ($apiKey !== '') {
            $this->assertSame('SUCCESS', $this->xmlBody($resp)['return_code']);
            $this->assertSame(Order::STATUS_PAID, Order::find($order->id)->status);
            $this->assertSame('success', OrderPayment::find($payment->id)->status);
        } else {
            // 无密钥环境：验签必然失败（FAIL），但锁仍已释放
            $this->assertSame('FAIL', $this->xmlBody($resp)['return_code']);
        }
    }
}
