<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use support\Log;
use support\Request;
use support\Response;
use Illuminate\Support\Facades\Redis;

/**
 * IP 黑名单管理控制器
 * 基于 Redis 的安全IP封锁管理（配合 erikwang2013/security-php）
 */
class IpBlacklistController extends BaseController
{
    /**
     * IP 黑名单列表
     * 读取 Redis 中 security:blocked_ips:* 模式的键
     */
    public function index(Request $request): Response
    {
        $page  = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 20);
        $ipSearch = $request->input('ip', '');

        $blockedIps = [];
        try {
            $keys = Redis::keys('security:blocked_ips:*');
            foreach ($keys as $key) {
                $ip = str_replace('security:blocked_ips:', '', $key);
                $data = Redis::get($key);

                if ($data) {
                    $decoded = json_decode($data, true);
                    $blockedIps[] = [
                        'ip'          => $ip,
                        'reason'      => $decoded['reason'] ?? '安全规则封锁',
                        'blocked_at'  => $decoded['blocked_at'] ?? date('Y-m-d H:i:s'),
                        'attack_count' => (int) ($decoded['attack_count'] ?? 0),
                        'expires_at'  => $decoded['expires_at'] ?? '永久',
                        'attack_type' => $decoded['attack_type'] ?? '',
                        'duration_min' => (int) ($decoded['duration_min'] ?? -1),
                    ];
                } else {
                    $blockedIps[] = [
                        'ip'          => $ip,
                        'reason'      => '未知',
                        'blocked_at'  => '',
                        'attack_count' => 0,
                        'expires_at'  => '',
                        'attack_type' => '',
                        'duration_min' => -1,
                    ];
                }
            }
        } catch (\Throwable $e) {
            // M3: 内部异常详情仅记日志，对外返回通用文案
            Log::error('[IpBlacklistController] read blacklist failed: ' . $e->getMessage());
            return $this->fail('无法读取黑名单数据，请稍后重试', 500);
        }

        // 按封锁时间降序
        usort($blockedIps, fn($a, $b) => strcmp($b['blocked_at'], $a['blocked_at']));

        // IP 搜索过滤
        if ($ipSearch) {
            $blockedIps = array_filter($blockedIps, function ($item) use ($ipSearch) {
                return str_contains($item['ip'], $ipSearch);
            });
        }
        $blockedIps = array_values($blockedIps);

        // 分页
        $total = count($blockedIps);
        $list = array_slice($blockedIps, ($page - 1) * $limit, $limit);

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 手动封锁 IP
     * @Apidoc\Param("ip", type="string", require=true, desc="要封锁的IP地址")
     * @Apidoc\Param("reason", type="string", require=false, desc="封锁原因")
     * @Apidoc\Param("duration", type="int", require=false, desc="封锁时长(分钟), -1=永久", default="60")
     */
    public function block(Request $request): Response
    {
        $ip       = $request->input('ip', '');
        $reason   = $request->input('reason', '管理员手动封锁');
        $duration = (int) $request->input('duration', -1); // -1 永久

        if (empty($ip)) {
            return $this->fail('IP 地址不能为空', 422);
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return $this->fail('无效的 IP 地址格式', 422);
        }

        $blockData = [
            'ip'          => $ip,
            'reason'      => $reason,
            'blocked_at'  => date('Y-m-d H:i:s'),
            'expires_at'  => $duration > 0 ? date('Y-m-d H:i:s', time() + $duration * 60) : '永久',
            'duration_min' => $duration,
            'attack_count' => 1,
            'attack_type'  => 'manual',
        ];

        try {
            Redis::set('security:blocked_ips:' . $ip, json_encode($blockData, JSON_UNESCAPED_UNICODE));

            // 如果有过期时间且非永久
            if ($duration > 0) {
                Redis::expire('security:blocked_ips:' . $ip, $duration * 60);
            }

            return $this->success($blockData, "IP {$ip} 已加入黑名单");
        } catch (\Throwable $e) {
            // M3: 内部异常详情仅记日志，对外返回通用文案
            Log::error('[IpBlacklistController] block failed: ' . $e->getMessage());
            return $this->fail('封锁失败，请稍后重试', 500);
        }
    }

    /**
     * 解除 IP 封锁
     */
    public function unblock(Request $request, string $ip): Response
    {
        $ip = urldecode($ip);

        if (empty($ip)) {
            return $this->fail('IP 地址不能为空', 422);
        }

        try {
            $key = 'security:blocked_ips:' . $ip;
            $exists = Redis::exists($key);

            if (!$exists) {
                return $this->fail("IP {$ip} 不在黑名单中", 404);
            }

            Redis::del($key);

            return $this->success([], "IP {$ip} 已从黑名单移除");
        } catch (\Throwable $e) {
            // M3: 内部异常详情仅记日志，对外返回通用文案
            Log::error('[IpBlacklistController] unblock failed: ' . $e->getMessage());
            return $this->fail('解封失败，请稍后重试', 500);
        }
    }

    /**
     * 近期攻击日志
     * 从安全日志中读取最近的攻击记录
     */
    public function attacks(Request $request): Response
    {
        $page  = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 20);

        $attackLogs = [];

        try {
            // 读取 security:attack_log:* 模式的日志
            $keys = Redis::keys('security:attack_log:*');

            if (!empty($keys)) {
                foreach ($keys as $key) {
                    $data = Redis::get($key);
                    if ($data) {
                        $decoded = json_decode($data, true);
                        if ($decoded) {
                            $attackLogs[] = [
                                'ip'         => $decoded['ip'] ?? '',
                                'type'       => $decoded['type'] ?? '',
                                'path'       => $decoded['path'] ?? '',
                                'timestamp'  => $decoded['timestamp'] ?? $decoded['created_at'] ?? '',
                                'user_agent' => $decoded['user_agent'] ?? '',
                                'details'    => $decoded['details'] ?? '',
                            ];
                        }
                    }
                }
            }

            // 也尝试从 Redis 的 list 类型读取
            $listKeys = Redis::keys('security:attack_list:*');
            foreach ($listKeys as $listKey) {
                $items = Redis::lRange($listKey, 0, 99);
                foreach ($items as $item) {
                    $decoded = json_decode($item, true);
                    if ($decoded) {
                        $attackLogs[] = [
                            'ip'         => $decoded['ip'] ?? '',
                            'type'       => $decoded['type'] ?? '',
                            'path'       => $decoded['path'] ?? '',
                            'timestamp'  => $decoded['timestamp'] ?? $decoded['created_at'] ?? '',
                            'user_agent' => $decoded['user_agent'] ?? '',
                            'details'    => $decoded['details'] ?? '',
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            // 安全日志不可用时返回空
        }

        // 按时间倒序
        usort($attackLogs, fn($a, $b) => strcmp($b['timestamp'], $a['timestamp']));

        $total = count($attackLogs);
        $list = array_slice($attackLogs, ($page - 1) * $limit, $limit);

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }
}
