<?php
/**
 * views/officer/weather.php  v1.4
 * – Section renamed: "Weekly District Forecast" with day-click popup modal
 * – "Weather Intelligence" is now a sub-section heading above alert creation
 * – Translations applied
 */

$canApprove = in_array($officer['role'] ?? '', ['ward_officer','dao','super_admin'], true);
$roleLabel  = ['ward_officer'=>'WAO','dao'=>'DAO','super_admin'=>'Admin'][$officer['role'] ?? ''] ?? 'Officer';

$agriTips = [
    'thunderstorm'      => __('heavy_rain') . ': Epuka kulima. Hifadhi mazao yaliyovunwa.',
    'wb_cloudy'         => 'Angalizo la mvua: Kagua mifereji ya maji shambani.',
    'wb_sunny'          => 'Jua kali: Mwagilia mapema asubuhi. Tumia matandazo.',
    'partly_cloudy_day' => 'Hewa nzuri kwa dawa za wadudu. Hali nzuri ya kupanda.',
];

function approvalProgress(array $a): string {
    $steps = [];
    if (!empty($a['approved_by_ward'])) $steps[] = 'WAO';
    if (!empty($a['approved_by_dao']))  $steps[] = 'DAO';
    if (!empty($a['approved_by_admin'])) $steps[] = 'Admin';
    return implode(' + ', $steps) ?: __('no_data');
}

