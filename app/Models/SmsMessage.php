<?php
namespace App\Models;

class SmsMessage extends BaseModel {
    protected $table = 'sms_messages';

    public function getConversation(int $farmerId): array {
        $stmt = $this->db->prepare(
            "SELECT m.*, u.name AS responder_name
             FROM sms_messages m
             LEFT JOIN users u ON u.id = m.responder_id
             WHERE m.farmer_id = :fid
             ORDER BY m.sent_at ASC"
        );
        $stmt->execute(['fid' => $farmerId]);
        return $stmt->fetchAll();
    }

    public function countPendingEscalations(int $wardId = 0): int {
        $sql = "SELECT COUNT(*) FROM sms_escalations WHERE status = 'pending'";
        $params = [];
        if ($wardId) {
            $sql = "SELECT COUNT(*) FROM sms_escalations e
                    JOIN sms_messages m ON m.id = e.sms_message_id
                    JOIN farmers f ON f.id = m.farmer_id
                    WHERE e.status = 'pending' AND f.ward_id = :wid";
            $params['wid'] = $wardId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getPendingEscalations(int $wardId = 0): array {
        $sql = "SELECT e.*, m.content AS question, m.sent_at,
                       f.name AS farmer_name, f.phone AS farmer_phone
                FROM sms_escalations e
                JOIN sms_messages m ON m.id = e.sms_message_id
                JOIN farmers f ON f.id = m.farmer_id
                WHERE e.status = 'pending'";
        $params = [];
        if ($wardId) {
            $sql .= " AND f.ward_id = :wid";
            $params['wid'] = $wardId;
        }
        $sql .= " ORDER BY e.escalated_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function log(int $farmerId, string $direction, string $content, string $type = 'system', int $responderId = 0, string $confidence = ''): int {
        $data = [
            'farmer_id'      => $farmerId,
            'direction'      => $direction,
            'content'        => $content,
            'responder_type' => $type,
        ];
        if ($responderId) $data['responder_id'] = $responderId;
        if ($confidence)  $data['ai_confidence'] = $confidence;
        return $this->insert($data);
    }

    public function escalate(int $messageId, string $priority = 'normal'): void {
        $stmt = $this->db->prepare(
            "INSERT INTO sms_escalations (sms_message_id, priority) VALUES (:mid, :p)"
        );
        $stmt->execute(['mid' => $messageId, 'p' => $priority]);
    }

    public function closeEscalation(int $escalationId): void {
        $stmt = $this->db->prepare(
            "UPDATE sms_escalations SET status='responded', responded_at=NOW() WHERE id = :id"
        );
        $stmt->execute(['id' => $escalationId]);
    }
}
