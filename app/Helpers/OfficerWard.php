<?php

namespace App\Helpers;

use PDO;

/**
 * Ward ↔ WAO assignment rules: max 3 wards per officer, max 3 officers per ward.
 */
class OfficerWard
{
    public const MAX_WARDS_PER_OFFICER = 3;
    public const MAX_OFFICERS_PER_WARD = 3;

    /** @param int[] $wardIds */
    public static function validate(PDO $db, array $wardIds, ?int $officerId = null): ?string
    {
        $wardIds = array_values(array_unique(array_filter(array_map('intval', $wardIds))));

        if (count($wardIds) > self::MAX_WARDS_PER_OFFICER) {
            return 'Afisa anaweza kupewa kata zisizozidi 3 tu.';
        }

        foreach ($wardIds as $wardId) {
            $sql = 'SELECT COUNT(*) FROM officer_wards WHERE ward_id = ?';
            $params = [$wardId];
            if ($officerId) {
                $sql .= ' AND officer_id != ?';
                $params[] = $officerId;
            }
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            if ((int)$stmt->fetchColumn() >= self::MAX_OFFICERS_PER_WARD) {
                $w = $db->prepare('SELECT name FROM wards WHERE id = ?');
                $w->execute([$wardId]);
                $name = $w->fetchColumn() ?: "ID {$wardId}";
                return "Kata ya {$name} tayari ina afisa 3. Ondoa mmoja kwanza.";
            }
        }

        return null;
    }

    /** @param int[] $wardIds */
    public static function sync(PDO $db, int $officerId, array $wardIds): void
    {
        $wardIds = array_values(array_unique(array_filter(array_map('intval', $wardIds))));
        $db->prepare('DELETE FROM officer_wards WHERE officer_id = ?')->execute([$officerId]);
        if (!$wardIds) {
            return;
        }
        $stmt = $db->prepare('INSERT INTO officer_wards (officer_id, ward_id) VALUES (?, ?)');
        foreach ($wardIds as $wid) {
            $stmt->execute([$officerId, $wid]);
        }
    }

    public static function countForWard(PDO $db, int $wardId): int
    {
        $stmt = $db->prepare('SELECT COUNT(*) FROM officer_wards WHERE ward_id = ?');
        $stmt->execute([$wardId]);
        return (int)$stmt->fetchColumn();
    }
}
