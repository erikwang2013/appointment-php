<?php
declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use app\model\Ticket;
use app\user\v1\controller\TicketController;
use support\Container;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 客服工单用户端闭环测试
 *
 * 覆盖：提交成功（pending+落库）、非法分类/空描述 422、
 * 非本人详情 404、仅本人可关闭、resolved 不可关闭、列表只返回本人。
 * 基建与 AftersaleTest 一致（真实 DB + tearDown 清理）。
 */
class TicketTest extends TestCase
{
    /** @var int[] 用例创建的工单 ID，tearDown 统一清理 */
    private array $ticketIds = [];

    protected function tearDown(): void
    {
        foreach ($this->ticketIds as $id) {
            Ticket::where('id', $id)->delete();
        }
        $this->ticketIds = [];
    }

    private function makeRequest(array $post = [], string $method = 'POST'): Request
    {
        $body = http_build_query($post);
        $head = "Host: localhost\r\n"
            . "Content-Type: application/x-www-form-urlencoded\r\n"
            . "Content-Length: " . strlen($body) . "\r\n";
        return new Request("$method / HTTP/1.1\r\n" . $head . "\r\n" . $body);
    }

    private function body(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?: [];
    }

    private function newUserId(): string
    {
        return (string) (9900000000000000 + random_int(1, 999999));
    }

    /** 以指定用户提交工单 */
    private function submit(string $userId, array $post): array
    {
        $request = $this->makeRequest($post);
        $request->user_id = $userId;
        return $this->body((new TicketController())->store($request));
    }

    private function encodeId(int $id): string
    {
        return Container::get('hashids')->encode($id);
    }

    private function decodeId(string $hashid): int
    {
        return (int) Container::get('hashids')->decode($hashid)[0];
    }

    /** 直接插入一条工单（绕过控制器） */
    private function makeTicket(string $userId, string $status = 'pending'): Ticket
    {
        $ticket = Ticket::create([
            'id'          => Ticket::generateId(),
            'user_id'     => $userId,
            'category'    => 'service',
            'description' => '测试工单',
            'status'      => $status,
        ]);
        $this->ticketIds[] = $ticket->id;
        return $ticket;
    }

    // ── 提交成功 ──

    #[Test] public function submit_creates_pending_ticket(): void
    {
        $userId = $this->newUserId();

        $resp = $this->submit($userId, [
            'category'    => 'service',
            'description' => '预约后技师未按时到店',
            'images'      => ['https://example.com/1.jpg'],
        ]);

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame('pending', $resp['data']['status']);

        $row = Ticket::where('user_id', $userId)->first();
        $this->assertNotNull($row);
        $this->ticketIds[] = $row->id;
        $this->assertSame($userId, (string) $row->user_id);
        $this->assertSame('service', $row->category);
        $this->assertSame('预约后技师未按时到店', $row->description);
        $this->assertSame(['https://example.com/1.jpg'], $row->images, 'images 应以 JSON 数组落库');
        $this->assertSame((int) $row->id, $this->decodeId((string) $resp['data']['id']), 'id 应为 hashid 且可还原');
    }

    // ── 参数校验 ──

    #[Test] public function submit_rejects_invalid_category(): void
    {
        $userId = $this->newUserId();

        $resp = $this->submit($userId, [
            'category'    => 'complaint',
            'description' => '投诉',
        ]);

        $this->assertSame(422, $resp['code']);
        $this->assertSame(0, Ticket::where('user_id', $userId)->count(), '非法分类不得落库');
    }

    #[Test] public function submit_requires_description(): void
    {
        $userId = $this->newUserId();

        $resp = $this->submit($userId, ['category' => 'other', 'description' => '   ']);

        $this->assertSame(422, $resp['code']);
        $this->assertSame(0, Ticket::where('user_id', $userId)->count());
    }

    // ── 详情归属 ──

