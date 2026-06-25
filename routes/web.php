<?php

use App\Core\Controller;

// ─── Language Switching ────────────────────────────────────────────────────
$router->add('POST', '/set-lang', function () {
    $lang = $_POST['lang'] ?? 'en';
    if (in_array($lang, ['en', 'sw'])) {
        $_SESSION['lang'] = $lang;
    }
    echo json_encode(['ok' => true]);
});
$router->add('GET',  '/', [\App\Controllers\HomeController::class, 'index']);

// ─── Farmer Auth ───────────────────────────────────────────────────────────
$router->add('GET',  '/farmer/login',         [\App\Controllers\AuthController::class, 'showFarmerLogin']);
$router->add('GET',  '/farmer/register',      [\App\Controllers\AuthController::class, 'showFarmerRegister']);
$router->add('POST', '/api/auth/register',    [\App\Controllers\AuthController::class, 'registerFarmer']);
$router->add('POST', '/api/auth/request-otp', [\App\Controllers\AuthController::class, 'requestOtp']);
$router->add('POST', '/api/auth/verify-otp',  [\App\Controllers\AuthController::class, 'verifyOtp']);
$router->add('GET',  '/farmer/logout',        [\App\Controllers\AuthController::class, 'logout']);

// ─── Farmer Portal ─────────────────────────────────────────────────────────
$router->add('GET',  '/farmer/dashboard', [\App\Controllers\FarmerController::class, 'dashboard']);
$router->add('GET',  '/farmer/profile',   [\App\Controllers\FarmerController::class, 'profile']);
$router->add('POST', '/farmer/profile',   [\App\Controllers\FarmerController::class, 'updateProfile']);
$router->add('GET',  '/farmer/profile/complete', [\App\Controllers\FarmerController::class, 'completeProfile']);
$router->add('POST', '/farmer/profile/complete', [\App\Controllers\FarmerController::class, 'storeCompleteProfile']);
$router->add('GET',  '/farmer/chat',      [\App\Controllers\FarmerController::class, 'chat']);
$router->add('GET',  '/farmer/chat/new',  [\App\Controllers\FarmerController::class, 'newChatThread']);
$router->add('POST', '/farmer/chat/send', [\App\Controllers\FarmerController::class, 'sendMessage']);
$router->add('GET',  '/farmer/crops',     [\App\Controllers\FarmerController::class, 'crops']);
$router->add('POST', '/farmer/crops/stage', [\App\Controllers\FarmerController::class, 'updateCropStage']);
$router->add('GET',  '/farmer/weather',   [\App\Controllers\FarmerController::class, 'weather']);
$router->add('GET',  '/farmer/visits',    [\App\Controllers\FarmerController::class, 'visitRequests']);
$router->add('POST', '/farmer/visits/request', [\App\Controllers\FarmerController::class, 'createVisitRequest']);

// ─── Staff (Officer & Admin) Auth ──────────────────────────────────────────
$router->add('GET',  '/login',  [\App\Controllers\AuthController::class, 'showStaffLogin']);
$router->add('POST', '/login',  [\App\Controllers\AuthController::class, 'staffLogin']);
$router->add('GET',  '/logout', [\App\Controllers\AuthController::class, 'staffLogout']);

