<?php
require __DIR__ . '/../app/Core/Env.php';
\App\Core\Env::load(__DIR__ . '/../.env');

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', getenv('DB_HOST'), getenv('DB_PORT'), getenv('DB_NAME')),
    getenv('DB_USER'),
    getenv('DB_PASS'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$pdo->exec("ALTER TABLE ai_messages MODIFY COLUMN channel ENUM('sms', 'web', 'ussd') NOT NULL DEFAULT 'web'");
echo "ai_messages.channel updated to include ussd\n";
