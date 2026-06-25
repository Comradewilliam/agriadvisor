<?php
namespace App\Services;

use App\Helpers\Logger;
use App\Helpers\Sanitizer;

/**
 * Africa's Talking SMS integration.
 *
 * Two-way (conversational): reply with `from` = your SHORT CODE so farmers can reply back.
 * One-way (broadcasts/OTP): optional alphanumeric sender via AT_BULK_SENDER_ID.
 *
 * AT dashboard setup:
 *  - SMS → Callback URLs → Incoming Messages  → https://yourdomain.com/sms
 *  - SMS → Callback URLs → Delivery Reports    → https://yourdomain.com/sms/delivery
 *  - SMS → Short Codes → create your sandbox/production short code
 *
 * @see https://developers.africastalking.com/docs/sms/sending
 */
class AfricasTalkingService {
    private string $username;
    private string $apiKey;
    private string $baseUrl;
    private string $shortCode;
    private ?string $bulkSenderId;

    public function __construct() {
        $config = require __DIR__ . '/../../config.php';
        $this->username = $config['apis']['africas_talking']['username'] ?? 'sandbox';
        $this->apiKey = $config['apis']['africas_talking']['api_key'] ?? '';
        $this->shortCode = trim(getenv('AT_SHORT_CODE') ?: '');
        $this->bulkSenderId = trim(getenv('AT_BULK_SENDER_ID') ?: '') ?: null;

        $sandbox = getenv('AT_SANDBOX') === '1'
            || getenv('AT_SANDBOX') === 'true'
            || $this->username === 'sandbox';
        $this->baseUrl = $sandbox
            ? 'https://api.sandbox.africastalking.com/version1/messaging'
            : 'https://api.africastalking.com/version1/messaging';
    }

    public function getShortCode(): string {
        return $this->shortCode;
    }

    /**
     * Reply on the same short code the farmer texted — enables two-way conversation.
     *
     * @param string|null $linkId Pass through from inbound webhook when available.
     * @param string|null $fromShortCode Override `from` (defaults to inbound `to` or AT_SHORT_CODE).
     */
    public function sendTwoWayReply(string $to, string $message, ?string $linkId = null, ?string $fromShortCode = null): array {
        $from = trim($fromShortCode ?? '') ?: $this->shortCode;
        if ($from === '') {
            Logger::error('AT two-way SMS failed: AT_SHORT_CODE not set in .env');
            return ['status' => 'error', 'message' => 'missing_short_code'];
        }

        $extra = ['from' => $from, 'bulkSMSMode' => '0'];
        if ($linkId !== null && $linkId !== '') {
            $extra['linkId'] = $linkId;
        }

        return $this->dispatch($to, $message, $extra, 'twoway');
    }

    /** One-way SMS (OTP, welcome, alerts, broadcasts). From AFRICASTKNG → farmer phone. */
    public function sendOneWay(string $to, string $message): array {
        $from = $this->bulkSenderId ?: 'AFRICASTKNG';
        return $this->dispatch($to, $message, ['from' => $from, 'bulkSMSMode' => '1'], 'oneway');
    }

    /** @deprecated Use sendTwoWayReply() or sendOneWay() explicitly. */
    public function sendSms(string $to, string $message): array {
        return $this->sendTwoWayReply($to, $message);
    }

