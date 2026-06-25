<?php
namespace App\Controllers;

use App\Models\Farmer;
use App\Models\Ward;
use App\Core\Database;
use App\Services\WebhookValidator;
use App\Services\WeatherService;
use App\Services\FarmerChatService;
use App\Services\OfficerContactService;
use App\Services\AutomatedAlertService;
use App\Services\RateLimitService;
use App\Services\SystemEventService;
use App\Helpers\Sanitizer;
use App\Helpers\Logger;

class UssdController {

    private const USSD_MAX_CHARS = 182;

    private ?array $sessionRow = null;

    public function handle() {
        if (!WebhookValidator::verifyIncomingRequest($_POST)) {
            header('Content-type: text/plain');
            echo 'END Unauthorized request.';
            return;
        }

        $sessionId   = $_POST['sessionId']   ?? '';
        $phoneNumber = Sanitizer::normalizePhone($_POST['phoneNumber'] ?? '');
        $text        = $_POST['text']        ?? '';

        $inputArray = ($text === '') ? [] : explode('*', $text);
        $level      = count($inputArray);

        $farmerModel = new Farmer();
        $farmer      = $phoneNumber !== '' ? $farmerModel->findByPhone($phoneNumber) : null;

        $this->initSession($sessionId, $farmer['id'] ?? null, $inputArray);

        try {
            if (!$farmer) {
                $response = $this->registrationFlow($phoneNumber, $inputArray, $level);
                if (!$farmer && $phoneNumber !== '') {
                    $farmer = $farmerModel->findByPhone($phoneNumber);
                }
            } else {
                [$menuInputs, $menuLevel] = $this->normalizeMenuInputs($inputArray, $level);
                $response = $this->mainMenu($farmer, $menuInputs, $menuLevel);
            }
        } catch (\Throwable $e) {
            Logger::error('USSD handle failed', [
                'phone' => $phoneNumber,
                'text'  => $text,
                'error' => $e->getMessage(),
            ]);
            SystemEventService::record('error', 'ussd', 'handle_failed', [
                'phone' => $phoneNumber,
                'message' => mb_substr($text, 0, 200),
                'error' => $e->getMessage(),
            ]);
            $response = 'END Hitilafu ya mfumo. Jaribu tena baada ya muda mfupi.';
        }

        $isEnd = str_starts_with($response, 'END');
        $this->finalizeSession($sessionId, $inputArray, $isEnd, $farmer['id'] ?? null);

        header('Content-Type: text/plain; charset=utf-8');
        echo $response;
    }

    private function fitUssd(string $text): string {
        $text = trim(preg_replace('/\s+/', ' ', str_replace(["\r\n", "\r", "\n"], ' ', $text)) ?? $text);
        if (mb_strlen($text) <= self::USSD_MAX_CHARS) {
            return $text;
        }
        $suffix = '... Tuma SMS 5852 kwa zaidi.';
        $max = self::USSD_MAX_CHARS - mb_strlen($suffix);
        return mb_substr($text, 0, max(1, $max)) . $suffix;
    }

    /**
     * @return array{0: string[], 1: int}
     */
    private function normalizeMenuInputs(array $inputs, int $level): array {
        if ($level >= 4 && count($inputs) >= 4
            && is_numeric($inputs[1] ?? '')
            && is_numeric($inputs[2] ?? '')) {
            $menuInputs = array_slice($inputs, 3);
            return [$menuInputs, count($menuInputs)];
        }
        return [$inputs, $level];
    }

    private function existingFarmerMenu(string $phone, Farmer $farmerModel, array $inputs, int $level): ?string {
        $existing = $farmerModel->findByPhone($phone);
        if (!$existing) {
            return null;
        }
        if ($level >= 4) {
            [$menuInputs, $menuLevel] = $this->normalizeMenuInputs($inputs, $level);
            return $this->mainMenu($existing, $menuInputs, $menuLevel);
        }
        return $this->mainMenu($existing, [], 0);
    }

    private function initSession(string $sessionId, ?int $farmerId, array $menuPath): void {
        if ($sessionId === '') return;

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM ussd_sessions WHERE session_id = ? LIMIT 1');
        $stmt->execute([$sessionId]);
        $this->sessionRow = $stmt->fetch() ?: null;

        if (!$this->sessionRow) {
            $ins = $db->prepare("
                INSERT INTO ussd_sessions (farmer_id, session_id, menu_path, start_time, completed)
                VALUES (:fid, :sid, :path, NOW(), 0)
            ");
            $ins->execute([
                ':fid'  => $farmerId,
                ':sid'  => $sessionId,
                ':path' => json_encode($menuPath),
            ]);
        } else {
            $upd = $db->prepare('UPDATE ussd_sessions SET menu_path = ?, farmer_id = COALESCE(farmer_id, ?) WHERE session_id = ?');
            $upd->execute([json_encode($menuPath), $farmerId, $sessionId]);
        }
    }

    private function finalizeSession(string $sessionId, array $menuPath, bool $ended, ?int $farmerId): void {
        if ($sessionId === '') return;

        $db = Database::getInstance()->getConnection();
        $db->prepare("
            UPDATE ussd_sessions
            SET menu_path = ?, farmer_id = COALESCE(farmer_id, ?), completed = ?, end_time = IF(? = 1, NOW(), end_time)
            WHERE session_id = ?
        ")->execute([
            json_encode($menuPath),
            $farmerId,
            $ended ? 1 : 0,
            $ended ? 1 : 0,
            $sessionId,
        ]);
    }

