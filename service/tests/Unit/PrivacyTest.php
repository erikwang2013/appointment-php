<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\api\v1\controller\AuthController;
use app\api\v1\controller\PrivacyController;
use app\model\Order;
use app\model\User;
use support\Db;
use support\Model;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 隐私合规测试（真实 DB + tearDown 清理）
 *
 * 覆盖：
 * - 数据导出：个人信息（手机号原样）与各分组齐全
 * - 申请注销：余额非 0 / 未完成订单 / 进行中工单 → 422
 * - 申请 → 撤销 → 恢复正常
 * - 申请满 72h 后确认注销成功且手机号/昵称匿名化、账号禁用
 * - 已注销账号登录被拦截（403）
 */
class PrivacyTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理 */
    private array $userIds = [];

    protected function tearDown(): void
    {
        foreach ($this->userIds as $id) {
            Db::table('erik_order_review')->where('user_id', $id)->delete();
            Db::table('erik_wallet_txn')->where('user_id', $id)->delete();
            Db::table('erik_user_points')->where('user_id', $id)->delete();
            Db::table('erik_user_address')->where('user_id', $id)->delete();
            Db::table('erik_invoice')->where('user_id', $id)->delete();
            Db::table('erik_order')->where('user_id', $id)->delete();
            Db::table('erik_ticket')->where('user_id', $id)->delete();
            Db::table('erik_user_wallet')->where('user_id', $id)->delete();
            User::where('id', $id)->forceDelete();
        }
        $this->userIds = [];
    }

    /** 造用户 */
    private function makeUser(array $attrs = []): User
    {
        $user = User::create(array_merge([
            'phone'     => '188' . substr((string) random_int(10000000, 99999999), 0, 8),
            'nickname'  => '隐私测试用户',
            'user_type' => 'customer',
            'status'    => 1,
        ], $attrs));
        $this->userIds[] = $user->id;
        return $user;
    }

    /** 造请求（user_id 由 Auth 中间件注入，测试直接赋值） */
    private function makeRequest(string $method, ?string $userId = null, array $post = []): Request
    {
        $body = http_build_query($post);
        $head = "Host: localhost\r\n"
            . "Content-Type: application/x-www-form-urlencoded\r\n"
            . "Content-Length: " . strlen($body) . "\r\n";
        $request = new Request($method . " / HTTP/1.1\r\n" . $head . "\r\n" . $body);
        if ($userId !== null) {
            $request->user_id = $userId;
        }
        return $request;
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    private function controller(): PrivacyController
    {
        return new PrivacyController();
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    private function seedOrder(string $userId, string $status): void
    {
        Db::table('erik_order')->insert([
            'id'              => Model::generateId(),
            'order_no'        => 'T' . Model::generateId(),
            'user_id'         => $userId,
            'order_type'      => 'service',
            'total_amount'    => 99.00,
            'discount_amount' => 0,
            'paid_amount'     => 99.00,
            'service_time'    => date('Y-m-d H:i:s', time() + 3600),
            'status'          => $status,
            'created_at'      => $this->now(),
            'updated_at'      => $this->now(),
        ]);
    }

    #[Test]
    public function exportContainsPersonalInfoAndAllGroups(): void
    {
        $user = $this->makeUser();
        $this->seedOrder($user->id, Order::STATUS_COMPLETED);

        Db::table('erik_user_points')->insert([
            'id' => Model::generateId(), 'user_id' => $user->id, 'type' => 'earn',
            'points' => 100, 'balance' => 100, 'source' => 'order', 'description' => '消费赠送',
            'created_at' => $this->now(), 'updated_at' => $this->now(),
        ]);
        Db::table('erik_wallet_txn')->insert([
            'id' => Model::generateId(), 'user_id' => $user->id, 'type' => 'recharge',
            'amount' => 50.00, 'balance_after' => 50.00, 'remark' => '充值',
            'created_at' => $this->now(),
        ]);
        Db::table('erik_order_review')->insert([
            'id' => Model::generateId(), 'order_id' => Model::generateId(),
            'user_id' => $user->id, 'rating' => 5, 'content' => '服务很好',
            'status' => 1, 'created_at' => $this->now(), 'updated_at' => $this->now(),
        ]);
        Db::table('erik_user_address')->insert([
            'id' => Model::generateId(), 'user_id' => $user->id,
            'contact_name' => '张三', 'contact_phone' => '13900000000',
            'province' => '广东省', 'city' => '深圳市', 'district' => '南山区',
            'detail' => '科技园', 'is_default' => 1,
            'created_at' => $this->now(), 'updated_at' => $this->now(),
        ]);
        Db::table('erik_invoice')->insert([
            'id' => Model::generateId(), 'user_id' => $user->id,
            'order_id' => Model::generateId(), 'order_type' => 'service',
            'title_type' => 'personal', 'invoice_title' => '个人',
            'amount' => 99.00, 'status' => 'pending',
            'created_at' => $this->now(), 'updated_at' => $this->now(),
        ]);

        $response = $this->controller()->data($this->makeRequest('GET', $user->id));
        $data = $this->body($response)['data'];

        $this->assertSame(0, $this->body($response)['code']);
        $this->assertSame($user->phone, $data['personal']['phone']);
        $this->assertSame($user->nickname, $data['personal']['nickname']);
        $this->assertCount(1, $data['orders']);
        $this->assertCount(1, $data['points']);
        $this->assertCount(1, $data['wallet_txns']);
        $this->assertCount(1, $data['reviews']);
        $this->assertCount(1, $data['addresses']);
        $this->assertCount(1, $data['invoices']);
        $this->assertNotEmpty($data['exported_at']);
    }

    #[Test]
    public function closeRequestRejectedWhenBalanceNotZero(): void
    {
        $user = $this->makeUser();
        Db::table('erik_user_wallet')->insert([
            'id' => Model::generateId(), 'user_id' => $user->id,
            'balance' => 100.00, 'total_recharge' => 100.00, 'total_consume' => 0,
            'created_at' => $this->now(), 'updated_at' => $this->now(),
        ]);

        $response = $this->controller()->closeRequest($this->makeRequest('POST', $user->id));

        $this->assertSame(422, $this->body($response)['code']);
        $this->assertSame(0, (int) User::find($user->id)->close_status);
    }

    #[Test]
    public function closeRequestRejectedWhenUnfinishedOrderOrActiveTicket(): void
    {
        $user = $this->makeUser();
        $this->seedOrder($user->id, Order::STATUS_PENDING);

        $response = $this->controller()->closeRequest($this->makeRequest('POST', $user->id));
        $this->assertSame(422, $this->body($response)['code']);

        Db::table('erik_order')->where('user_id', $user->id)->delete();
        Db::table('erik_ticket')->insert([
            'id' => Model::generateId(), 'user_id' => $user->id,
            'category' => '售后', 'description' => '进行中', 'status' => 'processing',
            'created_at' => $this->now(), 'updated_at' => $this->now(),
        ]);

        $response = $this->controller()->closeRequest($this->makeRequest('POST', $user->id));
        $this->assertSame(422, $this->body($response)['code']);
    }

    #[Test]
    public function closeRequestThenCancelRestoresNormal(): void
    {
        $user = $this->makeUser();

        $response = $this->controller()->closeRequest($this->makeRequest('POST', $user->id));
        $this->assertSame(0, $this->body($response)['code']);

        $fresh = User::find($user->id);
        $this->assertSame(1, (int) $fresh->close_status);
        $this->assertNotNull($fresh->close_requested_at);

        $response = $this->controller()->closeCancel($this->makeRequest('POST', $user->id));
        $this->assertSame(0, $this->body($response)['code']);

        $fresh = User::find($user->id);
        $this->assertSame(0, (int) $fresh->close_status);
        $this->assertNull($fresh->close_requested_at);
    }

    #[Test]
    public function closeConfirmAfter72hAnonymizesAndDisablesAccount(): void
    {
        $user = $this->makeUser();
        $user->forceFill([
            'close_status' => 1,
            'close_requested_at' => date('Y-m-d H:i:s', time() - 73 * 3600),
        ])->save();

        $response = $this->controller()->closeConfirm($this->makeRequest('POST', $user->id));
        $this->assertSame(0, $this->body($response)['code']);

        $fresh = User::find($user->id);
        $this->assertSame(2, (int) $fresh->close_status);
        $this->assertSame('user' . $user->id, $fresh->phone);
        $this->assertSame('user' . $user->id, $fresh->nickname);
        $this->assertSame(0, (int) $fresh->status);
        $this->assertNotNull($fresh->close_at);
    }

    #[Test]
    public function loginBlockedForClosedAccount(): void
    {
        $password = 'secret123';
        $user = $this->makeUser([
            'password' => password_hash($password, PASSWORD_BCRYPT),
        ]);
        $user->forceFill(['close_status' => 2])->save();

        $response = (new AuthController())->login($this->makeRequest('POST', null, [
            'phone' => $user->phone,
            'password' => $password,
        ]));

        $this->assertSame(403, $this->body($response)['code']);
        $this->assertSame('账号已注销', $this->body($response)['message']);
    }
}
