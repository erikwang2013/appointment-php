<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\process;

use Erikwang2013\Jwt\JWT;
use Workerman\Worker;
use Workerman\Connection\TcpConnection;

/**
 * WebSocket 实时推送进程
 *
 * 在 config/process.php 中注册为独立进程，监听独立的 WebSocket 端口。
 * 客户端连接后发送 auth 消息进行 JWT 认证，认证通过后绑定 user_id，
 * 后续可通过 PushService 向指定用户推送实时消息。
 */
class WebSocket
{
    /**
     * Worker 启动回调
     */
    public function onWorkerStart(Worker $worker): void
    {
        echo "WebSocket process started on {$worker->getSocketName()}\n";
    }

    /**
     * 客户端连接回调
     */
    public function onConnect(TcpConnection $conn): void
    {
        $conn->user_id = 0;
        $conn->max_send_buffer_size = 1024 * 1024; // 1MB
    }

    /**
     * 接收消息回调
     *
     * 支持的消息类型:
     * - auth: JWT 认证绑定用户
     * - ping: 心跳检测
     */
    public function onMessage(TcpConnection $conn, string $data): void
    {
        $msg = json_decode($data, true);
        if (!is_array($msg)) {
            return;
        }

        $type = $msg['type'] ?? '';

        if ($type === 'auth') {
            $token = $msg['token'] ?? '';
            if (empty($token)) {
                $conn->send(json_encode(['type' => 'auth_error', 'message' => '缺少认证令牌']));
                return;
            }

            try {
                $payload = JWT::decode($token);
                $userId = $payload['user_id'] ?? 0;

                if (empty($userId)) {
                    $conn->send(json_encode(['type' => 'auth_error', 'message' => '令牌无效']));
                    return;
                }

                $conn->user_id = $userId;
                \app\common\PushService::register($userId, $conn);

                $conn->send(json_encode(['type' => 'auth_ok', 'message' => '认证成功']));
            } catch (\Throwable $e) {
                $conn->send(json_encode(['type' => 'auth_error', 'message' => '认证失败: ' . $e->getMessage()]));
            }

            return;
        }

        if ($type === 'ping') {
            $conn->send(json_encode(['type' => 'pong', 'time' => time()]));
            return;
        }
    }

    /**
     * 客户端断开连接回调
     */
    public function onClose(TcpConnection $conn): void
    {
        if (!empty($conn->user_id)) {
            \app\common\PushService::unregister($conn->user_id, $conn);
        }
    }
}