    private function registrationFlow(string $phone, array $inputs, int $level): string {
        $wardModel   = new Ward();
        $farmerModel = new Farmer();

        if ($existing = $this->existingFarmerMenu($phone, $farmerModel, $inputs, $level)) {
            return $existing;
        }

        switch ($level) {
            case 0:
                return "CON Karibu Agri-Advisory System!\nIngiza majina yako mawili (K.m. Ali Juma):";

            case 1:
                $wards = $wardModel->getAll('name ASC');
                $menu  = "CON Chagua Kata yako:\n";
                foreach ($wards as $i => $w) {
                    $menu .= ($i + 1) . '. ' . $w['name'] . "\n";
                }
                return rtrim($menu);

            case 2:
                $wardIdx = (int)$inputs[1] - 1;
                $wards   = $wardModel->getAll('name ASC');
                if (!isset($wards[$wardIdx])) {
                    return 'END Chaguo batili. Jaribu tena.';
                }

                $wardId = (int)$wards[$wardIdx]['id'];
                $db     = Database::getInstance()->getConnection();
                $stmt   = $db->prepare('SELECT * FROM villages WHERE ward_id = :wid ORDER BY name');
                $stmt->execute(['wid' => $wardId]);
                $villages = $stmt->fetchAll();

                $menu = "CON Chagua Kijiji:\n";
                foreach ($villages as $i => $v) {
                    $menu .= ($i + 1) . '. ' . $v['name'] . "\n";
                }
                return rtrim($menu);

            case 3:
                $fullName  = trim($inputs[0]);
                $parts     = explode(' ', $fullName, 2);
                $firstName = $parts[0] ?: 'N/A';
                $lastName  = $parts[1] ?? '';
                $wardIdx   = (int)$inputs[1] - 1;
                $villIdx   = (int)$inputs[2] - 1;

                $wards = $wardModel->getAll('name ASC');
                if (!isset($wards[$wardIdx])) {
                    return 'END Hitilafu. Jaribu tena.';
                }
                $wardId = (int)$wards[$wardIdx]['id'];

                $db       = Database::getInstance()->getConnection();
                $villStmt = $db->prepare('SELECT * FROM villages WHERE ward_id = :wid ORDER BY name');
                $villStmt->execute(['wid' => $wardId]);
                $villages = $villStmt->fetchAll();
                if (!isset($villages[$villIdx])) {
                    return 'END Hitilafu. Jaribu tena.';
                }
                $villageId = (int)$villages[$villIdx]['id'];

                $existing = $farmerModel->findByPhone($phone);
                if ($existing) {
                    $firstName = trim(explode(' ', trim($existing['name'] ?? ''))[0] ?: 'Mkulima');
                    return "END {$firstName}, tayari umesajiliwa. Piga tena USSD kwa menyu kuu.";
                }

                $farmerModel->create([
                    'first_name'       => htmlspecialchars($firstName),
                    'last_name'        => htmlspecialchars($lastName),
                    'phone'            => $phone,
                    'ward_id'          => $wardId,
                    'village_id'       => $villageId,
                    'registered_via'   => 'ussd',
                    'profile_complete' => 0
                ]);

                $farmer = $farmerModel->findByPhone($phone);
                if (!$farmer) {
                    return 'END Hitilafu ya usajili. Jaribu tena baada ya dakika chache.';
                }

                $farmerId = (int)$farmer['id'];
                (new AutomatedAlertService())->sendTriggeredAlerts('on_register', $farmerId);

                $firstName = trim(explode(' ', trim($farmer['name'] ?? ''))[0] ?: 'Mkulima');
                return "END Hongera {$firstName}! Umesajiliwa kikamilifu kwenye Agri-Advisory.\n"
                     . "Utapokea SMS ya kukaribisha. Piga tena USSD kwa huduma zaidi.";

            default:
                return 'END Hitilafu. Jaribu tena.';
        }
    }

    private function mainMenu(array $farmer, array $inputs, int $level): string {
        if ($level === 0) {
            $greeting = 'Karibu ' . trim($farmer['name'] ?? '') . "!\n";
            return 'CON ' . $greeting
                 . "1. Ushauri wa Kilimo\n"
                 . "2. Hali ya Hewa\n"
                 . "3. Wasiliana na Afisa\n"
                 . "4. Taarifa zangu";
        }

        switch ($inputs[0]) {
            case '1': return $this->advisoryQuestionFlow($farmer, $inputs, $level);
            case '2': return $this->weatherMenu($farmer);
            case '3': return $this->contactOfficerFlow($farmer);
            case '4': return $this->myInfoMenu($farmer);
            default:  return 'END Chaguo batili. Piga tena.';
        }
    }

