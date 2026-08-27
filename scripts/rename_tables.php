<?php
// 表前缀迁移脚本: erik_* → appointment_*
// 用法: php rename_tables.php            # dry-run, 只打印 SQL
//       php rename_tables.php --execute  # 真正执行重命名
declare(strict_types=1);

// --- 读取 service/.env (简单 KEY=VALUE 解析) ---
function loadEnv(string $file): array
{
    $env = [];
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $env[trim($key)] = trim($value);
    }
    return $env;
}

$envFile = dirname(__DIR__) . '/service/.env';
if (!is_file($envFile)) {
    fwrite(STDERR, "找不到 .env: $envFile\n");
    exit(1);
}
$env = loadEnv($envFile);

// getenv 优先(与 service/config/database.php 一致), 便于环境变量覆盖(空值也算覆盖)
function envVal(string $key, string $fallback): string
{
    $v = getenv($key);
    return $v === false ? $fallback : $v;
}

$host = envVal('DB_HOST', $env['DB_HOST'] ?? '127.0.0.1');
$port = (int)envVal('DB_PORT', $env['DB_PORT'] ?? 3306);
$db   = envVal('DB_DATABASE', $env['DB_DATABASE'] ?? 'appointment');
$user = envVal('DB_USERNAME', $env['DB_USERNAME'] ?? 'root');
$pass = envVal('DB_PASSWORD', $env['DB_PASSWORD'] ?? '');

// --- 连接 ---
try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    fwrite(STDERR, "连接失败: {$e->getMessage()}\n");
    exit(1);
}

$execute = in_array('--execute', $argv, true);

// --- 查询 erik_ 表 ---
$stmt = $pdo->prepare(
    "SELECT table_name FROM information_schema.tables
     WHERE table_schema = :db AND table_name LIKE 'erik\_%' ORDER BY table_name"
);
$stmt->execute([':db' => $db]);
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (count($tables) === 0) {
    echo "数据库 '$db' 中没有 erik_ 前缀的表。\n";
    exit(0);
}

// --- 生成并执行/预览重命名 ---
$renamed = 0;
foreach ($tables as $old) {
    $new = 'appointment_' . substr($old, strlen('erik_'));
    $sql = "ALTER TABLE `$old` RENAME TO `$new`";
    if ($execute) {
        $pdo->exec($sql);
        echo "已重命名: $old → $new\n";
        $renamed++;
    } else {
        echo "[dry-run] $sql\n";
    }
}

echo $execute
    ? "完成: 共重命名 {$renamed} 张表\n"
    : "[dry-run] 共检测到 " . count($tables) . " 张 erik_ 表, 加 --execute 执行重命名\n";
