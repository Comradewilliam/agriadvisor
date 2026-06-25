<?php
namespace App\Models;

class Visit extends BaseModel {
    protected $table = 'visits';

    private function baseSelect(): string
    {
        return "
            SELECT v.*,
                   CONCAT(f.first_name, ' ', f.last_name) AS farmer_name,
                   f.phone AS farmer_phone,
                   f.farm_size_acres AS farmer_farm_size,
                   vil.name AS village_name,
                   w.name AS ward_name,
                   u.name AS officer_name,
                   c.name_en AS crop_name,
                   c.name_sw AS crop_name_sw,
                   COALESCE(v.farm_size_acres, f.farm_size_acres) AS display_farm_size
            FROM visits v
            JOIN farmers f ON f.id = v.farmer_id
            LEFT JOIN villages vil ON vil.id = f.village_id
            LEFT JOIN wards w ON w.id = f.ward_id
            LEFT JOIN users u ON u.id = v.officer_id
            LEFT JOIN crops c ON c.id = v.crop_id
        ";
    }

    public function getByOfficer(int $officerId): array
    {
        $stmt = $this->db->prepare($this->baseSelect() . "
            WHERE v.officer_id = :oid
            ORDER BY v.scheduled_at DESC
        ");
        $stmt->execute(['oid' => $officerId]);
        return $stmt->fetchAll();
    }

    /** Visits scoped by officer role (WAO wards / DAO district / all for admin). */
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
            $sql .= ' AND v.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY v.scheduled_at DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->db->prepare($this->baseSelect() . ' WHERE v.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function countUpcoming(int $officerId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM visits WHERE officer_id = :oid AND scheduled_at >= NOW() AND status IN ('scheduled','pending','postponed')"
        );
        $stmt->execute(['oid' => $officerId]);
        return (int)$stmt->fetchColumn();
    }

    public function create(array $data): int
    {
        return $this->insert($data);
    }

    public function updateStatus(int $id, string $status, string $notes = ''): void
    {
        $data = ['status' => $status, 'id' => $id];
        $sql = 'UPDATE visits SET status = :status';
        if ($notes !== '') {
            $sql .= ', notes = :notes';
            $data['notes'] = $notes;
        }
        $sql .= ' WHERE id = :id';
        $this->db->prepare($sql)->execute($data);
    }

    public function updateFull(int $id, array $fields): void
    {
        $allowed = ['status', 'notes', 'outcome', 'not_done_reason', 'followup', 'followup_at',
                    'scheduled_at', 'rescheduled_at', 'crop_id', 'farm_size_acres', 'visit_batch_id'];
        $data = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $fields)) {
                $data[$key] = $fields[$key];
            }
        }
        if (!$data) {
            return;
        }
        $this->update($id, $data);
    }

    /** Snapshot farmer crop + farm size for a new visit. */
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

    /** Resolve farmer IDs for group visit scheduling (mirrors broadcast targeting). */
    public function resolveFarmerIds(array $officer, array $filters): array
    {
        $sql = "SELECT DISTINCT f.id FROM farmers f
                LEFT JOIN wards w ON w.id = f.ward_id
                WHERE f.is_active = 1";
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

        if (!empty($filters['ward_id'])) {
            $sql .= ' AND f.ward_id = :ward_id';
            $params['ward_id'] = (int)$filters['ward_id'];
        }
        if (!empty($filters['village_id'])) {
            $sql .= ' AND f.village_id = :village_id';
            $params['village_id'] = (int)$filters['village_id'];
        }
        if (!empty($filters['crop_id'])) {
            $sql .= " AND EXISTS (
                SELECT 1 FROM farmer_crops fc
                WHERE fc.farmer_id = f.id AND fc.crop_id = :crop_id
                AND fc.type = 'primary'
            )";
            $params['crop_id'] = (int)$filters['crop_id'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }
}
