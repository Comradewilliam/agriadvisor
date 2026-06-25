<?php
$targetId = (int)($target['id'] ?? 0);
$name = htmlspecialchars($target['name'] ?? '');
?>

<div class="p-6 md:p-8 max-w-6xl mx-auto">
  <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
      <a href="/officer/officers" class="inline-flex items-center gap-1 text-sm font-bold text-primary hover:underline mb-2">
        <span class="material-symbols-outlined text-base">arrow_back</span> Back to Officers
      </a>
      <h2 class="text-3xl font-bold text-on-surface">Officer Profile</h2>
    </div>
    <a href="/officer/officers?edit=<?php echo $targetId; ?>"
       class="flex items-center gap-2 bg-primary text-white font-bold px-4 py-2 rounded-xl hover:bg-primary-container shadow-sm">
      <span class="material-symbols-outlined text-base">edit</span> Edit Officer
    </a>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1 space-y-6">
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant p-6 text-center">
        <img src="<?php echo \App\Helpers\Avatar::url($target['name'] ?? 'O', '77574d', 96); ?>" class="w-24 h-24 rounded-2xl mx-auto mb-4" alt="">
        <h3 class="text-xl font-extrabold text-on-surface mb-1"><?php echo $name; ?></h3>
        <p class="text-sm text-on-surface-variant mb-3">Ward Agricultural Officer</p>
        <span class="text-xs font-bold px-3 py-1 rounded-full <?php echo ($target['is_active'] ?? 0) ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'; ?>">
          <?php echo ($target['is_active'] ?? 0) ? __('active') : __('inactive'); ?>
        </span>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant p-5 space-y-4 text-sm">
        <div>
          <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1"><?php echo __('email'); ?></p>
          <p class="font-medium"><?php echo htmlspecialchars($target['email'] ?? '—'); ?></p>
        </div>
        <div>
          <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1"><?php echo __('phone'); ?></p>
          <p class="font-medium"><?php echo htmlspecialchars($target['phone'] ?? '—'); ?></p>
        </div>
        <div>
          <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1"><?php echo __('working_office'); ?></p>
          <p class="font-medium"><?php echo htmlspecialchars($target['working_office'] ?? '—'); ?></p>
        </div>
        <div>
          <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Member since</p>
          <p class="font-medium"><?php echo !empty($target['created_at']) ? date('d M Y', strtotime($target['created_at'])) : '—'; ?></p>
        </div>
      </div>

      <div class="grid grid-cols-3 gap-3">
        <div class="bg-primary-fixed rounded-xl p-4 text-center">
          <p class="text-2xl font-black text-primary"><?php echo (int)($visitStats['total'] ?? 0); ?></p>
          <p class="text-[10px] font-bold text-on-surface-variant uppercase">Visits</p>
        </div>
        <div class="bg-tertiary-fixed rounded-xl p-4 text-center">
          <p class="text-2xl font-black text-tertiary"><?php echo (int)($visitStats['completed'] ?? 0); ?></p>
          <p class="text-[10px] font-bold text-on-surface-variant uppercase">Done</p>
        </div>
        <div class="bg-surface-container rounded-xl p-4 text-center">
          <p class="text-2xl font-black text-on-surface"><?php echo (int)$farmerCount; ?></p>
          <p class="text-[10px] font-bold text-on-surface-variant uppercase">Farmers</p>
        </div>
      </div>
    </div>

    <div class="lg:col-span-2 space-y-6">
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant p-6">
        <h3 class="font-bold text-on-surface mb-4"><?php echo __('assigned_wards'); ?></h3>
        <?php if (!empty($target['assigned_wards'])): ?>
          <div class="flex flex-wrap gap-2">
            <?php foreach (explode(', ', $target['assigned_wards']) as $wn): ?>
              <span class="bg-primary-fixed text-primary text-sm font-bold px-3 py-1 rounded-full"><?php echo htmlspecialchars($wn); ?></span>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-on-surface-variant text-sm">No wards assigned.</p>
        <?php endif; ?>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant overflow-hidden">
        <div class="p-5 border-b border-outline-variant"><h3 class="font-bold text-on-surface">Villages managed (<?php echo count($villages); ?>)</h3></div>
        <div class="p-5 flex flex-wrap gap-2 max-h-48 overflow-y-auto">
          <?php if (empty($villages)): ?>
            <p class="text-sm text-on-surface-variant">—</p>
          <?php else: foreach ($villages as $v): ?>
            <span class="text-xs bg-surface-container px-2 py-1 rounded-lg"><?php echo htmlspecialchars($v['ward_name'] . ' · ' . $v['name']); ?></span>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant overflow-hidden">
        <div class="p-5 border-b border-outline-variant flex justify-between items-center">
          <h3 class="font-bold text-on-surface">Farmers in coverage area</h3>
          <a href="/officer/farmers" class="text-xs font-bold text-primary hover:underline">View all</a>
        </div>
        <div class="divide-y divide-outline-variant">
          <?php if (empty($farmers)): ?>
            <div class="p-6 text-center text-on-surface-variant text-sm">No farmers registered in assigned wards.</div>
          <?php else: foreach ($farmers as $f): ?>
            <a href="/officer/farmers/view?id=<?php echo (int)$f['id']; ?>" class="p-4 flex justify-between items-center hover:bg-surface-container-lowest text-sm">
              <div>
                <p class="font-bold text-on-surface"><?php echo htmlspecialchars($f['name']); ?></p>
                <p class="text-on-surface-variant text-xs"><?php echo htmlspecialchars($f['village_name'] ?? '—'); ?></p>
              </div>
              <span class="material-symbols-outlined text-outline">chevron_right</span>
            </a>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant overflow-hidden">
        <div class="p-5 border-b border-outline-variant"><h3 class="font-bold text-on-surface">Recent visits</h3></div>
        <div class="divide-y divide-outline-variant">
          <?php if (empty($visits)): ?>
            <div class="p-6 text-center text-on-surface-variant text-sm">No visits recorded yet.</div>
          <?php else: foreach ($visits as $v): ?>
            <div class="p-4 flex justify-between items-center text-sm">
              <div>
                <p class="font-bold text-on-surface"><?php echo htmlspecialchars($v['farmer_name'] ?? 'Farmer'); ?></p>
                <p class="text-on-surface-variant"><?php echo date('d M Y H:i', strtotime($v['scheduled_at'])); ?> — <?php echo htmlspecialchars($v['reason'] ?? ''); ?></p>
              </div>
              <span class="text-xs font-bold px-2 py-1 rounded-full bg-primary-fixed text-primary"><?php echo htmlspecialchars($v['status'] ?? ''); ?></span>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
