<?php
namespace App\Services;

use App\Core\Database;

/** Maps storage/logs/app.log entries into system_events for admin analytics. */
class AppLogSyncService {
    private static ?string $logPath = null;

    /** @var array<string, array{0: string, 1: string}> */
    private const MESSAGE_MAP = [
        'USSD advisory answered'        => ['ussd', 'advisory_answered'],
        'USSD KB answer'                => ['ussd', 'kb_answer'],
        'USSD offline advice'           => ['ussd', 'offline_advice'],
        'USSD officer info shown'       => ['ussd', 'officer_shown'],
        'USSD handle failed'            => ['ussd', 'handle_failed'],
        'USSD advisory failed'          => ['ussd', 'advisory_failed'],
        'SMS inbox callback'            => ['sms', 'inbox'],
        'SMS reply sent'                => ['sms', 'reply_sent'],
        'SMS reply failed'              => ['sms', 'reply_failed'],
        'SMS two-way reply NOT delivered' => ['sms', 'reply_failed'],
        'SMS inbound handler failed'    => ['sms', 'inbound_failed'],
        'SMS delivery report'           => ['delivery', 'report'],
        'SMS delivery FAILED at telco'  => ['delivery', 'telco_failed'],
        'Farmer chat reply generated'   => ['ai', 'reply_generated'],
        'OpenRouter failed, trying KB fallback' => ['ai', 'openrouter_failed'],
        'AT SMS sent'                   => ['sms', 'at_sent'],
        'Automated alert sent'          => ['alert', 'sent'],
        'Automated alert SMS failed'    => ['alert', 'failed'],
        'Bulk SMS completed'            => ['sms', 'bulk_completed'],
    ];

    public static function logPath(): string {
        return self::$logPath ??= dirname(__DIR__, 2) . '/storage/logs/app.log';
    }

    /** Persist a live log entry (called from Logger after writing app.log). */
    public static function persist(string $level, string $message, array $context = []): void {
        self::insertEvent(
            self::normalizeLevel($level),
            $message,
            $context,
            date('Y-m-d H:i:s')
        );
    }

    /** Import historical lines from app.log (skips duplicates via source_hash). */
    public static function importAppLog(int $maxLines = 5000): int {
        $path = self::logPath();
        if (!is_readable($path)) {
            return 0;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false || $lines === []) {
            return 0;
        }

        $lines = array_slice($lines, -$maxLines);
        $imported = 0;
        foreach ($lines as $line) {
            $parsed = self::parseLine($line);
            if ($parsed === null) {
                continue;
            }
            if (self::insertEvent($parsed['level'], $parsed['message'], $parsed['context'], $parsed['created_at'], $parsed['hash'])) {
                $imported++;
            }
        }
        return $imported;
    }

    /**
     * @return array{level: string, message: string, context: array, created_at: string, hash: string}|null
     */
    public static function parseLine(string $line): ?array {
        if (!preg_match('/^\[([^\]]+)\]\s+(\w+):\s+(.+)$/', trim($line), $m)) {
            return null;
        }

        $message = trim($m[3]);
        $context = [];
        if (preg_match('/^(.+?)\s+(\{.*\})$/', $message, $parts)) {
            $message = trim($parts[1]);
            $decoded = json_decode($parts[2], true);
            if (is_array($decoded)) {
                $context = $decoded;
            }
        }

        $createdAt = date('Y-m-d H:i:s', strtotime($m[1]));
        $hash = self::hash($createdAt, $m[2], $message, $context);

        return [
            'level'      => self::normalizeLevel($m[2]),
            'message'    => $message,
            'context'    => $context,
            'created_at' => $createdAt,
            'hash'       => $hash,
        ];
    }

