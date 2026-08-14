<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\User;
use app\model\Order;
use app\model\UserMemberCard;
use support\Request;
use support\Response;

class MemberController extends BaseController
{
    /**
     * 会员列表（有订单的用户）
     * 搜索: uid / phone / nickname / reg_date
     */
    public function index(Request $request): Response
    {
        $page  = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 15);
        $uid   = $request->input('uid', '');
        $phone = $request->input('phone', '');
        $nickname = $request->input('nickname', '');
        $regDateStart = $request->input('reg_date_start', '');
        $regDateEnd   = $request->input('reg_date_end', '');
        $memberLevel = $request->input('member_level');

        $query = User::whereHas('orders');

        if ($uid) {
            $query->where('id', $uid);
        }
        if ($phone) {
            $query->where('phone', 'like', "%{$phone}%");
        }
        if ($nickname) {
            $query->where('nickname', 'like', "%{$nickname}%");
        }
        if ($regDateStart) {
            $query->whereDate('created_at', '>=', $regDateStart);
        }
        if ($regDateEnd) {
            $query->whereDate('created_at', '<=', $regDateEnd);
        }
        if ($memberLevel !== null && $memberLevel !== '') {
            $query->where('member_level', (string) $memberLevel);
        }

        $total = $query->count();
        $list  = $query->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->orderBy('id', 'desc')
                       ->get()
                       ->map(function ($user) {
                           $data = $user->toArray();
                           // 脱敏
                           if (!empty($data['phone'])) {
                               $data['phone'] = preg_replace('/^(\d{3})\d+(\d{4})$/', '$1****$2', $data['phone']);
                           }
                           if (!empty($data['real_name'])) {
                               $data['real_name'] = mb_substr($data['real_name'], 0, 1) . '**';
                           }
                           // 会员统计
                           $data['total_spent'] = Order::where('user_id', $user->id)
                               ->where('status', 'completed')
                               ->sum('paid_amount');
                           $data['order_count'] = Order::where('user_id', $user->id)->count();
                           $data['member_cards_count'] = UserMemberCard::where('user_id', $user->id)
                               ->where('status', 'active')->count();

                           return $this->encodeIds($data);
                       });

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 会员详情 + 订单历史 + 有效会员卡
     */
    public function show(Request $request, string $hashid): Response
    {
        $id   = $this->decodeId($hashid);
        $user = User::find($id);
        if (!$user) {
            return $this->fail('用户不存在', 404);
        }

        $data = $user->toArray();
        if (!empty($data['phone'])) {
            $data['phone'] = preg_replace('/^(\d{3})\d+(\d{4})$/', '$1****$2', $data['phone']);
        }
        if (!empty($data['real_name'])) {
            $data['real_name'] = mb_substr($data['real_name'], 0, 1) . '**';
        }

        // 订单历史
        $orderPage   = (int) $request->input('order_page', 1);
        $orderLimit  = (int) $request->input('order_limit', 10);
        $ordersQuery = Order::where('user_id', $id)->with(['items', 'payment']);
        $orderTotal  = $ordersQuery->count();
        $orders      = $ordersQuery->offset(($orderPage - 1) * $orderLimit)
                                   ->limit($orderLimit)
                                   ->orderBy('id', 'desc')
                                   ->get()
                                   ->map(fn($o) => $this->encodeIds($o->toArray()));

        // 有效会员卡
        $cards = UserMemberCard::where('user_id', $id)
            ->where('status', 'active')
            ->with('memberCard')
            ->get()
            ->map(fn($c) => $this->encodeIds($c->toArray()));

        // 消费统计
        $data['total_spent'] = Order::where('user_id', $id)->where('status', 'completed')->sum('paid_amount');

        return $this->success([
            'user'   => $this->encodeIds($data),
            'orders' => [
                'list'  => $orders,
                'total' => $orderTotal,
                'page'  => $orderPage,
                'limit' => $orderLimit,
            ],
            'active_cards' => $cards,
        ]);
    }

    /**
     * 设置会员等级
     */
    public function updateLevel(Request $request, string $hashid): Response
    {
        $id   = $this->decodeId($hashid);
        $user = User::find($id);
        if (!$user) {
            return $this->fail('用户不存在', 404);
        }

        $level = (int) $request->input('member_level', 0);
        $user->member_level = $level;
        $user->save();

        return $this->success($this->encodeIds($user->toArray()), '会员等级更新成功');
    }

    /**
     * 更改推荐人
     */
    public function updateParent(Request $request, string $hashid): Response
    {
        $id   = $this->decodeId($hashid);
        $user = User::find($id);
        if (!$user) {
            return $this->fail('用户不存在', 404);
        }

        $referrerId = $request->input('referrer_id', '');
        if (empty($referrerId)) {
            $user->referrer_id = null;
        } else {
            $referrer = User::find($referrerId);
            if (!$referrer) {
                return $this->fail('推荐人不存在', 404);
            }
            if ($referrerId === (string) $id) {
                return $this->fail('不能将自己设为推荐人', 422);
            }
            $user->referrer_id = $referrerId;
        }
        $user->save();

        return $this->success($this->encodeIds($user->toArray()), '推荐人更新成功');
    }
}
