<?php
// ─── USSD Webhook ──────────────────────────────────────────────────────────
$router->add('POST', '/api/ussd',         [\App\Controllers\UssdController::class, 'handle']);

// ─── SMS Webhooks ──────────────────────────────────────────────────────────
$router->add('POST', '/api/sms/inbound',  [\App\Controllers\SmsController::class, 'inbound']);
$router->add('POST', '/api/sms/delivery', [\App\Controllers\SmsController::class, 'delivery']);
