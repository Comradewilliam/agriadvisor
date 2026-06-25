<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Farmer;
use App\Models\User;
use App\Models\KnowledgeBase;
use App\Helpers\Validator;
use App\Helpers\Sanitizer;
use App\Helpers\Logger;

class AdminController extends Controller {

    private function requireAdmin(): array {
        if (!isset($_SESSION['officer_id']) || $_SESSION['role'] !== 'super_admin') {
            header('Location: /login'); exit;
        }
        return ['id' => (int)$_SESSION['officer_id'], 'role' => 'super_admin', 'name' => $_SESSION['officer_name'] ?? 'Admin'];
    }

    public function dashboard() {
        $admin = $this->requireAdmin();
        $db    = Database::getInstance()->getConnection();

        $totalFarmers  = (new Farmer())->count();
        $totalOfficers = (int)$db->query("SELECT COUNT(*) FROM users WHERE role IN ('ward_officer','dao') AND is_active = 1")->fetchColumn();
        $totalKb       = (new KnowledgeBase())->count();
        $totalMsgs     = (int)$db->query("SELECT COUNT(*) FROM ai_messages")->fetchColumn();
        $pendingEsc    = (int)$db->query("SELECT COUNT(*) FROM escalations WHERE status = 'pending'")->fetchColumn();

        $farmersThisMonth = (int)$db->query("
            SELECT COUNT(*) FROM farmers WHERE registered_at >= DATE_FORMAT(NOW(), '%Y-%m-01')
        ")->fetchColumn();

        $aiHighConfidence = (int)$db->query("SELECT COUNT(*) FROM ai_messages WHERE direction = 'out' AND ai_confidence = 'high'")->fetchColumn();
        $aiTotalOut = (int)$db->query("SELECT COUNT(*) FROM ai_messages WHERE direction = 'out' AND ai_confidence IS NOT NULL")->fetchColumn();
        $aiAccuracy = $aiTotalOut > 0 ? round(($aiHighConfidence / $aiTotalOut) * 100, 1) : 0;

        $activeWeatherAlerts = (int)$db->query("
            SELECT COUNT(*) FROM weather_alerts WHERE active = 1 AND (expires_at IS NULL OR expires_at > NOW())
        ")->fetchColumn();

        $msgStats = $db->query("
             SELECT DATE(sent_at) AS day, channel, COUNT(*) AS cnt
             FROM ai_messages WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND direction = 'in'
             GROUP BY day, channel ORDER BY day
        ")->fetchAll();

        $channelTotals = $db->query("
            SELECT channel, COUNT(*) AS cnt FROM ai_messages WHERE direction = 'in' GROUP BY channel
        ")->fetchAll();

        $escalationStats = $db->query("
            SELECT status, COUNT(*) AS cnt FROM escalations GROUP BY status
        ")->fetchAll();

        $wardStats = $db->query("
             SELECT w.name AS ward_name, d.name AS district_name, COUNT(f.id) AS farmer_count,
                    (SELECT COUNT(*) FROM users u JOIN officer_wards ow ON ow.officer_id = u.id WHERE ow.ward_id = w.id AND u.role = 'ward_officer' AND u.is_active = 1) AS officer_count
             FROM wards w
             JOIN districts d ON d.id = w.district_id
             LEFT JOIN farmers f ON f.ward_id = w.id
             GROUP BY w.id ORDER BY farmer_count DESC LIMIT 10
        ")->fetchAll();

        $districtStats = $db->query("
            SELECT d.name AS district_name, d.region, COUNT(DISTINCT f.id) AS farmer_count,
                   COUNT(DISTINCT ow.officer_id) AS officer_count
            FROM districts d
            LEFT JOIN wards w ON w.district_id = d.id
            LEFT JOIN farmers f ON f.ward_id = w.id
            LEFT JOIN officer_wards ow ON ow.ward_id = w.id
            GROUP BY d.id
            ORDER BY farmer_count DESC
            LIMIT 8
        ")->fetchAll();

        $recentEscalations = $db->query("
            SELECT e.priority, e.status, m.content, e.escalated_at
            FROM escalations e
            JOIN ai_messages m ON m.id = e.ai_message_id
            ORDER BY e.escalated_at DESC LIMIT 5
        ")->fetchAll();

        $weatherAdvisories = $db->query("
            SELECT title, severity, alert_type, created_at
            FROM weather_alerts
            WHERE active = 1 AND (expires_at IS NULL OR expires_at > NOW())
            ORDER BY created_at DESC LIMIT 5
        ")->fetchAll();

        $officers = (new User())->getAllOfficers();

        $this->view('admin/dashboard', compact(
            'admin', 'totalFarmers', 'totalOfficers', 'totalKb', 'totalMsgs', 'pendingEsc',
            'farmersThisMonth', 'aiAccuracy', 'activeWeatherAlerts', 'msgStats', 'channelTotals',
            'escalationStats', 'wardStats', 'districtStats', 'recentEscalations', 'weatherAdvisories', 'officers'
        ));
    }

    // ─── DISTRICTS, WARDS, VILLAGES ──────────────────────────────────────────

    public function districts() {
        $admin = $this->requireAdmin();
        $this->view('admin/districts', compact('admin'));
    }

    public function createDistrict() {
        $admin = $this->requireAdmin();
        $name = trim($_POST['name'] ?? '');
        $region = trim($_POST['region'] ?? 'Kigoma');
        
        if ($name) {
            $db = Database::getInstance()->getConnection();
            $db->prepare("INSERT INTO districts (name, region) VALUES (?, ?)")->execute([$name, $region]);
            $this->logAudit($admin['id'], 'create_district', 'district', $db->lastInsertId(), ['name' => $name]);
        }
        header('Location: /admin/districts'); exit;
    }

    public function updateDistrict() {
        $admin = $this->requireAdmin();
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $region = trim($_POST['region'] ?? '');
        
        if ($id && $name) {
            $db = Database::getInstance()->getConnection();
            $db->prepare("UPDATE districts SET name = ?, region = ? WHERE id = ?")->execute([$name, $region, $id]);
            $this->logAudit($admin['id'], 'update_district', 'district', $id, ['name' => $name]);
        }
        header('Location: /admin/districts'); exit;
    }

    public function createWard() {
        $admin = $this->requireAdmin();
        $district_id = (int)($_POST['district_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');

        if ($district_id && $name) {
            $db = Database::getInstance()->getConnection();
            $db->prepare("INSERT INTO wards (name, district_id) VALUES (?, ?)")->execute([$name, $district_id]);
            $this->logAudit($admin['id'], 'create_ward', 'ward', $db->lastInsertId(), ['name' => $name, 'district_id' => $district_id]);
        }
        header('Location: /admin/districts'); exit;
    }

    public function createVillage() {
        $admin = $this->requireAdmin();
        $ward_id = (int)($_POST['ward_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $network = $_POST['network_quality'] ?? 'average';

        if ($ward_id && $name) {
            $db = Database::getInstance()->getConnection();
            $db->prepare("INSERT INTO villages (name, ward_id, network_quality) VALUES (?, ?, ?)")->execute([$name, $ward_id, $network]);
            $this->logAudit($admin['id'], 'create_village', 'village', $db->lastInsertId(), ['name' => $name, 'ward_id' => $ward_id]);
        }
        header('Location: /admin/districts'); exit;
    }

    // ─── LANDING PAGE CMS ───────────────────────────────────────────────────

    public function landingPageCMS() {
        $admin = $this->requireAdmin();
        $db = Database::getInstance()->getConnection();
        
        $cmsData = $db->query("SELECT * FROM cms_content")->fetchAll();
        $cms = [];
        foreach ($cmsData as $item) {
            $cms[$item['key_name']] = $item;
        }

        $this->view('admin/landing_page_cms', compact('admin', 'cms'));
    }

    public function updateLandingPageText() {
        $this->requireAdmin();
        $db = Database::getInstance()->getConnection();
        
        $key = trim($_POST['key_name'] ?? '');
        $content = trim($_POST['content_value'] ?? '');
        
        if ($key) {
            $db->prepare("UPDATE cms_content SET content_value = ? WHERE key_name = ? AND content_type IN ('text', 'html')")->execute([$content, $key]);
        }
        
        header('Location: /admin/landing-page?success=text'); exit;
    }

    public function updateLandingPageImage() {
        $this->requireAdmin();
        $key = trim($_POST['key_name'] ?? '');
        
        if ($key && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/assets/images/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($fileExt, $allowed)) {
                $filename = $key . '_' . time() . '.' . $fileExt;
                $destination = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                    $db = Database::getInstance()->getConnection();
                    $db->prepare("UPDATE cms_content SET content_value = ? WHERE key_name = ? AND content_type = 'image'")
                       ->execute(['/assets/images/' . $filename, $key]);
                    header('Location: /admin/landing-page?success=image'); exit;
                }
            }
        }
        
        header('Location: /admin/landing-page?error=upload'); exit;
    }

    // ─── OFFICERS (USERS) ───────────────────────────────────────────────────

    public function users() {
        $admin = $this->requireAdmin();
        $this->view('admin/users', compact('admin'));
    }

    public function createOfficer() {
        $admin = $this->requireAdmin();

        $validator = Validator::make($_POST);
        if (!$validator->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|max:100',
            'role' => 'required|in:ward_officer,dao,super_admin',
            'phone' => 'nullable|phone',
        ])) {
            header('Location: /admin/users?error=' . urlencode($validator->firstError() ?? 'validation'));
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $userModel = new User();

        $email = Sanitizer::email($_POST['email']);
        if ($userModel->findByEmail($email)) {
            header('Location: /admin/users?error=Email+already+exists');
            exit;
        }

        $userId = $userModel->insert([
            'name'          => Sanitizer::string($_POST['name'], 255),
            'email'         => $email,
            'phone'         => Sanitizer::phone($_POST['phone'] ?? ''),
            'password_hash' => password_hash($_POST['password'], PASSWORD_BCRYPT),
            'role'          => $_POST['role'],
            'created_by'    => $admin['id'],
            'is_active'     => 1,
        ]);

        // Assignments
        if ($_POST['role'] === 'ward_officer') {
            $wardIds = array_map('intval', $_POST['ward_ids'] ?? []);
            if (empty($wardIds) && !empty($_POST['ward_id'])) {
                $wardIds = [(int)$_POST['ward_id']];
            }
            $err = \App\Helpers\OfficerWard::validate($db, $wardIds);
            if ($err) {
                header('Location: /admin/users?error=' . urlencode($err));
                exit;
            }
            \App\Helpers\OfficerWard::sync($db, $userId, $wardIds);
        } elseif ($_POST['role'] === 'dao' && !empty($_POST['district_id'])) {
            $db->prepare("INSERT INTO officer_districts (officer_id, district_id) VALUES (?, ?)")
               ->execute([$userId, (int)$_POST['district_id']]);
        }

        $this->logAudit($admin['id'], 'create_officer', 'user', $userId, ['role' => $_POST['role']]);
        header('Location: /admin/users?success=1'); exit;
    }

    public function updateOfficer() {
        $admin = $this->requireAdmin();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { header('Location: /admin/users'); exit; }

        $db = Database::getInstance()->getConnection();
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        // Update core info
        $sql = "UPDATE users SET name = :n, email = :e, phone = :p WHERE id = :id";
        $params = [':n' => $name, ':e' => $email, ':p' => $phone, ':id' => $id];
        
        // Update password if provided
        if (!empty($_POST['password'])) {
            $sql = "UPDATE users SET name = :n, email = :e, phone = :p, password_hash = :pw WHERE id = :id";
            $params[':pw'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
        }
        
        $db->prepare($sql)->execute($params);

        // Update WAO Ward Assignment if provided
        if (!empty($_POST['role']) && $_POST['role'] === 'ward_officer' && isset($_POST['ward_ids'])) {
            $wardIds = is_array($_POST['ward_ids']) ? $_POST['ward_ids'] : [(int)$_POST['ward_ids']];
            $err = \App\Helpers\OfficerWard::validate($db, $wardIds, $id);
            if ($err) {
                header('Location: /admin/users?error=' . urlencode($err));
                exit;
            }
            \App\Helpers\OfficerWard::sync($db, $id, $wardIds);
        } elseif (!empty($_POST['ward_id'])) {
            $err = \App\Helpers\OfficerWard::validate($db, [(int)$_POST['ward_id']], $id);
            if ($err) {
                header('Location: /admin/users?error=' . urlencode($err));
                exit;
            }
            \App\Helpers\OfficerWard::sync($db, $id, [(int)$_POST['ward_id']]);
        }

        $this->logAudit($admin['id'], 'update_officer', 'user', $id, []);
        header('Location: /admin/users?success=1'); exit;
    }

    public function toggleUser() {
        $admin = $this->requireAdmin();
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db = Database::getInstance()->getConnection();
            $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
            $this->logAudit($admin['id'], 'toggle_officer_status', 'user', $id, []);
        }
        header('Location: /admin/users'); exit;
    }

    public function channelAnalytics() {
        $admin = $this->requireAdmin();
        $db = Database::getInstance()->getConnection();

        $search = trim($_GET['q'] ?? '');
        $category = trim($_GET['category'] ?? '');
        $level = trim($_GET['level'] ?? '');
        $channel = trim($_GET['channel'] ?? '');
        $days = min(90, max(1, (int)($_GET['days'] ?? 30)));
        $limit = min(500, max(50, (int)($_GET['limit'] ?? 200)));

        $tableReady = true;
        try {
            $db->query('SELECT 1 FROM system_events LIMIT 1');
        } catch (\Throwable $e) {
            $tableReady = false;
        }

        $events = [];
        $totalAll = $todayCount = $errorCount = $ussdToday = $smsToday = 0;
        $categoryBreakdown = $eventBreakdown = $dailyStats = $categories = $eventTypes = [];

        if ($tableReady) {
            $where = ['se.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)'];
            $params = [$days];
            if ($search !== '') {
                $where[] = '(se.phone LIKE ? OR se.message LIKE ? OR se.event LIKE ? OR f.name LIKE ?)';
                $like = '%' . $search . '%';
                $params = array_merge($params, [$like, $like, $like, $like]);
            }
            if ($category !== '') {
                $where[] = 'se.category = ?';
                $params[] = $category;
            }
            if ($level !== '' && in_array($level, ['info', 'warning', 'error'], true)) {
                $where[] = 'se.level = ?';
                $params[] = $level;
            }
            if ($channel !== '') {
                $where[] = 'se.channel = ?';
                $params[] = $channel;
            }

            $sql = "
                SELECT se.*, f.name AS farmer_name
                FROM system_events se
                LEFT JOIN farmers f ON f.id = se.farmer_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY se.created_at DESC
                LIMIT {$limit}
            ";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $events = $stmt->fetchAll();

            $totalAll = (int)$db->query('SELECT COUNT(*) FROM system_events')->fetchColumn();
            $todayCount = (int)$db->query("SELECT COUNT(*) FROM system_events WHERE DATE(created_at) = CURDATE()")->fetchColumn();
            $errorCount = (int)$db->query("SELECT COUNT(*) FROM system_events WHERE level = 'error' AND created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)")->fetchColumn();
            $ussdToday = (int)$db->query("SELECT COUNT(*) FROM system_events WHERE channel = 'ussd' AND DATE(created_at) = CURDATE()")->fetchColumn();
            $smsToday = (int)$db->query("SELECT COUNT(*) FROM system_events WHERE channel = 'sms' AND DATE(created_at) = CURDATE()")->fetchColumn();

            $categoryBreakdown = $db->query("
                SELECT category, COUNT(*) AS cnt FROM system_events
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
                GROUP BY category ORDER BY cnt DESC
            ")->fetchAll();

            $eventBreakdown = $db->query("
                SELECT category, event, COUNT(*) AS cnt FROM system_events
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
                GROUP BY category, event ORDER BY cnt DESC LIMIT 12
            ")->fetchAll();

            $dailyStats = $db->query("
                SELECT DATE(created_at) AS day, channel, COUNT(*) AS cnt
                FROM system_events
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
                GROUP BY day, channel ORDER BY day
            ")->fetchAll();

            $categories = $db->query("SELECT DISTINCT category FROM system_events ORDER BY category")->fetchAll(\PDO::FETCH_COLUMN);
            $eventTypes = $db->query("SELECT DISTINCT event FROM system_events ORDER BY event")->fetchAll(\PDO::FETCH_COLUMN);
        }

        $this->view('admin/channel_analytics', compact(
            'admin', 'events', 'search', 'category', 'level', 'channel', 'days', 'limit',
            'totalAll', 'todayCount', 'errorCount', 'ussdToday', 'smsToday',
            'categoryBreakdown', 'eventBreakdown', 'dailyStats', 'categories', 'eventTypes', 'tableReady'
        ));
    }

    public function exportChannelAnalytics() {
        $admin = $this->requireAdmin();
        $db = Database::getInstance()->getConnection();
        $format = strtolower($_GET['format'] ?? 'csv');
        $days = min(90, max(1, (int)($_GET['days'] ?? 30)));

        try {
            $stmt = $db->prepare("
                SELECT se.created_at, se.level, se.category, se.event, se.farmer_id, f.name AS farmer_name,
                       se.phone, se.channel, se.message, se.meta
                FROM system_events se
                LEFT JOIN farmers f ON f.id = se.farmer_id
                WHERE se.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                ORDER BY se.created_at DESC
                LIMIT 2000
            ");
            $stmt->execute([$days]);
            $events = $stmt->fetchAll();
        } catch (\Throwable $e) {
            header('Location: /admin/channel_analytics?error=table');
            exit;
        }

        if ($format === 'pdf') {
            $lines = [
                'Agri-Advisory Channel Analytics Report',
                'Generated: ' . date('Y-m-d H:i') . ' by ' . ($admin['name'] ?? 'Admin'),
                'Period: last ' . $days . ' days',
                '',
                str_repeat('-', 72),
            ];
            foreach ($events as $e) {
                $lines[] = sprintf(
                    '%s | %s | %s.%s | %s | farmer#%s | %s',
                    date('Y-m-d H:i', strtotime($e['created_at'])),
                    strtoupper($e['level']),
                    $e['category'],
                    $e['event'],
                    $e['channel'] ?? '-',
                    $e['farmer_id'] ?? '-',
                    mb_substr($e['message'] ?? '', 0, 80)
                );
            }
            $pdf = \App\Helpers\PdfReport::fromLines($lines);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="channel-analytics-' . date('Y-m-d') . '.pdf"');
            header('Content-Length: ' . strlen($pdf));
            echo $pdf;
            exit;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="channel-analytics-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Timestamp', 'Level', 'Category', 'Event', 'Farmer ID', 'Farmer Name', 'Phone', 'Channel', 'Message', 'Meta']);
        foreach ($events as $e) {
            fputcsv($out, [
                $e['created_at'],
                $e['level'],
                $e['category'],
                $e['event'],
                $e['farmer_id'] ?? '',
                $e['farmer_name'] ?? '',
                $e['phone'] ?? '',
                $e['channel'] ?? '',
                $e['message'] ?? '',
                is_string($e['meta'] ?? '') ? $e['meta'] : json_encode($e['meta'] ?? []),
            ]);
        }
        fclose($out);
        exit;
    }

    public function auditLogs() {
        $admin = $this->requireAdmin();
        $db = Database::getInstance()->getConnection();

        $search = trim($_GET['q'] ?? '');
        $actionFilter = trim($_GET['action'] ?? '');
        $limit = min(500, max(50, (int)($_GET['limit'] ?? 200)));

        $where = ['1=1'];
        $params = [];
        if ($search !== '') {
            $where[] = '(u.name LIKE ? OR a.action LIKE ? OR a.entity_type LIKE ? OR a.actor_role LIKE ?)';
            $like = '%' . $search . '%';
            $params = array_merge($params, [$like, $like, $like, $like]);
        }
        if ($actionFilter !== '') {
            $where[] = 'a.action = ?';
            $params[] = $actionFilter;
        }

        $sql = "
            SELECT a.*, u.name AS actor_name
            FROM audit_log a
            LEFT JOIN users u ON u.id = a.actor_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY a.created_at DESC
            LIMIT {$limit}
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        $totalAll = (int)$db->query('SELECT COUNT(*) FROM audit_log')->fetchColumn();
        $todayCount = (int)$db->query("SELECT COUNT(*) FROM audit_log WHERE DATE(created_at) = CURDATE()")->fetchColumn();
        $securityAlerts = (int)$db->query("
            SELECT COUNT(*) FROM audit_log
            WHERE action IN ('toggle_officer_status','delete_officer','create_officer','update_officer','login_failed','delete_ward','delete_farmer')
        ")->fetchColumn();
        $sensitiveCount = (int)$db->query("
            SELECT COUNT(*) FROM audit_log
            WHERE action LIKE '%delete%' OR action LIKE '%toggle%' OR action LIKE '%create_%'
        ")->fetchColumn();

        $actionTypes = $db->query("SELECT DISTINCT action FROM audit_log ORDER BY action")->fetchAll(\PDO::FETCH_COLUMN);
        $actorBreakdown = $db->query("
            SELECT COALESCE(actor_role, 'system') AS role, COUNT(*) AS cnt
            FROM audit_log GROUP BY actor_role ORDER BY cnt DESC
        ")->fetchAll();

        $this->view('admin/audit_logs', compact(
            'admin', 'logs', 'search', 'actionFilter', 'limit',
            'totalAll', 'todayCount', 'securityAlerts', 'sensitiveCount', 'actionTypes', 'actorBreakdown'
        ));
    }

    public function exportAuditLogs() {
        $admin = $this->requireAdmin();
        $db = Database::getInstance()->getConnection();
        $format = strtolower($_GET['format'] ?? 'pdf');

        $stmt = $db->query("
            SELECT a.created_at, u.name AS actor_name, a.actor_role, a.action, a.entity_type, a.entity_id, a.meta
            FROM audit_log a
            LEFT JOIN users u ON u.id = a.actor_id
            ORDER BY a.created_at DESC
            LIMIT 300
        ");
        $logs = $stmt->fetchAll();

        $totalAll = (int)$db->query('SELECT COUNT(*) FROM audit_log')->fetchColumn();
        $todayCount = (int)$db->query("SELECT COUNT(*) FROM audit_log WHERE DATE(created_at) = CURDATE()")->fetchColumn();
        $securityAlerts = (int)$db->query("
            SELECT COUNT(*) FROM audit_log
            WHERE action IN ('toggle_officer_status','delete_officer','create_officer','update_officer')
        ")->fetchColumn();

        if ($format === 'pdf') {
            $lines = [
                'Agri-Advisory System Audit Report',
                'Generated: ' . date('Y-m-d H:i') . ' by ' . ($admin['name'] ?? 'Admin'),
                '',
                'Executive Summary',
                "Total audit events (all time): {$totalAll}",
                "Events today: {$todayCount}",
                "Security-sensitive actions: {$securityAlerts}",
                '',
                'Recent Events (latest 300)',
                str_repeat('-', 72),
            ];
            foreach ($logs as $l) {
                $meta = is_string($l['meta'] ?? '') ? $l['meta'] : json_encode($l['meta'] ?? []);
                $lines[] = sprintf(
                    '%s | %s (%s) | %s | %s #%s',
                    date('Y-m-d H:i', strtotime($l['created_at'])),
                    $l['actor_name'] ?? 'System',
                    $l['actor_role'] ?? '-',
                    $l['action'],
                    $l['entity_type'] ?? '-',
                    $l['entity_id'] ?? '-'
                );
                if ($meta && $meta !== '{}' && $meta !== 'null') {
                    $lines[] = '  meta: ' . mb_substr($meta, 0, 120);
                }
            }
            $pdf = \App\Helpers\PdfReport::fromLines($lines);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="audit-report-' . date('Y-m-d') . '.pdf"');
            header('Content-Length: ' . strlen($pdf));
            echo $pdf;
            exit;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="audit-logs-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Timestamp', 'Actor', 'Role', 'Action', 'Entity Type', 'Entity ID', 'Meta']);
        foreach ($logs as $l) {
            fputcsv($out, [
                $l['created_at'],
                $l['actor_name'] ?? '',
                $l['actor_role'] ?? '',
                $l['action'],
                $l['entity_type'] ?? '',
                $l['entity_id'] ?? '',
                is_string($l['meta'] ?? '') ? $l['meta'] : json_encode($l['meta'] ?? []),
            ]);
        }
        fclose($out);
        exit;
    }

    private function logAudit(int $actorId, string $action, string $entityType, int $entityId, array $meta = []) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO audit_log (actor_id, actor_role, action, entity_type, entity_id, meta) VALUES (?, 'super_admin', ?, ?, ?, ?)");
        $stmt->execute([$actorId, $action, $entityType, $entityId, json_encode($meta)]);
    }
}
