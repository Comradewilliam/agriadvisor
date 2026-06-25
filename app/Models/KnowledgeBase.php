<?php
namespace App\Models;

class KnowledgeBase extends BaseModel {
    protected $table = 'knowledge_base';

    public function getAllWithDetails(string $search = '', string $status = '', ?int $cropId = null, ?int $stageId = null): array {
        $sql = "
            SELECT kb.*, c.name_en AS crop_name, gs.name_en AS stage_name,
                    u.name AS created_by_name
             FROM knowledge_base kb
             LEFT JOIN crops c ON c.id = kb.crop_id
             LEFT JOIN growth_stages gs ON gs.id = kb.stage_id
             LEFT JOIN users u ON u.id = kb.created_by
             WHERE kb.deleted_at IS NULL
        ";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (
                kb.title LIKE :q1 OR kb.situation LIKE :q2 OR kb.solution LIKE :q3
                OR c.name_en LIKE :q4 OR c.name_sw LIKE :q5
                OR gs.name_en LIKE :q6 OR gs.name_sw LIKE :q7
            )";
            $like = '%' . $search . '%';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
            $params[':q4'] = $like;
            $params[':q5'] = $like;
            $params[':q6'] = $like;
            $params[':q7'] = $like;
        }

        if ($status !== '' && in_array($status, ['published', 'draft'], true)) {
            $sql .= " AND kb.status = :status";
            $params[':status'] = $status;
        }

        if ($cropId) {
            $sql .= " AND kb.crop_id = :crop_id";
            $params[':crop_id'] = $cropId;
        }

        if ($stageId) {
            $sql .= " AND kb.stage_id = :stage_id";
            $params[':stage_id'] = $stageId;
        }

        $sql .= " ORDER BY COALESCE(kb.updated_at, kb.created_at) DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Build RAG-aligned context string (same fields as RagService).
     */
    public function searchContext(string $query, ?int $cropId = null, ?int $districtId = null): string {
        $rag = new \App\Services\RagService();
        return $rag->buildContext($query, $cropId, $districtId);
    }

    public function create(array $data): int {
        return $this->insert($data);
    }
}
