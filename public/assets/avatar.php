<?php
declare(strict_types=1);

$name = trim($_GET['name'] ?? 'User');
$bg = preg_replace('/[^0-9a-fA-F]/', '', $_GET['bg'] ?? '154212') ?: '154212';
$size = max(16, min(256, (int)($_GET['size'] ?? 80)));

$parts = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY);
if (count($parts) >= 2) {
    $initials = mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
} elseif (count($parts) === 1) {
    $initials = mb_strtoupper(mb_substr($parts[0], 0, 2));
} else {
    $initials = '?';
}

$fontSize = (int)round($size * 0.38);
$escName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$escInitials = htmlspecialchars($initials, ENT_QUOTES, 'UTF-8');

header('Content-Type: image/svg+xml');
header('Cache-Control: public, max-age=86400');

echo <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$size}" height="{$size}" viewBox="0 0 {$size} {$size}" role="img" aria-label="{$escName}">
  <rect width="{$size}" height="{$size}" fill="#{$bg}" rx="{$size}"/>
  <text x="50%" y="50%" dominant-baseline="central" text-anchor="middle"
        fill="#ffffff" font-family="Inter, system-ui, sans-serif" font-size="{$fontSize}" font-weight="700">{$escInitials}</text>
</svg>
SVG;
