<?php
namespace App\Services;

use App\Core\Database;

/**
 * RagService — Retrieval-Augmented Generation for BwanaShamba AI
 *
 * Workflow:
 *  1. Search knowledge_base by keyword matching against situation + solution + title
 *  2. Return top matches ranked by relevance score
 *  3. Compose context string to inject into the AI system prompt
 *  4. After officer resolves an escalation, learn from the answer (save to KB)
 */
class RagService {

    private $db;
    private int $topK = 3;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Search the knowledge base for entries relevant to the farmer's query.
     * Returns an array of matching knowledge_base rows ordered by relevance,
     * with a relevance score for each.
     */
    public function search(string $query, ?int $cropId = null, ?int $districtId = null): array {
        $terms = $this->expandSearchTerms($this->tokenize($query));
        if (empty($terms)) {
            return [];
        }

        // Build LIKE conditions for each keyword and calculate a match score
        $caseStatements = [];
        $conditions = [];
        $params = [];
        foreach ($terms as $i => $term) {
            $like = "%{$term}%";
            $wA = ":w{$i}a";
            $wB = ":w{$i}b";
            $wC = ":w{$i}c";
            $wD = ":w{$i}d";
            $wE = ":w{$i}e";
            $cA = ":c{$i}a";
            $cB = ":c{$i}b";
            $cC = ":c{$i}c";
            $cD = ":c{$i}d";
            $cE = ":c{$i}e";

            $conditions[] = "(kb.title LIKE {$wA} OR kb.situation LIKE {$wB} OR kb.solution LIKE {$wC}
                OR c.name_en LIKE {$wD} OR c.name_sw LIKE {$wE})";
            $params[$wA] = $like;
            $params[$wB] = $like;
            $params[$wC] = $like;
            $params[$wD] = $like;
            $params[$wE] = $like;

            $caseStatements[] = "
                CASE 
                    WHEN kb.title LIKE {$cA} THEN 3
                    WHEN c.name_en LIKE {$cD} OR c.name_sw LIKE {$cE} THEN 3
                    WHEN kb.situation LIKE {$cB} THEN 2
                    WHEN kb.solution LIKE {$cC} THEN 1
                    ELSE 0 
                END
            ";
            $params[$cA] = $like;
            $params[$cB] = $like;
            $params[$cC] = $like;
            $params[$cD] = $like;
            $params[$cE] = $like;
        }

        $scoreSum = implode(' + ', $caseStatements);
        $whereClause = implode(' OR ', $conditions);
        $cropFilter = $cropId ? "AND (kb.crop_id = :crop_id OR kb.crop_id IS NULL)" : "";
        $districtFilter = $districtId ? "AND (kb.district_id = :district_id OR kb.district_id IS NULL)" : "";
        $limit = (int)$this->topK;

        $sql = "
            SELECT kb.*,
                   c.name_en AS crop_name,
                   gs.name_en AS stage_name,
                   ({$scoreSum}) AS relevance_score
            FROM knowledge_base kb
            LEFT JOIN crops c ON c.id = kb.crop_id
            LEFT JOIN growth_stages gs ON gs.id = kb.stage_id
            WHERE kb.status = 'published'
              AND kb.deleted_at IS NULL
              AND ({$whereClause})
              {$cropFilter}
              {$districtFilter}
            HAVING relevance_score > 0
            ORDER BY relevance_score DESC, COALESCE(kb.updated_at, kb.created_at) DESC
            LIMIT {$limit}
        ";

