<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\common;

use app\model\Notification;
use app\model\Order;
use app\model\OrderItem;
use app\model\Store;
use app\model\User;
use support\Db;
use support\Log;

/**
 * 预约前提醒服务（站内通知闭环）
 *
 * 扫描「服务开始前 2 小时 ~ 1 小时」窗口内的已支付预约单，
 * 为每个订单生成一条站内通知（erik_notification，type=order，
 * 标题固定「预约即将开始」），并挂接可配置降级的微信订阅消息钩子
 * （未配置 WECHAT_SUBSCRIBE_* 时仅站内通知）。
 *
 * 幂等方案选 DB 查重而非 Redis key（数据一致性更强）：
 * - 已提醒标记以 erik_notification 表本身为准（order_id + type=order
 *   + 标题「预约即将开始」），与通知写入同库同事务，天然一致；
 * - 处理时对订单行 lockForUpdate 加锁，并发扫描（多进程/多实例）
 *   后到者重读订单行后仍走查重分支，不会重复插入；
 * - Redis key（notif_remind:{order_id}）存在丢键风险（flush/重启/
 *   Redis 不可用），会造成重复提醒，故不采用。
 */
class NotificationReminderService
{
    /**
     * 提前提醒时间：服务开始前 2 小时
     */
    private const REMIND_AHEAD_SECONDS = 7200;

    /**
     * 扫描窗口宽度：1 小时（服务开始前 2h~1h 内生成提醒，避免重复/漏扫）
     */
    private const WINDOW_SECONDS = 3600;

    private const REMINDER_TITLE = '预约即将开始';
    private const REMINDER_TYPE  = 'order';

    /**
     * 扫描所有到期预约并生成提醒通知
     *
     * @param int|null $nowTimestamp 当前时间戳（测试可注入；null 取 time()）
     * @return int 本轮生成的通知数
     */
    public function sendReminderForDueOrders(?int $nowTimestamp = null): int
    {
        $now         = $nowTimestamp ?? time();
        $windowStart = date('Y-m-d H:i:s', $now + self::REMIND_AHEAD_SECONDS - self::WINDOW_SECONDS);
        $windowEnd   = date('Y-m-d H:i:s', $now + self::REMIND_AHEAD_SECONDS);

        try {
            $orders = Order::where('status', Order::STATUS_PAID)
                ->where('service_time', '>=', $windowStart)
                ->where('service_time', '<', $windowEnd)
                ->limit(50)
                ->get();

            if ($orders->isEmpty()) {
                return 0;
            }

            // 批量预取技师昵称/门店/服务名，避免 N+1
            $ctx = $this->preloadContext($orders);

            $sent = 0;
            foreach ($orders as $order) {
                try {
                    if ($this->processOrder($order, $ctx)) {
                        $sent++;
                    }
                } catch (\Throwable $e) {
                    Log::error('[NotificationReminder] Failed to remind order '
                        . ($order->id ?? 'unknown') . ': ' . $e->getMessage());
                }
            }

            if ($sent > 0) {
                Log::info('[NotificationReminder] Sent ' . $sent . ' appointment reminders');
            }
            return $sent;
        } catch (\Throwable $e) {
            Log::error('[NotificationReminder] Scan error: ' . $e->getMessage()
                . "\n" . $e->getTraceAsString());
            return 0;
        }
    }

