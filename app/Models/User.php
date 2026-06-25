<?php
namespace App\Models;

class User extends BaseModel {
    protected $table = 'users';

    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM `users` WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    /** Officers and DAOs assigned to a given ward */
    public function getByWard(int $wardId): array {
        $stmt = $this->db->prepare(
            "SELECT u.* FROM users u
             JOIN officer_wards ow ON ow.officer_id = u.id
             WHERE ow.ward_id = :ward_id"
        );
        $stmt->execute(['ward_id' => $wardId]);
        return $stmt->fetchAll();
    }

    public function getAllOfficers(): array {
        $stmt = $this->db->prepare(
            "SELECT u.*, GROUP_CONCAT(w.name SEPARATOR ', ') AS wards
             FROM users u
             LEFT JOIN officer_wards ow ON ow.officer_id = u.id
             LEFT JOIN wards w ON w.id = ow.ward_id
             WHERE u.role IN ('ward_officer','dao')
             GROUP BY u.id
             ORDER BY u.name"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
