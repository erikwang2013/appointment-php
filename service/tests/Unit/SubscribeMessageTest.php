<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\common\NotificationReminderService;
use app\common\WechatTemplateMessageService;
use app\model\Notification;
use app\model\Order;
use app\model\OrderItem;
use app\model\Store;
use app\model\User;
use Carbon\Carbon;

/**
 * 微信订阅消息发送链路测试（真实 DB，与 NotificationReminderServiceTest 同基建）
 *
 * 通过 makeWechatService() 工厂缝注入 fake 发送器（匿名子类覆写，
 * 不触碰真实 HTTP）。覆盖：
 * - 未配置 WECHAT_SUBSCRIBE_* 降级：返回 false，不触碰发送器（行为不变）
 * - 配置齐全 + 用户有 openid：data 组装正确（thing1 服务名/time2 时间/thing3 门店）
 *   + 成功后写 push_sent_at
 * - 幂等：push_sent_at 已写入后不再重复推送（发送器调用次数不增）
 * - 发送失败（errcode!=0）：返回 false，push_sent_at 保持 NULL（可重试）
 * - 用户无 openid：跳过发送
 */
class SubscribeMessageTest extends TestCase
{
    /** @var string[] 用例创建的订单/通知/用户/门店/服务项 ID，tearDown 统一清理 */
    private array $orderIds = [];
    private array $notificationIds = [];
    private array $userIds = [];
    private array $storeIds = [];
    private array $itemIds = [];

    protected function setUp(): void
    {
        $this->clearSubscribeEnv();
    }

    protected function tearDown(): void
    {
        $this->clearSubscribeEnv();
        foreach ($this->orderIds as $id) {
            Notification::where('order_id', $id)->delete();
            OrderItem::where('order_id', $id)->delete();
            Order::where('id', $id)->delete();
        }
        foreach ($this->notificationIds as $id) {
            Notification::where('id', $id)->delete();
        }
        foreach ($this->storeIds as $id) {
            Store::where('id', $id)->forceDelete();
        }
        foreach ($this->userIds as $id) {
            User::where('id', $id)->forceDelete();
        }
        $this->orderIds = [];
        $this->notificationIds = [];
        $this->userIds = [];
        $this->storeIds = [];
        $this->itemIds = [];
    }

    private function clearSubscribeEnv(): void
    {
        putenv('WECHAT_SUBSCRIBE_TEMPLATE_ID');
        putenv('WECHAT_SUBSCRIBE_APP_ID');
        putenv('WECHAT_SUBSCRIBE_APP_SECRET');
    }

    private function configureSubscribe(): void
    {
        putenv('WECHAT_SUBSCRIBE_TEMPLATE_ID=tpl_subscribe_test');
        putenv('WECHAT_SUBSCRIBE_APP_ID=wx_sub_appid');
        putenv('WECHAT_SUBSCRIBE_APP_SECRET=wx_sub_secret');
    }

    /** 造门店 */
    private function makeStore(): Store
    {
        $store = Store::create([
            'id'      => Store::generateId(),
            'name'    => '测试门店',
            'address' => '测试路 1 号',
            'status'  => 1,
        ]);
        $this->storeIds[] = $store->id;
        return $store;
    }

    /** 造用户 */
    private function makeUser(string $nickname): User
    {
        $user = User::create([
            'phone'     => '199' . substr((string) random_int(10000000, 99999999), 0, 8),
            'nickname'  => $nickname,
            'user_type' => 'user',
            'status'    => 1,
        ]);
        $this->userIds[] = $user->id;
        return $user;
    }

