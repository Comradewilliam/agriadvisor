<?php
/**
 * PHP built-in server router.
 * Serves /assets/* directly (Windows PHP mishandles "return false").
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$file = __DIR__ . str_replace(['../', '..\\'], '', $uri);

if ($uri !== '/' && is_file($file)) {
    $realFile = realpath($file);
    $realRoot = realpath(__DIR__);
    if ($realFile && $realRoot && str_starts_with($realFile, $realRoot)) {
        $ext = strtolower(pathinfo($realFile, PATHINFO_EXTENSION));
        $types = [
            'css'   => 'text/css; charset=UTF-8',
            'js'    => 'application/javascript; charset=UTF-8',
            'woff2' => 'font/woff2',
            'woff'  => 'font/woff',
            'svg'   => 'image/svg+xml',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'gif'   => 'image/gif',
            'ico'   => 'image/x-icon',
            'webp'  => 'image/webp',
        ];
        header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
        header('Content-Length: ' . filesize($realFile));
        header('Cache-Control: public, max-age=86400');
        readfile($realFile);
        exit;
    }
}

require __DIR__ . '/index.php';
