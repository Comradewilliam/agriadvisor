<?php
/**
 * views/layouts/app.php  –  Main application shell
 * v1.4: Language switching (WAO/DAO), removed Profile tab, multi-ward session support
 */

// ── Bootstrap Lang ──────────────────────────────────────────────────────────
require_once dirname(__DIR__, 2) . '/app/Helpers/Lang.php';
require_once dirname(__DIR__, 2) . '/app/Helpers/Avatar.php';
\App\Helpers\Lang::init();

$role   = $_SESSION['role']   ?? '';
$uri    = $_SERVER['REQUEST_URI'] ?? '/';
$lang   = \App\Helpers\Lang::getLocale();
$isFarmerChat = str_starts_with(parse_url($uri, PHP_URL_PATH) ?: '', '/farmer/chat');
$name   = $role === 'farmer'
        ? ($_SESSION['farmer_name'] ?? 'Mkulima')
        : ($_SESSION['officer_name'] ?? 'Afisa');

// Roles that get the language switcher
$langSwitchRoles = ['ward_officer', 'dao'];

function navLink(string $href, string $icon, string $label, string $currentUri): string {
    $active = rtrim($currentUri, '/') === rtrim($href, '/') || ($href !== '/' && str_starts_with($currentUri, $href));
    $cls = $active ? 'nav-link active' : 'nav-link';
    return "<a href=\"{$href}\" class=\"{$cls}\"><span class=\"material-symbols-outlined\">{$icon}</span><span>{$label}</span></a>";
}
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo htmlspecialchars($lang); ?>">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Agri-Advisory Portal</title>
<?php require dirname(__DIR__) . '/partials/head_assets.php'; ?>
</head>
<body class="bg-surface text-on-surface flex min-h-screen">

<!-- Sidebar -->
<aside data-mobile-sidebar class="hidden md:flex flex-col h-full w-64 fixed left-0 top-0 bg-surface-container-low border-r border-outline-variant p-4 gap-1 z-50 overflow-y-auto">
    <div class="mb-6 px-2">
        <h1 class="text-xl font-extrabold text-primary">Agri-Advisory System</h1>
        <p class="text-xs text-on-surface-variant mt-1"><?php echo htmlspecialchars($name); ?></p>
    </div>

    <nav class="flex-1 space-y-0.5">
        <?php if ($role === 'farmer'): ?>
            <?php echo navLink('/farmer/dashboard', 'dashboard', 'Nyumbani', $uri); ?>
            <?php echo navLink('/farmer/chat', 'forum', 'Maongezi ya AI', $uri); ?>
            <?php echo navLink('/farmer/crops', 'grass', 'Mazao Yangu', $uri); ?>
            <?php echo navLink('/farmer/weather', 'thunderstorm', 'Hali ya Hewa', $uri); ?>
            <?php echo navLink('/farmer/visits', 'event', 'Omba Ziara', $uri); ?>
        <?php elseif ($role === 'ward_officer'): ?>
            <?php echo navLink('/officer/dashboard',   'dashboard',                __('nav_dashboard'),   $uri); ?>
            <?php echo navLink('/officer/farmers',     'group',                    __('nav_farmers'),     $uri); ?>
            <?php echo navLink('/officer/broadcasts',  'campaign',                 __('nav_broadcasts'),  $uri); ?>
            <?php echo navLink('/officer/relocation',  'transfer_within_a_station',__('nav_relocation'),  $uri); ?>
            <?php echo navLink('/officer/escalations', 'chat_error',               __('nav_escalations'), $uri); ?>
            <?php echo navLink('/officer/visits',      'calendar_month',           __('nav_visits'),      $uri); ?>
            <?php echo navLink('/officer/weather',     'thunderstorm',             __('nav_weather'),     $uri); ?>
            <?php echo navLink('/officer/ai-mentorship','psychology',              __('nav_knowledge'),   $uri); ?>
            <?php echo navLink('/officer/analytics',   'analytics',                __('nav_analytics'),   $uri); ?>
            <!-- Profile tab removed: managed by DAO -->
        <?php elseif ($role === 'dao'): ?>
            <?php echo navLink('/officer/dashboard',   'dashboard',        __('nav_dashboard'),     $uri); ?>
            <?php echo navLink('/officer/officers',    'manage_accounts',  __('nav_officers'),      $uri); ?>
            <?php echo navLink('/officer/farmers',     'group',            __('nav_dist_farmers'),  $uri); ?>
            <?php echo navLink('/officer/broadcasts',  'campaign',         __('nav_broadcasts'),    $uri); ?>
            <?php echo navLink('/officer/escalations', 'chat_error',       __('nav_escalations'),   $uri); ?>
            <?php echo navLink('/officer/weather',     'thunderstorm',     __('nav_weather'),       $uri); ?>
            <?php echo navLink('/officer/visits',      'calendar_month',   __('nav_visits'),        $uri); ?>
            <?php echo navLink('/officer/automated-alerts', 'notifications_active', __('nav_auto_alerts'), $uri); ?>
            <?php echo navLink('/officer/ai-mentorship','psychology',      __('nav_knowledge'),     $uri); ?>
            <?php echo navLink('/officer/analytics',   'analytics',        __('nav_analytics'),     $uri); ?>
            <!-- Profile tab removed: DAO manages WAO profiles, not own -->
        <?php elseif ($role === 'super_admin'): ?>
            <?php echo navLink('/admin/dashboard',   'speed',           'System Overview',    $uri); ?>
            <?php echo navLink('/admin/districts',   'map',             'Districts & Wards',  $uri); ?>
            <?php echo navLink('/admin/users',       'manage_accounts', 'Officer Management', $uri); ?>
            <?php echo navLink('/admin/landing-page','web',             'Landing Page CMS',   $uri); ?>
            <?php echo navLink('/admin/channel_analytics', 'analytics', 'Channel Analytics', $uri); ?>
            <?php echo navLink('/admin/audit_logs',  'history',         'Audit Logs',         $uri); ?>
        <?php endif; ?>
    </nav>

    <div class="border-t border-outline-variant pt-3">
        <?php $logoutLink = $role === 'farmer' ? '/farmer/logout' : '/logout'; ?>
        <a href="<?php echo $logoutLink; ?>" class="nav-link text-error hover:bg-error-container/30">
            <span class="material-symbols-outlined">logout</span>
            <span><?php echo __('logout'); ?></span>
        </a>
    </div>
