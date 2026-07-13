<?php
namespace App\Services;

use App\Controllers\UssdController;
use App\Core\Database;

/**
 * Invokes UssdController::handle() with simulated Africa's Talking POST payloads.
 */
class UssdTestHarness
{
    public static function dispatch(string $sessionId, string $phone, string $text = ''): array
    {
        $_POST = [
            'sessionId'   => $sessionId,
            'phoneNumber' => $phone,
            'text'        => $text,
            'serviceCode' => '*384*123#',
        ];

        ob_start();
        $started = microtime(true);
        (new UssdController())->handle();
        $body = (string) ob_get_clean();
        $elapsedMs = (int) round((microtime(true) - $started) * 1000);

        $type = 'UNKNOWN';
        if (str_starts_with($body, 'CON ')) {
            $type = 'CON';
        } elseif (str_starts_with($body, 'END ')) {
            $type = 'END';
        } elseif ($body === 'CON' || str_starts_with($body, 'CON')) {
            $type = 'CON';
        } elseif ($body === 'END' || str_starts_with($body, 'END')) {
            $type = 'END';
        }

        return [
            'body'       => $body,
            'type'       => $type,
            'elapsed_ms' => $elapsedMs,
            'length'     => mb_strlen($body),
        ];
    }

    public static function sessionRow(string $sessionId): ?array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM ussd_sessions WHERE session_id = ? LIMIT 1');
        $stmt->execute([$sessionId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function uniqueSessionId(string $prefix = 'test'): string
    {
        return $prefix . '-' . bin2hex(random_bytes(8));
    }
}
