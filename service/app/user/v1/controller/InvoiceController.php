<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\user\v1\controller;

use app\common\BaseController;
use app\model\Invoice;
use app\model\InvoiceTitle;
use app\model\Order;
use app\model\WalletRecharge;
use Illuminate\Database\QueryException;
use Webman\Http\Request;

/**
 * 用户电子发票控制器
 *
 * POST /api/invoices          申请开票（order_id + order_type + 抬头信息）
 * GET  /api/invoices          我的发票分页（?status=&per_page=）
 * GET  /api/invoices/{id}     我的发票详情（非本人 404）
 *
 * 规则：
 * 1. 仅本人订单/充值单可开票（非本人 404）；
 * 2. 一单仅开一次：uk_order_type 唯一键兜底，并发冲突捕获 1062 返回 422；
 * 3. 服务订单须 status=completed，充值须 status=paid，否则 422；
 * 4. 金额服务端自动带出（服务订单=paid_amount，充值=充值金额），不接受客户端传值；
 * 5. 企业抬头（company）税号必填，邮箱可选但格式校验。
 */
class InvoiceController extends BaseController
{
    /** 开票状态白名单 */
    private const STATUSES = [
        Invoice::STATUS_PENDING,
        Invoice::STATUS_ISSUED,
        Invoice::STATUS_REJECTED,
    ];

    /**
     * 申请开票
     * POST /api/invoices
     */
    public function store(Request $request)
    {
        $userId    = (string) $request->user_id;
        $orderId   = $this->decodeId((string) $request->input('order_id', ''));
        $orderType = (string) $request->input('order_type', '');
        $titleType = (string) $request->input('title_type', '');
        $title     = trim((string) $request->input('invoice_title', ''));
        $taxNo     = trim((string) $request->input('tax_no', ''));
        $email     = trim((string) $request->input('email', ''));

        // 常用抬头带入：传 title_id 时抬头信息取自抬头库，忽略请求参数
        $titleId = $this->decodeId((string) $request->input('title_id', ''));
        if ($titleId !== null) {
            $titleRow = InvoiceTitle::where('id', $titleId)->where('user_id', $userId)->first();
            if (!$titleRow) {
                return $this->error('抬头不存在', 404);
            }
            $titleType = (string) $titleRow->title_type;
            $title     = (string) $titleRow->invoice_title;
            $taxNo     = trim((string) ($titleRow->tax_no ?? ''));
        }

        if ($orderId === null) {
            return $this->error('订单不存在', 404);
        }
        if (!in_array($orderType, [Invoice::ORDER_TYPE_SERVICE, Invoice::ORDER_TYPE_RECHARGE], true)) {
            return $this->error('订单类型无效', 422);
        }
        if (!in_array($titleType, [Invoice::TITLE_TYPE_PERSONAL, Invoice::TITLE_TYPE_COMPANY], true)) {
            return $this->error('抬头类型无效', 422);
        }
        if ($title === '') {
            return $this->error('发票抬头不能为空', 422);
        }
        if ($titleType === Invoice::TITLE_TYPE_COMPANY && $taxNo === '') {
            return $this->error('企业抬头必须填写税号', 422);
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('邮箱格式不正确', 422);
        }

        // 归属 + 状态 + 金额（金额由服务端带出，不接受客户端传值）
        if ($orderType === Invoice::ORDER_TYPE_SERVICE) {
            $order = Order::find($orderId);
            if (!$order || (string) $order->user_id !== $userId) {
                return $this->error('订单不存在', 404);
            }
            if ($order->status !== Order::STATUS_COMPLETED) {
                return $this->error('订单完成后才能申请开票', 422);
            }
            $amount = (string) $order->paid_amount;
        } else {
            $recharge = WalletRecharge::find($orderId);
            if (!$recharge || (string) $recharge->user_id !== $userId) {
                return $this->error('充值单不存在', 404);
            }
            if ($recharge->status !== WalletRecharge::STATUS_PAID) {
                return $this->error('充值支付完成后才能申请开票', 422);
            }
            $amount = (string) $recharge->amount;
        }

        // 防重复：唯一键兜底 + 先查后插
        if (Invoice::where('order_id', $orderId)->where('order_type', $orderType)->exists()) {
            return $this->error('该订单已申请开票', 422);
        }

        try {
            $invoice = Invoice::create([
                'id'            => Invoice::generateId(),
                'user_id'       => $userId,
                'order_id'      => $orderId,
                'order_type'    => $orderType,
                'title_type'    => $titleType,
                'invoice_title' => $title,
                'tax_no'        => $taxNo !== '' ? $taxNo : null,
                'amount'        => $amount,
                'email'         => $email !== '' ? $email : null,
                'status'        => Invoice::STATUS_PENDING,
            ]);
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                return $this->error('该订单已申请开票', 422);
            }
            throw $e;
        }

        return $this->success($invoice, '开票申请提交成功');
    }

    /**
     * 我的发票列表（分页）
     * GET /api/invoices?status=&per_page=
     */
    public function index(Request $request)
    {
        $userId  = (string) $request->user_id;
        $status  = (string) $request->input('status', '');
        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);

        if ($status !== '' && !in_array($status, self::STATUSES, true)) {
            return $this->error('发票状态无效', 422);
        }

        $query = Invoice::where('user_id', $userId);
        if ($status !== '') {
            $query->where('status', $status);
        }

        $paginator = $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        return $this->paginate($paginator);
    }

    /**
     * 我的发票详情
     * GET /api/invoices/{id}
     */
    public function show(Request $request, ?string $id)
    {
        $userId    = (string) $request->user_id;
        $invoiceId = $this->decodeId($id);
        if ($invoiceId === null) {
            return $this->error('发票不存在', 404);
        }

        $invoice = Invoice::find($invoiceId);
        if (!$invoice || (string) $invoice->user_id !== $userId) {
            return $this->error('发票不存在', 404);
        }

        return $this->success($invoice);
    }
}
