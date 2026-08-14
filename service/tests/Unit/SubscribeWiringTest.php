<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\api\v1\controller\PaymentNotifyController;
use app\common\NotificationReminderService;
use app\common\WechatTemplateMessageService;
use app\model\Notification;
use app\model\Order;
use app\model\OrderItem;
use app\model\OrderPayment;
use app\model\OrderVerification;
use app\model\Store;
use app\model\TechnicianProfile;
use app\model\User;
use app\model\UserWallet;
use app\model\WalletTxn;
use app\order\v1\controller\OrderController;
use support\Container;
use support\Db;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 第 8 轮「订阅消息接线」集成测试（真实 DB / Redis，与 OrderRefundFlowTest 同基建）
 *
 * 覆盖：
 * - 支付成功（零元直通 / 余额支付）→ notifySubscribeEvent(SCENE_PAY) 接线触发
 * - 退款到账（balance 渠道两段式成功）→ notifySubscribeEvent(SCENE_REFUND) 接线触发
 * - 扫码核销成功 → notifySubscribeEvent(SCENE_VERIFIED) 接线触发
 * - 微信支付回调成功 → PaymentNotifyController::notifyPaySubscribe 接线 + 幂等
 * - sendSubscribeForOrderEvent 发送链路：data 组装、push_sent_at 幂等、
 *   失败不写标记、未配置降级、发送器抛异常不冒泡
 *
 * 微信 IO 绕过：notifySubscribeEvent 为 protected，测试子类覆写记录调用；
 * 发送链路经 makeWechatService() 工厂缝注入 fake 发送器（不触碰真实 HTTP），
 * 与 SubscribeMessageTest 同法。
 */
class SubscribeWiringTest extends TestCase
{
    /** @var string[] 用例创建的订单 ID，tearDown 统一清理 */
    private array $orderIds = [];

    /** @var string[] 用例创建的用户 ID */
    private array $userIds = [];

    /** @var string[] 用例创建的门店 ID */
    private array $storeIds = [];

    /** @var string[] 用例创建的服务项 ID */
    private array $itemIds = [];

    /** @var string[] 用例创建的钱包用户 ID（连带流水清理） */
    private array $walletUserIds = [];

    /** @var string[] 用例创建的技师档案 ID */
    private array $profileIds = [];

    protected function setUp(): void
    {
        $this->clearSubscribeEnv();
    }

    protected function tearDown(): void
    {
        $this->clearSubscribeEnv();
        foreach ($this->orderIds as $id) {
            Db::table('erik_notification')->where('order_id', $id)->delete();
            OrderItem::where('order_id', $id)->delete();
            OrderVerification::where('order_id', $id)->delete();
            OrderPayment::where('order_id', $id)->delete();
            Order::where('id', $id)->delete();
        }
        foreach ($this->walletUserIds as $userId) {
            WalletTxn::where('user_id', $userId)->delete();
            UserWallet::where('user_id', $userId)->delete();
        }
        foreach ($this->storeIds as $id) {
            Store::where('id', $id)->forceDelete();
        }
        foreach ($this->userIds as $id) {
            User::where('id', $id)->forceDelete();
        }
        foreach ($this->profileIds as $id) {
            TechnicianProfile::where('id', $id)->forceDelete();
        }
        $this->orderIds = [];
        $this->userIds = [];
        $this->storeIds = [];
        $this->itemIds = [];
        $this->walletUserIds = [];
        $this->profileIds = [];
    }

    private function clearSubscribeEnv(): void
    {
        putenv('WECHAT_SUBSCRIBE_TEMPLATE_ID');
        putenv('WECHAT_SUBSCRIBE_TEMPLATE_PAID');
        putenv('WECHAT_SUBSCRIBE_TEMPLATE_REFUND');
        putenv('WECHAT_SUBSCRIBE_TEMPLATE_VERIFIED');
        putenv('WECHAT_SUBSCRIBE_APP_ID');
        putenv('WECHAT_SUBSCRIBE_APP_SECRET');
    }

