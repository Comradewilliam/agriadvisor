<?php
namespace App\Services;

use App\Core\Database;

class WeatherAlertService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function expireStaleAlerts(): void {
        $this->db->exec("
            UPDATE weather_alerts
            SET approval_status = 'expired', active = 0
            WHERE approval_status = 'pending'
              AND expires_at IS NOT NULL
              AND expires_at < NOW()
        ");
    }

    public function createAlert(array $data): int {
        $this->expireStaleAlerts();
        $stmt = $this->db->prepare("
            INSERT INTO weather_alerts
                (district_id, ward_id, title, message, alert_type, severity,
                 approval_status, active, expires_at, created_by)
            VALUES
                (:district_id, :ward_id, :title, :message, :alert_type, :severity,
                 'pending', 0, DATE_ADD(NOW(), INTERVAL 48 HOUR), :created_by)
        ");
        $stmt->execute([
            ':district_id' => $data['district_id'] ?? null,
            ':ward_id'     => $data['ward_id'] ?? null,
            ':title'       => $data['title'],
            ':message'     => $data['message'],
            ':alert_type'  => $data['alert_type'] ?? 'general',
            ':severity'    => $data['severity'] ?? 'medium',
            ':created_by'  => $data['created_by'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function recordApproval(int $alertId, array $officer): bool {
        $alert = $this->getAlert($alertId);
        if (!$alert || $alert['approval_status'] !== 'pending') {
            return false;
        }

        $role = $officer['role'];
        $oid  = (int)$officer['id'];

        if ($role === 'ward_officer' && empty($alert['approved_by_ward'])) {
            $this->db->prepare("
                UPDATE weather_alerts SET approved_by_ward = ?, ward_approved_at = NOW() WHERE id = ?
            ")->execute([$oid, $alertId]);
        } elseif ($role === 'dao' && empty($alert['approved_by_dao'])) {
            $this->db->prepare("
                UPDATE weather_alerts SET approved_by_dao = ?, dao_approved_at = NOW() WHERE id = ?
            ")->execute([$oid, $alertId]);
        } elseif ($role === 'super_admin' && empty($alert['approved_by_admin'])) {
            $this->db->prepare("
                UPDATE weather_alerts SET approved_by_admin = ?, admin_approved_at = NOW() WHERE id = ?
            ")->execute([$oid, $alertId]);
        } else {
            return false;
        }

        $alert = $this->getAlert($alertId);
        if ($this->isFullyApproved($alert)) {
            $this->finalizeApproval($alertId);
        }
        return true;
    }

    public function rejectAlert(int $alertId): void {
        $this->db->prepare("
            UPDATE weather_alerts SET approval_status = 'rejected', active = 0 WHERE id = ?
        ")->execute([$alertId]);
    }

    public function isFullyApproved(?array $alert): bool {
        if (!$alert) return false;
        return !empty($alert['approved_by_ward'])
            && !empty($alert['approved_by_dao'])
            && !empty($alert['approved_by_admin']);
    }

    private function finalizeApproval(int $alertId): void {
        $this->db->prepare("
            UPDATE weather_alerts SET approval_status = 'approved', active = 1 WHERE id = ?
        ")->execute([$alertId]);
        $this->broadcastToFarmers($alertId);
    }

    public function getPendingAlerts(?int $districtId = null): array {
        $this->expireStaleAlerts();
        $sql = "
            SELECT wa.*, u.name AS creator_name,
                   w.name AS ward_name, d.name AS district_name
            FROM weather_alerts wa
            JOIN users u ON u.id = wa.created_by
            LEFT JOIN wards w ON w.id = wa.ward_id
            LEFT JOIN districts d ON d.id = wa.district_id
            WHERE wa.approval_status = 'pending'
        ";
        if ($districtId) {
            $sql .= " AND (wa.district_id = " . (int)$districtId . " OR wa.district_id IS NULL)";
        }
        $sql .= " ORDER BY wa.created_at DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function getAlert(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM weather_alerts WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    private function broadcastToFarmers(int $alertId): void {
        $alert = $this->getAlert($alertId);
        if (!$alert) return;

        $sql = "SELECT id, phone FROM farmers WHERE is_active = 1";
        $params = [];
        if ($alert['ward_id']) {
            $sql .= " AND ward_id = ?";
            $params[] = $alert['ward_id'];
        } elseif ($alert['district_id']) {
            $sql = "SELECT f.id, f.phone FROM farmers f
                    JOIN wards w ON w.id = f.ward_id
                    WHERE f.is_active = 1 AND w.district_id = ?";
            $params[] = $alert['district_id'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $farmers = $stmt->fetchAll();

        $at = new AfricasTalkingService();
        $smsText = substr($alert['title'] . ': ' . $alert['message'], 0, 160);

        $ins = $this->db->prepare("
            INSERT INTO officer_messages (farmer_id, direction, channel, content, is_system_alert, sent_at)
            VALUES (?, 'out', 'sms', ?, 1, NOW())
        ");

        $phones = [];
        foreach ($farmers as $f) {
            $ins->execute([(int)$f['id'], $smsText]);
            if (!empty($f['phone'])) {
                $phones[] = $f['phone'];
            }
        }

        if ($phones) {
            $at->sendBulkSms($phones, $smsText);
        }
    }
}
