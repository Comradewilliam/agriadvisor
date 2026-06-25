<?php

namespace App\Models;

class VisitRequest extends BaseModel
{
    protected $table = 'visit_requests';

    public function create(array $data): int
    {
        return $this->insert($data);
    }

    private function baseSelect(): string
    {
        return "
            SELECT vr.*,
                   f.first_name, f.last_name, f.phone, f.village_id, f.farm_size_acres AS farmer_farm_size,
                   w.name AS ward_name, v.name AS village_name,
                   c.name_en AS crop_name, c.name_sw AS crop_name_sw,
                   COALESCE(vr.farm_size_acres, f.farm_size_acres) AS display_farm_size,
                   COALESCE(vr.crop_id, (SELECT fc.crop_id FROM farmer_crops fc WHERE fc.farmer_id = f.id AND fc.type = 'primary' LIMIT 1)) AS display_crop_id,
                   u.name AS handler_name
            FROM visit_requests vr
            JOIN farmers f ON f.id = vr.farmer_id
            LEFT JOIN wards w ON w.id = f.ward_id
            LEFT JOIN villages v ON v.id = f.village_id
            LEFT JOIN crops c ON c.id = COALESCE(vr.crop_id, (SELECT fc.crop_id FROM farmer_crops fc WHERE fc.farmer_id = f.id AND fc.type = 'primary' LIMIT 1))
            LEFT JOIN users u ON u.id = vr.handled_by
        ";
    }

    public function findByFarmer(int $farmerId): array
    {
        $stmt = $this->db->prepare($this->baseSelect() . '
            WHERE vr.farmer_id = :farmerId
            ORDER BY vr.requested_at DESC
        ');
        $stmt->execute(['farmerId' => $farmerId]);
        return $stmt->fetchAll();
    }

    public function getScoped(array $officer, ?string $status = null): array
    {
        $sql = $this->baseSelect() . ' WHERE 1=1';
        $params = [];

        if ($officer['role'] === 'ward_officer' && !empty($officer['ward_ids'])) {
            $in = implode(',', array_map('intval', $officer['ward_ids']));
            $sql .= " AND f.ward_id IN ({$in})";
        } elseif ($officer['role'] === 'dao') {
            $d = $this->db->prepare('SELECT district_id FROM officer_districts WHERE officer_id = ? LIMIT 1');
            $d->execute([$officer['id']]);
            $districtId = (int)($d->fetchColumn() ?: 0);
            if ($districtId) {
                $sql .= ' AND w.district_id = :district_id';
                $params['district_id'] = $districtId;
            }
        }

        if ($status && $status !== 'all') {
            $sql .= ' AND vr.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY vr.requested_at DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function farmerSnapshot(int $farmerId): array
    {
        $stmt = $this->db->prepare("
            SELECT f.farm_size_acres,
                   (SELECT fc.crop_id FROM farmer_crops fc WHERE fc.farmer_id = f.id AND fc.type = 'primary' LIMIT 1) AS crop_id
            FROM farmers f WHERE f.id = ?
        ");
        $stmt->execute([$farmerId]);
        $row = $stmt->fetch() ?: [];
        return [
            'farm_size_acres' => $row['farm_size_acres'] ?? null,
            'crop_id' => $row['crop_id'] ?? null,
        ];
    }
}
