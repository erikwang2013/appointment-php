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
use Illuminate\Support\Facades\DB;

/**
 * 系统监控面板控制器
 * 提供 CPU、内存、磁盘、Redis、MySQL、队列等实时健康数据
 */
class SystemMonitorController extends BaseController
{
    /**
     * 系统健康概览
     * 返回: CPU / 内存 / 磁盘 / Redis / MySQL / 队列 / 慢查询
     */
    public function index(Request $request): Response
    {
        return $this->success([
            'cpu'           => $this->getCpuInfo(),
            'memory'        => $this->getMemoryInfo(),
            'disk'          => $this->getDiskInfo(),
            'redis'         => $this->getRedisInfo(),
            'mysql'         => $this->getMysqlInfo(),
            'queues'        => $this->getQueueInfo(),
            'slow_queries'  => $this->getSlowQueryCount(),
            'server_time'   => date('Y-m-d H:i:s'),
            'php_version'   => PHP_VERSION,
            'uptime'        => $this->getSystemUptime(),
        ]);
    }

    /**
     * Webman 进程列表
     */
    public function processes(Request $request): Response
    {
        $processes = [];
        $statusFilePath = runtime_path() . '/webman.status';
        if (file_exists($statusFilePath)) {
            $content = file_get_contents($statusFilePath);
            if ($content !== false) {
                $lines = explode("\n", trim($content));
                foreach ($lines as $line) {
                    $parts = preg_split('/\s+/', trim($line));
                    if (count($parts) >= 4) {
                        $processes[] = [
                            'pid'   => $parts[0] ?? '',
                            'name'  => $parts[1] ?? '',
                            'listen' => $parts[2] ?? '',
                            'status' => $parts[3] ?? '',
                            'memory' => $parts[4] ?? '',
                        ];
                    }
                }
            }
        }

        return $this->success([
            'processes' => $processes,
            'total'     => count($processes),
        ]);
    }

    /**
     * 清除 Redis 缓存
     */
    public function clearCache(Request $request): Response
    {

        $prefix = $request->input('prefix', '');
        $deletedCount = 0;

        try {
            if ($prefix) {
                $keys = Redis::keys($prefix . '*');
                foreach ($keys as $key) {
                    Redis::del($key);
                    $deletedCount++;
                }
            } else {
                // 只清除应用缓存前缀，不清除安全/会话相关
                $patterns = [
                    'erik:cache:*',
                    'erik:dashboard:*',
                    'erik:query:*',
                ];
                foreach ($patterns as $pattern) {
                    $keys = Redis::keys($pattern);
                    foreach ($keys as $key) {
                        Redis::del($key);
                        $deletedCount++;
                    }
                }
            }

            return $this->success([
                'deleted_keys' => $deletedCount,
                'prefix'       => $prefix ?: 'erik:cache:/erik:dashboard:/erik:query:',
            ], "已清除 {$deletedCount} 个缓存键");
        } catch (\Throwable $e) {
            // M3: 内部异常详情仅记日志，对外返回通用文案
            Log::error('[SystemMonitorController] clear cache failed: ' . $e->getMessage());
            return $this->fail('清除缓存失败，请稍后重试', 500);
        }
    }

    // ── 内部数据采集方法 ──

    /**
     * CPU 使用率
     */
    private function getCpuInfo(): array
    {
        $load = sys_getloadavg();
        $cores = $this->getCpuCores();

        return [
            'load_1min'  => round($load[0], 2),
            'load_5min'  => round($load[1], 2),
            'load_15min' => round($load[2], 2),
            'cores'      => $cores,
            'usage_pct'  => $cores > 0 ? round(($load[0] / $cores) * 100, 1) : 0,
        ];
    }

    /**
     * CPU 核心数
     */
    private function getCpuCores(): int
    {
        if (function_exists('swoole_cpu_num')) {
            return swoole_cpu_num();
        }

        $cpuInfo = @file_get_contents('/proc/cpuinfo');
        if ($cpuInfo !== false) {
            $matches = [];
            preg_match_all('/^processor/m', $cpuInfo, $matches);
            return count($matches[0] ?? []) ?: 1;
        }

        return 1;
    }

    /**
     * 内存信息
     */
    private function getMemoryInfo(): array
    {
        $peak = memory_get_peak_usage(true);
        $current = memory_get_usage(true);
        $limit = $this->getMemoryLimit();

        return [
            'current_bytes'  => $current,
            'current_mb'     => round($current / 1048576, 2),
            'peak_bytes'     => $peak,
            'peak_mb'        => round($peak / 1048576, 2),
            'limit_bytes'    => $limit,
            'limit_mb'       => round($limit / 1048576, 2),
            'usage_pct'      => $limit > 0 ? round(($current / $limit) * 100, 1) : 0,
        ];
    }

    /**
     * 获取 PHP memory_limit（字节）
     */
    private function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        if (empty($limit) || $limit === '-1') {
            return 0; // 无限制
        }

        $unit = strtoupper(substr($limit, -1));
        $value = (int) substr($limit, 0, -1);

