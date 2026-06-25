<?php
namespace App\Models;

class AutomatedAlert extends BaseModel
{
    protected $table = 'automated_alerts';

    public function getByDistrict(int $districtId): array
    {
        $stmt = $this->db->prepare("
            SELECT aa.*, w.name AS ward_name, u.name AS creator_name
            FROM automated_alerts aa
            LEFT JOIN wards w ON w.id = aa.ward_id
            LEFT JOIN users u ON u.id = aa.created_by
            WHERE aa.district_id = ?
            ORDER BY aa.is_active DESC, aa.created_at DESC
        ");
        $stmt->execute([$districtId]);
        return $stmt->fetchAll();
    }

    public function createAlert(array $data): int
    {
        return $this->insert($data);
    }

    public function toggleActive(int $id, int $districtId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE automated_alerts SET is_active = NOT is_active
            WHERE id = ? AND district_id = ?
        ");
        return $stmt->execute([$id, $districtId]);
    }
}
