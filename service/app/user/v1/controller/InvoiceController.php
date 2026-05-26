<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\user\v1\controller;

use app\common\BaseController;
use app\model\Invoice;
use app\model\Order;
use Webman\Http\Request;

/**
 * 发票控制器
 * 申请发票、查看历史、发票详情
 */
class InvoiceController extends BaseController
{
    /**
     * 申请发票
     * POST /api/user/invoice
     */
    public function store(Request $request)
    {
        $userId = $request->user_id;

        $orderId = $this->decodeId($request->input('order_id', ''));
        $type    = $request->input('type', '');
        $title   = trim($request->input('title', ''));
        $taxNo   = trim($request->input('tax_no', ''));
        $email   = trim($request->input('email', ''));

        if (!$orderId) {
            return $this->error('订单ID无效');
        }

        if (!in_array($type, [Invoice::TYPE_PERSONAL, Invoice::TYPE_COMPANY], true)) {
            return $this->error('发票类型无效，仅支持 personal 或 company');
        }

        if (empty($title)) {
            return $this->error('发票抬头不能为空');
        }

        if ($type === Invoice::TYPE_COMPANY && empty($taxNo)) {
            return $this->error('企业发票需填写税号');
        }

        if (empty($email)) {
            return $this->error('请提供接收发票的邮箱');
        }

        // 校验订单是否属于当前用户且已完成
        $order = Order::where('user_id', $userId)
            ->where('id', $orderId)
            ->first();

        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        if ($order->status !== Order::STATUS_COMPLETED) {
            return $this->error('仅可对已完成的订单申请发票');
        }

        // 检查该订单是否已申请过发票
        $existing = Invoice::where('order_id', $orderId)->first();
        if ($existing) {
            return $this->error('该订单已申请过发票');
        }

        // 自动计算金额
        $amount = $order->paid_amount;

        $invoice = Invoice::create([
            'id'       => Invoice::generateId(),
            'user_id'  => $userId,
            'order_id' => $orderId,
            'type'     => $type,
            'title'    => $title,
            'tax_no'   => $type === Invoice::TYPE_COMPANY ? $taxNo : null,
            'email'    => $email,
            'amount'   => $amount,
            'status'   => Invoice::STATUS_PENDING,
        ]);

        return $this->success($invoice, '发票申请已提交');
    }

    /**
     * 用户发票列表
     * GET /api/user/invoice
     */
    public function index(Request $request)
    {
        $userId = $request->user_id;
        $status = $request->input('status', '');

        $query = Invoice::where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        if (!empty($status)) {
            if (!in_array($status, [Invoice::STATUS_PENDING, Invoice::STATUS_ISSUED], true)) {
                return $this->error('无效的状态筛选值');
            }
            $query->where('status', $status);
        }

        $perPage   = (int)$request->input('per_page', 15);
        $paginator = $query->with('order:id,order_no,paid_amount')->paginate($perPage);

        return $this->paginate($paginator);
    }

    /**
     * 发票详情
     * GET /api/user/invoice/{id}
     */
    public function show(Request $request, string $id)
    {
        $userId = $request->user_id;

        $invoiceId = $this->decodeId($id);
        if (!$invoiceId) {
            return $this->error('发票ID无效');
        }

        $invoice = Invoice::where('user_id', $userId)
            ->where('id', $invoiceId)
            ->with('order:id,order_no,paid_amount,service_time')
            ->first();

        if (!$invoice) {
            return $this->error('发票不存在', 404);
        }

        return $this->success($invoice);
    }
}