    private function advisoryQuestionFlow(array $farmer, array $inputs, int $level): string {
        if ($level === 1) {
            $ctx = $this->farmContextLabel($farmer);
            return "CON Ingiza swali lako la kilimo kulingana na shamba lako hapa: {$ctx}:";
        }

        $question = trim(implode(' ', array_slice($inputs, 1)));
        if ($question === '') {
            return 'END Swali halijawekwa. Chagua 1 tena na uandike swali lako.';
        }

        $farmerId = (int)$farmer['id'];
        $rateLimit = new RateLimitService();
        if (!$rateLimit->canSendSmsQuery($farmerId)) {
            return 'END Umefikia kikomo cha maswali ' . $rateLimit->dailyLimit() . ' kwa siku. Jaribu kesho.';
        }

        try {
            set_time_limit(45);
            $result = (new FarmerChatService())->handleMessage($farmerId, $question, 'ussd');
            Logger::info('USSD advisory answered', ['farmer_id' => $farmerId]);
            SystemEventService::record('info', 'ussd', 'advisory_answered', [
                'farmer_id' => $farmerId,
                'channel'   => 'ussd',
                'message'   => mb_substr($question, 0, 200),
                'reply_preview' => mb_substr($result['reply'], 0, 120),
            ]);
            return 'END ' . $this->fitUssd($result['reply']);
        } catch (\Throwable $e) {
            Logger::error('USSD advisory failed', ['farmer_id' => $farmerId, 'error' => $e->getMessage()]);
            SystemEventService::record('error', 'ussd', 'advisory_failed', [
                'farmer_id' => $farmerId,
                'channel'   => 'ussd',
                'message'   => mb_substr($question, 0, 200),
                'error'     => $e->getMessage(),
            ]);
            return 'END Samahani, hitilafu imetokea. Jaribu tena baada ya muda mfupi.';
        }
    }

    private function weatherMenu(array $farmer): string {
        $village = trim($farmer['village_name'] ?? '') ?: '—';
        $weatherLine = 'Hali ya hewa haijulikani kwa sasa, jaribu tena baadaye.';

        try {
            $data = (new WeatherService())->getWeatherByVillage($farmer['village_id'] ?? $village);
            $current = $data['current'] ?? [];
            if (!empty($current)) {
                $temp = $current['main']['temp'] ?? '—';
                $desc = ucfirst($current['weather'][0]['description'] ?? 'Hali ya hewa');
                $weatherLine = "{$desc}, {$temp}°C";
            }
        } catch (\Throwable $e) {
            Logger::warning('USSD weather failed', ['farmer_id' => $farmer['id'] ?? null, 'error' => $e->getMessage()]);
        }

        return "END Hali ya hewa ya leo hapa kijini ni:\n{$weatherLine}\nKijiji chako ni:\n{$village}";
    }

    private function contactOfficerFlow(array $farmer): string {
        $reply = OfficerContactService::buildOfficerChatReply((int)($farmer['ward_id'] ?? 0));
        Logger::info('USSD officer info shown', ['farmer_id' => (int)$farmer['id']]);
        SystemEventService::record('info', 'ussd', 'officer_shown', [
            'farmer_id' => (int)$farmer['id'],
            'channel'   => 'ussd',
        ]);
        return 'END ' . $this->fitUssd($reply);
    }

    private function myInfoMenu(array $farmer): string {
        $name   = trim($farmer['name'] ?? '—');
        $phone  = trim($farmer['phone'] ?? '—');
        $ward   = trim($farmer['ward_name'] ?? '—');
        $village = trim($farmer['village_name'] ?? '—');

        return "END Taarifa:\nJina: {$name}\nSimu: {$phone}\nKata: {$ward}\nKijiji: {$village}\nTunakutakia maamuzi mema kwa ajili ya shamba lako.";
    }

    private function farmContextLabel(array $farmer): string {
        $bits = array_filter([
            !empty($farmer['village_name']) ? trim($farmer['village_name']) : '',
            !empty($farmer['ward_name']) ? 'Kata ' . trim($farmer['ward_name']) : '',
        ]);

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "SELECT c.name_sw FROM farmer_crops fc
             JOIN crops c ON c.id = fc.crop_id
             WHERE fc.farmer_id = ? AND fc.type = 'primary' LIMIT 1"
        );
        $stmt->execute([(int)$farmer['id']]);
        $crop = $stmt->fetchColumn();
        if ($crop) {
            $bits[] = 'Zao ' . $crop;
        }

        return $bits ? implode(', ', $bits) : 'shamba lako';
    }
}
