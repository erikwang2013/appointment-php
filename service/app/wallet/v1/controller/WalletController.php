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
        if (in_array($type, [WalletTxn::TYPE_RECHARGE, WalletTxn::TYPE_CONSUME, WalletTxn::TYPE_REFUND, WalletTxn::TYPE_GIFT_CARD, WalletTxn::TYPE_POINTS_EXCHANGE, WalletTxn::TYPE_REFERRAL_REWARD], true)) {
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
}
