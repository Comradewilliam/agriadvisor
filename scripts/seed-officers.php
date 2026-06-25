<?php
/**
 * Apply officer portal DB patch + seed WAOs and automated alerts.
 * Usage: php scripts/seed-officers.php
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/Core/Env.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

$config = require dirname(__DIR__) . '/config.php';
$db = $config['db'];
$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['dbname']};charset={$db['charset']}",
    $db['user'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

require __DIR__ . '/migrate-officer-portal.php';
echo "--- Seeding officers ---\n";

$hash = '$2y$10$4itNM1bQ2llzH2QbIrnT4OH1ADTliLpqP0EKWGry.kpvyz8j1hv2y'; // QAZzaq123

$adminId = (int)$pdo->query("SELECT id FROM users WHERE role = 'super_admin' ORDER BY id LIMIT 1")->fetchColumn();
if (!$adminId) {
    $adminId = 1;
}

// DAO for Kakonko
$pdo->exec("DELETE FROM officer_districts WHERE officer_id IN (SELECT id FROM users WHERE email = 'mwandambo@agriadvisory.co.tz')");
$pdo->prepare("DELETE FROM users WHERE email = ?")->execute(['mwandambo@agriadvisory.co.tz']);

$pdo->prepare("
    INSERT INTO users (name, email, phone, password_hash, role, working_office, is_active, created_by)
    VALUES (?, ?, ?, ?, 'dao', ?, 1, ?)
")->execute([
    'Joseph Mwandambo',
    'mwandambo@agriadvisory.co.tz',
    '255754000001',
    $hash,
    'Ofisi ya DAO Kakonko',
    $adminId,
]);
$daoId = (int)$pdo->lastInsertId();
$pdo->prepare('INSERT IGNORE INTO officer_districts (officer_id, district_id) VALUES (?, 1)')->execute([$daoId]);

$officers = [
    ['John Mushi',       'mushi@agriadvisory.co.tz',       '255754000101', [1, 2, 3]],
    ['Grace Nyoni',      'nyoni@agriadvisory.co.tz',       '255754000102', [3, 4, 5]],
    ['Peter Kasanga',    'kasanga@agriadvisory.co.tz',     '255754000103', [5, 6, 7]],
    ['Anna Rwakatare',   'rwakatare@agriadvisory.co.tz',   '255754000104', [7, 8, 9]],
    ['James Ndunguru',   'ndunguru@agriadvisory.co.tz',    '255754000105', [9, 10, 11]],
    ['Mary Kilonzo',     'kilonzo@agriadvisory.co.tz',     '255754000106', [11, 12, 13]],
    ['David Magesa',     'magesa@agriadvisory.co.tz',      '255754000107', [1, 4, 7]],
    ['Sarah Buhigwe',    'buhigwe@agriadvisory.co.tz',     '255754000108', [2, 5, 8]],
    ['Emmanuel Rugendo', 'rugendo@agriadvisory.co.tz',     '255754000109', [3, 6, 9]],
];

$emails = array_column($officers, 1);
$placeholders = implode(',', array_fill(0, count($emails), '?'));
$pdo->prepare("DELETE FROM officer_wards WHERE officer_id IN (SELECT id FROM users WHERE email IN ($placeholders))")->execute($emails);
$pdo->prepare("DELETE FROM users WHERE email IN ($placeholders) AND role = 'ward_officer'")->execute($emails);

$wardStmt = $pdo->prepare('INSERT INTO officer_wards (officer_id, ward_id) VALUES (?, ?)');

foreach ($officers as [$name, $email, $phone, $wards]) {
    $pdo->prepare("
        INSERT INTO users (name, email, phone, password_hash, role, working_office, is_active, created_by)
        VALUES (?, ?, ?, ?, 'ward_officer', 'Ofisi ya Kilimo Kakonko', 1, ?)
    ")->execute([$name, $email, $phone, $hash, $daoId]);
    $oid = (int)$pdo->lastInsertId();
    foreach ($wards as $wid) {
        $wardStmt->execute([$oid, $wid]);
    }
    echo "OK: {$name} ({$email}) → wards " . implode(',', $wards) . "\n";
}

// Automated alerts (clear demo rows for district 1)
$pdo->exec("DELETE FROM automated_alerts WHERE district_id = 1 AND title LIKE 'Demo:%'");

$alerts = [
    [
        'Demo: Karibu Mkulima',
        'welcome',
        'on_register',
        null,
        'Karibu {name} kwenye Agri-Advisory! Umesajiliwa kijiji cha {village}. Mazao yako: {crop}. Wasiliana na afisa wako kwa msaada.',
        0,
    ],
    [
        'Demo: Ziara Imeandaliwa',
        'visit',
        'on_visit_scheduled',
        null,
        'Habari {name}, ziara ya shamba imeandaliwa tarehe {visit_date} kwa sababu: {visit_reason}. Tafadhali kuwa tayari.',
        0,
    ],
    [
        'Demo: Ukumbusho wa Ziara',
        'visit',
        'on_visit_reminder',
        null,
        'Ukumbusho {name}: kesho {visit_date} afisa wa kilimo atakuja shambani. Kijiji: {village}.',
        24,
    ],
    [
        'Demo: Tahadhari ya Hali ya Hewa',
        'weather',
        'weather_daily',
        null,
        'Tahadhari ya leo {name}: {weather_summary}. Panga shughuli za shamba ipasavyo. Kijiji: {village}.',
        0,
    ],
    [
        'Demo: Mvua Kakonko Ward',
        'weather',
        'weather_daily',
        1,
        'Wakulima wa {village}: tarifa ya hewa leo — {weather_summary}. Tahadhari na mipango ya kilimo.',
        0,
    ],
];

$alertStmt = $pdo->prepare("
    INSERT INTO automated_alerts
        (district_id, ward_id, alert_type, title, message_template, channel, trigger_event, trigger_offset_hours, is_active, created_by)
    VALUES (1, ?, ?, ?, ?, 'both', ?, ?, 1, ?)
");

foreach ($alerts as [$title, $type, $trigger, $wardId, $template, $offset]) {
    $alertStmt->execute([$wardId, $type, $title, $template, $trigger, $offset, $daoId]);
    echo "Alert: {$title}\n";
}

echo "\nDone. Password for all seeded officers: QAZzaq123\n";
