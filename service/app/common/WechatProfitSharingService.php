<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\common;

use app\model\Order;
use app\model\OrderPayment;
use app\model\ProfitSharing;
use support\Db;
use support\Log;

/**
 * 微信官方分账服务（请求单次分账 API）
 *
 * 配置驱动 + 降级 + 记录：
 * - 配置：appointment_system_config group=profit_sharing（enabled 默认 '0'、receiver_ratio 默认 0.7），
 *   商户凭据（appid/mch_id/证书路径）复用 group=wechat_pay 或 config/wechat（.env 键，可空）。
 * - 未启用/未配置 → 返回 disabled 降级结果并记日志，不抛异常、不影响主流程。
 * - 启用时构造微信「请求单次分账」请求结构；无商户凭据时不执行真实 HTTP，
 *   请求内容记入日志、记录保持 pending（生产环境填凭据后即可生效）。
 * - HTTP 调用隔离在私有方法，测试环境（无凭据）不会触发网络请求。
 *
 * 金额规则：分账金额 = 订单实付 paid_amount × receiver_ratio（默认 0.7 可配），
 * 平台留存剩余；金额校验 amount > 0 且 ≤ paid_amount。
 * 幂等：同订单已存在 pending/success 记录则跳过，不重复分账。
 */
class WechatProfitSharingService
{
    private const PROFIT_SHARING_URL = 'https://api.mch.weixin.qq.com/v3/profitsharing/orders';

    private bool $enabled;
    private float $receiverRatio;
    private string $appId;
    private string $mchId;
    private string $certPath;
    private string $keyPath;

    public function __construct(?array $wechat = null)
    {
        $configs = Db::table('appointment_system_config')
            ->where('group', 'profit_sharing')
            ->pluck('value', 'key')
            ->toArray();

        $this->enabled       = (string) ($configs['enabled'] ?? '0') === '1';
        $this->receiverRatio = (float) ($configs['receiver_ratio'] ?? 0.7);

        // 商户凭据：系统配置优先，回落 config/wechat.php（.env WECHAT_* 键），可空占位
        $wechat  = $wechat ?? (array) config('wechat', []);
        $payCfgs = Db::table('appointment_system_config')
            ->where('group', 'wechat_pay')
            ->pluck('value', 'key')
            ->toArray();
        $this->appId    = $payCfgs['app_id'] ?? ($wechat['app_id'] ?? '');
        $this->mchId    = $payCfgs['mch_id'] ?? ($wechat['mch_id'] ?? '');
        $this->certPath = $payCfgs['cert_path'] ?? ($wechat['cert_path'] ?? '');
        $this->keyPath  = $payCfgs['key_path'] ?? ($wechat['key_path'] ?? '');
    }

