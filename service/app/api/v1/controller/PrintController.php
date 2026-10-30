<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\common\BluetoothPrintService;
use app\model\Order;
use app\model\TechnicianProfile;
use Webman\Http\Request;

/**
 * 打印控制器
 *
 * 生成订单小票的打印数据和 HTML 预览。
 * 实际蓝牙连接由前端（小程序/App）通过蓝牙 API 完成。
 */
class PrintController extends BaseController
{
    private BluetoothPrintService $printService;

    public function __construct()
    {
        $this->printService = new BluetoothPrintService();
    }

    /**
     * 订单可见性：下单用户本人或订单所属技师
     * Order.technician_id 存 TechnicianProfile.id，技师需按 user_id 反查档案
     */
    private function canAccessOrder(Order $order, mixed $userId): bool
    {
        if ((string) $order->user_id === (string) $userId) {
            return true;
        }
        $profile = TechnicianProfile::where('user_id', $userId)->first();
        return $profile !== null && (string) $order->technician_id === (string) $profile->id;
    }

    /**
     * 获取小票打印数据
     *
     * GET /api/print/receipt/{order_id}
     *
     * 返回格式化的小票打印数据（包含 base64 编码的 ESC/POS 文本），
     * 前端解码后通过蓝牙发送到热敏打印机。
     *
     * @param Request $request
     * @param string $order_id Hashids 编码的订单ID
     * @return \Webman\Http\Response
     */
    public function receipt(Request $request, string $order_id)
    {
        $userId = $request->user_id;
        $orderId = $this->decodeId($order_id);

        if (!$orderId) {
            return $this->error('订单ID无效');
        }

        // 验证订单归属
        $order = Order::find($orderId);
        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        if (!$this->canAccessOrder($order, $userId)) {
            return $this->error('无权打印此订单', 403);
        }

        $result = $this->printService->printReceipt((string) $orderId);

        if (isset($result['error'])) {
            return $this->error($result['error']);
        }

        return $this->success($result);
    }

    /**
     * 获取小票 HTML 预览
     *
     * GET /api/print/preview/{order_id}
     *
     * 返回 58mm 小票的 HTML 预览页面，适用于屏幕查看或截图分享。
     *
     * @param Request $request
     * @param string $order_id Hashids 编码的订单ID
     * @return \Webman\Http\Response
     */
    public function preview(Request $request, string $order_id)
    {
        $userId = $request->user_id;
        $orderId = $this->decodeId($order_id);

        if (!$orderId) {
            return $this->error('订单ID无效');
        }

        $order = Order::find($orderId);
        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        if (!$this->canAccessOrder($order, $userId)) {
            return $this->error('无权预览此订单', 403);
        }

        $result = $this->printService->previewReceipt((string) $orderId);

        if (isset($result['error'])) {
            return $this->error($result['error']);
        }

        return $this->success($result);
    }
}
