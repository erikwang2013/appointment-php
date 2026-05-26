<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\common;

use app\model\Order;
use app\model\OrderItem;
use app\model\Store;

/**
 * 蓝牙打印服务
 *
 * 生成 58mm 热敏小票打印数据和 HTML 预览。
 * 前端负责实际的蓝牙连接与发送，后端提供格式化后的打印数据。
 */
class BluetoothPrintService
{
    /**
     * 打印宽度（毫米）
     */
    private const PRINT_WIDTH_MM = 58;

    /**
     * 每行字符数（英文等宽字体参考）
     */
    private const CHARS_PER_LINE = 32;

    /**
     * 构建小票打印数据
     *
     * 返回适合 58mm 热敏打印机的格式化数据，
     * 包含 ESC/POS 指令字节或纯文本数据，供前端通过蓝牙发送。
     *
     * @param string $orderId 订单ID
     * @return array{templates: array, order: array, print_data: string}|array{error: string}
     */
    public function printReceipt(string $orderId): array
    {
        $order = Order::with(['items', 'store', 'technician', 'user'])->find($orderId);

        if (!$order) {
            return ['error' => '订单不存在'];
        }

        $storeName = $order->store ? $order->store->name : '本店';
        $storePhone = $order->store ? ($order->store->phone ?? '') : '';

        // 构建打印模板数据
        $template = $this->buildReceiptTemplate($order, $storeName, $storePhone);

        // 生成纯文本打印数据（可直接转为 ESC/POS 字节流）
        $printData = $this->buildTextPrintData($order, $storeName, $storePhone);

        return [
            'templates' => $template,
            'order'     => $this->formatOrderForPrint($order),
            'print_data' => base64_encode($printData),
        ];
    }

    /**
     * 构建 HTML 预览数据
     *
     * @param string $orderId 订单ID
     * @return array{html: string, order: array}|array{error: string}
     */
    public function previewReceipt(string $orderId): array
    {
        $order = Order::with(['items', 'store', 'technician', 'user'])->find($orderId);

        if (!$order) {
            return ['error' => '订单不存在'];
        }

        $storeName = $order->store ? $order->store->name : '本店';
        $storePhone = $order->store ? ($order->store->phone ?? '') : '';

        $html = $this->buildHtmlPreview($order, $storeName, $storePhone);

        return [
            'html'  => $html,
            'order' => $this->formatOrderForPrint($order),
        ];
    }

    /**
     * 构建小票模板数据
     */
    private function buildReceiptTemplate(Order $order, string $storeName, string $storePhone): array
    {
        $lines = [];

        // 页眉
        $lines[] = ['type' => 'center', 'text' => $storeName, 'bold' => true, 'size' => 'large'];
        $lines[] = ['type' => 'divider', 'char' => '='];

        // 订单信息
        $lines[] = ['type' => 'left', 'text' => '订单编号: ' . $order->order_no];
        $lines[] = ['type' => 'left', 'text' => '下单时间: ' . ($order->created_at ? $order->created_at->format('Y-m-d H:i') : '')];
        $lines[] = ['type' => 'divider', 'char' => '-'];

        // 服务/商品列表
        $lines[] = ['type' => 'columns', 'columns' => ['名称', '数量', '金额'], 'bold' => true];
        foreach ($order->items as $item) {
            $lines[] = [
                'type'    => 'columns',
                'columns' => [
                    $this->truncateText($item->name, 14),
                    'x' . ($item->quantity ?? 1),
                    number_format(($item->price ?? 0) * ($item->quantity ?? 1), 2),
                ],
            ];
        }

        $lines[] = ['type' => 'divider', 'char' => '-'];

        // 金额汇总
        $lines[] = ['type' => 'right', 'text' => '合计: ' . number_format($order->total_amount, 2) . ' 元', 'bold' => true];

        if ($order->discount_amount > 0) {
            $lines[] = ['type' => 'right', 'text' => '优惠: -' . number_format($order->discount_amount, 2) . ' 元'];
        }

        $lines[] = ['type' => 'right', 'text' => '实付: ' . number_format($order->paid_amount, 2) . ' 元', 'bold' => true];

        // 预约信息
        if ($order->service_time) {
            $lines[] = ['type' => 'divider', 'char' => '-'];
            $lines[] = ['type' => 'left', 'text' => '预约时间: ' . $order->service_time->format('Y-m-d H:i')];
        }

        if ($order->technician) {
            $technicianProfile = $order->technician->technicianProfile;
            $techName = $technicianProfile ? ($technicianProfile->real_name ?: $order->technician->nickname) : $order->technician->nickname;
            $lines[] = ['type' => 'left', 'text' => '技师: ' . $techName];
        }

        // 页脚
        $lines[] = ['type' => 'divider', 'char' => '='];
        $lines[] = ['type' => 'center', 'text' => '感谢您的光临'];
        $lines[] = ['type' => 'center', 'text' => '联系电话: ' . $storePhone, 'size' => 'small'];

        return [
            'width_mm'    => self::PRINT_WIDTH_MM,
            'chars_per_line' => self::CHARS_PER_LINE,
            'lines'       => $lines,
        ];
    }

