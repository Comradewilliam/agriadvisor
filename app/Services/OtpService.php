<?php

namespace App\Services;

use App\Core\Database;
use App\Helpers\Sanitizer;

class OtpService
{
    private $db;
    private const EXPIRY_MINUTES = 5;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Generate a 6-digit OTP, store as plain text (one row per phone, replaces previous).
     */
    public function generateToken(string $phone): string
    {
        $phone = Sanitizer::normalizePhone($phone);
        $token = sprintf('%06d', random_int(0, 999999));

        // Remove any stale rows for this phone, then insert fresh OTP
        $this->db->prepare('DELETE FROM otp_tokens WHERE phone = ?')->execute([$phone]);

        $stmt = $this->db->prepare("
            INSERT INTO otp_tokens (phone, token_hash, expires_at, used_at)
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), NULL)
        ");
        $stmt->execute([$phone, $token, self::EXPIRY_MINUTES]);

        return $token;
    }

    /**
     * Verify OTP: must match stored plain text, not used, and not expired (5 min window).
     */
    public function verifyToken(string $phone, string $token): bool
    {
        $phone = Sanitizer::normalizePhone($phone);
        $token = preg_replace('/\D/', '', trim($token));
        if (strlen($token) < 4 || strlen($token) > 6) {
            return false;
        }
        $token = str_pad($token, 6, '0', STR_PAD_LEFT);

        $stmt = $this->db->prepare("
            SELECT id FROM otp_tokens
            WHERE phone = ?
              AND token_hash = ?
              AND used_at IS NULL
              AND expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([$phone, $token]);

        if (!$stmt->fetch()) {
            return false;
        }

        $this->db->prepare('UPDATE otp_tokens SET used_at = NOW() WHERE phone = ?')->execute([$phone]);
        return true;
    }

    public function expiryMinutes(): int
    {
        return self::EXPIRY_MINUTES;
    }
}
