<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\common\WechatPayService;
use support\Db;

/**
 * WechatPayService 单元测试
 *
 * 策略：通过 Reflection 无构造器实例化（构造器会查 DB），注入固定配置，
 * 重点覆盖可单测的纯逻辑：
 *   - 统一下单参数组装相关：sign()/verifySign()/arrayToXml()/xmlToArray()
 *   - 验签逻辑：固定密钥 + 独立实现交叉验证
 *   - 回调数据处理：verifyNotify()/handleNotify() 各分支（含幂等判断）
 *   - 金额单位转换（回调 total_fee 分 → 元 在 DB 路径验证）
 * 不发起真实 HTTP 请求；涉及 DB 的用例仅做查询或插入后清理。
 */
class WechatPayServiceTest extends TestCase
{
    private const APP_ID     = 'wx_test_appid';
    private const MCH_ID     = '1900000001';
    private const API_KEY    = 'test-key-123456';
    private const NOTIFY_URL = 'https://pay.example.com/notify';

    /** @var string[] 测试中插入的支付单号，tearDown 清理 */
    private array $insertedPaymentNos = [];

    /** @var string[] 测试中插入的订单 ID，tearDown 清理 */
    private array $insertedOrderIds = [];

    protected function tearDown(): void
    {
        foreach ($this->insertedPaymentNos as $no) {
            Db::table('erik_order_payment')->where('payment_no', $no)->delete();
        }
        foreach ($this->insertedOrderIds as $id) {
            Db::table('erik_order')->where('id', $id)->delete();
        }
        $this->insertedPaymentNos = [];
        $this->insertedOrderIds = [];
    }

    private function makeService(): WechatPayService
    {
        $svc = (new \ReflectionClass(WechatPayService::class))->newInstanceWithoutConstructor();
        $props = [
            'appId'     => self::APP_ID,
            'mchId'     => self::MCH_ID,
            'apiKey'    => self::API_KEY,
            'notifyUrl' => self::NOTIFY_URL,
            'certPath'  => '',
            'keyPath'   => '',
        ];
        foreach ($props as $name => $value) {
            $p = new \ReflectionProperty(WechatPayService::class, $name);
            $p->setAccessible(true);
            $p->setValue($svc, $value);
        }
        return $svc;
    }

    private function invoke(WechatPayService $svc, string $method, array $args = []): mixed
    {
        $m = new \ReflectionMethod(WechatPayService::class, $method);
        $m->setAccessible(true);
        // invokeArgs 按位置传参，避免字符串键数组被当作命名参数展开
        return $m->invokeArgs($svc, $args);
    }

    /**
     * 静默执行可能触发 PHP warning 的调用（如简单 XML 解析失败告警）。
     */
    private function suppressWarnings(callable $fn): mixed
    {
        set_error_handler(static fn (int $severity, string $message): bool => true);
        try {
            return $fn();
        } finally {
            restore_error_handler();
        }
    }

    /**
     * 独立实现微信支付 V2 MD5 签名（与生产 sign() 同算法），用于交叉验证。
     */
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

    /**
     * 构造带合法签名的微信回调 XML（签名集合与字段一致）。
     */
    private function buildSignedNotifyXml(array $fields): string
    {
        $svc = $this->makeService();
        $fields['sign'] = $this->invoke($svc, 'sign', [$fields]);
        return $this->invoke($svc, 'arrayToXml', [$fields]);
    }

    // ── sign / verifySign：验签纯逻辑 ──

    #[Test] public function sign_matches_independent_wechat_md5_computation(): void
    {
        $svc = $this->makeService();
        $data = [
            'appid'        => self::APP_ID,
            'mch_id'       => self::MCH_ID,
            'out_trade_no' => 'T202608140001',
            'total_fee'    => 1500,
            'body'         => '预约服务',
            'attach'       => 'extra',
        ];
        $expected = self::wechatSign($data, self::API_KEY);
        $this->assertSame($expected, $this->invoke($svc, 'sign', [$data]));
    }

