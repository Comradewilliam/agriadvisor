<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Farmer;
use App\Models\Visit;
use App\Models\Crop;
use App\Models\VisitRequest;
use App\Services\WeatherService;
use App\Services\FarmerChatService;
use App\Helpers\Validator;
use App\Helpers\Sanitizer;
use App\Helpers\Logger;
use App\Helpers\Cms;
use App\Helpers\ChatThreads;
use App\Models\ChatThread;

class FarmerController extends Controller
{

    private function requireAuth(): void
    {
        if (!isset($_SESSION['farmer_id'])) {
            header('Location: /farmer/login');
            exit;
        }
    }

    public function dashboard()
    {
        $this->requireAuth();
        $farmerId = (int)$_SESSION['farmer_id'];

        $farmerModel  = new Farmer();
        $farmer       = $farmerModel->findWithDetails($farmerId);

        // Recent conversation (last 10 messages)
        $db = Database::getInstance()->getConnection();
        // Fetch ai_messages
        $stmtAi = $db->prepare("SELECT id, direction, channel, content, sent_at, 'ai' AS source FROM ai_messages WHERE farmer_id = ? ORDER BY sent_at DESC LIMIT 10");
        $stmtAi->execute([$farmerId]);

        // Fetch officer_messages (broadcasts)
        $stmtOfficer = $db->prepare("SELECT id, direction, channel, content, sent_at, 'officer' AS source FROM officer_messages WHERE farmer_id = ? ORDER BY sent_at DESC LIMIT 10");
        $stmtOfficer->execute([$farmerId]);

        // Fetch escalation replies
        $stmtEsc = $db->prepare("SELECT e.id, 'out' AS direction, 'sms' AS channel, e.officer_reply AS content, e.responded_at AS sent_at, 'officer_reply' AS source 
                                 FROM escalations e 
                                 JOIN ai_messages m ON m.id = e.ai_message_id 
                                 WHERE m.farmer_id = ? AND e.status = 'responded' ORDER BY e.responded_at DESC LIMIT 10");
        $stmtEsc->execute([$farmerId]);

        $recentMessages = array_merge($stmtAi->fetchAll(), $stmtOfficer->fetchAll(), $stmtEsc->fetchAll());
        usort($recentMessages, fn($a, $b) => strtotime($a['sent_at']) <=> strtotime($b['sent_at']));
        $recentMessages = array_slice($recentMessages, -10);

        // Upcoming visits
        $visitModel = new Visit();
        $visits = $visitModel->getByOfficer(0); // all for farmer — we'll filter in the query
        $stmt = $db->prepare(
            "SELECT v.*, u.name AS officer_name FROM visits v
             JOIN users u ON u.id = v.officer_id
             WHERE v.farmer_id = :fid AND v.scheduled_at >= NOW()
             ORDER BY v.scheduled_at ASC LIMIT 3"
        );
        $stmt->execute(['fid' => $farmerId]);
        $upcomingVisits = $stmt->fetchAll();

        // Assigned Officer
        $assignedOfficer = null;
        if ($farmer['ward_id']) {
            $offStmt = $db->prepare("
                SELECT u.name, u.phone FROM users u
                JOIN officer_wards ow ON ow.officer_id = u.id
                WHERE ow.ward_id = ? AND u.role = 'ward_officer' AND u.is_active = 1
                LIMIT 1
            ");
            $offStmt->execute([$farmer['ward_id']]);
            $assignedOfficer = $offStmt->fetch() ?: null;
        }

        // Live weather (cached in session for 30 min)
        $weatherService = new WeatherService();
        $weatherRaw = [];
        if (isset($_SESSION['weather_cache_time']) && (time() - $_SESSION['weather_cache_time'] < 1800)) {
            $weatherRaw = $_SESSION['weather_cache'] ?? [];
        } else {
            $weatherRaw = $weatherService->getWeatherByVillage($farmer['village_id'] ?? $farmer['village_name'] ?? 'Kakonko');
            $_SESSION['weather_cache'] = $weatherRaw;
            $_SESSION['weather_cache_time'] = time();
        }

        $current = $weatherRaw['current'] ?? [];
        $weather = [
            'temp'        => $current['main']['temp'] ?? null,
            'humidity'    => $current['main']['humidity'] ?? null,
            'description' => $current['weather'][0]['description'] ?? '',
            'source'      => $weatherRaw['source'] ?? 'fallback',
            'raw'         => $weatherRaw,
        ];

        $cropProgress = $this->buildCropProgress($farmer);

        $this->view('farmer/dashboard', [
            'farmer'         => $farmer,
            'recentMessages' => $recentMessages,
            'upcomingVisits' => $upcomingVisits,
            'weather'        => $weather,
            'assignedOfficer' => $assignedOfficer,
            'cropProgress'   => $cropProgress,
        ]);
    }

    public function profile()
    {
        $this->requireAuth();
        $farmerId = (int)$_SESSION['farmer_id'];
        $farmerModel = new Farmer();
        $farmer = $farmerModel->findWithDetails($farmerId);

        $db = Database::getInstance()->getConnection();
        $wards = $db->query("SELECT id, name FROM wards ORDER BY name")->fetchAll();
        $crops = (new Crop())->getActive();

        $this->view('farmer/profile', ['farmer' => $farmer, 'wards' => $wards, 'crops' => $crops]);
    }

    public function updateProfile()
    {
        $this->requireAuth();
        $farmerId = (int)$_SESSION['farmer_id'];

        $validator = Validator::make($_POST);
        if (!$validator->validate([
            'phone' => 'required|phone|max:20',
            'ward_id' => 'nullable|int',
            'village_id' => 'nullable|int',
            'farm_size_acres' => 'nullable|float|min:0',
            'crops' => 'nullable|array',
        ])) {
            header('Location: /farmer/profile?error=' . urlencode($validator->firstError() ?? 'validation'));
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $phone = Sanitizer::phone($_POST['phone']);
        $ward_id = Sanitizer::int($_POST['ward_id'] ?? null);
        $village_id = Sanitizer::int($_POST['village_id'] ?? null);
        $farm_size = Sanitizer::float($_POST['farm_size_acres'] ?? null);

        try {
            $db->prepare("
                UPDATE farmers 
                SET phone = ?, ward_id = ?, village_id = ?, farm_size_acres = ?, profile_complete = 1
                WHERE id = ?
            ")->execute([$phone, $ward_id, $village_id, $farm_size, $farmerId]);

            $db->prepare("DELETE FROM farmer_crops WHERE farmer_id = ?")->execute([$farmerId]);
            if (!empty($_POST['crops']) && is_array($_POST['crops'])) {
                $stmt = $db->prepare("INSERT INTO farmer_crops (farmer_id, crop_id, type) VALUES (?, ?, 'primary')");
                foreach ($_POST['crops'] as $cid) {
                    $cropId = Sanitizer::int($cid);
                    if ($cropId) {
                        $stmt->execute([$farmerId, $cropId]);
                    }
                }
            }

            Logger::info('Farmer profile updated', ['farmer_id' => $farmerId]);
            header('Location: /farmer/profile?success=1');
        } catch (\Exception $e) {
            Logger::error('Farmer profile update failed', ['farmer_id' => $farmerId, 'error' => $e->getMessage()]);
            header('Location: /farmer/profile?error=save');
        }
        exit;
    }

    public function chat()
    {
        $this->requireAuth();
        $farmerId = (int)$_SESSION['farmer_id'];
        $threadModel = new ChatThread();

        if (isset($_GET['thread'])) {
            $tid = (int)$_GET['thread'];
            if ($threadModel->findForFarmer($tid, $farmerId)) {
                $_SESSION['chat_thread_id'] = $tid;
            }
        } else {
            unset($_SESSION['chat_thread_id']);
        }

        $activeThreadId = isset($_SESSION['chat_thread_id']) ? (int)$_SESSION['chat_thread_id'] : null;
        $rawThreads = $threadModel->listForFarmer($farmerId, 'web');
        $threads = array_map(fn($t) => ChatThreads::formatSidebarRow($t), $rawThreads);

        $this->view('farmer/chat', [
            'activeThreadId' => $activeThreadId,
            'threads'        => $threads,
        ]);
    }

    public function newChatThread()
    {
        $this->requireAuth();
        unset($_SESSION['chat_thread_id']);
        header('Location: /farmer/chat');
        exit;
    }

    public function completeProfile()
    {
        $this->requireAuth();
        $farmerId = (int)$_SESSION['farmer_id'];
        $farmerModel = new Farmer();
        $farmer = $farmerModel->findWithDetails($farmerId);

        if (($farmer['profile_complete'] ?? 1) == 1) {
            header('Location: /farmer/dashboard');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $wards = $db->query("SELECT w.id, w.name, d.name AS district_name FROM wards w JOIN districts d ON d.id = w.district_id ORDER BY d.name, w.name")->fetchAll();
        $crops = (new Crop())->getActive();

        $this->view('farmer/complete_profile', ['farmer' => $farmer, 'wards' => $wards, 'crops' => $crops]);
    }

    public function storeCompleteProfile()
    {
        $this->requireAuth();
        header('Content-Type: application/json');

        $farmerId = (int)$_SESSION['farmer_id'];
        $ward_id = (int)($_POST['ward_id'] ?? 0);
        $village_id = (int)($_POST['village_id'] ?? 0);
        $farm_size_acres = (float)($_POST['farm_size_acres'] ?? 0);
        $primary_crop_id = (int)($_POST['primary_crop_id'] ?? 0);
        $secondary_crop_ids = $_POST['secondary_crop_ids'] ?? [];

        if (!$ward_id || !$village_id || !$farm_size_acres || !$primary_crop_id) {
            echo json_encode(['ok' => false, 'msg' => 'Tafadhali jaza sehemu zote zinazohitajika.']);
            return;
        }

        $db = Database::getInstance()->getConnection();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("UPDATE farmers SET village_id=?, ward_id=?, farm_size_acres=?, profile_complete=1 WHERE id=?");
            $stmt->execute([$village_id, $ward_id, $farm_size_acres, $farmerId]);

            // Update crops (wipe and recreate)
            $db->prepare("DELETE FROM farmer_crops WHERE farmer_id = ?")->execute([$farmerId]);
            $db->prepare("INSERT INTO farmer_crops (farmer_id, crop_id, type) VALUES (?, ?, 'primary')")->execute([$farmerId, $primary_crop_id]);

            // Insert secondary crops
            foreach ($secondary_crop_ids as $cid) {
                $cid = (int)$cid;
                if ($cid > 0 && $cid !== $primary_crop_id) {
                    $db->prepare("INSERT INTO farmer_crops (farmer_id, crop_id, type) VALUES (?, ?, 'secondary') ON DUPLICATE KEY UPDATE type=type")->execute([$farmerId, $cid]);
                }
            }

            $db->commit();

            // update session with ward
            $_SESSION['ward_id'] = $ward_id;

            echo json_encode(['ok' => true, 'redirect' => '/farmer/dashboard']);
        } catch (\Exception $e) {
            $db->rollBack();
            echo json_encode(['ok' => false, 'msg' => 'Hitilafu imetokea wakati wa kuhifadhi.']);
        }
    }

    public function sendMessage()
    {
        $this->requireAuth();
        header('Content-Type: application/json');
        set_time_limit(180);

        $farmerId = (int)$_SESSION['farmer_id'];
        $text = Sanitizer::textarea($_POST['text'] ?? '', 1000);

        if (!$text) {
            echo json_encode(['ok' => false, 'error' => 'Message is empty']);
            return;
        }

        try {
            $threadId = isset($_SESSION['chat_thread_id']) ? (int)$_SESSION['chat_thread_id'] : null;
            $chatService = new FarmerChatService();
            $result = $chatService->handleMessage($farmerId, $text, 'web', $threadId ?: null);
            if (!empty($result['thread_id'])) {
                $_SESSION['chat_thread_id'] = (int)$result['thread_id'];
            }
            $threadPreview = null;
            if (!empty($result['thread_id'])) {
                $row = (new ChatThread())->findForFarmer((int)$result['thread_id'], $farmerId);
                $preview = $row['title'] ?? $text;
                $threadPreview = mb_substr($preview, 0, 50) . (mb_strlen($preview) > 50 ? '…' : '');
            }
            echo json_encode([
                'ok'             => true,
                'reply'          => $result['reply'],
                'escalated'      => $result['escalated'],
                'thread_id'      => $result['thread_id'] ?? null,
                'thread_preview' => $threadPreview,
            ]);
        } catch (\Throwable $e) {
            Logger::error('Farmer web chat failed', ['farmer_id' => $farmerId, 'error' => $e->getMessage()]);
            echo json_encode(['ok' => false, 'error' => 'Hitilafu imetokea. Jaribu tena.']);
        }
    }

    public function crops()
    {
        $this->requireAuth();
        $farmerId = (int)$_SESSION['farmer_id'];
        $farmerModel = new Farmer();
        $farmer = $farmerModel->findWithDetails($farmerId);
        $db = Database::getInstance()->getConnection();

        $cropProgress = $this->buildCropProgress($farmer);

        $weatherService = new WeatherService();
        $weatherRaw = $weatherService->getWeatherByVillage($farmer['village_id'] ?? $farmer['village_name'] ?? 'Kakonko');
        $weatherAlert = $weatherService->buildCropAlert($weatherRaw);

        $articles = Cms::farmerLibraryArticles($db);

        $harvests = [];
        try {
            $hStmt = $db->prepare("
                SELECT fh.*, c.name_en AS crop_name
                FROM farmer_harvests fh
                JOIN crops c ON c.id = fh.crop_id
                WHERE fh.farmer_id = ?
                ORDER BY fh.harvest_year DESC, fh.recorded_at DESC
                LIMIT 10
            ");
            $hStmt->execute([$farmerId]);
            $harvests = $hStmt->fetchAll();
        } catch (\Throwable $e) {
            $harvests = [];
        }

        $this->view('farmer/crops', [
            'farmer'        => $farmer,
            'cropProgress'  => $cropProgress,
            'weatherAlert'  => $weatherAlert,
            'articles'      => $articles,
            'harvests'      => $harvests,
        ]);
    }

    public function updateCropStage()
    {
        $this->requireAuth();
        header('Content-Type: application/json');

        $farmerId = (int)$_SESSION['farmer_id'];
        $cropId = Sanitizer::int($_POST['crop_id'] ?? null);
        $stageId = Sanitizer::int($_POST['stage_id'] ?? null);

        if (!$cropId || !$stageId) {
            echo json_encode(['ok' => false, 'error' => 'Taarifa haijakamilika.']);
            return;
        }

        $db = Database::getInstance()->getConnection();

        $check = $db->prepare("SELECT crop_id FROM farmer_crops WHERE farmer_id = ? AND crop_id = ?");
        $check->execute([$farmerId, $cropId]);
        if (!$check->fetch()) {
            echo json_encode(['ok' => false, 'error' => 'Zao halipatikani.']);
            return;
        }

        $stageCheck = $db->prepare("SELECT id FROM growth_stages WHERE id = ? AND crop_id = ?");
        $stageCheck->execute([$stageId, $cropId]);
        if (!$stageCheck->fetch()) {
            echo json_encode(['ok' => false, 'error' => 'Hatua si sahihi kwa zao hili.']);
            return;
        }

        try {
            $db->prepare("UPDATE farmer_crops SET growth_stage_id = ? WHERE farmer_id = ? AND crop_id = ?")
               ->execute([$stageId, $farmerId, $cropId]);
            echo json_encode(['ok' => true]);
        } catch (\Throwable $e) {
            Logger::error('Crop stage update failed', ['farmer_id' => $farmerId, 'error' => $e->getMessage()]);
            echo json_encode(['ok' => false, 'error' => 'Imeshindikana kusasisha. Jaribu tena.']);
        }
    }

    /** Build timeline + progress for the farmer's primary crop. */
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

        $cropId = (int)($primary['id'] ?? 1);
        $cropName = $primary['name'] ?? 'Mahindi';
        $plantedDate = $primary['planted_date'] ?? date('Y-m-d', strtotime('-60 days'));
        $currentStageId = $primary['growth_stage_id'] ?? null;

        $cropModel = new Crop();
        $dbStages = $cropModel->getStages($cropId);
        if (empty($dbStages)) {
            $dbStages = $cropModel->getStages(1);
            $cropId = 1;
        }

        if (!$currentStageId && !empty($dbStages)) {
            $currentStageId = (int)$dbStages[min(2, count($dbStages) - 1)]['id'];
        }

        $stageIcons = ['eco', 'grass', 'spa', 'water_drop', 'forest', 'local_florist', 'agriculture', 'inventory_2', 'store', 'warehouse'];
        $activeIdx = 0;
        foreach ($dbStages as $i => $st) {
            if ((int)$st['id'] === (int)$currentStageId) {
                $activeIdx = $i;
                break;
            }
        }

        $stages = [];
        foreach ($dbStages as $i => $st) {
            $entry = [
                'id'    => (int)$st['id'],
                'label' => $st['name_sw'],
                'icon'  => $stageIcons[$i % count($stageIcons)],
                'done'  => $i < $activeIdx,
            ];
            if ($i === $activeIdx) {
                $entry['active'] = true;
            }
            $stages[] = $entry;
        }

        $total = max(count($stages), 1);
        $percent = (int)round((($activeIdx + 1) / $total) * 100);
        $activeLabel = $stages[$activeIdx]['label'] ?? 'Ukuaji';

        return [
            'crop_id'       => $cropId,
            'crop_name'     => $cropName,
            'planted_date'  => $plantedDate,
            'stage_id'      => $currentStageId,
            'stage_label'   => $activeLabel,
            'progress_pct'  => $percent,
            'stages'        => $stages,
            'db_stages'     => $dbStages,
        ];
    }

    public function weather()
    {
        $this->requireAuth();
        $farmerId = (int)$_SESSION['farmer_id'];
        $farmerModel = new Farmer();
        $farmer = $farmerModel->findWithDetails($farmerId);

        $weatherService = new WeatherService();
        $weather = $weatherService->getWeatherByVillage($farmer['village_id'] ?? $farmer['ward_name'] ?? 'Kakonko');

        $this->view('farmer/weather', compact('farmer', 'weather'));
    }

    public function visitRequests()
    {
        $this->requireAuth();
        $farmerId = (int)$_SESSION['farmer_id'];
        $farmerModel = new Farmer();
        $farmer = $farmerModel->findWithDetails($farmerId);
        $visitRequestModel = new VisitRequest();
        $requests = $visitRequestModel->findByFarmer($farmerId);
        $this->view('farmer/visit_requests', compact('farmer', 'requests'));
    }

    public function createVisitRequest()
    {
        $this->requireAuth();
        $farmerId = (int)$_SESSION['farmer_id'];
        $reason = trim($_POST['reason'] ?? '');
        $preferredDate = trim($_POST['preferred_date'] ?? '');
        $preferredTime = trim($_POST['preferred_time'] ?? '');

        if (empty($reason)) {
            $_SESSION['flash'] = 'Reason is required';
            header('Location: /farmer/visits');
            exit;
        }
        $visitRequestModel = new VisitRequest();
        $snap = $visitRequestModel->farmerSnapshot($farmerId);
        $visitRequestModel->create([
            'farmer_id'       => $farmerId,
            'request_reason'  => $reason,
            'preferred_date'  => $preferredDate ?: null,
            'preferred_time'  => $preferredTime ?: null,
            'crop_id'         => $snap['crop_id'],
            'farm_size_acres' => $snap['farm_size_acres'],
        ]);
        $_SESSION['flash'] = 'Request sent successfully!';
        header('Location: /farmer/visits');
        exit;
    }
}
