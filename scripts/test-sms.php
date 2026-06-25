<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/Core/Env.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base = dirname(__DIR__) . '/app/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $file = $base . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

$to = $argv[1] ?? '+255757070229';
$msg = $argv[2] ?? 'Agri-Advisory SMS test ' . date('H:i:s');
$mode = in_array('--oneway', $argv, true) ? 'oneway' : 'twoway';

$at = new \App\Services\AfricasTalkingService();
echo "Mode: {$mode}\n";
echo "Short code (AT_SHORT_CODE): " . ($at->getShortCode() ?: 'NOT SET — add to .env') . "\n";
echo "Sending to {$to}...\n";

$result = $mode === 'oneway'
    ? $at->sendOneWay($to, $msg)
    : $at->sendTwoWayReply($to, $msg);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

if (\App\Services\AfricasTalkingService::isSuccess($result)) {
    echo "SUCCESS — farmer should reply to your short code, not AFRICASTKNG\n";
    exit(0);
}

echo "FAILED — check storage/logs/app.log\n";
exit(1);