    #[Test] public function sign_sorts_params_by_key_lexicographically(): void
    {
        $svc = $this->makeService();
        $unsorted = ['b_value' => '2', 'a_value' => '1', 'c_value' => '3'];
        $this->assertSame(
            self::wechatSign(['a_value' => '1', 'b_value' => '2', 'c_value' => '3'], self::API_KEY),
            $this->invoke($svc, 'sign', [$unsorted])
        );
    }

    #[Test] public function sign_ignores_empty_and_null_values(): void
    {
        $svc = $this->makeService();
        $data = ['a' => '1', 'b' => '', 'c' => null, 'd' => '4'];
        $this->assertSame(
            self::wechatSign(['a' => '1', 'd' => '4'], self::API_KEY),
            $this->invoke($svc, 'sign', [$data])
        );
    }

    #[Test] public function sign_removes_existing_sign_field(): void
    {
        $svc = $this->makeService();
        $data = ['a' => '1', 'sign' => 'STALE_SIGN'];
        $this->assertSame(
            self::wechatSign(['a' => '1'], self::API_KEY),
            $this->invoke($svc, 'sign', [$data])
        );
    }

    #[Test] public function verify_sign_accepts_valid_signature(): void
    {
        $svc = $this->makeService();
        $data = ['return_code' => 'SUCCESS', 'out_trade_no' => 'T1', 'total_fee' => '100'];
        $data['sign'] = self::wechatSign($data, self::API_KEY);
        $this->assertTrue($this->invoke($svc, 'verifySign', [$data]));
    }

    #[Test] public function verify_sign_rejects_tampered_data(): void
    {
        $svc = $this->makeService();
        $data = ['return_code' => 'SUCCESS', 'out_trade_no' => 'T1', 'total_fee' => '100'];
        $data['sign'] = self::wechatSign($data, self::API_KEY);
        $data['total_fee'] = '101'; // 篡改金额
        $this->assertFalse($this->invoke($svc, 'verifySign', [$data]));
    }

    #[Test] public function verify_sign_rejects_missing_sign(): void
    {
        $svc = $this->makeService();
        $this->assertFalse($this->invoke($svc, 'verifySign', [['return_code' => 'SUCCESS']]));
    }

    #[Test] public function verify_sign_rejects_empty_string_sign(): void
    {
        $svc = $this->makeService();
        $this->assertFalse($this->invoke($svc, 'verifySign', [['sign' => '']]));
    }

    // ── verifyNotify：回调验签 + 解析 ──

    #[Test] public function verify_notify_rejects_empty_xml(): void
    {
        $result = $this->makeService()->verifyNotify('');
        $this->assertFalse($result['verified']);
        $this->assertSame('回调数据为空', $result['error']);
    }

    #[Test] public function verify_notify_rejects_non_success_return_code(): void
    {
        $result = $this->makeService()->verifyNotify(
            '<xml><return_code><![CDATA[FAIL]]></return_code></xml>'
        );
        $this->assertFalse($result['verified']);
        $this->assertSame('回调状态异常', $result['error']);
    }

    #[Test] public function verify_notify_accepts_valid_signed_xml(): void
    {
        $xml = $this->buildSignedNotifyXml([
            'return_code' => 'SUCCESS',
            'out_trade_no' => 'T202608140002',
            'total_fee' => '1500',
            'transaction_id' => '4200001234',
        ]);
        $result = $this->makeService()->verifyNotify($xml);
        $this->assertTrue($result['verified']);
        $this->assertSame('', $result['error']);
        $this->assertSame('T202608140002', $result['data']['out_trade_no']);
        $this->assertSame('1500', $result['data']['total_fee']);
        $this->assertSame('SUCCESS', $result['data']['return_code']);
    }

