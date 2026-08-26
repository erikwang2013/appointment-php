<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use support\Request;
use support\Response;
use support\Db;

/**
 * 一键安装向导
 * 访问 /install 进入安装向导
 */
class InstallController
{
    public function index(Request $request): Response
    {
        // C2: 已安装的系统禁止再次进入安装向导，防止重置超级管理员。
        // 文件锁（.install.lock）优先——向导可被指向任意数据库，DB 内 installed 标记可被绕过；
        // 文件锁与 isInstalled() 数据库标记双保险，任一命中即拒绝。
        if (file_exists(base_path() . '/.install.lock') || $this->isInstalled()) {
            return response('系统已安装，安装向导已禁用', 404);
        }

        $step = (int)($request->get('step', '1'));

        if ($request->method() === 'POST') {
            return $this->handlePost($request, $step);
        }

        return match ($step) {
            1 => $this->step1(),
            2 => $this->step2($request),
            3 => $this->step3($request),
            4 => $this->step4($request),
            default => $this->step1(),
        };
    }

    private function step1(): Response
    {
        $checks = $this->envChecks();
        $allOk = !in_array(false, array_column($checks, 'pass'), true);
        return $this->render('步骤 1/4 · 环境检查', $this->htmlStep1($checks, $allOk));
    }

    private function envChecks(): array
    {
        $envOk = is_writable(base_path() . '/.env') || !file_exists(base_path() . '/.env');
        $sqlOk = file_exists(base_path(false) . '/docs/install.sql');
        return [
            ['name' => 'PHP 版本 ≥ 8.3',   'pass' => version_compare(PHP_VERSION, '8.3.0', '>='), 'current' => PHP_VERSION],
            ['name' => 'PDO MySQL 扩展',     'pass' => extension_loaded('pdo_mysql'), 'current' => extension_loaded('pdo_mysql') ? '已安装' : '未安装'],
            ['name' => 'GD 扩展',            'pass' => extension_loaded('gd'), 'current' => extension_loaded('gd') ? '已安装' : '未安装'],
            ['name' => 'Mbstring 扩展',      'pass' => extension_loaded('mbstring'), 'current' => extension_loaded('mbstring') ? '已安装' : '未安装'],
            ['name' => 'Redis 扩展',        'pass' => extension_loaded('redis'), 'current' => extension_loaded('redis') ? '已安装' : '未安装'],
            ['name' => 'Curl 扩展',         'pass' => extension_loaded('curl'), 'current' => extension_loaded('curl') ? '已安装' : '未安装'],
            ['name' => 'Pcntl 扩展',        'pass' => extension_loaded('pcntl'), 'current' => extension_loaded('pcntl') ? '已安装' : '未安装'],
            ['name' => '.env 文件可写',      'pass' => $envOk, 'current' => $envOk ? '可写' : '不可写'],
            ['name' => 'install.sql 存在',   'pass' => $sqlOk, 'current' => $sqlOk ? '存在' : '缺失'],
        ];
    }

    private function step2(Request $request): Response
    {
        $db = session('install_db') ?? ['host' => '127.0.0.1', 'port' => '3306', 'database' => 'appointment', 'username' => 'root', 'password' => ''];
        $err = session('install_error') ?? '';
        session()->forget('install_error');
        return $this->render('步骤 2/4 · 数据库配置', $this->htmlStep2($db, $err));
    }

    private function step3(Request $request): Response
    {
        if (!session('install_db_ok')) {
            return redirect('/install?step=2');
        }
        $adm = session('install_admin') ?? ['username' => 'admin', 'password' => '', 'password2' => '', 'app_name' => '康悦养生'];
        $err = session('install_error') ?? '';
        $warn = (int)session('install_existing_tables');
        session()->forget('install_error');
        return $this->render('步骤 3/4 · 管理员账号', $this->htmlStep3($adm, $err, $warn));
    }

    private function step4(Request $request): Response
    {
        if (!session('install_db_ok') || !session('install_admin')) {
            return redirect('/install?step=2');
        }
        return $this->render('步骤 4/4 · 安装中', $this->htmlStep4());
    }

