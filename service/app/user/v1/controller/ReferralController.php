<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\user\v1\controller;

use app\common\BaseController;
use app\model\Order;
use app\model\User;
use app\model\UserReferral;
use support\Db;
use Webman\Http\Request;

/**
 * 用户推广推荐控制器
 * 查看推荐码、推荐统计、被推荐用户列表、生成推广二维码
 */
class ReferralController extends BaseController
{
    /**
     * 获取当前用户的推荐码和统计数据
     * GET /api/user/referral
     */
    public function index(Request $request)
    {
        $userId = $request->user_id;

        $user = User::find($userId);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        // 如果没有推荐码，自动生成
        if (empty($user->referral_code)) {
            $user->referral_code = User::generateReferralCode();
            $user->save();
        }

        // 统计推荐人数
        $referralCount = UserReferral::where('referrer_id', $userId)->count();

        // 统计已产生首单的推荐人数
        $firstOrderCount = UserReferral::where('referrer_id', $userId)
            ->whereNotNull('first_order_at')
            ->count();

        // 统计推荐积分（从积分流水表查询）
        $totalPoints = (int)Db::table('erik_user_points')
            ->where('user_id', $userId)
            ->where('source', 'referral')
            ->where('type', 'earn')
            ->sum('points');

        return $this->success([
            'referral_code' => $user->referral_code,
            'referral_count' => $referralCount,
            'first_order_count' => $firstOrderCount,
            'earned_points' => $totalPoints,
        ]);
    }

    /**
     * 生成推广二维码
     * GET /api/user/referral/qrcode
     */
    public function qrcode(Request $request)
    {
        $userId = $request->user_id;

        $user = User::find($userId);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        if (empty($user->referral_code)) {
            $user->referral_code = User::generateReferralCode();
            $user->save();
        }

        // 生成邀请链接和推广码
        $appUrl = getenv('APP_URL') ?: 'https://appointment.example.com';
        $inviteUrl = $appUrl . '/invite?code=' . $user->referral_code;

        // 前端根据 invite_url 自行生成二维码（推荐做法，无需服务端生成）
        // 如需服务端生成: composer require endroid/qr-code

        return $this->success([
            'referral_code' => $user->referral_code,
            'invite_url' => $inviteUrl,
        ]);
    }

    /**
     * 获取被推荐用户列表
     * GET /api/user/referral/referred-users
     */
    public function referredUsers(Request $request)
    {
        $userId = $request->user_id;

        $referrals = UserReferral::where('referrer_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        $result = [];
        foreach ($referrals as $referral) {
            $referredUser = User::find($referral->referred_user_id);

            $result[] = [
                'id' => $referral->id,
                'user_id' => $referral->referred_user_id,
                'nickname' => $referredUser ? $referredUser->nickname : '',
                'avatar' => $referredUser ? $referredUser->avatar : '',
                'registered_at' => $referral->registered_at,
                'first_order_at' => $referral->first_order_at,
                'has_first_order' => !empty($referral->first_order_at),
                'reward_type' => $referral->reward_type,
                'reward_amount' => $referral->reward_amount,
                'rewarded_at' => $referral->rewarded_at,
            ];
        }

        return $this->success($result);
    }

    /**
     * 获取推荐返佣明细（分页）
     * GET /api/user/referral/earnings
     *
     * 当前用户作为推荐人已发放的返佣记录：
     * 被推荐人昵称/头像、触发订单号（该被推荐人第一笔已完成订单）、金额、发放时间。
     */
    public function earnings(Request $request)
    {
        $userId = $request->user_id;

        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);
        $paginator = UserReferral::where('referrer_id', $userId)
            ->whereNotNull('rewarded_at')
            ->orderBy('rewarded_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        foreach ($paginator->getCollection() as $referral) {
            $referredUser = User::find($referral->referred_user_id);
            $rewardOrder = Order::where('user_id', $referral->referred_user_id)
                ->where('status', Order::STATUS_COMPLETED)
                ->orderBy('service_end_at', 'asc')
                ->orderBy('id', 'asc')
                ->first();

            $referral->nickname  = $referredUser ? (string) $referredUser->nickname : '';
            $referral->avatar    = $referredUser ? (string) $referredUser->avatar : '';
            $referral->order_no  = $rewardOrder ? (string) $rewardOrder->order_no : '';
            $referral->reward_amount = (float) ($referral->reward_amount ?? 0);
        }

        return $this->paginate($paginator);
    }
}
