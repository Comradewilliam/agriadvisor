<?php
declare(strict_types=1);

/**
 * Diagnose Africa's Talking SMS delivery.
 * Usage: php scripts/diagnose-sms.php +2557XXXXXXXX
 */

require dirname(__DIR__) . '/app/Core/Env.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

if (!extension_loaded('curl')) {
    fwrite(STDERR, "ERROR: PHP curl extension is NOT loaded in this PHP binary.\n");
    fwrite(STDERR, "Use the same PHP as Apache/MAMP web server, or enable extension=php_curl.dll in php.ini\n");
    exit(1);
}

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base = dirname(__DIR__) . '/app/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
    $file = $base . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) require $file;
});

$to = $argv[1] ?? '+255616363951';

echo "=== Agri-Advisory SMS Diagnostic ===\n\n";
echo "PHP: " . PHP_BINARY . " (curl: " . (extension_loaded('curl') ? 'yes' : 'NO') . ")\n";
echo "AT_USERNAME: " . (getenv('AT_USERNAME') ?: 'sandbox') . "\n";
echo "AT_SANDBOX: " . (getenv('AT_SANDBOX') ?: '0') . "\n";
echo "AT_SHORT_CODE: " . (getenv('AT_SHORT_CODE') ?: 'NOT SET') . "\n";
echo "Recipient: {$to}\n\n";

$at = new \App\Services\AfricasTalkingService();

echo "--- Test 1: One-way (welcome/OTP style, no short code) ---\n";
$r1 = $at->sendOneWay($to, 'Agri-Advisory one-way test ' . date('H:i:s'));
printResult($r1);

echo "\n--- Test 2: Two-way (from short code {$at->getShortCode()}) ---\n";
$r2 = $at->sendTwoWayReply($to, 'Agri-Advisory two-way test ' . date('H:i:s'));
printResult($r2);

echo "\n=== AT statusCode reference ===\n";
echo "101=Sent  403=InvalidPhoneNumber  405=InsufficientBalance  407=CouldNotRoute\n";
echo "\nIf API says Success but phone receives nothing:\n";
echo "1. Register phone in AT Sandbox → Phone Numbers (+{$to})\n";
echo "2. Check storage/logs/app.log for 'SMS delivery FAILED at telco'\n";
echo "3. Sandbox may not deliver to all TZ numbers — use AT Simulator or live API key\n";

function printResult(array $r): void {
    echo json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    $rec = $r['SMSMessageData']['Recipients'][0] ?? [];
    $code = (int)($rec['statusCode'] ?? 0);
    $st = $rec['status'] ?? 'unknown';
    echo "Recipient status: {$st}, statusCode: {$code}\n";
    if ($code === 403) {
        echo ">>> InvalidPhoneNumber — register this phone in AT sandbox dashboard\n";
    }
    if (\App\Services\AfricasTalkingService::isSuccess($r)) {
        echo ">>> API accepted message. Wait 60s and check delivery report in app.log\n";
    } else {
        echo ">>> API REJECTED — fix API key, short code, or phone format\n";
    }
}