        return match ($unit) {
            'G'     => $value * 1073741824,
            'M'     => $value * 1048576,
            'K'     => $value * 1024,
            default => (int) $limit,
        };
    }

    /**
     * 磁盘信息
     */
    private function getDiskInfo(): array
    {
        $dir = runtime_path();

        $free = @disk_free_space($dir);
        $total = @disk_total_space($dir);

        if ($free === false || $total === false) {
            return [
                'free_mb'   => 0,
                'total_mb'  => 0,
                'used_mb'   => 0,
                'usage_pct' => 0,
            ];
        }

        return [
            'free_mb'   => round($free / 1048576, 2),
            'total_mb'  => round($total / 1048576, 2),
            'used_mb'   => round(($total - $free) / 1048576, 2),
            'usage_pct' => $total > 0 ? round((($total - $free) / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Redis 运行信息
     */
    private function getRedisInfo(): array
    {
        try {
            $info = Redis::info();

            return [
                'connected_clients' => (int) ($info['connected_clients'] ?? 0),
                'used_memory_human' => $info['used_memory_human'] ?? '0',
                'used_memory_bytes' => (int) ($info['used_memory'] ?? 0),
                'uptime_seconds'    => (int) ($info['uptime_in_seconds'] ?? 0),
                'uptime_days'       => round(((int) ($info['uptime_in_seconds'] ?? 0)) / 86400, 1),
                'redis_version'     => $info['redis_version'] ?? '',
                'keyspace_hits'     => (int) ($info['keyspace_hits'] ?? 0),
                'keyspace_misses'   => (int) ($info['keyspace_misses'] ?? 0),
                'hit_rate'          => $this->calcHitRate(
                    (int) ($info['keyspace_hits'] ?? 0),
                    (int) ($info['keyspace_misses'] ?? 0)
                ),
            ];
        } catch (\Throwable $e) {
            return [
                'status'  => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * MySQL 运行信息
     */
    private function getMysqlInfo(): array
    {
        try {
            $status = DB::select('SHOW STATUS WHERE Variable_name IN (?, ?, ?, ?, ?, ?, ?, ?)', [
                'Threads_connected',
                'Threads_running',
                'Uptime',
                'Questions',
                'Slow_queries',
                'Innodb_buffer_pool_read_requests',
                'Innodb_buffer_pool_reads',
                'Innodb_buffer_pool_hit_rate',
            ]);

            $data = [];
            foreach ($status as $row) {
                $key = strtolower($row->Variable_name);
                $data[$key] = $row->Value;
            }

            $questions = (int) ($data['questions'] ?? 0);
            $uptime = (int) ($data['uptime'] ?? 1);
            $qps = $uptime > 0 ? round($questions / $uptime, 2) : 0;

            $poolReads = (int) ($data['innodb_buffer_pool_reads'] ?? 0);
            $poolRequests = (int) ($data['innodb_buffer_pool_read_requests'] ?? 1);
            $poolHitRate = $poolRequests > 0
                ? round((1 - $poolReads / $poolRequests) * 100, 1)
                : 100;

            return [
                'threads_connected' => (int) ($data['threads_connected'] ?? 0),
                'threads_running'   => (int) ($data['threads_running'] ?? 0),
                'uptime_seconds'    => $uptime,
                'uptime_days'       => round($uptime / 86400, 1),
                'questions'         => $questions,
                'qps'               => $qps,
                'slow_queries'      => (int) ($data['slow_queries'] ?? 0),
                'buffer_pool_hit'   => $poolHitRate,
            ];
        } catch (\Throwable $e) {
            return [
                'status'  => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Redis 队列大小
     */
    private function getQueueInfo(): array
    {
        try {
            $queuePatterns = [
                'webman:queue:default'      => '默认队列',
                'webman:queue:notifications' => '通知队列',
                'webman:queue:exports'       => '导出队列',
                'erik:queue:pending'         => '待处理队列',
                'erik:queue:failed'          => '失败队列',
            ];

            $queues = [];
            foreach ($queuePatterns as $pattern => $label) {
                // 尝试 LLEN（list 类型队列）
                try {
                    $len = Redis::lLen($pattern);
                } catch (\Throwable) {
                    $len = 0;
                }

                // 也尝试 ZCARD（sorted set 类型，用于延迟队列）
                try {
                    $zcard = Redis::zCard($pattern . ':delayed');
                } catch (\Throwable) {
                    $zcard = 0;
                }

                $queues[] = [
                    'name'    => $pattern,
                    'label'   => $label,
                    'pending' => $len,
                    'delayed' => $zcard,
                    'total'   => $len + $zcard,
                ];
            }

            return $queues;
        } catch (\Throwable $e) {
            return [
                ['name' => 'error', 'label' => '错误', 'pending' => 0, 'delayed' => 0, 'total' => 0, 'error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Redis 慢查询计数
     */
    private function getSlowQueryCount(): int
    {
        try {
            return (int) Redis::get('appointment:slow_queries:count') ?: 0;
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * 系统启动时间
     */
    private function getSystemUptime(): string
    {
        $uptimeFile = '/proc/uptime';
        if (file_exists($uptimeFile)) {
            $content = @file_get_contents($uptimeFile);
            if ($content !== false) {
                $parts = explode(' ', trim($content));
                $seconds = (float) ($parts[0] ?? 0);
                $days = floor($seconds / 86400);
                $hours = floor(($seconds % 86400) / 3600);
                $minutes = floor(($seconds % 3600) / 60);
                return "{$days}d {$hours}h {$minutes}m";
            }
        }

        return '未知';
    }

    /**
     * 计算缓存命中率
     */
    private function calcHitRate(int $hits, int $misses): string
    {
        $total = $hits + $misses;
        if ($total === 0) {
            return '0%';
        }
        return round(($hits / $total) * 100, 1) . '%';
    }
}
