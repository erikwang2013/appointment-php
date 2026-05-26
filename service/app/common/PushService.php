<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\common;

use Workerman\Connection\TcpConnection;

/**
 * WebSocket 消息推送服务
 *
 * 维护 WebSocket 连接池（userId => connections[]），
 * 对外提供向指定用户、技师推送实时消息的能力。
 *
 * 注意: 此服务仅在 WebSocket 进程中被 WebSocket::onMessage/onClose 调用
 * register/unregister 方法。其他业务进程（HTTP 进程）调用 send* 方法时，
 * 无法直接操作跨进程的连接池。生产环境建议使用 Redis Pub/Sub 或
 * webman Channel 组件实现跨进程消息转发。
 *
 * 当前实现作为单进程/开发环境的直接推送方案，或配合 Channel 组件使用。
 */
class PushService
{
    /**
     * 用户连接池
     * userId(int) => [TcpConnection, TcpConnection, ...]
     * @var array<int, array<int, TcpConnection>>
     */
    private static array $connections = [];

    /**
     * 注册 WebSocket 连接
     *
     * @param int $userId 用户ID
     * @param TcpConnection $conn WebSocket 连接
     */
    public static function register(int $userId, TcpConnection $conn): void
    {
        if ($userId <= 0) {
            return;
        }

        // 同一连接不重复注册
        if (!isset(self::$connections[$userId])) {
            self::$connections[$userId] = [];
        }

        foreach (self::$connections[$userId] as $existing) {
            if ($existing === $conn) {
                return;
            }
        }

        self::$connections[$userId][] = $conn;
    }

    /**
     * 取消注册 WebSocket 连接
     *
     * @param int $userId 用户ID
     * @param TcpConnection $conn WebSocket 连接
     */
    public static function unregister(int $userId, TcpConnection $conn): void
    {
        if ($userId <= 0 || !isset(self::$connections[$userId])) {
            return;
        }

        self::$connections[$userId] = array_values(
            array_filter(self::$connections[$userId], fn($c) => $c !== $conn)
        );

        if (empty(self::$connections[$userId])) {
            unset(self::$connections[$userId]);
        }
    }

    /**
     * 向指定用户推送消息
     *
     * @param int $userId 目标用户ID
     * @param array $data 推送数据
     * @return int 成功发送的连接数
     */
    public static function sendToUser(int $userId, array $data): int
    {
        if (!isset(self::$connections[$userId])) {
            return 0;
        }

        $payload = json_encode($data, JSON_UNESCAPED_UNICODE);
        $sent = 0;

        foreach (self::$connections[$userId] as $conn) {
            try {
                $conn->send($payload);
                $sent++;
            } catch (\Throwable $e) {
                // 连接可能已断开，忽略错误
            }
        }

        return $sent;
    }

    /**
     * 推送订单状态更新
     *
     * 同时通知订单所属用户和技师
     *
     * @param int $userId 下单用户ID
     * @param int $technicianId 技师用户ID（可为0）
     * @param string $orderId 订单ID
     * @param string $orderNo 订单编号
     * @param string $status 新状态
     * @param array $extra 额外数据
     * @return int 成功发送的连接数
     */
    public static function sendOrderUpdate(
        int $userId,
        int $technicianId,
        string $orderId,
        string $orderNo,
        string $status,
        array $extra = []
    ): int {
        $data = array_merge([
            'type'       => 'order_update',
            'order_id'   => $orderId,
            'order_no'   => $orderNo,
            'status'     => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ], $extra);

        $sent = 0;
        $sent += self::sendToUser($userId, $data);

        if ($technicianId > 0) {
            $sent += self::sendToUser($technicianId, $data);
        }

        return $sent;
    }

    /**
     * 通知所有收藏了某技师在线的用户
     *
     * @param int $technicianId 技师用户ID
     * @param string $technicianName 技师名称
     * @return int 成功发送的连接数
     */
    public static function sendTechnicianOnline(int $technicianId, string $technicianName = ''): int
    {
        $data = [
            'type'            => 'technician_online',
            'technician_id'   => $technicianId,
            'technician_name' => $technicianName,
            'online_at'       => date('Y-m-d H:i:s'),
        ];

        // 查询收藏了该技师的所有用户（跨进程限制：依赖 HTTP 进程已加载的数据）
        $sent = 0;
        try {
            $favoriteUserIds = \app\model\UserFavorite::where('target_type', 'technician')
                ->where('target_id', $technicianId)
                ->pluck('user_id')
                ->toArray();

            foreach ($favoriteUserIds as $uid) {
                $sent += self::sendToUser((int)$uid, $data);
            }
        } catch (\Throwable $e) {
            // 非 WebSocket 进程可能无法访问数据库，忽略
        }

        return $sent;
    }

    /**
     * 批量广播消息
     *
     * @param array $data 推送数据
     * @param array<int> $userIds 目标用户ID列表，为空则广播给所有已连接用户
     * @return int 成功发送的连接数
     */
    public static function broadcast(array $data, array $userIds = []): int
    {
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE);
        $sent = 0;

        $targets = empty($userIds)
            ? array_keys(self::$connections)
            : $userIds;

        foreach ($targets as $userId) {
            $userId = (int)$userId;
            if (!isset(self::$connections[$userId])) {
                continue;
            }

            foreach (self::$connections[$userId] as $conn) {
                try {
                    $conn->send($payload);
                    $sent++;
                } catch (\Throwable $e) {
                    // 忽略已断开的连接
                }
            }
        }

        return $sent;
    }

    /**
     * 获取当前在线用户数
     *
     * @return int
     */
    public static function getOnlineCount(): int
    {
        return count(self::$connections);
    }

    /**
     * 获取当前连接总数
     *
     * @return int
     */
    public static function getConnectionCount(): int
    {
        $count = 0;
        foreach (self::$connections as $conns) {
            $count += count($conns);
        }
        return $count;
    }
}
