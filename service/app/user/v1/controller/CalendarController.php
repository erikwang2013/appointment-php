<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\user\v1\controller;

use app\common\BaseController;
use app\model\Order;
use Webman\Http\Request;

/**
 * 预约日历控制器
 * 用户查看个人预约月视图/周视图
 */
class CalendarController extends BaseController
{
    /**
     * 月视图
     * GET /api/user/calendar/month/{year}/{month}
     *
     * 返回用户在该月每一天的预约概况
     */
    public function month(Request $request, string $year, string $month)
    {
        $userId = $request->user_id;

        $year  = (int)$year;
        $month = (int)$month;

        if ($year < 2020 || $year > 2099 || $month < 1 || $month > 12) {
            return $this->error('年份或月份无效');
        }

        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate   = date('Y-m-t', strtotime($startDate));

        $orders = Order::where('user_id', $userId)
            ->whereBetween('service_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereNotIn('status', [Order::STATUS_CANCELLED, Order::STATUS_REFUNDED])
            ->orderBy('service_time')
            ->get()
            ->groupBy(function ($order) {
                return $order->service_time->format('Y-m-d');
            });

        $days = [];
        $current = strtotime($startDate);
        $lastDay = strtotime($endDate);

        while ($current <= $lastDay) {
            $dateKey = date('Y-m-d', $current);
            $dayOrders = $orders->get($dateKey, collect());

            $days[] = [
                'date'       => $dateKey,
                'day'        => (int)date('d', $current),
                'weekday'    => (int)date('w', $current),
                'count'      => $dayOrders->count(),
                'first_order' => $dayOrders->isNotEmpty()
                    ? [
                        'order_id'      => $dayOrders->first()->id,
                        'service_time'  => $dayOrders->first()->service_time,
                        'status'        => $dayOrders->first()->status,
                        'technician_name' => $dayOrders->first()->technician->nickname ?? '',
                    ]
                    : null,
            ];

            $current = strtotime('+1 day', $current);
        }

        return $this->success([
            'year'  => $year,
            'month' => $month,
            'days'  => $days,
        ]);
    }

    /**
     * 周视图
     * GET /api/user/calendar/week/{date}
     *
     * 返回以 date 所在周的时间槽视图
     */
    public function week(Request $request, string $date)
    {
        $userId = $request->user_id;

        $timestamp = strtotime($date);
        if (!$timestamp) {
            return $this->error('日期格式无效');
        }

        // 计算周起止日期（周一至周日）
        $weekday = (int)date('N', $timestamp); // 1=周一, 7=周日
        $monday = strtotime("-" . ($weekday - 1) . " days", $timestamp);
        $sunday = strtotime("+" . (7 - $weekday) . " days", $timestamp);

        $startDate = date('Y-m-d', $monday);
        $endDate   = date('Y-m-d', $sunday);

        // 获取该周用户的预约
        $orders = Order::where('user_id', $userId)
            ->whereBetween('service_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereNotIn('status', [Order::STATUS_CANCELLED, Order::STATUS_REFUNDED])
            ->orderBy('service_time')
            ->get()
            ->groupBy(function ($order) {
                return $order->service_time->format('Y-m-d');
            });

        // 构建每天的时间槽（按小时划分）
        $timeSlots = [];
        $hours = range(8, 20); // 8:00 - 20:00

        $current = $monday;
        while ($current <= $sunday) {
            $dateKey = date('Y-m-d', $current);
            $dayOrders = $orders->get($dateKey, collect());

            $slots = [];
            foreach ($hours as $hour) {
                $slotStart = sprintf('%02d:00', $hour);
                $slotEnd   = sprintf('%02d:00', $hour + 1);

                $bookedOrder = $dayOrders->first(function ($order) use ($hour) {
                    $orderHour = (int)$order->service_time->format('H');
                    return $orderHour === $hour;
                });

                $slots[] = [
                    'time'   => "{$slotStart}-{$slotEnd}",
                    'booked' => $bookedOrder !== null,
                    'order'  => $bookedOrder ? [
                        'order_id'     => $bookedOrder->id,
                        'service_time' => $bookedOrder->service_time->format('H:i'),
                        'status'       => $bookedOrder->status,
                    ] : null,
                ];
            }

            $timeSlots[] = [
                'date'   => $dateKey,
                'day'    => (int)date('d', $current),
                'weekday'=> (int)date('N', $current),
                'slots'  => $slots,
            ];

            $current = strtotime('+1 day', $current);
        }

        return $this->success([
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'days'       => $timeSlots,
        ]);
    }
}
