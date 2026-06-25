<?php
/**
 * views/officer/visits.php  v1.4
 * – Visit type badge (officer_planned / farmer_requested)
 * – Feedback modal (completed → outcome | not done → reason)
 * – DAO sees all district visits; WAO sees ward-scoped
 */
$weekDays   = ['SUN','MON','TUE','WED','THU','FRI','SAT'];
$today      = date('j');
$daysInMonth = date('t');
$firstDow   = (int)date('w', strtotime(date('Y-m-01')));

$visitByDay = [];
foreach ($visits as $v) {
    $d = (int)date('j', strtotime($v['scheduled_at']));
    $visitByDay[$d][] = $v;
}
$activeTab = $tab ?? 'visits';
$pendingCount = count($pendingRequests ?? []);
?>
<div class="p-6 md:p-8">

  <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
      <h3 class="text-3xl font-bold text-primary"><?php echo __('visits_title'); ?></h3>
      <p class="text-on-surface-variant">
        <?php echo !empty($isDao) ? __('visit_dao_view') : __('visits_subtitle'); ?>
      </p>
    </div>
    <?php if (empty($isDao) && $activeTab === 'visits'): ?>
    <button onclick="document.getElementById('scheduleModal').classList.remove('hidden')"
            class="bg-primary text-white px-6 py-2.5 rounded-xl font-bold flex items-center gap-2 hover:bg-primary-container transition-colors shadow-sm">
      <span class="material-symbols-outlined">add_location_alt</span> <?php echo __('visit_schedule'); ?>
    </button>
    <?php endif; ?>
  </div>

  <!-- Main tabs: Visits | Farmer Requests -->
  <div class="mb-6 flex gap-1 bg-surface-container-low border border-outline-variant rounded-xl p-1 w-fit">
    <a href="/officer/visits?tab=visits"
       class="text-sm font-bold px-5 py-2 rounded-lg <?php echo $activeTab === 'visits' ? 'bg-white shadow-sm text-on-surface' : 'text-on-surface-variant hover:bg-white/60'; ?>">
      <?php echo __('nav_visits'); ?>
    </a>
    <a href="/officer/visits?tab=requests"
       class="text-sm font-bold px-5 py-2 rounded-lg flex items-center gap-2 <?php echo $activeTab === 'requests' ? 'bg-white shadow-sm text-on-surface' : 'text-on-surface-variant hover:bg-white/60'; ?>">
      <?php echo __('nav_visit_requests'); ?>
      <?php if ($pendingCount > 0): ?>
        <span class="bg-error text-white text-xs font-bold min-w-[1.25rem] h-5 px-1 rounded-full flex items-center justify-center"><?php echo $pendingCount; ?></span>
      <?php endif; ?>
    </a>
  </div>

  <?php if (isset($_GET['success'])): ?>
    <div class="mb-4 bg-primary-fixed text-primary rounded-xl px-5 py-3 font-bold">
      <?php if (($_GET['success'] ?? '') === 'group' && !empty($_GET['count'])): ?>
        <?php echo sprintf(__('visit_group_saved'), (int)$_GET['count']); ?>
      <?php else: ?>
        <?php echo __('visit_saved'); ?>
      <?php endif; ?>
    </div>
  <?php endif; ?>
  <?php if (isset($_GET['error'])): ?>
    <div class="mb-4 bg-error-container text-error rounded-xl px-5 py-3 font-medium">
      <?php
      echo match ($_GET['error'] ?? '') {
          'readonly'  => 'View only for DAO.',
          'missing'   => 'Tafadhali jaza tarehe, sababu, na mkulima/lengo.',
          'nofarmers' => __('visit_no_farmers_match'),
          'server'    => 'Hitilafu ya mfumo wakati wa kuhifadhi ziara. Jaribu tena.',
          default     => 'Something went wrong.',
      };
      ?>
    </div>
  <?php endif; ?>

