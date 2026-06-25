<?php
namespace App\Services;

use App\Core\Database;

/** Ward officer lookup and SMS formatting for farmers. */
class OfficerContactService {
    /** @return array<int, array{name: string, phone: string}> */
    public static function officersForWard(int $wardId): array {
        if (!$wardId) {
            return [];
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT u.name, u.phone
            FROM users u
            JOIN officer_wards ow ON ow.officer_id = u.id
            WHERE ow.ward_id = ? AND u.role = 'ward_officer' AND u.is_active = 1
            ORDER BY u.name
        ");
        $stmt->execute([$wardId]);
        return $stmt->fetchAll() ?: [];
    }

    /** @return array{name?: string, phone?: string}|null */
    public static function primaryOfficerForWard(int $wardId): ?array {
        $officers = self::officersForWard($wardId);
        return $officers[0] ?? null;
    }

    /**
     * Personalized officer contact SMS for USSD option 3.
     *
     * @param array<string, mixed> $farmer
     */
    public static function buildOfficerSms(array $farmer): string {
        $firstName = trim(explode(' ', $farmer['name'] ?? 'Mkulima')[0]);
        $wardName = trim($farmer['ward_name'] ?? 'kata yako');
        $wardId = (int)($farmer['ward_id'] ?? 0);
        $officers = self::officersForWard($wardId);

        if ($officers === []) {
            return 'Samahani ' . $firstName . ', hakuna afisa wa kilimo aliyeapangwa kwenye kata yako (' . $wardName . ') kwa sasa. Tutakujulisha.';
        }

        $lines = [
            'Habari ' . $firstName . ', kata ' . $wardName . ':',
        ];
        foreach ($officers as $i => $o) {
            $tel = trim($o['phone'] ?? '') ?: '—';
            $lines[] = ($i + 1) . '.' . trim($o['name']) . ' ' . $tel;
        }
        $lines[] = 'Piga moja kwa moja.';

        $message = implode(' ', $lines);
        if (mb_strlen($message) > 150) {
            $o = $officers[0];
            $tel = trim($o['phone'] ?? '') ?: '—';
            $extra = count($officers) > 1 ? ' (+' . (count($officers) - 1) . ' wengine)' : '';
            $message = "Habari {$firstName}, afisa wa {$wardName}: {$o['name']} {$tel}{$extra}. Piga moja kwa moja.";
        }
        return mb_substr($message, 0, 150);
    }

    /** Short reply for AI/SMS chat when farmer asks about their officer. */
    public static function buildOfficerChatReply(?int $wardId): string {
        $officers = self::officersForWard((int)$wardId);
        if ($officers === []) {
            return 'Samahani, hakuna afisa wa kilimo aliyeapangwa kwenye kata yako kwa sasa. Tutajaribu kushirikiana na ofisi ya wilaya.';
        }

        if (count($officers) === 1) {
            $o = $officers[0];
            return "Afisa wako wa kilimo ni {$o['name']}. Simu: {$o['phone']}. Unaweza kumpigia moja kwa moja.";
        }

        $lines = ['Maafisa wa kilimo wa kata yako:'];
        foreach ($officers as $i => $o) {
            $tel = trim($o['phone'] ?? '') ?: '—';
            $lines[] = ($i + 1) . '. ' . $o['name'] . ' - ' . $tel;
        }
        $lines[] = 'Unaweza kuwapigia kwa msaada wa shamba lako.';
        return implode("\n", $lines);
    }
}