    #[Test] public function show_returns_own_detail_and_hides_foreign(): void
    {
        $owner = $this->newUserId();
        $other = $this->newUserId();
        $ticket = $this->makeTicket($owner);

        // 本人可见（含 status 文案、回复内容、回复时间）
        $req1 = $this->makeRequest([], 'GET');
        $req1->user_id = $owner;
        $resp1 = $this->body((new TicketController())->show($req1, $this->encodeId((int) $ticket->id)));
        $this->assertSame(0, $resp1['code'], json_encode($resp1));
        $this->assertSame('待处理', $resp1['data']['status_text']);
        $this->assertArrayHasKey('reply_content', $resp1['data']);
        $this->assertArrayHasKey('replied_at', $resp1['data']);

        // 他人不可见（404）
        $req2 = $this->makeRequest([], 'GET');
        $req2->user_id = $other;
        $resp2 = $this->body((new TicketController())->show($req2, $this->encodeId((int) $ticket->id)));
        $this->assertSame(404, $resp2['code']);
    }

    // ── 关闭 ──

    #[Test] public function close_only_owner_and_active_status(): void
    {
        $owner = $this->newUserId();
        $other = $this->newUserId();
        $ticket = $this->makeTicket($owner, 'processing');

        // 非本人 → 404
        $reqForeign = $this->makeRequest();
        $reqForeign->user_id = $other;
        $respForeign = $this->body((new TicketController())->close($reqForeign, $this->encodeId((int) $ticket->id)));
        $this->assertSame(404, $respForeign['code']);

        // 本人 pending/processing 可关
        $req = $this->makeRequest();
        $req->user_id = $owner;
        $resp = $this->body((new TicketController())->close($req, $this->encodeId((int) $ticket->id)));
        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame('closed', $resp['data']['status']);
        $this->assertSame('closed', (string) Ticket::find($ticket->id)->status, '落库状态应置 closed');

        // 已关闭不可再关 → 422
        $req2 = $this->makeRequest();
        $req2->user_id = $owner;
        $resp2 = $this->body((new TicketController())->close($req2, $this->encodeId((int) $ticket->id)));
        $this->assertSame(422, $resp2['code']);
    }

    #[Test] public function close_resolved_rejected_with_422(): void
    {
        $userId = $this->newUserId();
        $ticket = $this->makeTicket($userId, 'resolved');

        $req = $this->makeRequest();
        $req->user_id = $userId;
        $resp = $this->body((new TicketController())->close($req, $this->encodeId((int) $ticket->id)));

        $this->assertSame(422, $resp['code']);
        $this->assertSame('resolved', (string) Ticket::find($ticket->id)->status, '状态不得变更');
    }

    // ── 我的列表 ──

    #[Test] public function list_returns_only_own_tickets_with_status_filter(): void
    {
        $userA = $this->newUserId();
        $userB = $this->newUserId();
        $t1 = $this->makeTicket($userA, 'pending');
        $t2 = $this->makeTicket($userA, 'resolved');
        $this->makeTicket($userB, 'pending');

        // 人工制造 created_at 先后（DATETIME 秒级精度，同秒内排序不确定），验证 desc 排序
        Ticket::where('id', $t1->id)->update(['created_at' => '2026-08-14 09:00:00']);
        Ticket::where('id', $t2->id)->update(['created_at' => '2026-08-14 10:00:00']);

        $request = $this->makeRequest(['page' => 1, 'limit' => 10], 'GET');
        $request->user_id = $userA;
        $resp = $this->body((new TicketController())->index($request));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame(2, $resp['meta']['total'], '只返回本人工单');
        $this->assertSame(2, count($resp['data']));
        $this->assertSame((int) $t2->id, $this->decodeId((string) $resp['data'][0]['id']), '按 created_at desc');

        // status 筛选
        $request2 = $this->makeRequest(['page' => 1, 'limit' => 10, 'status' => 'resolved'], 'GET');
        $request2->user_id = $userA;
        $resp2 = $this->body((new TicketController())->index($request2));
        $this->assertSame(1, $resp2['meta']['total']);
        $this->assertSame('resolved', $resp2['data'][0]['status']);
    }
}
