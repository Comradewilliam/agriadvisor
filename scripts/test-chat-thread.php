<?php
/**
 * Smoke test: farmer chat thread create + message save.
 */
require dirname(__DIR__) . '/app/Core/Env.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base = dirname(__DIR__) . '/app/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $file = $base . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

$config = require dirname(__DIR__) . '/config.php';
$pdo = new PDO(
    "mysql:host={$config['db']['host']};dbname={$config['db']['dbname']}",
    $config['db']['user'],
    $config['db']['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$farmerId = (int)$pdo->query('SELECT id FROM farmers ORDER BY id ASC LIMIT 1')->fetchColumn();
if (!$farmerId) {
    echo "No farmer in DB — skip chat test\n";
    exit(0);
}

echo "Farmer #{$farmerId}\n";

$threadModel = new \App\Models\ChatThread();
$tid = $threadModel->create($farmerId, 'web', 'Test thread mahindi');
echo "Created thread #{$tid}\n";

$chat = new \App\Services\FarmerChatService();
$result = $chat->handleMessage($farmerId, 'Mbolea gani kwa mahindi?', 'web', $tid);
echo "Reply thread_id: {$result['thread_id']}\n";
echo "Reply preview: " . mb_substr($result['reply'], 0, 80) . "...\n";

$count = $pdo->prepare('SELECT COUNT(*) FROM ai_messages WHERE thread_id = ?');
$count->execute([$tid]);
echo "Messages in thread: " . $count->fetchColumn() . "\n";

$list = $threadModel->listForFarmer($farmerId, 'web');
echo "Sidebar threads: " . count($list) . "\n";

echo "Chat thread test OK.\n";
