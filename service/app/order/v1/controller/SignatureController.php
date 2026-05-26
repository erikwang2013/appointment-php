<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\order\v1\controller;

use app\common\BaseController;
use app\model\Order;
use app\model\Signature;
use support\Log;
use Webman\Http\Request;

/**
 * 电子签名控制器
 *
 * 管理订单完成后的电子签名记录
 */
class SignatureController extends BaseController
{
    /**
     * 保存电子签名
     *
     * POST /api/order/signature
     *
     * 为指定订单保存签名图片链接，要求订单必须已完成
     * 且当前用户是该订单的用户或技师
     */
    public function store(Request $request)
    {
        $userId = $request->user_id;

        $orderId = $request->input('order_id');
        $imageUrl = $request->input('image_url', '');

        $orderId = $this->decodeId($orderId);

        if (!$orderId || empty($imageUrl)) {
            return $this->error('请提供完整的签名信息');
        }

        // 查找订单，验证归属
        $order = Order::with(['technician'])->find($orderId);

        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        // 验证当前用户是下单用户或技师
        $isOwner = $order->user_id === $userId;
        $isTechnician = $order->technician_id === $userId;

        if (!$isOwner && !$isTechnician) {
            return $this->error('无权为此订单签名', 403);
        }

        // 订单必须完成
        if ($order->status !== Order::STATUS_COMPLETED) {
            return $this->error('只能为已完成的订单签名');
        }

        // 检查是否已有签名
        $existing = Signature::where('order_id', $orderId)->first();
        if ($existing) {
            return $this->error('该订单已有签名记录');
        }

        try {
            $signature = Signature::create([
                'id'             => Signature::generateId(),
                'order_id'       => $orderId,
                'user_id'        => $order->user_id,
                'technician_id'  => $order->technician_id,
                'image_url'      => $imageUrl,
                'signed_at'      => now(),
            ]);

            Log::info("[Signature] created for order {$orderId} by user {$userId}");

            return $this->success($signature, '签名保存成功');
        } catch (\Throwable $e) {
            Log::error("[Signature] store failed: {$e->getMessage()}");
            return $this->error('签名保存失败');
        }
    }

    /**
     * 获取订单签名
     *
     * GET /api/order/signature/{order_id}
     *
     * 获取指定订单的电子签名，需要是订单相关人员
     */
    public function show(Request $request, string $orderId)
    {
        $userId = $request->user_id;
        $orderId = $this->decodeId($orderId);

        if (!$orderId) {
            return $this->error('订单ID无效');
        }

        $order = Order::find($orderId);
        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        // 验证权限：下单用户或技师
        if ($order->user_id !== $userId && $order->technician_id !== $userId) {
            return $this->error('无权查看此签名', 403);
        }

        $signature = Signature::where('order_id', $orderId)->first();

        if (!$signature) {
            return $this->error('该订单暂无签名记录', 404);
        }

        return $this->success($signature);
    }
}
