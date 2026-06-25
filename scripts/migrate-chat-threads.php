<?php
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

$sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS `chat_threads` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `farmer_id` INT NOT NULL,
    `channel` ENUM('web', 'sms') NOT NULL DEFAULT 'web',
    `title` VARCHAR(255) DEFAULT NULL,
    `started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`farmer_id`) REFERENCES `farmers`(`id`) ON DELETE CASCADE,
    INDEX `idx_chat_threads_farmer` (`farmer_id`, `updated_at`)
)
SQL;

try {
    $pdo->exec($sql);
    echo "OK: chat_threads created\n";
} catch (PDOException $e) {
    echo "chat_threads: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("ALTER TABLE `ai_messages` ADD COLUMN `thread_id` INT NULL DEFAULT NULL AFTER `farmer_id`");
    echo "OK: thread_id column added\n";
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate column')) {
        echo "SKIP: thread_id already exists\n";
    } else {
        echo "thread_id: " . $e->getMessage() . "\n";
    }
}

try {
    $pdo->exec("ALTER TABLE `ai_messages` ADD CONSTRAINT `fk_ai_thread` FOREIGN KEY (`thread_id`) REFERENCES `chat_threads`(`id`) ON DELETE SET NULL");
    echo "OK: fk_ai_thread added\n";
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'already exists')) {
        echo "SKIP: fk_ai_thread already present\n";
    } else {
        echo "fk: " . $e->getMessage() . "\n";
    }
}

echo "Done.\n";
