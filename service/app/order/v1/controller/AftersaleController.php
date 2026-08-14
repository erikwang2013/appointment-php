<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\order\v1\controller;

use app\common\BaseController;
use app\model\Order;
use app\model\OrderAftersale;
use Webman\Http\Request;

/**
 * 售后（退换货）控制器
 *
 * 用户申请售后（refund=仅退款 / exchange=换货）、查看我的售后列表与详情。
 * 审核动作在管理端 /admin/aftersales/{id}/review 完成。
 */
class AftersaleController extends BaseController
{
    /**
     * 申请售后
     * POST /api/aftersales
     *
     * 校验：订单属于本人（404）、订单状态 paid/completed（422）、
     * 同订单无 pending/approved 的既有售后（422）。
     */
    public function store(Request $request)
    {
        $userId = $request->user_id;

        $type = (string) $request->input('type', '');
        if (!in_array($type, [OrderAftersale::TYPE_REFUND, OrderAftersale::TYPE_EXCHANGE], true)) {
            return $this->error('售后类型无效', 422);
        }

        $orderId = $this->decodeId((string) $request->input('order_id', ''));
        if ($orderId === null) {
            return $this->error('订单不存在', 404);
        }

        $reason = trim((string) $request->input('reason', ''));
        if ($reason === '') {
            return $this->error('请填写售后原因', 422);
        }

        $order = Order::where('user_id', $userId)->where('id', $orderId)->first();
        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        if (!in_array($order->status, [Order::STATUS_PAID, Order::STATUS_COMPLETED], true)) {
            return $this->error('当前订单状态不支持申请售后', 422);
        }

        // 同订单存在进行中的售后（待审核/已通过）则拒绝重复申请
        $active = OrderAftersale::where('order_id', $order->id)
            ->whereIn('status', [OrderAftersale::STATUS_PENDING, OrderAftersale::STATUS_APPROVED])
            ->exists();
        if ($active) {
            return $this->error('该订单已存在进行中的售后申请', 422);
        }

        $aftersale = OrderAftersale::create([
            'id'            => OrderAftersale::generateId(),
            'aftersale_no'  => OrderAftersale::generateAftersaleNo(),
            'order_id'      => $order->id,
            'user_id'       => $userId,
            'type'          => $type,
            'reason'        => $reason,
            'status'        => OrderAftersale::STATUS_PENDING,
            'refund_amount' => $type === OrderAftersale::TYPE_REFUND ? (float) $order->paid_amount : 0.00,
        ]);

        return $this->success($aftersale, '售后申请提交成功');
    }

    /**
     * 我的售后列表
     * GET /api/aftersales?page=1&limit=15
     */
    public function index(Request $request)
    {
        $userId = $request->user_id;
        $page   = (int) $request->input('page', 1);
        $limit  = (int) $request->input('limit', 15);

        $paginator = OrderAftersale::where('user_id', $userId)
            ->with('order')
            ->orderBy('created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

        return $this->paginate($paginator);
    }

    /**
     * 售后详情
     * GET /api/aftersales/{id}
     */
    public function show(Request $request, string $id)
    {
        $id = $this->decodeId((string) $id);
        if ($id === null) {
            return $this->error('售后记录不存在', 404);
        }

        $aftersale = OrderAftersale::with('order')
            ->where('user_id', $request->user_id)
            ->where('id', $id)
            ->first();

        if (!$aftersale) {
            return $this->error('售后记录不存在', 404);
        }

        return $this->success($aftersale);
    }
}