<?php if ($activeTab === 'requests'): ?>
  <?php
  $reqTabs = ['all' => __('visit_all'), 'pending' => __('visit_pending'), 'scheduled' => __('visit_schedule'), 'postponed' => __('visit_postponed'), 'completed' => __('visit_done'), 'cancelled' => __('cancel')];
  ?>
  <div class="mb-4 flex flex-wrap gap-2">
    <?php foreach ($reqTabs as $key => $label): ?>
      <a href="/officer/visits?tab=requests&amp;rstatus=<?php echo $key; ?>"
         class="text-sm font-bold px-4 py-1.5 rounded-xl border <?php echo ($requestStatusFilter ?? 'all') === $key ? 'bg-primary text-white border-primary' : 'bg-white border-outline-variant text-on-surface-variant hover:border-primary'; ?>">
        <?php echo $label; ?>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="bg-white rounded-2xl shadow-sm border border-outline-variant overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left">
        <thead class="bg-surface-container">
          <tr class="text-xs text-on-surface-variant uppercase tracking-wider">
            <th class="px-6 py-4"><?php echo __('farmer_name'); ?></th>
            <th class="px-6 py-4"><?php echo __('visit_village'); ?> / <?php echo __('visit_crop'); ?></th>
            <th class="px-6 py-4"><?php echo __('visit_notes'); ?></th>
            <th class="px-6 py-4"><?php echo __('visit_date'); ?></th>
            <th class="px-6 py-4"><?php echo __('kb_status'); ?></th>
            <th class="px-6 py-4"><?php echo __('actions'); ?></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-outline-variant">
          <?php if (empty($requests)): ?>
            <tr><td colspan="6" class="px-6 py-8 text-center text-on-surface-variant"><?php echo __('no_pending'); ?></td></tr>
          <?php else: foreach ($requests as $req): ?>
            <tr class="hover:bg-surface-container-low">
              <td class="px-6 py-4">
                <p class="font-bold text-on-surface"><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></p>
                <p class="text-xs text-on-surface-variant"><?php echo htmlspecialchars($req['phone']); ?></p>
              </td>
              <td class="px-6 py-4 text-sm">
                <p class="text-on-surface"><?php echo htmlspecialchars($req['village_name'] ?? $req['ward_name'] ?? '—'); ?></p>
                <p class="text-xs text-on-surface-variant">
                  <?php echo htmlspecialchars($req['crop_name'] ?? '—'); ?>
                  <?php if (!empty($req['display_farm_size'])): ?> &bull; <?php echo (float)$req['display_farm_size']; ?> ac<?php endif; ?>
                </p>
              </td>
              <td class="px-6 py-4 max-w-xs text-sm text-on-surface line-clamp-2"><?php echo htmlspecialchars($req['request_reason']); ?></td>
              <td class="px-6 py-4 text-sm text-on-surface">
                <?php if (!empty($req['scheduled_at'])): ?>
                  <?php echo date('d M Y H:i', strtotime($req['scheduled_at'])); ?>
                <?php elseif ($req['preferred_date']): ?>
                  Pref: <?php echo date('d M Y', strtotime($req['preferred_date'])); ?>
                <?php else: ?>
                  <span class="text-outline">Flexible</span>
                <?php endif; ?>
              </td>
              <td class="px-6 py-4">
                <?php $badge = match($req['status']) {
                    'pending' => 'bg-amber-100 text-amber-800', 'scheduled' => 'bg-blue-100 text-blue-800',
                    'postponed' => 'bg-orange-100 text-orange-800', 'completed' => 'bg-green-100 text-green-800',
                    'cancelled' => 'bg-red-100 text-red-800', default => 'bg-gray-100 text-gray-700',
                }; ?>
                <span class="inline-block px-3 py-1 rounded-full text-xs font-bold <?php echo $badge; ?>"><?php echo ucfirst($req['status']); ?></span>
              </td>
              <td class="px-6 py-4">
                <?php if (!empty($canHandle) && in_array($req['status'], ['pending', 'postponed'], true)): ?>
                  <button type="button" onclick="document.getElementById('handle-modal-<?php echo $req['id']; ?>').classList.remove('hidden')"
                          class="text-primary font-bold text-sm hover:underline"><?php echo __('actions'); ?></button>
                <?php elseif (!empty($req['handler_name'])): ?>
                  <span class="text-xs text-outline"><?php echo htmlspecialchars($req['handler_name']); ?></span>
                <?php else: ?><span class="text-outline text-sm">—</span><?php endif; ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php else: ?>

  <?php
  $statusTabs = ['all' => __('visit_all'), 'pending' => __('visit_pending'), 'scheduled' => __('visit_schedule'), 'postponed' => __('visit_postponed'), 'completed' => __('visit_done'), 'cancelled' => __('cancel')];
  ?>
  <div class="mb-4 flex flex-wrap gap-2">
    <?php foreach ($statusTabs as $key => $label): ?>
      <a href="/officer/visits?tab=visits&amp;status=<?php echo $key; ?>"
         class="text-sm font-bold px-4 py-1.5 rounded-xl border <?php echo ($statusFilter ?? 'all') === $key ? 'bg-primary text-white border-primary' : 'bg-white border-outline-variant text-on-surface-variant hover:border-primary'; ?>">
        <?php echo $label; ?>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-1 gap-6">
    <div class="space-y-6">

      <!-- Calendar -->
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant p-5">
        <div class="flex items-center justify-between mb-5">
          <h4 class="text-xl font-bold text-on-surface"><?php echo date('F Y'); ?></h4>
        </div>
        <div class="grid grid-cols-7 mb-2">
          <?php foreach ($weekDays as $d): ?>
            <div class="text-center text-xs font-bold text-on-surface-variant uppercase py-2"><?php echo $d; ?></div>
          <?php endforeach; ?>
        </div>
        <div class="grid grid-cols-7 gap-1">
          <?php for ($i = 0; $i < $firstDow; $i++): ?>
            <div class="h-14 rounded-xl"></div>
          <?php endfor;
          for ($day = 1; $day <= $daysInMonth; $day++):
            $isToday  = $day == $today;
            $hasVisit = isset($visitByDay[$day]);
            $hasUrgent = $hasVisit && count(array_filter($visitByDay[$day] ?? [], fn($v) => $v['status'] === 'pending')) > 0;
          ?>
            <div class="h-14 rounded-xl flex flex-col items-center justify-start pt-1.5 relative cursor-pointer
              <?php echo $isToday ? 'bg-primary text-white shadow-lg ring-2 ring-primary/30' : 'hover:bg-surface-container-low'; ?> transition-all">
              <span class="text-sm font-bold"><?php echo $day; ?></span>
              <?php if ($isToday): ?>
                <span class="text-xs text-white/70 mt-0.5">Today</span>
              <?php elseif ($hasUrgent): ?>
                <span class="w-2 h-2 rounded-full bg-error mt-1 block"></span>
              <?php elseif ($hasVisit): ?>
                <div class="mt-1 bg-tertiary text-white text-xs rounded-md px-1.5 py-0.5 leading-tight max-w-full overflow-hidden">
                  <?php echo htmlspecialchars(mb_substr($visitByDay[$day][0]['farmer_name'] ?? 'Visit', 0, 8)); ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endfor; ?>
        </div>
      </div>

      <!-- All Visits List -->
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant overflow-hidden">
        <div class="p-5 border-b border-outline-variant">
          <h4 class="font-bold text-on-surface"><?php echo __('upcoming_visits'); ?></h4>
        </div>
        <div class="divide-y divide-outline-variant">
          <?php if (empty($visits)): ?>
            <div class="p-8 text-center text-outline">
              <span class="material-symbols-outlined text-4xl mb-2 block">event_busy</span>
              <?php echo __('no_visits_sched'); ?>
            </div>
          <?php else: ?>
            <?php foreach ($visits as $v):
              $statusCls = [
                'scheduled' => 'bg-tertiary-fixed text-tertiary',
                'completed' => 'bg-primary-fixed text-primary',
                'cancelled' => 'bg-error-container text-error',
                'pending'   => 'bg-secondary-fixed text-secondary',
                'postponed' => 'bg-amber-100 text-amber-800',
              ][$v['status']] ?? 'bg-surface-container text-on-surface-variant';
              $typeLabel = ($v['visit_type'] ?? 'officer_planned') === 'farmer_requested'
                  ? __('requested_by_farmer') : __('planned_by_officer');
              $typeCls = ($v['visit_type'] ?? '') === 'farmer_requested'
                  ? 'bg-secondary-fixed text-secondary' : 'bg-surface-container text-on-surface-variant';
            ?>
              <div class="flex items-center gap-4 p-4 hover:bg-surface-container-lowest transition-colors">
                <div class="w-14 h-14 rounded-xl bg-tertiary flex flex-col items-center justify-center text-white shrink-0">
                  <span class="text-xs font-bold"><?php echo strtoupper(date('M', strtotime($v['scheduled_at']))); ?></span>
                  <span class="text-2xl font-extrabold leading-tight"><?php echo date('j', strtotime($v['scheduled_at'])); ?></span>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="font-bold text-on-surface flex flex-wrap items-center gap-2">
                    <?php echo htmlspecialchars($v['farmer_name']); ?>
                    <?php if (!empty($v['visit_batch_id']) && ($batchCounts[$v['visit_batch_id']] ?? 0) > 1): ?>
                      <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-800"><?php echo sprintf(__('visit_group_badge'), $batchCounts[$v['visit_batch_id']]); ?></span>
                    <?php endif; ?>
                  </p>
                  <p class="text-xs text-on-surface-variant">
                    <span class="material-symbols-outlined text-xs align-middle">schedule</span>
                    <?php echo date('H:i', strtotime($v['scheduled_at'])); ?> &bull;
                    <span class="material-symbols-outlined text-xs align-middle">location_on</span>
                    <?php echo htmlspecialchars($v['village_name'] ?? '—'); ?>
                    <?php if (!empty($v['crop_name'])): ?> &bull; <?php echo htmlspecialchars($v['crop_name']); ?><?php endif; ?>
                    <?php if (!empty($v['display_farm_size'])): ?> &bull; <?php echo (float)$v['display_farm_size']; ?> ac<?php endif; ?>
                  </p>
                  <?php if (!empty($isDao) && !empty($v['officer_name'])): ?>
                  <p class="text-xs text-outline"><?php echo __('visit_officer'); ?>: <?php echo htmlspecialchars($v['officer_name']); ?></p>
                  <?php endif; ?>
                  <p class="text-xs text-outline mt-0.5 truncate"><?php echo htmlspecialchars($v['reason'] ?? ''); ?></p>
                  <?php if (!empty($v['outcome'])): ?>
                  <p class="text-xs text-primary mt-1"><strong><?php echo __('visit_outcome'); ?>:</strong> <?php echo htmlspecialchars(mb_substr($v['outcome'],0,80)); ?></p>
                  <?php elseif (!empty($v['not_done_reason'])): ?>
                  <p class="text-xs text-error mt-1"><strong><?php echo __('visit_not_done'); ?>:</strong> <?php echo htmlspecialchars(mb_substr($v['not_done_reason'],0,80)); ?></p>
                  <?php endif; ?>
                  <?php if (!empty($v['followup'])): ?>
                  <p class="text-xs text-on-surface-variant mt-1"><strong><?php echo __('visit_followup'); ?>:</strong> <?php echo htmlspecialchars(mb_substr($v['followup'],0,80)); ?></p>
                  <?php endif; ?>
                </div>
                <div class="flex flex-col items-end gap-1 shrink-0">
                  <span class="text-xs font-bold px-2 py-0.5 rounded-full <?php echo $statusCls; ?>"><?php echo ucfirst($v['status']); ?></span>
                  <span class="text-xs px-2 py-0.5 rounded-full <?php echo $typeCls; ?>"><?php echo $typeLabel; ?></span>
                  <?php if (empty($isDao) && in_array($v['status'], ['scheduled','postponed','pending'], true)): ?>
                    <button onclick="openFeedbackModal(<?php echo (int)$v['id']; ?>, '<?php echo htmlspecialchars(addslashes($v['farmer_name'])); ?>')"
                            class="text-xs text-primary border border-primary rounded-lg px-3 py-1 hover:bg-primary hover:text-white transition-colors mt-1">
                      <?php echo __('visit_feedback'); ?>
                    </button>
                    <button onclick="openFollowupModal(<?php echo (int)$v['id']; ?>)"
                            class="text-xs text-on-surface-variant border border-outline-variant rounded-lg px-3 py-1 hover:bg-surface-container mt-1">
                      <?php echo __('visit_followup'); ?>
                    </button>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

