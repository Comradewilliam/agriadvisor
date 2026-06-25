<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Farmer;
use App\Models\User;
use App\Services\OtpService;
use App\Services\AfricasTalkingService;

class AuthController extends Controller {

    public function showFarmerLogin() {
        $this->view('farmer/login', [], 'guest');
    }

    public function showFarmerRegister() {
        $db = \App\Core\Database::getInstance()->getConnection();
        $districts = $db->query("SELECT id, name, region FROM districts WHERE is_active = 1 OR is_active IS NULL ORDER BY name")->fetchAll();
        $crops = $db->query("SELECT * FROM crops WHERE is_active = 1")->fetchAll();
        $this->view('farmer/register', ['districts' => $districts, 'crops' => $crops], 'guest');
    }

    public function registerFarmer() {
        header('Content-Type: application/json');
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $phone = \App\Helpers\Sanitizer::normalizePhone(trim($_POST['phone'] ?? ''));
        $ward_id = (int)($_POST['ward_id'] ?? 0);
        $village_id = (int)($_POST['village_id'] ?? 0);
        $farm_size_acres = (float)($_POST['farm_size_acres'] ?? 0);
        $primary_crop_id = (int)($_POST['primary_crop_id'] ?? 0);
        $secondary_crop_ids = $_POST['secondary_crop_ids'] ?? [];

        if (!$first_name || !$last_name || !$phone || !$ward_id || !$village_id || !$farm_size_acres || !$primary_crop_id) {
            echo json_encode(['ok'=>false, 'msg'=>'Tafadhali jaza sehemu zote zinazohitajika.']);
            return;
        }

        $farmerModel = new Farmer();
        if ($farmerModel->findByPhone($phone)) {
            echo json_encode(['ok'=>false, 'msg'=>'Namba ya simu imeshasajiliwa.']);
            return;
        }

        $db = \App\Core\Database::getInstance()->getConnection();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("INSERT INTO farmers (first_name, last_name, phone, village_id, ward_id, farm_size_acres, profile_complete, registered_via) VALUES (?, ?, ?, ?, ?, ?, 1, 'web')");
            $stmt->execute([$first_name, $last_name, $phone, $village_id, $ward_id, $farm_size_acres]);
            $farmerId = $db->lastInsertId();

            // Insert primary crop
            $db->prepare("INSERT INTO farmer_crops (farmer_id, crop_id, type) VALUES (?, ?, 'primary')")->execute([$farmerId, $primary_crop_id]);

            // Insert secondary crops
            foreach ($secondary_crop_ids as $cid) {
                $cid = (int)$cid;
                if ($cid > 0 && $cid !== $primary_crop_id) {
                    $db->prepare("INSERT INTO farmer_crops (farmer_id, crop_id, type) VALUES (?, ?, 'secondary') ON DUPLICATE KEY UPDATE type=type")->execute([$farmerId, $cid]);
                }
            }
            $db->commit();
            echo json_encode(['ok'=>true, 'redirect'=>'/farmer/login?registered=1']);
        } catch (\Exception $e) {
            $db->rollBack();
            echo json_encode(['ok'=>false, 'msg'=>'Hitilafu imetokea wakati wa usajili.']);
        }
    }

    public function requestOtp() {
        header('Content-Type: application/json');
        $phone = \App\Helpers\Sanitizer::normalizePhone($_POST['phone'] ?? '');
        if (!$phone) { echo json_encode(['ok'=>false,'msg'=>'Phone number required.']); return; }

        $farmerModel = new Farmer();
        $farmer = $farmerModel->findByPhone($phone);

        if (!$farmer) {
            echo json_encode(['ok'=>false,'msg'=>'Phone number not registered. Please register via USSD first.']);
            return;
        }

        $otpService = new OtpService();
        $token = $otpService->generateToken($phone);
        $mins = $otpService->expiryMinutes();
        $at = new AfricasTalkingService();
        $at->sendOneWay($phone, "Nambari yako ya kuingia Agri-Advisory System: {$token}. Inaisha dakika {$mins}.");

        echo json_encode(['ok'=>true,'msg'=>'OTP sent to '.$phone]);
    }

    public function verifyOtp() {
        header('Content-Type: application/json');
        $phone = \App\Helpers\Sanitizer::normalizePhone($_POST['phone'] ?? '');
        $token = trim($_POST['token'] ?? '');

        if (!$phone || !$token) {
            echo json_encode(['ok'=>false,'msg'=>'Phone and OTP are required.']);
            return;
        }

        $otpService = new OtpService();
        if (!$otpService->verifyToken($phone, $token)) {
            echo json_encode(['ok'=>false,'msg'=>'Invalid or expired OTP. Please try again.']);
            return;
        }

        $farmerModel = new Farmer();
        $farmer = $farmerModel->findByPhone($phone);

        $_SESSION['farmer_id'] = $farmer['id'];
        $_SESSION['farmer_name'] = $farmer['name'];
        $_SESSION['ward_id'] = $farmer['ward_id'];
        $_SESSION['role'] = 'farmer';

        if (($farmer['profile_complete'] ?? 1) == 0) {
            echo json_encode(['ok'=>true,'redirect'=>'/farmer/profile/complete']);
        } else {
            echo json_encode(['ok'=>true,'redirect'=>'/farmer/dashboard']);
        }
    }

    public function logout() {
        session_destroy();
        header('Location: /farmer/login');
        exit;
    }

    // ─── Staff Login (Officer/Admin) ────────────────────────────────────────

    public function showStaffLogin() {
        $this->view('officer/login', [], 'guest');
    }

    public function staffLogin() {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if ($user && in_array($user['role'], ['ward_officer','dao','super_admin']) && password_verify($password, $user['password_hash'])) {
            if (!$user['is_active']) {
                $this->view('officer/login', ['error' => 'Akaunti imesitishwa. Wasiliana na msimamizi.'], 'guest');
                return;
            }

            // Get assigned wards
            $db = \App\Core\Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT ward_id FROM officer_wards WHERE officer_id = :id");
            $stmt->execute(['id' => $user['id']]);
            $wardRows = $stmt->fetchAll();
            $wardIds = array_column($wardRows, 'ward_id');

            $_SESSION['user'] = $user;
            $_SESSION['officer_id']   = $user['id'];
            $_SESSION['officer_name'] = $user['name'];
            $_SESSION['role']         = $user['role'];
            $_SESSION['ward_id']      = !empty($wardIds) ? (int)$wardIds[0] : 0;
            $_SESSION['ward_ids']     = $wardIds;
            
            if ($user['role'] === 'super_admin') {
                header('Location: /admin/dashboard');
            } else {
                header('Location: /officer/dashboard');
            }
            exit;
        }

        $this->view('officer/login', ['error' => 'Barua pepe au nenosiri sio sahihi.'], 'guest');
    }

    public function staffLogout() {
        session_destroy();
        header('Location: /login');
        exit;
    }
}
