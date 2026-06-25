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

$models = [
    'openrouter/free',
    'meta-llama/llama-3.1-8b-instruct:free',
    'google/gemma-3-12b-it:free',
    'mistralai/mistral-7b-instruct:free',
    'meta-llama/llama-3.3-70b-instruct:free',
    getenv('OPENROUTER_MODEL') ?: 'openai/gpt-oss-120b:free',
];

$config = require dirname(__DIR__) . '/config.php';
$key = $config['apis']['openrouter']['api_key'];
$appUrl = getenv('APP_URL') ?: 'http://127.0.0.1:1234';

foreach ($models as $model) {
    $t0 = microtime(true);
    $data = json_encode([
        'model' => $model,
        'messages' => [
            ['role' => 'user', 'content' => 'Jibu kwa Kiswahili sentensi 2: jinsi ya kupanda mahindi?'],
        ],
        'max_tokens' => 150,
    ]);
    $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $key,
            'HTTP-Referer: ' . $appUrl,
        ],
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $elapsed = round(microtime(true) - $t0, 1);
    if ($err) {
        echo "{$model}: FAIL curl {$err} ({$elapsed}s)\n";
        continue;
    }
    $j = json_decode($resp, true);
    $text = trim($j['choices'][0]['message']['content'] ?? '');
    if ($text === '' && $code !== 200) {
        echo "{$model}: HTTP {$code} ({$elapsed}s) " . mb_substr($resp, 0, 120) . "\n";
    } elseif ($text === '') {
        echo "{$model}: HTTP {$code} ({$elapsed}s) EMPTY — " . mb_substr($resp, 0, 150) . "\n";
    } else {
        echo "{$model}: HTTP {$code} ({$elapsed}s) " . mb_substr($text, 0, 80) . "\n";
    }
}
