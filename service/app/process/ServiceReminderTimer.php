<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\process;

use app\common\NotificationReminderService;
use app\model\Notification;
use app\model\Order;
use app\model\User;
use support\Db;
use support\Log;
use Workerman\Timer;

/**
 * 服务开始前预约提醒定时器
 *
 * 每 60 秒扫描一次 service_time 落在 [now+1h, now+1h+60s) 窗口内、
 * 状态为 confirmed/serving 的 appointment 订单，为订单用户写站内通知
 * （type=service_reminder，内容含服务/技师/门店/时间），并挂接可配置降级的
 * 微信订阅消息（SCENE_REMINDER，未配置 WECHAT_SUBSCRIBE_TEMPLATE_REMINDER
 * 时仅站内通知）。
 *
 * 防重机制（与 PointsExpiryTimer 同三层）：
 * 1. 处理时对订单行 lockForUpdate + 站内通知查重（order_id + type），
 *    同订单至多一条 service_reminder 通知，并发进程在行锁上串行化；
 * 2. 扫描按 id 游标递增分页（BATCH_SIZE 一批），同一进程不重复扫到同一行；
 * 3. 订阅消息仅在通知行实际写入的扫描轮次产生，且推送成功才写 push_sent_at。
 */
class ServiceReminderTimer
{
    /** 扫描间隔（秒） */
    private const SCAN_INTERVAL = 60;

    /** 每批扫描行数 */
    private const BATCH_SIZE = 100;

    /** 提前提醒时间：服务开始前 1 小时 */
    private const REMIND_AHEAD_SECONDS = 3600;

    private const NOTIFY_TYPE  = 'service_reminder';
    private const NOTIFY_TITLE = '服务即将开始';

    public function __construct()
    {
        Timer::add(self::SCAN_INTERVAL, function (): void {
            $this->scanAndRemind();
        });
    }

    /**
     * 扫描 1 小时窗口内的预约订单并提醒（幂等，可重复调用）
     */
    public function scanAndRemind(): void
    {
        try {
            $cursor      = 0;
            $windowStart = date('Y-m-d H:i:s', time() + self::REMIND_AHEAD_SECONDS);
            $windowEnd   = date('Y-m-d H:i:s', time() + self::REMIND_AHEAD_SECONDS + self::SCAN_INTERVAL);

            while (true) {
                $rows = Order::with(['items', 'store'])
                    ->whereIn('status', [Order::STATUS_CONFIRMED, Order::STATUS_SERVING])
                    ->where('order_type', Order::ORDER_TYPE_APPOINTMENT)
                    ->where('service_time', '>=', $windowStart)
                    ->where('service_time', '<', $windowEnd)
                    ->where('id', '>', $cursor)
                    ->orderBy('id')
                    ->limit(self::BATCH_SIZE)
                    ->get();

                if ($rows->isEmpty()) {
                    break;
                }

                // 批量预取技师昵称，避免 N+1
                $technicians = $this->preloadTechnicians($rows);

                $sent = 0;
                foreach ($rows as $order) {
                    try {
                        if ($this->processOrder($order, $technicians)) {
                            $sent++;
                        }
                    } catch (\Throwable $e) {
                        Log::error('[ServiceReminderTimer] Failed to remind order '
                            . ($order->id ?? 'unknown') . ': ' . $e->getMessage());
                    }
                    $cursor = max($cursor, (int) $order->id);
                }

                if ($rows->count() < self::BATCH_SIZE) {
                    break; // 最后一批
                }
            }

            if (isset($sent) && $sent > 0) {
                Log::info('[ServiceReminderTimer] Sent ' . $sent . ' service reminders');
            }
        } catch (\Throwable $e) {
            Log::error('[ServiceReminderTimer] Scan error: ' . $e->getMessage()
                . "\n" . $e->getTraceAsString());
        }
    }