$weatherDayNames = [
    0 => 'Jumapili', 1 => 'Jumatatu', 2 => 'Jumanne',
    3 => 'Jumatano', 4 => 'Alhamisi', 5 => 'Ijumaa', 6 => 'Jumamosi',
];
?>
<div class="p-6 md:p-8 max-w-6xl mx-auto">

  <?php if (!empty($_GET['success'])): ?>
    <div class="mb-4 p-4 rounded-xl bg-primary-fixed text-primary text-sm font-medium">
      <?php $msgs = ['created'=>__('alert_submit').' ✓','approved'=>__('approve').' ✓','rejected'=>__('reject').' ✓'];
            echo $msgs[$_GET['success']] ?? __('success'); ?>
    </div>
  <?php endif; ?>

  <!-- ── Weekly District Forecast ──────────────────────────────────────── -->
  <div class="mb-8">
    <h2 class="text-3xl font-bold text-on-surface mb-1"><?php echo __('weather_title'); ?></h2>
    <p class="text-on-surface-variant text-sm mb-5"><?php echo date('l, d F Y'); ?></p>

    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2">
      <?php foreach ($forecast as $idx => $day): ?>
        <button type="button"
          onclick="openDayModal(<?php echo $idx; ?>)"
          class="<?php echo $day['featured']
            ? 'bg-tertiary text-white rounded-2xl p-4 col-span-2 sm:col-span-2 text-left'
            : 'bg-white border border-outline-variant rounded-2xl p-3 text-center hover:border-primary hover:shadow-md'; ?> transition-all cursor-pointer">
          <p class="text-xs font-bold uppercase <?php echo $day['featured'] ? 'text-white/70' : 'text-on-surface-variant'; ?>">
            <?php echo $day['day']; ?>
          </p>
          <?php if ($day['featured']): ?>
            <p class="text-4xl font-extrabold mt-2"><?php echo $day['temp_h']; ?>°</p>
            <p class="text-sm mt-1 text-white/80"><?php echo htmlspecialchars($day['detail']); ?></p>
            <div class="flex items-center gap-2 mt-2">
              <span class="material-symbols-outlined text-base text-white/70">water_drop</span>
              <span class="text-xs text-white/80"><?php echo $day['rain']; ?>%</span>
            </div>
          <?php else: ?>
            <span class="material-symbols-outlined text-2xl mt-2 block <?php echo $day['featured'] ? '' : 'text-on-surface-variant'; ?>">
              <?php echo $day['icon']; ?>
            </span>
            <p class="font-extrabold text-on-surface mt-1"><?php echo $day['temp_h']; ?>°</p>
            <p class="text-xs text-on-surface-variant"><?php echo $day['rain']; ?>%</p>
          <?php endif; ?>
        </button>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ── Weather Intelligence ──────────────────────────────────────────── -->
  <div class="mb-2 flex flex-wrap justify-between gap-4">
    <div>
      <h3 class="text-xl font-bold text-on-surface"><?php echo __('weather_intel'); ?></h3>
      <p class="text-on-surface-variant text-sm"><?php echo __('weather_subtitle'); ?></p>
    </div>
  </div>

  <!-- Create Alert -->
  <div class="bg-white rounded-2xl shadow-sm border border-outline-variant p-5 mb-8">
    <h4 class="font-bold text-on-surface mb-4"><?php echo __('new_alert'); ?></h4>
    <form method="POST" action="/officer/weather/create" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="text-xs font-bold text-on-surface-variant uppercase"><?php echo __('alert_type'); ?></label>
        <select name="alert_type" class="w-full mt-1 border border-outline rounded-lg px-3 py-2 text-sm">
          <option value="heavy_rain"><?php echo __('heavy_rain'); ?></option>
          <option value="drought"><?php echo __('drought'); ?></option>
          <option value="strong_wind"><?php echo __('strong_wind'); ?></option>
          <option value="pest_outbreak"><?php echo __('pest_outbreak'); ?></option>
          <option value="planting_window"><?php echo __('planting_window'); ?></option>
          <option value="general"><?php echo __('general'); ?></option>
        </select>
      </div>
      <div>
        <label class="text-xs font-bold text-on-surface-variant uppercase"><?php echo __('alert_severity'); ?></label>
        <select name="severity" class="w-full mt-1 border border-outline rounded-lg px-3 py-2 text-sm">
          <option value="low"><?php echo __('severity_low'); ?></option>
          <option value="medium" selected><?php echo __('severity_medium'); ?></option>
          <option value="high"><?php echo __('severity_high'); ?></option>
        </select>
      </div>
      <?php if (($officer['role'] ?? '') === 'ward_officer' && !empty($officer['ward_id'])): ?>
        <input type="hidden" name="ward_id" value="<?php echo (int)$officer['ward_id']; ?>">
      <?php elseif (!empty($wards)): ?>
      <div>
        <label class="text-xs font-bold text-on-surface-variant uppercase"><?php echo __('alert_ward'); ?></label>
        <select name="ward_id" class="w-full mt-1 border border-outline rounded-lg px-3 py-2 text-sm">
          <option value=""><?php echo __('alert_district'); ?></option>
          <?php foreach ($wards as $w): ?>
            <option value="<?php echo (int)$w['id']; ?>"><?php echo htmlspecialchars($w['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div class="md:col-span-2">
        <label class="text-xs font-bold text-on-surface-variant uppercase"><?php echo __('alert_title'); ?></label>
        <input type="text" name="title" required class="w-full mt-1 border border-outline rounded-lg px-3 py-2 text-sm" placeholder="<?php echo __('alert_title'); ?>..." />
      </div>
      <div class="md:col-span-2">
        <label class="text-xs font-bold text-on-surface-variant uppercase"><?php echo __('alert_message'); ?></label>
        <textarea name="message" required rows="3" class="w-full mt-1 border border-outline rounded-lg px-3 py-2 text-sm" placeholder="<?php echo __('alert_message'); ?>..."></textarea>
      </div>
      <div class="md:col-span-2">
        <button type="submit" class="bg-primary text-white font-bold px-6 py-2.5 rounded-xl hover:bg-primary-container"><?php echo __('alert_submit'); ?></button>
      </div>
    </form>
  </div>

  <!-- Pending Approvals -->
  <div class="bg-white rounded-2xl shadow-sm border border-outline-variant overflow-hidden mb-8">
    <div class="p-5 border-b border-outline-variant flex items-center gap-3">
      <h3 class="font-bold text-on-surface flex-1"><?php echo __('pending_approvals'); ?></h3>
      <?php if (!empty($pendingAlerts)): ?>
        <span class="w-6 h-6 bg-error text-white text-xs font-bold rounded-full flex items-center justify-center"><?php echo count($pendingAlerts); ?></span>
      <?php endif; ?>
    </div>
    <?php if (empty($pendingAlerts)): ?>
      <div class="p-8 text-center text-outline">
        <span class="material-symbols-outlined text-4xl mb-2 block">check_circle</span>
        <p><?php echo __('no_pending_alerts'); ?></p>
      </div>
    <?php else: ?>
      <div class="divide-y divide-outline-variant">
        <?php foreach ($pendingAlerts as $alert):
          $myApprovalDone = false;
          if ($officer['role'] === 'ward_officer' && !empty($alert['approved_by_ward'])) $myApprovalDone = true;
          if ($officer['role'] === 'dao'         && !empty($alert['approved_by_dao']))  $myApprovalDone = true;
          if ($officer['role'] === 'super_admin' && !empty($alert['approved_by_admin'])) $myApprovalDone = true;
          $target = $alert['ward_name'] ? $alert['ward_name'] . ' Ward' : ($alert['district_name'] ?? 'District-wide');
        ?>
          <div class="p-5 flex flex-col md:flex-row md:items-start gap-4">
            <div class="flex-1">
              <p class="font-bold text-on-surface uppercase text-sm"><?php echo htmlspecialchars(str_replace('_',' ',$alert['alert_type'] ?? 'alert')); ?></p>
              <p class="text-xs text-on-surface-variant mb-2">By <?php echo htmlspecialchars($alert['creator_name'] ?? ''); ?> · <?php echo htmlspecialchars($target); ?></p>
              <div class="bg-surface-container-low rounded-xl p-4 mb-2">
                <p class="text-sm font-bold"><?php echo htmlspecialchars($alert['title']); ?></p>
                <p class="text-sm text-on-surface-variant mt-1 italic">"<?php echo htmlspecialchars($alert['message']); ?>"</p>
              </div>
              <p class="text-xs text-on-surface-variant"><?php echo __('approval_by'); ?> <?php echo approvalProgress($alert); ?></p>
              <?php if ($alert['expires_at']): ?>
                <p class="text-xs text-error mt-1"><?php echo __('expires'); ?> <?php echo date('M j, H:i', strtotime($alert['expires_at'])); ?></p>
              <?php endif; ?>
            </div>
            <?php if ($canApprove && !$myApprovalDone): ?>
            <div class="flex md:flex-col gap-2 shrink-0">
              <form method="POST" action="/officer/weather/approve">
                <input type="hidden" name="alert_id" value="<?php echo (int)$alert['id']; ?>">
                <button type="submit" class="w-full flex items-center justify-center gap-2 bg-primary text-white font-bold px-5 py-2 rounded-xl text-sm">
                  <span class="material-symbols-outlined text-base">check</span> <?php echo __('approve'); ?> (<?php echo $roleLabel; ?>)
                </button>
              </form>
              <form method="POST" action="/officer/weather/reject">
                <input type="hidden" name="alert_id" value="<?php echo (int)$alert['id']; ?>">
                <button type="submit" class="w-full flex items-center justify-center gap-2 border border-outline-variant font-bold px-5 py-2 rounded-xl text-sm">
                  <span class="material-symbols-outlined text-base">close</span> <?php echo __('reject'); ?>
                </button>
              </form>
            </div>
            <?php elseif ($myApprovalDone): ?>
              <span class="text-xs font-bold text-primary bg-primary-fixed px-3 py-1 rounded-full"><?php echo __('already_approved'); ?></span>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Broadcast History -->
  <div class="bg-white rounded-2xl shadow-sm border border-outline-variant overflow-hidden">
    <div class="p-5 border-b border-outline-variant">
      <h3 class="font-bold text-on-surface"><?php echo __('broadcast_history'); ?></h3>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-surface-container text-xs text-on-surface-variant uppercase">
          <tr>
            <th class="p-4 text-left"><?php echo __('date'); ?></th>
            <th class="p-4 text-left"><?php echo __('alert_type'); ?></th>
            <th class="p-4 text-left"><?php echo __('target'); ?></th>
            <th class="p-4 text-left"><?php echo __('status'); ?></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-outline-variant">
          <?php if (empty($broadcastHistory)): ?>
            <tr><td colspan="4" class="p-6 text-center text-outline"><?php echo __('no_history'); ?></td></tr>
          <?php else: ?>
            <?php foreach ($broadcastHistory as $bh):
              $status = $bh['approval_status'] ?? ($bh['active'] ? 'approved' : 'pending');
              $cls = match($status) {
                'approved' => 'bg-primary-fixed text-primary',
                'rejected' => 'bg-error-container text-error',
                'expired'  => 'bg-surface-container text-outline',
                default    => 'bg-secondary-fixed text-secondary',
              };
            ?>
              <tr class="hover:bg-surface-container-lowest">
                <td class="p-4"><?php echo date('M j, Y', strtotime($bh['created_at'])); ?></td>
                <td class="p-4 font-medium"><?php echo htmlspecialchars(str_replace('_',' ',$bh['alert_type'] ?? 'alert')); ?></td>
                <td class="p-4 text-on-surface-variant"><?php echo htmlspecialchars($bh['ward_name'] ?? $bh['district_name'] ?? 'District'); ?></td>
                <td class="p-4"><span class="text-xs font-bold px-2 py-1 rounded-full <?php echo $cls; ?>"><?php echo ucfirst($status); ?></span></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ─── Day Forecast Modal ──────────────────────────────────────────────────── -->
<div id="dayModal" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm">
    <div class="bg-tertiary rounded-t-3xl p-6 text-white relative overflow-hidden">
      <div class="absolute inset-0 opacity-10" style="background-image:radial-gradient(circle at 70% 50%, white 1px, transparent 1px); background-size:20px 20px;"></div>
      <button onclick="document.getElementById('dayModal').classList.add('hidden')" class="absolute top-4 right-4 text-white/70 hover:text-white">
        <span class="material-symbols-outlined">close</span>
      </button>
      <p id="modalDayName" class="text-sm font-bold uppercase text-white/70"></p>
      <p id="modalDate" class="text-lg font-extrabold mt-1"></p>
      <div class="flex items-end gap-4 mt-4">
        <p id="modalTempH" class="text-6xl font-extrabold"></p>
        <div>
          <p class="text-sm text-white/70"><?php echo __('temp_high'); ?> <span id="modalTempHval"></span>°</p>
          <p class="text-sm text-white/70"><?php echo __('temp_low'); ?> <span id="modalTempLval"></span>°</p>
        </div>
      </div>
    </div>
    <div class="p-6 space-y-4">
      <div class="flex items-center gap-3">
        <span class="material-symbols-outlined text-tertiary text-2xl" id="modalIcon"></span>
        <p id="modalDetail" class="text-sm text-on-surface-variant"></p>
      </div>
      <div class="flex items-center gap-3 bg-tertiary-fixed/40 rounded-xl px-4 py-3">
        <span class="material-symbols-outlined text-tertiary">water_drop</span>
        <div>
          <p class="text-xs text-on-surface-variant"><?php echo __('rain_chance'); ?></p>
          <p id="modalRain" class="font-extrabold text-tertiary"></p>
        </div>
      </div>
      <div class="bg-primary-fixed/40 rounded-xl px-4 py-3">
        <p class="text-xs font-bold text-primary uppercase tracking-wider mb-1"><?php echo __('agri_tip'); ?></p>
        <p id="modalAgriTip" class="text-sm text-on-surface"></p>
      </div>
    </div>
  </div>
</div>

<script>
const forecastData = <?php echo json_encode(array_values($forecast)); ?>;
const agriTips = <?php echo json_encode($agriTips); ?>;

function openDayModal(idx) {
  const d = forecastData[idx];
  document.getElementById('modalDayName').textContent  = d.day;
  document.getElementById('modalDate').textContent      = d.date;
  document.getElementById('modalTempH').textContent     = d.temp_h + '°';
  document.getElementById('modalTempHval').textContent  = d.temp_h;
  document.getElementById('modalTempLval').textContent  = d.temp_l;
  document.getElementById('modalIcon').textContent      = d.icon;
  document.getElementById('modalDetail').textContent    = d.detail || '';
  document.getElementById('modalRain').textContent      = d.rain + '%';
  document.getElementById('modalAgriTip').textContent   = agriTips[d.icon] || '—';
  document.getElementById('dayModal').classList.remove('hidden');
}
</script>
