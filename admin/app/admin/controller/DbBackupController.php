<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use support\Request;
use support\Response;

/**
 * 数据库备份管理控制器
 * 管理 mysqldump 备份文件的创建、下载、恢复与删除
 */
class DbBackupController extends BaseController
{
    /**
     * 备份文件存放目录
     */
    private string $backupDir;

    public function __construct()
    {
        $this->backupDir = base_path() . '/database/backup/';
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    /**
     * 备份文件列表
     * 返回: filename, size_bytes, size_human, created_at
     */
    public function index(Request $request): Response
    {
        $page  = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 20);

        $files = [];
        $glob = glob($this->backupDir . '*.{sql,gz,sql.gz,zip}', GLOB_BRACE);

        if ($glob === false) {
            $glob = [];
        }

        foreach ($glob as $file) {
            $files[] = [
                'filename'    => basename($file),
                'size_bytes'  => filesize($file),
                'size_human'  => $this->formatBytes(filesize($file)),
                'created_at'  => date('Y-m-d H:i:s', filemtime($file)),
                'modified_at' => date('Y-m-d H:i:s', filemtime($file)),
            ];
        }

        // 按创建时间倒序
        usort($files, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

        $total = count($files);
        $list = array_slice($files, ($page - 1) * $limit, $limit);

        return $this->success([
            'list'  => array_values($list),
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 创建数据库备份
     * 执行 mysqldump + gzip，保存到 database/backup/
     */
    public function create(Request $request): Response
    {

        $dbConfig = config('database.connections.mysql', []);
        $host     = $dbConfig['host'] ?? '127.0.0.1';
        $port     = $dbConfig['port'] ?? '3306';
        $database = $dbConfig['database'] ?? 'appointment';
        $username = $dbConfig['username'] ?? 'root';
        $password = $dbConfig['password'] ?? '';

        $timestamp = date('YmdHis');
        $filename  = "db-backup-{$timestamp}.sql.gz";
        $filepath  = $this->backupDir . $filename;

        // 构建 mysqldump 命令
        $passwordArg = $password ? "-p'" . escapeshellarg($password) . "'" : '';
        $cmd = sprintf(
            'mysqldump -h%s -P%s -u%s %s %s 2>&1 | gzip > %s',
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($username),
            $passwordArg,
            escapeshellarg($database),
            escapeshellarg($filepath)
        );

        $output = [];
        $exitCode = 0;
        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || !file_exists($filepath)) {
            $errorMsg = implode("\n", $output);
            return $this->fail("备份失败 (exit code: {$exitCode}): " . ($errorMsg ?: '未知错误'), 500);
        }

        $size = filesize($filepath);

        return $this->success([
            'filename'    => basename($filepath),
            'size_bytes'  => $size,
            'size_human'  => $this->formatBytes($size),
            'created_at'  => date('Y-m-d H:i:s'),
        ], '备份创建成功');
    }

    /**
     * 下载备份文件
     */
    public function download(Request $request, string $filename): Response
    {
        $filepath = $this->backupDir . basename($filename);

        if (!file_exists($filepath)) {
            return $this->fail('备份文件不存在', 404);
        }

        return response()->download($filepath, basename($filename));
    }

    /**
     * 从备份恢复数据库（危险操作）
     * 需要密码验证 + Poster 验证
     */
    public function restore(Request $request, string $filename): Response
    {

        $filename = basename($filename);
        $filepath = $this->backupDir . $filename;

        if (!file_exists($filepath)) {
            return $this->fail('备份文件不存在', 404);
        }

        // 二次密码确认
        $adminId = $request->adminId ?? 0;
        $error = $this->confirmPassword($adminId, $request->input('password', ''), $request);
        if ($error !== null) {
            return $this->fail($error, 422);
        }

        $dbConfig = config('database.connections.mysql', []);
        $host     = $dbConfig['host'] ?? '127.0.0.1';
        $port     = $dbConfig['port'] ?? '3306';
        $database = $dbConfig['database'] ?? 'appointment';
        $username = $dbConfig['username'] ?? 'root';
        $password = $dbConfig['password'] ?? '';

        $passwordArg = $password ? "-p'" . escapeshellarg($password) . "'" : '';

        // 判断是否为 .gz 文件
        if (str_ends_with($filename, '.gz')) {
            $cmd = sprintf(
                'gunzip < %s | mysql -h%s -P%s -u%s %s %s 2>&1',
                escapeshellarg($filepath),
                escapeshellarg($host),
                escapeshellarg((string) $port),
                escapeshellarg($username),
                $passwordArg,
                escapeshellarg($database)
            );
        } else {
            $cmd = sprintf(
                'mysql -h%s -P%s -u%s %s %s < %s 2>&1',
                escapeshellarg($host),
                escapeshellarg((string) $port),
                escapeshellarg($username),
                $passwordArg,
                escapeshellarg($database),
                escapeshellarg($filepath)
            );
        }

        $output = [];
        $exitCode = 0;
        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0) {
            $errorMsg = implode("\n", $output);
            return $this->fail("恢复失败 (exit code: {$exitCode}): " . ($errorMsg ?: '未知错误'), 500);
        }

        return $this->success([
            'filename' => $filename,
            'restored_at' => date('Y-m-d H:i:s'),
        ], '数据库恢复成功');
    }

    /**
     * 删除旧备份
     */
    public function destroy(Request $request, string $filename): Response
    {

        $filename = basename($filename);
        $filepath = $this->backupDir . $filename;

        if (!file_exists($filepath)) {
            return $this->fail('备份文件不存在', 404);
        }

        if (!unlink($filepath)) {
            return $this->fail('删除备份文件失败', 500);
        }

        return $this->success([], "备份 {$filename} 已删除");
    }

    /**
     * 格式化字节为人类可读格式
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $level = (int) floor(log($bytes, 1024));

        if ($level >= count($units)) {
            $level = count($units) - 1;
        }

        return round($bytes / (1024 ** $level), $precision) . ' ' . $units[$level];
    }
}