    private function configureSubscribe(string $templateEnv = 'WECHAT_SUBSCRIBE_TEMPLATE_PAID'): void
    {
        putenv('WECHAT_SUBSCRIBE_TEMPLATE_ID=tpl_subscribe_test');
        putenv($templateEnv . '=tpl_event_test');
        putenv('WECHAT_SUBSCRIBE_APP_ID=wx_sub_appid');
        putenv('WECHAT_SUBSCRIBE_APP_SECRET=wx_sub_secret');
    }

    // ── 造数 ──

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

    private function makeUser(string $nickname, string $openid = ''): User
    {
        $user = User::create([
            'phone'     => '199' . substr((string) random_int(10000000, 99999999), 0, 8),
            'nickname'  => $nickname,
            'user_type' => 'user',
            'status'    => 1,
            'wx_openid' => $openid,
        ]);
        $this->userIds[] = $user->id;
        return $user;
    }

    private function makeItem(Order $order, string $name = '肩颈按摩'): void
    {
        $item = OrderItem::create([
            'id'          => OrderItem::generateId(),
            'order_id'    => $order->id,
            'target_type' => 'service',
            'target_id'   => OrderItem::generateId(),
            'name'        => $name,
            'price'       => 100.0,
            'quantity'    => 1,
        ]);
        $this->itemIds[] = $item->id;
    }

    /** 造待支付订单 + pending 支付记录（金额可指定，用于零元直通路径） */
    private function makePendingOrder(User $customer, float $amount = 100.0): Order
    {
        $order = Order::create([
            'order_no'        => 'ORD_WIRE_' . uniqid(),
            'user_id'         => $customer->id,
            'technician_id'   => (string) (9900000000000000 + random_int(1, 999999)),
            'store_id'        => $this->makeStore()->id,
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => $amount,
            'discount_amount' => 0.0,
            'paid_amount'     => $amount,
            'service_time'    => date('Y-m-d H:i:s', time() + 86400),
            'status'          => Order::STATUS_PENDING,
        ]);
        $this->orderIds[] = $order->id;
        $this->makeItem($order);

        OrderPayment::create([
            'order_id'   => $order->id,
            'payment_no' => 'PAYWIRE_' . uniqid(),
            'pay_type'   => 'balance',
            'amount'     => $amount,
            'status'     => OrderPayment::STATUS_PENDING,
        ]);

        return $order;
    }

    /** 造已支付订单 + 成功支付记录（pay_type 可指定；balance 供退款链路用） */
    private function makePaidOrder(User $customer, string $payType = 'wechat'): Order
    {
        $order = Order::create([
            'order_no'        => 'ORD_WIREP_' . uniqid(),
            'user_id'         => $customer->id,
            'technician_id'   => (string) (9900000000000000 + random_int(1, 999999)),
            'store_id'        => $this->makeStore()->id,
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => 100.0,
            'discount_amount' => 0.0,
            'paid_amount'     => 100.0,
            'service_time'    => date('Y-m-d H:i:s', time() + 86400),
            'status'          => Order::STATUS_PAID,
        ]);
        $this->orderIds[] = $order->id;
        $this->makeItem($order);

        OrderPayment::create([
            'order_id'   => $order->id,
            'payment_no' => 'PAYWIREP_' . uniqid(),
            'pay_type'   => $payType,
            'amount'     => 100.0,
            'status'     => OrderPayment::STATUS_SUCCESS,
            'paid_at'    => date('Y-m-d H:i:s'),
        ]);

        return $order;
    }

    private function makeWallet(User $customer, float $balance): void
    {
        UserWallet::create([
            'user_id'        => $customer->id,
            'balance'        => $balance,
            'total_recharge' => 0.0,
            'total_consume'  => 0.0,
        ]);
        $this->walletUserIds[] = $customer->id;
    }

