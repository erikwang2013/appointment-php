<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\user\controller;

use app\common\BaseController;
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

        // TODO: 集成二维码生成服务生成真实二维码图片
        // 当前返回推广码和邀请链接，由前端生成二维码

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
}
