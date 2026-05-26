<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\marketing\v1\controller;

use app\common\BaseController;
use app\model\CardTransfer;
use app\model\User;
use app\model\UserMemberCard;
use support\Db;
use Webman\Http\Request;

/**
 * 会员卡转赠控制器
 * 用户将会员卡转赠给其他用户
 */
class CardTransferController extends BaseController
{
    /**
     * 转赠会员卡
     * POST /api/marketing/card/transfer
     */
    public function transfer(Request $request)
    {
        $userId     = $request->user_id;
        $cardId     = $this->decodeId($request->input('card_id', ''));
        $toUserCode = $request->input('to_user_phone', '');

        if (!$cardId) {
            return $this->error('会员卡ID无效');
        }

        if (empty($toUserCode)) {
            return $this->error('请提供接收方手机号');
        }

        // 确认卡片属于当前用户且状态为active
        $userCard = UserMemberCard::where('id', $cardId)
            ->where('user_id', $userId)
            ->first();

        if (!$userCard) {
            return $this->error('会员卡不存在', 404);
        }

        if ($userCard->status !== 'active') {
            return $this->error('仅可转赠状态为"有效"的会员卡');
        }

        // 查找接收用户
        $toUser = User::where('phone', $toUserCode)->first();

        if (!$toUser) {
            return $this->error('接收用户不存在');
        }

        if ($toUser->id === $userId) {
            return $this->error('不能将会员卡转赠给自己');
        }

        Db::beginTransaction();
        try {
            // 创建转赠记录
            CardTransfer::create([
                'id'            => CardTransfer::generateId(),
                'card_id'       => $cardId,
                'from_user_id'  => $userId,
                'to_user_id'    => $toUser->id,
                'transferred_at'=> now(),
            ]);

            // 更新卡片归属
            $userCard->user_id = $toUser->id;
            $userCard->save();

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollBack();
            return $this->error('转赠失败: ' . $e->getMessage());
        }

        return $this->success([
            'card_id'       => $cardId,
            'from_user_id'  => $userId,
            'to_user_id'    => $toUser->id,
        ], '转赠成功');
    }

    /**
     * 转赠历史（发出的/收到的）
     * GET /api/marketing/card/transfer/history
     */
    public function history(Request $request)
    {
        $userId = $request->user_id;
        $type   = $request->input('type', 'sent'); // sent | received

        $query = CardTransfer::query();
        if ($type === 'sent') {
            $query->where('from_user_id', $userId);
        } else {
            $query->where('to_user_id', $userId);
        }

        $perPage = (int)$request->input('per_page', 15);
        $paginator = $query->with([
                'fromUser:id,nickname,avatar,phone',
                'toUser:id,nickname,avatar,phone',
                'userCard.card:id,name',
            ])
            ->orderBy('transferred_at', 'desc')
            ->paginate($perPage);

        return $this->paginate($paginator);
    }
}
