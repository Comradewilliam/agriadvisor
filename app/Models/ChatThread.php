<?php

namespace App\Models;

class ChatThread extends BaseModel
{
    protected $table = 'chat_threads';

    public function listForFarmer(int $farmerId, string $channel = 'web'): array
    {
        $stmt = $this->db->prepare("
            SELECT t.id, t.title, t.started_at, t.updated_at,
                   (SELECT content FROM ai_messages
                    WHERE thread_id = t.id AND direction = 'in'
                    ORDER BY sent_at ASC LIMIT 1) AS first_message
            FROM chat_threads t
            WHERE t.farmer_id = :fid AND t.channel = :ch
            ORDER BY t.updated_at DESC
        ");
        $stmt->execute(['fid' => $farmerId, 'ch' => $channel]);
        return $stmt->fetchAll();
    }

    public function findForFarmer(int $threadId, int $farmerId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM chat_threads WHERE id = :id AND farmer_id = :fid LIMIT 1
        ");
        $stmt->execute(['id' => $threadId, 'fid' => $farmerId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(int $farmerId, string $channel, string $title): int
    {
        $title = mb_substr(trim($title), 0, 255);
        $stmt = $this->db->prepare("
            INSERT INTO chat_threads (farmer_id, channel, title) VALUES (?, ?, ?)
        ");
        $stmt->execute([$farmerId, $channel, $title]);
        return (int)$this->db->lastInsertId();
    }

    public function touch(int $threadId): void
    {
        $this->db->prepare("UPDATE chat_threads SET updated_at = NOW() WHERE id = ?")
            ->execute([$threadId]);
    }
}
