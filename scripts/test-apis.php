<?php
/**
 * Verify .env API keys load and external services respond.
 * Usage: php scripts/test-apis.php
 */
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
    if (file_exists($file)) {
        require $file;
    }
});

$config = require dirname(__DIR__) . '/config.php';
$ok = true;

echo "=== .env / config ===\n";
$orKey = $config['apis']['openrouter']['api_key'] ?? '';
$orModel = $config['apis']['openrouter']['model'] ?? '';
$wxKey = $config['apis']['weatherapi']['api_key'] ?? '';
echo 'OPENROUTER_API_KEY: ' . ($orKey ? 'set (' . strlen($orKey) . ' chars)' : 'MISSING') . "\n";
echo 'OPENROUTER_MODEL: ' . ($orModel ?: 'default') . "\n";
echo 'WEATHER_API_KEY: ' . ($wxKey ? 'set (' . strlen($wxKey) . ' chars)' : 'MISSING') . "\n\n";

echo "=== OpenRouter AI ===\n";
if (!$orKey) {
    echo "SKIP — no key\n\n";
    $ok = false;
} else {
    $ai = new \App\Services\OpenAIService();
    $t0 = microtime(true);
    $result = $ai->generateWithHistory(
        'Jibu kwa Kiswahili kwa sentensi 2 kuhusu kilimo.',
        [['role' => 'user', 'content' => 'Mbolea gani kwa mahindi?']],
        120
    );
    $elapsed = round(microtime(true) - $t0, 1);
    if (!empty($result['error'])) {
        echo "FAIL ({$elapsed}s): {$result['error']}\n";
        $ok = false;
    } else {
        echo "OK ({$elapsed}s): " . mb_substr(trim($result['response'] ?? ''), 0, 150) . "\n";
    }
    echo "\n";
}

echo "=== WeatherAPI ===\n";
if (!$wxKey) {
    echo "SKIP — no key\n\n";
    $ok = false;
} else {
    $wx = new \App\Services\WeatherService();
    $t0 = microtime(true);
    $data = $wx->getWeatherByVillage('Kakonko');
    $elapsed = round(microtime(true) - $t0, 1);
    $temp = $data['current']['main']['temp'] ?? null;
    $source = $data['source'] ?? 'unknown';
    if ($temp === null && ($source === 'fallback')) {
        echo "WARN ({$elapsed}s): using fallback — live API may have failed (check logs)\n";
    } elseif ($temp !== null) {
        echo "OK ({$elapsed}s): {$temp}°C — {$source}\n";
        $desc = $data['current']['weather'][0]['description'] ?? '';
        if ($desc) {
            echo "     {$desc}\n";
        }
    } else {
        echo "FAIL ({$elapsed}s): no weather data\n";
        $ok = false;
    }
    echo "\n";
}

echo $ok ? "All API checks passed.\n" : "Some API checks failed.\n";
exit($ok ? 0 : 1);
