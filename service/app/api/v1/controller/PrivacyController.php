<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\model\Invoice;
use app\model\Order;
use app\model\OrderReview;
use app\model\Ticket;
use app\model\User;
use app\model\UserAddress;
use app\model\UserPoints;
use app\model\UserWallet;
use app\model\WalletTxn;
use support\Log;
use Webman\Http\Request;

/**
 * 隐私合规控制器
 * 用户数据导出 + 账号注销闭环（申请 / 撤销 / 二次确认）
 */
class PrivacyController extends BaseController
{
    /** 确认注销需距申请至少的小时数 */
    private const CLOSE_WAIT_HOURS = 72;

    /**
     * 导出本人全部数据（JSON 分组返回）
     * GET /api/privacy/data
     */
    public function data(Request $request)
    {
        $userId = $request->user_id;

        $user = User::find($userId);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        $data = [
            'personal' => [
                'id' => $user->id,
                'phone' => $user->phone,
                'nickname' => $user->nickname,
                'avatar' => $user->avatar,
                'gender' => $user->gender,
                'user_type' => $user->user_type,
                'created_at' => $user->created_at ? $user->created_at->format('Y-m-d H:i:s') : null,
            ],
            'orders' => $this->orders($userId),
            'points' => $this->points($userId),
            'wallet_txns' => $this->walletTxns($userId),
            'reviews' => $this->reviews($userId),
            'addresses' => $this->addresses($userId),
            'invoices' => $this->invoices($userId),
            'exported_at' => date('Y-m-d H:i:s'),
        ];

        // 日志脱敏：只记 user_id 与各类条数，不落敏感字段明文
        Log::info('[Privacy] 用户数据导出', [
            'user_id' => $userId,
            'phone' => $this->maskPhone($user->phone),
            'counts' => [
                'orders' => count($data['orders']),
                'points' => count($data['points']),
                'wallet_txns' => count($data['wallet_txns']),
                'reviews' => count($data['reviews']),
                'addresses' => count($data['addresses']),
                'invoices' => count($data['invoices']),
            ],
        ]);

        return $this->success($data, 'success');
    }

    /**
     * 申请注销（校验余额为 0、无未完成订单、无进行中工单）
     * POST /api/privacy/close-request
     */
    public function closeRequest(Request $request)
    {
        $userId = $request->user_id;

        $user = User::find($userId);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        if ((int) $user->close_status === 2) {
            return $this->error('账号已注销', 409);
        }
        if ((int) $user->close_status === 1) {
            return $this->error('已提交注销申请，可在冷却期内撤销');
        }

        // 余额必须为 0（以钱包表余额为准，无记录视为 0）
        $balance = (float) UserWallet::where('user_id', $userId)->value('balance');
        if (UserWallet::toCents($balance) > 0) {
            return $this->error('账户余额不为 0，请先消费或提现', 422);
        }

        // 未完成订单（pending/paid/confirmed/serving/refunding）
        $unfinished = Order::where('user_id', $userId)
            ->whereNotIn('status', [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED, Order::STATUS_REFUNDED])
            ->exists();
        if ($unfinished) {
            return $this->error('存在未完成订单，请先处理后再申请注销', 422);
        }

        // 进行中工单（pending/processing）
        $activeTicket = Ticket::where('user_id', $userId)
            ->whereIn('status', ['pending', 'processing'])
            ->exists();
        if ($activeTicket) {
            return $this->error('存在进行中的工单，请先处理后再申请注销', 422);
        }

        $user->forceFill([
            'close_status' => 1,
            'close_requested_at' => date('Y-m-d H:i:s'),
        ])->save();

        Log::info('[Privacy] 申请注销', ['user_id' => $userId]);

        return $this->success([
            'close_status' => 1,
            'close_requested_at' => $user->close_requested_at,
        ], '注销申请已提交，72小时后可确认注销');
    }

    /**
     * 撤销注销申请
     * POST /api/privacy/close-cancel
     */
    public function closeCancel(Request $request)
    {
        $userId = $request->user_id;

        $user = User::find($userId);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }
        if ((int) $user->close_status !== 1) {
            return $this->error('当前没有待确认的注销申请');
        }

        $user->forceFill([
            'close_status' => 0,
            'close_requested_at' => null,
        ])->save();

        Log::info('[Privacy] 撤销注销申请', ['user_id' => $userId]);

        return $this->success([
            'close_status' => 0,
        ], '已撤销注销申请');
    }

    /**
     * 二次确认注销（申请满 72 小时后可执行）
     * POST /api/privacy/close-confirm
     */
    public function closeConfirm(Request $request)
    {
        $userId = $request->user_id;

        $user = User::find($userId);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }
        if ((int) $user->close_status !== 1) {
            return $this->error('未提交注销申请或申请已失效');
        }

        $requestedAt = $user->close_requested_at;
        if (!$requestedAt || strtotime($requestedAt) + self::CLOSE_WAIT_HOURS * 3600 > time()) {
            return $this->error('距申请注销未满' . self::CLOSE_WAIT_HOURS . '小时，无法确认注销', 422);
        }

        // 注销：手机号/昵称匿名化、状态禁用
        $anonymized = 'user' . $user->id;
        $user->forceFill([
            'close_status' => 2,
            'close_at' => date('Y-m-d H:i:s'),
            'phone' => $anonymized,
            'nickname' => $anonymized,
            'status' => 0,
        ])->save();

        Log::info('[Privacy] 确认注销完成', ['user_id' => $userId]);

        return $this->success([
            'close_status' => 2,
            'close_at' => $user->close_at,
        ], '账号已注销');
    }

    private function orders(string $userId): array
    {
        return Order::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['order_no', 'order_type', 'service_time', 'status', 'total_amount', 'discount_amount', 'paid_amount', 'cancel_reason', 'created_at'])
            ->map(fn(Order $o) => $o->toArray())
            ->all();
    }

    private function points(string $userId): array
    {
        return UserPoints::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['type', 'points', 'balance', 'source', 'order_id', 'description', 'expires_at', 'created_at'])
            ->map(fn(UserPoints $p) => $p->toArray())
            ->all();
    }

    private function walletTxns(string $userId): array
    {
        return WalletTxn::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['type', 'amount', 'balance_after', 'order_id', 'recharge_id', 'remark', 'created_at'])
            ->map(fn(WalletTxn $t) => $t->toArray())
            ->all();
    }

    private function reviews(string $userId): array
    {
        return OrderReview::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get(['order_id', 'technician_id', 'rating', 'content', 'images', 'reply', 'append_content', 'created_at'])
            ->map(fn(OrderReview $r) => $r->toArray())
            ->all();
    }

    private function addresses(string $userId): array
    {
        return UserAddress::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get(['contact_name', 'contact_phone', 'province', 'city', 'district', 'detail', 'is_default', 'created_at'])
            ->map(fn(UserAddress $a) => $a->toArray())
            ->all();
    }

    private function invoices(string $userId): array
    {
        return Invoice::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get(['order_id', 'order_type', 'title_type', 'invoice_title', 'tax_no', 'amount', 'status', 'issued_no', 'issued_at', 'created_at'])
            ->map(fn(Invoice $i) => $i->toArray())
            ->all();
    }

    private function maskPhone(string $phone): string
    {
        if (strlen($phone) >= 7) {
            return substr($phone, 0, 3) . '****' . substr($phone, -4);
        }
        return $phone;
    }
}
