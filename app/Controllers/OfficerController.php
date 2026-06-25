<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\User;
use App\Models\Farmer;
use App\Models\Visit;
use App\Models\VisitRequest;
use App\Models\KnowledgeBase;
use App\Models\Crop;
use App\Models\Ward;
use App\Models\Village;
use App\Models\AutomatedAlert;
use App\Services\AfricasTalkingService;
use App\Services\RagService;
use App\Services\WeatherService;
use App\Helpers\Validator;
use App\Helpers\Sanitizer;
use App\Helpers\Logger;

class OfficerController extends Controller
{

    private function requireAuth(string $redirect = '/login'): array
    {
        if (!isset($_SESSION['officer_id'])) {
            header("Location: {$redirect}");
            exit;
        }
        return [
            'id'   => (int)$_SESSION['officer_id'],
            'role' => $_SESSION['role'],
            'name' => $_SESSION['officer_name'] ?? 'Officer',
            'ward_id' => (int)($_SESSION['ward_id'] ?? 0),
            'ward_ids' => $_SESSION['ward_ids'] ?? [],
        ];
    }

    private function requireDao(): array
    {
        $officer = $this->requireAuth();
        if ($officer['role'] !== 'dao' && $officer['role'] !== 'super_admin') {
            header("Location: /officer/dashboard");
            exit;
        }
        return $officer;
    }

    // ─────────────────────────────────────────────
    // DASHBOARD
    // ─────────────────────────────────────────────
    public function dashboard()
    {
        $officer  = $this->requireAuth();
        $wardIds  = $officer['ward_ids'];

        $farmerModel = new Farmer();
        $visitModel  = new Visit();
        $db = Database::getInstance()->getConnection();

        $farmerCount = 0;
        if (!empty($wardIds)) {
            $in = implode(',', array_map('intval', $wardIds));
            $farmerCount = (int)$db->query("SELECT COUNT(*) FROM farmers WHERE ward_id IN ({$in})")->fetchColumn();
        } elseif ($officer['role'] !== 'ward_officer') {
            $farmerCount = $farmerModel->count();
        }

        $upcomingVisits  = $visitModel->countUpcoming($officer['id']);

        // Weather alerts
        $alertStmt = $db->query("SELECT COUNT(*) FROM weather_alerts WHERE approval_status = 'pending'");
        $activeAlerts = (int)$alertStmt->fetchColumn();

        // Escalations count
        $escQuery = "
            SELECT COUNT(*) FROM escalations e
            JOIN ai_messages m ON m.id = e.ai_message_id
            JOIN farmers f ON f.id = m.farmer_id
            WHERE e.status = 'pending'
        ";
        if (!empty($wardIds)) {
            $in = implode(',', array_map('intval', $wardIds));
            $escQuery .= " AND f.ward_id IN ({$in})";
        }
        $pendingEsc = (int)$db->query($escQuery)->fetchColumn();

        // Recent farmers
        if (!empty($wardIds)) {
            $in = implode(',', array_map('intval', $wardIds));
            $recentFarmers = $db->query("SELECT f.*, CONCAT(f.first_name, ' ', f.last_name) AS name, v.name AS village_name, w.name AS ward_name FROM farmers f LEFT JOIN villages v ON v.id = f.village_id LEFT JOIN wards w ON w.id = f.ward_id WHERE f.ward_id IN ({$in}) ORDER BY f.registered_at DESC LIMIT 5")->fetchAll();
        } else {
            $recentFarmers = $farmerModel->getAllWithDetails();
            $recentFarmers = array_slice($recentFarmers, 0, 5);
        }

        $recentVisits = $visitModel->getByOfficer($officer['id']);
        $recentVisits = array_slice($recentVisits, 0, 5);

        $this->view('officer/dashboard', compact(
            'officer',
            'farmerCount',
            'pendingEsc',
            'upcomingVisits',
            'activeAlerts',
            'recentFarmers',
            'recentVisits'
        ));
    }

    // ─────────────────────────────────────────────
    // FARMERS
    // ─────────────────────────────────────────────
    public function farmers()
    {
        $officer  = $this->requireAuth();
        $wardIds  = $officer['ward_ids'];
        $search   = trim($_GET['q'] ?? '');

        $farmerModel  = new Farmer();
        $wardModel    = new Ward();
        $db = Database::getInstance()->getConnection();

        $farmers = [];
        if (!empty($wardIds)) {
            $in = implode(',', array_map('intval', $wardIds));
            $sql = "SELECT f.*, CONCAT(f.first_name, ' ', f.last_name) AS name, v.name AS village_name, w.name AS ward_name FROM farmers f LEFT JOIN villages v ON v.id = f.village_id LEFT JOIN wards w ON w.id = f.ward_id WHERE f.ward_id IN ({$in})";
            $params = [];
            if ($search) {
                $sql .= " AND (f.first_name LIKE :s OR f.last_name LIKE :s OR f.phone LIKE :s)";
                $params['s'] = "%{$search}%";
            }
            $sql .= " ORDER BY f.registered_at DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $farmers = $stmt->fetchAll();
        } else {
            $farmers = $farmerModel->getAllWithDetails($search);
        }

        $wards = $wardModel->getAll('name ASC');

        $this->view('officer/farmers', compact('farmers', 'wards', 'search', 'officer'));
    }

