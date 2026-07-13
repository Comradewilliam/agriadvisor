<?php
namespace App\Models;

class Farmer extends BaseModel {
    protected $table = 'farmers';

    public function findByPhone(string $phone): ?array {
        $variants = self::phoneLookupVariants($phone);
        if ($variants === []) {
            return null;
        }

        $placeholders = implode(',', array_fill(0, count($variants), '?'));
        $stmt = $this->db->prepare(
            "SELECT f.*, CONCAT(f.first_name, ' ', f.last_name) AS name, w.name AS ward_name, v.name AS village_name
             FROM farmers f
             LEFT JOIN wards w ON w.id = f.ward_id
             LEFT JOIN villages v ON v.id = f.village_id
             WHERE f.phone IN ({$placeholders})
             LIMIT 1"
        );
        $stmt->execute($variants);
        return $stmt->fetch() ?: null;
    }

    /** @return string[] */
    public static function phoneLookupVariants(string $phone): array {
        $normalized = \App\Helpers\Sanitizer::normalizePhone($phone);
        $variants = [];
        if ($normalized !== '') {
            $variants[] = $normalized;
        }
        if (str_starts_with($normalized, '0') && strlen($normalized) >= 10) {
            $variants[] = '255' . substr($normalized, 1);
            $variants[] = '+255' . substr($normalized, 1);
        }
        $digits = preg_replace('/\D/', '', trim($phone));
        if ($digits !== '' && $digits !== $normalized) {
            $variants[] = $digits;
            if (str_starts_with($digits, '255') && strlen($digits) >= 12) {
                $variants[] = '0' . substr($digits, 3);
            }
        }
        return array_values(array_unique(array_filter($variants)));
    }

    public function findWithDetails(int $id): ?array {
        $cropCols = $this->farmerCropColumnNames();
        $plantedExpr = in_array('planted_at', $cropCols, true) ? "IFNULL(fc.planted_at,'')" : "''";
        $stageExpr = in_array('growth_stage_id', $cropCols, true) ? "IFNULL(fc.growth_stage_id,'')" : "''";

        $stmt = $this->db->prepare(
            "SELECT f.*, CONCAT(f.first_name, ' ', f.last_name) AS name, w.name AS ward_name, v.name AS village_name,
                    GROUP_CONCAT(CONCAT(c.id,'|',c.name_en,'|',fc.type,'|',{$plantedExpr},'|',{$stageExpr}) SEPARATOR ';;') AS crops_raw
             FROM farmers f
             LEFT JOIN wards w ON w.id = f.ward_id
             LEFT JOIN villages v ON v.id = f.village_id
             LEFT JOIN farmer_crops fc ON fc.farmer_id = f.id
             LEFT JOIN crops c ON c.id = fc.crop_id
             WHERE f.id = :id
             GROUP BY f.id"
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if ($row && $row['crops_raw']) {
            $row['crops'] = array_map(function ($s) {
                $p = explode('|', $s);
                return [
                    'id'               => (int)($p[0] ?? 0),
                    'name'             => $p[1] ?? '',
                    'type'             => $p[2] ?? 'primary',
                    'planted_date'     => ($p[3] ?? '') !== '' ? $p[3] : null,
                    'growth_stage_id'  => ($p[4] ?? '') !== '' ? (int)$p[4] : null,
                ];
            }, explode(';;', $row['crops_raw']));
        } else {
            $row['crops'] = [];
        }
        return $row ?: null;
    }

    /** @return string[] */
    private function farmerCropColumnNames(): array {
        static $cols = null;
        if ($cols !== null) {
            return $cols;
        }
        try {
            $rows = $this->db->query('SHOW COLUMNS FROM farmer_crops')->fetchAll();
            $cols = array_column($rows, 'Field');
        } catch (\Throwable) {
            $cols = ['id', 'farmer_id', 'crop_id', 'type'];
        }
        return $cols;
    }

    /** All farmers visible to a ward officer */
    public function getByWard(int $wardId, string $search = ''): array {
        $sql = "SELECT f.*, CONCAT(f.first_name, ' ', f.last_name) AS name, v.name AS village_name FROM farmers f
                LEFT JOIN villages v ON v.id = f.village_id
                WHERE f.ward_id = :ward_id";
        $params = ['ward_id' => $wardId];
        if ($search) {
            $sql .= " AND (f.first_name LIKE :s OR f.last_name LIKE :s OR f.phone LIKE :s)";
            $params['s'] = "%{$search}%";
        }
        $sql .= " ORDER BY f.registered_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** All farmers (super admin) */
    public function getAllWithDetails(string $search = ''): array {
        $sql = "SELECT f.*, CONCAT(f.first_name, ' ', f.last_name) AS name, w.name AS ward_name, v.name AS village_name
                FROM farmers f
                LEFT JOIN wards w ON w.id = f.ward_id
                LEFT JOIN villages v ON v.id = f.village_id";
        $params = [];
        if ($search) {
            $sql .= " WHERE f.first_name LIKE :s OR f.last_name LIKE :s OR f.phone LIKE :s";
            $params['s'] = "%{$search}%";
        }
        $sql .= " ORDER BY f.registered_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        if (!empty($data['phone'])) {
            $data['phone'] = \App\Helpers\Sanitizer::normalizePhone((string)$data['phone']);
        }
        return $this->insert($data);
    }

    public function countByWard(int $wardId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM farmers WHERE ward_id = :ward_id");
        $stmt->execute(['ward_id' => $wardId]);
        return (int)$stmt->fetchColumn();
    }

    public function addCrop(int $farmerId, int $cropId, string $type = 'primary'): void {
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO farmer_crops (farmer_id, crop_id, type) VALUES (:fid, :cid, :type)"
        );
        $stmt->execute(['fid' => $farmerId, 'cid' => $cropId, 'type' => $type]);
    }
}
