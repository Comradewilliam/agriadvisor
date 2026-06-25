<?php
/**
 * Apply SQL patch file (idempotent where possible).
 * Usage: php scripts/apply-patch.php database/patch_farmer_portal.sql
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/Core/Env.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

$patchFile = $argv[1] ?? '';
if (!$patchFile || !is_file($patchFile)) {
    fwrite(STDERR, "Usage: php scripts/apply-patch.php <path-to.sql>\n");
    exit(1);
}

$config = require dirname(__DIR__) . '/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['dbname']};charset={$db['charset']}",
    $db['user'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$sql = file_get_contents($patchFile);

// Split on semicolons (simple; patch file avoids semicolons in strings)
$statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));

foreach ($statements as $stmt) {
    if ($stmt === '' || str_starts_with($stmt, '--')) {
        continue;
    }
    // Skip pure comment blocks
    $lines = array_filter(array_map('trim', explode("\n", $stmt)), fn($l) => $l !== '' && !str_starts_with($l, '--'));
    if (empty($lines)) {
        continue;
    }
    $stmt = implode("\n", $lines);
    // Strip inline leading comment if file used single-line blocks
    $stmt = preg_replace('/^--[^\n]*\n+/m', '', $stmt);

    try {
        $pdo->exec($stmt);
        echo "OK: " . substr(str_replace("\n", ' ', $stmt), 0, 80) . "...\n";
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        // Ignore duplicate column / already exists
        if (str_contains($msg, 'Duplicate column')
            || str_contains($msg, 'already exists')
            || str_contains($msg, 'Duplicate key name')
            || str_contains($msg, 'Duplicate entry')) {
            echo "SKIP: " . substr($msg, 0, 100) . "\n";
            continue;
        }
        fwrite(STDERR, "FAIL: {$msg}\n  SQL: " . substr($stmt, 0, 120) . "\n");
    }
}

// Add FK for growth_stage_id if missing
try {
    $pdo->exec("ALTER TABLE `farmer_crops` ADD CONSTRAINT `fk_fc_growth_stage` FOREIGN KEY (`growth_stage_id`) REFERENCES `growth_stages`(`id`) ON DELETE SET NULL");
    echo "OK: Added FK fk_fc_growth_stage\n";
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'already exists')) {
        echo "SKIP: FK already present\n";
    } else {
        echo "NOTE: " . $e->getMessage() . "\n";
    }
}

echo "Done.\n";
