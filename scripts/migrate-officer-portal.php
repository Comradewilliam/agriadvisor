<?php
/**
 * Idempotent officer portal migration (visits, visit_requests, automated_alerts).
 */
declare(strict_types=1);

if (!isset($pdo)) {
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
}

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return (bool)$stmt->fetch();
}

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$table]);
    return (bool)$stmt->fetch();
}

function addColumn(PDO $pdo, string $table, string $sql): void
{
    try {
        $pdo->exec($sql);
        echo "OK: $table — " . substr($sql, 0, 60) . "...\n";
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate column')) {
            echo "SKIP: column exists\n";
        } else {
            echo "NOTE: " . $e->getMessage() . "\n";
        }
    }
}

$visitCols = [
    "ALTER TABLE `visits` ADD COLUMN `visit_type` VARCHAR(50) DEFAULT 'officer_planned' AFTER `reason`",
    "ALTER TABLE `visits` ADD COLUMN `crop_id` INT NULL AFTER `visit_type`",
    "ALTER TABLE `visits` ADD COLUMN `farm_size_acres` DECIMAL(6,2) NULL AFTER `crop_id`",
    "ALTER TABLE `visits` ADD COLUMN `visit_request_id` INT NULL AFTER `farm_size_acres`",
    "ALTER TABLE `visits` ADD COLUMN `visit_batch_id` VARCHAR(64) NULL AFTER `visit_request_id`",
    "ALTER TABLE `visits` ADD COLUMN `outcome` VARCHAR(100) NULL AFTER `notes`",
    "ALTER TABLE `visits` ADD COLUMN `not_done_reason` TEXT NULL AFTER `outcome`",
    "ALTER TABLE `visits` ADD COLUMN `followup` TEXT NULL AFTER `not_done_reason`",
    "ALTER TABLE `visits` ADD COLUMN `followup_at` DATETIME NULL AFTER `followup`",
    "ALTER TABLE `visits` ADD COLUMN `rescheduled_at` DATETIME NULL AFTER `followup_at`",
];

foreach ($visitCols as $sql) {
    if (!columnExists($pdo, 'visits', explode('`', $sql)[3] ?? '')) {
        addColumn($pdo, 'visits', $sql);
    }
}

try {
    $pdo->exec("ALTER TABLE `visits` MODIFY COLUMN `status` ENUM('pending','scheduled','completed','cancelled','postponed') DEFAULT 'scheduled'");
    echo "OK: visits.status enum updated\n";
} catch (PDOException $e) {
    echo "NOTE status: " . $e->getMessage() . "\n";
}

$vrCols = [
    'crop_id' => "ALTER TABLE `visit_requests` ADD COLUMN `crop_id` INT NULL AFTER `request_reason`",
    'farm_size_acres' => "ALTER TABLE `visit_requests` ADD COLUMN `farm_size_acres` DECIMAL(6,2) NULL AFTER `crop_id`",
    'scheduled_at' => "ALTER TABLE `visit_requests` ADD COLUMN `scheduled_at` DATETIME NULL AFTER `farm_size_acres`",
    'visit_id' => "ALTER TABLE `visit_requests` ADD COLUMN `visit_id` INT NULL AFTER `scheduled_at`",
    'rescheduled_at' => "ALTER TABLE `visit_requests` ADD COLUMN `rescheduled_at` DATETIME NULL AFTER `visit_id`",
];
foreach ($vrCols as $col => $sql) {
    if (!columnExists($pdo, 'visit_requests', $col)) {
        addColumn($pdo, 'visit_requests', $sql);
    }
}

if (!tableExists($pdo, 'automated_alerts')) {
    $pdo->exec(file_get_contents(dirname(__DIR__) . '/database/patch_officer_portal.sql'));
    // extract CREATE TABLE only
    $sql = <<<'SQL'
CREATE TABLE `automated_alerts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `district_id` INT NOT NULL,
    `ward_id` INT NULL,
    `alert_type` ENUM('weather','welcome','visit','crop_advisory','custom') NOT NULL DEFAULT 'custom',
    `title` VARCHAR(255) NOT NULL,
    `message_template` TEXT NOT NULL,
    `channel` ENUM('sms','both') NOT NULL DEFAULT 'sms',
    `trigger_event` ENUM('on_register','on_visit_scheduled','on_visit_reminder','weather_daily','manual') NOT NULL DEFAULT 'manual',
    `trigger_offset_hours` INT NOT NULL DEFAULT 24,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`district_id`) REFERENCES `districts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`ward_id`) REFERENCES `wards`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_auto_alerts_district` (`district_id`, `is_active`)
)
SQL;
    try {
        $pdo->exec($sql);
        echo "OK: automated_alerts created\n";
    } catch (PDOException $e) {
        echo "automated_alerts: " . $e->getMessage() . "\n";
    }
} else {
    echo "SKIP: automated_alerts exists\n";
}

// Run migration only when executed directly
if (realpath($argv[0] ?? '') === realpath(__FILE__)) {
    echo "Migration complete.\n";
}