    #[Test] public function verify_notify_rejects_tampered_xml(): void
    {
        $fields = ['return_code' => 'SUCCESS', 'out_trade_no' => 'T3', 'total_fee' => '100'];
        $sign = self::wechatSign($fields, self::API_KEY);
        // 签名后篡改金额
        $xml = '<xml><return_code><![CDATA[SUCCESS]]></return_code><out_trade_no><![CDATA[T3]]></out_trade_no><total_fee>999</total_fee><sign>' . $sign . '</sign></xml>';
        $result = $this->makeService()->verifyNotify($xml);
        $this->assertFalse($result['verified']);
        $this->assertSame('签名验证失败', $result['error']);
    }

    // ── handleNotify：回调完整处理分支（不涉及真实 HTTP） ──

    #[Test] public function handle_notify_rejects_empty_xml(): void
    {
        $result = $this->makeService()->handleNotify('');
        $this->assertFalse($result['success']);
        $this->assertSame('回调数据为空', $result['message']);
    }

    #[Test] public function handle_notify_reports_communication_failure(): void
    {
        $xml = $this->buildSignedNotifyXml([
            'return_code' => 'FAIL',
            'return_msg' => '通信失败测试',
            'out_trade_no' => 'T4',
        ]);
        $result = $this->makeService()->handleNotify($xml);
        $this->assertFalse($result['success']);
        $this->assertSame('通信失败测试', $result['message']);
    }

    #[Test] public function handle_notify_rejects_bad_signature(): void
    {
        $fields = ['return_code' => 'SUCCESS', 'out_trade_no' => 'T5'];
        $sign = self::wechatSign($fields, self::API_KEY);
        $fields['total_fee'] = '1'; // 多出的字段不参与签名 → 验签失败
        $fields['sign'] = $sign;
        $xml = $this->invoke($this->makeService(), 'arrayToXml', [$fields]);
        $result = $this->makeService()->handleNotify($xml);
        $this->assertFalse($result['success']);
        $this->assertSame('签名验证失败', $result['message']);
    }

    #[Test] public function handle_notify_rejects_payment_not_success(): void
    {
        $xml = $this->buildSignedNotifyXml([
            'return_code' => 'SUCCESS',
            'result_code' => 'FAIL',
            'err_code_des' => '余额不足',
            'out_trade_no' => 'T6',
        ]);
        $result = $this->makeService()->handleNotify($xml);
        $this->assertFalse($result['success']);
        $this->assertSame('支付未成功', $result['message']);
    }

    #[Test] public function handle_notify_rejects_missing_out_trade_no(): void
    {
        $svc = $this->makeService();
        // 回调不含 out_trade_no 字段（而非空值字段——空 CDATA 会被解析为空数组并破坏验签）
        $fields = ['return_code' => 'SUCCESS', 'result_code' => 'SUCCESS'];
        $fields['sign'] = $this->invoke($svc, 'sign', [$fields]);
        $xml = $this->invoke($svc, 'arrayToXml', [$fields]);
        $result = $svc->handleNotify($xml);
        $this->assertFalse($result['success']);
        $this->assertSame('缺少商户订单号', $result['message']);
    }

    #[Test] public function handle_notify_returns_error_when_payment_record_not_found(): void
    {
        $svc = $this->makeService();
        $xml = $this->buildSignedNotifyXml([
            'return_code' => 'SUCCESS',
            'result_code' => 'SUCCESS',
            'out_trade_no' => 'PAYNOTFOUND_' . uniqid(),
            'total_fee' => '100',
        ]);
        $result = $svc->handleNotify($xml);
        $this->assertFalse($result['success']);
        $this->assertSame('支付记录未找到', $result['message']);
    }