<?php endif; ?>
</div>

<?php if (!empty($canHandle) && ($activeTab === 'requests')): foreach ($requests ?? [] as $req): ?>
  <?php if (in_array($req['status'], ['pending', 'postponed'], true)): ?>
    <div id="handle-modal-<?php echo $req['id']; ?>" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-outline-variant flex items-center justify-between sticky top-0 bg-white">
          <h3 class="text-lg font-bold text-on-surface"><?php echo __('visit_reschedule'); ?></h3>
          <button type="button" onclick="document.getElementById('handle-modal-<?php echo $req['id']; ?>').classList.add('hidden')"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form method="POST" action="/officer/visit-requests/handle" class="p-6 space-y-4">
          <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
          <div class="grid grid-cols-2 gap-3 text-sm">
            <div class="bg-surface-container-low rounded-xl p-3">
              <p class="text-xs text-outline uppercase"><?php echo __('visit_village'); ?></p>
              <p class="font-bold"><?php echo htmlspecialchars($req['village_name'] ?? '—'); ?></p>
            </div>
            <div class="bg-surface-container-low rounded-xl p-3">
              <p class="text-xs text-outline uppercase"><?php echo __('visit_crop'); ?></p>
              <p class="font-bold"><?php echo htmlspecialchars($req['crop_name'] ?? '—'); ?></p>
            </div>
          </div>
          <div>
            <label class="block text-sm font-bold text-on-surface-variant mb-2"><?php echo __('kb_status'); ?></label>
            <select id="status-<?php echo $req['id']; ?>" name="status" required class="w-full bg-surface-container rounded-xl px-4 py-3 border border-outline-variant" onchange="toggleScheduleAt(<?php echo $req['id']; ?>)">
              <option value="">—</option>
              <option value="scheduled"><?php echo __('visit_schedule'); ?> / Accept</option>
              <option value="postponed"><?php echo __('visit_reschedule'); ?></option>
              <option value="completed"><?php echo __('visit_done'); ?></option>
              <option value="cancelled"><?php echo __('cancel'); ?></option>
            </select>
          </div>
          <div id="schedule-at-wrapper-<?php echo $req['id']; ?>" class="hidden">
            <label class="block text-sm font-bold text-on-surface-variant mb-2"><?php echo __('visit_date'); ?></label>
            <input type="datetime-local" name="scheduled_at" class="w-full bg-surface-container rounded-xl px-4 py-3 border border-outline-variant">
          </div>
          <div>
            <textarea name="notes" rows="3" class="w-full bg-surface-container rounded-xl px-4 py-3 border border-outline-variant" placeholder="<?php echo __('visit_notes'); ?>..."></textarea>
          </div>
          <div class="flex gap-3 justify-end">
            <button type="button" onclick="document.getElementById('handle-modal-<?php echo $req['id']; ?>').classList.add('hidden')" class="px-5 py-2 rounded-xl text-on-surface-variant"><?php echo __('cancel'); ?></button>
            <button type="submit" class="px-5 py-2 bg-primary text-white font-bold rounded-xl"><?php echo __('save_changes'); ?></button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>
