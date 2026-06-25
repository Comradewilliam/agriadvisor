<?php
namespace App\Controllers;

use App\Models\Farmer;
use App\Core\Database;
use App\Services\AfricasTalkingService;
use App\Services\SmsReplyService;
use App\Services\SystemEventService;
use App\Services\WebhookValidator;
use App\Services\RateLimitService;
use App\Helpers\Sanitizer;
use App\Helpers\Logger;

class SmsController {

    public function inbound() {
        header('Content-Type: text/plain; charset=utf-8');

        $payload = array_merge($_POST, $_GET);
        $linkId = trim((string)($payload['linkId'] ?? ''));
        $inboundTo = trim((string)($payload['to'] ?? ''));

        Logger::info('SMS inbox callback', [
            'from'   => $payload['from'] ?? '',
            'to'     => $inboundTo,
            'linkId' => $linkId,
            'text'   => mb_substr((string)($payload['text'] ?? ''), 0, 80),
        ]);
        SystemEventService::record('info', 'sms', 'inbox', [
            'phone'   => $payload['from'] ?? '',
            'channel' => 'sms',
            'message' => mb_substr((string)($payload['text'] ?? ''), 0, 200),
            'link_id' => $linkId,
            'shortcode' => $inboundTo,
        ]);

        if (!WebhookValidator::verifyIncomingRequest($payload)) {
            Logger::warning('SMS inbox webhook signature invalid');
            http_response_code(403);
            echo 'BAD';
            return;
        }

        $from = Sanitizer::normalizePhone($payload['from'] ?? '');
        $text = Sanitizer::textarea($payload['text'] ?? '', 500);

        if ($from === '' || $text === '') {
            http_response_code(200);
            echo 'GOOD';
            return;
        }

        $atService   = new AfricasTalkingService();
        $farmerModel = new Farmer();
        $farmer      = $farmerModel->findByPhone($from);

        if (!$farmer) {
            $result = $atService->sendTwoWayReply(
                $from,
                'Simu hii haijasajiliwa kwenye mfumo wa Agri-Advisory. Jiandikishe kwanza kupitia USSD.',
                $linkId,
                $inboundTo
            );
            if (!AfricasTalkingService::isSuccess($result)) {
                Logger::error('SMS unregistered reply failed', ['from' => $from, 'response' => $result]);
            }
            http_response_code(200);
            echo 'GOOD';
            return;
        }

        $rateLimit = new RateLimitService();
        if (!$rateLimit->canSendSmsQuery((int)$farmer['id'])) {
            $atService->sendTwoWayReply(
                $from,
                'Umefikia kikomo cha maswali ' . $rateLimit->dailyLimit() . ' kwa siku. Jaribu kesho.',
                $linkId,
                $inboundTo
            );
            http_response_code(200);
            echo 'GOOD';
            return;
        }

        try {
            if (!SmsReplyService::sendChatReply((int)$farmer['id'], $from, $text, $linkId, $inboundTo)) {
                Logger::error('SMS two-way reply NOT delivered', [
                    'farmer_id' => $farmer['id'],
                    'from'      => $from,
                    'shortcode' => $inboundTo,
                ]);
            }
        } catch (\Throwable $e) {
            Logger::error('SMS inbound handler failed', ['phone' => $from, 'error' => $e->getMessage()]);
            $fail = $atService->sendTwoWayReply(
                $from,
                'Samahani, hitilafu imetokea. Jaribu tena baada ya muda mfupi.',
                $linkId,
                $inboundTo
            );
            if (!AfricasTalkingService::isSuccess($fail)) {
                Logger::error('SMS error fallback also failed', ['response' => $fail]);
            }
        }

        http_response_code(200);
        echo 'GOOD';
    }

    public function delivery() {
        header('Content-Type: text/plain; charset=utf-8');

        $payload = array_merge($_POST, $_GET);
        if (!WebhookValidator::verifyIncomingRequest($payload)) {
            http_response_code(403);
            echo 'BAD';
            return;
        }

        $db = Database::getInstance()->getConnection();
        $id = trim($payload['id'] ?? '');
        $status = strtolower(trim($payload['status'] ?? ''));

        if ($id !== '' && $status !== '') {
            $updateStmt = $db->prepare('UPDATE ai_messages SET delivery_status = :status WHERE provider_message_id = :id');
            $updateStmt->execute([':status' => $status, ':id' => $id]);

            if ($updateStmt->rowCount() === 0) {
                $updateStmt = $db->prepare('UPDATE officer_messages SET delivery_status = :status WHERE provider_message_id = :id');
                $updateStmt->execute([':status' => $status, ':id' => $id]);
            }

            Logger::info('SMS delivery report', [
                'id'             => $id,
                'status'         => $status,
                'phone'          => $payload['phoneNumber'] ?? ($payload['to'] ?? ''),
                'failure_reason' => $payload['failureReason'] ?? ($payload['reason'] ?? null),
                'network_code'   => $payload['networkCode'] ?? null,
                'retry_count'    => $payload['retryCount'] ?? null,
            ]);
            SystemEventService::record(
                ($status === 'failed' || $status === 'rejected') ? 'error' : 'info',
                'delivery',
                'report',
                [
                    'phone'          => $payload['phoneNumber'] ?? ($payload['to'] ?? ''),
                    'channel'        => 'sms',
                    'message_id'     => $id,
                    'status'         => $status,
                    'failure_reason' => $payload['failureReason'] ?? ($payload['reason'] ?? null),
                    'network_code'   => $payload['networkCode'] ?? null,
                ]
            );

            if ($status === 'failed' || $status === 'rejected') {
                Logger::error('SMS delivery FAILED at telco', [
                    'id'             => $id,
                    'phone'          => $payload['phoneNumber'] ?? '',
                    'failure_reason' => $payload['failureReason'] ?? 'unknown',
                    'raw'            => $payload,
                ]);
            }
        }

        http_response_code(200);
        echo 'GOOD';
    }

    /** GET /internal/sms/diagnose?token=SECRET&phone=+255... — run via web PHP (has curl). */
    public function diagnose(): void {
        header('Content-Type: application/json; charset=utf-8');

        $token = getenv('USSD_ASYNC_SECRET') ?: '';
        if ($token === '' || !hash_equals($token, (string)($_GET['token'] ?? ''))) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'forbidden']);
            return;
        }

        if (!extension_loaded('curl')) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'curl extension not loaded in web PHP']);
            return;
        }

        $phone = Sanitizer::toInternational((string)($_GET['phone'] ?? '+255616363951'));
        $at = new AfricasTalkingService();

        $oneWay = $at->sendOneWay($phone, 'Agri-Advisory one-way test ' . date('H:i:s'));
        $twoWay = $at->sendTwoWayReply($phone, 'Agri-Advisory two-way test ' . date('H:i:s'));

        echo json_encode([
            'ok'          => true,
            'phone'       => $phone,
            'short_code'  => $at->getShortCode(),
            'one_way'     => $oneWay,
            'two_way'     => $twoWay,
            'one_ok'      => AfricasTalkingService::isSuccess($oneWay),
            'two_ok'      => AfricasTalkingService::isSuccess($twoWay),
            'note'        => 'If ok=true but no SMS on phone, register number in AT Sandbox → Phone Numbers',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
