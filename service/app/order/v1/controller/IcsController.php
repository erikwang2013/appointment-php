<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\order\v1\controller;

use app\common\BaseController;
use app\model\Order;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * ICS 日历导出控制器
 * 导出当前用户最近 90 天内进行中（pending/paid/confirmed/serving）的预约订单为 iCal 文件，
 * 供用户下载导入手机日历。
 */
class IcsController extends BaseController
{
    /** 导出状态：未完成、未取消的预约 */
    private const EXPORT_STATUSES = [
        Order::STATUS_PENDING,
        Order::STATUS_PAID,
        Order::STATUS_CONFIRMED,
        Order::STATUS_SERVING,
    ];

    /** 默认预约时长（小时），用于生成 DTEND */
    private const DEFAULT_DURATION_HOURS = 1;

    /** iCal 文本行最长长度（RFC5545 3.1：不超过 75 字节，多出折叠） */
    private const LINE_LIMIT = 75;

    /**
     * 导出我的预约日历
     * GET /api/order/ics
     *
     * @param Request $request
     * @return Response
     */
    public function export(Request $request): Response
    {
        $userId = $request->user_id;
        $since = date('Y-m-d H:i:s', strtotime('-90 days'));

        $orders = Order::with(['items', 'technician', 'store'])
            ->where('user_id', $userId)
            ->whereIn('status', self::EXPORT_STATUSES)
            ->where('service_time', '>=', $since)
            ->orderBy('service_time', 'asc')
            ->get();

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Erik//Appointment Service//CN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
        ];

        foreach ($orders as $order) {
            $lines = array_merge($lines, $this->buildEvent($order));
        }

        $lines[] = 'END:VCALENDAR';
        $ics = $this->foldLines(implode("\r\n", $lines) . "\r\n");

        return new Response(200, [
            'Content-Type'        => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="my-appointments.ics"',
        ], $ics);
    }

    /**
     * 构建单个 VEVENT 文本行
     *
     * @param Order $order
     * @return string[]
     */
    private function buildEvent(Order $order): array
    {
        $serviceTime = $order->service_time instanceof \DateTimeInterface
            ? $order->service_time
            : new \DateTimeImmutable($order->service_time ?? 'now');
        $serviceTime = \DateTimeImmutable::createFromInterface($serviceTime)
            ->setTimezone(new \DateTimeZone('Asia/Shanghai'));

        $name = $order->items->first()->name ?? '';
        $summary = '预约' . ($name !== '' ? '：' . $name : '');
        $location = $order->store->name ?? '未指定门店';
        $description = $this->buildDescription($order);

        $start = $serviceTime->format('Ymd\THis');
        $end = $serviceTime->modify('+' . self::DEFAULT_DURATION_HOURS . ' hours')->format('Ymd\THis');
        $stamp = gmdate('Ymd\THis\Z');

        return [
            'BEGIN:VEVENT',
            'UID:' . $order->id,
            'DTSTAMP:' . $stamp,
            'DTSTART;TZID=Asia/Shanghai:' . $start,
            'DTEND;TZID=Asia/Shanghai:' . $end,
            'SUMMARY:' . $this->escapeText($summary),
            'DESCRIPTION:' . $this->escapeText($description),
            'LOCATION:' . $this->escapeText($location),
            'STATUS:CONFIRMED',
            'END:VEVENT',
        ];
    }

    /**
     * 生成 DESCRIPTION：技师 / 门店 / 地址，缺失项跳过
     */
    private function buildDescription(Order $order): string
    {
        $parts = [];
        if ($order->technician && $order->technician->nickname) {
            $parts[] = '技师：' . $order->technician->nickname;
        }
        if ($order->store) {
            if ($order->store->name) {
                $parts[] = '门店：' . $order->store->name;
            }
            if ($order->store->address) {
                $parts[] = '地址：' . $order->store->address;
            }
        }
        return implode("\n", $parts);
    }

    /**
     * RFC5545 文本转义：反斜杠 / 分号 / 逗号 / 换行
     */
    private function escapeText(string $text): string
    {
        $text = str_replace(['\\', ';', ','], ['\\\\', '\\;', '\\,'], $text);
        return str_replace(["\r\n", "\r", "\n"], '\\n', $text);
    }

    /**
     * RFC5545 行折叠：超过 75 字节的行在空格后换行（CRLF + 空格续行）
     */
    private function foldLines(string $ics): string
    {
        $out = '';
        foreach (explode("\r\n", $ics) as $line) {
            if ($line === '') {
                $out .= "\r\n";
                continue;
            }
            $out .= $this->foldLine($line);
        }
        return $out;
    }

    private function foldLine(string $line): string
    {
        $bytes = strlen($line);
        if ($bytes <= self::LINE_LIMIT) {
            return $line . "\r\n";
        }
        $chunks = [];
        for ($i = 0; $i < $bytes; $i += self::LINE_LIMIT - 1) {
            $chunks[] = substr($line, $i, self::LINE_LIMIT - 1);
        }
        return implode("\r\n ", $chunks) . "\r\n";
    }
}