    /** 造订单（service_time 落在提醒窗口：90 分钟后） */
    private function makeOrder(Store $store, User $technician, User $customer): Order
    {
        $order = Order::create([
            'order_no'        => 'ORD_SUB_' . uniqid(),
            'user_id'         => $customer->id,
            'technician_id'   => $technician->id,
            'store_id'        => $store->id,
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => 100.0,
            'discount_amount' => 0.0,
            'paid_amount'     => 100.0,
            'service_time'    => Carbon::now()->addMinutes(90)->format('Y-m-d H:i:s'),
            'status'          => Order::STATUS_PAID,
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
        $this->orderIds[] = $order->id;
        return $order;
    }

    /** 造订单服务项 */
    private function makeItem(Order $order): void
    {
        $item = OrderItem::create([
            'id'          => OrderItem::generateId(),
            'order_id'    => $order->id,
            'target_type' => 'service',
            'target_id'   => OrderItem::generateId(),
            'name'        => '肩颈按摩',
            'price'       => 100.0,
            'quantity'    => 1,
        ]);
        $this->itemIds[] = $item->id;
    }

    /** 造完整预约（门店/技师/顾客/服务项），用户 openid 可指定 */
    private function makeAppointment(string $openid = ''): Order
    {
        $store      = $this->makeStore();
        $technician = $this->makeUser('测试技师');
        $customer   = $this->makeUser('测试顾客');
        if ($openid !== '') {
            User::where('id', $customer->id)->update(['wx_openid' => $openid]);
        }
        $order = $this->makeOrder($store, $technician, $customer);
        $this->makeItem($order);
        return $order;
    }

    /**
     * 造 fake 发送器：记录调用参数，返回预设微信响应（不触发真实 HTTP）
     */
    private function makeFakeSender(array $result): WechatTemplateMessageService
    {
        return new class($result) extends WechatTemplateMessageService {
            public array $calls = [];
            private array $result;

            public function __construct(array $result)
            {
                $this->result = $result;
            }

            public function sendSubscribeMessage(
                string $openid,
                string $templateId,
                array  $data,
                string $page = ''
            ): array {
                $this->calls[] = [
                    'openid'      => $openid,
                    'template_id' => $templateId,
                    'data'        => $data,
                    'page'        => $page,
                ];
                return $this->result;
            }
        };
    }

    /**
     * 造带 fake 发送器的服务实例（覆写 makeWechatService 工厂）
     */
    private function makeServiceWithSender(WechatTemplateMessageService $sender): NotificationReminderService
    {
        return new class($sender) extends NotificationReminderService {
            private WechatTemplateMessageService $sender;

            public function __construct(WechatTemplateMessageService $sender)
            {
                $this->sender = $sender;
            }

            protected function makeWechatService(): WechatTemplateMessageService
            {
                return $this->sender;
            }
        };
    }

    // ── 未配置降级（行为不变）──

    #[Test] public function unconfigured_degrades_and_never_touches_sender(): void
    {
        $order  = $this->makeAppointment('openid_a');
        $sender = $this->makeFakeSender(['errcode' => 0, 'errmsg' => 'ok']);
        $service = $this->makeServiceWithSender($sender);

        $this->assertFalse($service->sendSubscribeReminder($order));
        $this->assertCount(0, $sender->calls);
    }

    // ── 配置齐全：发送 + push_sent_at 幂等标记 ──

    #[Test] public function configured_sends_with_correct_data_and_marks_pushed_once(): void
    {
        $this->configureSubscribe();
        $order = $this->makeAppointment('openid_ok');
        $sender = $this->makeFakeSender(['errcode' => 0, 'errmsg' => 'ok']);
        $service = $this->makeServiceWithSender($sender);

        // 完整链路：扫描 → 写站内通知 → 发订阅消息 → 写 push_sent_at
        $this->assertSame(1, $service->sendReminderForDueOrders());

        $this->assertCount(1, $sender->calls);
        $call = $sender->calls[0];
        $this->assertSame('openid_ok', $call['openid']);
        $this->assertSame('tpl_subscribe_test', $call['template_id']);
        $this->assertSame('', $call['page']);
        // 模板字段名（thing1/time2/thing3）与值组装正确
        $this->assertSame(['value' => '肩颈按摩'], $call['data']['thing1']);
        $this->assertSame(
            ['value' => Carbon::parse($order->service_time)->format('Y-m-d H:i')],
            $call['data']['time2']
        );
        $this->assertSame(['value' => '测试门店'], $call['data']['thing3']);

        // 推送成功后写"已推送"标记
        $this->assertNotNull(Notification::where('order_id', $order->id)->value('push_sent_at'));

        // 幂等：再次扫描不重复生成，也不重复推送
        $this->assertSame(0, $service->sendReminderForDueOrders());
        $this->assertCount(1, $sender->calls);

        // 直接调用钩子：push_sent_at 已写入 → 跳过，不重复推送
        $this->assertFalse($service->sendSubscribeReminder($order));
        $this->assertCount(1, $sender->calls);
    }

    // ── 发送失败：不写标记，可重试 ──

    #[Test] public function failure_keeps_push_sent_at_null(): void
    {
        $this->configureSubscribe();
        $order = $this->makeAppointment('openid_fail');
        $sender = $this->makeFakeSender(['errcode' => 43101, 'errmsg' => 'user refuse']);
        $service = $this->makeServiceWithSender($sender);

        // 站内通知照常生成（返回 1），订阅消息发送失败
        $this->assertSame(1, $service->sendReminderForDueOrders());
        $this->assertCount(1, $sender->calls);
        $this->assertNull(Notification::where('order_id', $order->id)->value('push_sent_at'));

        // 失败不写标记：直接重试会再次尝试（推送链路本身有上限，日志可追踪）
        $this->assertFalse($service->sendSubscribeReminder($order));
        $this->assertCount(2, $sender->calls);
        $this->assertNull(Notification::where('order_id', $order->id)->value('push_sent_at'));
    }

    // ── 用户无 openid：跳过发送 ──

    #[Test] public function user_without_openid_skips_send(): void
    {
        $this->configureSubscribe();
        $order = $this->makeAppointment(); // wx_openid 默认 ''
        $sender = $this->makeFakeSender(['errcode' => 0, 'errmsg' => 'ok']);
        $service = $this->makeServiceWithSender($sender);

        $this->assertSame(1, $service->sendReminderForDueOrders());
        $this->assertCount(0, $sender->calls);
        $this->assertNull(Notification::where('order_id', $order->id)->value('push_sent_at'));
    }
}
