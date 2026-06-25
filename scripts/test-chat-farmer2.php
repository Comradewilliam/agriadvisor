<?php
require dirname(__DIR__) . '/app/Core/Env.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base = dirname(__DIR__) . '/app/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
    $file = $base . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (file_exists($file)) require $file;
});

$farmerId = 2;
$text = 'Jinsi ya kupanda mahindi?';
echo "Testing farmer #{$farmerId}: {$text}\n";

try {
    $chat = new \App\Services\FarmerChatService();
    $result = $chat->handleMessage($farmerId, $text, 'web', null);
    echo "OK thread={$result['thread_id']}\n";
    echo mb_substr($result['reply'], 0, 200) . "\n";
} catch (Throwable $e) {
    echo "FAIL: " . $e->getMessage() . "\n";
    echo $e->getFile() . ':' . $e->getLine() . "\n";
}