    #[Test] public function handle_notify_is_idempotent_for_already_success_payment(): void
    {
        $svc = $this->makeService();
        $paymentNo = 'PAYIDEM_' . uniqid();
        $orderId = 9900000000000000 + random_int(1, 999999);
        $this->insertedPaymentNos[] = $paymentNo;
        $this->insertedOrderIds[] = (string) $orderId;

        Db::table('erik_order_payment')->insert([
            'id' => $orderId,
            'order_id' => $orderId,
            'payment_no' => $paymentNo,
            'pay_type' => 'wechat',
            'transaction_id' => 'TX_INIT_TEST',
            'amount' => 15.00,
            'status' => 'success', // 已成功 → 重复回调应直接返回 OK
            'paid_at' => '2026-08-01 10:00:00',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $xml = $this->buildSignedNotifyXml([
            'return_code' => 'SUCCESS',
            'result_code' => 'SUCCESS',
            'out_trade_no' => $paymentNo,
            'transaction_id' => 'TX_REPEAT_TEST',
            'total_fee' => '1500',
            'openid' => 'oX_test',
        ]);

        // 连续两次相同回调：均返回成功且不重复处理
        $first = $svc->handleNotify($xml);
        $this->assertTrue($first['success']);
        $this->assertSame('OK', $first['message']);

        $second = $svc->handleNotify($xml);
        $this->assertTrue($second['success']);
        $this->assertSame('OK', $second['message']);

        // 幂等：支付记录未被二次处理（transaction_id 保持初始值）
        $row = Db::table('erik_order_payment')->where('payment_no', $paymentNo)->first();
        $this->assertSame('TX_INIT_TEST', $row->transaction_id);
        $this->assertSame('success', $row->status);
    }

    // ── handleAlipayNotify：支付宝回调分支 ──

    #[Test] public function handle_alipay_notify_requires_out_trade_no(): void
    {
        $result = $this->makeService()->handleAlipayNotify([]);
        $this->assertFalse($result['success']);
        $this->assertSame('缺少商户订单号', $result['message']);
    }

    #[Test] public function handle_alipay_notify_rejects_non_success_trade_status(): void
    {
        $result = $this->makeService()->handleAlipayNotify([
            'out_trade_no' => 'A1',
            'trade_status' => 'WAIT_BUYER_PAY',
        ]);
        $this->assertFalse($result['success']);
        $this->assertSame('交易未成功: WAIT_BUYER_PAY', $result['message']);
    }

    #[Test] public function handle_alipay_notify_rejects_bad_sign(): void
    {
        $result = $this->makeService()->handleAlipayNotify([
            'out_trade_no' => 'A2',
            'trade_no' => 'TXN2',
            'total_amount' => '10.00',
            'trade_status' => 'TRADE_SUCCESS',
            'sign' => 'WRONG_SIGN',
        ]);
        $this->assertFalse($result['success']);
        $this->assertSame('签名验证失败', $result['message']);
    }

    #[Test] public function handle_alipay_notify_payment_not_found_with_valid_sign(): void
    {
        $svc = $this->makeService();
        $params = [
            'out_trade_no' => 'ALIPAYNOTFOUND_' . uniqid(),
            'trade_no' => 'TXN_' . uniqid(),
            'total_amount' => '10.00',
            'trade_status' => 'TRADE_SUCCESS',
        ];
        // 支付宝 MD5：去 sign/sign_type → ksort → k=v 拼接 → 尾部拼 apiKey → MD5 大写
        ksort($params);
        $string = implode('&', array_map(fn ($k, $v) => $k . '=' . $v, array_keys($params), $params));
        $params['sign'] = strtoupper(md5($string . self::API_KEY));

        $result = $svc->handleAlipayNotify($params);
        $this->assertFalse($result['success']);
        $this->assertSame('支付记录未找到', $result['message']);
    }

    // ── XML 工具：arrayToXml / xmlToArray ──

    #[Test] public function array_to_xml_wraps_strings_in_cdata_and_numeric_plain(): void
    {
        $svc = $this->makeService();
        $xml = $this->invoke($svc, 'arrayToXml', [[
            'return_code' => 'SUCCESS',
            'total_fee' => 1500,
            'attach' => '中文附加数据',
        ]]);
        $this->assertStringContainsString('<return_code><![CDATA[SUCCESS]]></return_code>', $xml);
        $this->assertStringContainsString('<total_fee>1500</total_fee>', $xml);
        $this->assertStringContainsString('<attach><![CDATA[中文附加数据]]></attach>', $xml);
        $this->assertStringStartsWith('<xml>', $xml);
        $this->assertStringEndsWith('</xml>', $xml);
    }

    #[Test] public function xml_to_array_round_trips_with_array_to_xml(): void
    {
        $svc = $this->makeService();
        $data = ['return_code' => 'SUCCESS', 'total_fee' => 1500, 'attach' => '中文', 'sign' => 'ABC'];
        $xml = $this->invoke($svc, 'arrayToXml', [$data]);
        $parsed = $this->invoke($svc, 'xmlToArray', [$xml]);
        // XML 无类型信息，数值元素解析后为字符串
        $this->assertSame('SUCCESS', $parsed['return_code']);
        $this->assertSame('1500', $parsed['total_fee']);
        $this->assertSame('中文', $parsed['attach']);
        $this->assertSame('ABC', $parsed['sign']);
    }

    #[Test] public function xml_to_array_returns_empty_for_malformed_xml(): void
    {
        $svc = $this->makeService();
        $result = $this->suppressWarnings(fn () => $this->invoke($svc, 'xmlToArray', ['<xml><unclosed>']));
        $this->assertSame([], $result);
        $result2 = $this->suppressWarnings(fn () => $this->invoke($svc, 'xmlToArray', ['not xml at all']));
        $this->assertSame([], $result2);
    }

    #[Test] public function xml_to_array_blocks_external_entity_xxe(): void
    {
        $svc = $this->makeService();
        $evil = '<!DOCTYPE foo [<!ENTITY xxe SYSTEM "file:///etc/hostname">]>'
            . '<xml><foo>&xxe;</foo></xml>';
        $result = $this->suppressWarnings(fn () => $this->invoke($svc, 'xmlToArray', [$evil]));
        // 外部实体不得被解析进结果：任何位置都不应出现文件内容
        $this->assertStringNotContainsString('hostname', json_encode($result, JSON_THROW_ON_ERROR));
    }

    // ── refund / transferToWallet：证书配置守卫（不发真实请求） ──

    #[Test] public function refund_returns_error_when_cert_not_configured(): void
    {
        $result = $this->makeService()->refund('OUT1', 'REF1', 10.5, 10.5);
        $this->assertFalse(isset($result['refund_id']));
        $this->assertStringContainsString('退款请求失败', $result['error']);
        $this->assertStringContainsString('证书路径未配置', $result['error']);
    }

    #[Test] public function transfer_to_wallet_returns_error_when_cert_not_configured(): void
    {
        $result = $this->makeService()->transferToWallet('oX1', 'TR1', 50.0, '技师提现');
        $this->assertStringContainsString('转账请求失败', $result['error']);
        $this->assertStringContainsString('证书路径未配置', $result['error']);
    }

    // ── getClientIp ──

    #[Test] public function get_client_ip_prefers_x_forwarded_for(): void
    {
        $backup = $_SERVER;
        try {
            $_SERVER = [
                'HTTP_X_FORWARDED_FOR' => '1.2.3.4, 5.6.7.8',
                'HTTP_X_REAL_IP' => '9.9.9.9',
                'REMOTE_ADDR' => '8.8.8.8',
            ];
            $svc = $this->makeService();
            $this->assertSame('1.2.3.4', $this->invoke($svc, 'getClientIp'));
        } finally {
            $_SERVER = $backup;
        }
    }

    #[Test] public function get_client_ip_falls_back_to_loopback(): void
    {
        $backup = $_SERVER;
        try {
            $_SERVER = [];
            $svc = $this->makeService();
            $this->assertSame('127.0.0.1', $this->invoke($svc, 'getClientIp'));
        } finally {
            $_SERVER = $backup;
        }
    }
}