    private function makeTechnician(): TechnicianProfile
    {
        $profile = new TechnicianProfile();
        $profile->id        = TechnicianProfile::generateId();
        $profile->user_id   = (string) (9900000000000000 + random_int(1, 999999));
        $profile->real_name = '测试技师';
        $profile->gender    = 1;
        $profile->status    = 'approved';
        $profile->save();
        $this->profileIds[] = $profile->id;
        return $profile;
    }

    // ── 工具 ──

    private function makeRequest(array $post = []): Request
    {
        $body = http_build_query($post);
        $head = "Host: localhost\r\n"
            . "Content-Type: application/x-www-form-urlencoded\r\n"
            . "Content-Length: " . strlen($body) . "\r\n";
        return new Request("POST / HTTP/1.1\r\n" . $head . "\r\n" . $body);
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    private static function invokePrivate(object $obj, string $method, array $args = []): mixed
    {
        $m = new \ReflectionMethod($obj, $method);
        $m->setAccessible(true);
        return $m->invokeArgs($obj, $args);
    }

    /** 记录 notifySubscribeEvent 调用的控制器子类（验证接线点） */
    private function makeRecordingController(array &$recorded): OrderController
    {
        return new class($recorded) extends OrderController {
            private array $recorded;

            public function __construct(array &$recorded)
            {
                $this->recorded = &$recorded;
            }

            protected function notifySubscribeEvent(Order $order, string $scene, array $extra = []): void
            {
                $this->recorded[] = [
                    'order_id' => (string) $order->id,
                    'scene'    => $scene,
                    'extra'    => $extra,
                ];
            }
        };
    }

    /** fake 发送器：记录调用参数，返回预设微信响应（不触发真实 HTTP） */
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

    /** 带 fake 发送器的服务实例（覆写 makeWechatService 工厂） */
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

    // ── 接线点：支付成功 ──

    #[Test] public function zero_amount_pay_wires_pay_subscribe_event(): void
    {
        $customer = $this->makeUser('顾客');
        $order = $this->makePendingOrder($customer, 0.0);
        $recorded = [];
        $ctl = $this->makeRecordingController($recorded);

        $request = $this->makeRequest();
        $request->user_id = $customer->id;
        $id = Container::get('hashids')->encode((int) $order->id);
        $resp = $ctl->pay($request, (string) $id);

        $this->assertSame(0, $this->body($resp)['code']);
        $this->assertSame('订单支付成功', $this->body($resp)['message']);
        $this->assertCount(1, $recorded);
        $this->assertSame(NotificationReminderService::SCENE_PAY, $recorded[0]['scene']);
        $this->assertSame((string) $order->id, $recorded[0]['order_id']);
    }

    #[Test] public function balance_pay_wires_pay_subscribe_event(): void
    {
        $customer = $this->makeUser('顾客');
        $this->makeWallet($customer, 100.0);
        $order = $this->makePendingOrder($customer);
        $recorded = [];
        $ctl = $this->makeRecordingController($recorded);

        $resp = self::invokePrivate($ctl, 'doBalancePay', [$order, $order->payment()->first()]);

        $this->assertSame(0, $this->body($resp)['code']);
        $this->assertSame(Order::STATUS_PAID, Order::find($order->id)->status);
        $this->assertCount(1, $recorded);
        $this->assertSame(NotificationReminderService::SCENE_PAY, $recorded[0]['scene']);
    }

    // ── 接线点：退款到账（balance 渠道两段式成功）──

    #[Test] public function balance_refund_wires_refund_subscribe_event(): void
    {
        $customer = $this->makeUser('顾客');
        $this->makeWallet($customer, 0.0);
        $order = $this->makePaidOrder($customer, 'balance');
        $recorded = [];
        $ctl = $this->makeRecordingController($recorded);

        $resp = self::invokePrivate($ctl, 'doRefund', [
            $this->makeRequest(['reason' => '不想要了']),
            $order,
            1.0,
        ]);

        $this->assertSame(0, $this->body($resp)['code']);
        $this->assertSame(Order::STATUS_REFUNDED, Order::find($order->id)->status);
        $this->assertCount(1, $recorded);
        $this->assertSame(NotificationReminderService::SCENE_REFUND, $recorded[0]['scene']);
        $this->assertSame(100.0, (float) ($recorded[0]['extra']['refund_amount'] ?? 0));
        $this->assertSame('不想要了', $recorded[0]['extra']['refund_reason'] ?? '');
    }

    // ── 接线点：扫码核销成功 ──

    #[Test] public function verify_wires_verified_subscribe_event(): void
    {
        $customer = $this->makeUser('顾客');
        $technician = $this->makeTechnician();
        $order = Order::create([
            'order_no'        => 'ORD_WIREV_' . uniqid(),
            'user_id'         => $customer->id,
            'technician_id'   => $technician->id,
            'store_id'        => $this->makeStore()->id,
            'order_type'      => Order::ORDER_TYPE_APPOINTMENT,
            'total_amount'    => 100.0,
            'discount_amount' => 0.0,
            'paid_amount'     => 100.0,
            'status'          => Order::STATUS_PAID,
        ]);
        $this->orderIds[] = $order->id;
        $this->makeItem($order);

        $code = bin2hex(random_bytes(16));
        OrderVerification::create([
            'id'       => OrderVerification::generateId(),
            'order_id' => $order->id,
            'code'     => $code,
        ]);

        $recorded = [];
        $ctl = $this->makeRecordingController($recorded);

        $request = $this->makeRequest(['code' => $code]);
        $request->user_id = $technician->user_id;
        $resp = $ctl->verifyByCode($request);

        $this->assertSame(0, $this->body($resp)['code']);
        $this->assertSame(Order::STATUS_SERVING, Order::find($order->id)->status);
        $this->assertCount(1, $recorded);
        $this->assertSame(NotificationReminderService::SCENE_VERIFIED, $recorded[0]['scene']);
    }

    // ── 接线点：微信支付回调成功（PaymentNotifyController）──

    #[Test] public function wechat_notify_pay_subscribe_is_idempotent(): void
    {
        $customer = $this->makeUser('顾客');
        $order = $this->makePaidOrder($customer); // 模拟回调已置 PAID
        $ctl = new PaymentNotifyController();

        self::invokePrivate($ctl, 'notifyPaySubscribe', [$order->id]);
        self::invokePrivate($ctl, 'notifyPaySubscribe', [$order->id]); // 重复回调

        // 站内通知行只建一条；模板未配置 → 降级仅站内通知
        $this->assertSame(1, Db::table('erik_notification')
            ->where('order_id', $order->id)
            ->where('title', '订单支付成功')
            ->count());
    }

    // ── sendSubscribeForOrderEvent 发送链路 ──

    #[Test] public function pay_event_sends_with_data_and_marks_pushed_once(): void
    {
        $this->configureSubscribe();
        $customer = $this->makeUser('顾客', 'openid_ok');
        $order = $this->makePaidOrder($customer);
        $sender = $this->makeFakeSender(['errcode' => 0, 'errmsg' => 'ok']);
        $service = $this->makeServiceWithSender($sender);

        $this->assertTrue($service->sendSubscribeForOrderEvent($order, NotificationReminderService::SCENE_PAY));

        $this->assertCount(1, $sender->calls);
        $call = $sender->calls[0];
        $this->assertSame('openid_ok', $call['openid']);
        $this->assertSame('tpl_event_test', $call['template_id']);
        $this->assertSame('', $call['page']);
        // 字段名（thing1/thing2/amount3）与值组装正确
        $this->assertSame(['value' => '肩颈按摩'], $call['data']['thing1']);
        $this->assertSame(['value' => $order->order_no], $call['data']['thing2']);
        $this->assertSame(['value' => '100.00'], $call['data']['amount3']);

        // 推送成功后写"已推送"标记
        $this->assertNotNull(Notification::where('order_id', $order->id)
            ->where('title', '订单支付成功')
            ->value('push_sent_at'));

        // 幂等：再次触发不重复推送（同订单同场景只推一次）
        $this->assertFalse($service->sendSubscribeForOrderEvent($order, NotificationReminderService::SCENE_PAY));
        $this->assertCount(1, $sender->calls);
    }

    #[Test] public function refund_event_sends_with_extra_data(): void
    {
        $this->configureSubscribe('WECHAT_SUBSCRIBE_TEMPLATE_REFUND');
        $customer = $this->makeUser('顾客', 'openid_refund');
        $order = $this->makePaidOrder($customer);
        $sender = $this->makeFakeSender(['errcode' => 0, 'errmsg' => 'ok']);
        $service = $this->makeServiceWithSender($sender);

        $sent = $service->sendSubscribeForOrderEvent($order, NotificationReminderService::SCENE_REFUND, [
            'refund_amount' => 50.0,
            'refund_reason' => '不想要了',
        ]);

        $this->assertTrue($sent);
        $this->assertCount(1, $sender->calls);
        $data = $sender->calls[0]['data'];
        $this->assertSame(['value' => $order->order_no], $data['thing1']);
        $this->assertSame(['value' => '50.00'], $data['amount2']);
        $this->assertSame(['value' => '不想要了'], $data['thing3']);
        // 主路径 writeRefundNotification 已写「退款已到账」行 → 复用不双写
        $this->assertSame(1, Notification::where('order_id', $order->id)
            ->where('title', '退款已到账')
            ->count());
    }

    #[Test] public function send_failure_keeps_push_sent_at_null(): void
    {
        $this->configureSubscribe();
        $customer = $this->makeUser('顾客', 'openid_fail');
        $order = $this->makePaidOrder($customer);
        $sender = $this->makeFakeSender(['errcode' => 43101, 'errmsg' => 'user refuse']);
        $service = $this->makeServiceWithSender($sender);

        $this->assertFalse($service->sendSubscribeForOrderEvent($order, NotificationReminderService::SCENE_PAY));

        $this->assertCount(1, $sender->calls);
        $this->assertNull(Notification::where('order_id', $order->id)
            ->where('title', '订单支付成功')
            ->value('push_sent_at'));
    }

    #[Test] public function unconfigured_template_degrades_to_in_app_only(): void
    {
        $customer = $this->makeUser('顾客', 'openid_degrade');
        $order = $this->makePaidOrder($customer);
        $sender = $this->makeFakeSender(['errcode' => 0, 'errmsg' => 'ok']);
        $service = $this->makeServiceWithSender($sender);

        $this->assertFalse($service->sendSubscribeForOrderEvent($order, NotificationReminderService::SCENE_PAY));
        $this->assertCount(0, $sender->calls);
        // 站内通知行照常补建（降级仅站内通知）
        $this->assertSame(1, Notification::where('order_id', $order->id)
            ->where('title', '订单支付成功')
            ->count());
    }

    #[Test] public function sender_exception_does_not_bubble(): void
    {
        $this->configureSubscribe();
        $customer = $this->makeUser('顾客', 'openid_exc');
        $order = $this->makePaidOrder($customer);

        $throwing = new class extends WechatTemplateMessageService {
            public function sendSubscribeMessage(
                string $openid,
                string $templateId,
                array  $data,
                string $page = ''
            ): array {
                throw new \RuntimeException('wechat timeout');
            }
        };
        $service = $this->makeServiceWithSender($throwing);

        // 发送器抛异常：方法捕获返回 false，异常不冒泡，站内通知仍补建
        $this->assertFalse($service->sendSubscribeForOrderEvent($order, NotificationReminderService::SCENE_PAY));
        $this->assertSame(1, Notification::where('order_id', $order->id)
            ->where('title', '订单支付成功')
            ->count());
    }
}