// ─── Officer Portal ────────────────────────────────────────────────────────
$router->add('GET',  '/officer/dashboard',      [\App\Controllers\OfficerController::class, 'dashboard']);
$router->add('GET',  '/officer/farmers',         [\App\Controllers\OfficerController::class, 'farmers']);
$router->add('GET',  '/officer/farmers/add',     [\App\Controllers\OfficerController::class, 'addFarmer']);
$router->add('POST', '/officer/farmers/add',     [\App\Controllers\OfficerController::class, 'addFarmer']);
$router->add('GET',  '/officer/farmers/view',    [\App\Controllers\OfficerController::class, 'viewFarmer']);
$router->add('GET',  '/officer/farmers/edit',    [\App\Controllers\OfficerController::class, 'editFarmer']);
$router->add('POST', '/officer/farmers/edit',    [\App\Controllers\OfficerController::class, 'updateFarmerProfile']);
$router->add('GET',  '/officer/weather',         [\App\Controllers\OfficerController::class, 'weather']);
$router->add('GET',  '/officer/ai-mentorship',   [\App\Controllers\OfficerController::class, 'aiMentorship']);
$router->add('GET',  '/officer/ai-mentorship/search', [\App\Controllers\OfficerController::class, 'searchKnowledge']);
$router->add('POST', '/officer/ai-mentorship',   [\App\Controllers\OfficerController::class, 'storeKnowledge']);
$router->add('POST', '/officer/ai-mentorship/update', [\App\Controllers\OfficerController::class, 'updateKnowledge']);
$router->add('POST', '/officer/ai-mentorship/delete', [\App\Controllers\OfficerController::class, 'deleteKnowledge']);
$router->add('GET',  '/officer/escalations',     [\App\Controllers\OfficerController::class, 'escalations']);
$router->add('POST', '/officer/escalations/reply', [\App\Controllers\OfficerController::class, 'replyEscalation']);
$router->add('GET',  '/officer/visits',          [\App\Controllers\OfficerController::class, 'visits']);
$router->add('POST', '/officer/visits/schedule', [\App\Controllers\OfficerController::class, 'scheduleVisit']);
$router->add('POST', '/officer/visits/update',   [\App\Controllers\OfficerController::class, 'updateVisit']);
$router->add('POST', '/officer/visits/followup', [\App\Controllers\OfficerController::class, 'visitFollowup']);
$router->add('GET',  '/officer/visit-requests',    [\App\Controllers\OfficerController::class, 'visitRequests']);
$router->add('POST', '/officer/visit-requests/handle', [\App\Controllers\OfficerController::class, 'handleVisitRequest']);
$router->add('GET',  '/officer/automated-alerts', [\App\Controllers\OfficerController::class, 'automatedAlerts']);
$router->add('POST', '/officer/automated-alerts', [\App\Controllers\OfficerController::class, 'storeAutomatedAlert']);
$router->add('POST', '/officer/automated-alerts/toggle', [\App\Controllers\OfficerController::class, 'toggleAutomatedAlert']);
$router->add('POST', '/officer/automated-alerts/update', [\App\Controllers\OfficerController::class, 'updateAutomatedAlert']);
$router->add('GET',  '/officer/analytics',       [\App\Controllers\OfficerController::class, 'analytics']);
$router->add('GET',  '/officer/analytics/export', [\App\Controllers\OfficerController::class, 'exportAnalytics']);
$router->add('POST', '/officer/weather/create',  [\App\Controllers\OfficerController::class, 'createWeatherAlert']);
$router->add('POST', '/officer/weather/approve',  [\App\Controllers\OfficerController::class, 'approveWeatherAlert']);
$router->add('POST', '/officer/weather/reject',   [\App\Controllers\OfficerController::class, 'rejectWeatherAlert']);
$router->add('GET',  '/officer/profile',         [\App\Controllers\OfficerController::class, 'profile']);

// DAO specific routes
$router->add('GET',  '/officer/officers',        [\App\Controllers\OfficerController::class, 'manageWardOfficers']);
$router->add('POST', '/officer/officers/create', [\App\Controllers\OfficerController::class, 'createWardOfficer']);
$router->add('POST', '/officer/officers/update', [\App\Controllers\OfficerController::class, 'updateWardOfficer']);
$router->add('POST', '/officer/automated-alerts/delete', [\App\Controllers\OfficerController::class, 'deleteAutomatedAlert']);
$router->add('GET',  '/officer/officers/view',      [\App\Controllers\OfficerController::class, 'viewOfficer']);

