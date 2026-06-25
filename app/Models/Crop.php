<?php
namespace App\Models;

class Crop extends BaseModel {
    protected $table = 'crops';

    public function getActive(): array {
        $stmt = $this->db->query("SELECT * FROM crops WHERE is_active = 1 ORDER BY name_en");
        return $stmt->fetchAll();
    }

    public function getStages(int $cropId): array {
        $stmt = $this->db->prepare("SELECT * FROM growth_stages WHERE crop_id = :c ORDER BY sort_order");
        $stmt->execute(['c' => $cropId]);
        return $stmt->fetchAll();
    }
}
