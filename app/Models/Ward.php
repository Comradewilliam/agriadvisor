<?php
namespace App\Models;

class Ward extends BaseModel {
    protected $table = 'wards';

    public function getAllWithVillageCount(): array {
        $stmt = $this->db->query(
            "SELECT w.*, COUNT(v.id) AS village_count
             FROM wards w LEFT JOIN villages v ON v.ward_id = w.id
             GROUP BY w.id ORDER BY w.name"
        );
        return $stmt->fetchAll();
    }
}