    /**
     * 处理单个订单：查重 + 写站内通知 + 挂订阅消息钩子（幂等，事务内）
     *
     * @param Order $order
     * @param array $ctx  preloadContext() 的预取上下文（可为空，buildContent 容错）
     * @return bool 是否生成了提醒
     */
    public function processOrder(Order $order, array $ctx = []): bool
    {
        return Db::transaction(function () use ($order, $ctx): bool {
            // 重新查询并加锁，串行化并发扫描
            $locked = Order::where('id', $order->id)
                ->where('status', Order::STATUS_PAID)
                ->lockForUpdate()
                ->first();

            if (!$locked) {
                return false; // 状态已变更（取消/退款/完成）或已被处理
            }

            // 幂等查重：同订单 + 同类型 + 同标题视为已提醒
            $exists = Notification::where('order_id', $order->id)
                ->where('type', self::REMINDER_TYPE)
                ->where('title', self::REMINDER_TITLE)
                ->exists();

            if ($exists) {
                return false; // 已提醒过，跳过
            }

            $content = $this->buildContent($order, $ctx);

            // 写站内通知（与 AutoCancelTimer 同模式：Db::table 直插含 id）
            Db::table('erik_notification')->insert([
                'id'         => Notification::generateId(),
                'user_id'    => $order->user_id,
                'type'       => self::REMINDER_TYPE,
                'title'      => self::REMINDER_TITLE,
                'content'    => $content,
                'order_id'   => $order->id,
                'is_read'    => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            // 订阅消息钩子：未配置微信模板时仅站内通知（可配置降级）
            $this->sendSubscribeReminder($order, $ctx, $content);

            return true;
        });
    }

    /**
     * 微信订阅消息发送钩子（可配置降级）
     *
     * 当前仅实现站内通知闭环；订阅消息发送留待商户凭证就绪后接入
     * （小程序订阅消息走 /cgi-bin/message/subscribe/send，需 access_token +
     * 用户订阅授权记录 + 模板 ID）。配置项见 .env.example 的 WECHAT_SUBSCRIBE_* 注释块。
     *
     * @return bool 是否已发送（未配置/未实现时恒为 false，仅降级日志）
     */
    public function sendSubscribeReminder(Order $order, array $ctx = [], string $content = ''): bool
    {
        $templateId = (string)(getenv('WECHAT_SUBSCRIBE_TEMPLATE_ID') ?: '');

        if ($templateId === '') {
            Log::info('[NotificationReminder] WECHAT_SUBSCRIBE_TEMPLATE_ID 未配置，'
                . '跳过订阅消息（仅站内通知）order=' . $order->id);
            return false;
        }

        // TODO(订阅消息): 商户凭证就绪后接入小程序 subscribe/send——
        // 复用 WechatTemplateMessageService 的 access_token 缓存，并校验
        // UserDevice 订阅授权记录。当前仅占位，不写死不可测的发送代码。
        Log::info('[NotificationReminder] 订阅消息发送未实现（TODO），仅站内通知 order=' . $order->id);
        return false;
    }

    // ── 内部方法 ──

    /**
     * 批量预取技师昵称 / 门店 / 服务名，返回上下文映射
     *
     * @param iterable $orders
     * @return array{technicians: array, stores: \Illuminate\Support\Collection, items: \Illuminate\Support\Collection}
     */
    private function preloadContext(iterable $orders): array
    {
        $technicianIds = [];
        $storeIds      = [];
        $orderIds      = [];

        foreach ($orders as $order) {
            if (!empty($order->technician_id)) {
                $technicianIds[] = (string) $order->technician_id;
            }
            if (!empty($order->store_id)) {
                $storeIds[] = (string) $order->store_id;
            }
            $orderIds[] = (string) $order->id;
        }

        $technicians = User::whereIn('id', $technicianIds)->pluck('nickname', 'id')->toArray();
        $stores      = Store::whereIn('id', $storeIds)->get()->keyBy('id');
        // snowflake id 单调递增，按 id 排序保证同订单内服务名顺序稳定
        $items = OrderItem::whereIn('order_id', $orderIds)
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy('order_id');

        return [
            'technicians' => $technicians,
            'stores'      => $stores,
            'items'       => $items,
        ];
    }

    /**
     * 组装通知内容：服务名 / 技师名 / 门店地址 / 时间
     */
    private function buildContent(Order $order, array $ctx): string
    {
        $serviceName = '';
        if (!empty($ctx['items'][$order->id])) {
            $names = [];
            foreach ($ctx['items'][$order->id] as $item) {
                if (!empty($item->name)) {
                    $names[] = $item->name;
                }
            }
            $serviceName = implode('、', $names);
        }

        $technicianName = (string)($ctx['technicians'][$order->technician_id] ?? '');

        $store         = $ctx['stores'][$order->store_id] ?? null;
        $storeName     = (string)($store->name ?? '');
        $storeAddress  = (string)($store->address ?? '');
        $location      = trim($storeName . ($storeAddress !== '' ? '（' . $storeAddress . '）' : ''));

        $time = $order->service_time ? $order->service_time->format('Y-m-d H:i') : '';

        $parts = ['您的预约将在 ' . $time . ' 开始'];
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
}