<?php endforeach; endif; ?>

<!-- ─── Schedule Visit Modal ───────────────────────────────────────────────── -->
<?php if (empty($isDao)): ?>
<div id="scheduleModal" class="hidden modal-backdrop">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg modal-panel">
    <div class="p-6 border-b border-outline-variant flex justify-between items-center">
      <h4 class="text-xl font-bold text-on-surface"><?php echo __('visit_schedule'); ?></h4>
      <button onclick="document.getElementById('scheduleModal').classList.add('hidden')">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>
    <form action="/officer/visits/schedule" method="POST" class="p-6 space-y-4" id="scheduleForm">
      <input type="hidden" name="visit_type" value="officer_planned">

      <div>
        <label class="block text-sm font-medium text-on-surface-variant mb-2"><?php echo __('visit_scope'); ?></label>
        <div class="grid grid-cols-2 gap-2">
          <label class="flex items-center gap-2 cursor-pointer border border-outline-variant rounded-xl px-3 py-2 hover:border-primary has-[:checked]:border-primary has-[:checked]:bg-primary-fixed">
            <input type="radio" name="scope_type" value="individual" checked onchange="toggleScheduleScope()" class="text-primary">
            <span class="text-sm font-medium"><?php echo __('visit_scope_individual'); ?></span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer border border-outline-variant rounded-xl px-3 py-2 hover:border-primary has-[:checked]:border-primary has-[:checked]:bg-primary-fixed">
            <input type="radio" name="scope_type" value="village" onchange="toggleScheduleScope()" class="text-primary">
            <span class="text-sm font-medium"><?php echo __('visit_scope_village'); ?></span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer border border-outline-variant rounded-xl px-3 py-2 hover:border-primary has-[:checked]:border-primary has-[:checked]:bg-primary-fixed">
            <input type="radio" name="scope_type" value="ward" onchange="toggleScheduleScope()" class="text-primary">
            <span class="text-sm font-medium"><?php echo __('visit_scope_ward'); ?></span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer border border-outline-variant rounded-xl px-3 py-2 hover:border-primary has-[:checked]:border-primary has-[:checked]:bg-primary-fixed">
            <input type="radio" name="scope_type" value="crop" onchange="toggleScheduleScope()" class="text-primary">
            <span class="text-sm font-medium"><?php echo __('visit_scope_crop'); ?></span>
          </label>
          <label class="col-span-2 flex items-center gap-2 cursor-pointer border border-outline-variant rounded-xl px-3 py-2 hover:border-primary has-[:checked]:border-primary has-[:checked]:bg-primary-fixed">
            <input type="radio" name="scope_type" value="village_crop" onchange="toggleScheduleScope()" class="text-primary">
            <span class="text-sm font-medium"><?php echo __('visit_scope_village_crop'); ?></span>
          </label>
        </div>
      </div>

      <div id="scopeIndividual">
        <label class="block text-sm font-medium text-on-surface-variant mb-1"><?php echo __('visit_select_farmer'); ?> *</label>
        <select id="farmerSelect" name="farmer_id" onchange="updateModalContext(this)"
                class="w-full bg-surface border border-outline rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary outline-none">
          <option value="">— <?php echo __('visit_select_farmer'); ?> —</option>
          <?php foreach ($farmers as $f): ?>
            <option value="<?php echo $f['id']; ?>" data-name="<?php echo htmlspecialchars($f['name']); ?>">
              <?php echo htmlspecialchars($f['name']); ?> — <?php echo htmlspecialchars($f['phone']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div id="scopeWard" class="hidden">
        <label class="block text-sm font-medium text-on-surface-variant mb-1"><?php echo __('visit_scope_ward'); ?> *</label>
        <select name="target_ward_id" class="w-full bg-surface border border-outline rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary outline-none">
          <option value="">— <?php echo __('visit_scope_ward'); ?> —</option>
          <?php foreach ($wards ?? [] as $w): ?>
            <option value="<?php echo (int)$w['id']; ?>"><?php echo htmlspecialchars($w['name']); ?></option>
          <?php endforeach; ?>
        </select>
        <p class="text-xs text-on-surface-variant mt-1"><?php echo __('visit_group_hint'); ?></p>
      </div>

      <div id="scopeVillage" class="hidden">
        <label class="block text-sm font-medium text-on-surface-variant mb-1"><?php echo __('visit_village'); ?> *</label>
        <select name="target_village_id" class="w-full bg-surface border border-outline rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary outline-none">
          <option value="">— <?php echo __('visit_village'); ?> —</option>
          <?php foreach ($villages ?? [] as $vil): ?>
            <option value="<?php echo (int)$vil['id']; ?>"><?php echo htmlspecialchars($vil['name']); ?></option>
          <?php endforeach; ?>
        </select>
        <p class="text-xs text-on-surface-variant mt-1"><?php echo __('visit_group_hint'); ?></p>
      </div>

      <div id="scopeCrop" class="hidden">
        <label class="block text-sm font-medium text-on-surface-variant mb-1"><?php echo __('visit_crop'); ?> *</label>
        <select name="target_crop_id" class="w-full bg-surface border border-outline rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary outline-none">
          <option value="">— <?php echo __('visit_crop'); ?> —</option>
          <?php foreach ($crops ?? [] as $c): ?>
            <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['name_en'] ?? $c['name_sw']); ?></option>
          <?php endforeach; ?>
        </select>
        <p class="text-xs text-on-surface-variant mt-1"><?php echo __('visit_group_hint'); ?></p>
      </div>
      <div>
        <label class="block text-sm font-medium text-on-surface-variant mb-1"><?php echo __('visit_date'); ?> *</label>
        <input type="date" name="visit_date" required class="w-full bg-surface border border-outline rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary outline-none">
      </div>
      <div>
        <label class="block text-sm font-medium text-on-surface-variant mb-1"><?php echo __('visit_time'); ?></label>
        <select name="time_slot" class="w-full bg-surface border border-outline rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary outline-none">
          <option>Morning (08:00 - 12:00)</option>
          <option>Afternoon (12:00 - 17:00)</option>
          <option>Evening (17:00 - 19:00)</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-on-surface-variant mb-1"><?php echo __('visit_notes'); ?> *</label>
        <textarea name="reason" required rows="3" class="w-full bg-surface border border-outline rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary outline-none resize-none" placeholder="<?php echo __('visit_notes'); ?>..."></textarea>
      </div>
      <input type="hidden" name="scheduled_at" id="scheduledAt">
      <div class="flex gap-3 pt-2">
        <button type="submit" onclick="combineDateTime()" class="flex-1 bg-primary text-white py-3 rounded-xl font-bold hover:bg-primary-container transition-colors"><?php echo __('visit_confirm'); ?></button>
        <button type="button" onclick="document.getElementById('scheduleModal').classList.add('hidden')" class="flex-1 border border-outline py-3 rounded-xl text-on-surface-variant hover:bg-surface-container-low transition-colors font-bold"><?php echo __('cancel'); ?></button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- ─── Visit Feedback Modal ────────────────────────────────────────────────── -->
