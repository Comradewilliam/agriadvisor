<?php

namespace App\Services;

use App\Core\Database;
use App\Helpers\Logger;
use App\Models\Farmer;
use App\Models\ChatThread;
use App\Services\SystemEventService;

/**
 * Shared farmer chat logic for web and SMS channels.
 * Handles officer/visit lookups, KB-augmented AI advisory, and thread continuity.
 */
class FarmerChatService
{
    private $db;
    private RagService $rag;
    private OpenAIService $openAI;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->rag = new RagService();
        $this->openAI = new OpenAIService();
    }

    /**
     * Process an inbound farmer message and return reply metadata.
     *
     * @return array{reply: string, confidence: string, escalated: bool, in_msg_id: int, thread_id: ?int}
     */
    public function handleMessage(int $farmerId, string $text, string $channel = 'web', ?int $threadId = null): array
    {
        $text = trim($text);
        $dbChannel = $this->dbChannel($channel);
        $farmerModel = new Farmer();
        $farmer = $farmerModel->findWithDetails($farmerId);

        $threadModel = new ChatThread();
        if ($channel === 'web') {
            $threadId = $this->resolveWebThreadId($threadModel, $farmerId, $threadId, $text);
        }

        $historyLimit = $channel === 'ussd' ? 4 : 20;
        $history = $this->loadThreadHistory($farmerId, $dbChannel, $historyLimit, $threadId);

        $this->db->prepare("
            INSERT INTO ai_messages (farmer_id, thread_id, direction, channel, content, sent_at)
            VALUES (?, ?, 'in', ?, ?, NOW())
        ")->execute([$farmerId, $threadId, $dbChannel, $text]);
        $inMsgId = (int)$this->db->lastInsertId();

        if ($threadId) {
            $threadModel->touch($threadId);
        }

        $districtId = null;
        $wardId = $farmer['ward_id'] ?? null;
        if ($wardId) {
            $distStmt = $this->db->prepare('SELECT district_id FROM wards WHERE id = ?');
            $distStmt->execute([$wardId]);
            $districtId = (int)($distStmt->fetchColumn() ?: 0) ?: null;
        }

        if ($this->isOfficerQuery($text)) {
            $reply = OfficerContactService::buildOfficerChatReply($wardId);
            return $this->saveOutbound($farmerId, $dbChannel, $reply, 'high', false, $inMsgId, $threadId);
        }

        if ($this->isVisitQuery($text)) {
            $reply = $this->buildVisitReply($farmerId);
            return $this->saveOutbound($farmerId, $dbChannel, $reply, 'high', false, $inMsgId, $threadId);
        }

        $isAgricultural = $this->rag->isAgriculturalQuery($text)
            || $this->isThreadContinuation($history);

        if (!$isAgricultural) {
            $reply = 'Samahani, mimi ni msaidizi wa kilimo tu. Naweza kukusaidia tu kuhusu maswala ya kilimo, mazao, mazingira ya kilimo, na maswala yanayohusiana na kilimo. Tafadhali nipe swali kuhusu kilimo!';
            return $this->saveOutbound($farmerId, $dbChannel, $reply, 'low', false, $inMsgId, $threadId);
        }

        $primaryCropId = $this->farmerPrimaryCropId($farmer);
        $kbResults = $this->rag->search($text, $primaryCropId, $districtId);

        // USSD: KB + Swahili tips only — no AI (OpenRouter exceeds telco ~30s timeout).
        if ($channel === 'ussd') {
            return $this->answerUssdAdvisory(
                $farmerId,
                $farmer,
                $text,
                $kbResults,
                $dbChannel,
                $inMsgId,
                $threadId
            );
        }

        if (empty($kbResults) && $primaryCropId) {
            $kbResults = $this->rag->search($text, null, $districtId);
        }

        $kbContext = $this->rag->buildContextFromResults($kbResults);
        $farmerCtx = $this->rag->getFarmerContext($farmerId);
        $season = $this->getTanzaniaSeason();

        $maxTokens = in_array($channel, ['sms'], true) ? 180 : 320;
        $smsRule = $channel === 'sms'
            ? "8. Weka jibu chini ya herufi 155 kwa SMS/USSD.\n"
            : "8. Unaweza kutoa majibu ya kina zaidi kwa mazungumzo ya wavuti.\n";

        $kbNote = $kbContext
            ? "MAARIFA YA KUMBUKUMBU (tumia kama marejeleo ya ziada, si lazima yawe kwenye DB ili ujibu):\n{$kbContext}\n"
            : "Hakuna entry maalum ya Knowledge Base kwa swali hili — jibu kwa ujuzi wako wa jumla wa kilimo Tanzania.\n";

        $systemPrompt = <<<PROMPT
Wewe ni BwanaShamba, msaidizi wa AI wa kilimo kwenye mfumo wa Agri-Advisory nchini Tanzania.
Mkulima anauliza KISWAHILI. Maarifa ya Knowledge Base yanaweza kuwa ENGLISH — tafsiri na utumie kwa ushauri wa vitendo.Jibu lugha rahisi ya kila siku.

WAJIBU WAKO:
1. Jibu KISWAHILI tu — toa ushauri wa vitendo kuhusu shamba, mimea, mbolea, udongo, mvua, na mavuno.
2. Knowledge Base (KB) ni marejeleo ya ziada tu — SI LAZIMA kuwa na jibu kwenye DB ili ujibu. Tumia ujuzi wako wa kilimo pia.
3. Ikiwa KB ina taarifa zinazofaa, zichanganye na ushauri wako; usisite kujibu maswali ya kawaida ya kilimo.
4. Endelea na mada ya mazungumzo yaliyopita ikiwa mkulima anaendelea kuuliza kuhusu hilo hilo.
5. Usijibu maswala yasiyo ya kilimo.
6. Andika "ESCALATE:" tu kwa masuala magumu sana yanayohitaji uchunguzi wa afisa shambani (si kwa maswali ya kawaida ya kilimo).
7. Wewe si tu kujibu maswali — uwe mshauri wa kuwasaidia mkulima kufanya maamuzi mazuri kuhusu shamba lake.
{$smsRule}
Msimu wa sasa: {$season}
{$farmerCtx}
{$kbNote}
PROMPT;

        $messages = $this->buildOpenAIMessages($history, $text);
        $result = $this->openAI->generateWithHistory($systemPrompt, $messages, $maxTokens);
        $aiText = trim($result['response'] ?? '');
        $apiFailed = !empty($result['error']) || $aiText === '';

        if ($apiFailed) {
            Logger::warning('OpenRouter failed, trying KB fallback', [
                'farmer_id' => $farmerId,
                'error'     => $result['error'] ?? 'empty',
            ]);
            SystemEventService::record('warning', 'ai', 'openrouter_failed', [
                'farmer_id' => $farmerId,
                'channel'   => $channel,
                'error'     => $result['error'] ?? 'empty',
            ]);

            $direct = $this->rag->getDirectAnswerFromResults($kbResults);
            if (!$direct['found']) {
                $direct = $this->rag->getSupplementalAnswerFromResults($kbResults);
            }
            if (!$direct['found']) {
                $direct = $this->rag->getAnyAnswerFromResults($kbResults);
            }
            if ($direct['found']) {
                return $this->saveOutbound(
                    $farmerId,
                    $dbChannel,
                    $this->compactReply($direct['answer']),
                    'medium',
                    false,
                    $inMsgId,
                    $threadId
                );
            }

            if ($channel === 'ussd') {
                $offline = $this->buildUssdOfflineAdvice($text, $farmer);
                if ($offline !== '') {
                    Logger::info('USSD offline advice', ['farmer_id' => $farmerId]);
                    return $this->saveOutbound($farmerId, $dbChannel, $offline, 'low', false, $inMsgId, $threadId);
                }
            }

            $firstName = explode(' ', $farmer['name'] ?? 'Rafiki')[0];
            $reply = "Samahani {$firstName}, huduma ya AI ina shughuli nyingi sasa hivi na nimejaribu tena bila mafanikio. Tafadhali subiri dakika moja kisha ujaribu tena, au wasiliana na afisa wako wa kilimo kwa msaada wa haraka.";
            return $this->saveOutbound($farmerId, $dbChannel, $reply, 'low', false, $inMsgId, $threadId);
        }

        if (str_starts_with(strtoupper($aiText), 'ESCALATE')) {
            $firstName = explode(' ', $farmer['name'] ?? 'Rafiki')[0];
            $reply = "Samahani {$firstName}, swali hili linahitaji uchunguzi wa karibu na afisa wa kilimo. Nimelifikisha, utapata majibu hivi karibuni!";
            $out = $this->saveOutbound($farmerId, $dbChannel, $reply, 'low', true, $inMsgId, $threadId);
            $this->createEscalation($inMsgId, $wardId);
            return $out;
        }

        if ($channel === 'sms') {
            $aiText = mb_substr($aiText, 0, 155);
        }

        Logger::info('Farmer chat reply generated', [
            'farmer_id' => $farmerId,
            'channel' => $channel,
            'kb_matches' => count($kbResults),
        ]);
        SystemEventService::record('info', 'ai', 'reply_generated', [
            'farmer_id'  => $farmerId,
            'channel'    => $channel,
            'kb_matches' => count($kbResults),
        ]);

        return $this->saveOutbound($farmerId, $dbChannel, $aiText, 'high', false, $inMsgId, $threadId);
    }

    /** USSD advisory — instant KB/offline Swahili (no AI). */
    private function answerUssdAdvisory(
        int $farmerId,
        array $farmer,
        string $text,
        array $kbResults,
        string $dbChannel,
        int $inMsgId,
        ?int $threadId
    ): array {
        $kbReply = $this->replyFromKbForUssd($kbResults, $text, $farmer, $farmerId, $dbChannel, $inMsgId, $threadId);
        if ($kbReply !== null) {
            Logger::info('USSD KB answer', ['farmer_id' => $farmerId, 'kb_matches' => count($kbResults)]);
            SystemEventService::record('info', 'ussd', 'kb_answer', [
                'farmer_id'  => $farmerId,
                'channel'    => 'ussd',
                'kb_matches' => count($kbResults),
            ]);
            return $kbReply;
        }

        $offline = $this->buildUssdOfflineAdvice($text, $farmer);
        Logger::info('USSD offline advice', ['farmer_id' => $farmerId, 'kb_matches' => count($kbResults)]);
        SystemEventService::record('info', 'ussd', 'offline_advice', [
            'farmer_id'  => $farmerId,
            'channel'    => 'ussd',
            'kb_matches' => count($kbResults),
        ]);
        return $this->saveOutbound($farmerId, $dbChannel, $offline, 'medium', false, $inMsgId, $threadId);
    }

    private function farmerPrimaryCropId(array $farmer): ?int
    {
        if (!empty($farmer['crops'][0]['crop_id'])) {
            return (int)$farmer['crops'][0]['crop_id'];
        }
        if (!empty($farmer['crops'][0]['id'])) {
            return (int)$farmer['crops'][0]['id'];
        }
        return null;
    }

    /** KB hit for USSD — always return Swahili text farmers can read. */
    private function replyFromKbForUssd(
        array $kbResults,
        string $query,
        array $farmer,
        int $farmerId,
        string $dbChannel,
        int $inMsgId,
        ?int $threadId
    ): ?array {
        if ($kbResults === []) {
            return null;
        }

        $swahili = $this->buildUssdOfflineAdvice($query, $farmer);
        $generic = str_contains($swahili, 'Fuata kalenda ya kilimo');
        if (!$generic) {
            return $this->saveOutbound($farmerId, $dbChannel, $swahili, 'high', false, $inMsgId, $threadId);
        }

        $top = $kbResults[0];
        $title = (string)($top['title'] ?? '');
        $solution = trim((string)($top['solution'] ?? ''));
        if ($solution === '') {
            return null;
        }

        if (preg_match('/planting|timing|when/i', $title . ' ' . $solution)) {
            $timing = $this->buildUssdOfflineAdvice($query, $farmer);
            if (!str_contains($timing, 'Fuata kalenda')) {
                return $this->saveOutbound($farmerId, $dbChannel, $timing, 'high', false, $inMsgId, $threadId);
            }
        }

        $crop = trim((string)($top['crop_name'] ?? ''));
        $intro = $crop !== '' ? "Ushauri wa {$crop}: " : 'Ushauri: ';
        return $this->saveOutbound(
            $farmerId,
            $dbChannel,
            $this->compactReply($intro . $solution),
            'medium',
            false,
            $inMsgId,
            $threadId
        );
    }

    /** Try KB direct/supplemental answer (SMS/web). */
    private function replyFromKb(
        array $kbResults,
        int $farmerId,
        string $dbChannel,
        int $inMsgId,
        ?int $threadId
    ): ?array {
        $direct = $this->rag->getDirectAnswerFromResults($kbResults);
        if ($direct['found']) {
            return $this->saveOutbound($farmerId, $dbChannel, $this->compactReply($direct['answer']), 'high', false, $inMsgId, $threadId);
        }
        $supplemental = $this->rag->getSupplementalAnswerFromResults($kbResults);
        if ($supplemental['found']) {
            return $this->saveOutbound($farmerId, $dbChannel, $this->compactReply($supplemental['answer']), 'medium', false, $inMsgId, $threadId);
        }
        $any = $this->rag->getAnyAnswerFromResults($kbResults);
        if ($any['found']) {
            return $this->saveOutbound($farmerId, $dbChannel, $this->compactReply($any['answer']), 'medium', false, $inMsgId, $threadId);
        }
        return null;
    }

    private function compactReply(string $text): string
    {
        $text = preg_replace('/^Kuhusiana na swali lako:\s*/iu', '', trim($text));
        $text = trim(preg_replace('/\s+/', ' ', str_replace(["\r\n", "\r", "\n"], ' ', $text)) ?? $text);
        return mb_strlen($text) > 155 ? mb_substr($text, 0, 152) . '...' : $text;
    }

    /** Short crop tips when OpenRouter is unavailable (USSD). */
    private function buildUssdOfflineAdvice(string $text, array $farmer): string
    {
        $lower = mb_strtolower($text);
        $tips = [
            'mahindi'  => 'Mahindi: panda mwanzo wa masika (Machi-Juni) au vuli (Okt-Des) mvua ikianza. Panda mapema, nafasi 75cm, DAP kupanda, NPK kuchipua.',
            'mihogo'   => 'Mihogo: panda vipande safi vilivyochaguliwa, ondoa magugu, hakikisha mvua inatosha, vuna baada ya miezi 9-12.',
            'maharage' => 'Maharage: panda mvua, changanya na DAP, palilia mara mbili, dhibiti wadudu wa majani na magugu.',
            'mpunga'   => 'Mpunga: panda kwenye shamba lenye maji, tumia mbegu safi, dhibiti mchele na magugu, hakikisha unyevu wa kutosha.',
            'viazi'    => 'Viazi: panda vipande vilivyochaguliwa, ondoa magugu, dhibiti wadudu wa majani, vuna baada ya miezi 3-4.',
            'ndizi'    => 'Ndizi: panda shina safi, ondoa majani mabovu, tumia mbolea ya potasiamu, epuka maji yasiyosimama.',
            'kahawa'   => 'Kahawa: panda kwenye udongo wenye ujirai, punguza kivuli kidogo, dhibiti magonjwa ya majani na wadudu.',
            'udongo'   => 'Udongo: chunguza udongo kwanza, ongeza mbolea ya asili, epuka kulima wakati wa mvua nyingi sana.',
            'mbolea'   => 'Mbolea: tumia kulingana na udongo, DAP kupanda, CAN/NPK kukua, usizidi kiwango kilichopendekezwa.',
            'mvua'     => 'Mvua: panda kabla ya mvua, tumia mulching kuhifadhi unyevu, epuka kulima udongo mvua nyingi.',
        ];

        foreach ($tips as $keyword => $tip) {
            if (str_contains($lower, $keyword)) {
                return $this->compactReply($tip);
            }
        }

        if ((str_contains($lower, 'lini') || str_contains($lower, 'wakati') || str_contains($lower, 'kupanda') || str_contains($lower, 'panda'))
            && (str_contains($lower, 'mahindi') || str_contains($lower, 'maize'))) {
            return $this->compactReply($tips['mahindi']);
        }

        if (!empty($farmer['crops'][0]['name_sw'])) {
            $crop = mb_strtolower((string)$farmer['crops'][0]['name_sw']);
            foreach ($tips as $keyword => $tip) {
                if (str_contains($crop, $keyword) || str_contains($keyword, $crop)) {
                    return $this->compactReply($tip);
                }
            }
        }

        return $this->compactReply(
            'Msimu: ' . $this->getTanzaniaSeason()
            . '. Fuata kalenda ya kilimo, tumia mbolea sahihi, na wasiliana na afisa (chagua 3).'
        );
    }

    /** Map logical channel to DB ENUM (ai_messages.channel may be sms|web|ussd). */
    private function dbChannel(string $channel): string
    {
        if ($channel !== 'ussd') {
            return $channel;
        }
        static $ussdOk = null;
        if ($ussdOk === null) {
            $row = $this->db->query("SHOW COLUMNS FROM ai_messages LIKE 'channel'")->fetch(\PDO::FETCH_ASSOC);
            $ussdOk = str_contains((string)($row['Type'] ?? ''), 'ussd');
        }
        return $ussdOk ? 'ussd' : 'sms';
    }

    private function isOfficerQuery(string $text): bool
    {
        $lower = mb_strtolower($text);
        $keywords = [
            'afisa', 'ofisa', 'ward officer', 'ofisa wa kijiwa', 'ofisa wa ward',
            'afisa wangu', 'namba ya afisa', 'simu ya afisa', 'kuwasiliana na afisa',
            'wasiliana na afisa', 'afisa wa kilimo',
        ];
        foreach ($keywords as $kw) {
            if (str_contains($lower, $kw)) {
                return true;
            }
        }
        return false;
    }

    private function isVisitQuery(string $text): bool
    {
        $lower = mb_strtolower($text);
        $keywords = [
            'ziara', 'visit', 'safari', 'scheduled', 'kuandaliwa', 'watakuja', 'watafika',
            'ratiba ya ziara', 'ratiba', 'matembezi', 'atakuja', 'kututembelea', 'kunitembelea',
            'anakuja', 'watakuja kunitembelea', 'watakuja kututembelea', 'tarehe ya ziara',
            'lini anakuja', 'lini watakuja', 'tembelea shamba',
        ];
        foreach ($keywords as $kw) {
            if (str_contains($lower, $kw)) {
                return true;
            }
        }
        return false;
    }

    private function buildVisitReply(int $farmerId): string
    {
        $visitStmt = $this->db->prepare("
            SELECT v.scheduled_at, v.reason, u.name AS officer_name
            FROM visits v
            LEFT JOIN users u ON u.id = v.officer_id
            WHERE v.farmer_id = ? AND v.scheduled_at >= NOW()
            ORDER BY v.scheduled_at ASC
            LIMIT 3
        ");
        $visitStmt->execute([$farmerId]);
        $visits = $visitStmt->fetchAll();

        if (count($visits) > 0) {
            $reply = "Ziara zifuatazo zimeandaliwa:\n";
            foreach ($visits as $i => $v) {
                $num = $i + 1;
                $date = date('d/m/Y H:i', strtotime($v['scheduled_at']));
                $officer = $v['officer_name'] ?: 'Afisa wa kilimo';
                $reply .= "{$num}. {$date} na {$officer}: {$v['reason']}\n";
            }
            return trim($reply);
        }

        $reqStmt = $this->db->prepare("
            SELECT preferred_date, status, request_reason
            FROM visit_requests
            WHERE farmer_id = ? AND status IN ('pending', 'scheduled')
            ORDER BY requested_at DESC
            LIMIT 2
        ");
        $reqStmt->execute([$farmerId]);
        $requests = $reqStmt->fetchAll();

        if (count($requests) > 0) {
            $reply = "Hakuna ziara iliyothibitishwa bado, lakini una maombi yafuatayo:\n";
            foreach ($requests as $i => $r) {
                $num = $i + 1;
                $date = $r['preferred_date'] ? date('d/m/Y', strtotime($r['preferred_date'])) : 'Tarehe haijathibitishwa';
                $reply .= "{$num}. Hali: {$r['status']}, tarehe iliyopendelewa: {$date}\n";
            }
            return trim($reply);
        }

        return 'Hakuna ziara zilizopo kuhusiana na shamba lako. Kama unahitaji ziara, unaweza kuomba, kutuma ombi la ziara kupitia dashibodi yako!';
    }

    private function resolveWebThreadId(ChatThread $threadModel, int $farmerId, ?int $threadId, string $text): int
    {
        if ($threadId && $threadModel->findForFarmer($threadId, $farmerId)) {
            return $threadId;
        }

        return $threadModel->create($farmerId, 'web', $text);
    }

    private function loadThreadHistory(int $farmerId, string $channel, int $limit = 20, ?int $threadId = null): array
    {
        if ($threadId) {
            $stmt = $this->db->prepare("
                SELECT direction, content
                FROM ai_messages
                WHERE farmer_id = ? AND channel = ? AND thread_id = ? AND direction IN ('in', 'out')
                ORDER BY sent_at DESC
                LIMIT ?
            ");
            $stmt->bindValue(1, $farmerId, \PDO::PARAM_INT);
            $stmt->bindValue(2, $channel, \PDO::PARAM_STR);
            $stmt->bindValue(3, $threadId, \PDO::PARAM_INT);
            $stmt->bindValue(4, $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return array_reverse($stmt->fetchAll(\PDO::FETCH_ASSOC));
        }

        $stmt = $this->db->prepare("
            SELECT direction, content
            FROM ai_messages
            WHERE farmer_id = ? AND channel = ? AND direction IN ('in', 'out')
            ORDER BY sent_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $farmerId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $channel, \PDO::PARAM_STR);
        $stmt->bindValue(3, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return array_reverse($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    private function isThreadContinuation(array $history): bool
    {
        if (count($history) < 2) {
            return false;
        }
        $recent = array_slice($history, -4);
        foreach ($recent as $msg) {
            if ($msg['direction'] === 'out' && !str_contains($msg['content'], 'msaidizi wa kilimo tu')) {
                return true;
            }
        }
        return false;
    }

    private function buildOpenAIMessages(array $history, string $currentText): array
    {
        $messages = [];
        foreach ($history as $msg) {
            if ($msg['direction'] === 'in') {
                $messages[] = ['role' => 'user', 'content' => $msg['content']];
            } else {
                $messages[] = ['role' => 'assistant', 'content' => $msg['content']];
            }
        }
        $messages[] = ['role' => 'user', 'content' => $currentText];
        return $messages;
    }

    private function saveOutbound(
        int $farmerId,
        string $channel,
        string $reply,
        string $confidence,
        bool $escalated,
        int $inMsgId,
        ?int $threadId = null
    ): array {
        $this->db->prepare("
            INSERT INTO ai_messages (farmer_id, thread_id, direction, channel, content, ai_confidence, escalated, sent_at)
            VALUES (?, ?, 'out', ?, ?, ?, ?, NOW())
        ")->execute([$farmerId, $threadId, $channel, $reply, $confidence, $escalated ? 1 : 0]);

        if ($threadId) {
            (new ChatThread())->touch($threadId);
        }

        return [
            'reply'      => $reply,
            'confidence' => $confidence,
            'escalated'  => $escalated,
            'in_msg_id'  => $inMsgId,
            'out_msg_id' => (int)$this->db->lastInsertId(),
            'thread_id'  => $threadId,
        ];
    }

    private function createEscalation(int $inMsgId, ?int $wardId): void
    {
        if (!$wardId) {
            return;
        }
        $this->db->prepare("
            INSERT INTO escalations (ai_message_id, assigned_officer_id, status, priority, escalated_at)
            SELECT ?, u.id, 'pending', 'normal', NOW()
            FROM users u
            JOIN officer_wards ow ON ow.officer_id = u.id
            WHERE ow.ward_id = ? AND u.role = 'ward_officer' AND u.is_active = 1
            LIMIT 1
        ")->execute([$inMsgId, $wardId]);
    }

    private function getTanzaniaSeason(): string
    {
        $month = (int)date('n');
        if ($month >= 3 && $month <= 6) {
            return 'Masika (Mvua Ndefu, Machi-Juni): Mahindi, maharage';
        }
        if ($month >= 10 && $month <= 12) {
            return 'Vuli (Mvua Fupi, Oktoba-Desemba): Maharage, njugu';
        }
        return 'Msimu wa Kiangazi (Kutayarisha shamba, uhifadhi)';
    }
}