    private static function insertEvent(
        string $level,
        string $message,
        array $context,
        string $createdAt,
        ?string $sourceHash = null
    ): bool {
        [$category, $event] = self::classify($message, $context);
        $hash = $sourceHash ?? self::hash($createdAt, strtoupper($level), $message, $context);

        $farmerId = isset($context['farmer_id']) ? (int)$context['farmer_id'] : null;
        $phone = (string)($context['phone'] ?? $context['from'] ?? $context['to'] ?? '');
        $phone = $phone !== '' ? $phone : null;
        $channel = (string)($context['channel'] ?? '');
        if ($channel === '') {
            $channel = self::inferChannel($category, $message);
        }
        $channel = $channel !== '' ? $channel : null;

        $eventMessage = (string)($context['message'] ?? $context['text'] ?? $message);
        $eventMessage = mb_substr($eventMessage, 0, 500);

        $meta = $context;
        unset($meta['farmer_id'], $meta['phone'], $meta['from'], $meta['to'], $meta['channel'], $meta['message'], $meta['text']);
        if ($meta === []) {
            $meta = null;
        }

        try {
            $db = Database::getInstance()->getConnection();
            self::ensureSourceHashColumn($db);

            $stmt = $db->prepare("
                INSERT IGNORE INTO system_events
                    (level, category, event, farmer_id, phone, channel, message, meta, source_hash, created_at)
                VALUES
                    (:level, :category, :event, :farmer_id, :phone, :channel, :message, :meta, :source_hash, :created_at)
            ");
            $stmt->execute([
                ':level'       => $level,
                ':category'    => mb_substr($category, 0, 50),
                ':event'       => mb_substr($event, 0, 100),
                ':farmer_id'   => $farmerId ?: null,
                ':phone'       => $phone,
                ':channel'     => $channel,
                ':message'     => $eventMessage,
                ':meta'        => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
                ':source_hash' => $hash,
                ':created_at'  => $createdAt,
            ]);
            return $stmt->rowCount() > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    /** @return array{0: string, 1: string} */
    private static function classify(string $message, array $context): array {
        foreach (self::MESSAGE_MAP as $prefix => $pair) {
            if ($message === $prefix || str_starts_with($message, $prefix)) {
                return $pair;
            }
        }

        $upper = strtoupper($message);
        if (str_contains($upper, 'USSD')) {
            return ['ussd', self::slug($message)];
        }
        if (str_contains($upper, 'SMS') || str_contains($message, 'AT SMS')) {
            return ['sms', self::slug($message)];
        }
        if (str_contains($message, 'OpenRouter') || str_contains($message, 'Farmer chat')) {
            return ['ai', self::slug($message)];
        }
        if (str_contains($message, 'delivery')) {
            return ['delivery', self::slug($message)];
        }
        if (str_contains($message, 'alert')) {
            return ['alert', self::slug($message)];
        }
        if (isset($context['channel']) && in_array($context['channel'], ['ussd', 'sms', 'web'], true)) {
            return [(string)$context['channel'], self::slug($message)];
        }

        return ['app', self::slug($message)];
    }

    private static function inferChannel(string $category, string $message): ?string {
        if (in_array($category, ['ussd', 'sms', 'web', 'ai'], true)) {
            return $category === 'ai' ? ($message !== '' ? null : null) : $category;
        }
        $upper = strtoupper($message);
        if (str_contains($upper, 'USSD')) return 'ussd';
        if (str_contains($upper, 'SMS')) return 'sms';
        return null;
    }

    private static function slug(string $message): string {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $message) ?? '');
        $slug = trim($slug, '_');
        return mb_substr($slug !== '' ? $slug : 'event', 0, 100);
    }

    private static function normalizeLevel(string $level): string {
        $level = strtolower($level);
        return in_array($level, ['info', 'warning', 'error'], true) ? $level : 'info';
    }

    /** @param array<string, mixed> $context */
    private static function hash(string $createdAt, string $level, string $message, array $context): string {
        return hash('sha256', $createdAt . '|' . $level . '|' . $message . '|' . json_encode($context, JSON_UNESCAPED_UNICODE));
    }

    private static function ensureSourceHashColumn(\PDO $db): void {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;
        try {
            $cols = $db->query("SHOW COLUMNS FROM system_events LIKE 'source_hash'")->fetchAll();
            if ($cols === []) {
                $db->exec("ALTER TABLE system_events ADD COLUMN source_hash CHAR(64) NULL AFTER meta");
                $db->exec("CREATE UNIQUE INDEX idx_se_source_hash ON system_events (source_hash)");
            }
        } catch (\Throwable) {
            // Table may not exist yet.
        }
    }
}
