<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\Order;
use app\model\OrderPayment;
use app\model\OrderRefund;
use app\model\TechnicianEarning;
use app\model\TechnicianWithdrawal;
use support\Redis;
use support\Request;
use support\Response;

class FinanceController extends BaseController
{
    /**
     * 财务流水列表
     * 搜索: finance_no / type / uid / date
     */
    public function transactions(Request $request): Response
    {
        $page       = (int) $request->input('page', 1);
        $limit      = (int) $request->input('limit', 15);
        $financeNo  = $request->input('finance_no', '');
        $type       = $request->input('type', ''); // payment / refund / withdrawal / commission
        $uid        = $request->input('uid', '');
        $dateStart  = $request->input('date_start', '');
        $dateEnd    = $request->input('date_end', '');

        // 从支付表查询
        $payments = $this->buildPaymentQuery($request)->get();
        // 从退款表查询
        $refunds = $this->buildRefundQuery($request)->get();
        // 从提现表查询
        $withdrawals = $this->buildWithdrawalQuery($request)->get();

        // 合并流水
        $transactions = [];
        foreach ($payments as $p) {
            $transactions[] = [
                'id'              => $p->id,
                'finance_no'      => $p->payment_no,
                'type'            => 'payment',
                'order_no'        => $p->order->order_no ?? '',
                'amount'          => $p->amount,
                'pay_type'        => $p->pay_type,
                'status'          => $p->status,
                'transaction_id'  => $p->transaction_id,
                'created_at'      => (string) $p->created_at,
            ];
        }
        foreach ($refunds as $r) {
            $transactions[] = [
                'id'         => $r->id,
                'finance_no' => $r->refund_no,
                'type'       => 'refund',
                'order_no'   => $r->order->order_no ?? '',
                'amount'     => -$r->amount,
                'reason'     => $r->reason,
                'status'     => $r->status,
                'created_at' => (string) $r->created_at,
            ];
        }
        foreach ($withdrawals as $w) {
            $technicianName = $w->technician->real_name ?? '';
            $transactions[] = [
                'id'               => $w->id,
                'finance_no'       => $w->withdrawal_no,
                'type'             => 'withdrawal',
                'technician_name'  => mb_substr($technicianName, 0, 1) . '**',
                'amount'           => -$w->amount,
                'actual_amount'    => -$w->actual_amount,
                'commission_fee'   => $w->commission_fee,
                'status'           => $w->status,
                'created_at'       => (string) $w->created_at,
            ];
        }

        // 按时间排序
        usort($transactions, function ($a, $b) {
            return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
        });

        // 分页
        $total = count($transactions);
        $slice = array_slice($transactions, ($page - 1) * $limit, $limit);

        return $this->success([
            'list'  => $slice,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 流水详情
     */
    public function show(Request $request, string $hashid): Response
    {
        $id = $this->decodeId($hashid);

        // 尝试从各表查询
        $payment = OrderPayment::with('order')->find($id);
        if ($payment) {
            return $this->success([
                'type' => 'payment',
                'data' => $this->encodeIds($payment->toArray()),
            ]);
        }

        $refund = OrderRefund::with('order')->find($id);
        if ($refund) {
            return $this->success([
                'type' => 'refund',
                'data' => $this->encodeIds($refund->toArray()),
            ]);
        }

        $withdrawal = TechnicianWithdrawal::with('technician')->find($id);
        if ($withdrawal) {
            return $this->success([
                'type' => 'withdrawal',
                'data' => $this->encodeIds($withdrawal->toArray()),
            ]);
        }

        return $this->fail('记录不存在', 404);
    }

    /**
     * 收入统计（按时间段）
     */
    public function stats(Request $request): Response
    {
        $dateStart = $request->input('date_start', date('Y-m-d', strtotime('-30 days')));
        $dateEnd   = $request->input('date_end', date('Y-m-d'));

        $cacheKey = "finance_stats:{$dateStart}_{$dateEnd}";
        $cached   = Redis::get($cacheKey);
        if ($cached) {
            return $this->success(json_decode($cached, true));
        }

        // 收入（支付成功）
        $revenue = OrderPayment::whereBetween('paid_at', [$dateStart . ' 00:00:00', $dateEnd . ' 23:59:59'])
            ->where('status', 'success')
            ->sum('amount');

        // 退款
        $refunds = OrderRefund::whereBetween('refunded_at', [$dateStart . ' 00:00:00', $dateEnd . ' 23:59:59'])
            ->where('status', 'success')
            ->sum('amount');

        // 提现
        $withdrawals = TechnicianWithdrawal::whereBetween('completed_at', [$dateStart . ' 00:00:00', $dateEnd . ' 23:59:59'])
            ->where('status', 'completed')
            ->sum('amount');

        // 佣金
        $commissions = TechnicianEarning::whereBetween('created_at', [$dateStart . ' 00:00:00', $dateEnd . ' 23:59:59'])
            ->where('type', 'commission')
            ->sum('amount');

        $data = [
            'date_start'    => $dateStart,
            'date_end'      => $dateEnd,
            'revenue'       => $revenue,
            'refunds'       => $refunds,
            'withdrawals'   => $withdrawals,
            'commissions'   => $commissions,
            'net_revenue'   => $revenue - $refunds - $commissions,
            'net_income'    => $revenue - $refunds - $withdrawals,
        ];

        Redis::setex($cacheKey, 300, json_encode($data, JSON_UNESCAPED_UNICODE));

        return $this->success($data);
    }

    private function buildPaymentQuery(Request $request)
    {
        $financeNo = $request->input('finance_no', '');
        $uid       = $request->input('uid', '');
        $dateStart = $request->input('date_start', '');
        $dateEnd   = $request->input('date_end', '');
        $type      = $request->input('type', '');

        if ($type && $type !== 'payment') {
            return OrderPayment::whereRaw('1 = 0');
        }

        $query = OrderPayment::with('order');
        if ($financeNo) {
            $query->where('payment_no', 'like', "%{$financeNo}%");
        }
        if ($uid || $dateStart || $dateEnd) {
            $query->whereHas('order', function ($q) use ($uid, $dateStart, $dateEnd) {
                if ($uid) {
                    $q->where('user_id', $uid);
                }
                if ($dateStart) {
                    $q->whereDate('created_at', '>=', $dateStart);
                }
                if ($dateEnd) {
                    $q->whereDate('created_at', '<=', $dateEnd);
                }
            });
        }

        return $query;
    }

    private function buildRefundQuery(Request $request)
    {
        $financeNo = $request->input('finance_no', '');
        $dateStart = $request->input('date_start', '');
        $dateEnd   = $request->input('date_end', '');
        $type      = $request->input('type', '');

        if ($type && $type !== 'refund') {
            return OrderRefund::whereRaw('1 = 0');
        }

        $query = OrderRefund::with('order');
        if ($financeNo) {
            $query->where('refund_no', 'like', "%{$financeNo}%");
        }
        if ($dateStart) {
            $query->whereDate('created_at', '>=', $dateStart);
        }
        if ($dateEnd) {
            $query->whereDate('created_at', '<=', $dateEnd);
        }

        return $query;
    }

    private function buildWithdrawalQuery(Request $request)
    {
        $financeNo = $request->input('finance_no', '');
        $dateStart = $request->input('date_start', '');
        $dateEnd   = $request->input('date_end', '');
        $type      = $request->input('type', '');

        if ($type && $type !== 'withdrawal') {
            return TechnicianWithdrawal::whereRaw('1 = 0');
        }

        $query = TechnicianWithdrawal::with('technician');
        if ($financeNo) {
            $query->where('withdrawal_no', 'like', "%{$financeNo}%");
        }
        if ($dateStart) {
            $query->whereDate('created_at', '>=', $dateStart);
        }
        if ($dateEnd) {
            $query->whereDate('created_at', '<=', $dateEnd);
        }

        return $query;
    }
}