// WAO specific routes
$router->add('GET',  '/officer/relocation',      [\App\Controllers\OfficerController::class, 'relocationRequests']);
$router->add('POST', '/officer/relocation/request', [\App\Controllers\OfficerController::class, 'createRelocationRequest']);
$router->add('POST', '/officer/relocation/approve', [\App\Controllers\OfficerController::class, 'approveRelocation']);
$router->add('POST', '/officer/relocation/reject', [\App\Controllers\OfficerController::class, 'rejectRelocation']);

// ─── AJAX endpoints ────────────────────────────────────────────────────────
$router->add('GET',  '/ajax/villages', [\App\Controllers\OfficerController::class, 'getVillages']);
$router->add('GET',  '/ajax/wards',    [\App\Controllers\OfficerController::class, 'getWards']);
$router->add('GET',  '/ajax/stages',   [\App\Controllers\OfficerController::class, 'getStages']);

// ─── Admin Portal ──────────────────────────────────────────────────────────
$router->add('GET',  '/admin/dashboard',        [\App\Controllers\AdminController::class, 'dashboard']);
$router->add('GET',  '/admin/districts', [\App\Controllers\AdminController::class, 'districts']);
$router->add('POST', '/admin/districts/add', [\App\Controllers\AdminController::class, 'createDistrict']);
$router->add('POST', '/admin/districts/edit', [\App\Controllers\AdminController::class, 'updateDistrict']);
$router->add('POST', '/admin/wards/add', [\App\Controllers\AdminController::class, 'createWard']);
$router->add('POST', '/admin/villages/add', [\App\Controllers\AdminController::class, 'createVillage']);

$router->add('GET',  '/admin/landing-page', [\App\Controllers\AdminController::class, 'landingPageCMS']);
$router->add('POST', '/admin/landing-page/update', [\App\Controllers\AdminController::class, 'updateLandingPageText']);
$router->add('POST', '/admin/landing-page/upload', [\App\Controllers\AdminController::class, 'updateLandingPageImage']);

// ─── API Routes ────────────────────────────────────────────────────────────
$router->add('GET',  '/admin/users',            [\App\Controllers\AdminController::class, 'users']);
$router->add('POST', '/admin/users/create',     [\App\Controllers\AdminController::class, 'createOfficer']);
$router->add('POST', '/admin/users/update',     [\App\Controllers\AdminController::class, 'updateOfficer']);
$router->add('POST', '/admin/users/toggle',     [\App\Controllers\AdminController::class, 'toggleUser']);
$router->add('GET',  '/admin/audit_logs',       [\App\Controllers\AdminController::class, 'auditLogs']);
$router->add('GET',  '/admin/audit_logs/export', [\App\Controllers\AdminController::class, 'exportAuditLogs']);
$router->add('GET',  '/admin/channel_analytics', [\App\Controllers\AdminController::class, 'channelAnalytics']);
$router->add('GET',  '/admin/channel_analytics/export', [\App\Controllers\AdminController::class, 'exportChannelAnalytics']);

// ─── Broadcasts (WAO / DAO / Admin) ───────────────────────────────────────
$router->add('GET',  '/officer/broadcasts',      [\App\Controllers\BroadcastController::class, 'index']);
$router->add('POST', '/officer/broadcasts/send', [\App\Controllers\BroadcastController::class, 'send']);

// ─── Africa's Talking webhooks ─────────────────────────────────────────────
// USSD callback URL:  https://yourdomain.com/ussd
// Two-way SMS (inbound farmer replies): https://yourdomain.com/sms
// Delivery reports (all outbound SMS):  https://yourdomain.com/sms/delivery
// Outbound one-way bulk (broadcasts, alerts, OTP) uses the Messaging API — no separate callback.
$router->add('POST', '/ussd', [\App\Controllers\UssdController::class, 'handle']);
$router->add('GET',  '/internal/sms/diagnose', [\App\Controllers\SmsController::class, 'diagnose']);
$router->add('POST', '/sms', [\App\Controllers\SmsController::class, 'inbound']);
$router->add('POST', '/sms/callback', [\App\Controllers\SmsController::class, 'inbound']);
$router->add('POST', '/sms/delivery', [\App\Controllers\SmsController::class, 'delivery']);
