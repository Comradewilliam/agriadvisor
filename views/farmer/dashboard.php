<?php
function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return "{$diff}s ago";
    if ($diff < 3600) return floor($diff/60) . "m ago";
    if ($diff < 86400) return floor($diff/3600) . "h ago";
    return date('d M', strtotime($datetime));
}

$cropProgress = $cropProgress ?? ['crop_name' => 'Mahindi', 'stage_label' => 'Ukuaji', 'progress_pct' => 75];
$cropName    = $cropProgress['crop_name'] ?? 'Mahindi';
$stageLabel  = $cropProgress['stage_label'] ?? 'Ukuaji';
$progressPct = $cropProgress['progress_pct'] ?? 75;

$greetings = [
    "Mkulima bora ni yule anayelisha dunia kwa moyo mweupe.",
    "Mavuno mazuri yanaanza na mbegu nzuri na juhudi.",
    "Ardhi ni hazina — itunze vizuri.",
];
$greeting = $greetings[date('j') % count($greetings)];
?>

<div class="flex flex-col min-h-screen">

  <!-- Hero Banner -->
  <div class="bg-primary text-white px-6 md:px-10 py-8 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10 dashboard-hero-pattern" aria-hidden="true"></div>
    <div class="relative max-w-[1400px] mx-auto w-full">
      <?php if (isset($_SESSION['flash'])): ?>
        <div class="mb-4 bg-white/20 text-white rounded-xl px-5 py-2 font-semibold text-sm">
          <?php echo htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?>
        </div>
      <?php endif; ?>
      <p class="text-white/70 text-sm font-medium mb-1">Karibu tena,</p>
      <h2 class="text-3xl md:text-4xl font-extrabold mb-2"><?php echo htmlspecialchars($farmer['name']); ?>!</h2>
      <p class="text-white/80 italic text-sm max-w-lg">"<?php echo $greeting; ?>"</p>
    </div>
  </div>

  <div class="flex-1 p-6 md:p-8 w-full max-w-[1400px] mx-auto">

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

      <!-- Crop Status Card -->
      <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-outline-variant overflow-hidden">
        <div class="flex">
          <div class="w-36 shrink-0 hidden sm:block relative bg-surface-container-highest">
            <img src="/assets/images/hero1.png"
                 alt="<?php echo htmlspecialchars($cropName); ?>"
                 class="w-full h-full object-cover" onerror="this.style.display='none'">
          </div>
          <div class="flex-1 p-5">
            <div class="flex items-start justify-between mb-3">
              <div>
                <p class="text-xs text-on-surface-variant uppercase tracking-wider mb-1">Zao Lako</p>
                <h3 class="text-xl font-extrabold text-on-surface">Hali ya <?php echo htmlspecialchars($cropName); ?></h3>
              </div>
              <span class="bg-tertiary-fixed text-tertiary text-xs font-bold px-3 py-1 rounded-full">Hatua: <?php echo htmlspecialchars($stageLabel); ?></span>
            </div>
            <!-- Progress bar -->
            <div class="w-full bg-surface-container rounded-full h-2 mb-2">
              <div class="bg-primary h-2 rounded-full transition-all duration-700" style="width:<?php echo (int)$progressPct; ?>%"></div>
            </div>
            <div class="flex justify-between text-xs text-on-surface-variant mb-4">
              <span>Maendeleo: <?php echo (int)$progressPct; ?>%</span>
              <span>Mavuno: <?php echo date('d M Y', strtotime('+45 days')); ?></span>
            </div>
            <div class="flex items-start gap-2 text-sm text-on-surface-variant">
              <span class="material-symbols-outlined text-base text-primary shrink-0">eco</span>
              <span>Mazao yako yanaendelea vizuri sana msimu huu kwenye hatua ya <strong><?php echo htmlspecialchars($stageLabel); ?></strong>. Endelea kufuata ratiba ya mbolea.</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Weather Card -->
      <div class="bg-tertiary text-white rounded-2xl shadow-sm p-5 flex flex-col justify-between">
        <div>
          <p class="text-white/70 text-xs uppercase tracking-wider mb-1">Hali ya Hewa</p>
          <h3 class="text-lg font-bold"><?php echo htmlspecialchars($farmer['village_name'] ?? $farmer['ward_name'] ?? 'Kata Yako'); ?></h3>
        </div>
        <?php if (!empty($weather['temp'])): ?>
          <div class="my-3">
            <span class="material-symbols-outlined text-5xl">wb_cloudy</span>
            <p class="text-5xl font-extrabold mt-1"><?php echo round($weather['temp']); ?>°<span class="text-2xl">C</span></p>
            <p class="text-white/80 text-sm mt-1"><?php echo htmlspecialchars($weather['description'] ?? ''); ?> &bull; Unyevu: <?php echo $weather['humidity'] ?? '--'; ?>%</p>
          </div>
          <div class="bg-white/20 rounded-xl p-3 text-sm">
            <span class="material-symbols-outlined text-base align-middle mr-1">info</span>
            Angalia hali ya hewa kabla ya kupanda mbolea au dawa.
          </div>
        <?php else: ?>
          <div class="my-3">
            <span class="material-symbols-outlined text-5xl">wb_sunny</span>
            <p class="text-5xl font-extrabold mt-1">28°<span class="text-2xl">C</span></p>
            <p class="text-white/80 text-sm mt-1">Mawingu kiasi &bull; Unyevu: 65%</p>
          </div>
          <div class="bg-white/20 rounded-xl p-3 text-sm">
            <span class="material-symbols-outlined text-base align-middle mr-1">water_drop</span>
            Utabiri: Mvua nyepesi inatarajiwa jioni ya leo. Andaa mifereji.
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Quick Actions + Today's Tip -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

      <!-- Quick Actions -->
      <div class="space-y-3">
        <a href="/farmer/chat" class="flex items-center gap-4 bg-white border border-outline-variant rounded-2xl p-4 hover:border-primary hover:shadow-md transition-all group">
          <div class="w-12 h-12 rounded-xl bg-primary-fixed flex items-center justify-center shrink-0 group-hover:bg-primary transition-colors">
            <span class="material-symbols-outlined text-primary group-hover:text-white transition-colors">forum</span>
          </div>
          <div class="flex-1">
            <p class="font-bold text-on-surface text-sm">Uliza BwanaShamba</p>
            <p class="text-xs text-on-surface-variant">Msaada wa AI saa 24</p>
          </div>
          <span class="material-symbols-outlined text-outline">chevron_right</span>
        </a>
        <a href="/farmer/visits" class="flex items-center gap-4 bg-white border border-outline-variant rounded-2xl p-4 hover:border-secondary hover:shadow-md transition-all group">
          <div class="w-12 h-12 rounded-xl bg-secondary-fixed flex items-center justify-center shrink-0 group-hover:bg-secondary transition-colors">
            <span class="material-symbols-outlined text-secondary group-hover:text-white transition-colors">calendar_month</span>
          </div>
          <div class="flex-1">
            <p class="font-bold text-on-surface text-sm">Omba Ziara</p>
            <p class="text-xs text-on-surface-variant">Tembelewa na mtaalamu</p>
          </div>
          <span class="material-symbols-outlined text-outline">chevron_right</span>
        </a>
        <a href="/farmer/crops" class="flex items-center gap-4 bg-white border border-outline-variant rounded-2xl p-4 hover:border-tertiary hover:shadow-md transition-all group">
          <div class="w-12 h-12 rounded-xl bg-tertiary-fixed flex items-center justify-center shrink-0 group-hover:bg-tertiary transition-colors">
            <span class="material-symbols-outlined text-tertiary group-hover:text-white transition-colors">grass</span>
          </div>
          <div class="flex-1">
            <p class="font-bold text-on-surface text-sm">Mazao Yangu</p>
            <p class="text-xs text-on-surface-variant">Fuatilia maendeleo</p>
          </div>
          <span class="material-symbols-outlined text-outline">chevron_right</span>
        </a>
      </div>

      <!-- Today's Tip -->
      <div class="lg:col-span-2 bg-secondary-fixed rounded-2xl p-6 border border-secondary-fixed-dim">
        <div class="flex items-start gap-4">
          <div class="w-14 h-14 rounded-2xl bg-white flex items-center justify-center shrink-0 shadow-sm">
            <span class="material-symbols-outlined text-secondary text-3xl">lightbulb</span>
          </div>
          <div class="flex-1">
            <p class="text-xs text-secondary font-bold uppercase tracking-wider mb-2">Ushauri wa Leo</p>
            <p class="text-on-surface font-medium leading-relaxed">
              "Unapopalilia mahindi, hakikisha hauchimbui mizizi kwa undani sana kwani hii inaweza kupunguza uwezo wa mmea kufyonza maji. Palilia ukiwa na sentimita 3–5 tu kutoka kwenye shina."
            </p>
            <a href="/farmer/chat" class="inline-flex items-center gap-1 text-secondary font-bold text-sm mt-3 hover:underline">
              Soma zaidi <span class="material-symbols-outlined text-base">arrow_forward</span>
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Upcoming Visits + Recent Messages -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

      <!-- Upcoming Visits & Assigned Officer -->
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant overflow-hidden flex flex-col">
        <div class="p-5 border-b border-outline-variant flex justify-between items-center">
          <h4 class="font-bold text-on-surface flex items-center gap-2">
            <span class="material-symbols-outlined text-tertiary">event</span> Afisa na Ziara Zilizopangwa
          </h4>
        </div>
        <?php if (!empty($assignedOfficer)): ?>
        <div class="bg-surface-container-low p-4 border-b border-outline-variant flex items-center gap-4">
          <div class="w-12 h-12 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined">badge</span>
          </div>
          <div>
            <p class="text-xs text-on-surface-variant uppercase tracking-wider mb-0.5">Afisa Wako Mteule</p>
            <p class="font-bold text-on-surface text-sm"><?php echo htmlspecialchars($assignedOfficer['name']); ?></p>
            <p class="text-xs text-on-surface-variant flex items-center gap-1 mt-0.5"><span class="material-symbols-outlined text-[14px]">call</span> <?php echo htmlspecialchars($assignedOfficer['phone']); ?></p>
          </div>
        </div>
        <?php endif; ?>
        <div class="p-4 space-y-3 flex-1">
          <?php if (empty($upcomingVisits)): ?>
            <div class="text-center py-8 text-outline">
              <span class="material-symbols-outlined text-4xl mb-2 block">event_busy</span>
              <p class="text-sm">Hakuna ziara zilizopangwa.</p>
            </div>
          <?php else: ?>
            <?php foreach ($upcomingVisits as $v): ?>
              <div class="flex items-center gap-4 p-3 bg-surface-container-low rounded-xl">
                <div class="w-12 h-12 rounded-xl bg-tertiary flex flex-col items-center justify-center text-white shrink-0">
                  <span class="text-xs font-bold leading-none"><?php echo strtoupper(date('M', strtotime($v['scheduled_at']))); ?></span>
                  <span class="text-xl font-extrabold leading-none"><?php echo date('j', strtotime($v['scheduled_at'])); ?></span>
                </div>
                <div>
                  <p class="font-bold text-on-surface text-sm"><?php echo htmlspecialchars($v['reason']); ?></p>
                  <p class="text-xs text-on-surface-variant"><?php echo date('H:i', strtotime($v['scheduled_at'])); ?> &bull; <?php echo htmlspecialchars($v['officer_name']); ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Recent Messages -->
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant overflow-hidden">
        <div class="p-5 border-b border-outline-variant flex justify-between items-center">
          <h4 class="font-bold text-on-surface flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">forum</span> Mazungumzo ya Hivi Karibuni
          </h4>
          <a href="/farmer/chat" class="text-primary text-sm font-bold hover:underline">Ona Yote</a>
        </div>
        <div class="p-4 space-y-2 max-h-56 overflow-y-auto">
          <?php if (empty($recentMessages)): ?>
            <div class="text-center py-8 text-outline">
              <span class="material-symbols-outlined text-4xl mb-2 block">chat_bubble_outline</span>
              <p class="text-sm">Hakuna ujumbe bado.</p>
            </div>
          <?php else: ?>
            <?php foreach (array_slice($recentMessages, -5) as $msg): ?>
              <div class="flex <?php echo $msg['direction'] === 'in' ? 'justify-end' : 'justify-start'; ?>">
                <div class="max-w-xs rounded-xl px-3 py-2 text-sm <?php echo $msg['direction'] === 'in' ? 'bg-primary text-white' : ($msg['source'] === 'officer' ? 'bg-amber-100 text-amber-900 border border-amber-200' : ($msg['source'] === 'officer_reply' ? 'bg-blue-100 text-blue-900 border border-blue-200' : 'bg-surface-container border border-outline-variant text-on-surface')); ?>">
                  <?php if ($msg['source'] === 'officer' || $msg['source'] === 'officer_reply'): ?>
                    <p class="text-[10px] uppercase font-bold mb-1 opacity-70 flex items-center gap-1">
                      <span class="material-symbols-outlined text-[12px]"><?php echo $msg['source'] === 'officer' ? 'campaign' : 'support_agent'; ?></span>
                      <?php echo $msg['source'] === 'officer' ? 'Broadcast' : 'Afisa'; ?>
                    </p>
                  <?php endif; ?>
                  <p class="leading-snug"><?php echo htmlspecialchars(mb_substr($msg['content'], 0, 100)) . (mb_strlen($msg['content']) > 100 ? '…' : ''); ?></p>
                  <p class="text-xs mt-1 opacity-60"><?php echo timeAgo($msg['sent_at']); ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div><!-- /max-w -->

  <!-- Footer -->
  <footer class="bg-on-surface text-inverse-on-surface mt-8 px-6 md:px-10 py-8">
    <div class="max-w-[1400px] mx-auto flex flex-col md:flex-row justify-between items-start gap-6">
      <div>
        <p class="font-extrabold text-lg">Agri-Advisory</p>
        <p class="text-sm text-white/60 mt-1">© <?php echo date('Y'); ?> Agri-Advisory. Haki zote zimehifadhiwa.</p>
      </div>
      <div class="flex gap-8 text-sm text-white/70">
        <div>
          <p class="font-bold text-white mb-2">Msaada</p>
          <a href="#" class="block hover:text-white">Msaada wa Haraka</a>
          <a href="#" class="block hover:text-white">Faragha</a>
        </div>
        <div>
          <p class="font-bold text-white mb-2">Mfumo</p>
          <a href="/farmer/chat" class="block hover:text-white">Uliza AI</a>
          <a href="/farmer/crops" class="block hover:text-white">Mazao Yangu</a>
        </div>
      </div>
    </div>
  </footer>

</div>