        if ($cropId) $params[':crop_id'] = $cropId;
        if ($districtId) $params[':district_id'] = $districtId;

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $type = ($key === ':crop_id' || $key === ':district_id')
                ? \PDO::PARAM_INT
                : \PDO::PARAM_STR;
            $stmt->bindValue($key, $val, $type);
        }
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Check if the given query is agricultural in nature.
     */
    public function isAgriculturalQuery(string $query): bool {
        $agriculturalKeywords = [
            // English
            'maize', 'corn', 'bean', 'rice', 'wheat', 'cassava', 'potato', 'tomato',
            'pest', 'disease', 'fertilizer', 'irrigation', 'soil', 'weather', 'rain',
            'planting', 'harvest', 'storage', 'livestock', 'cow', 'goat', 'chicken',
            'farm', 'farmer', 'crop', 'yield', 'kilimo', 'mazao',
            
            // Swahili
            'mahindi', 'udongo', 'mboga', 'dawa',
            'mvua', 'hali ya hewa', 'mbegu', 'mabadiliko ya mazingira', 'mawese', 'wadudu',
            'kukuwa', 'kilimo', 'kupalilia', 'kuvuna', 'hifadhi', 'kupanda', 'palizi', 'mihogo',
            'mbolea', 'msimu', 'mimea', 'shamba', 'magonjwa', 'umwagiliaji', 'ukuaji', 'kuchipua',
            'maua', 'virutubisho', 'urea', 'joto', 'mvua', 'ekari',
            'gunia', 'mavuno', 'magugu', 'drip', 'lishe', 'chanua', 'punje', 'mikoba', 'kukausha',
            'ghala', 'msaidizi', 'kilimo cha', 'zao', 'mazao', 'soko', 'wiki', 'msimu huu',
            'msimu wa', 'chanjo', 'dawa ya mimea', 'wataalamu', 'afisa wa kilimo'
        ];
        
        $lowerQuery = strtolower($query);
        foreach ($agriculturalKeywords as $keyword) {
            if (str_contains($lowerQuery, strtolower($keyword))) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Best KB snippet for supplementing AI (score >= 1). Not required to answer.
     */
    public function getSupplementalAnswer(string $query, ?int $districtId = null): array
    {
        $results = $this->search($query, null, $districtId);
        if (empty($results)) {
            return ['found' => false, 'answer' => ''];
        }

        $top = $results[0];
        if (($top['relevance_score'] ?? 0) >= 1) {
            $answer = "Kuhusiana na swali lako:\n" . ($top['solution'] ?? '');
            return ['found' => true, 'answer' => trim($answer), 'title' => $top['title'] ?? ''];
        }

        return ['found' => false, 'answer' => ''];
    }

    /**
     * Strong KB match (score >= 2) — used as fallback when AI is unavailable.
     */
    public function getDirectAnswer(string $query, ?int $districtId = null): array
    {
        $results = $this->search($query, null, $districtId);
        if (empty($results)) {
            return ['found' => false, 'answer' => ''];
        }

        $topResult = $results[0];
        if (($topResult['relevance_score'] ?? 0) >= 2) {
            return [
                'found'  => true,
                'answer' => "Kuhusiana na swali lako:\n{$topResult['solution']}",
            ];
        }

        return ['found' => false, 'answer' => ''];
    }

    /**
     * Build context from pre-fetched search results (avoids duplicate DB query).
     */
    public function buildContextFromResults(array $results): string
    {
        if (empty($results)) {
            return '';
        }

        $context = "RELEVANT KNOWLEDGE BASE ENTRIES (use these to inform your answer):\n\n";
        foreach ($results as $i => $row) {
            $num = $i + 1;
            $context .= "--- Entry {$num}: {$row['title']} ---\n";
            if ($row['crop_name']) $context .= "Crop: {$row['crop_name']}\n";
            if ($row['stage_name']) $context .= "Stage: {$row['stage_name']}\n";
            $context .= "Situation: {$row['situation']}\n";
            $context .= "Solution: {$row['solution']}\n\n";
        }

        return $context;
    }

    public function getSupplementalAnswerFromResults(array $results): array
    {
        if (empty($results)) {
            return ['found' => false, 'answer' => ''];
        }
        $top = $results[0];
        if (($top['relevance_score'] ?? 0) >= 1) {
            $answer = "Kuhusiana na swali lako:\n" . ($top['solution'] ?? '');
            return ['found' => true, 'answer' => trim($answer), 'title' => $top['title'] ?? ''];
        }
        return ['found' => false, 'answer' => ''];
    }

    public function getDirectAnswerFromResults(array $results): array
    {
        if (empty($results)) {
            return ['found' => false, 'answer' => ''];
        }
        $topResult = $results[0];
        if (($topResult['relevance_score'] ?? 0) >= 2) {
            return [
                'found'  => true,
                'answer' => "Kuhusiana na swali lako:\n{$topResult['solution']}",
            ];
        }
        return ['found' => false, 'answer' => ''];
    }

    /** Weakest KB match — any published hit with a solution (for USSD when AI is down). */
    public function getAnyAnswerFromResults(array $results): array
    {
        foreach ($results as $row) {
            $sol = trim((string)($row['solution'] ?? ''));
            if ($sol !== '') {
                return ['found' => true, 'answer' => $sol, 'title' => $row['title'] ?? ''];
            }
        }
        return ['found' => false, 'answer' => ''];
    }

    /**
     * Build the context string to inject into the AI system prompt.
     */
    public function buildContext(string $query, ?int $cropId = null, ?int $districtId = null): string {
        return $this->buildContextFromResults($this->search($query, $cropId, $districtId));
    }

    /**
     * Build context string about the farmer's assigned officer and upcoming visits.
     */
    public function getFarmerContext(int $farmerId): string {
        $farmerStmt = $this->db->prepare("SELECT ward_id FROM farmers WHERE id = ?");
        $farmerStmt->execute([$farmerId]);
        $farmer = $farmerStmt->fetch();
        if (!$farmer || !$farmer['ward_id']) return "";

        $context = "";
        $officerStmt = $this->db->prepare("
            SELECT u.name, u.phone FROM users u
            JOIN officer_wards ow ON ow.officer_id = u.id
            WHERE ow.ward_id = ? AND u.role = 'ward_officer' AND u.is_active = 1
            LIMIT 1
        ");
        $officerStmt->execute([$farmer['ward_id']]);
        $officer = $officerStmt->fetch();
        
        if ($officer) {
            $context .= "Assigned Ward Agricultural Officer: {$officer['name']} (Phone: {$officer['phone']}).\n";
        }

        $visitsStmt = $this->db->prepare("
            SELECT scheduled_at, reason FROM visits
            WHERE farmer_id = ? AND scheduled_at >= NOW()
            ORDER BY scheduled_at ASC LIMIT 1
        ");
        $visitsStmt->execute([$farmerId]);
        $visit = $visitsStmt->fetch();
        
        if ($visit) {
            $context .= "Upcoming farm visit: {$visit['scheduled_at']} for {$visit['reason']}.\n";
        }

        return $context ? "FARMER CONTEXT:\n" . $context . "\n" : "";
    }

    /**
     * After an officer resolves an escalation, save the Q&A to the knowledge base
     * so BwanaShamba AI can answer similar questions in the future.
     */
    public function learnFromEscalation(
        string $question,
        string $answer,
        int $officerId,
        ?int $cropId = null,
        ?int $stageId = null,
        ?int $districtId = null
    ): void {
        $officerRow = $this->db->prepare("SELECT role FROM users WHERE id = ?");
        $officerRow->execute([$officerId]);
        $officer = $officerRow->fetch();
        $source = $officer ? $officer['role'] : 'ward_officer';

        $stmt = $this->db->prepare("
            INSERT INTO knowledge_base
                (crop_id, stage_id, title, situation, solution, language, district_id, source, created_by, status)
            VALUES
                (:crop_id, :stage_id, :title, :situation, :solution, 'en', :district_id, :source, :created_by, 'published')
        ");
        $stmt->execute([
            ':crop_id'    => $cropId,
            ':stage_id'   => $stageId,
            ':title'      => 'Learned from escalation: ' . mb_substr($question, 0, 100),
            ':situation'  => $question,
            ':solution'   => $answer,
            ':district_id'=> $districtId,
            ':source'     => $source,
            ':created_by' => $officerId,
        ]);
    }

    /**
     * Expand Swahili farmer terms to English KB equivalents (KB is mostly English).
     *
     * @param string[] $terms
     * @return string[]
     */
    private function expandSearchTerms(array $terms): array {
        $map = [
            'mahindi'   => ['maize', 'corn'],
            'mihogo'    => ['cassava'],
            'maharage'  => ['bean', 'beans'],
            'mpunga'    => ['rice'],
            'ngano'     => ['wheat'],
            'viazi'     => ['potato'],
            'ndizi'     => ['banana'],
            'kahawa'    => ['coffee'],
            'kupanda'   => ['planting', 'plant', 'sow'],
            'panda'     => ['planting', 'plant'],
            'lini'      => ['when', 'timing', 'time', 'season'],
            'wakati'    => ['when', 'timing', 'time', 'season'],
            'bora'      => ['best', 'optimal', 'recommended'],
            'mbolea'    => ['fertilizer', 'fertiliser', 'dap', 'npk', 'urea'],
            'mvua'      => ['rain', 'rainfall'],
            'magugu'    => ['weed', 'weeds'],
            'magonjwa'  => ['disease', 'diseases'],
            'wadudu'    => ['pest', 'pests', 'insect'],
            'kuvuna'    => ['harvest', 'harvesting'],
            'mbegu'     => ['seed', 'seeds'],
            'udongo'    => ['soil'],
            'umwagiliaji' => ['irrigation'],
            'kuchipua'  => ['germination', 'emergence'],
            'mavuno'    => ['yield', 'harvest'],
        ];

        $expanded = $terms;
        foreach ($terms as $term) {
            if (isset($map[$term])) {
                foreach ($map[$term] as $en) {
                    $expanded[] = $en;
                }
            }
        }

        if (array_intersect($terms, ['kupanda', 'panda', 'lini', 'wakati'])) {
            $expanded[] = 'planting';
        }

        return array_values(array_unique(array_slice($expanded, 0, 12)));
    }

    /**
     * Tokenize the query into meaningful keywords (stop-word filtered).
     */
    private function tokenize(string $text): array {
        $stopWords = [
            'i', 'me', 'my', 'the', 'a', 'an', 'is', 'are', 'was', 'were',
            'how', 'what', 'when', 'where', 'why', 'who', 'which', 'do', 'does',
            'can', 'could', 'should', 'would', 'will', 'have', 'has', 'had',
            'it', 'its', 'to', 'of', 'in', 'on', 'for', 'with', 'and', 'or',
            'but', 'not', 'be', 'this', 'that', 'my', 'your', 'his', 'her',
            'we', 'they', 'them', 'their', 'at', 'by', 'from', 'up', 'about',
            'into', 'through', 'during', 'before', 'after', 'if', 'then',
            'na', 'ya', 'wa', 'ni', 'kwa', 'katika', 'la', 'au', 'hii', 'hiyo',
            'kuwa', 'si', 'za', 'ile', 'hizo', 'wao', 'yao', 'jinsi', 'gani', 'je',
        ];

        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s]/u', ' ', $text);
        $words = preg_split('/\s+/', trim($text));

        return array_values(array_filter($words, function($w) use ($stopWords) {
            return strlen($w) >= 3 && !in_array($w, $stopWords);
        }));
    }
}
