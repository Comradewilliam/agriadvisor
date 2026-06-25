<?php
/**
 * Farmer detail — uses real data from controller ($farmer, $farmerVisits, $interactions, $weather, $cropProgress).
 */
$farmerId = (int)($farmer['id'] ?? 0);
$farmerName = htmlspecialchars($farmer['name'] ?? trim(($farmer['first_name'] ?? '') . ' ' . ($farmer['last_name'] ?? '')));
$primaryCrop = $cropProgress['crop_name'] ?? '—';
$farmSize = $farmer['farm_size_acres'] ?? null;
$plantedDate = $cropProgress['planted_date'] ?? null;
$progressPct = (int)($cropProgress['progress_pct'] ?? 0);
$stageName = $cropProgress['stage_name'] ?? '—';
$filterTab = $filterTab ?? 'all';
$isDao = ($_SESSION['role'] ?? '') === 'dao';

function interactionIcon(string $type): string {
    return match ($type) {
        'visit' => 'event',
        'chat' => 'forum',
        'broadcast' => 'campaign',
        'escalation' => 'support_agent',
        default => 'chat',
    };
}
?>

<div class="p-6 md:p-8 max-w-6xl mx-auto">

  <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
      <a href="/officer/farmers" class="inline-flex items-center gap-1 text-sm font-bold text-primary hover:underline mb-2">
        <span class="material-symbols-outlined text-base">arrow_back</span> Back to Directory
      </a>
      <h2 class="text-3xl font-bold text-on-surface">Farmer Profile</h2>
    </div>
    <div class="flex gap-3">
      <a href="/officer/visits?farmer_id=<?php echo $farmerId; ?>" class="flex items-center gap-2 border border-outline-variant text-on-surface font-bold px-4 py-2 rounded-xl hover:bg-surface-container transition-colors shadow-sm">
        <span class="material-symbols-outlined text-base">event</span> Schedule Visit
      </a>
      <?php if ($isDao): ?>
      <a href="/officer/farmers/edit?id=<?php echo $farmerId; ?>" class="flex items-center gap-2 bg-primary text-white font-bold px-4 py-2 rounded-xl hover:bg-primary-container transition-colors shadow-sm">
        <span class="material-symbols-outlined text-base">edit</span> Edit Details
      </a>
      <?php endif; ?>
    </div>
  </div>

  <?php if (isset($_GET['success'])): ?>
    <div class="mb-4 bg-primary-fixed text-primary rounded-xl px-5 py-3 font-bold">Profile updated successfully.</div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-1 space-y-6">
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant p-6 text-center">
        <div class="w-24 h-24 mx-auto rounded-2xl overflow-hidden bg-primary-fixed mb-4 shadow-sm">
          <img src="<?php echo \App\Helpers\Avatar::url($farmerName, '154212', 96); ?>" alt="Profile" class="w-full h-full object-cover">
        </div>
        <h3 class="text-xl font-extrabold text-on-surface mb-1"><?php echo $farmerName; ?></h3>
        <p class="text-sm text-on-surface-variant flex items-center justify-center gap-1 mb-4">
          <span class="material-symbols-outlined text-base">location_on</span>
          <?php echo htmlspecialchars($farmer['village_name'] ?? '—'); ?> &bull; <?php echo htmlspecialchars($farmer['ward_name'] ?? '—'); ?>
        </p>
        <div class="flex justify-center gap-2">
          <?php if (!empty($farmer['phone'])): ?>
          <a href="tel:<?php echo htmlspecialchars($farmer['phone']); ?>" class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center hover:bg-surface-container-high text-primary" title="Call">
            <span class="material-symbols-outlined">call</span>
          </a>
          <a href="sms:<?php echo htmlspecialchars($farmer['phone']); ?>" class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center hover:bg-surface-container-high text-secondary" title="SMS">
            <span class="material-symbols-outlined">sms</span>
          </a>
          <?php endif; ?>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant p-5 space-y-4">
        <div>
          <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Phone</p>
          <p class="font-medium text-on-surface"><?php echo htmlspecialchars($farmer['phone'] ?? '—'); ?></p>
        </div>
        <div>
          <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Member since</p>
          <p class="font-medium text-on-surface"><?php echo !empty($farmer['registered_at']) ? date('d M Y', strtotime($farmer['registered_at'])) : '—'; ?></p>
        </div>
        <div>
          <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Farm size</p>
          <p class="font-medium text-on-surface"><?php echo $farmSize !== null ? htmlspecialchars((string)$farmSize) . ' acres' : '—'; ?></p>
     
        </div>
        <div>
          <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Primary crop</p>
          <p class="font-medium text-on-surface"><?php echo htmlspecialchars($primaryCrop); ?></p>
        </div>
        <?php if (!empty($farmer['crops']) && count($farmer['crops']) > 1): ?>
        <div>
          <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Other crops</p>
          <p class="font-medium text-on-surface text-sm">
            <?php
            $others = array_filter($farmer['crops'], fn($c) => ($c['type'] ?? '') !== 'primary');
            echo htmlspecialchars(implode(', ', array_column($others, 'name')) ?: '—');
            ?>
          </p>
        </div>
        <?php endif; ?>
      </div>

      <?php if (!empty($weather['description'])): ?>
      <div class="bg-tertiary-fixed/30 text-on-surface rounded-2xl p-5 border border-tertiary-fixed-dim">
        <h4 class="font-bold mb-2 flex items-center gap-2"><span class="material-symbols-outlined text-tertiary">cloud</span> Local weather</h4>
        <p class="text-sm leading-relaxed capitalize">
          <?php echo htmlspecialchars($weather['temp'] ?? ''); ?><?php echo isset($weather['temp']) ? '°C' : ''; ?>
          <?php echo $weather['description'] ? ' — ' . htmlspecialchars($weather['description']) : ''; ?>
        </p>
      </div>
      <?php endif; ?>
    </div>

    <div class="lg:col-span-2 space-y-6">
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant p-6">
        <div class="flex items-center justify-between mb-5">
          <h3 class="font-bold text-on-surface">Current crop progress</h3>
          <span class="bg-primary-fixed text-primary text-xs font-bold px-3 py-1 rounded-full"><?php echo htmlspecialchars($cropProgress['season'] ?? 'Season'); ?></span>
        </div>
        <div class="flex flex-col md:flex-row items-center gap-6">
          <div class="w-full md:w-1/3 text-center md:text-left border-b md:border-b-0 md:border-r border-outline-variant pb-4 md:pb-0 md:pr-4">
            <p class="text-sm text-on-surface-variant mb-1">Primary crop</p>
            <p class="text-2xl font-extrabold text-on-surface mb-2"><?php echo htmlspecialchars($primaryCrop); ?></p>
            <p class="text-xs text-outline"><?php echo $plantedDate ? 'Planted: ' . date('d M Y', strtotime($plantedDate)) : 'Planting date not set'; ?></p>
          </div>
          <div class="flex-1 w-full">
            <p class="text-sm font-bold text-primary mb-2">Stage: <?php echo htmlspecialchars($stageName); ?></p>
            <div class="w-full bg-surface-container-highest rounded-full h-2.5 mb-2">
              <div class="bg-primary h-2.5 rounded-full relative" style="width: <?php echo max(5, min(100, $progressPct)); ?>%"></div>
            </div>
            <div class="flex justify-between text-xs text-on-surface-variant">
              <span>Planting</span>
              <span>Growth</span>
              <span>Harvest</span>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant overflow-hidden">
        <div class="p-5 border-b border-outline-variant flex flex-wrap items-center justify-between gap-3">
          <h3 class="font-bold text-on-surface">Interaction history</h3>
          <div class="flex gap-1 bg-surface-container-low rounded-lg p-1 border border-outline-variant">
            <?php foreach (['all' => 'All', 'visit' => 'Visits', 'chat' => 'AI Chats'] as $key => $label): ?>
            <a href="/officer/farmers/view?id=<?php echo $farmerId; ?>&amp;tab=<?php echo $key; ?>"
               class="text-xs font-bold px-4 py-1.5 rounded-md <?php echo $filterTab === $key ? 'bg-white shadow-sm text-on-surface' : 'text-on-surface-variant hover:text-on-surface'; ?>">
              <?php echo $label; ?>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="divide-y divide-outline-variant">
          <?php
          $shown = array_filter($interactions, function ($i) use ($filterTab) {
              if ($filterTab === 'all') return true;
              if ($filterTab === 'visit') return $i['type'] === 'visit';
              if ($filterTab === 'chat') return $i['type'] === 'chat';
              return true;
          });
          if (empty($shown)): ?>
            <div class="p-8 text-center text-on-surface-variant text-sm">No interactions yet for this filter.</div>
          <?php else: foreach ($shown as $i): ?>
            <div class="p-5 flex items-start gap-4 hover:bg-surface-container-lowest transition-colors">
              <div class="w-10 h-10 rounded-xl <?php echo $i['type'] === 'visit' ? 'bg-tertiary-fixed text-tertiary' : 'bg-primary-fixed text-primary'; ?> flex items-center justify-center shrink-0 mt-1">
                <span class="material-symbols-outlined text-xl"><?php echo interactionIcon($i['type']); ?></span>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2 mb-1">
                  <p class="font-bold text-on-surface text-sm"><?php echo htmlspecialchars($i['title']); ?></p>
                  <span class="text-xs font-bold px-2 py-0.5 rounded-full shrink-0 <?php echo htmlspecialchars($i['badge_class'] ?? 'bg-surface-container text-outline'); ?>">
                    <?php echo htmlspecialchars($i['status']); ?>
                  </span>
                </div>
                <p class="text-xs text-outline mb-2"><?php echo htmlspecialchars($i['date_label']); ?></p>
                <p class="text-sm text-on-surface-variant leading-relaxed"><?php echo nl2br(htmlspecialchars($i['desc'])); ?></p>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <?php if (!empty($farmerVisits)): ?>
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant overflow-hidden">
        <div class="p-5 border-b border-outline-variant"><h3 class="font-bold text-on-surface">Scheduled visits</h3></div>
        <div class="divide-y divide-outline-variant">
          <?php foreach ($farmerVisits as $v): ?>
          <div class="p-4 flex justify-between items-center text-sm">
            <div>
              <p class="font-bold text-on-surface"><?php echo date('d M Y H:i', strtotime($v['scheduled_at'])); ?></p>
              <p class="text-on-surface-variant"><?php echo htmlspecialchars($v['reason'] ?? ''); ?></p>
            </div>
            <span class="text-xs font-bold px-2 py-1 rounded-full bg-primary-fixed text-primary"><?php echo htmlspecialchars($v['status'] ?? ''); ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
