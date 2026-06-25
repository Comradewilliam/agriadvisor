<?php
namespace App\Services;

use App\Core\Database;
use App\Helpers\Logger;
use App\Services\SystemEventService;

/**
 * Outbound two-way SMS replies — inbound farmer texts to short code only.
 */
class SmsReplyService {
    /**
     * Process farmer text with AI/rules and reply via short code.
     */
    public static function sendChatReply(
        int $farmerId,
        string $phone,
        string $text,
        ?string $linkId = null,
        ?string $fromShortCode = null
    ): bool {
        $phone = \App\Helpers\Sanitizer::normalizePhone($phone);
        $at = new AfricasTalkingService();
        $rateLimit = new RateLimitService();

        if (!$rateLimit->canSendSmsQuery($farmerId)) {
            $at->sendTwoWayReply(
                $phone,
                'Umefikia kikomo cha maswali ' . $rateLimit->dailyLimit() . ' kwa siku. Jaribu kesho.',
                $linkId,
                $fromShortCode
            );
            return true;
        }

        try {
            $result = (new FarmerChatService())->handleMessage($farmerId, $text, 'sms');
            $atResult = $at->sendTwoWayReply($phone, $result['reply'], $linkId, $fromShortCode);

            if (!AfricasTalkingService::isSuccess($atResult)) {
                Logger::error('SMS reply failed', ['farmer_id' => $farmerId, 'phone' => $phone, 'response' => $atResult]);
            SystemEventService::record('error', 'sms', 'reply_failed', [
                'farmer_id' => $farmerId,
                'phone'     => $phone,
                'channel'   => 'sms',
                'response'  => $atResult['message'] ?? 'unknown',
            ]);
                return false;
            }

            $outMsgId = $result['out_msg_id'] ?? 0;
            $providerId = $atResult['SMSMessageData']['Recipients'][0]['messageId'] ?? null;
            if ($providerId && $outMsgId) {
                Database::getInstance()->getConnection()->prepare(
                    'UPDATE ai_messages SET provider_message_id = :pid WHERE id = :id'
                )->execute([':pid' => $providerId, ':id' => $outMsgId]);
            }

            Logger::info('SMS reply sent', [
                'farmer_id'  => $farmerId,
                'phone'      => $phone,
                'escalated'  => $result['escalated'] ?? false,
                'message_id' => $providerId,
            ]);
            SystemEventService::record('info', 'sms', 'reply_sent', [
                'farmer_id'    => $farmerId,
                'phone'        => $phone,
                'channel'      => 'sms',
                'message_id'   => $providerId,
                'escalated'    => $result['escalated'] ?? false,
                'reply_preview'=> mb_substr($result['reply'], 0, 120),
            ]);
            return true;
        } catch (\Throwable $e) {
            Logger::error('SMS reply handler failed', ['farmer_id' => $farmerId, 'error' => $e->getMessage()]);
            $at->sendTwoWayReply($phone, 'Samahani, hitilafu imetokea. Jaribu tena baada ya muda mfupi.', $linkId, $fromShortCode);
            return false;
        }
    }
}
