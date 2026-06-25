<?php
namespace App\Services;

use App\Core\Database;
use App\Models\Farmer;
use App\Helpers\Logger;

/** Renders and sends automated alert templates from the DB. */
class AutomatedAlertService {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /** @return array<int, array<string, mixed>> */
    public function getWelcomeAlertsForFarmer(int $farmerId): array {
        $farmer = (new Farmer())->findWithDetails($farmerId);
        if (!$farmer) {
            return [];
        }
        $wardId = (int)($farmer['ward_id'] ?? 0);
        $districtId = $this->districtIdForWard($wardId);
        if (!$districtId) {
            return [];
        }
        $alerts = $this->matchingAlerts('on_register', $districtId, $wardId);
        return $alerts !== [] ? [ $alerts[0] ] : [];
    }

    /** @return int Number of SMS sent */
    public function sendTriggeredAlerts(string $triggerEvent, int $farmerId): int {
        $farmer = (new Farmer())->findWithDetails($farmerId);
        if (!$farmer || empty($farmer['phone'])) {
            return 0;
        }

        $wardId = (int)($farmer['ward_id'] ?? 0);
        $districtId = $this->districtIdForWard($wardId);
        if (!$districtId) {
            return 0;
        }

        $alerts = $this->matchingAlerts($triggerEvent, $districtId, $wardId);
        if ($alerts === []) {
            if ($triggerEvent === 'on_register') {
                return $this->sendDefaultWelcome($farmer) ? 1 : 0;
            }
            return 0;
        }

        // One welcome SMS per registration (ward-specific template preferred)
        if ($triggerEvent === 'on_register') {
            $alerts = [ $alerts[0] ];
        }

        $at = new AfricasTalkingService();
        $sent = 0;

        foreach ($alerts as $alert) {
            $message = $this->renderTemplate((string)$alert['message_template'], $farmer);
            if ($message === '') {
                continue;
            }
            $result = $at->sendOneWay((string)$farmer['phone'], $message);
            if (AfricasTalkingService::isSuccess($result)) {
                $sent++;
                Logger::info('Automated alert sent', [
                    'farmer_id' => $farmerId,
                    'alert_id'  => $alert['id'],
                    'trigger'   => $triggerEvent,
                ]);
            } else {
                Logger::error('Automated alert SMS failed', [
                    'farmer_id' => $farmerId,
                    'alert_id'  => $alert['id'],
                    'response'  => $result,
                ]);
            }
        }

        return $sent;
    }

    /** @param array<string, mixed> $farmer */
    private function sendDefaultWelcome(array $farmer): bool {
        $name = trim(explode(' ', $farmer['name'] ?? 'Mkulima')[0]);
        $village = trim($farmer['village_name'] ?? 'kijiji chako');
        $ward = trim($farmer['ward_name'] ?? 'kata yako');
        $message = "Karibu {$name} kwenye Agri-Advisory! Umesajiliwa kijiji cha {$village}, kata {$ward}. Piga USSD au tuma SMS kwa msaada wa kilimo.";
        $result = (new AfricasTalkingService())->sendOneWay((string)$farmer['phone'], $message);
        return AfricasTalkingService::isSuccess($result);
    }

    /** @return array<int, array<string, mixed>> */
    private function matchingAlerts(string $triggerEvent, int $districtId, int $wardId): array {
        if ($wardId > 0) {
            $stmt = $this->db->prepare("
                SELECT *
                FROM automated_alerts
                WHERE trigger_event = ?
                  AND district_id = ?
                  AND is_active = 1
                  AND (ward_id IS NULL OR ward_id = ?)
                ORDER BY ward_id DESC, id ASC
            ");
            $stmt->execute([$triggerEvent, $districtId, $wardId]);
        } else {
            $stmt = $this->db->prepare("
                SELECT *
                FROM automated_alerts
                WHERE trigger_event = ?
                  AND district_id = ?
                  AND is_active = 1
                  AND ward_id IS NULL
                ORDER BY id ASC
            ");
            $stmt->execute([$triggerEvent, $districtId]);
        }
        return $stmt->fetchAll() ?: [];
    }

    private function districtIdForWard(int $wardId): ?int {
        if (!$wardId) {
            return null;
        }
        $stmt = $this->db->prepare('SELECT district_id FROM wards WHERE id = ? LIMIT 1');
        $stmt->execute([$wardId]);
        $id = (int)($stmt->fetchColumn() ?: 0);
        return $id ?: null;
    }

    /** @param array<string, mixed> $farmer */
    public function renderTemplate(string $template, array $farmer): string {
        $firstName = trim(explode(' ', $farmer['name'] ?? 'Mkulima')[0]);
        $crop = '—';
        if (!empty($farmer['crops'][0]['name'])) {
            $crop = $farmer['crops'][0]['name'];
        } elseif (!empty($farmer['crops'][0]['name_en'])) {
            $crop = $farmer['crops'][0]['name_en'];
        }

        $officer = OfficerContactService::primaryOfficerForWard((int)($farmer['ward_id'] ?? 0));
        $officerName = $officer['name'] ?? 'afisa wa kilimo';
        $officerPhone = $officer['phone'] ?? '';

        $replacements = [
            '{name}'         => $firstName,
            '{full_name}'    => trim($farmer['name'] ?? $firstName),
            '{village}'      => trim($farmer['village_name'] ?? '—'),
            '{ward}'         => trim($farmer['ward_name'] ?? '—'),
            '{crop}'         => $crop,
            '{phone}'        => trim($farmer['phone'] ?? ''),
            '{officer_name}' => $officerName,
            '{officer_phone}'=> $officerPhone,
            '{visit_date}'   => '—',
            '{visit_reason}' => '—',
            '{weather_summary}' => '—',
        ];

        $message = str_replace(array_keys($replacements), array_values($replacements), $template);
        return trim(preg_replace('/\s+/', ' ', $message) ?? $message);
    }
}
