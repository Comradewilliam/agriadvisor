<?php
require dirname(__DIR__) . '/app/Core/Env.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $file = $base_dir . str_replace('\\', '/', substr($class, $len)) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

$n = App\Services\AppLogSyncService::importAppLog();
$total = (int) App\Core\Database::getInstance()->getConnection()->query('SELECT COUNT(*) FROM system_events')->fetchColumn();
echo "imported={$n} total={$total}\n";
