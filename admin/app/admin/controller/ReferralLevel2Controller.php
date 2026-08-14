<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\admin\controller;

use app\model\Order;
use app\model\ReferralLevel2Reward;
use support\Request;
use support\Response;

/**
 * 二级返佣记录控制器（只读）
 *
 * 查看已发放的二级返佣记录，分页返回，支持按二级推荐人/被推荐人昵称或手机号关键词筛选。
 */
class ReferralLevel2Controller extends BaseController
{
    /**
     * 二级返佣记录列表
     * 筛选: keyword（二级推荐人/被推荐人昵称或手机号）
     */
    public function index(Request $request): Response
    {
        $page    = (int) $request->input('page', 1);
        $limit   = (int) $request->input('limit', 15);
        $keyword = (string) $request->input('keyword', '');

        $query = ReferralLevel2Reward::with(['referrer', 'referredUser']);

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
        $list  = $query->orderBy('created_at', 'desc')
                       ->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->get()
                       ->map(fn($reward) => $this->decorate($reward));

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 输出装饰：hashid 编码 + 补充二级推荐人/被推荐人昵称 + 订单号
     */
    private function decorate(ReferralLevel2Reward $reward): array
    {
        $data = $this->encodeIds(
            $reward->toArray(),
            ['id', 'order_id', 'referred_user_id', 'referrer_id']
        );

        $data['amount']              = (float) $reward->amount;
        $data['referrer_nickname']   = $reward->referrer ? (string) $reward->referrer->nickname : '';
        $data['referred_nickname']   = $reward->referredUser ? (string) $reward->referredUser->nickname : '';
        $data['order_no']            = '';
        $order = Order::where('id', (string) $reward->order_id)->first();
        if ($order) {
            $data['order_no'] = (string) $order->order_no;
        }

        return $data;
    }
}
