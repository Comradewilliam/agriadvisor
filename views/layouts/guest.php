<!DOCTYPE html>
<html class="light" lang="sw">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Kuingia - Agri-Advisory</title>
<?php require dirname(__DIR__) . '/partials/head_assets.php'; ?>
<style>
    .glass-card { background:rgba(255,255,255,0.9); backdrop-filter:blur(12px); border:1px solid rgba(114,121,110,0.15); }
</style>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary/5 via-surface to-primary/10 relative">
    <div id="toast-root" class="toast-root" aria-live="polite"></div>
    <?php echo $content; ?>
</body>
</html>