<?php if (empty($isDao)): ?>
<div id="feedbackModal" class="hidden modal-backdrop">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg modal-panel">
    <div class="p-6 border-b border-outline-variant flex justify-between items-center">
      <h4 class="text-xl font-bold text-on-surface"><?php echo __('visit_feedback'); ?></h4>
      <button onclick="document.getElementById('feedbackModal').classList.add('hidden')">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>
    <form action="/officer/visits/update" method="POST" class="p-6 space-y-5">
      <input type="hidden" name="id" id="feedbackVisitId">
      <div class="bg-surface-container-low rounded-xl p-3">
        <p class="text-xs text-on-surface-variant uppercase tracking-wider mb-0.5"><?php echo __('farmer_name'); ?></p>
        <p class="font-bold text-on-surface" id="feedbackFarmerName"></p>
      </div>

      <div>
        <label class="block text-sm font-bold text-on-surface-variant mb-2"><?php echo __('visit_completed_q'); ?></label>
        <div class="flex gap-4">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="radio" name="status" value="completed" onchange="toggleFeedbackSections()" class="text-primary" required>
            <span class="font-medium"><?php echo __('yes'); ?></span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="radio" name="status" value="cancelled" onchange="toggleFeedbackSections()" class="text-error">
            <span class="font-medium"><?php echo __('no'); ?></span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="radio" name="status" value="postponed" onchange="toggleFeedbackSections()" class="text-amber-600">
            <span class="font-medium"><?php echo __('visit_postponed'); ?></span>
          </label>
        </div>
      </div>

      <div id="postponeSection" class="hidden">
        <label class="block text-sm font-bold text-on-surface-variant mb-2"><?php echo __('visit_reschedule'); ?> *</label>
        <input type="datetime-local" name="scheduled_at" class="w-full bg-surface border border-outline rounded-xl px-4 py-3 focus:ring-2 focus:ring-primary outline-none">
        <textarea name="followup" rows="2" class="mt-2 w-full bg-surface border border-outline rounded-xl px-4 py-3 text-sm" placeholder="<?php echo __('visit_notes'); ?>..."></textarea>
      </div>

      <div id="outcomeSection" class="hidden">
        <label class="block text-sm font-bold text-on-surface-variant mb-2"><?php echo __('visit_outcome_lbl'); ?> *</label>
        <textarea name="outcome" rows="4" class="w-full bg-surface border border-outline rounded-xl px-4 py-3 focus:ring-2 focus:ring-primary outline-none resize-none" placeholder="<?php echo __('visit_outcome_lbl'); ?>..."></textarea>
      </div>

      <div id="notDoneSection" class="hidden">
        <label class="block text-sm font-bold text-on-surface-variant mb-2"><?php echo __('visit_not_done_lbl'); ?> *</label>
        <textarea name="not_done_reason" rows="3" class="w-full bg-surface border border-outline rounded-xl px-4 py-3 focus:ring-2 focus:ring-primary outline-none resize-none" placeholder="<?php echo __('visit_not_done_lbl'); ?>..."></textarea>
      </div>

      <div class="flex gap-3">
        <button type="submit" class="flex-1 bg-primary text-white py-3 rounded-xl font-bold hover:bg-primary-container transition-colors"><?php echo __('visit_submit_fb'); ?></button>
        <button type="button" onclick="document.getElementById('feedbackModal').classList.add('hidden')" class="flex-1 border border-outline py-3 rounded-xl text-on-surface-variant font-bold"><?php echo __('cancel'); ?></button>
      </div>
    </form>
  </div>
