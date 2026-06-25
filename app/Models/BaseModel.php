<?php
namespace App\Models;

use App\Core\Database;
use PDO;

abstract class BaseModel {
    protected $db;
    protected $table;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(string $order = 'id DESC'): array {
        $stmt = $this->db->query("SELECT * FROM `{$this->table}` ORDER BY {$order}");
        return $stmt->fetchAll();
    }

    public function find($id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function delete($id): bool {
        $stmt = $this->db->prepare("DELETE FROM `{$this->table}` WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function count(): int {
        $stmt = $this->db->query("SELECT COUNT(*) FROM `{$this->table}`");
        return (int)$stmt->fetchColumn();
    }

    public function insert(array $data): int {
        $cols = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($data)));
        $placeholders = implode(', ', array_map(fn($k) => ":{$k}", array_keys($data)));
        $stmt = $this->db->prepare("INSERT INTO `{$this->table}` ({$cols}) VALUES ({$placeholders})");
        $stmt->execute($data);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $sets = implode(', ', array_map(fn($k) => "`{$k}` = :{$k}", array_keys($data)));
        $data['id'] = $id;
        $stmt = $this->db->prepare("UPDATE `{$this->table}` SET {$sets} WHERE id = :id");
        return $stmt->execute($data);
    }
}
