<?php
namespace App\Services;

use App\Core\Database;

class RateLimitService {
    private const SMS_DAILY_LIMIT = 15;

    public function canSendSmsQuery(int $farmerId): bool {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM ai_messages
            WHERE farmer_id = :fid AND direction = 'in' AND channel IN ('sms', 'ussd')
              AND DATE(sent_at) = CURDATE()
        ");
        $stmt->execute([':fid' => $farmerId]);
        return (int)$stmt->fetchColumn() < self::SMS_DAILY_LIMIT;
    }

    public function dailyLimit(): int {
        return self::SMS_DAILY_LIMIT;
    }
}