</div>

<!-- ─── Follow-up Modal ─────────────────────────────────────────────────────── -->
<div id="followupModal" class="hidden modal-backdrop">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg modal-panel">
    <div class="p-6 border-b border-outline-variant flex justify-between items-center">
      <h4 class="text-xl font-bold text-on-surface"><?php echo __('visit_followup'); ?></h4>
      <button onclick="document.getElementById('followupModal').classList.add('hidden')"><span class="material-symbols-outlined">close</span></button>
    </div>
    <form action="/officer/visits/followup" method="POST" class="p-6 space-y-4">
      <input type="hidden" name="id" id="followupVisitId">
      <textarea name="followup" required rows="5" class="w-full bg-surface border border-outline rounded-xl px-4 py-3 focus:ring-2 focus:ring-primary outline-none resize-none" placeholder="<?php echo __('visit_outcome_lbl'); ?>..."></textarea>
      <button type="submit" class="w-full bg-primary text-white py-3 rounded-xl font-bold"><?php echo __('save_changes'); ?></button>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
function openScheduleModal(farmerId) {
  document.getElementById('scheduleModal').classList.remove('hidden');
  toggleScheduleScope();
  if (farmerId) {
    document.querySelector('[name="scope_type"][value="individual"]').checked = true;
    toggleScheduleScope();
    document.getElementById('farmerSelect').value = farmerId;
    updateModalContext(document.getElementById('farmerSelect'));
  }
}
function toggleScheduleScope() {
  const scope = document.querySelector('[name="scope_type"]:checked')?.value || 'individual';
  document.getElementById('scopeIndividual').classList.toggle('hidden', scope !== 'individual');
  document.getElementById('scopeWard').classList.toggle('hidden', scope !== 'ward');
  document.getElementById('scopeVillage').classList.toggle('hidden', !['village', 'village_crop'].includes(scope));
  document.getElementById('scopeCrop').classList.toggle('hidden', !['crop', 'village_crop'].includes(scope));
  const farmerSel = document.getElementById('farmerSelect');
  if (farmerSel) farmerSel.required = scope === 'individual';
}
function updateModalContext(sel) {
  const opt = sel.options[sel.selectedIndex];
  const el = document.getElementById('modalFarmerName');
  if (el) el.textContent = opt.dataset.name || '';
}
function combineDateTime() {
  const date = document.querySelector('[name="visit_date"]').value;
  const slot  = document.querySelector('[name="time_slot"]').value;
  const time  = slot.includes('08') ? '09:00:00' : slot.includes('12') ? '14:00:00' : '17:00:00';
  if (date) document.getElementById('scheduledAt').value = date + ' ' + time;
}
function openFeedbackModal(visitId, farmerName) {
  document.getElementById('feedbackVisitId').value = visitId;
  document.getElementById('feedbackFarmerName').textContent = farmerName;
  document.getElementById('outcomeSection').classList.add('hidden');
  document.getElementById('notDoneSection').classList.add('hidden');
  document.querySelectorAll('[name="status"]').forEach(r => r.checked = false);
  document.getElementById('feedbackModal').classList.remove('hidden');
}
function toggleFeedbackSections() {
  const val = document.querySelector('[name="status"]:checked')?.value;
  document.getElementById('outcomeSection').classList.toggle('hidden', val !== 'completed');
  document.getElementById('notDoneSection').classList.toggle('hidden', val !== 'cancelled');
  document.getElementById('postponeSection').classList.toggle('hidden', val !== 'postponed');
}
function openFollowupModal(visitId) {
  document.getElementById('followupVisitId').value = visitId;
  document.getElementById('followupModal').classList.remove('hidden');
}
function toggleScheduleAt(requestId) {
  const statusSelect = document.getElementById('status-' + requestId);
  const wrapper = document.getElementById('schedule-at-wrapper-' + requestId);
  if (wrapper) wrapper.classList.toggle('hidden', !['scheduled', 'postponed'].includes(statusSelect.value));
}
<?php if (!empty($_GET['farmer_id'])): ?>
document.addEventListener('DOMContentLoaded', function() {
  openScheduleModal(<?php echo (int)$_GET['farmer_id']; ?>);
});
<?php endif; ?>
</script>
