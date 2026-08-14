<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\model\TechnicianEarning;
use app\model\TechnicianProfile;
use app\model\User;
use app\technician\v1\controller\EarningController;
use Webman\Http\Request;

/**
 * EarningController::index 汇总结构测试（第4轮审计 P1）
 *
 * 覆盖：汇总由多次独立 SUM 合并为一次 GROUP BY status 聚合后，返回结构与语义不变：
 *   - summary.today_income / pending_settlement / balance
 *   - earnings 明细 + meta 分页结构
 */
class EarningControllerTest extends TestCase
{
    /** @var string[] 用例创建的收益流水 ID */
    private array $earningIds = [];

    /** @var string[] 用例创建的技师档案 ID */
    private array $technicianIds = [];

    /** @var string[] 用例创建的用户 ID */
    private array $userIds = [];

    protected function tearDown(): void
    {
        foreach ($this->earningIds as $id) {
            TechnicianEarning::where('id', $id)->delete();
        }
        foreach ($this->technicianIds as $id) {
            TechnicianProfile::where('id', $id)->forceDelete();
        }
        foreach ($this->userIds as $id) {
            User::where('id', $id)->forceDelete();
        }
        $this->earningIds = [];
        $this->technicianIds = [];
        $this->userIds = [];
    }

    private function makeUser(): User
    {
        $user = User::create([
            'phone'     => '199' . substr((string) random_int(10000000, 99999999), 0, 8),
            'wx_openid' => '',
            'user_type' => 'user',
            'status'    => 1,
        ]);
        $this->userIds[] = $user->id;
        return $user;
    }

    private function makeTechnician(User $user): TechnicianProfile
    {
        $tp = TechnicianProfile::create([
            'user_id'    => $user->id,
            'real_name'  => '收益汇总测试技师',
            'status'     => 'active',
            'audited_at' => date('Y-m-d H:i:s'),
        ]);
        $this->technicianIds[] = $tp->id;
        return $tp;
    }

    /** 造收益流水（createdAt 可显式指定以控制统计口径） */
    private function makeEarning(TechnicianProfile $technician, float $amount, string $status, ?string $createdAt = null): TechnicianEarning
    {
        $e = TechnicianEarning::create([
            'id'            => TechnicianEarning::generateId(),
            'technician_id' => $technician->id,
            'order_id'      => 0,
            'type'          => 'commission',
            'amount'        => number_format($amount, 2, '.', ''),
            'description'   => '',
            'status'        => $status,
        ]);
        if ($createdAt !== null) {
            $e->created_at = $createdAt;
            $e->save();
        }
        $this->earningIds[] = $e->id;
        return $e;
    }

    private function makeRequest(): Request
    {
        $query = http_build_query(['page' => 1, 'per_page' => 15]);
        $head  = "Host: localhost\r\n"
            . "Content-Type: application/x-www-form-urlencoded\r\n"
            . "Content-Length: 0\r\n";
        return new Request("GET /?" . $query . " HTTP/1.1\r\n" . $head . "\r\n");
    }

    #[Test] public function index_returns_merged_summary_structure(): void
    {
        $user = $this->makeUser();
        $technician = $this->makeTechnician($user);

        // 三天前的三态收益（不参与今日收入）：pending 20 / settled 30 / withdrawn 10
        $this->makeEarning($technician, 20.0, 'pending', date('Y-m-d', strtotime('-3 days')) . ' 10:00:00');
        $this->makeEarning($technician, 30.0, 'settled', date('Y-m-d', strtotime('-3 days')) . ' 10:00:00');
        $this->makeEarning($technician, 10.0, 'withdrawn', date('Y-m-d', strtotime('-3 days')) . ' 10:00:00');
        // 今日 commission 收益（参与今日收入）：15
        $this->makeEarning($technician, 15.0, 'settled', date('Y-m-d') . ' 09:00:00');

        $request = $this->makeRequest();
        $request->technician_id = $technician->id;

        $response = (new EarningController())->index($request);
        $body = json_decode($response->rawBody(), true);

        $this->assertSame(0, $body['code']);
        $this->assertSame('success', $body['message']);

        // 汇总口径与合并前一致（JSON 整数值反序列化为 int，比较前转 float）
        $this->assertSame(15.0, (float)$body['data']['summary']['today_income']);
        $this->assertSame(20.0, (float)$body['data']['summary']['pending_settlement']);
        // 余额 = 全部 settled(30+15) - withdrawn(10) = 35（与合并前的全量 SUM 口径一致）
        $this->assertSame(35.0, (float)$body['data']['summary']['balance']);

        // 明细与分页结构
        $this->assertCount(4, $body['data']['earnings']);
        $this->assertSame(1, $body['data']['meta']['current_page']);
        $this->assertSame(15, $body['data']['meta']['per_page']);
        $this->assertSame(4, $body['data']['meta']['total']);
        $this->assertSame(1, $body['data']['meta']['last_page']);
        $this->assertFalse($body['data']['meta']['has_more']);
        $this->assertArrayHasKey('id', $body['data']['earnings'][0]);
        $this->assertArrayHasKey('status', $body['data']['earnings'][0]);
    }
}
