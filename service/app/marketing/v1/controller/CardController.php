<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\marketing\v1\controller;

use app\common\BaseController;
use app\model\MemberCard;
use app\model\UserMemberCard;
use support\Db;
use Webman\Http\Request;

/**
 * 会员卡控制器
 */
class CardController extends BaseController
{
    /**
     * 获取可购买的会员卡列表
     * GET /api/marketing/cards
     */
    public function index(Request $request)
    {
        $cards = MemberCard::where('status', 1)->orderBy('sort')->orderBy('created_at', 'desc')->get();

        return $this->success($cards);
    }

    /**
     * 购买会员卡
     * POST /api/marketing/cards/buy
     */
    public function buy(Request $request)
    {
        $userId = $request->user_id;
        $cardId = $this->decodeId($request->input('card_id'));

        if (!$cardId) {
            return $this->error('会员卡ID无效');
        }

        $card = MemberCard::find($cardId);
        if (!$card) {
            return $this->error('会员卡不存在', 404);
        }

        if ($card->status != 1) {
            return $this->error('会员卡已下架');
        }

        $now = date('Y-m-d H:i:s');

        Db::beginTransaction();
        try {
            $userCard = new UserMemberCard();
            $userCard->id = UserMemberCard::generateId();
            $userCard->user_id = $userId;
            $userCard->card_id = $cardId;
            $userCard->start_at = $now;

            if (!empty($card->duration_days) && $card->duration_days > 0) {
                $userCard->end_at = date('Y-m-d H:i:s', strtotime("+{$card->duration_days} days"));
            }

            $userCard->total_times = $card->total_times ?? 0;
            $userCard->used_times = 0;
            $userCard->status = 'active';
            $userCard->save();

            Db::commit();

            return $this->success([
                'id' => $userCard->id,
                'card_id' => $userCard->card_id,
                'start_at' => $userCard->start_at,
                'end_at' => $userCard->end_at,
                'total_times' => $userCard->total_times,
                'status' => $userCard->status,
            ], '购买成功');
        } catch (\Throwable $e) {
            Db::rollBack();
            return $this->error('购买失败，请稍后重试');
        }
    }
}
