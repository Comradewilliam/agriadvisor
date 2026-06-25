<?php
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($uriPath !== '/' && str_ends_with($uriPath, '/')) {
    $uriPath = rtrim($uriPath, '/');
}
$isUssdWebhook = str_ends_with($uriPath, '/ussd')
    || str_contains($uriPath, '/internal/sms/')
    || str_contains($uriPath, '/sms');
if (!$isUssdWebhook) {
    session_start();
}

$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require $autoload;
}

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

\App\Core\Env::load(__DIR__ . '/../.env');

set_exception_handler(function (\Throwable $e) {
    if (class_exists(\App\Helpers\Logger::class)) {
        \App\Helpers\Logger::error('Uncaught exception', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }
    http_response_code(500);
    if (file_exists(__DIR__ . '/../views/errors/500.php')) {
        require __DIR__ . '/../views/errors/500.php';
    } else {
        echo 'Internal Server Error';
    }
    exit;
});

$router = new \App\Core\Router();

require __DIR__ . '/../routes/web.php';
require __DIR__ . '/../routes/api.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$scriptName = dirname($_SERVER['SCRIPT_NAME']);
if ($scriptName !== '/' && $scriptName !== '\\') {
    $uri = str_replace($scriptName, '', $uri);
}
if ($uri === '') {
    $uri = '/';
}
if ($uri !== '/' && str_ends_with($uri, '/')) {
    $uri = rtrim($uri, '/');
}

$router->dispatch($uri, $method);
