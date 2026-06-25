<?php
namespace App\Services;

use App\Core\Database;
use App\Helpers\Logger;

/** Persists USSD/SMS/AI events for admin analytics (alongside storage/logs/app.log). */
class SystemEventService {
    /**
     * @param array<string, mixed> $data farmer_id, phone, channel, message, plus meta fields
     */
    public static function record(string $level, string $category, string $event, array $data = []): void {
        $farmerId = isset($data['farmer_id']) ? (int)$data['farmer_id'] : null;
        $phone = isset($data['phone']) ? (string)$data['phone'] : null;
        $channel = isset($data['channel']) ? (string)$data['channel'] : null;
        $message = isset($data['message']) ? mb_substr((string)$data['message'], 0, 500) : null;

        $meta = $data;
        unset($meta['farmer_id'], $meta['phone'], $meta['channel'], $meta['message']);
        if ($meta === []) {
            $meta = null;
        }

        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                INSERT INTO system_events (level, category, event, farmer_id, phone, channel, message, meta)
                VALUES (:level, :category, :event, :farmer_id, :phone, :channel, :message, :meta)
            ");
            $stmt->execute([
                ':level'     => in_array($level, ['info', 'warning', 'error'], true) ? $level : 'info',
                ':category'  => mb_substr($category, 0, 50),
                ':event'     => mb_substr($event, 0, 100),
                ':farmer_id' => $farmerId ?: null,
                ':phone'     => $phone,
                ':channel'   => $channel,
                ':message'   => $message,
                ':meta'      => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
            ]);
        } catch (\Throwable $e) {
            Logger::warning('system_events insert failed', ['error' => $e->getMessage()]);
        }
    }
}