    /**
     * 判断系统是否已完成安装
     * 标记写入 erik_system_config（group=system, key=installed, value=1）；
     * 兼容历史安装（无标记但已存在管理员账号）视为已安装。
     * 注意: 不能以 erik_system_config 表是否存在或 app_name 配置判定 ——
     * install.sql 会种子化 app_name，仅导入 SQL 未建管理员的全新库仍应允许安装。
     */
    private function isInstalled(): bool
    {
        try {
            $pdo = Db::connection()->getPdo();
        } catch (\Throwable $e) {
            // 数据库不可达视为未安装，允许进入安装向导
            return false;
        }
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM `erik_system_config` WHERE `key`='installed' AND `value`='1'");
            if ((int)$stmt->fetchColumn() > 0) {
                return true;
            }
            // 管理员账号仅由安装向导创建（install.sql 不种子 admin_user），存在即视为已安装
            $stmt = $pdo->query("SELECT COUNT(*) FROM `erik_admin_user`");
            return (int)$stmt->fetchColumn() > 0;
        } catch (\Throwable $e) {
            // 表不存在（全新库）视为未安装
            return false;
        }
    }

    private function handlePost(Request $request, int $step): Response
    {
        return match ($step) {
            2 => $this->postStep2($request),
            3 => $this->postStep3($request),
            4 => $this->postStep4(),
            default => $this->step1(),
        };
    }

    private function postStep2(Request $request): Response
    {
        $db = [
            'host' => trim($request->post('host', '127.0.0.1')),
            'port' => trim($request->post('port', '3306')),
            'database' => trim($request->post('database', 'appointment')),
            'username' => trim($request->post('username', 'root')),
            'password' => trim($request->post('password', '')),
        ];
        if (empty($db['host']) || empty($db['database']) || empty($db['username'])) {
            session()->set('install_db', $db);
            session()->set('install_error', '请填写完整的数据库连接信息');
            return redirect('/install?step=2');
        }
        // H1: 数据库名白名单校验，禁止注入反引号/分号/路径字符
        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $db['database'])) {
            session()->set('install_db', $db);
            session()->set('install_error', '数据库名称只能包含字母、数字、下划线和中划线');
            return redirect('/install?step=2');
        }
        try {
            $dsn = "mysql:host={$db['host']};port={$db['port']};charset=utf8mb4";
            $pdo = new \PDO($dsn, $db['username'], $db['password'], [
                \PDO::ATTR_TIMEOUT => 5,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db['database']}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$db['database']}`");
            $stmt = $pdo->query("SHOW TABLES LIKE 'erik_%'");
            $cnt = count($stmt->fetchAll(\PDO::FETCH_NUM));
        } catch (\PDOException $e) {
            session()->set('install_db', $db);
            session()->set('install_error', '数据库连接失败: ' . $e->getMessage());
            return redirect('/install?step=2');
        }
        session()->set('install_db', $db);
        session()->set('install_db_ok', true);
        session()->set('install_existing_tables', $cnt);
        return redirect('/install?step=3');
    }

    private function postStep3(Request $request): Response
    {
        // 未通过数据库配置（step 2）不允许跳级到管理员账号步骤
        if (!session('install_db_ok')) {
            return redirect('/install?step=2');
        }

        $adm = [
            'username' => trim($request->post('username', 'admin')),
            'password' => trim($request->post('password', '')),
            'password2' => trim($request->post('password2', '')),
            'app_name' => trim($request->post('app_name', '康悦养生')),
        ];
        if (empty($adm['username']) || strlen($adm['username']) < 3) {
            session()->set('install_admin', $adm);
            session()->set('install_error', '用户名至少需要 3 个字符');
            return redirect('/install?step=3');
        }
        if (empty($adm['password']) || strlen($adm['password']) < 6) {
            session()->set('install_admin', $adm);
            session()->set('install_error', '密码至少需要 6 个字符');
            return redirect('/install?step=3');
        }
        if ($adm['password'] !== $adm['password2']) {
            session()->set('install_admin', $adm);
            session()->set('install_error', '两次输入的密码不一致');
            return redirect('/install?step=3');
        }
        session()->set('install_admin', $adm);
        return redirect('/install?step=4');
    }

    private function postStep4(): Response
    {
        if (!session('install_db_ok') || !session('install_admin')) {
            return json(['success' => false, 'errors' => ['会话已过期，请重新开始']]);
        }

        $db = session('install_db');
        $adm = session('install_admin');
        $logs = [];
        $errs = [];

        // Connect
        try {
            $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset=utf8mb4";
            $pdo = new \PDO($dsn, $db['username'], $db['password'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
            $pdo->exec("SET NAMES utf8mb4");
            $logs[] = ['name' => '数据库连接成功', 'ok' => true];
        } catch (\PDOException $e) {
            return json(['success' => false, 'errors' => ['数据库连接失败: ' . $e->getMessage()]]);
        }

        // Import SQL
        $sqlFile = base_path(false) . '/docs/install.sql';
        if (!file_exists($sqlFile)) {
            return json(['success' => false, 'errors' => ["找不到 install.sql: {$sqlFile}"]]);
        }
        try {
            $stmts = $this->splitSql(file_get_contents($sqlFile));
            $cnt = 0;
            foreach ($stmts as $s) {
                $s = trim($s);
                if ($s === '') continue;
                try { $pdo->exec($s); $cnt++; } catch (\PDOException $e) {
                    if (!str_contains($e->getMessage(), 'already exists') && !str_contains($e->getMessage(), 'Duplicate')) {
                        $errs[] = $e->getMessage();
                    }
                }
            }
            $logs[] = ['name' => "导入 SQL（{$cnt} 条语句）", 'ok' => count($errs) === 0];
        } catch (\Throwable $e) {
            return json(['success' => false, 'errors' => ['SQL 导入失败: ' . $e->getMessage()]]);
        }

        // Create admin
        try {
            $hash = password_hash($adm['password'], PASSWORD_BCRYPT);
            $uid = $this->snowflake();
            $pdo->prepare(
                "INSERT INTO `erik_admin_user` (`id`,`username`,`password`,`real_name`,`status`,`created_at`,`updated_at`)
                 VALUES (:id,:u,:p,'系统管理员',1,NOW(),NOW())
                 ON DUPLICATE KEY UPDATE `password`=VALUES(`password`),`updated_at`=NOW()"
            )->execute(['id' => $uid, 'u' => $adm['username'], 'p' => $hash]);
            $logs[] = ['name' => "创建管理员: {$adm['username']}", 'ok' => true];

            $role = $pdo->query("SELECT id FROM `erik_admin_role` WHERE slug='super_admin' LIMIT 1")->fetch(\PDO::FETCH_ASSOC);
            if ($role) {
                $pdo->exec("INSERT IGNORE INTO `erik_admin_user_role` (`user_id`,`role_id`) VALUES ({$uid},{$role['id']})");
                $logs[] = ['name' => '分配超级管理员角色', 'ok' => true];
            }
        } catch (\PDOException $e) {
            $errs[] = '创建管理员失败: ' . $e->getMessage();
        }

        // App name
        try {
            $aid = $this->snowflake();
            $pdo->prepare(
                "INSERT INTO `erik_system_config` (`id`,`group`,`key`,`value`,`type`,`description`)
                 VALUES (:id,'app','app_name',:n,'string','应用名称')
                 ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)"
            )->execute(['id' => $aid, 'n' => $adm['app_name']]);
            $logs[] = ['name' => "应用名称: {$adm['app_name']}", 'ok' => true];
        } catch (\PDOException $e) {
            $logs[] = ['name' => '应用名称 (跳过)', 'ok' => true];
        }

        // C2: 写入"已安装"标记，防止安装向导被再次执行重置管理员
        try {
            $markId = $this->snowflake();
            $pdo->prepare(
                "INSERT INTO `erik_system_config` (`id`,`group`,`key`,`value`,`type`,`description`)
                 VALUES (:id,'system','installed','1','string','系统安装状态（1=已安装，安装向导自动禁用）')
                 ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)"
            )->execute(['id' => $markId]);
            $logs[] = ['name' => '写入已安装标记', 'ok' => true];
        } catch (\PDOException $e) {
            $errs[] = '写入已安装标记失败: ' . $e->getMessage();
        }

        // Write .env
        try {
            file_put_contents(base_path() . '/.env', $this->buildEnv($db));
            $logs[] = ['name' => '写入 .env 配置文件', 'ok' => true];
        } catch (\Throwable $e) {
            $errs[] = '写入 .env 失败: ' . $e->getMessage();
        }

        // C2: 安装成功落盘文件锁（与数据库 installed 标记双保险），防止向导被再次执行
        if (empty($errs)) {
            @file_put_contents(base_path() . '/.install.lock', date('Y-m-d H:i:s'));
        }

        session()->destroy();

        return json([
            'success' => empty($errs),
            'results' => $logs,
            'errors' => $errs,
            'admin' => ['username' => $adm['username'], 'password' => $adm['password']],
        ]);
    }

    private function splitSql(string $sql): array
    {
        $out = [];
        $cur = '';
        $in = false;
        $q = '';
        foreach (str_split($sql) as $c) {
            if ($in) { $cur .= $c; if ($c === $q) $in = false; continue; }
            if ($c === "'" || $c === '"') { $in = true; $q = $c; $cur .= $c; continue; }
            if ($c === ';') { $t = trim($cur); if ($t !== '' && !str_starts_with($t, '--') && !str_starts_with($t, '#')) $out[] = $t; $cur = ''; continue; }
            $cur .= $c;
        }
        $t = trim($cur);
        if ($t !== '' && !str_starts_with($t, '--') && !str_starts_with($t, '#')) $out[] = $t;
        return $out;
    }

    private function snowflake(): string
    {
        $ts = (int)(microtime(true) * 1000) - 1700000000000;
        return (string)(($ts << 22) | (1 << 17) | (1 << 12) | random_int(0, 4095));
    }

    private function buildEnv(array $db): string
    {
        return implode("\n", [
            '# 预约服务系统 — 环境变量 (由安装向导生成)',
            '# ' . date('Y-m-d H:i:s'),
            '',
            '# ── 应用 ──',
            'APP_NAME=预约服务系统',
            'APP_DEBUG=false',
            'APP_URL=http://localhost:8787',
            '',
            '# ── 数据库 ──',
            'DB_CONNECTION=mysql',
            "DB_HOST={$db['host']}",
            "DB_PORT={$db['port']}",
            "DB_DATABASE={$db['database']}",
            "DB_USERNAME={$db['username']}",
            "DB_PASSWORD={$db['password']}",
            '',
            '# ── Redis ──',
            'REDIS_HOST=127.0.0.1',
            'REDIS_PORT=6379',
            'REDIS_PASSWORD=',
            'REDIS_DATABASE=0',
            '',
            '# ── JWT ──',
            'JWT_SECRET_KEY=' . bin2hex(random_bytes(32)),
            'JWT_ALGORITHM=HS256',
            'JWT_DEFAULT_EXPIRE=7200',
            'JWT_REFRESH_EXPIRE=1209600',
            'JWT_ISSUER=open-admin',
            'JWT_AUDIENCE=open-admin',
            '',
            '# ── Hashids ──',
            'HASHIDS_SALT=' . bin2hex(random_bytes(16)),
            'HASHIDS_ALT_SALT=' . bin2hex(random_bytes(16)),
            '',
            '# ── Snowflake ──',
            'SNOWFLAKE_DATACENTER_ID=1',
            'SNOWFLAKE_WORKER_ID=1',
            'SNOWFLAKE_START_TIMESTAMP=1700000000000',
            '',
            '# ── 加密 ──',
            'ENCRYPTION_KEY=' . bin2hex(random_bytes(16)),
            'ENCRYPTION_CIPHER=AES-256-CBC',
            'ENCRYPTABLE_KEY=' . bin2hex(random_bytes(16)),
            'ENCRYPTABLE_CIPHER=AES-256-CBC',
            '',
            '# ── ES ──',
            'SCOUT_DRIVER=elasticsearch',
            'SCOUT_HOSTS=http://localhost:9200',
            'SCOUT_PREFIX=erik_',
            '',
            '# ── 验证码 ──',
            'POSTER_CAPTCHA_STORAGE=file',
            'POSTER_CAPTCHA_TTL=300',
            'POSTER_CAPTCHA_DIFFICULTY=medium',
        ]) . "\n";
    }

    // ============================================================
    // HTML
    // ============================================================

    private function render(string $title, string $body): Response
    {
        $h = <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>{$title} — 预约服务系统安装向导</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:#f5f7fa;color:#333;line-height:1.6}
.container{max-width:640px;margin:40px auto;padding:0 20px}
.card{background:#fff;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.08);padding:32px;margin-bottom:20px}
h1{font-size:24px;margin-bottom:8px;color:#1a1a2e}
h2{font-size:18px;margin-bottom:20px;color:#555}
.steps{display:flex;margin-bottom:24px}
.steps>div{flex:1;text-align:center;padding:8px;font-size:12px;color:#999;border-bottom:3px solid #e0e0e0}
.steps>div.active{color:#4f46e5;border-bottom-color:#4f46e5;font-weight:600}
.steps>div.done{color:#059669;border-bottom-color:#059669}
.fg{margin-bottom:16px}
.fg label{display:block;margin-bottom:4px;font-size:14px;font-weight:500;color:#444}
.fg input{width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px}
.fg input:focus{outline:none;border-color:#4f46e5;box-shadow:0 0 0 3px rgba(79,70,229,.1)}
.row{display:flex;gap:12px}.row>*{flex:1}
.btn{display:inline-block;padding:10px 24px;border:none;border-radius:8px;font-size:15px;font-weight:500;cursor:pointer;text-decoration:none}
.btn-p{background:#4f46e5;color:#fff}.btn-p:hover{background:#4338ca}
.btn-o{background:#fff;color:#4f46e5;border:1px solid #4f46e5}
.alert{padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:14px}
.alert-e{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
.alert-w{background:#fffbeb;color:#d97706;border:1px solid #fde68a}
.cl{list-style:none}.cl li{display:flex;align-items:center;padding:8px 0;border-bottom:1px solid #f3f4f6;font-size:14px}
.cl .ic{width:24px;text-align:center;margin-right:8px}.cl .cur{color:#888;font-size:12px;margin-left:auto}
.pass{color:#059669}.fail{color:#dc2626}
.rl{list-style:none}.rl li{padding:6px 0;font-size:14px}
.spin{display:inline-block;width:20px;height:20px;border:2px solid #e0e0e0;border-top-color:#4f46e5;border-radius:50%;animation:spin .6s linear infinite;margin-right:8px;vertical-align:middle}
@keyframes spin{to{transform:rotate(360deg)}}
.done-box{text-align:center;padding:20px 0}
.done-box .check{font-size:48px;color:#059669;margin-bottom:12px}
.done-box .info{background:#f9fafb;border-radius:8px;padding:12px;margin:12px 0;text-align:left;font-size:14px}
.done-box .info code{background:#e5e7eb;padding:1px 6px;border-radius:4px}
.ft{text-align:center;font-size:12px;color:#999;margin-top:20px}
</style>
</head>
<body>
<div class="container">
<h1>预约服务系统 · 安装向导</h1>
{$body}
<div class="ft">Copyright &copy; 2026 erik &lt;erik@erik.xyz&gt;</div>
</div>
</body>
</html>
HTML;
        return new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $h);
    }

    private function steps(int $n): string
    {
        $labels = ['环境检查', '数据库配置', '管理员账号', '安装'];
        $h = '<div class="steps">';
        foreach ($labels as $i => $l) {
            $cls = ($i + 1 < $n) ? 'done' : (($i + 1 === $n) ? 'active' : '');
            $h .= "<div class=\"{$cls}\">{$l}</div>";
        }
        return $h . '</div>';
    }

    private function htmlStep1(array $checks, bool $all): string
    {
        $s = $this->steps(1);
        $li = '';
        foreach ($checks as $c) {
            $ic = $c['pass'] ? '<span class="ic pass">&#10003;</span>' : '<span class="ic fail">&#10007;</span>';
            $li .= "<li>{$ic}{$c['name']}<span class=\"cur\">{$c['current']}</span></li>";
        }
        $btn = $all
            ? '<a href="/install?step=2" class="btn btn-p">下一步 · 数据库配置 &rarr;</a>'
            : '<div class="alert alert-e">部分检查未通过，请安装所需扩展后刷新重试。</div>';
        return "{$s}<div class=\"card\"><h2>环境检查</h2><ul class=\"cl\">{$li}</ul></div>{$btn}";
    }

    private function htmlStep2(array $db, string $err): string
    {
        $s = $this->steps(2);
        $e = $err ? "<div class=\"alert alert-e\">{$err}</div>" : '';
        return <<<HTML
{$s}
<div class="card"><h2>数据库配置</h2>{$e}
<form method="post" action="/install?step=2">
<div class="row"><div class="fg"><label>主机地址</label><input type="text" name="host" value="{$db['host']}" required></div>
<div class="fg"><label>端口</label><input type="number" name="port" value="{$db['port']}" required></div></div>
<div class="fg"><label>数据库名称</label><input type="text" name="database" value="{$db['database']}" required placeholder="不存在将自动创建"></div>
<div class="row"><div class="fg"><label>用户名</label><input type="text" name="username" value="{$db['username']}" required></div>
<div class="fg"><label>密码</label><input type="password" name="password" value="{$db['password']}"></div></div>
<button type="submit" class="btn btn-p">测试连接 &amp; 下一步 &rarr;</button>
</form></div>
HTML;
    }

    private function htmlStep3(array $adm, string $err, int $exist): string
    {
        $s = $this->steps(3);
        $e = $err ? "<div class=\"alert alert-e\">{$err}</div>" : '';
        $w = $exist > 0 ? "<div class=\"alert alert-w\">检测到已有 {$exist} 个 erik_ 表，已存在数据不会被覆盖。</div>" : '';
        return <<<HTML
{$s}
<div class="card"><h2>管理员账号</h2>{$e}{$w}
<form method="post" action="/install?step=3">
<div class="fg"><label>应用名称</label><input type="text" name="app_name" value="{$adm['app_name']}" required></div>
<div class="fg"><label>管理员用户名</label><input type="text" name="username" value="{$adm['username']}" required minlength="3"></div>
<div class="row"><div class="fg"><label>登录密码</label><input type="password" name="password" required minlength="6" placeholder="至少 6 位"></div>
<div class="fg"><label>确认密码</label><input type="password" name="password2" required minlength="6"></div></div>
<button type="submit" class="btn btn-p">确认 &amp; 开始安装 &rarr;</button>
</form></div>
HTML;
    }

    private function htmlStep4(): string
    {
        $s = $this->steps(4);
        return <<<HTML
{$s}
<div class="card"><h2>正在安装...</h2>
<div id="stat"><p><span class="spin"></span> 正在导入数据库结构及种子数据，请稍候...</p></div>
<ul id="log" class="rl" style="margin-top:12px"></ul>
<div id="errs"></div>
<div id="fin" style="display:none"></div>
</div>
<script>
(async function(){
var l=document.getElementById('log'),e=document.getElementById('errs'),f=document.getElementById('fin');
try{
var r=await fetch('/install?step=4',{method:'POST'}),d=await r.json();
if(d.success){
document.getElementById('stat').innerHTML='<p style="color:#059669;font-weight:500">安装完成！</p>';
d.results.forEach(function(x){l.innerHTML+='<li><span class="pass">&#10003;</span> '+x.name+'</li>'});
f.innerHTML='<div class="done-box"><div class="check">&#10003;</div><h2>安装成功！</h2>'+
'<div class="info"><p><strong>管理员账号:</strong> <code>'+d.admin.username+'</code></p>'+
'<p><strong>登录密码:</strong> <code>'+d.admin.password+'</code></p>'+
'<p style="margin-top:8px;color:#dc2626">请妥善保管密码，此页面关闭后无法再次查看。</p></div>'+
'<a href="/" class="btn btn-p" style="margin-right:8px">进入系统</a>'+
'<a href="/health" class="btn btn-o">健康检查</a></div>';
f.style.display='block';
}else{
document.getElementById('stat').innerHTML='<p style="color:#dc2626">安装失败</p>';
if(d.results)d.results.forEach(function(x){l.innerHTML+='<li><span class="'+(x.ok?'pass':'fail')+'">'+(x.ok?'&#10003;':'&#10007;')+'</span> '+x.name+'</li>'});
if(d.errors)d.errors.forEach(function(x){e.innerHTML+='<div class="alert alert-e">'+x+'</div>'});
}
}catch(x){document.getElementById('stat').innerHTML='<div class="alert alert-e">请求失败: '+x.message+'</div>'}
})();
</script>
HTML;
    }
}
