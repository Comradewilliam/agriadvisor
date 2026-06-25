<?php
namespace App\Models;

class Village extends BaseModel {
    protected $table = 'villages';

    public function getByWard(int $wardId): array {
        $stmt = $this->db->prepare("SELECT * FROM villages WHERE ward_id = :w ORDER BY name");
        $stmt->execute(['w' => $wardId]);
        return $stmt->fetchAll();
    }
}