    /**
     * @param array<string, string> $extra
     * @return array<string, mixed>
     */
    private function dispatch(string $to, string $message, array $extra, string $mode): array {
        if (!$this->apiKey) {
            Logger::error('Africa\'s Talking API key missing');
            return ['status' => 'error', 'message' => 'missing_api_key'];
        }

        $to = $this->formatRecipients($to);
        if ($to === '') {
            Logger::error('AT SMS invalid recipient');
            return ['status' => 'error', 'message' => 'invalid_recipient'];
        }

        $data = array_merge([
            'username' => $this->username,
            'to'       => $to,
            'message'  => $message,
        ], $extra);

        $ch = \curl_init($this->baseUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'apiKey: ' . $this->apiKey,
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT        => 60,
        ]);
        $this->configureCurlSsl($ch);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Logger::error('AT SMS curl error', ['error' => $error, 'to' => $to, 'mode' => $mode]);
            return ['status' => 'error', 'message' => $error];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            Logger::error('AT SMS invalid JSON', ['http' => $httpCode, 'body' => mb_substr((string)$response, 0, 500)]);
            return ['status' => 'error', 'message' => 'invalid_response', 'http_code' => $httpCode];
        }

        if (!self::isSuccess($decoded)) {
            Logger::error('AT SMS rejected', [
                'to'       => $to,
                'from'     => $extra['from'] ?? '(default)',
                'mode'     => $mode,
                'http'     => $httpCode,
                'response' => $decoded,
                'recipient_status' => $decoded['SMSMessageData']['Recipients'][0]['status'] ?? null,
                'status_code'      => $decoded['SMSMessageData']['Recipients'][0]['statusCode'] ?? null,
            ]);
        } else {
            $rec = $decoded['SMSMessageData']['Recipients'][0] ?? [];
            Logger::info('AT SMS sent', [
                'to'           => $to,
                'from'         => $extra['from'] ?? 'AFRICASTKNG(default)',
                'mode'         => $mode,
                'status'       => $rec['status'] ?? 'ok',
                'status_code'  => $rec['statusCode'] ?? null,
                'message_id'   => $rec['messageId'] ?? null,
                'msg_length'   => mb_strlen($message),
            ]);
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $result
     */
    public static function isSuccess(array $result): bool {
        if (($result['status'] ?? '') === 'error') {
            return false;
        }
        $recipients = $result['SMSMessageData']['Recipients'] ?? [];
        if ($recipients === []) {
            return false;
        }
        foreach ($recipients as $r) {
            $st = strtolower((string)($r['status'] ?? ''));
            if (in_array($st, ['success', 'sent', 'submitted', 'buffered'], true)) {
                return true;
            }
        }
        return false;
    }

    /** @return string Comma-separated E.164 numbers */
    private function formatRecipients(string $to): string {
        $parts = array_filter(array_map('trim', explode(',', $to)));
        $out = [];
        foreach ($parts as $p) {
            $intl = Sanitizer::toInternational($p);
            if ($intl !== '') {
                $out[] = $intl;
            }
        }
        return implode(',', $out);
    }

    private function configureCurlSsl(\CurlHandle $ch): void {
        $verify = getenv('AT_SSL_VERIFY');
        if ($verify === '0' || $verify === 'false') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            return;
        }
        if ($verify === '' && (getenv('AT_SANDBOX') === '1' || PHP_OS_FAMILY === 'Windows')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            return;
        }
        $ca = getenv('AT_CAINFO') ?: '';
        if ($ca !== '' && is_file($ca)) {
            curl_setopt($ch, CURLOPT_CAINFO, $ca);
        }
    }

    /**
     * Bulk one-way SMS (officer broadcasts, weather alerts).
     *
     * @param string[] $phones
     * @return array{sent: int, chunks: int, recipients: array}
     */
    public function sendBulkSms(array $phones, string $message, int $chunkSize = 100): array {
        $phones = array_values(array_filter(array_unique($phones)));
        $result = ['sent' => 0, 'chunks' => 0, 'recipients' => []];

        foreach (array_chunk($phones, $chunkSize) as $chunk) {
            if ($chunk === []) {
                continue;
            }
            $apiResult = $this->sendOneWay(implode(',', $chunk), $message);
            $result['chunks']++;
            foreach ($apiResult['SMSMessageData']['Recipients'] ?? [] as $r) {
                $result['recipients'][] = $r;
                if (in_array(strtolower($r['status'] ?? ''), ['success', 'sent', 'submitted', 'buffered'], true)) {
                    $result['sent']++;
                }
            }
        }

        Logger::info('Bulk SMS completed', ['chunks' => $result['chunks'], 'sent' => $result['sent'], 'total' => count($phones)]);
        return $result;
    }
}
