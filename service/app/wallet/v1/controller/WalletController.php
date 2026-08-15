<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\wallet\v1\controller;

use app\common\BaseController;
use app\common\WechatPayService;
use app\model\User;
use app\model\UserWallet;
use app\model\WalletRecharge;
use app\model\WalletTxn;
use support\Log;
use Webman\Http\Request;

/**
 * 钱包控制器（储值支付）
 *
 * GET  /api/wallet                余额 + 汇总
 * POST /api/wallet/recharge       创建充值单
 * POST /api/wallet/recharge/{id}/pay  充值单发起微信支付
 * GET  /api/wallet/txns           流水明细（分页 + type 筛选）
 * POST /api/wallet/pay-password/set     设置/修改支付密码
 * POST /api/wallet/pay-password/verify  验证支付密码（前端支付确认）
 * POST /api/wallet/pay-password/check   查询是否已设置支付密码
 *
 * 充值回调（微信异步通知）在 PaymentNotifyController::wechatNotify 按
 * out_trade_no 前缀 'R' 分支处理，不走本控制器。
 */
class WalletController extends BaseController
{
    /** 单笔充值上限（元） */
    private const RECHARGE_MAX = 50000;

    /**
     * 余额 + 汇总
     */
    public function index(Request $request)
    {
        $userId = $request->user_id;

        // 首查并发兜底：uk_user_id 唯一键冲突时重取（防双写 500）
        try {
            $wallet = UserWallet::firstOrCreate(
                ['user_id' => $userId],
                ['balance' => 0.00, 'total_recharge' => 0.00, 'total_consume' => 0.00]
            );
        } catch (\Throwable) {
            $wallet = UserWallet::where('user_id', $userId)->first();
            if (!$wallet) {
                return $this->error('钱包初始化失败，请稍后重试');
            }
        }

        return $this->success([
            'balance'        => $wallet->balance,
            'total_recharge' => $wallet->total_recharge,
            'total_consume'  => $wallet->total_consume,
        ]);
    }

    /**
     * 创建充值单
     *
     * body: { amount: number } 金额（元），0.01 ~ 50000
     */
    public function recharge(Request $request)
    {
        $userId = $request->user_id;

        // 金额校验：转分比对，禁止浮点直接比较
        $amountCents = UserWallet::toCents((float) $request->input('amount', 0));
        if ($amountCents <= 0 || $amountCents > (int) round(self::RECHARGE_MAX * 100)) {
            return $this->error('充值金额需在 0.01 ~ 50000 元之间', 422);
        }
        $amount = $amountCents / 100;

        $recharge = WalletRecharge::create([
            'user_id'    => $userId,
            'order_no'   => WalletRecharge::generateOrderNo(),
            'amount'     => $amount,
            'status'     => WalletRecharge::STATUS_PENDING,
            'pay_channel' => 'wechat',
        ]);

        return $this->success([
            'recharge_id' => $recharge->id,
            'order_no'    => $recharge->order_no,
            'amount'      => $recharge->amount,
        ], '充值单创建成功');
    }

    /**
     * 充值单发起微信支付（JSAPI）
     *
     * 校验归属 + 状态 pending；out_trade_no 用充值单号（R 前缀），
     * 微信回调按前缀分支到充值处理，不破坏现有订单支付回调。
     */
    public function pay(Request $request, string $id)
    {
        $id = $this->decodeId((string) $id);
        if ($id === null) {
            return $this->error('充值单不存在', 404);
        }
        $userId = $request->user_id;

        $recharge = WalletRecharge::where('user_id', $userId)
            ->where('id', $id)
            ->first();
        if (!$recharge) {
            return $this->error('充值单不存在', 404);
        }
        if ($recharge->status !== WalletRecharge::STATUS_PENDING) {
            return $this->error('当前充值单状态不可支付');
        }

        $user = User::find($userId);
        if (!$user || empty($user->wx_openid)) {
            return $this->error('用户微信信息缺失，无法发起支付');
        }

        $payService = new WechatPayService();
        $result = $payService->unifiedOrder([
            'openid'       => $user->wx_openid,
            'total_fee'    => (float) $recharge->amount,
            'out_trade_no' => $recharge->order_no,
            'body'         => '余额充值',
            'attach'       => 'recharge',
            'trade_type'   => 'JSAPI',
        ]);

        if (!empty($result['error'])) {
            Log::error('[WalletController] recharge unifiedOrder failed, order_no: ' . $recharge->order_no . ', error: ' . $result['error']);
            return $this->error('充值下单失败: ' . $result['error']);
        }

        return $this->success([
            'prepay_id'   => $result['prepay_id'],
            'sign_params' => $result['sign_params'],
            'order_no'    => $recharge->order_no,
            'amount'      => $recharge->amount,
        ], '支付参数已生成');
    }