    /**
     * 生成纯文本打印数据（ESC/POS 可编码格式）
     */
    private function buildTextPrintData(Order $order, string $storeName, string $storePhone): string
    {
        $lines = [];

        // ESC/POS 初始化
        $esc = chr(27);
        $lines[] = $esc . '@'; // 初始化打印机

        // 居中 + 加粗
        $lines[] = $esc . 'a' . chr(1); // 居中
        $lines[] = $esc . 'E' . chr(1); // 加粗
        $lines[] = $storeName;
        $lines[] = $esc . 'E' . chr(0); // 取消加粗
        $lines[] = $esc . 'a' . chr(0); // 左对齐

        $lines[] = str_repeat('=', self::CHARS_PER_LINE);
        $lines[] = '订单编号: ' . $order->order_no;
        $lines[] = '下单时间: ' . ($order->created_at ? $order->created_at->format('Y-m-d H:i') : '');
        $lines[] = str_repeat('-', self::CHARS_PER_LINE);

        $lines[] = sprintf("%-16s %4s %8s", '名称', '数量', '金额');
        foreach ($order->items as $item) {
            $name = $this->truncateText($item->name, 12);
            $qty = 'x' . ($item->quantity ?? 1);
            $amt = number_format(($item->price ?? 0) * ($item->quantity ?? 1), 2);
            $lines[] = sprintf("%-16s %4s %8s", $name, $qty, $amt);
        }

        $lines[] = str_repeat('-', self::CHARS_PER_LINE);
        $lines[] = sprintf('%28s', '合计: ' . number_format($order->total_amount, 2) . ' 元');

        if ($order->discount_amount > 0) {
            $lines[] = sprintf('%28s', '优惠: -' . number_format($order->discount_amount, 2) . ' 元');
        }

        $lines[] = sprintf('%28s', '实付: ' . number_format($order->paid_amount, 2) . ' 元');

        if ($order->service_time) {
            $lines[] = str_repeat('-', self::CHARS_PER_LINE);
            $lines[] = '预约时间: ' . $order->service_time->format('Y-m-d H:i');
        }

        $lines[] = str_repeat('=', self::CHARS_PER_LINE);
        $lines[] = $esc . 'a' . chr(1); // 居中
        $lines[] = '感谢您的光临';
        $lines[] = '联系电话: ' . $storePhone;
        $lines[] = $esc . 'a' . chr(0); // 左对齐

        // 切纸
        $lines[] = $esc . 'i'; // 切纸命令

        return implode("\n", $lines);
    }

