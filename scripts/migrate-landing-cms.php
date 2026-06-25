<?php
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

$sql = file_get_contents(dirname(__DIR__) . '/database/patch_landing_cms.sql');
$pdo->exec($sql);

$count = (int)$pdo->query("SELECT COUNT(*) FROM cms_content WHERE section IN ('hero','features','impact','cta','footer')")->fetchColumn();
echo "Landing CMS patch applied ({$count} keys).\n";
