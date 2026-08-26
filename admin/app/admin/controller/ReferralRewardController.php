<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\admin\controller;

use app\model\UserReferral;
use support\Request;
use support\Response;

/**
 * 分销返佣记录控制器（只读）
 *
 * 查看已发放的返佣记录（rewarded_at 非空），分页返回，
 * 支持按推荐人/被推荐人昵称或手机号关键词筛选。
 */
class ReferralRewardController extends BaseController
{
    /**
     * 返佣记录列表
     * 筛选: keyword（推荐人/被推荐人昵称或手机号）
     */
    public function index(Request $request): Response
    {
        $page    = (int) $request->input('page', 1);
        $limit   = (int) $request->input('limit', 15);
        $keyword = (string) $request->input('keyword', '');

        $query = UserReferral::with(['referrer', 'referredUser'])
            ->whereNotNull('rewarded_at');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->whereHas('referrer', function ($sub) use ($keyword) {
                    $sub->where('nickname', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%");
                })->orWhereHas('referredUser', function ($sub) use ($keyword) {
                    $sub->where('nickname', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%");
                });
            });
        }

        $total = $query->count();
        $list  = $query->orderBy('rewarded_at', 'desc')
                       ->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->get()
                       ->map(fn($referral) => $this->decorate($referral));

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 输出装饰：hashid 编码 + 补充推荐人/被推荐人昵称
     */
    private function decorate(UserReferral $referral): array
    {
        $data = $referral->toArray();

        $data['reward_amount']     = (float) ($referral->reward_amount ?? 0);
        $data['referrer_nickname'] = $referral->referrer ? (string) $referral->referrer->nickname : '';
        $data['referred_nickname'] = $referral->referredUser ? (string) $referral->referredUser->nickname : '';

        return $data;
    }
}
