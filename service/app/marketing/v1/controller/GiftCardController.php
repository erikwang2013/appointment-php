<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\marketing\v1\controller;

use app\common\BaseController;
use app\model\GiftCard;
use app\model\UserWallet;
use app\model\WalletTxn;
use support\Db;
use support\Log;
use Webman\Http\Request;

/**
 * 礼品卡控制器
 */
class GiftCardController extends BaseController
{
    /**
     * 获取用户的礼品卡列表
     * GET /api/marketing/gift-cards
     */
    public function index(Request $request)
    {
        $userId = $request->user_id;

        $giftCards = GiftCard::where('used_by', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($giftCards);
    }

    /**
     * 我的礼品卡列表
     * GET /api/marketing/gift-cards/my
     */
    public function my(Request $request)
    {
        $userId = $request->user_id;

        $giftCards = GiftCard::where('used_by', $userId)
            ->orderBy('used_at', 'desc')
            ->get();

        return $this->success($giftCards);
    }

    /**
     * 兑换礼品卡
     * POST /api/marketing/gift-cards/redeem
     *
     * 幂等防双入账：事务内对礼品卡行 lockForUpdate + status 复验；
     * cash 类型额外对钱包行 lockForUpdate（不存在则创建）→ balance 累加
     * → 写流水(gift_card, balance_after)。全部单事务原子提交。
     */
    public function redeem(Request $request)
    {
        $userId = $request->user_id;
        $code = trim($request->input('code', ''));

        if (empty($code)) {
            return $this->error('请输入兑换码');
        }

        $giftCard = GiftCard::where('code', $code)->first();
        if (!$giftCard) {
            return $this->error('兑换码无效', 404);
        }

        if ($giftCard->status !== 'unused') {
            return $this->error('兑换码已被使用或已过期');
        }

        Db::beginTransaction();
        try {
            // 行锁 + 状态复验（并发防双入账，锁内再校验一次 status）
            $giftCard = GiftCard::where('id', $giftCard->id)->lockForUpdate()->first();
            if (!$giftCard || $giftCard->status !== 'unused') {
                Db::rollBack();
                return $this->error('兑换码已被使用或已过期');
            }

            $giftCard->status = 'used';
            $giftCard->used_by = $userId;
            $giftCard->used_at = date('Y-m-d H:i:s');
            $giftCard->save();

            if ($giftCard->type === 'cash') {
                $this->creditWallet($userId, (float) $giftCard->amount, $giftCard->code);
            }

            Db::commit();

            return $this->success([
                'id' => $giftCard->id,
                'code' => $giftCard->code,
                'type' => $giftCard->type,
                'amount' => $giftCard->amount,
                'gift_name' => $giftCard->gift_name,
                'status' => $giftCard->status,
            ], '兑换成功');
        } catch (\Throwable $e) {
            Db::rollBack();
            Log::error('[GiftCardController] redeem failed, code: ' . $code . ', error: ' . $e->getMessage());
            return $this->error('兑换失败，请稍后重试');
        }
    }

    /**
     * 现金礼品卡入账钱包（必须在事务内调用，钱包行 lockForUpdate）
     *
     * @param string $userId
     * @param float  $amount 入账金额（元）
     * @param string $code   礼品卡兑换码（写入流水 remark）
     */
    private function creditWallet(string $userId, float $amount, string $code): void
    {
        // 钱包行锁（不存在则创建；并发首充由 uk_user_id 唯一约束兜底，冲突整体回滚）
        $wallet = UserWallet::where('user_id', $userId)->lockForUpdate()->first();
        if (!$wallet) {
            $wallet = UserWallet::create([
                'user_id'        => $userId,
                'balance'        => 0.00,
                'total_recharge' => 0.00,
                'total_consume'  => 0.00,
            ]);
        }

        $wallet->balance = round((float) $wallet->balance + $amount, 2);
        $wallet->save();

        WalletTxn::create([
            'user_id'       => $userId,
            'type'          => WalletTxn::TYPE_GIFT_CARD,
            'amount'        => $amount,
            'balance_after' => (float) $wallet->balance,
            'remark'        => '礼品卡兑换 ' . $code,
        ]);
    }

    /**
     * 创建礼品卡（管理后台用）
     * POST /api/marketing/gift-cards/store
     */
    public function store(Request $request)
    {
        $type = $request->input('type', 'gift');
        $amount = (float) $request->input('amount', 0);
        $giftName = $request->input('gift_name', '');

        if ($type === 'cash' && $amount <= 0) {
            return $this->error('请输入有效金额');
        }

        if ($type === 'gift' && empty($giftName)) {
            return $this->error('请输入礼品名称');
        }

        // 生成唯一兑换码
        $code = strtoupper(substr(md5(uniqid((string) random_int(0, 99999), true)), 0, 12));
        // erik_gift_card.uk_code 唯一索引：兑换码禁止为空
        if ($code === '') {
            return $this->error('兑换码生成失败，请重试');
        }

        $giftCard = new GiftCard();
        $giftCard->id = GiftCard::generateId();
        $giftCard->code = $code;
        $giftCard->type = $type;
        $giftCard->amount = $type === 'cash' ? $amount : 0;
        $giftCard->gift_name = $type === 'gift' ? $giftName : '';
        $giftCard->status = 'unused';
        $giftCard->save();

        return $this->success([
            'id' => $giftCard->id,
            'code' => $giftCard->code,
            'type' => $giftCard->type,
            'amount' => $giftCard->amount,
            'gift_name' => $giftCard->gift_name,
            'status' => $giftCard->status,
        ], '创建成功');
    }
}