    /**
     * 构建 HTML 预览
     */
    private function buildHtmlPreview(Order $order, string $storeName, string $storePhone): string
    {
        $itemsHtml = '';
        foreach ($order->items as $item) {
            $itemsHtml .= sprintf(
                '<tr><td class="name">%s</td><td class="qty">x%d</td><td class="amt">%s</td></tr>',
                htmlspecialchars($item->name, ENT_QUOTES, 'UTF-8'),
                $item->quantity ?? 1,
                number_format(($item->price ?? 0) * ($item->quantity ?? 1), 2)
            );
        }

        $technicianInfo = '';
        if ($order->technician) {
            $techProfile = $order->technician->technicianProfile;
            $techName = $techProfile ? ($techProfile->real_name ?: $order->technician->nickname) : $order->technician->nickname;
            $technicianInfo = '<div class="info-line">技师: ' . htmlspecialchars($techName, ENT_QUOTES, 'UTF-8') . '</div>';
        }

        $serviceTimeInfo = '';
        if ($order->service_time) {
            $serviceTimeInfo = '<div class="info-line">预约时间: ' . $order->service_time->format('Y-m-d H:i') . '</div>';
        }

        $discountHtml = '';
        if ($order->discount_amount > 0) {
            $discountHtml = sprintf(
                '<div class="total-line"><span>优惠</span><span>- %s 元</span></div>',
                number_format($order->discount_amount, 2)
            );
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>小票预览</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Courier New', monospace; background: #e8e8e8; display: flex; justify-content: center; padding: 20px; }
.receipt { width: 58mm; background: #fff; padding: 8mm 4mm; font-size: 12px; }
.store-name { text-align: center; font-size: 16px; font-weight: bold; margin-bottom: 4px; }
.divider { border-top: 1px dashed #333; margin: 6px 0; }
.divider-solid { border-top: 1px solid #333; margin: 6px 0; }
.order-info { font-size: 11px; }
.order-info .label { color: #666; }
table.items { width: 100%; border-collapse: collapse; margin: 4px 0; }
table.items th { text-align: left; font-size: 11px; border-bottom: 1px solid #ddd; padding-bottom: 2px; }
table.items td { font-size: 11px; padding: 2px 0; }
table.items td.qty { text-align: center; }
table.items td.amt { text-align: right; }
.total-line { display: flex; justify-content: space-between; font-size: 11px; margin: 2px 0; }
.total-line.bold { font-weight: bold; font-size: 13px; }
.footer { text-align: center; font-size: 10px; margin-top: 8px; }
.info-line { font-size: 11px; margin: 2px 0; }
@media print {
  body { background: #fff; padding: 0; }
  .receipt { box-shadow: none; }
}
</style>
</head>
<body>
<div class="receipt">
  <div class="store-name">{$storeName}</div>
  <div class="divider-solid"></div>
  <div class="order-info">
    <div class="info-line">订单编号: {$order->order_no}</div>
    <div class="info-line">下单时间: {$order->created_at?->format('Y-m-d H:i')}</div>
  </div>
  <div class="divider"></div>
  <table class="items">
    <thead><tr><th>名称</th><th>数量</th><th>金额</th></tr></thead>
    <tbody>{$itemsHtml}</tbody>
  </table>
  <div class="divider"></div>
  <div class="total-line bold"><span>合计</span><span>{$order->total_amount} 元</span></div>
  {$discountHtml}
  <div class="total-line bold"><span>实付</span><span>{$order->paid_amount} 元</span></div>
  {$serviceTimeInfo}
  {$technicianInfo}
  <div class="divider-solid"></div>
  <div class="footer">
    <div>感谢您的光临</div>
    <div>联系电话: {$storePhone}</div>
  </div>
</div>
</body>
</html>
HTML;
    }

    /**
     * 格式化订单数据用于打印响应
     */
    private function formatOrderForPrint(Order $order): array
    {
        return [
            'order_id'       => $order->id,
            'order_no'       => $order->order_no,
            'total_amount'   => $order->total_amount,
            'discount_amount' => $order->discount_amount,
            'paid_amount'    => $order->paid_amount,
            'status'         => $order->status,
            'service_time'   => $order->service_time ? $order->service_time->format('Y-m-d H:i') : null,
            'created_at'     => $order->created_at ? $order->created_at->format('Y-m-d H:i') : null,
        ];
    }

    /**
     * 截断文本
     */
    private function truncateText(string $text, int $maxLen): string
    {
        if (mb_strlen($text) <= $maxLen) {
            return $text;
        }

        return mb_substr($text, 0, $maxLen - 2) . '..';
    }
}