    /**
     * 处理单个订单：锁行 + 查重 + 写站内通知 + 订阅消息（幂等）
     *
     * @return bool 是否生成了提醒
     */
    private function processOrder(Order $order, array $technicians): bool
    {
        // 消息偏好：用户关闭服务提醒则不写站内通知（订阅消息一并跳过）
        if (!NotificationReminderService::notifySettingEnabled(
            (string) $order->user_id,
            NotificationReminderService::NOTIFY_TYPE_SERVICE_REMINDER
        )) {
            return false;
        }

        $notificationId = Db::transaction(function () use ($order, $technicians): ?string {
            // 重新查询并加锁，串行化并发扫描
            $locked = Order::where('id', $order->id)
                ->whereIn('status', [Order::STATUS_CONFIRMED, Order::STATUS_SERVING])
                ->lockForUpdate()
                ->first();

            if (!$locked) {
                return null; // 状态已变更（取消/退款/完成）或已被处理
            }

            // 幂等查重：同订单 + 同类型视为已提醒
            $exists = Notification::where('order_id', $order->id)
                ->where('type', self::NOTIFY_TYPE)
                ->exists();
            if ($exists) {
                return null;
            }

            $id = Notification::generateId();
            Db::table('erik_notification')->insert([
                'id'         => $id,
                'user_id'    => $order->user_id,
                'type'       => self::NOTIFY_TYPE,
                'title'      => self::NOTIFY_TITLE,
                'content'    => $this->buildContent($order, $technicians),
                'order_id'   => $order->id,
                'is_read'    => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return $id;
        });

        if ($notificationId === null) {
            return false; // 已提醒过或订单状态已变更
        }

        // 订阅消息钩子：未配置微信模板时仅站内通知（可配置降级）；失败不影响主流程
        (new NotificationReminderService())->sendSubscribeForNotification(
            NotificationReminderService::SCENE_REMINDER,
            (string) $order->user_id,
            $notificationId,
            $this->buildSubscribeData($order)
        );

        // R22 APP 推送：服务即将开始（未启用时静默降级，失败不影响主流程）
        try {
            \app\common\AppPushService::pushToUser(
                (int) $order->user_id,
                self::NOTIFY_TITLE,
                $this->buildContent($order, $technicians),
                ['type' => 'service_reminder', 'order_id' => (string) $order->id, 'order_no' => $order->order_no]
            );
        } catch (\Throwable $e) {
            Log::warning('[AppPush] service reminder push failed: ' . $e->getMessage());
        }

        return true;
    }

    // ── 内部方法 ──

    /** 批量预取技师昵称（id → nickname） */
    private function preloadTechnicians(iterable $orders): array
    {
        $technicianIds = [];
        foreach ($orders as $order) {
            if (!empty($order->technician_id)) {
                $technicianIds[] = (string) $order->technician_id;
            }
        }
        if (empty($technicianIds)) {
            return [];
        }
        return User::whereIn('id', $technicianIds)->pluck('nickname', 'id')->toArray();
    }

    /** 组装通知内容：服务 / 技师 / 门店 / 时间 */
    private function buildContent(Order $order, array $technicians): string
    {
        $serviceName = '';
        if (!empty($order->items)) {
            $names = [];
            foreach ($order->items as $item) {
                if (!empty($item->name)) {
                    $names[] = (string) $item->name;
                }
            }
            $serviceName = implode('、', $names);
        }

        $technicianName = (string) ($technicians[$order->technician_id] ?? '');

        $store = $order->store;
        $location = '';
        if ($store) {
            $location = trim((string) $store->name
                . (!empty($store->address) ? '（' . $store->address . '）' : ''));
        }

        $time = $order->service_time ? $order->service_time->format('Y-m-d H:i') : '';

        $parts = ['您的服务将在 ' . $time . ' 开始'];
        if ($serviceName !== '') {
            $parts[] = '服务：' . $serviceName;
        }
        if ($technicianName !== '') {
            $parts[] = '技师：' . $technicianName;
        }
        if ($location !== '') {
            $parts[] = '门店：' . $location;
        }
        $parts[] = '请准时到达，如有变动请联系客服。';

        return implode("\n", $parts);
    }

    /** 组装订阅消息 data：预约项目 / 开始时间 / 门店（thing 字段值上限 20 字符） */
    private function buildSubscribeData(Order $order): array
    {
        $serviceName = '';
        if (!empty($order->items)) {
            $names = [];
            foreach ($order->items as $item) {
                if (!empty($item->name)) {
                    $names[] = (string) $item->name;
                }
            }
            $serviceName = implode('、', $names);
        }

        return [
            'thing1' => ['value' => $this->truncate($serviceName, 20)],
            'time2'  => ['value' => $order->service_time ? $order->service_time->format('Y-m-d H:i') : ''],
            'thing3' => ['value' => $this->truncate((string) ($order->store->name ?? ''), 20)],
        ];
    }

    /** 按字符数截断（thing 字段值上限 20 字符，避免微信 47003 报错） */
    private function truncate(string $value, int $length): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $length);
        }
        return substr($value, 0, $length);
    }
}
