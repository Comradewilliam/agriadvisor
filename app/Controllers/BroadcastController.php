<?php
namespace App\Controllers;

use App\Core\Database;
use App\Services\AfricasTalkingService;

/**
 * BroadcastController — Mass alert/broadcast to farmers.
 *
 * WAO: broadcast to farmers in their ward(s).
 * DAO: broadcast to all farmers in their district.
 * Admin: broadcast district-wide or system-wide.
 *
 * Each broadcast is:
 *   - Sent via SMS using AfricasTalkingService (real AT call)
 *   - Inserted into the `messages` table (channel='web') so it appears
 *     in each farmer's web chat as an official notification.
 *   - Logged in audit_log for admin traceability.
 */
class BroadcastController {

    private function requireOfficer(): array {
        if (empty($_SESSION['officer_id'])) {
            header('Location: /login'); exit;
        }
        return [
            'id'   => $_SESSION['officer_id'],
            'role' => $_SESSION['role'],
            'name' => $_SESSION['officer_name'] ?? 'Officer',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /officer/broadcasts  — Show broadcast UI
    // ─────────────────────────────────────────────────────────────────────────
    public function index(): void {
        $officer = $this->requireOfficer();
        $db = Database::getInstance()->getConnection();

        // Build available ward/district filter options based on role
        $wards = [];
        if ($officer['role'] === 'ward_officer') {
            $stmt = $db->prepare(
                "SELECT w.id, w.name FROM wards w
                 JOIN officer_wards ow ON ow.ward_id = w.id
                 WHERE ow.officer_id = ?"
            );
            $stmt->execute([$officer['id']]);
            $wards = $stmt->fetchAll();

        } elseif ($officer['role'] === 'dao') {
            $stmt = $db->prepare(
                "SELECT w.id, w.name FROM wards w
                 JOIN districts d ON d.id = w.district_id
                 JOIN officer_districts od ON od.district_id = d.id
                 WHERE od.officer_id = ?"
            );
            $stmt->execute([$officer['id']]);
            $wards = $stmt->fetchAll();

        } elseif ($officer['role'] === 'super_admin') {
            $wards = $db->query("SELECT id, name FROM wards ORDER BY name")->fetchAll();
        }

        // Load all crops for crop-based targeting
        $crops = $db->query("SELECT id, name_sw, name_en FROM crops ORDER BY name_sw")->fetchAll();

        // Recent broadcasts sent by this officer
        $recent = $db->prepare(
            "SELECT b.*, u.name as sender_name FROM broadcasts b
             LEFT JOIN users u ON u.id = b.officer_id
             WHERE b.officer_id = ?
             ORDER BY b.sent_at DESC LIMIT 20"
        );
        $recent->execute([$officer['id']]);
        $broadcasts = $recent->fetchAll();

        $this->view('officer/broadcasts', compact('officer', 'wards', 'crops', 'broadcasts'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /officer/broadcasts/send  — Execute the broadcast
    // ─────────────────────────────────────────────────────────────────────────
    public function send(): void {
        $officer = $this->requireOfficer();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /officer/broadcasts'); exit;
        }

        $db      = Database::getInstance()->getConnection();
        $message = trim($_POST['message'] ?? '');
        $wardIds = $_POST['ward_ids'] ?? [];  // array of ward IDs, or ['all']
        $cropId  = (int)($_POST['crop_id'] ?? 0);

        if (strlen($message) < 5) {
            header('Location: /officer/broadcasts?error=Ujumbe mfupi sana'); exit;
        }

        // Build farmer query
        $sql    = "SELECT f.id, f.phone, CONCAT(f.first_name, ' ', f.last_name) AS name FROM farmers f WHERE f.is_active = 1";
        $params = [];

        if (!empty($wardIds) && $wardIds[0] !== 'all') {
            $placeholders = implode(',', array_fill(0, count($wardIds), '?'));
            $sql .= " AND f.ward_id IN ({$placeholders})";
            $params = array_merge($params, $wardIds);
        } else {
            // Role restriction: WAO can only target their wards
            if ($officer['role'] === 'ward_officer') {
                $stmt = $db->prepare(
                    "SELECT ward_id FROM officer_wards WHERE officer_id = ?"
                );
                $stmt->execute([$officer['id']]);
                $myWards = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                if (!empty($myWards)) {
                    $ph = implode(',', array_fill(0, count($myWards), '?'));
                    $sql .= " AND f.ward_id IN ({$ph})";
                    $params = array_merge($params, $myWards);
                }
            } elseif ($officer['role'] === 'dao') {
                $stmt = $db->prepare(
                    "SELECT w.id FROM wards w
                     JOIN officer_districts od ON od.district_id = w.district_id
                     WHERE od.officer_id = ?"
                );
                $stmt->execute([$officer['id']]);
                $myWards = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                if (!empty($myWards)) {
                    $ph = implode(',', array_fill(0, count($myWards), '?'));
                    $sql .= " AND f.ward_id IN ({$ph})";
                    $params = array_merge($params, $myWards);
                }
            }
        }

        // Optional crop filter
        if ($cropId > 0) {
            $sql .= " AND EXISTS (SELECT 1 FROM farmer_crops fc WHERE fc.farmer_id = f.id AND fc.crop_id = ?)";
            $params[] = $cropId;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $farmers = $stmt->fetchAll();

        if (empty($farmers)) {
            header('Location: /officer/broadcasts?error=Hakuna wakulima waliopatikana'); exit;
        }

        // Log the broadcast record first
        $db->exec("
            CREATE TABLE IF NOT EXISTS `broadcasts` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `officer_id` INT NOT NULL,
                `message` TEXT NOT NULL,
                `target_count` INT DEFAULT 0,
                `sent_count` INT DEFAULT 0,
                `target_description` VARCHAR(255) DEFAULT NULL,
                `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $broadcastStmt = $db->prepare(
            "INSERT INTO broadcasts (officer_id, message, target_count, target_description, sent_at)
             VALUES (?, ?, ?, ?, NOW())"
        );

        $targetDesc = empty($wardIds) || $wardIds[0] === 'all' ? 'All Farmers' : 'Selected Wards';
        if ($cropId > 0) {
            $cropRow = $db->prepare("SELECT name_sw FROM crops WHERE id = ?");
            $cropRow->execute([$cropId]);
            $cropName = $cropRow->fetchColumn();
            $targetDesc .= " – {$cropName} Farmers";
        }

        $broadcastStmt->execute([$officer['id'], $message, count($farmers), $targetDesc]);
        $broadcastId = (int)$db->lastInsertId();

        $atService = new AfricasTalkingService();
        $smsText = "[Agri-Advisory] " . $message;
        $phones = [];
        $phoneToFarmer = [];

        foreach ($farmers as $farmer) {
            $db->prepare(
                "INSERT INTO officer_messages (farmer_id, direction, channel, content, officer_id, sent_at)
                 VALUES (?, 'out', 'web', ?, ?, NOW())"
            )->execute([$farmer['id'], "[TAARIFA RASMI] " . $message, $officer['id']]);

            if (!empty($farmer['phone'])) {
                $phones[] = $farmer['phone'];
                $phoneToFarmer[$farmer['phone']] = (int)$farmer['id'];
            }
        }

        $bulk = $atService->sendBulkSms($phones, $smsText);
        $sentCount = $bulk['sent'];

        foreach ($bulk['recipients'] as $recipient) {
            $phone = $recipient['number'] ?? '';
            $providerId = $recipient['messageId'] ?? null;
            $farmerId = $phoneToFarmer[$phone] ?? null;
            if ($farmerId && $providerId) {
                $db->prepare(
                    "INSERT INTO officer_messages (farmer_id, direction, channel, content, officer_id, provider_message_id, sent_at)
                     VALUES (?, 'out', 'sms', ?, ?, ?, NOW())"
                )->execute([$farmerId, $smsText, $officer['id'], $providerId]);
            }
        }

        if ($sentCount === 0 && !empty($phones)) {
            foreach ($farmers as $farmer) {
                if (empty($farmer['phone'])) {
                    continue;
                }
                $result = $atService->sendOneWay($farmer['phone'], $smsText);
                $providerId = $result['SMSMessageData']['Recipients'][0]['messageId'] ?? null;
                $db->prepare(
                    "INSERT INTO officer_messages (farmer_id, direction, channel, content, officer_id, provider_message_id, sent_at)
                     VALUES (?, 'out', 'sms', ?, ?, ?, NOW())"
                )->execute([$farmer['id'], $smsText, $officer['id'], $providerId]);
                $sentCount++;
            }
        }

        // Update sent count
        $db->prepare("UPDATE broadcasts SET sent_count = ? WHERE id = ?")->execute([$sentCount, $broadcastId]);

        header("Location: /officer/broadcasts?success=Ujumbe umetumwa kwa wakulima {$sentCount}"); exit;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper
    // ─────────────────────────────────────────────────────────────────────────
    private function view(string $view, array $data = []): void {
        extract($data);
        $content = '';
        ob_start();
        require __DIR__ . '/../../views/' . $view . '.php';
        $content = ob_get_clean();
        require __DIR__ . '/../../views/layouts/app.php';
    }
}
