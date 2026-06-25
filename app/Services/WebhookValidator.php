<?php
namespace App\Services;

/**
 * Africa's Talking webhook signature validation.
 * Set AT_SKIP_WEBHOOK_VERIFY=1 in .env for local/sandbox testing.
 */
class WebhookValidator {

    public static function verifyIncomingRequest(array $post): bool {
        if (getenv('AT_SKIP_WEBHOOK_VERIFY') === '1' || getenv('AT_SKIP_WEBHOOK_VERIFY') === 'true') {
            return true;
        }

        $apiKey = getenv('AT_API_KEY') ?: '';
        if ($apiKey === '') {
            return false;
        }

        $signature = $_SERVER['HTTP_X_AT_SIGNATURE'] ?? ($post['signature'] ?? ($post['hash'] ?? ''));
        if ($signature === '') {
            return false;
        }

        $payload = self::buildSigningString($post);
        $expected = base64_encode(hash_hmac('sha256', $payload, $apiKey, true));

        return hash_equals($expected, $signature) || hash_equals($expected, base64_decode($signature, true) ?: '');
    }

    private static function buildSigningString(array $post): string {
        ksort($post);
        $parts = [];
        foreach ($post as $key => $value) {
            if (in_array($key, ['signature', 'hash'], true)) {
                continue;
            }
            $parts[] = $key . (string)$value;
        }
        return implode('', $parts);
    }
}
