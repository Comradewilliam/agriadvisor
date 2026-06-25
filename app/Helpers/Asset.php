<?php
namespace App\Helpers;

/**
 * Root-relative asset URLs (single leading slash, no double-slash).
 */
class Asset
{
    public static function url(string $path): string
    {
        $path = '/' . ltrim(str_replace('\\', '/', $path), '/');

        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        if ($script === '' || $script === '/index.php' || $script === '/router.php') {
            return $path;
        }

        $dir = dirname($script);
        if ($dir === '/' || $dir === '.' || $dir === '\\' || $dir === '') {
            return $path;
        }

        return rtrim($dir, '/') . $path;
    }
}
