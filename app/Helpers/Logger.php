<?php

namespace App\Helpers;

/**
 * Application logger — uses Monolog when available, file fallback otherwise.
 */
class Logger
{
    private static $monolog = null;
    private static bool $useFallback = false;

    private static function boot(): void
    {
        if (self::$monolog !== null || self::$useFallback) {
            return;
        }

        if (class_exists(\Monolog\Logger::class)) {
            $logDir = dirname(__DIR__, 2) . '/storage/logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0775, true);
            }

            $level = match (strtolower(getenv('LOG_LEVEL') ?: 'debug')) {
                'info' => \Monolog\Level::Info,
                'warning', 'warn' => \Monolog\Level::Warning,
                'error' => \Monolog\Level::Error,
                default => \Monolog\Level::Debug,
            };

            self::$monolog = new \Monolog\Logger('agriad');
            self::$monolog->pushHandler(new \Monolog\Handler\RotatingFileHandler(
                $logDir . '/app.log',
                14,
                $level
            ));
            return;
        }

        self::$useFallback = true;
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }
    }

    public static function debug(string $message, array $context = []): void
    {
        self::write('DEBUG', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    private static function write(string $level, string $message, array $context): void
    {
        self::boot();

        if (self::$monolog instanceof \Monolog\Logger) {
            self::$monolog->log(
                constant(\Monolog\Level::class . '::' . $level),
                $message,
                $context
            );
            self::mirrorToAnalytics($level, $message, $context);
            return;
        }

        $line = sprintf(
            "[%s] %s: %s %s\n",
            date('Y-m-d H:i:s'),
            $level,
            $message,
            $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
        );
        file_put_contents(
            dirname(__DIR__, 2) . '/storage/logs/app.log',
            $line,
            FILE_APPEND | LOCK_EX
        );
        self::mirrorToAnalytics($level, $message, $context);
    }

    private static function mirrorToAnalytics(string $level, string $message, array $context): void {
        if (!class_exists(\App\Services\AppLogSyncService::class)) {
            return;
        }
        try {
            \App\Services\AppLogSyncService::persist($level, $message, $context);
        } catch (\Throwable) {
            // Never break logging if analytics insert fails.
        }
    }
}