    /**
     * 发起分账
     *
     * 未启用 → disabled 降级（仅日志）；已启用 → 金额校验 + 幂等检查 →
     * 落 pending 记录 → 构造分账请求（无凭据则记日志不执行 HTTP）。
     *
     * @param string $orderId   订单 ID
     * @param array  $receivers 分账接收方（可空，缺省取订单技师用户）：
     *                          [['user_id' => int, 'openid' => string], ...]
     * @return array{status: string, order_id?: string, sharing_no?: string, amount?: float, message: string}
     */
    public function requestSharing(string $orderId, array $receivers = []): array
    {
        if (!$this->enabled) {
            Log::info('[ProfitSharing] disabled, skip order: ' . $orderId);
            return ['status' => ProfitSharing::STATUS_DISABLED, 'order_id' => $orderId, 'message' => '分账未启用'];
        }

        $order = Order::find($orderId);
        if (!$order) {
            Log::error('[ProfitSharing] order not found: ' . $orderId);
            return ['status' => ProfitSharing::STATUS_FAILED, 'order_id' => $orderId, 'message' => '订单不存在'];
        }

        $paid = (float) $order->paid_amount;
        if ($paid <= 0) {
            Log::info('[ProfitSharing] zero paid amount, skip order: ' . $order->order_no);
            return ['status' => 'skipped', 'order_id' => $orderId, 'message' => '订单实付金额为 0，无需分账'];
        }

        // 幂等：同单已存在 pending/success 分账记录则跳过（防重复分账）
        $existing = ProfitSharing::where('order_id', $orderId)
            ->whereIn('status', [ProfitSharing::STATUS_PENDING, ProfitSharing::STATUS_SUCCESS])
            ->exists();
        if ($existing) {
            Log::info('[ProfitSharing] already shared, skip order: ' . $order->order_no);
            return ['status' => 'skipped', 'order_id' => $orderId, 'message' => '该订单已存在分账记录'];
        }

        // 金额规则：实付 × 比例（默认 0.7 可配），平台留存剩余；string 域乘法防浮点丢分
        $ratio  = max(0.0, min(1.0, $this->receiverRatio));
        $amount = (float) Money::round(Money::mul((string) $paid, (string) $ratio), 2);
        if (Money::cmp((string) $amount, '0') <= 0 || Money::cmp((string) $amount, (string) $paid) > 0) {
            Log::error('[ProfitSharing] amount invalid, order: ' . $order->order_no . ', paid: ' . $paid . ', amount: ' . $amount);
            return ['status' => ProfitSharing::STATUS_FAILED, 'order_id' => $orderId, 'message' => '分账金额校验失败'];
        }

        // 分账接收方：缺省取订单技师用户
        $receiver = $this->resolveReceiver($order, $receivers);
        if (!$receiver) {
            Log::warning('[ProfitSharing] no receiver, skip order: ' . $order->order_no);
            return ['status' => 'skipped', 'order_id' => $orderId, 'message' => '未找到分账接收方（技师）'];
        }

        $sharingNo = $order->order_no;

        $record = ProfitSharing::create([
            'id'         => ProfitSharing::generateId(),
            'user_id'    => $receiver['user_id'],
            'order_id'   => $orderId,
            'sharing_no' => $sharingNo,
            'amount'     => $amount,
            'ratio'      => $ratio,
            'status'     => ProfitSharing::STATUS_PENDING,
            'response'   => null,
        ]);

        $payload = $this->buildPayload($order, $sharingNo, $receiver, $amount);

        // 无商户凭据：不执行真实 HTTP，请求内容记日志（生产填凭据即走真实调用）
        if (empty($this->appId) || empty($this->mchId)) {
            Log::info('[ProfitSharing] request constructed (no credentials, skipped), order: ' . $order->order_no
                . ', payload: ' . json_encode($payload, JSON_UNESCAPED_UNICODE));
            return [
                'status'     => ProfitSharing::STATUS_PENDING,
                'order_id'   => $orderId,
                'sharing_no' => $sharingNo,
                'amount'     => $amount,
                'message'    => '分账请求已构造（未配置商户凭据，未实际调用）',
            ];
        }

        try {
            $result = $this->doRequest($payload);
            if (!empty($result['error'])) {
                $this->markResult($record, ProfitSharing::STATUS_FAILED, $result);
                Log::error('[ProfitSharing] request failed, order: ' . $order->order_no . ', error: ' . $result['error']);
                return ['status' => ProfitSharing::STATUS_FAILED, 'order_id' => $orderId, 'sharing_no' => $sharingNo, 'message' => $result['error']];
            }
            $this->markResult($record, ProfitSharing::STATUS_SUCCESS, $result);
            Log::info('[ProfitSharing] request success, order: ' . $order->order_no . ', sharing_no: ' . $sharingNo);
            return ['status' => ProfitSharing::STATUS_SUCCESS, 'order_id' => $orderId, 'sharing_no' => $sharingNo, 'amount' => $amount, 'message' => '分账成功'];
        } catch (\Throwable $e) {
            $this->markResult($record, ProfitSharing::STATUS_FAILED, ['error' => $e->getMessage()]);
            Log::error('[ProfitSharing] request exception, order: ' . $order->order_no . ', error: ' . $e->getMessage());
            return ['status' => ProfitSharing::STATUS_FAILED, 'order_id' => $orderId, 'sharing_no' => $sharingNo, 'message' => $e->getMessage()];
        }
    }

