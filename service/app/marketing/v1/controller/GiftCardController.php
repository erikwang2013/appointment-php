<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\marketing\v1\controller;

use app\common\BaseController;
use app\model\GiftCard;
use support\Db;
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
     * 兑换礼品卡
     * POST /api/marketing/gift-cards/redeem
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
            $giftCard->status = 'used';
            $giftCard->used_by = $userId;
            $giftCard->used_at = date('Y-m-d H:i:s');
            $giftCard->save();

            // 现金类型: 需增加钱包余额系统后启用
            // 所需 schema: erik_user 添加 balance DECIMAL(10,2), 创建 erik_user_balance_log 表
            // 实现后: $user->increment("balance", $giftCard->amount)

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
            return $this->error('兑换失败，请稍后重试');
        }
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
