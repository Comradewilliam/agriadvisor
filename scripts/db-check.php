<?php
require dirname(__DIR__) . '/app/Core/Env.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');
$config = require dirname(__DIR__) . '/config.php';
$db = $config['db'];
$pdo = new PDO(
    "mysql:host={$db['host']};dbname={$db['dbname']}",
    $db['user'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$tables = $pdo->query("SHOW TABLES LIKE 'chat_threads'")->fetchAll();
echo empty($tables) ? "chat_threads: MISSING\n" : "chat_threads: exists\n";

if (!empty($tables)) {
    foreach ($pdo->query('DESCRIBE chat_threads') as $c) {
        echo "  {$c['Field']} {$c['Type']}\n";
    }
}

foreach ($pdo->query("SHOW COLUMNS FROM ai_messages LIKE 'thread_id'") as $c) {
    echo "ai_messages.thread_id: {$c['Type']}\n";
}
