<?php
$totalEvents = (int)($totalAll ?? 0);
$todayEvents = (int)($todayCount ?? 0);
$errors = (int)($errorCount ?? 0);
$ussdToday = (int)($ussdToday ?? 0);
$smsToday = (int)($smsToday ?? 0);

function eventLevelBadge(string $level): array {
    return match ($level) {
        'error'   => ['Error', 'bg-error-container text-error', 'error'],
        'warning' => ['Warning', 'bg-tertiary-fixed text-tertiary', 'warning'],
        default   => ['Info', 'bg-primary-container text-primary', 'info'],
    };
}

function eventCategoryIcon(string $cat): string {
    return match ($cat) {
        'ussd'     => 'dialpad',
        'sms'      => 'sms',
        'ai'       => 'psychology',
        'delivery' => 'local_shipping',
        'alert'    => 'notifications',
        default    => 'event',
    };
}
?>

<div class="p-6 md:p-8 max-w-6xl mx-auto">
  <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
      <h2 class="text-3xl font-bold text-on-surface">Channel Analytics</h2>
      <p class="text-on-surface-variant mt-1">USSD, SMS, AI, and delivery events — same data as <code class="text-xs bg-surface-container px-1 rounded">storage/logs/app.log</code>, structured for reporting.</p>
    </div>
    <?php if (!empty($tableReady)): ?>
    <div class="flex gap-3">
      <a href="/admin/channel_analytics/export?format=pdf&days=<?php echo (int)$days; ?>" class="flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-xl text-sm font-bold hover:bg-primary-container transition-colors shadow-sm">
        <span class="material-symbols-outlined text-base">picture_as_pdf</span> Export PDF
      </a>
      <a href="/admin/channel_analytics/export?format=csv&days=<?php echo (int)$days; ?>" class="flex items-center gap-2 border border-outline-variant bg-white text-on-surface px-4 py-2 rounded-xl text-sm font-bold hover:bg-surface-container transition-colors shadow-sm">
        <span class="material-symbols-outlined text-base">download</span> Export CSV
      </a>
    </div>
    <?php endif; ?>
  </div>

  <?php if (empty($tableReady)): ?>
    <div class="bg-error-container/30 border border-error/30 rounded-2xl p-6 text-error">
      <p class="font-bold">Analytics table not ready</p>
      <p class="text-sm mt-1">Run the migration: <code>php scripts/patch-system-events.php</code> (or apply <code>database/patch_system_events.sql</code> in MySQL).</p>
    </div>
  <?php else: ?>

  <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-surface-container-low rounded-2xl border border-outline-variant p-4">
      <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-0.5">All events</p>
      <p class="text-2xl font-extrabold text-on-surface"><?php echo number_format($totalEvents); ?></p>
    </div>
    <div class="bg-surface-container-low rounded-2xl border border-outline-variant p-4">
      <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-0.5">Today</p>
      <p class="text-2xl font-extrabold text-primary"><?php echo number_format($todayEvents); ?></p>
    </div>
    <div class="bg-surface-container-low rounded-2xl border border-outline-variant p-4">
      <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-0.5">Errors (<?php echo (int)$days; ?>d)</p>
      <p class="text-2xl font-extrabold text-error"><?php echo number_format($errors); ?></p>
    </div>
    <div class="bg-surface-container-low rounded-2xl border border-outline-variant p-4">
      <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-0.5">USSD today</p>
      <p class="text-2xl font-extrabold text-secondary"><?php echo number_format($ussdToday); ?></p>
    </div>
    <div class="bg-surface-container-low rounded-2xl border border-outline-variant p-4">
      <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-0.5">SMS today</p>
      <p class="text-2xl font-extrabold text-tertiary"><?php echo number_format($smsToday); ?></p>
    </div>
  </div>

  <?php if (!empty($categoryBreakdown)): ?>
  <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="bg-white rounded-2xl border border-outline-variant p-4">
      <p class="text-xs font-bold text-on-surface-variant uppercase mb-2">By category (<?php echo (int)$days; ?> days)</p>
      <div class="flex flex-wrap gap-2">
        <?php foreach ($categoryBreakdown as $cb): ?>
          <span class="text-sm bg-surface-container px-3 py-1 rounded-full flex items-center gap-1">
            <span class="material-symbols-outlined text-sm"><?php echo eventCategoryIcon($cb['category']); ?></span>
            <strong><?php echo htmlspecialchars($cb['category']); ?></strong>: <?php echo (int)$cb['cnt']; ?>
          </span>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="bg-white rounded-2xl border border-outline-variant p-4">
      <p class="text-xs font-bold text-on-surface-variant uppercase mb-2">Top events</p>
      <div class="space-y-1">
        <?php foreach ($eventBreakdown as $eb): ?>
          <div class="flex justify-between text-sm">
            <span class="text-on-surface-variant"><?php echo htmlspecialchars($eb['category'] . '.' . $eb['event']); ?></span>
            <strong><?php echo (int)$eb['cnt']; ?></strong>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if (!empty($dailyStats)): ?>
  <div class="mb-6 bg-white rounded-2xl border border-outline-variant p-4">
    <p class="text-xs font-bold text-on-surface-variant uppercase mb-3">Daily activity</p>
    <?php
    $byDay = [];
    foreach ($dailyStats as $row) {
        $byDay[$row['day']][$row['channel'] ?? 'other'] = (int)$row['cnt'];
    }
    $maxDay = 1;
    foreach ($byDay as $channels) {
        $maxDay = max($maxDay, array_sum($channels));
    }
    ?>
    <div class="flex items-end gap-1 h-24 overflow-x-auto">
      <?php foreach ($byDay as $day => $channels): ?>
        <?php $total = array_sum($channels); $h = max(4, round(($total / $maxDay) * 96)); ?>
        <div class="flex flex-col items-center min-w-[20px]" title="<?php echo $day; ?>: <?php echo $total; ?>">
          <div class="w-3 bg-primary rounded-t" style="height:<?php echo $h; ?>px"></div>
          <span class="text-[8px] text-outline mt-1"><?php echo substr($day, 8); ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <form method="GET" action="/admin/channel_analytics" class="mb-4 flex flex-wrap gap-3 items-center bg-white rounded-2xl border border-outline-variant p-4">
    <div class="flex items-center gap-2 flex-1 min-w-[180px]">
      <span class="material-symbols-outlined text-on-surface-variant">search</span>
      <input type="text" name="q" value="<?php echo htmlspecialchars($search ?? ''); ?>" placeholder="Phone, message, event…" class="bg-transparent outline-none text-sm flex-1">
    </div>
    <select name="category" class="border border-outline-variant rounded-xl px-3 py-2 text-sm">
      <option value="">All categories</option>
      <?php foreach ($categories ?? [] as $cat): ?>
        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($category ?? '') === $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
      <?php endforeach; ?>
    </select>
    <select name="channel" class="border border-outline-variant rounded-xl px-3 py-2 text-sm">
      <option value="">All channels</option>
      <?php foreach (['ussd', 'sms', 'web'] as $ch): ?>
        <option value="<?php echo $ch; ?>" <?php echo ($channel ?? '') === $ch ? 'selected' : ''; ?>><?php echo strtoupper($ch); ?></option>
      <?php endforeach; ?>
    </select>
    <select name="level" class="border border-outline-variant rounded-xl px-3 py-2 text-sm">
      <option value="">All levels</option>
      <?php foreach (['info', 'warning', 'error'] as $lv): ?>
        <option value="<?php echo $lv; ?>" <?php echo ($level ?? '') === $lv ? 'selected' : ''; ?>><?php echo ucfirst($lv); ?></option>
      <?php endforeach; ?>
    </select>
    <select name="days" class="border border-outline-variant rounded-xl px-3 py-2 text-sm">
      <?php foreach ([7, 14, 30, 60, 90] as $d): ?>
        <option value="<?php echo $d; ?>" <?php echo (int)($days ?? 30) === $d ? 'selected' : ''; ?>><?php echo $d; ?> days</option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="bg-primary text-white px-4 py-2 rounded-xl text-sm font-bold">Filter</button>
    <?php if (!empty($search) || !empty($category) || !empty($level) || !empty($channel)): ?>
      <a href="/admin/channel_analytics" class="text-sm font-bold text-primary">Clear</a>
    <?php endif; ?>
  </form>

  <div class="bg-white rounded-2xl shadow-sm border border-outline-variant overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-surface-container-low text-xs text-on-surface-variant uppercase border-b border-outline-variant">
          <tr>
            <th class="p-4 text-left font-bold">Time</th>
            <th class="p-4 text-left font-bold">Event</th>
            <th class="p-4 text-left font-bold">Farmer</th>
            <th class="p-4 text-left font-bold">Channel</th>
            <th class="p-4 text-left font-bold">Message</th>
            <th class="p-4 text-center font-bold">Meta</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-outline-variant">
          <?php if (empty($events)): ?>
            <tr><td colspan="6" class="p-8 text-center text-on-surface-variant">No events yet. USSD/SMS activity will appear here automatically.</td></tr>
          <?php else: foreach ($events as $e):
            [$lvlLabel, $lvlClass, $lvlIcon] = eventLevelBadge($e['level'] ?? 'info');
            $metaRaw = $e['meta'] ?? '{}';
            if (is_array($metaRaw)) $metaRaw = json_encode($metaRaw);
          ?>
            <tr class="hover:bg-surface-container-lowest transition-colors">
              <td class="p-4 whitespace-nowrap">
                <p class="text-on-surface font-medium"><?php echo date('M d, Y', strtotime($e['created_at'])); ?></p>
                <p class="text-xs text-outline"><?php echo date('H:i:s', strtotime($e['created_at'])); ?></p>
              </td>
              <td class="p-4">
                <div class="flex items-center gap-2">
                  <span class="material-symbols-outlined text-base text-on-surface-variant"><?php echo eventCategoryIcon($e['category']); ?></span>
                  <div>
                    <p class="font-bold text-on-surface text-xs"><?php echo htmlspecialchars($e['category'] . '.' . $e['event']); ?></p>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full <?php echo $lvlClass; ?>"><?php echo $lvlLabel; ?></span>
                  </div>
                </div>
              </td>
              <td class="p-4">
                <?php if (!empty($e['farmer_id'])): ?>
                  <p class="font-medium text-on-surface"><?php echo htmlspecialchars($e['farmer_name'] ?? ('#' . $e['farmer_id'])); ?></p>
                  <p class="text-xs text-outline"><?php echo htmlspecialchars($e['phone'] ?? ''); ?></p>
                <?php else: ?>
                  <p class="text-xs text-on-surface-variant"><?php echo htmlspecialchars($e['phone'] ?? '—'); ?></p>
                <?php endif; ?>
              </td>
              <td class="p-4">
                <span class="text-[10px] font-extrabold uppercase px-2 py-1 rounded-md bg-surface-container"><?php echo htmlspecialchars($e['channel'] ?? '—'); ?></span>
              </td>
              <td class="p-4 text-on-surface-variant text-xs max-w-xs truncate" title="<?php echo htmlspecialchars($e['message'] ?? ''); ?>">
                <?php echo htmlspecialchars(mb_substr($e['message'] ?? '—', 0, 100)); ?>
              </td>
              <td class="p-4 text-center">
                <?php if ($metaRaw && $metaRaw !== '{}' && $metaRaw !== 'null'): ?>
                <button type="button" onclick="showEventMeta(this)" data-meta="<?php echo htmlspecialchars($metaRaw, ENT_QUOTES); ?>"
                        class="w-8 h-8 rounded-lg text-on-surface-variant hover:bg-surface-container hover:text-primary transition-colors flex items-center justify-center mx-auto">
                  <span class="material-symbols-outlined text-base">code</span>
                </button>
                <?php else: ?>—<?php endif; ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>

<div id="eventMetaModal" class="hidden modal-backdrop">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg modal-panel p-6">
    <div class="flex justify-between items-center mb-4">
      <h4 class="font-bold text-lg">Event details</h4>
      <button type="button" onclick="document.getElementById('eventMetaModal').classList.add('hidden')"><span class="material-symbols-outlined">close</span></button>
    </div>
    <pre id="eventMetaBody" class="text-xs bg-surface-container p-4 rounded-xl overflow-x-auto whitespace-pre-wrap"></pre>
  </div>
</div>

<script>
function showEventMeta(btn) {
  const raw = btn.getAttribute('data-meta') || '{}';
  let formatted = raw;
  try { formatted = JSON.stringify(JSON.parse(raw), null, 2); } catch (e) {}
  document.getElementById('eventMetaBody').textContent = formatted;
  document.getElementById('eventMetaModal').classList.remove('hidden');
}
</script>
