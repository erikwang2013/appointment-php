<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\user\v1\controller\AddressController;
use app\model\User;
use app\model\UserAddress;
use support\Container;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 收货地址控制器测试
 *
 * 覆盖：新增（含默认地址互斥）/ 必填校验 400 / 列表排序、
 * 详情与更新与删除的 404（他人地址不可见）、默认地址切换互斥、删除后不可见。
 *
 * 注意：本控制器 {id} 路由参数未做 hashids 解码（与 PointsExchange 等控制器不同），
 * 测试按实际行为传原始 ID 字符串。
 */
class AddressControllerTest extends TestCase
{
    /** @var string[] 用例用户 ID，tearDown 统一清理 */
    private array $userIds = [];

    /** @var string[] 用例地址 ID */
    private array $addressIds = [];

    protected function tearDown(): void
    {
        foreach ($this->userIds as $uid) {
            UserAddress::where('user_id', $uid)->delete();
            User::where('id', $uid)->delete();
        }
        $this->userIds = [];
        $this->addressIds = [];
    }

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

    private function newUserId(): string
    {
        $uid = (string) (9900000000000000 + random_int(1, 999999));
        User::create([
            'id'        => $uid,
            'phone'     => '197' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'wx_openid' => '',
            'user_type' => 'user',
            'status'    => 1,
        ]);
        $this->userIds[] = $uid;
        return $uid;
    }

    private function makeAddress(string $userId, array $attrs = []): UserAddress
    {
        $a = UserAddress::create(array_merge([
            'id'            => UserAddress::generateId(),
            'user_id'       => $userId,
            'contact_name'  => '张三',
            'contact_phone' => '13800138000',
            'province'      => '广东省',
            'city'          => '深圳市',
            'district'      => '南山区',
            'detail'        => '科技园路 1 号',
            'is_default'    => 0,
        ], $attrs));
        $this->addressIds[] = $a->id;
        return $a;
    }

    private function withUser(string $userId, array $post = []): Request
    {
        $request = $this->makeRequest($post);
        $request->user_id = $userId;
        return $request;
    }

    #[Test] public function store_creates_address_and_sets_default(): void
    {
        $uid = $this->newUserId();

        $resp = $this->body((new AddressController())->store($this->withUser($uid, [
            'contact_name' => '李四', 'contact_phone' => '13900139000',
            'province' => '广东省', 'city' => '广州市', 'district' => '天河区',
            'detail' => '体育西路 100 号', 'lat' => '23.12', 'lng' => '113.32', 'is_default' => '1',
        ])));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame('地址添加成功', $resp['message']);
        $this->assertSame('李四', $resp['data']['contact_name']);
        $this->assertSame(1, $resp['data']['is_default']);
        $this->assertSame(23.12, $resp['data']['lat']);

        $row = UserAddress::where('user_id', $uid)->first();
        $this->assertNotNull($row);
        $this->assertSame('13900139000', $row->contact_phone);
    }

    #[Test] public function store_rejects_incomplete_info(): void
    {
        $uid = $this->newUserId();

        $resp = $this->body((new AddressController())->store($this->withUser($uid, [
            'contact_name' => '李四', 'contact_phone' => '13900139000',
            'province' => '', 'city' => '', 'district' => '', 'detail' => '',
        ])));

        $this->assertSame(400, $resp['code']);
        $this->assertStringContainsString('请填写完整地址信息', (string) $resp['message']);
        $this->assertSame(0, UserAddress::where('user_id', $uid)->count(), '失败不落库');
    }

    #[Test] public function store_new_default_unsets_previous_default(): void
    {
        $uid = $this->newUserId();
        $this->makeAddress($uid, ['contact_name' => '旧默认', 'is_default' => 1]);

        $resp = $this->body((new AddressController())->store($this->withUser($uid, [
            'contact_name' => '新默认', 'contact_phone' => '13900139000',
            'province' => '广东省', 'city' => '广州市', 'district' => '天河区',
            'detail' => '体育西路 100 号', 'is_default' => '1',
        ])));

        $this->assertSame(0, $resp['code']);
        $this->assertSame(1, UserAddress::where('user_id', $uid)->where('is_default', 1)->count(), '默认地址唯一');
    }

    #[Test] public function index_lists_addresses_default_first(): void
    {
        $uid = $this->newUserId();
        $this->makeAddress($uid, ['contact_name' => '普通', 'is_default' => 0]);
        $this->makeAddress($uid, ['contact_name' => '默认', 'is_default' => 1]);

        $resp = $this->body((new AddressController())->index($this->withUser($uid)));

        $this->assertSame(0, $resp['code']);
        $this->assertCount(2, $resp['data']);
        $this->assertSame('默认', $resp['data'][0]['contact_name'], '默认地址排最前');
    }

    #[Test] public function show_returns_own_address_only(): void
    {
        $uid = $this->newUserId();
        $other = $this->newUserId();
        $mine = $this->makeAddress($uid, ['contact_name' => '我的地址']);

        $found = $this->body((new AddressController())->show($this->withUser($uid), (string) $mine->id));
        $this->assertSame(0, $found['code']);
        $this->assertSame('我的地址', $found['data']['contact_name']);

        $notFound = $this->body((new AddressController())->show($this->withUser($other), (string) $mine->id));
        $this->assertSame(404, $notFound['code'], '他人地址不可见');
    }

    #[Test] public function update_changes_fields_and_switches_default(): void
    {
        $uid = $this->newUserId();
        $old = $this->makeAddress($uid, ['contact_name' => '旧名字', 'is_default' => 1]);
        $new = $this->makeAddress($uid, ['contact_name' => '另一条', 'is_default' => 0]);

        $resp = $this->body((new AddressController())->update($this->withUser($uid, [
            'contact_name' => '新名字', 'is_default' => '1',
        ]), (string) $new->id));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame('新名字', UserAddress::find($new->id)->contact_name);
        $this->assertSame(0, (int) UserAddress::find($old->id)->is_default, '旧默认被取消');
        $this->assertSame(1, (int) UserAddress::find($new->id)->is_default);
    }

    #[Test] public function destroy_removes_address_and_404_on_second_call(): void
    {
        $uid = $this->newUserId();
        $a = $this->makeAddress($uid);

        $first = $this->body((new AddressController())->destroy($this->withUser($uid), (string) $a->id));
        $this->assertSame(0, $first['code']);
        $this->assertNull(UserAddress::find($a->id), '地址已删除');

        $second = $this->body((new AddressController())->destroy($this->withUser($uid), (string) $a->id));
        $this->assertSame(404, $second['code']);
    }
}
