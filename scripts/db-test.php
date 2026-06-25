<?php
require __DIR__ . '/../app/Core/Env.php';
\App\Core\Env::load(__DIR__ . '/../.env');
require __DIR__ . '/../app/Core/Database.php';

$db = \App\Core\Database::getInstance()->getConnection();
echo 'OK: connected to ' . $db->query('SELECT DATABASE()')->fetchColumn() . PHP_EOL;