    public function viewFarmer()
    {
        $officer = $this->requireAuth();
        $id = (int)($_GET['id'] ?? 0);
        $farmerModel = new Farmer();
        $farmer = $farmerModel->findWithDetails($id);
        if (!$farmer) {
            http_response_code(404);
            echo 'Farmer not found';
            return;
        }

        if (!$this->farmerInOfficerScope($officer, (int)$farmer['ward_id'])) {
            header('Location: /officer/farmers?error=access');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $visitModel = new Visit();
        $farmerVisits = $visitModel->getScoped($officer, 'all');
        $farmerVisits = array_values(array_filter($farmerVisits, fn($v) => (int)$v['farmer_id'] === $id));

        $interactions = $this->buildFarmerInteractions($db, $id);

        $cropProgress = $this->buildCropProgress($farmer);
        $weather = ['description' => '', 'temp' => null];
        if (!empty($farmer['village_id'])) {
            try {
                $wxRaw = (new WeatherService())->getWeatherByVillage((int)$farmer['village_id']);
                $current = $wxRaw['current'] ?? [];
                $weather = [
                    'temp'        => $current['main']['temp'] ?? null,
                    'description' => $current['weather'][0]['description'] ?? '',
                ];
            } catch (\Throwable $e) {
                Logger::warning('Farmer detail weather failed', ['error' => $e->getMessage()]);
            }
        }

        $filterTab = in_array($_GET['tab'] ?? 'all', ['all', 'visit', 'chat'], true) ? ($_GET['tab'] ?? 'all') : 'all';

        $this->view('officer/farmer_detail', compact(
            'farmer', 'farmerVisits', 'interactions', 'weather', 'cropProgress', 'filterTab', 'officer'
        ));
    }

    private function farmerInOfficerScope(array $officer, ?int $wardId): bool
    {
        if (!$wardId) {
            return $officer['role'] === 'dao' || ($officer['role'] ?? '') === 'super_admin';
        }
        if ($officer['role'] === 'ward_officer') {
            return in_array($wardId, array_map('intval', $officer['ward_ids'] ?? []), true);
        }
        if ($officer['role'] === 'dao') {
            $districtId = $this->getDaoDistrictId((int)$officer['id']);
            if (!$districtId) {
                return false;
            }
            $stmt = Database::getInstance()->getConnection()->prepare('SELECT id FROM wards WHERE id = ? AND district_id = ?');
            $stmt->execute([$wardId, $districtId]);
            return (bool)$stmt->fetch();
        }
        return true;
    }

    /** @return array<int, array<string, string>> */
    private function buildFarmerInteractions(\PDO $db, int $farmerId): array
    {
        $items = [];

        $msgStmt = $db->prepare("SELECT direction, channel, content, sent_at FROM ai_messages WHERE farmer_id = ? ORDER BY sent_at DESC LIMIT 30");
        $msgStmt->execute([$farmerId]);
        foreach ($msgStmt->fetchAll() as $m) {
            $items[] = [
                'type'        => 'chat',
                'title'       => $m['direction'] === 'in' ? 'Farmer message' : 'BwanaShamba AI',
                'date_label'  => date('M j, Y H:i', strtotime($m['sent_at'])),
                'sort'        => strtotime($m['sent_at']),
                'status'      => $m['channel'] === 'sms' ? 'SMS' : 'Web',
                'badge_class' => 'bg-primary-fixed text-primary',
                'desc'        => mb_substr($m['content'], 0, 300),
            ];
        }

        $visitStmt = $db->prepare("
            SELECT v.scheduled_at, v.reason, v.status, v.notes, u.name AS officer_name
            FROM visits v LEFT JOIN users u ON u.id = v.officer_id
            WHERE v.farmer_id = ? ORDER BY v.scheduled_at DESC LIMIT 20
        ");
        $visitStmt->execute([$farmerId]);
        foreach ($visitStmt->fetchAll() as $v) {
            $items[] = [
                'type'        => 'visit',
                'title'       => 'Farm visit' . ($v['officer_name'] ? ' — ' . $v['officer_name'] : ''),
                'date_label'  => date('M j, Y H:i', strtotime($v['scheduled_at'])),
                'sort'        => strtotime($v['scheduled_at']),
                'status'      => ucfirst($v['status'] ?? 'scheduled'),
                'badge_class' => match ($v['status'] ?? '') {
                    'completed' => 'bg-primary-fixed text-primary',
                    'scheduled', 'pending' => 'bg-tertiary-fixed text-tertiary',
                    default => 'bg-surface-container text-outline',
                },
                'desc'        => trim(($v['reason'] ?? '') . ($v['notes'] ? "\n" . $v['notes'] : '')),
            ];
        }

        $bcStmt = $db->prepare("SELECT content, sent_at FROM officer_messages WHERE farmer_id = ? ORDER BY sent_at DESC LIMIT 10");
        $bcStmt->execute([$farmerId]);
        foreach ($bcStmt->fetchAll() as $b) {
            $items[] = [
                'type'        => 'broadcast',
                'title'       => 'Officer broadcast',
                'date_label'  => date('M j, Y H:i', strtotime($b['sent_at'])),
                'sort'        => strtotime($b['sent_at']),
                'status'      => 'Sent',
                'badge_class' => 'bg-amber-100 text-amber-800',
                'desc'        => mb_substr($b['content'], 0, 300),
            ];
        }

        usort($items, fn($a, $b) => ($b['sort'] ?? 0) <=> ($a['sort'] ?? 0));
        return $items;
    }

    /** @param array<string, mixed> $farmer */
    private function buildCropProgress(array $farmer): array
    {
        $primary = null;
        foreach ($farmer['crops'] ?? [] as $c) {
            if (($c['type'] ?? '') === 'primary') {
                $primary = $c;
                break;
            }
        }
        if (!$primary && !empty($farmer['crops'])) {
            $primary = $farmer['crops'][0];
        }

        $progressPct = 25;
        $stageName = '—';
        if (!empty($primary['growth_stage_id'])) {
            $db = Database::getInstance()->getConnection();
            $gs = $db->prepare('SELECT name_en, sort_order FROM growth_stages WHERE id = ?');
            $gs->execute([(int)$primary['growth_stage_id']]);
            $row = $gs->fetch();
            if ($row) {
                $stageName = $row['name_en'];
                $max = $db->prepare('SELECT MAX(sort_order) FROM growth_stages WHERE crop_id = ?');
                $max->execute([(int)$primary['id']]);
                $maxOrder = max(1, (int)$max->fetchColumn());
                $progressPct = (int)round(((int)$row['sort_order'] / $maxOrder) * 100);
            }
        }

        $month = (int)date('n');
        $season = ($month >= 3 && $month <= 6) ? 'Masika' : (($month >= 10 && $month <= 12) ? 'Vuli' : 'Kiangazi');

        return [
            'crop_name'    => $primary['name'] ?? '—',
            'planted_date' => $primary['planted_date'] ?? null,
            'stage_name'   => $stageName,
            'progress_pct' => $progressPct,
            'season'       => $season,
        ];
    }

    public function editFarmer()
    {
        $officer = $this->requireDao();
        $this->view('officer/edit_farmer', compact('officer'));
    }

    public function updateFarmerProfile()
    {
        $officer = $this->requireDao();
        $db = Database::getInstance()->getConnection();

        $farmerId = (int)($_POST['farmer_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $ward_id = (int)($_POST['ward_id'] ?? 0);
        $village_id = (int)($_POST['village_id'] ?? 0);
        $farm_size = (float)($_POST['farm_size_acres'] ?? 0);
        $primary_crop_id = (int)($_POST['primary_crop_id'] ?? 0);
        $secondary_crop_ids = $_POST['secondary_crop_ids'] ?? [];

        if (!$farmerId || !$name || !$phone) {
            header('Location: /officer/farmers/view?id=' . $farmerId . '&error=missing');
            exit;
        }

        try {
            $db->beginTransaction();
            $parts = explode(' ', $name, 2);
            $fn = $parts[0];
            $ln = $parts[1] ?? '';

            $db->prepare("UPDATE farmers SET first_name=?, last_name=?, phone=?, ward_id=?, village_id=?, farm_size_acres=? WHERE id=?")
                ->execute([$fn, $ln, $phone, $ward_id ?: null, $village_id ?: null, $farm_size ?: null, $farmerId]);

            $db->prepare("DELETE FROM farmer_crops WHERE farmer_id = ?")->execute([$farmerId]);
            if ($primary_crop_id > 0) {
                $db->prepare("INSERT INTO farmer_crops (farmer_id, crop_id, type) VALUES (?, ?, 'primary')")->execute([$farmerId, $primary_crop_id]);
            }

            foreach ($secondary_crop_ids as $cid) {
                $cid = (int)$cid;
                if ($cid > 0 && $cid !== $primary_crop_id) {
                    $db->prepare("INSERT INTO farmer_crops (farmer_id, crop_id, type) VALUES (?, ?, 'secondary') ON DUPLICATE KEY UPDATE type=type")->execute([$farmerId, $cid]);
                }
            }

            $db->commit();
            header('Location: /officer/farmers/view?id=' . $farmerId . '&success=1');
            exit;
        } catch (\Exception $e) {
            $db->rollBack();
            header('Location: /officer/farmers/view?id=' . $farmerId . '&error=update_failed');
            exit;
        }
    }

    public function addFarmer()
    {
        $officer = $this->requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $crops    = (new Crop())->getActive();
            $wards    = (new Ward())->getAll('name ASC');
            $this->view('officer/add_farmer', compact('crops', 'wards', 'officer'));
            return;
        }

        $required = ['name', 'phone', 'ward_id', 'village_id'];
        foreach ($required as $f) {
            if (empty($_POST[$f])) {
                $_SESSION['flash'] = "All fields are required.";
                header('Location: /officer/farmers/add');
                exit;
            }
        }

        $farmerModel = new Farmer();
        if ($farmerModel->findByPhone($_POST['phone'])) {
            $_SESSION['flash'] = "A farmer with this phone already exists.";
            header('Location: /officer/farmers/add');
            exit;
        }

        $farmerId = $farmerModel->create([
            'name'           => htmlspecialchars(trim($_POST['name'])),
            'phone'          => trim($_POST['phone']),
            'ward_id'        => (int)$_POST['ward_id'],
            'village_id'     => (int)$_POST['village_id'],
            'registered_via' => 'officer',
        ]);

        if (!empty($_POST['crop_id'])) {
            $farmerModel->addCrop($farmerId, (int)$_POST['crop_id'], 'primary');
        }

        $_SESSION['flash'] = "Farmer registered successfully!";
        header('Location: /officer/farmers');
        exit;
    }

    // ─────────────────────────────────────────────
    // DAO: MANAGE WARD OFFICERS
    // ─────────────────────────────────────────────
    public function manageWardOfficers()
    {
        $officer = $this->requireDao();
        $db = Database::getInstance()->getConnection();
        $officerId = (int)$officer['id'];

        $daoDistricts = $db->prepare('SELECT d.id, d.name FROM districts d JOIN officer_districts od ON od.district_id = d.id WHERE od.officer_id = ?');
        $daoDistricts->execute([$officerId]);
        $myDistricts = $daoDistricts->fetchAll();
        $districtIds = array_column($myDistricts, 'id');

        $wardOfficers = [];
        $wards = [];
        if (!empty($districtIds)) {
            $pl = implode(',', array_map('intval', $districtIds));
            $wardOfficers = $db->query("
                SELECT u.*,
                       GROUP_CONCAT(DISTINCT w.name ORDER BY w.name SEPARATOR ', ') AS assigned_wards,
                       GROUP_CONCAT(DISTINCT w.id   ORDER BY w.name SEPARATOR ',')  AS assigned_ward_ids
                FROM users u
                LEFT JOIN officer_wards ow ON ow.officer_id = u.id
                LEFT JOIN wards w ON w.id = ow.ward_id AND w.district_id IN ({$pl})
                WHERE u.role = 'ward_officer'
                GROUP BY u.id
                ORDER BY u.name
            ")->fetchAll();

            $wards = $db->query("
                SELECT w.id, w.name, d.name AS district_name
                FROM wards w JOIN districts d ON d.id = w.district_id
                WHERE d.id IN ({$pl}) ORDER BY d.name, w.name
            ")->fetchAll();
        }

        $search = trim($_GET['q'] ?? '');
        $letter = strtoupper(trim($_GET['letter'] ?? ''));
        $wardFilter = (int)($_GET['ward_id'] ?? 0);

        if ($search !== '') {
            $q = mb_strtolower($search);
            $wardOfficers = array_values(array_filter($wardOfficers, function ($o) use ($q) {
                return str_contains(mb_strtolower($o['name'] ?? ''), $q)
                    || str_contains(mb_strtolower($o['email'] ?? ''), $q)
                    || str_contains(mb_strtolower($o['phone'] ?? ''), $q);
            }));
        }
        if ($letter !== '' && preg_match('/^[A-Z]$/', $letter)) {
            $wardOfficers = array_values(array_filter($wardOfficers, fn($o) => strtoupper($o['name'][0] ?? '') === $letter));
        }
        if ($wardFilter > 0) {
            $wardOfficers = array_values(array_filter($wardOfficers, function ($o) use ($wardFilter) {
                $ids = array_map('intval', explode(',', $o['assigned_ward_ids'] ?? ''));
                return in_array($wardFilter, $ids, true);
            }));
        }

        $editOfficer = null;
        if (!empty($_GET['edit']) && !empty($districtIds)) {
            $editId = (int)$_GET['edit'];
            $pl = implode(',', array_map('intval', $districtIds));
            $es = $db->prepare("
                SELECT u.*,
                       GROUP_CONCAT(DISTINCT w.name ORDER BY w.name SEPARATOR ', ') AS assigned_wards,
                       GROUP_CONCAT(DISTINCT w.id ORDER BY w.name SEPARATOR ',') AS assigned_ward_ids
                FROM users u
                LEFT JOIN officer_wards ow ON ow.officer_id = u.id
                LEFT JOIN wards w ON w.id = ow.ward_id AND w.district_id IN ({$pl})
                WHERE u.id = ? AND u.role = 'ward_officer'
                GROUP BY u.id
            ");
            $es->execute([$editId]);
            $editOfficer = $es->fetch() ?: null;
        }

        $this->view('officer/manage_ward_officers', compact(
            'officer', 'myDistricts', 'wardOfficers', 'wards', 'search', 'letter', 'wardFilter', 'editOfficer'
        ));
    }

    public function viewOfficer()
    {
        $dao = $this->requireDao();
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            header('Location: /officer/officers');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $districtIds = $this->getDaoDistrictIds((int)$dao['id']);
        if (empty($districtIds)) {
            header('Location: /officer/officers?error=access');
            exit;
        }

        $pl = implode(',', array_map('intval', $districtIds));
        $stmt = $db->prepare("
            SELECT u.*,
                   GROUP_CONCAT(DISTINCT w.name ORDER BY w.name SEPARATOR ', ') AS assigned_wards,
                   GROUP_CONCAT(DISTINCT w.id ORDER BY w.name SEPARATOR ',') AS assigned_ward_ids
            FROM users u
            LEFT JOIN officer_wards ow ON ow.officer_id = u.id
            LEFT JOIN wards w ON w.id = ow.ward_id AND w.district_id IN ({$pl})
            WHERE u.id = ? AND u.role = 'ward_officer'
            GROUP BY u.id
        ");
        $stmt->execute([$id]);
        $target = $stmt->fetch();
        if (!$target) {
            http_response_code(404);
            echo 'Officer not found';
            return;
        }

        $wardIds = array_filter(array_map('intval', explode(',', $target['assigned_ward_ids'] ?? '')));
        $villages = [];
        $farmers = [];
        $farmerCount = 0;
        $visits = [];

        if (!empty($wardIds)) {
            $wpl = implode(',', $wardIds);
            $villages = $db->query("
                SELECT v.id, v.name, w.name AS ward_name
                FROM villages v JOIN wards w ON w.id = v.ward_id
                WHERE v.ward_id IN ({$wpl}) ORDER BY w.name, v.name
            ")->fetchAll();

            $farmerCount = (int)$db->query("SELECT COUNT(*) FROM farmers WHERE ward_id IN ({$wpl})")->fetchColumn();
            $farmers = $db->query("
                SELECT f.id, CONCAT(f.first_name, ' ', f.last_name) AS name, f.phone, v.name AS village_name
                FROM farmers f LEFT JOIN villages v ON v.id = f.village_id
                WHERE f.ward_id IN ({$wpl}) ORDER BY f.registered_at DESC LIMIT 12
            ")->fetchAll();

            $visits = $db->query("
                SELECT v.*, CONCAT(f.first_name, ' ', f.last_name) AS farmer_name
                FROM visits v
                JOIN farmers f ON f.id = v.farmer_id
                WHERE v.officer_id = {$id} OR f.ward_id IN ({$wpl})
                ORDER BY v.scheduled_at DESC LIMIT 20
            ")->fetchAll();
        }

        $visitStats = $db->prepare("
            SELECT
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN status IN ('scheduled','pending') THEN 1 ELSE 0 END) AS upcoming,
                COUNT(*) AS total
            FROM visits WHERE officer_id = ?
        ");
        $visitStats->execute([$id]);
        $visitStats = $visitStats->fetch() ?: ['completed' => 0, 'upcoming' => 0, 'total' => 0];

        $this->view('officer/officer_detail', compact(
            'dao', 'target', 'villages', 'farmers', 'farmerCount', 'visits', 'visitStats', 'wardIds'
        ));
    }

    public function createWardOfficer()
    {
        $officer = $this->requireDao();

        $db = Database::getInstance()->getConnection();
        $userModel = new User();

        if ($userModel->findByEmail($_POST['email'])) {
            header('Location: /officer/officers?error=Email+already+exists');
            exit;
        }

        $wardIds = array_map('intval', $_POST['ward_ids'] ?? []);
        $err = \App\Helpers\OfficerWard::validate($db, $wardIds);
        if ($err) {
            header('Location: /officer/officers?error=' . urlencode($err));
            exit;
        }

        $userId = $userModel->insert([
            'name'           => htmlspecialchars(trim($_POST['name'])),
            'email'          => trim($_POST['email']),
            'phone'          => trim($_POST['phone'] ?? null),
            'password_hash'  => password_hash($_POST['password'], PASSWORD_BCRYPT),
            'working_office' => trim($_POST['working_office'] ?? null),
            'role'           => 'ward_officer',
            'created_by'     => $officer['id'],
            'is_active'      => 1,
        ]);

        if (!empty($_POST['ward_ids'])) {
            \App\Helpers\OfficerWard::sync($db, $userId, $_POST['ward_ids']);
        }

        header('Location: /officer/officers?success=1');
        exit;
    }

    public function updateWardOfficer()
    {
        $officer = $this->requireDao();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            header('Location: /officer/officers');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $working_office = trim($_POST['working_office'] ?? '');

        $sql = "UPDATE users SET name = :n, email = :e, phone = :p, working_office = :w WHERE id = :id";
        $params = [':n' => $name, ':e' => $email, ':p' => $phone, ':w' => $working_office, ':id' => $id];

        if (!empty($_POST['password'])) {
            $sql = "UPDATE users SET name = :n, email = :e, phone = :p, working_office = :w, password_hash = :pw WHERE id = :id";
            $params[':pw'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
        }

        $db->prepare($sql)->execute($params);

        if (isset($_POST['ward_ids']) && is_array($_POST['ward_ids'])) {
            $err = \App\Helpers\OfficerWard::validate($db, $_POST['ward_ids'], $id);
            if ($err) {
                header('Location: /officer/officers?error=' . urlencode($err));
                exit;
            }
            \App\Helpers\OfficerWard::sync($db, $id, $_POST['ward_ids']);
        }

        header('Location: /officer/officers?success=1');
        exit;
    }

    public function toggleWardOfficer()
    {
        $officer = $this->requireDao();
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db = Database::getInstance()->getConnection();
            $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
        }
        header('Location: /officer/officers');
        exit;
    }

    // ─────────────────────────────────────────────
    // WAO: RELOCATION REQUESTS
    // ─────────────────────────────────────────────
    public function relocationRequests()
    {
        $officer = $this->requireAuth();
        $this->view('officer/relocation_requests', compact('officer'));
    }

    public function createRelocationRequest()
    {
        $officer = $this->requireAuth();
        $farmerId = (int)$_POST['farmer_id'];
        $toWardId = (int)$_POST['to_ward_id'];
        $notes    = trim($_POST['notes'] ?? '');

        if (!$farmerId || !$toWardId) {
            header('Location: /officer/relocation?error=missing');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $farmer = $db->prepare("SELECT ward_id FROM farmers WHERE id = ?");
        $farmer->execute([$farmerId]);
        $fromWardId = (int)$farmer->fetchColumn();

        // Check if both wards are managed by this same officer
        $stmt = $db->prepare("SELECT COUNT(*) FROM officer_wards WHERE officer_id = ? AND ward_id = ?");
        $stmt->execute([$officer['id'], $toWardId]);
        $isSameOfficer = (bool)$stmt->fetchColumn();

        $status = $isSameOfficer ? 'approved' : 'pending';

        $db->prepare("
            INSERT INTO farmer_relocation_requests (farmer_id, from_ward_id, to_ward_id, requested_by, status, notes)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([$farmerId, $fromWardId, $toWardId, $officer['id'], $status, $notes]);

        if ($isSameOfficer) {
            $db->prepare("UPDATE farmers SET ward_id = ?, village_id = NULL WHERE id = ?")->execute([$toWardId, $farmerId]);
            $this->logAudit($officer['id'], 'auto_approve_relocation', 'farmer', $farmerId, ['to_ward' => $toWardId]);
        } else {
            $this->logAudit($officer['id'], 'request_relocation', 'farmer', $farmerId, ['to_ward' => $toWardId]);
        }

        header('Location: /officer/relocation?success=1');
        exit;
    }

    public function approveRelocation()
    {
        $officer = $this->requireAuth();
        $id = (int)$_POST['id'];

        $db = Database::getInstance()->getConnection();
        $req = $db->prepare("SELECT * FROM farmer_relocation_requests WHERE id = ?");
        $req->execute([$id]);
        $request = $req->fetch();

        if ($request && $request['status'] === 'pending') {
            $db->beginTransaction();
            try {
                $db->prepare("UPDATE farmer_relocation_requests SET status = 'approved', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?")
                    ->execute([$officer['id'], $id]);

                $db->prepare("UPDATE farmers SET ward_id = ?, village_id = NULL WHERE id = ?")
                    ->execute([$request['to_ward_id'], $request['farmer_id']]);

                $this->logAudit($officer['id'], 'approve_relocation', 'farmer', $request['farmer_id'], ['req_id' => $id]);
                $db->commit();
            } catch (\Exception $e) {
                $db->rollBack();
            }
        }
        header('Location: /officer/relocation');
        exit;
    }

    public function rejectRelocation()
    {
        $officer = $this->requireAuth();
        $id = (int)$_POST['id'];
        $db = Database::getInstance()->getConnection();
        $db->prepare("UPDATE farmer_relocation_requests SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?")
            ->execute([$officer['id'], $id]);
        header('Location: /officer/relocation');
        exit;
    }

    // ─────────────────────────────────────────────
    // AI MENTORSHIP / KNOWLEDGE BASE
    // ─────────────────────────────────────────────
    public function aiMentorship()
    {
        $officer = $this->requireAuth();
        $kbModel = new KnowledgeBase();
        $crops   = (new Crop())->getActive();
        $search  = trim($_GET['q'] ?? '');
        $status  = $_GET['status'] ?? '';
        $cropId  = Sanitizer::int($_GET['crop_id'] ?? 0) ?: null;
        $stageId = Sanitizer::int($_GET['stage_id'] ?? 0) ?: null;
        $entries = $kbModel->getAllWithDetails($search, $status, $cropId, $stageId);

        $this->view('officer/ai_mentorship', compact('entries', 'crops', 'officer', 'search', 'status', 'cropId', 'stageId'));
    }

    public function searchKnowledge()
    {
        $this->requireAuth();
        header('Content-Type: application/json');

        $kbModel = new KnowledgeBase();
        $search  = trim($_GET['q'] ?? '');
        $status  = $_GET['status'] ?? '';
        $cropId  = Sanitizer::int($_GET['crop_id'] ?? 0) ?: null;
        $stageId = Sanitizer::int($_GET['stage_id'] ?? 0) ?: null;
        $entries = $kbModel->getAllWithDetails($search, $status, $cropId, $stageId);

        echo json_encode(['entries' => $entries, 'count' => count($entries)]);
    }

    public function storeKnowledge()
    {
        $officer = $this->requireAuth();

        $validator = Validator::make($_POST);
        if (!$validator->validate([
            'crop_id' => 'required|int',
            'stage_id' => 'required|int',
            'topic_id' => 'required|int',
            'title' => 'required|max:255',
            'situation' => 'required|max:500',
            'solution' => 'required|max:2000',
            'language' => 'in:sw,en',
        ])) {
            header('Location: /officer/ai-mentorship?error=' . urlencode($validator->firstError() ?? 'validation'));
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $db->prepare("
            INSERT INTO knowledge_base (crop_id, stage_id, topic_id, title, situation, solution, language, source, created_by, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'published')
        ")->execute([
            Sanitizer::int($_POST['crop_id']),
            Sanitizer::int($_POST['stage_id']),
            Sanitizer::int($_POST['topic_id']),
            Sanitizer::string($_POST['title'], 255),
            Sanitizer::textarea($_POST['situation'] ?? '', 500),
            Sanitizer::textarea($_POST['solution'] ?? '', 2000),
            in_array($_POST['language'] ?? 'sw', ['sw', 'en']) ? $_POST['language'] : 'sw',
            $officer['role'],
            $officer['id'],
        ]);

        Logger::info('KB entry created', ['officer_id' => $officer['id'], 'title' => $_POST['title']]);
        header('Location: /officer/ai-mentorship?success=1');
        exit;
    }

    public function updateKnowledge()
    {
        $officer = $this->requireAuth();
        $id = Sanitizer::int($_POST['id'] ?? $_POST['entry_id'] ?? 0);
        if (!$id) {
            header('Location: /officer/ai-mentorship');
            exit;
        }

        $validator = Validator::make($_POST);
        if (!$validator->validate([
            'title' => 'required|max:255',
            'situation' => 'required|max:500',
            'solution' => 'required|max:2000',
        ])) {
            header('Location: /officer/ai-mentorship?error=' . urlencode($validator->firstError() ?? 'validation'));
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $db->prepare("
            UPDATE knowledge_base SET title=?, situation=?, solution=?, updated_by=?, updated_at=NOW() WHERE id=? AND deleted_at IS NULL
        ")->execute([
            Sanitizer::string($_POST['title'], 255),
            Sanitizer::textarea($_POST['situation'] ?? '', 500),
            Sanitizer::textarea($_POST['solution'] ?? '', 2000),
            $officer['id'],
            $id,
        ]);

        Logger::info('KB entry updated', ['officer_id' => $officer['id'], 'entry_id' => $id]);
        header('Location: /officer/ai-mentorship?updated=1');
        exit;
    }

    public function deleteKnowledge()
    {
        $officer = $this->requireAuth();
        $id = Sanitizer::int($_POST['id'] ?? $_POST['entry_id'] ?? 0);
        if ($id) {
            $db = Database::getInstance()->getConnection();
            $db->prepare("UPDATE knowledge_base SET deleted_at = NOW(), updated_by = ? WHERE id = ?")->execute([$officer['id'], $id]);
            Logger::info('KB entry soft-deleted', ['officer_id' => $officer['id'], 'entry_id' => $id]);
        }
        header('Location: /officer/ai-mentorship?deleted=1');
        exit;
    }

    // ─────────────────────────────────────────────
    // ESCALATIONS
    // ─────────────────────────────────────────────
    public function escalations()
    {
        $officer = $this->requireAuth();
        $db = Database::getInstance()->getConnection();
        $wardIds = $officer['ward_ids'];

        $query = "
            SELECT e.*, m.content, m.sent_at as msg_date, CONCAT(f.first_name, ' ', f.last_name) as farmer_name, f.phone, f.id as farmer_id
            FROM escalations e
            JOIN ai_messages m ON m.id = e.ai_message_id
            JOIN farmers f ON f.id = m.farmer_id
            WHERE e.status = 'pending'
        ";
        if (!empty($wardIds)) {
            $in = implode(',', array_map('intval', $wardIds));
            $query .= " AND f.ward_id IN ({$in})";
        }
        $query .= " ORDER BY e.escalated_at ASC";

        $escalations = $db->query($query)->fetchAll();

        $this->view('officer/escalations', compact('escalations', 'officer'));
    }

    public function replyEscalation()
    {
        $officer = $this->requireAuth();
        $escalationId = (int)($_POST['escalation_id'] ?? 0);
        $farmerId     = (int)($_POST['farmer_id'] ?? 0);
        $phone        = trim($_POST['farmer_phone'] ?? '');
        $reply        = trim($_POST['reply'] ?? '');
        $question     = trim($_POST['question'] ?? '');

        if (!$escalationId || !$farmerId || !$phone || !$reply) {
            header('Location: /officer/escalations?error=1');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $atService = new AfricasTalkingService();
        $result = $atService->sendTwoWayReply($phone, $reply);
        
        $providerId = $result['SMSMessageData']['Recipients'][0]['messageId'] ?? null;
        
        $db->prepare(
            "INSERT INTO officer_messages (farmer_id, direction, channel, content, officer_id, provider_message_id, sent_at)
             VALUES (?, 'out', 'sms', ?, ?, ?, NOW())"
        )->execute([$farmerId, $reply, $officer['id'], $providerId]);

        // Close escalation
        $db->prepare("UPDATE escalations SET status = 'responded', responded_at = NOW(), officer_reply = ?, assigned_officer_id = ? WHERE id = ?")
            ->execute([$reply, $officer['id'], $escalationId]);

        // Feedback loop: Learn from this escalation into the KB
        $rag = new RagService();
        $rag->learnFromEscalation($question, $reply, $officer['id']);

        header('Location: /officer/escalations?success=1');
        exit;
    }

    // ─────────────────────────────────────────────
    // VISITS
    // ─────────────────────────────────────────────
    public function visits()
    {
        $officer = $this->requireAuth();
        $visitModel = new Visit();
        $db = Database::getInstance()->getConnection();
        $statusFilter = $_GET['status'] ?? 'all';
        $isDao = in_array($officer['role'], ['dao', 'super_admin'], true);

        if ($isDao) {
            $visits = $visitModel->getScoped($officer, $statusFilter === 'all' ? null : $statusFilter);
        } else {
            $visits = $visitModel->getScoped($officer, $statusFilter === 'all' ? null : $statusFilter);
        }

        $visitRequestModel = new VisitRequest();
        $pendingRequests = $visitRequestModel->getScoped($officer, 'pending');
        $tab = $_GET['tab'] ?? 'visits';
        $requestStatusFilter = $_GET['rstatus'] ?? 'all';
        $requests = $visitRequestModel->getScoped($officer, $requestStatusFilter === 'all' ? null : $requestStatusFilter);
        $canHandle = in_array($officer['role'], ['ward_officer', 'super_admin'], true);

        $farmers = [];
        $wardIds = $officer['ward_ids'];
        if (!empty($wardIds)) {
            $in = implode(',', array_map('intval', $wardIds));
            $farmers = $db->query("
                SELECT f.*, CONCAT(f.first_name, ' ', f.last_name) AS name,
                       v.name AS village_name, w.name AS ward_name,
                       c.name_en AS crop_name, f.farm_size_acres
                FROM farmers f
                LEFT JOIN villages v ON v.id = f.village_id
                LEFT JOIN wards w ON w.id = f.ward_id
                LEFT JOIN farmer_crops fc ON fc.farmer_id = f.id AND fc.type = 'primary'
                LEFT JOIN crops c ON c.id = fc.crop_id
                WHERE f.ward_id IN ({$in})
                ORDER BY f.first_name
            ")->fetchAll();
        } elseif ($isDao) {
            $districtId = $this->getDaoDistrictId($officer['id']) ?? 1;
            $farmers = $db->prepare("
                SELECT f.*, CONCAT(f.first_name, ' ', f.last_name) AS name,
                       v.name AS village_name, w.name AS ward_name,
                       c.name_en AS crop_name, f.farm_size_acres
                FROM farmers f
                LEFT JOIN villages v ON v.id = f.village_id
                LEFT JOIN wards w ON w.id = f.ward_id
                LEFT JOIN farmer_crops fc ON fc.farmer_id = f.id AND fc.type = 'primary'
                LEFT JOIN crops c ON c.id = fc.crop_id
                WHERE w.district_id = ?
                ORDER BY f.first_name
                LIMIT 500
            ");
            $farmers->execute([$districtId]);
            $farmers = $farmers->fetchAll();
        }

        $crops = (new Crop())->getActive();

        $wards = [];
        $villages = [];
        if (!empty($wardIds)) {
            $in = implode(',', array_map('intval', $wardIds));
            $wards = $db->query("SELECT id, name FROM wards WHERE id IN ({$in}) ORDER BY name")->fetchAll();
            $villages = $db->query("SELECT id, name, ward_id FROM villages WHERE ward_id IN ({$in}) ORDER BY name")->fetchAll();
        } elseif ($isDao) {
            $districtId = $this->getDaoDistrictId($officer['id']) ?? 1;
            $wStmt = $db->prepare("SELECT w.id, w.name FROM wards w WHERE w.district_id = ? ORDER BY w.name");
            $wStmt->execute([$districtId]);
            $wards = $wStmt->fetchAll();
            $wardIdList = array_column($wards, 'id');
            if ($wardIdList) {
                $in = implode(',', array_map('intval', $wardIdList));
                $villages = $db->query("SELECT id, name, ward_id FROM villages WHERE ward_id IN ({$in}) ORDER BY name")->fetchAll();
            }
        }

        $batchCounts = [];
        foreach ($visits as $v) {
            if (!empty($v['visit_batch_id'])) {
                $bid = $v['visit_batch_id'];
                $batchCounts[$bid] = ($batchCounts[$bid] ?? 0) + 1;
            }
        }

        $this->view('officer/visits', compact(
            'visits', 'farmers', 'officer', 'statusFilter', 'isDao', 'pendingRequests',
            'crops', 'tab', 'requests', 'requestStatusFilter', 'canHandle',
            'wards', 'villages', 'batchCounts'
        ));
    }

    public function scheduleVisit()
    {
        $officer = $this->requireAuth();
        if ($officer['role'] === 'dao') {
            header('Location: /officer/visits?error=readonly');
            exit;
        }

        try {
            if (empty($_POST['scheduled_at']) || empty($_POST['reason'])) {
                header('Location: /officer/visits?error=missing');
                exit;
            }

            $scopeType = $_POST['scope_type'] ?? 'individual';
            $visitModel = new Visit();
            $farmerIds = [];

            switch ($scopeType) {
                case 'individual':
                    if (empty($_POST['farmer_id'])) {
                        header('Location: /officer/visits?error=missing');
                        exit;
                    }
                    $farmerIds = [(int)$_POST['farmer_id']];
                    break;
                case 'ward':
                    if (empty($_POST['target_ward_id'])) {
                        header('Location: /officer/visits?error=missing');
                        exit;
                    }
                    $farmerIds = $visitModel->resolveFarmerIds($officer, ['ward_id' => (int)$_POST['target_ward_id']]);
                    break;
                case 'village':
                    if (empty($_POST['target_village_id'])) {
                        header('Location: /officer/visits?error=missing');
                        exit;
                    }
                    $farmerIds = $visitModel->resolveFarmerIds($officer, ['village_id' => (int)$_POST['target_village_id']]);
                    break;
                case 'crop':
                    if (empty($_POST['target_crop_id'])) {
                        header('Location: /officer/visits?error=missing');
                        exit;
                    }
                    $farmerIds = $visitModel->resolveFarmerIds($officer, ['crop_id' => (int)$_POST['target_crop_id']]);
                    break;
                case 'village_crop':
                    if (empty($_POST['target_village_id']) || empty($_POST['target_crop_id'])) {
                        header('Location: /officer/visits?error=missing');
                        exit;
                    }
                    $farmerIds = $visitModel->resolveFarmerIds($officer, [
                        'village_id' => (int)$_POST['target_village_id'],
                        'crop_id'    => (int)$_POST['target_crop_id'],
                    ]);
                    break;
                default:
                    header('Location: /officer/visits?error=missing');
                    exit;
            }

            if (empty($farmerIds)) {
                header('Location: /officer/visits?error=nofarmers');
                exit;
            }

            $batchId = count($farmerIds) > 1 ? bin2hex(random_bytes(16)) : null;
            $scheduledAt = $_POST['scheduled_at'];
            $reason = Sanitizer::string($_POST['reason'], 500);
            $visitType = $_POST['visit_type'] ?? 'officer_planned';
            $targetCropId = Sanitizer::int($_POST['target_crop_id'] ?? 0) ?: null;

            foreach ($farmerIds as $farmerId) {
                $snap = $visitModel->farmerSnapshot($farmerId);
                $cropId = Sanitizer::int($_POST['crop_id'] ?? 0) ?: $snap['crop_id'];
                if ($scopeType === 'crop' || $scopeType === 'village_crop') {
                    $cropId = $targetCropId ?: $cropId;
                }

                $row = [
                    'farmer_id'    => $farmerId,
                    'officer_id'   => $officer['id'],
                    'scheduled_at' => $scheduledAt,
                    'reason'       => $reason,
                    'status'       => 'scheduled',
                ];

                $optional = [
                    'visit_type'       => $visitType,
                    'crop_id'          => $cropId,
                    'farm_size_acres'  => Sanitizer::float($_POST['farm_size_acres'] ?? 0) ?: $snap['farm_size_acres'],
                    'visit_request_id' => Sanitizer::int($_POST['visit_request_id'] ?? 0) ?: null,
                    'visit_batch_id'   => $batchId,
                ];
                foreach ($optional as $k => $v) {
                    if ($v !== null && $v !== '') {
                        $row[$k] = $v;
                    }
                }

                $visitModel->create($row);
            }

            $count = count($farmerIds);
            header('Location: /officer/visits?success=' . ($count > 1 ? 'batch' : '1'));
            exit;
        } catch (\Throwable $e) {
            Logger::error('scheduleVisit failed', ['error' => $e->getMessage(), 'officer_id' => $officer['id'] ?? null]);
            header('Location: /officer/visits?error=server');
            exit;
        }
    }

    public function updateVisit()
    {
        $officer = $this->requireAuth();
        if ($officer['role'] === 'dao') {
            header('Location: /officer/visits?error=readonly');
            exit;
        }

        $id     = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $outcome = trim($_POST['outcome'] ?? '');
        $notDone = trim($_POST['not_done_reason'] ?? '');

        if (!$id || !$status) {
            header('Location: /officer/visits?error=1');
            exit;
        }

        $visitModel = new Visit();
        $fields = [
            'status' => $status,
            'followup_at' => date('Y-m-d H:i:s'),
        ];
        if ($status === 'completed') {
            $fields['outcome'] = Sanitizer::textarea($outcome, 2000);
            $fields['followup'] = $fields['outcome'];
        } elseif ($status === 'cancelled') {
            $fields['not_done_reason'] = Sanitizer::textarea($notDone, 1000);
            $fields['followup'] = $fields['not_done_reason'];
        } elseif ($status === 'postponed') {
            $newDate = $_POST['scheduled_at'] ?? '';
            if ($newDate) {
                $fields['scheduled_at'] = $newDate;
                $fields['rescheduled_at'] = date('Y-m-d H:i:s');
                $fields['followup'] = Sanitizer::textarea(trim($_POST['followup'] ?? 'Visit postponed'), 1000);
            }
        }

        $visitModel->updateFull($id, $fields);
        header('Location: /officer/visits?success=1');
        exit;
    }

    public function visitFollowup()
    {
        $officer = $this->requireAuth();
        if ($officer['role'] === 'dao') {
            header('Location: /officer/visits?error=readonly');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $followup = trim($_POST['followup'] ?? '');
        if (!$id || $followup === '') {
            header('Location: /officer/visits?error=1');
            exit;
        }

        (new Visit())->updateFull($id, [
            'followup'    => Sanitizer::textarea($followup, 2000),
            'followup_at' => date('Y-m-d H:i:s'),
        ]);

        header('Location: /officer/visits?success=followup');
        exit;
    }

    public function visitRequests()
    {
        $q = $_SERVER['QUERY_STRING'] ?? '';
        header('Location: /officer/visits?tab=requests' . ($q !== '' ? '&' . $q : ''));
        exit;
    }

    public function handleVisitRequest()
    {
        $officer = $this->requireAuth();
        if ($officer['role'] === 'dao') {
            header('Location: /officer/visits?tab=requests&error=readonly');
            exit;
        }

        $requestId  = (int)($_POST['request_id'] ?? 0);
        $status     = $_POST['status'] ?? '';
        $notes      = trim($_POST['notes'] ?? '');
        $scheduleAt = trim($_POST['scheduled_at'] ?? '');

        if (!$requestId || !$status) {
            header('Location: /officer/visits?tab=requests&error=1');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM visit_requests WHERE id = ?');
        $stmt->execute([$requestId]);
        $request = $stmt->fetch();

        if (!$request) {
            header('Location: /officer/visits?tab=requests&error=1');
            exit;
        }

        $visitModel = new Visit();
        $snap = $visitModel->farmerSnapshot((int)$request['farmer_id']);
        $visitId = null;

        if (in_array($status, ['scheduled', 'postponed'], true)) {
            if ($scheduleAt === '') {
                header('Location: /officer/visits?tab=requests&error=date');
                exit;
            }
            $visitStatus = $status === 'postponed' ? 'postponed' : 'scheduled';
            $visitId = $visitModel->create([
                'farmer_id'        => (int)$request['farmer_id'],
                'officer_id'       => $officer['id'],
                'scheduled_at'     => $scheduleAt,
                'reason'           => $request['request_reason'],
                'status'           => $visitStatus,
                'visit_type'       => 'farmer_requested',
                'crop_id'          => $snap['crop_id'],
                'farm_size_acres'  => $snap['farm_size_acres'],
                'visit_request_id' => $requestId,
                'rescheduled_at'   => $status === 'postponed' ? date('Y-m-d H:i:s') : null,
            ]);
        }

        $db->prepare("
            UPDATE visit_requests SET
                status = ?, handled_by = ?, handled_at = NOW(), notes = ?,
                scheduled_at = ?, visit_id = ?, rescheduled_at = IF(? = 'postponed', NOW(), rescheduled_at),
                crop_id = COALESCE(crop_id, ?), farm_size_acres = COALESCE(farm_size_acres, ?)
            WHERE id = ?
        ")->execute([
            $status, $officer['id'], $notes,
            $scheduleAt ?: null, $visitId,
            $status,
            $snap['crop_id'], $snap['farm_size_acres'],
            $requestId,
        ]);

        header('Location: /officer/visits?tab=requests&success=1');
        exit;
    }

    // ─────────────────────────────────────────────
    // AUTOMATED ALERTS (DAO)
    // ─────────────────────────────────────────────
    public function automatedAlerts()
    {
        $officer = $this->requireDao();
        $districtId = $this->getDaoDistrictId($officer['id']) ?? 1;
        $db = Database::getInstance()->getConnection();

        $alerts = (new AutomatedAlert())->getByDistrict($districtId);
        $wards  = $db->prepare('SELECT id, name FROM wards WHERE district_id = ? ORDER BY name');
        $wards->execute([$districtId]);
        $wards = $wards->fetchAll();

        $this->view('officer/automated_alerts', compact('officer', 'alerts', 'wards', 'districtId'));
    }

    public function storeAutomatedAlert()
    {
        $officer = $this->requireDao();
        $districtId = $this->getDaoDistrictId($officer['id']) ?? 1;

        $validator = Validator::make($_POST);
        if (!$validator->validate([
            'title' => 'required|max:255',
            'message_template' => 'required|max:2000',
            'alert_type' => 'in:weather,welcome,visit,crop_advisory,custom',
            'trigger_event' => 'in:on_register,on_visit_scheduled,on_visit_reminder,weather_daily,manual',
        ])) {
            header('Location: /officer/automated-alerts?error=validation');
            exit;
        }

        (new AutomatedAlert())->createAlert([
            'district_id'          => $districtId,
            'ward_id'              => Sanitizer::int($_POST['ward_id'] ?? 0) ?: null,
            'alert_type'           => $_POST['alert_type'],
            'title'                => Sanitizer::string($_POST['title'], 255),
            'message_template'     => Sanitizer::textarea($_POST['message_template'], 2000),
            'channel'              => in_array($_POST['channel'] ?? 'sms', ['sms', 'both']) ? $_POST['channel'] : 'sms',
            'trigger_event'        => $_POST['trigger_event'],
            'trigger_offset_hours' => Sanitizer::int($_POST['trigger_offset_hours'] ?? 24),
            'is_active'            => 1,
            'created_by'           => $officer['id'],
        ]);

        header('Location: /officer/automated-alerts?success=1');
        exit;
    }

    public function toggleAutomatedAlert()
    {
        $officer = $this->requireDao();
        $districtId = $this->getDaoDistrictId($officer['id']) ?? 1;
        $id = Sanitizer::int($_POST['id'] ?? 0);
        if ($id) {
            (new AutomatedAlert())->toggleActive($id, $districtId);
        }
        header('Location: /officer/automated-alerts');
        exit;
    }

    public function deleteAutomatedAlert()
    {
        $officer = $this->requireDao();
        $districtId = $this->getDaoDistrictId($officer['id']) ?? 1;
        $id = Sanitizer::int($_POST['id'] ?? 0);
        if ($id) {
            $db = Database::getInstance()->getConnection();
            $db->prepare('DELETE FROM automated_alerts WHERE id = ? AND district_id = ?')->execute([$id, $districtId]);
        }
        header('Location: /officer/automated-alerts?deleted=1');
        exit;
    }

    public function updateAutomatedAlert()
    {
        $officer = $this->requireDao();
        $districtId = $this->getDaoDistrictId($officer['id']) ?? 1;
        $id = Sanitizer::int($_POST['id'] ?? 0);
        if (!$id) {
            header('Location: /officer/automated-alerts');
            exit;
        }

        $validator = Validator::make($_POST);
        if (!$validator->validate([
            'title' => 'required|max:255',
            'message_template' => 'required|max:2000',
            'alert_type' => 'in:weather,welcome,visit,crop_advisory,custom',
            'trigger_event' => 'in:on_register,on_visit_scheduled,on_visit_reminder,weather_daily,manual',
        ])) {
            header('Location: /officer/automated-alerts?error=validation');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $db->prepare("
            UPDATE automated_alerts SET
                ward_id = ?, alert_type = ?, title = ?, message_template = ?,
                channel = ?, trigger_event = ?, trigger_offset_hours = ?
            WHERE id = ? AND district_id = ?
        ")->execute([
            Sanitizer::int($_POST['ward_id'] ?? 0) ?: null,
            $_POST['alert_type'],
            Sanitizer::string($_POST['title'], 255),
            Sanitizer::textarea($_POST['message_template'], 2000),
            in_array($_POST['channel'] ?? 'sms', ['sms', 'both']) ? $_POST['channel'] : 'sms',
            $_POST['trigger_event'],
            Sanitizer::int($_POST['trigger_offset_hours'] ?? 24),
            $id,
            $districtId,
        ]);

        header('Location: /officer/automated-alerts?success=1');
        exit;
    }

    private function getDaoDistrictId(int $officerId): ?int
    {
        $ids = $this->getDaoDistrictIds($officerId);
        return $ids[0] ?? null;
    }

    /** @return int[] */
    private function getDaoDistrictIds(int $officerId): array
    {
        $db = Database::getInstance()->getConnection();
        $d = $db->prepare('SELECT district_id FROM officer_districts WHERE officer_id = ?');
        $d->execute([$officerId]);
        return array_map('intval', array_column($d->fetchAll(), 'district_id'));
    }

    // ─────────────────────────────────────────────
    // AJAX
    // ─────────────────────────────────────────────
    public function getVillages()
    {
        header('Content-Type: application/json');
        $wardId = (int)($_GET['ward_id'] ?? 0);
        $villageModel = new Village();
        echo json_encode($villageModel->getByWard($wardId));
    }

    public function getWards()
    {
        header('Content-Type: application/json');
        $districtId = (int)($_GET['district_id'] ?? 0);
        if (!$districtId) {
            echo json_encode([]);
            return;
        }
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, name FROM wards WHERE district_id = ? AND (is_active = 1 OR is_active IS NULL) ORDER BY name");
        $stmt->execute([$districtId]);
        echo json_encode($stmt->fetchAll());
    }

    public function getStages()
    {
        $cropId = (int)($_GET['crop_id'] ?? 0);
        $stages = \App\Core\Database::getInstance()->getConnection()->query("SELECT * FROM growth_stages WHERE crop_id = $cropId ORDER BY sort_order ASC")->fetchAll();
        header('Content-Type: application/json');
        echo json_encode($stages);
    }

    // ─────────────────────────────────────────────
    // OTHER
    // ─────────────────────────────────────────────
    public function weather()
    {
        $officer = $this->requireAuth();
        $svc = new \App\Services\WeatherAlertService();

        $districtId = null;
        if ($officer['role'] === 'dao') {
            $db = Database::getInstance()->getConnection();
            $d = $db->prepare('SELECT district_id FROM officer_districts WHERE officer_id = ? LIMIT 1');
            $d->execute([$officer['id']]);
            $districtId = (int)($d->fetchColumn() ?: 1) ?: 1;
        }

        $pendingAlerts = $svc->getPendingAlerts($districtId);
        $db = Database::getInstance()->getConnection();
        $broadcastHistory = $db->query("
            SELECT wa.*, u.name AS creator_name, w.name AS ward_name, d.name AS district_name
            FROM weather_alerts wa
            LEFT JOIN users u ON u.id = wa.created_by
            LEFT JOIN wards w ON w.id = wa.ward_id
            LEFT JOIN districts d ON d.id = wa.district_id
            ORDER BY wa.created_at DESC LIMIT 15
        ")->fetchAll();

        $wards = [];
        if ($officer['role'] === 'ward_officer') {
            $w = $db->prepare('SELECT w.id, w.name FROM wards w JOIN officer_wards ow ON ow.ward_id = w.id WHERE ow.officer_id = ?');
            $w->execute([$officer['id']]);
            $wards = $w->fetchAll();
        } elseif ($officer['role'] === 'dao' || $officer['role'] === 'super_admin') {
            $wards = $db->query('SELECT id, name FROM wards ORDER BY name')->fetchAll();
        }

        $weatherSvc = new \App\Services\WeatherService();
        $forecast = $weatherSvc->buildOfficerWeekForecast();

        $this->view('officer/weather', compact('officer', 'pendingAlerts', 'broadcastHistory', 'wards', 'forecast'));
    }

    public function createWeatherAlert()
    {
        $officer = $this->requireAuth();
        $svc = new \App\Services\WeatherAlertService();

        $wardId = (int)($_POST['ward_id'] ?? 0) ?: null;
        if (!$wardId && $officer['role'] === 'ward_officer' && !empty($officer['ward_id'])) {
            $wardId = (int)$officer['ward_id'];
        }
        $districtId = 1;
        if ($wardId) {
            $db = Database::getInstance()->getConnection();
            $d = $db->prepare('SELECT district_id FROM wards WHERE id = ?');
            $d->execute([$wardId]);
            $districtId = (int)$d->fetchColumn() ?: 1;
        }

        $svc->createAlert([
            'district_id' => $districtId,
            'ward_id'     => $wardId,
            'title'       => trim($_POST['title'] ?? 'Weather Alert'),
            'message'     => trim($_POST['message'] ?? ''),
            'alert_type'  => trim($_POST['alert_type'] ?? 'general'),
            'severity'    => $_POST['severity'] ?? 'medium',
            'created_by'  => $officer['id'],
        ]);

        $this->logAudit($officer['id'], 'create_weather_alert', 'weather_alert', 0, ['title' => $_POST['title'] ?? '']);
        header('Location: /officer/weather?success=created');
        exit;
    }

    public function approveWeatherAlert()
    {
        $officer = $this->requireAuth();
        $id = (int)($_POST['alert_id'] ?? 0);
        $svc = new \App\Services\WeatherAlertService();
        $svc->recordApproval($id, $officer);
        header('Location: /officer/weather?success=approved');
        exit;
    }

    public function rejectWeatherAlert()
    {
        $officer = $this->requireAuth();
        $id = (int)($_POST['alert_id'] ?? 0);
        (new \App\Services\WeatherAlertService())->rejectAlert($id);
        header('Location: /officer/weather?success=rejected');
        exit;
    }

    public function analytics()
    {
        $officer = $this->requireAuth();

        $db = Database::getInstance()->getConnection();
        $channelStats = $db->query("
            SELECT channel, COUNT(*) as cnt FROM ai_messages GROUP BY channel
            UNION ALL
            SELECT channel, COUNT(*) as cnt FROM officer_messages GROUP BY channel
        ")->fetchAll();

        $this->view('officer/analytics', compact('officer', 'channelStats'));
    }

    public function exportAnalytics()
    {
        $this->requireAuth();
        $format = strtolower($_GET['format'] ?? 'pdf');
        $db = Database::getInstance()->getConnection();

        $totalFarmers = (int)$db->query('SELECT COUNT(*) FROM farmers')->fetchColumn();
        $totalSmsIn   = (int)$db->query("SELECT COUNT(*) FROM ai_messages WHERE direction = 'in'")->fetchColumn();
        $totalVisits  = (int)$db->query("SELECT COUNT(*) FROM visits WHERE status = 'completed'")->fetchColumn();
        $pendingEsc   = (int)$db->query("SELECT COUNT(*) FROM escalations WHERE status = 'pending'")->fetchColumn();

        $regData = $db->query("
            SELECT DATE_FORMAT(registered_at, '%Y-%m') AS month, COUNT(*) AS cnt
            FROM farmers WHERE registered_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY month ORDER BY month
        ")->fetchAll();

        if ($format === 'pdf') {
            $pdf = \App\Helpers\PdfReport::analytics('Agri-Advisory Analytics Report', [
                'Total Farmers'        => $totalFarmers,
                'SMS Inbound'          => $totalSmsIn,
                'Completed Visits'     => $totalVisits,
                'Pending Escalations'  => $pendingEsc,
            ], $regData);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="agri-advisory-report.pdf"');
            header('Content-Length: ' . strlen($pdf));
            echo $pdf;
            exit;
        }

        // Legacy CSV (hidden from UI)
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="agri-advisory-report.csv"');
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Summary']);
        fputcsv($output, ['Metric', 'Value']);
        fputcsv($output, ['Total Farmers', $totalFarmers]);
        fputcsv($output, ['SMS Inbound', $totalSmsIn]);
        fputcsv($output, ['Completed Visits', $totalVisits]);
        fputcsv($output, ['Pending Escalations', $pendingEsc]);
        fputcsv($output, []);
        fputcsv($output, ['Registrations by Month']);
        fputcsv($output, ['Month', 'Count']);
        foreach ($regData as $r) {
            fputcsv($output, [$r['month'], $r['cnt']]);
        }
        fclose($output);
        exit;
    }

    public function profile()
    {
        $officer = $this->requireAuth();
        $user = Database::getInstance()->getConnection()->prepare("SELECT * FROM users WHERE id = ?");
        $user->execute([$officer['id']]);
        $officerDetails = $user->fetch();
        $this->view('officer/profile', ['officer' => $officerDetails]);
    }

    private function logAudit(int $actorId, string $action, string $entityType, int $entityId, array $meta = [])
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO audit_log (actor_id, actor_role, action, entity_type, entity_id, meta) VALUES (?, 'officer', ?, ?, ?, ?)");
        $stmt->execute([$actorId, $action, $entityType, $entityId, json_encode($meta)]);
    }
}