    /**
     * 流水明细（分页 + type 筛选）
     *
     * GET /api/wallet/txns?per_page=&type=recharge|consume|refund
     */
    public function txns(Request $request)
    {
        $userId = $request->user_id;
        $type = (string) $request->input('type', '');

        $query = WalletTxn::where('user_id', $userId);
        if (in_array($type, [WalletTxn::TYPE_RECHARGE, WalletTxn::TYPE_CONSUME, WalletTxn::TYPE_REFUND, WalletTxn::TYPE_GIFT_CARD, WalletTxn::TYPE_POINTS_EXCHANGE, WalletTxn::TYPE_REFERRAL_REWARD, WalletTxn::TYPE_REFERRAL_LEVEL2, WalletTxn::TYPE_TRANSFER_OUT, WalletTxn::TYPE_TRANSFER_IN], true)) {
            $query->where('type', $type);
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);
        $paginator = $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        // 附加 type 文案
        foreach ($paginator->getCollection() as $txn) {
            $txn->type_text = WalletTxn::TYPE_TEXT[$txn->type] ?? $txn->type;
        }

        return $this->paginate($paginator);
    }

    /**
     * 设置/修改支付密码
     *
     * body: { password: string 新密码 6 位数字, confirm: string 确认密码,
     *         pay_password?: string 原密码（已设置时必填）}
     * 首次设置直接存储；已设置需验证原密码。password_hash 存储，绝不明文。
     */
    public function setPayPassword(Request $request)
    {
        $userId = $request->user_id;
        $password = (string) $request->input('password', '');
        $confirm = (string) $request->input('confirm', '');

        if (!preg_match('/^\d{6}$/', $password)) {
            return $this->error('支付密码须为 6 位数字', 422);
        }
        if ($password !== $confirm) {
            return $this->error('两次输入的支付密码不一致', 422);
        }

        $wallet = $this->getWallet($userId);
        if (!empty($wallet->pay_password)) {
            $old = (string) $request->input('pay_password', '');
            if (!preg_match('/^\d{6}$/', $old)) {
                return $this->error('请输入原支付密码', 422);
            }
            if (!password_verify($old, (string) $wallet->pay_password)) {
                return $this->error('原支付密码错误', 422);
            }
        }

        $wallet->pay_password = password_hash($password, PASSWORD_DEFAULT);
        $wallet->pay_password_set_at = date('Y-m-d H:i:s');
        $wallet->save();

        return $this->success(['set' => true], '支付密码设置成功');
    }

    /**
     * 验证支付密码（前端支付确认）
     *
     * body: { pay_password: string 6 位数字 }
     * 未设置返回 422；错误返回 422；正确返回 { valid: true }。
     */
    public function verifyPayPassword(Request $request)
    {
        $userId = $request->user_id;
        $wallet = $this->getWallet($userId);

        if (empty($wallet->pay_password)) {
            return $this->error('请先设置支付密码', 422);
        }

        $password = (string) $request->input('pay_password', '');
        if (!preg_match('/^\d{6}$/', $password)) {
            return $this->error('支付密码须为 6 位数字', 422);
        }

        if (!password_verify($password, (string) $wallet->pay_password)) {
            return $this->error('支付密码错误', 422);
        }

        return $this->success(['valid' => true], '支付密码验证通过');
    }

    /**
     * 查询是否已设置支付密码
     *
     * 返回 { set: true/false }，供前端决定展示「设置」还是「输入密码」。
     */
    public function checkPayPassword(Request $request)
    {
        $userId = $request->user_id;
        $wallet = $this->getWallet($userId);

        return $this->success(['set' => !empty($wallet->pay_password)]);
    }

    /**
     * 获取（必要时惰性创建）用户钱包
     * 与 index() 同策略：uk_user_id 唯一键并发冲突时重取。
     */
    private function getWallet(mixed $userId): UserWallet
    {
        try {
            return UserWallet::firstOrCreate(
                ['user_id' => $userId],
                ['balance' => 0.00, 'total_recharge' => 0.00, 'total_consume' => 0.00]
            );
        } catch (\Throwable) {
            $wallet = UserWallet::where('user_id', $userId)->first();
            if ($wallet) {
                return $wallet;
            }
            throw new \RuntimeException('钱包初始化失败');
        }
    }
}
