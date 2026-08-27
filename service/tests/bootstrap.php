<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Load .env
if (class_exists('Dotenv\Dotenv') && file_exists(__DIR__ . '/../.env')) {
    \Dotenv\Dotenv::createUnsafeMutable(__DIR__ . '/..')->load();
}

// Setup Eloquent Capsule for testing
$capsule = new \Illuminate\Database\Capsule\Manager();
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => env('DB_HOST', '127.0.0.1'),
    'port'      => env('DB_PORT', '3306'),
    'database'  => env('DB_DATABASE', 'appointment'),
    'username'  => env('DB_USERNAME', 'root'),
    'password'  => env('DB_PASSWORD', ''),
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => 'appointment_',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Load webman configurations
if (class_exists('Webman\Config')) {
    \Webman\Config::clear();
    support\App::loadAllConfig(['route']);
}

// Load autoload files
foreach (config('autoload.files', []) as $file) {
    include_once $file;
}
foreach (config('plugin', []) as $firm => $projects) {
    foreach ($projects as $name => $project) {
        if (!is_array($project)) continue;
        foreach ($project['autoload']['files'] ?? [] as $file) {
            include_once $file;
        }
    }
}

// Run bootstrap plugins
$worker = $worker ?? null;
foreach (config('bootstrap', []) as $className) {
    if (class_exists($className)) {
        $className::start($worker);
    }
}
foreach (config('plugin', []) as $firm => $projects) {
    foreach ($projects as $name => $project) {
        if (!is_array($project)) continue;
        foreach ($project['bootstrap'] ?? [] as $className) {
            if (class_exists($className)) {
                $className::start($worker);
            }
        }
    }
}

// Helper: Polyfill for env() in test context
if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed {
        $value = getenv($key);
        if ($value === false) return $default;
        return $value;
    }
}
