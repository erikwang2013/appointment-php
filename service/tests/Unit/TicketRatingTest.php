<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use app\model\Ticket;
use app\user\v1\controller\TicketController;
use support\Container;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 工单关闭评分测试
 *
 * 覆盖：关闭带评分成功（rating+rated_at 落库）、无评分兼容（保持 NULL）、
 * 评分越界 422、非本人 404、重复关闭 422。
 * 基建与 TicketTest 一致（真实 DB + tearDown 清理）。
 */
class TicketRatingTest extends TestCase
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
        return (string) (9900000000000000 + random_int(1, 999999));
    }

    private function encodeId(int $id): string
    {
        return Container::get('hashids')->encode($id);
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

    private function close(string $userId, string $hashid, array $post = []): array
    {
        $request = $this->makeRequest($post);
        $request->user_id = $userId;
        return $this->body((new TicketController())->close($request, $hashid));
    }

    #[Test] public function close_with_rating_succeeds(): void
    {
        $userId = $this->newUserId();
        $ticket = $this->makeTicket($userId, 'processing');

        $resp = $this->close($userId, $this->encodeId((int) $ticket->id), ['rating' => '5']);

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame('closed', $resp['data']['status']);
        $this->assertSame(5, $resp['data']['rating'], '响应应带评分');

        $row = Ticket::find($ticket->id);
        $this->assertSame('closed', (string) $row->status);
        $this->assertSame(5, (int) $row->rating, '评分应落库');
        $this->assertNotNull($row->rated_at, '应记录评分时间');
    }

    #[Test] public function close_without_rating_keeps_null(): void
    {
        $userId = $this->newUserId();
        $ticket = $this->makeTicket($userId, 'pending');

        $resp = $this->close($userId, $this->encodeId((int) $ticket->id));

        $this->assertSame(0, $resp['code'], json_encode($resp));
        $this->assertSame('closed', $resp['data']['status']);

        $row = Ticket::find($ticket->id);
        $this->assertNull($row->rating, '未提供评分应保持 NULL');
        $this->assertNull($row->rated_at, '未提供评分不应记录评分时间');
    }

    #[Test] public function close_rejects_invalid_rating(): void
    {
        $userId = $this->newUserId();

        foreach (['0', '6', '3.5', 'abc', '-1'] as $invalid) {
            $ticket2 = $this->makeTicket($userId, 'pending');
            $resp = $this->close($userId, $this->encodeId((int) $ticket2->id), ['rating' => $invalid]);

            $this->assertSame(422, $resp['code'], "rating=$invalid 应 422");
            $this->assertSame('pending', (string) Ticket::find($ticket2->id)->status, '非法评分不得关闭工单');
        }
    }

    #[Test] public function close_foreign_user_with_rating_returns_404(): void
    {
        $owner = $this->newUserId();
        $other = $this->newUserId();
        $ticket = $this->makeTicket($owner, 'pending');

        $resp = $this->close($other, $this->encodeId((int) $ticket->id), ['rating' => '4']);

        $this->assertSame(404, $resp['code']);
        $this->assertSame('pending', (string) Ticket::find($ticket->id)->status, '非本人不得关闭');
    }

    #[Test] public function close_duplicate_with_rating_returns_422(): void
    {
        $userId = $this->newUserId();
        $ticket = $this->makeTicket($userId, 'pending');

        $first = $this->close($userId, $this->encodeId((int) $ticket->id), ['rating' => '3']);
        $this->assertSame(0, $first['code'], json_encode($first));

        $second = $this->close($userId, $this->encodeId((int) $ticket->id), ['rating' => '5']);
        $this->assertSame(422, $second['code']);

        $row = Ticket::find($ticket->id);
        $this->assertSame(3, (int) $row->rating, '重复关闭不得覆盖原评分');
    }
}