    /**
     * 查询分账结果
     *
     * 未启用 → disabled 降级（仅日志）；启用时返回本地最新记录
     * （真实查询需商户凭据 + 分账回调，此处占位，生产可扩展调用微信查询分账结果 API）。
     *
     * @param string $orderId 订单 ID
     * @return array{status: string, order_id?: string, sharing_no?: string, amount?: float, response?: mixed, message: string}
     */
    public function querySharing(string $orderId): array
    {
        if (!$this->enabled) {
            Log::info('[ProfitSharing] query disabled, order: ' . $orderId);
            return ['status' => ProfitSharing::STATUS_DISABLED, 'order_id' => $orderId, 'message' => '分账未启用'];
        }

        $record = ProfitSharing::where('order_id', $orderId)
            ->orderByDesc('id')
            ->first();
        if (!$record) {
            Log::info('[ProfitSharing] query not found, order: ' . $orderId);
            return ['status' => 'not_found', 'order_id' => $orderId, 'message' => '无分账记录'];
        }

        Log::info('[ProfitSharing] query result, order: ' . $orderId . ', sharing_no: ' . $record->sharing_no . ', status: ' . $record->status);
        return [
            'status'     => $record->status,
            'order_id'   => $orderId,
            'sharing_no' => $record->sharing_no,
            'amount'     => (float) $record->amount,
            'response'   => $record->response,
            'message'    => '分账查询成功（本地记录）',
        ];
    }

    /**
     * 解析分账接收方：显式传参优先，缺省取订单技师用户（需绑定微信 openid）
     */
    private function resolveReceiver(Order $order, array $receivers): ?array
    {
        if (!empty($receivers[0]['user_id'])) {
            return ['user_id' => $receivers[0]['user_id'], 'openid' => (string) ($receivers[0]['openid'] ?? '')];
        }
        $technician = $order->technician;
        if ($technician) {
            return ['user_id' => $technician->id, 'openid' => (string) ($technician->wx_openid ?? '')];
        }
        return null;
    }

    /**
     * 构造微信「请求单次分账」请求结构
     *
     * 字段对齐官方接口：appid/mch_id/nonce_str/sign + transaction_id/out_order_no/receivers
     * （receivers[].type= PERSONAL_OPENID、account= openid、amount= 分（整数）、description= 描述）。
     */
    private function buildPayload(Order $order, string $sharingNo, array $receiver, float $amount): array
    {
        $transactionId = OrderPayment::where('order_id', $order->id)
            ->where('status', OrderPayment::STATUS_SUCCESS)
            ->value('transaction_id') ?? '';

        return [
            'appid'          => $this->appId,
            'mch_id'         => $this->mchId,
            'nonce_str'      => $this->generateNonceStr(),
            'sign'           => '',
            'transaction_id' => $transactionId,
            'out_order_no'   => $sharingNo,
            'receivers'      => [
                [
                    'type'        => 'PERSONAL_OPENID',
                    'account'     => $receiver['openid'],
                    'amount'      => Money::toFen($amount),
                    'description' => '订单分账',
                ],
            ],
        ];
    }

    /**
     * 真实 HTTP 调用（请求单次分账）——生产配置凭据后启用；
     * 无证书文件时直接返回错误，绝不静默放行。
     */
    private function doRequest(array $payload): array
    {
        if (empty($this->certPath) || empty($this->keyPath)
            || !file_exists($this->certPath) || !file_exists($this->keyPath)) {
            return ['error' => '分账证书未配置'];
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => self::PROFIT_SHARING_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
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
            return ['error' => 'cURL error: ' . $error];
        }
        if ($httpCode !== 200) {
            return ['error' => 'HTTP ' . $httpCode . ' from ' . self::PROFIT_SHARING_URL];
        }
        return json_decode(is_string($response) ? $response : '', true) ?: ['error' => '响应解析失败'];
    }

    /**
     * 分账结果落库（独立小事务，失败仅记日志不抛异常）
     */
    private function markResult(ProfitSharing $record, string $status, array $result): void
    {
        try {
            Db::beginTransaction();
            $record->status   = $status;
            $record->response = $result;
            $record->save();
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollBack();
            Log::error('[ProfitSharing] mark result failed, sharing_no: ' . $record->sharing_no . ', error: ' . $e->getMessage());
        }
    }

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
}
