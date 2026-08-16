<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

/**
 * 演示数据种子运行器
 *
 * 从 docs/install.sql 中提取演示数据段落并按顺序执行。
 * 使用方法: php seed.php
 *
 * 支持环境变量或 .env 文件配置数据库连接。
 */

require_once __DIR__ . '/support/bootstrap.php';

use support\Db;

/**
 * 获取配置文件路径
 */
function getConfigPath(string $file): string
{
    return __DIR__ . '/config/' . $file;
}

/**
 * 从 install.sql 中提取指定文件名标记的段落（不含标记行）
 */
function extractSection(string $sql, string $marker): string
{
    $start = strpos($sql, "-- [{$marker}]");
    if ($start === false) {
        return '';
    }
    $end = strpos($sql, "\n-- [", $start + strlen("-- [{$marker}]"));
    $section = $end === false ? substr($sql, $start) : substr($sql, $start, $end - $start);
    return preg_replace('/^\s*-- \[' . preg_quote($marker, '/') . '\]\s*$/m', '', $section);
}

/**
 * 解析 SQL 文本中的分号分隔语句并逐条执行
 */
function executeSqlText(string $sql, string $label): array
{
    if (empty(trim($sql))) {
        return ['error' => "SQL 文本为空: {$label}"];
    }

    // 移除注释
    $sql = preg_replace('/--.*$/m', '', $sql);

    // 按分号拆分语句（排除字符串中的分号）
    $statements = [];
    $current = '';
    $inString = false;
    $quoteChar = '';

    foreach (str_split($sql) as $char) {
        if ($inString) {
            $current .= $char;
            if ($char === $quoteChar) {
                $inString = false;
            }
            continue;
        }

        if ($char === "'" || $char === '"') {
            $inString = true;
            $quoteChar = $char;
            $current .= $char;
            continue;
        }

        if ($char === ';') {
            $stmt = trim($current);
            if (!empty($stmt)) {
                $statements[] = $stmt;
            }
            $current = '';
            continue;
        }

        $current .= $char;
    }

    // 最后一个语句（没有分号结尾的情况）
    $last = trim($current);
    if (!empty($last)) {
        $statements[] = $last;
    }

    $succeeded = 0;
    $failed = 0;
    $errors = [];

    foreach ($statements as $i => $stmt) {
        // 跳过纯注释行
        if (preg_match('/^\s*(--|#|\/\*)/', $stmt)) {
            continue;
        }

        try {
            Db::unprepared($stmt);
            $succeeded++;
        } catch (\Throwable $e) {
            $failed++;
            // INSERT IGNORE 的重复键不算错误（1062）
            $errorMsg = $e->getMessage();
            if (str_contains($errorMsg, 'Duplicate entry') || str_contains($errorMsg, 'already exists')) {
                $succeeded++;
                $failed--;
            } else {
                $errors[] = "语句 #{$i}: {$errorMsg}";
            }
        }
    }

    return [
        'file'      => $label,
        'total'     => $succeeded + $failed,
        'succeeded' => $succeeded,
        'failed'    => $failed,
        'errors'    => $errors,
    ];
}

// ============================================================
// 主流程
// ============================================================
echo "========================================\n";
echo "  预约服务系统 - 演示数据种子运行器\n";
echo "========================================\n\n";

// 测试数据库连接
try {
    Db::connection()->getPdo();
    echo "[OK] 数据库连接成功\n\n";
} catch (\Throwable $e) {
    echo "[ERROR] 数据库连接失败: " . $e->getMessage() . "\n";
    echo "请检查 .env 文件中的数据库配置。\n";
    exit(1);
}

// 从统一安装脚本中提取演示数据段落
$installSql = file_get_contents(__DIR__ . '/../docs/install.sql');
if ($installSql === false) {
    echo "[ERROR] 无法读取 docs/install.sql\n";
    exit(1);
}

$seedSections = [
    'demo_seeds' => extractSection($installSql, '2026_05_27_000006_demo_seeds.sql'),
];

$totalSucceeded = 0;
$totalFailed = 0;

foreach ($seedSections as $label => $section) {
    echo "执行: {$label}\n";
    $result = executeSqlText($section, $label);

    if (isset($result['error'])) {
        echo "  [SKIP] {$result['error']}\n";
        continue;
    }

    echo "  语句数: {$result['total']}, 成功: {$result['succeeded']}, 失败: {$result['failed']}\n";

    foreach ($result['errors'] as $error) {
        echo "  [WARN] {$error}\n";
    }

    $totalSucceeded += $result['succeeded'];
    $totalFailed += $result['failed'];

    echo "\n";
}

echo "========================================\n";
echo "  完成: 成功 {$totalSucceeded} 条, 失败 {$totalFailed} 条\n";
echo "========================================\n";

exit($totalFailed > 0 ? 1 : 0);
