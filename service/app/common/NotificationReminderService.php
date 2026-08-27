<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\common;

use app\model\Notification;
use app\model\Order;
use app\model\OrderItem;
use app\model\Store;
use app\model\User;
use app\model\UserNotifySetting;
use support\Db;
use support\Log;

/**
 * 预约前提醒服务（站内通知闭环）
 *
 * 扫描「服务开始前 2 小时 ~ 1 小时」窗口内的已支付预约单，
 * 为每个订单生成一条站内通知（appointment_notification，type=order，
 * 标题固定「预约即将开始」），并挂接可配置降级的微信订阅消息钩子
 * （未配置 WECHAT_SUBSCRIBE_* 时仅站内通知）。
 *
 * 幂等方案选 DB 查重而非 Redis key（数据一致性更强）：
 * - 已提醒标记以 appointment_notification 表本身为准（order_id + type=order
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

    /** 订单事件订阅消息场景：支付成功 */
    public const SCENE_PAY = 'pay';
    /** 订单事件订阅消息场景：退款到账 */
    public const SCENE_REFUND = 'refund';
    /** 订单事件订阅消息场景：核销成功 */
    public const SCENE_VERIFIED = 'verified';
    /** 订单事件订阅消息场景：预约改期成功 */
    public const SCENE_RESCHEDULE = 'reschedule';
    /** 订阅消息场景：服务即将开始提醒（ServiceReminderTimer 定时器） */
    public const SCENE_REMINDER = 'reminder';
    /** 订阅消息场景：会员卡/优惠券到期提醒（ExpiryReminderTimer 定时器） */
    public const SCENE_EXPIRY = 'expiry';

    /** 消息偏好类型：服务提醒（预约即将开始 / 服务即将开始） */
    public const NOTIFY_TYPE_SERVICE_REMINDER = 'service_reminder';
    /** 消息偏好类型：到期提醒（会员卡/优惠券到期，伞形覆盖 card_expiry + coupon_expiry） */
    public const NOTIFY_TYPE_CARD_EXPIRY = 'card_expiry';
    /** 消息偏好类型：积分过期 */
    public const NOTIFY_TYPE_POINTS_EXPIRY = 'points_expiry';
    /** 消息偏好类型：营销（预留，暂无写入路径） */
    public const NOTIFY_TYPE_MARKETING = 'marketing';
    /** 消息偏好类型：系统（订单支付/退款/核销/改期等交易事件，不可关闭） */
    public const NOTIFY_TYPE_SYSTEM = 'system';

    /**
     * 订阅消息模板字段 key（对应小程序后台审核通过的模板字段名，
     * 如模板字段名不同可在此调整；thing 字段值上限 20 字符）
     */
    private const SUBSCRIBE_DATA_KEYS = [
        'service' => 'thing1', // 预约项目
        'time'    => 'time2',  // 开始时间
        'store'   => 'thing3', // 门店
    ];

    /**
     * 订单事件订阅消息场景注册表：站内通知标题 / 模板 env key / 订阅消息字段映射
     *
     * data_keys 的 key（thing1/amount2/time2...）为小程序后台模板字段名，
     * 申请模板后按实际字段名调整（与 SUBSCRIBE_DATA_KEYS 同一约定）。
     * 标题与主路径站内通知一致（退款已到账/订单已核销），同订单同标题不双写。
     */
    private const SUBSCRIBE_EVENT_SCENES = [
        self::SCENE_PAY => [
            'title'        => '订单支付成功',
            'template_env' => 'WECHAT_SUBSCRIBE_TEMPLATE_PAID',
            'data_keys'    => ['service' => 'thing1', 'order_no' => 'thing2', 'amount' => 'amount3'],
        ],
        self::SCENE_REFUND => [
            'title'        => '退款已到账',
            'template_env' => 'WECHAT_SUBSCRIBE_TEMPLATE_REFUND',
            'data_keys'    => ['order_no' => 'thing1', 'amount' => 'amount2', 'reason' => 'thing3'],
        ],
        self::SCENE_VERIFIED => [
            'title'        => '订单已核销',
            'template_env' => 'WECHAT_SUBSCRIBE_TEMPLATE_VERIFIED',
            'data_keys'    => ['service' => 'thing1', 'time' => 'time2', 'store' => 'thing3'],
        ],
        self::SCENE_RESCHEDULE => [
            'title'        => '预约改期成功',
            'template_env' => 'WECHAT_SUBSCRIBE_TEMPLATE_RESCHEDULE',
            'data_keys'    => ['service' => 'thing1', 'order_no' => 'thing2', 'time' => 'time3'],
        ],
        self::SCENE_REMINDER => [
            'title'        => '预约即将开始',
            'template_env' => 'WECHAT_SUBSCRIBE_TEMPLATE_REMINDER',
            'data_keys'    => ['service' => 'thing1', 'time' => 'time2', 'store' => 'thing3'],
        ],
        self::SCENE_EXPIRY => [
            'title'        => '权益即将到期',
            'template_env' => 'WECHAT_SUBSCRIBE_TEMPLATE_EXPIRY',
            'data_keys'    => ['name' => 'thing1', 'time' => 'time2'],
        ],
    ];

    /** 订阅消息场景 → 消息偏好类型（订单交易事件归 system，不可关闭） */
    private const SCENE_NOTIFY_TYPE_MAP = [
        self::SCENE_PAY        => self::NOTIFY_TYPE_SYSTEM,
        self::SCENE_REFUND     => self::NOTIFY_TYPE_SYSTEM,
        self::SCENE_VERIFIED   => self::NOTIFY_TYPE_SYSTEM,
        self::SCENE_RESCHEDULE => self::NOTIFY_TYPE_SYSTEM,
        self::SCENE_REMINDER   => self::NOTIFY_TYPE_SERVICE_REMINDER,
        self::SCENE_EXPIRY     => self::NOTIFY_TYPE_CARD_EXPIRY,
    ];

    /**
     * 用户是否开启某类通知（appointment_user_notify_setting 开关）
     *
     * 未插入行视为开启（默认开）；system 类型强制开启不可关闭。
     * 本服务内写入路径与定时进程（ServiceReminderTimer/ExpiryReminderTimer/
     * PointsExpiryTimer）在写站内通知前调用，关闭则该类型不写站内通知，
     * 订阅消息一并跳过。
     */
    public static function notifySettingEnabled(string $userId, string $type): bool
    {
        if ($type === self::NOTIFY_TYPE_SYSTEM) {
            return true;
        }
        $switch = UserNotifySetting::where('user_id', $userId)
            ->where('type', $type)
            ->value('switch');
        return $switch === null ? true : (int) $switch === 1;
    }

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
            // 消息偏好：用户关闭服务提醒则不写站内通知（订阅消息一并跳过）
            if (!self::notifySettingEnabled((string) $order->user_id, self::NOTIFY_TYPE_SERVICE_REMINDER)) {
                return false;
            }

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

            $content        = $this->buildContent($order, $ctx);
            $notificationId = Notification::generateId();

            // 写站内通知（与 AutoCancelTimer 同模式：Db::table 直插含 id）
            Db::table('appointment_notification')->insert([
                'id'         => $notificationId,
                'user_id'    => $order->user_id,
                'type'       => self::REMINDER_TYPE,
                'title'      => self::REMINDER_TITLE,
                'content'    => $content,
                'order_id'   => $order->id,
                'is_read'    => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            // 订阅消息钩子：未配置微信模板时仅站内通知（可配置降级）；失败不影响主流程
            $this->sendSubscribeReminder($order, $ctx, $content, $notificationId);

            return true;
        });
    }

    /**
     * 微信订阅消息发送钩子（可配置降级）
     *
     * 发送链路：WechatTemplateMessageService::sendSubscribeMessage（小程序
     * subscribe/send，独立 access_token）。前置条件：
     * 1) WECHAT_SUBSCRIBE_TEMPLATE_ID/APP_ID/APP_SECRET 三件套齐全；
     * 2) 用户 appointment_user.wx_openid 非空（订阅消息按 openid 投递）；
     * 3) 用户已在小程序内订阅过该模板（未订阅微信返回 43101）。
     *
     * 幂等：推送成功（微信 errcode=0）才写 appointment_notification.push_sent_at；
     * 已写入则跳过，防止 60s 定时扫描重复推送。失败不写标记（下次扫描
     * 可重试，扫描查重上限 60s 一次，可接受）。异常不影响主流程。
     *
     * @param Order       $order          订单
     * @param array       $ctx            preloadContext() 预取上下文（可空）
     * @param string      $content        站内通知内容（兼容保留，未使用）
     * @param string|null $notificationId 已写入的通知 ID（processOrder 传入；
     *                                    直接调用时为 null 则按订单查）
     * @return bool 是否推送成功（未配置/无 openid/微信失败均 false）
     */
    public function sendSubscribeReminder(
        Order $order,
        array $ctx = [],
        string $content = '',
        ?string $notificationId = null
    ): bool {
        try {
            $templateId = (string)(getenv('WECHAT_SUBSCRIBE_TEMPLATE_ID') ?: '');
            $appId      = (string)(getenv('WECHAT_SUBSCRIBE_APP_ID') ?: '');
            $appSecret  = (string)(getenv('WECHAT_SUBSCRIBE_APP_SECRET') ?: '');

            if ($templateId === '' || $appId === '' || $appSecret === '') {
                Log::info('[NotificationReminder] WECHAT_SUBSCRIBE_* 未配置齐全，'
                    . '跳过订阅消息（仅站内通知）order=' . $order->id);
                return false;
            }

            // 幂等：该通知已推送过订阅消息则不再推送
            $notificationId ??= (string)(Notification::where('order_id', $order->id)
                ->where('type', self::REMINDER_TYPE)
                ->where('title', self::REMINDER_TITLE)
                ->value('id') ?? '');
            if ($notificationId !== '') {
                $pushSentAt = Notification::where('id', $notificationId)->value('push_sent_at');
                if ($pushSentAt !== null) {
                    Log::info('[NotificationReminder] 订阅消息已推送过（push_sent_at 已写入），'
                        . '跳过 order=' . $order->id);
                    return false;
                }
            }

            // 用户 openid 缺失则无法投递
            $openid = (string)(User::where('id', $order->user_id)->value('wx_openid') ?? '');
            if ($openid === '') {
                Log::info('[NotificationReminder] 用户无 wx_openid，无法发送订阅消息 order=' . $order->id);
                return false;
            }

            $result = $this->makeWechatService()->sendSubscribeMessage(
                $openid,
                $templateId,
                $this->buildSubscribeData($order, $ctx)
            );

            if (($result['errcode'] ?? -1) === 0) {
                // 推送成功才写"已推送"标记；失败不写（下次扫描可重试）
                if ($notificationId !== '') {
                    Db::table('appointment_notification')
                        ->where('id', $notificationId)
                        ->update(['push_sent_at' => date('Y-m-d H:i:s')]);
                }
                Log::info('[NotificationReminder] 订阅消息发送成功 order=' . $order->id);
                return true;
            }

            Log::error('[NotificationReminder] 订阅消息发送失败 order=' . $order->id
                . ' errcode=' . ($result['errcode'] ?? -1)
                . ' errmsg=' . ($result['errmsg'] ?? 'unknown'));
            return false;
        } catch (\Throwable $e) {
            Log::error('[NotificationReminder] sendSubscribeReminder exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 订单事件订阅消息发送（支付成功 / 退款到账 / 核销成功，第 8 轮接线）
     *
     * 与 sendSubscribeReminder 同一发送链路（WechatTemplateMessageService::
     * sendSubscribeMessage，独立小程序 access_token）与幂等约定：
     * - 站内通知行（appointment_notification，order_id + type + 场景标题）始终确保存在；
     *   缺失时补建（内容与主路径 writeRefundNotification/notifyVerified 一致），
     *   主路径已写则复用，不双写；
     * - 微信订阅消息为可配置降级：模板 env key（或 APP_ID/APP_SECRET）未配置、
     *   用户无 wx_openid 时仅站内通知；
     * - 幂等：推送成功（微信 errcode=0）才写 push_sent_at；已写入则跳过
     *   （微信重复回调 / 并发重试不重复推送）；失败不写标记（事件路径不重扫，
     *   由日志追踪，失败不影响主流程）。
     *
     * @param Order  $order 订单
     * @param string $scene 场景（SCENE_PAY / SCENE_REFUND / SCENE_VERIFIED）
     * @param array  $extra 场景补充数据：refund → ['refund_amount' => float, 'refund_reason' => string]
     * @return bool 是否推送成功（未配置/无 openid/微信失败均 false）
     */
    public function sendSubscribeForOrderEvent(Order $order, string $scene, array $extra = []): bool
    {
        try {
            $sceneConfig = self::SUBSCRIBE_EVENT_SCENES[$scene] ?? null;
            if ($sceneConfig === null) {
                Log::warning('[NotificationReminder] unknown subscribe scene: ' . $scene);
                return false;
            }

            // 消息偏好：该场景对应类型被关闭则跳过站内通知补建与订阅消息（订单交易事件归 system 不可关）
            $notifyType = self::SCENE_NOTIFY_TYPE_MAP[$scene] ?? self::NOTIFY_TYPE_SYSTEM;
            if (!self::notifySettingEnabled((string) $order->user_id, $notifyType)) {
                Log::info('[NotificationReminder] 用户已关闭通知类型 ' . $notifyType
                    . '，跳过 order=' . $order->id . ' scene=' . $scene);
                return false;
            }

            // 站内通知行 find-or-create：标题与主路径一致，主路径已写则复用不双写
            $title = $sceneConfig['title'];
            $notificationId = (string) (Notification::where('order_id', $order->id)
                ->where('type', self::REMINDER_TYPE)
                ->where('title', $title)
                ->value('id') ?? '');

            if ($notificationId === '') {
                $notification = Notification::create([
                    'id'         => Notification::generateId(),
                    'user_id'    => (string) $order->user_id,
                    'type'       => self::REMINDER_TYPE,
                    'title'      => $title,
                    'content'    => $this->buildEventContent($order, $scene, $extra),
                    'order_id'   => $order->id,
                ]);
                $notificationId = (string) $notification->id;
            }

            // 幂等：该场景已推送过订阅消息则不再推送
            $pushSentAt = Notification::where('id', $notificationId)->value('push_sent_at');
            if ($pushSentAt !== null) {
                Log::info('[NotificationReminder] 订阅消息已推送过（push_sent_at 已写入），'
                    . '跳过 order=' . $order->id . ' scene=' . $scene);
                return false;
            }

            // 模板/凭据未配置齐全 → 降级仅站内通知
            $templateId = (string) (getenv($sceneConfig['template_env']) ?: '');
            if ($templateId === ''
                || (string) (getenv('WECHAT_SUBSCRIBE_APP_ID') ?: '') === ''
                || (string) (getenv('WECHAT_SUBSCRIBE_APP_SECRET') ?: '') === ''
            ) {
                Log::info('[NotificationReminder] ' . $sceneConfig['template_env'] . ' 未配置齐全，'
                    . '跳过订阅消息（仅站内通知）order=' . $order->id);
                return false;
            }

            // 用户 openid 缺失则无法投递
            $openid = (string) (User::where('id', $order->user_id)->value('wx_openid') ?? '');
            if ($openid === '') {
                Log::info('[NotificationReminder] 用户无 wx_openid，无法发送订阅消息 order=' . $order->id);
                return false;
            }

            $result = $this->makeWechatService()->sendSubscribeMessage(
                $openid,
                $templateId,
                $this->buildEventSubscribeData($order, $scene, $extra)
            );

            if (($result['errcode'] ?? -1) === 0) {
                // 推送成功才写"已推送"标记；失败不写（日志可追踪，不阻塞主流程）
                Db::table('appointment_notification')
                    ->where('id', $notificationId)
                    ->update(['push_sent_at' => date('Y-m-d H:i:s')]);
                Log::info('[NotificationReminder] 订阅消息发送成功 order=' . $order->id . ' scene=' . $scene);
                return true;
            }

            Log::error('[NotificationReminder] 订阅消息发送失败 order=' . $order->id . ' scene=' . $scene
                . ' errcode=' . ($result['errcode'] ?? -1)
                . ' errmsg=' . ($result['errmsg'] ?? 'unknown'));
            return false;
        } catch (\Throwable $e) {
            Log::error('[NotificationReminder] sendSubscribeForOrderEvent exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 定时器场景订阅消息发送（服务提醒 / 到期提醒，通知行由调用方进程先行写入）
     *
     * 与 sendSubscribeForOrderEvent 同一发送链路（WechatTemplateMessageService::
     * sendSubscribeMessage，独立小程序 access_token）与幂等约定：
     * - 调用方（ServiceReminderTimer / ExpiryReminderTimer）已写入站内通知行
     *   （type=service_reminder/card_expiry/coupon_expiry），此处仅按通知 ID
     *   定位并标记 push_sent_at，不重复写通知；
     * - 微信订阅消息为可配置降级：模板 env key（WECHAT_SUBSCRIBE_TEMPLATE_*
     *   + APP_ID/APP_SECRET）未配置、用户无 wx_openid 时仅站内通知；
     * - 幂等：推送成功（微信 errcode=0）才写 push_sent_at；已写入则跳过。
     *   失败不写标记——通知行判重不阻塞，下次扫描轮次仍可重试。
     *
     * @param string $scene          场景（SCENE_REMINDER / SCENE_EXPIRY）
     * @param string $userId         用户 ID
     * @param string $notificationId 已写入的站内通知 ID
     * @param array  $data           订阅消息 data（key 见 SUBSCRIBE_EVENT_SCENES.data_keys）
     * @return bool 是否推送成功（未配置/无 openid/微信失败均 false）
     */
    public function sendSubscribeForNotification(string $scene, string $userId, string $notificationId, array $data): bool
    {
        try {
            $sceneConfig = self::SUBSCRIBE_EVENT_SCENES[$scene] ?? null;
            if ($sceneConfig === null) {
                Log::warning('[NotificationReminder] unknown subscribe scene: ' . $scene);
                return false;
            }

            // 消息偏好：该类型被关闭则跳过订阅消息（通知行由调用方先行写入，行写入已按其开关门控）
            $notifyType = self::SCENE_NOTIFY_TYPE_MAP[$scene] ?? self::NOTIFY_TYPE_SYSTEM;
            if (!self::notifySettingEnabled($userId, $notifyType)) {
                Log::info('[NotificationReminder] 用户已关闭通知类型 ' . $notifyType
                    . '，跳过订阅消息 notification=' . $notificationId . ' scene=' . $scene);
                return false;
            }

            // 幂等：该通知已推送过订阅消息则不再推送（失败不写标记，下次扫描可重试）
            $pushSentAt = Notification::where('id', $notificationId)->value('push_sent_at');
            if ($pushSentAt !== null) {
                Log::info('[NotificationReminder] 订阅消息已推送过（push_sent_at 已写入），'
                    . '跳过 notification=' . $notificationId . ' scene=' . $scene);
                return false;
            }

            // 模板/凭据未配置齐全 → 降级仅站内通知
            $templateId = (string) (getenv($sceneConfig['template_env']) ?: '');
            if ($templateId === ''
                || (string) (getenv('WECHAT_SUBSCRIBE_APP_ID') ?: '') === ''
                || (string) (getenv('WECHAT_SUBSCRIBE_APP_SECRET') ?: '') === ''
            ) {
                Log::info('[NotificationReminder] ' . $sceneConfig['template_env'] . ' 未配置齐全，'
                    . '跳过订阅消息（仅站内通知）notification=' . $notificationId);
                return false;
            }

            // 用户 openid 缺失则无法投递
            $openid = (string) (User::where('id', $userId)->value('wx_openid') ?? '');
            if ($openid === '') {
                Log::info('[NotificationReminder] 用户无 wx_openid，无法发送订阅消息 user=' . $userId);
                return false;
            }

            $result = $this->makeWechatService()->sendSubscribeMessage($openid, $templateId, $data);

            if (($result['errcode'] ?? -1) === 0) {
                // 推送成功才写"已推送"标记；失败不写（日志可追踪，不阻塞主流程）
                Db::table('appointment_notification')
                    ->where('id', $notificationId)
                    ->update(['push_sent_at' => date('Y-m-d H:i:s')]);
                Log::info('[NotificationReminder] 订阅消息发送成功 notification=' . $notificationId . ' scene=' . $scene);
                return true;
            }

            Log::error('[NotificationReminder] 订阅消息发送失败 notification=' . $notificationId . ' scene=' . $scene
                . ' errcode=' . ($result['errcode'] ?? -1)
                . ' errmsg=' . ($result['errmsg'] ?? 'unknown'));
            return false;
        } catch (\Throwable $e) {
            Log::error('[NotificationReminder] sendSubscribeForNotification exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 发送器工厂（测试可覆写注入 fake，避免真实 HTTP）
     */
    protected function makeWechatService(): WechatTemplateMessageService
    {
        return new WechatTemplateMessageService();
    }

    // ── 内部方法 ──

    /**
     * 组装订阅消息 data：预约项目 / 开始时间 / 门店（字段 key 见 SUBSCRIBE_DATA_KEYS）
     */
    private function buildSubscribeData(Order $order, array $ctx): array
    {
        $serviceName = '';
        if (!empty($ctx['items'][$order->id])) {
            $names = [];
            foreach ($ctx['items'][$order->id] as $item) {
                if (!empty($item->name)) {
                    $names[] = (string) $item->name;
                }
            }
            $serviceName = implode('、', $names);
        }

        $store     = $ctx['stores'][$order->store_id] ?? null;
        $storeName = (string)($store->name ?? '');

        return [
            self::SUBSCRIBE_DATA_KEYS['service'] => ['value' => $this->truncate($serviceName, 20)],
            self::SUBSCRIBE_DATA_KEYS['time']    => [
                'value' => $order->service_time ? $order->service_time->format('Y-m-d H:i') : '',
            ],
            self::SUBSCRIBE_DATA_KEYS['store']   => ['value' => $this->truncate($storeName, 20)],
        ];
    }

    /**
     * 按字符数截断（thing 字段值上限 20 字符，避免微信 47003 报错）
     */
    private function truncate(string $value, int $length): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $length);
        }
        return substr($value, 0, $length);
    }

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

    // ── 订单事件订阅消息（第 8 轮接线）内部方法 ──

    /**
     * 组装订单事件站内通知内容（仅补建行时使用；主路径已写则复用不覆盖）
     */
    private function buildEventContent(Order $order, string $scene, array $extra): string
    {
        $orderNo = $order->order_no;

        return match ($scene) {
            self::SCENE_PAY => '您的订单 ' . $orderNo . ' 已支付成功 ¥'
                . number_format((float) $order->paid_amount, 2) . '，预约已确认。',
            self::SCENE_REFUND => '您的订单 ' . $orderNo . ' 已退款 ¥'
                . number_format((float) ($extra['refund_amount'] ?? 0), 2)
                . '，款项将原路退回至支付账户。',
            self::SCENE_VERIFIED => '您的订单 ' . $orderNo . ' 已核销，服务即将开始，祝您体验愉快。',
            self::SCENE_RESCHEDULE => '您的订单 ' . $orderNo . ' 已改期至 '
                . ($order->service_time ? $order->service_time->format('Y-m-d H:i') : '') . '，请准时到达。',
            default => '',
        };
    }

    /**
     * 组装订单事件订阅消息 data（字段 key 见 SUBSCRIBE_EVENT_SCENES.data_keys）
     */
    private function buildEventSubscribeData(Order $order, string $scene, array $extra): array
    {
        $keys    = self::SUBSCRIBE_EVENT_SCENES[$scene]['data_keys'];
        $orderNo = $order->order_no;

        return match ($scene) {
            self::SCENE_PAY => [
                $keys['service']  => ['value' => $this->truncate($this->firstServiceName($order), 20)],
                $keys['order_no'] => ['value' => $orderNo],
                $keys['amount']   => ['value' => number_format((float) $order->paid_amount, 2)],
            ],
            self::SCENE_REFUND => [
                $keys['order_no'] => ['value' => $orderNo],
                $keys['amount']   => ['value' => number_format((float) ($extra['refund_amount'] ?? 0), 2)],
                $keys['reason']   => ['value' => $this->truncate((string) ($extra['refund_reason'] ?? '用户申请退款'), 20)],
            ],
            self::SCENE_VERIFIED => [
                $keys['service'] => ['value' => $this->truncate($this->firstServiceName($order), 20)],
                $keys['time']    => ['value' => $order->service_time ? $order->service_time->format('Y-m-d H:i') : ''],
                $keys['store']   => ['value' => $this->truncate((string) (Store::where('id', $order->store_id)->value('name') ?? ''), 20)],
            ],
            self::SCENE_RESCHEDULE => [
                $keys['service']  => ['value' => $this->truncate($this->firstServiceName($order), 20)],
                $keys['order_no'] => ['value' => $orderNo],
                $keys['time']     => ['value' => $order->service_time ? $order->service_time->format('Y-m-d H:i') : ''],
            ],
            default => [],
        };
    }

    /**
     * 订单首条服务项名称（与 OrderController::sendOrderConfirmTemplate 口径一致）
     */
    private function firstServiceName(Order $order): string
    {
        $item = $order->items()->first();
        return $item ? (string) $item->name : '';
    }
}
