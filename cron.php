<?php
/**
 * Cron Job Script for Agri-Advisory
 * Usage: Run via cron every hour/day as needed
 * Example: 0 * * * * /usr/bin/php /path/to/agriad/cron.php
 */

// Define paths
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/app/Core/Database.php';

try {
    $db = App\Core\Database::getInstance()->getConnection();
    $now = date('Y-m-d H:i:s');

    echo "Starting cron jobs at {$now}...\n";

    // 1. Expire old OTP tokens (older than 1 hour)
    $stmt = $db->prepare("DELETE FROM otp_tokens WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->execute();
    echo "Cleaned up old OTP tokens: {$stmt->rowCount()} rows deleted.\n";

    // 2. Expire old USSD sessions (older than 1 day)
    $stmt = $db->prepare("DELETE FROM ussd_sessions WHERE start_time < DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $stmt->execute();
    echo "Cleaned up old USSD sessions: {$stmt->rowCount()} rows deleted.\n";

    // 3. Expire pending weather alerts (older than 2 days)
    $stmt = $db->prepare("UPDATE weather_alerts SET approval_status = 'expired', active = 0 WHERE approval_status = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL 2 DAY)");
    $stmt->execute();
    echo "Expired pending weather alerts: {$stmt->rowCount()} rows updated.\n";

    // 4. Optional: Backup database (if you want to enable)
    // $backupFile = BASE_PATH . '/backups/agri-db-' . date('Y-m-d-His') . '.sql';
    // exec('mysqldump -u[user] -p[pass] [dbname] > ' . escapeshellarg($backupFile));
    // echo "DB backup saved to: {$backupFile}\n";

    echo "Cron jobs completed successfully at " . date('Y-m-d H:i:s') . "\n";
} catch (Exception $e) {
    echo "Cron job failed: " . $e->getMessage() . "\n";
    exit(1);
}