</aside>

<!-- Main -->
<main class="flex-1 md:ml-64 w-full min-w-0 flex flex-col <?php echo $isFarmerChat ? 'h-screen overflow-hidden pb-0' : 'min-h-screen pb-8'; ?>">
    <!-- Top Bar -->
    <header class="flex justify-between items-center px-6 py-3 sticky top-0 z-40 bg-surface/90 backdrop-blur-sm shadow-sm border-b border-outline-variant">
    <!-- Mobile hamburger -->
        <button type="button" class="md:hidden text-on-surface-variant" data-mobile-nav-toggle aria-label="Menu">
            <span class="material-symbols-outlined">menu</span>
        </button>
        <h2 class="text-lg font-extrabold text-primary hidden md:block">Agri-Advisor System</h2>

        <div class="flex items-center gap-3 ml-auto">

            <?php if (in_array($role, $langSwitchRoles, true)): ?>
            <!-- Language Switcher (WAO & DAO only) -->
            <div class="flex items-center gap-1 bg-surface-container rounded-xl px-2 py-1 border border-outline-variant">
                <span class="material-symbols-outlined text-sm text-on-surface-variant mr-1">language</span>
                <button id="btnEN" onclick="switchLang('en')"
                        class="lang-btn <?php echo $lang === 'en' ? 'active-lang' : ''; ?>">EN</button>
                <span class="text-outline-variant text-xs">|</span>
                <button id="btnSW" onclick="switchLang('sw')"
                        class="lang-btn <?php echo $lang === 'sw' ? 'active-lang' : ''; ?>">SW</button>
            </div>
            <?php endif; ?>

            <!-- User chip → profile -->
            <?php
            $profileUrl = match ($role) {
                'farmer' => '/farmer/profile',
                'ward_officer', 'dao' => '/officer/profile',
                'super_admin' => '/admin/dashboard',
                default => null,
            };
            ?>
            <?php if ($profileUrl): ?>
            <a href="<?php echo htmlspecialchars($profileUrl); ?>" class="flex items-center gap-2 bg-surface-container rounded-full px-3 py-1 hover:bg-surface-container-high transition-colors" title="My profile">
                <?php if ($role === 'farmer'): ?>
                <span class="w-8 h-8 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center shrink-0" aria-hidden="true"><?php echo htmlspecialchars(\App\Helpers\Avatar::initials($name)); ?></span>
                <?php else: ?>
                <span class="w-8 h-8 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center shrink-0" aria-hidden="true"><?php echo htmlspecialchars(\App\Helpers\Avatar::initials($name)); ?></span>
                <?php endif; ?>
                <span class="text-sm font-medium text-on-surface hidden sm:block"><?php echo htmlspecialchars($name); ?></span>
            </a>
            <?php else: ?>
            <div class="flex items-center gap-2 bg-surface-container rounded-full px-3 py-1">
                <span class="material-symbols-outlined text-primary">account_circle</span>
                <span class="text-sm font-medium text-on-surface hidden sm:block"><?php echo htmlspecialchars($name); ?></span>
            </div>
            <?php endif; ?>

        </div>
    </header>

    <!-- Page content -->
    <?php echo $content; ?>
</main>

<script>
function switchLang(locale) {
    fetch('/set-lang', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'lang=' + locale
    }).then(() => window.location.reload());
}
</script>

</body>
</html>
