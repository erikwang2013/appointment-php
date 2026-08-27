<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\api\v1\controller\PaymentNotifyController;
use app\model\Notification;
use app\model\UserWallet;
use app\model\WalletRecharge;
use app\model\WalletTxn;
use support\Db;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 充值到账站内通知测试
 *
 * 覆盖：回调成功后通知落库且金额正确、重复回调不重复写通知、充值失败路径不写通知。
 * 基建与 WalletTest 一致（真实 DB + tearDown 清理，临时覆盖 wechat_pay api_key 后恢复）。
 */
class RechargeNotifyTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理钱包三表与充值通知 */
    private array $userIds = [];

    /** @var string 原 wechat_pay api_key 配置值（用例临时覆盖后恢复） */
    private string $savedApiKey = '';

    protected function setUp(): void
    {
        $this->savedApiKey = (string) Db::table('appointment_system_config')
            ->where('group', 'wechat_pay')
            ->where('key', 'api_key')
            ->value('value');
    }

    protected function tearDown(): void
    {
        foreach ($this->userIds as $uid) {
            Notification::where('user_id', $uid)->where('type', 'wallet_recharge')->delete();
            WalletTxn::where('user_id', $uid)->delete();
            WalletRecharge::where('user_id', $uid)->delete();
            UserWallet::where('user_id', $uid)->delete();
        }
        // 恢复 wechat_pay api_key 配置（若被用例覆盖）
        Db::table('appointment_system_config')
            ->where('group', 'wechat_pay')
            ->where('key', 'api_key')
            ->update(['value' => $this->savedApiKey]);

        $this->userIds = [];
    }

    private function newUserId(): string
    {
        $uid = (string) (9900000000000000 + random_int(1, 999999));
        $this->userIds[] = $uid;
        return $uid;
    }

    private function makeRecharge(string $userId, float $amount, string $status = WalletRecharge::STATUS_PENDING): WalletRecharge
    {
        return WalletRecharge::create([
            'user_id'     => $userId,
            'order_no'    => WalletRecharge::generateOrderNo(),
            'amount'      => $amount,
            'status'      => $status,
            'pay_channel' => 'wechat',
        ]);
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

    /** 临时写入 wechat_pay api_key 测试密钥（tearDown 恢复），返回 key */
    private function enableWechatSign(): string
    {
        $key = 'TEST_API_KEY_' . uniqid();
        Db::table('appointment_system_config')
            ->where('group', 'wechat_pay')
            ->where('key', 'api_key')
            ->update(['value' => $key]);
        return $key;
    }

    /** 构造携带原生 XML body 的 POST 请求 */
    private function makeNotifyRequest(string $xml): Request
    {
        $head = "POST /payment/wechat-notify HTTP/1.1\r\n"
            . "Host: localhost\r\n"
            . "Content-Type: text/xml\r\n"
            . "Content-Length: " . strlen($xml) . "\r\n";
        return new Request($head . "\r\n" . $xml);
    }

    /** 构造带签名的充值回调 XML */
    private function buildRechargeNotifyXml(WalletRecharge $recharge, int $totalFeeCents, string $apiKey): string
    {
        $data = [
            'appid'          => 'wx_test',
            'mch_id'         => '1900000001',
            'out_trade_no'   => $recharge->order_no,
            'transaction_id' => 'TX_' . uniqid(),
            'total_fee'      => $totalFeeCents,
            'result_code'    => 'SUCCESS',
            'return_code'    => 'SUCCESS',
        ];
        $data['sign'] = self::wechatSign($data, $apiKey);

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

    /** 查询某用户充值到账通知 */
    private function rechargeNotifications(string $userId)
    {
        return Notification::where('user_id', $userId)
            ->where('type', 'wallet_recharge')
            ->orderBy('id')
            ->get();
    }

    #[Test] public function notify_after_recharge_success_contains_correct_amount(): void
    {
        $userId = $this->newUserId();
        $recharge = $this->makeRecharge($userId, 100.0);
        $apiKey = $this->enableWechatSign();

        $resp = (new PaymentNotifyController())->wechatNotify(
            $this->makeNotifyRequest($this->buildRechargeNotifyXml($recharge, 10000, $apiKey))
        );

        $this->assertSame('SUCCESS', $this->xmlBody($resp)['return_code']);

        $notices = $this->rechargeNotifications($userId);
        $this->assertCount(1, $notices);
        $notice = $notices->first();
        $this->assertSame('wallet_recharge', $notice->type);
        $this->assertSame('充值到账', $notice->title);
        $this->assertSame('您已成功充值 ¥100.00', $notice->content);
        $this->assertSame($userId, (string) $notice->user_id);
        $this->assertSame(0, (int) $notice->is_read);
        $this->assertNotEmpty($notice->created_at);
        $this->assertNotEmpty($notice->updated_at);
    }

    #[Test] public function notify_is_idempotent_on_duplicate_callback(): void
    {
        $userId = $this->newUserId();
        $recharge = $this->makeRecharge($userId, 100.0);
        $apiKey = $this->enableWechatSign();
        $ctl = new PaymentNotifyController();
        $xml = $this->buildRechargeNotifyXml($recharge, 10000, $apiKey);

        // 连续两次回调（微信超时重试场景）
        $r1 = $ctl->wechatNotify($this->makeNotifyRequest($xml));
        $r2 = $ctl->wechatNotify($this->makeNotifyRequest($xml));

        $this->assertSame('SUCCESS', $this->xmlBody($r1)['return_code']);
        $this->assertSame('SUCCESS', $this->xmlBody($r2)['return_code']);
        $this->assertSame(1, $this->rechargeNotifications($userId)->count(), '重复回调不得重复写通知');
    }

    #[Test] public function notify_not_written_on_recharge_failure(): void
    {
        $userId = $this->newUserId();
        $recharge = $this->makeRecharge($userId, 100.0);
        $apiKey = $this->enableWechatSign();

        // 回调金额 99.00 与充值单 100.00 不符 → 失败路径
        $resp = (new PaymentNotifyController())->wechatNotify(
            $this->makeNotifyRequest($this->buildRechargeNotifyXml($recharge, 9900, $apiKey))
        );

        $this->assertSame('FAIL', $this->xmlBody($resp)['return_code']);
        $this->assertSame(0, $this->rechargeNotifications($userId)->count(), '失败路径不得写通知');
        $this->assertSame(WalletRecharge::STATUS_PENDING, WalletRecharge::find($recharge->id)->status);
    }
}
