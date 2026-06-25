<?php
/**
 * views/officer/dashboard.php  v1.4
 * – WAO: ward-scoped farmers, visits, KPIs
 * – DAO: district-wide farmers (no Add Farmer quick action), all visit plans
 * – Recent farmers capped at 5
 * – Visit plans panel with done/not-done status & outcome
 */
$isDao = ($officer['role'] === 'dao');
?>
<div class="p-6 md:p-8">
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h3 class="text-3xl font-bold text-primary"><?php echo __('dashboard_title'); ?></h3>
            <p class="text-on-surface-variant"><?php echo __('dashboard_welcome'); ?> <?php echo htmlspecialchars($officer['name']); ?>.</p>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-8">
        <div class="glass-card p-5 rounded-xl border border-outline-variant">
            <div class="flex items-center gap-3 mb-2">
                <span class="material-symbols-outlined text-primary">group</span>
                <p class="text-xs text-on-surface-variant uppercase tracking-wider"><?php echo __('kpi_farmers'); ?></p>
            </div>
            <p class="text-3xl font-extrabold text-primary"><?php echo number_format($farmerCount); ?></p>
        </div>
        <div class="glass-card p-5 rounded-xl border border-outline-variant">
            <div class="flex items-center gap-3 mb-2">
                <span class="material-symbols-outlined text-error">chat_error</span>
                <p class="text-xs text-on-surface-variant uppercase tracking-wider"><?php echo __('kpi_questions'); ?></p>
            </div>
            <p class="text-3xl font-extrabold text-error"><?php echo $pendingEsc; ?></p>
        </div>
        <div class="glass-card p-5 rounded-xl border border-outline-variant">
            <div class="flex items-center gap-3 mb-2">
                <span class="material-symbols-outlined text-tertiary">calendar_month</span>
                <p class="text-xs text-on-surface-variant uppercase tracking-wider"><?php echo __('kpi_visits'); ?></p>
            </div>
            <p class="text-3xl font-extrabold text-tertiary"><?php echo $upcomingVisits; ?></p>
        </div>
        <div class="glass-card p-5 rounded-xl border border-outline-variant">
            <div class="flex items-center gap-3 mb-2">
                <span class="material-symbols-outlined text-secondary">thunderstorm</span>
                <p class="text-xs text-on-surface-variant uppercase tracking-wider"><?php echo __('kpi_alerts'); ?></p>
            </div>
            <p class="text-3xl font-extrabold text-secondary"><?php echo $activeAlerts; ?></p>
        </div>
    </div>

    <!-- Quick actions (DAO cannot add farmers) -->
    <div class="grid grid-cols-2 md:grid-cols-<?php echo $isDao ? '3' : '4'; ?> gap-4 mb-8">
        <?php if (!$isDao): ?>
        <a href="/officer/farmers/add" class="flex flex-col items-center justify-center gap-2 bg-primary text-white rounded-xl p-4 hover:bg-primary-container transition-colors font-bold text-sm">
            <span class="material-symbols-outlined text-3xl">person_add</span> <?php echo __('add_farmer'); ?>
        </a>
        <?php endif; ?>
        <a href="/officer/escalations" class="flex flex-col items-center justify-center gap-2 bg-error text-white rounded-xl p-4 hover:opacity-90 transition-opacity font-bold text-sm">
            <span class="material-symbols-outlined text-3xl">mark_email_unread</span> <?php echo __('answer_queries'); ?>
        </a>
        <a href="/officer/visits" onclick="event.preventDefault();document.getElementById('visitModal').classList.remove('hidden')"
           class="flex flex-col items-center justify-center gap-2 bg-tertiary text-white rounded-xl p-4 hover:opacity-90 transition-opacity font-bold text-sm">
            <span class="material-symbols-outlined text-3xl">add_location_alt</span> <?php echo __('plan_visit'); ?>
        </a>
        <a href="/officer/ai-mentorship" class="flex flex-col items-center justify-center gap-2 bg-secondary text-white rounded-xl p-4 hover:opacity-90 transition-opacity font-bold text-sm">
            <span class="material-symbols-outlined text-3xl">psychology</span> <?php echo __('ai_training'); ?>
        </a>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

    <!-- Visit Plans Panel -->
    <div class="bg-white rounded-xl shadow-sm border border-outline-variant overflow-hidden">
        <div class="p-5 bg-surface-container-low border-b border-outline-variant flex justify-between items-center">
            <h4 class="text-lg font-bold text-on-surface"><?php echo __('visit_plans'); ?></h4>
            <a href="/officer/visits" class="text-primary text-sm font-bold hover:underline"><?php echo __('view_all'); ?></a>
        </div>
        <?php if (empty($recentVisits)): ?>
            <div class="p-8 text-center text-outline">
                <span class="material-symbols-outlined text-3xl block mb-2">event_busy</span>
                <?php echo __('no_visits'); ?>
            </div>
        <?php else: ?>
        <div class="divide-y divide-outline-variant">
            <?php foreach ($recentVisits as $v):
                $statusMap = [
                    'completed' => ['label' => __('visit_done'),     'cls' => 'bg-primary-fixed text-primary'],
                    'scheduled' => ['label' => __('visit_pending'),   'cls' => 'bg-tertiary-fixed text-tertiary'],
                    'cancelled' => ['label' => __('visit_not_done'), 'cls' => 'bg-error-container text-error'],
                    'pending'   => ['label' => __('visit_pending'),   'cls' => 'bg-secondary-fixed text-secondary'],
                ];
                $st = $statusMap[$v['status']] ?? ['label' => ucfirst($v['status']), 'cls' => 'bg-surface-container text-outline'];
                $typeLabel = ($v['visit_type'] ?? 'officer_planned') === 'farmer_requested'
                    ? __('requested_by_farmer') : __('planned_by_officer');
                $typeCls   = ($v['visit_type'] ?? '') === 'farmer_requested'
                    ? 'bg-secondary-fixed text-secondary' : 'bg-surface-container text-on-surface-variant';
            ?>
            <div class="p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-on-surface truncate"><?php echo htmlspecialchars($v['farmer_name']); ?></p>
                        <p class="text-xs text-on-surface-variant mt-0.5">
                            <?php echo date('d M Y', strtotime($v['scheduled_at'])); ?> &bull;
                            <?php echo htmlspecialchars($v['village_name'] ?? '—'); ?>
                        </p>
                        <?php if (!empty($v['outcome'])): ?>
                        <p class="text-xs text-primary mt-1 bg-primary-fixed/40 rounded px-2 py-1">
                            <strong><?php echo __('visit_outcome'); ?>:</strong> <?php echo htmlspecialchars(mb_substr($v['outcome'], 0, 100)); ?>
                        </p>
                        <?php elseif (!empty($v['not_done_reason'])): ?>
                        <p class="text-xs text-error mt-1 bg-error-container/40 rounded px-2 py-1">
                            <strong><?php echo __('visit_not_done'); ?>:</strong> <?php echo htmlspecialchars(mb_substr($v['not_done_reason'], 0, 100)); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <div class="flex flex-col items-end gap-1 shrink-0">
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full <?php echo $st['cls']; ?>"><?php echo $st['label']; ?></span>
                        <span class="text-xs px-2 py-0.5 rounded-full <?php echo $typeCls; ?>"><?php echo $typeLabel; ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Recent Farmers (max 5) -->
    <div class="bg-white rounded-xl shadow-sm border border-outline-variant overflow-hidden">
        <div class="p-5 bg-surface-container-low border-b border-outline-variant flex justify-between">
            <h4 class="text-lg font-bold text-on-surface"><?php echo __('recent_farmers'); ?></h4>
            <a href="/officer/farmers" class="text-primary text-sm font-bold hover:underline"><?php echo __('view_all'); ?></a>
        </div>
        <table class="w-full text-left">
            <thead class="text-xs text-on-surface-variant uppercase bg-surface-container">
                <tr>
                    <th class="p-4"><?php echo __('farmer_name'); ?></th>
                    <th class="p-4"><?php echo __('farmer_phone'); ?></th>
                    <th class="p-4"><?php echo __('farmer_village'); ?></th>
                    <th class="p-4"><?php echo __('farmer_date'); ?></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                <?php if (empty($recentFarmers)): ?>
                    <tr><td colspan="4" class="p-6 text-center text-outline"><?php echo __('no_farmers'); ?></td></tr>
                <?php else: ?>
                    <?php foreach (array_slice($recentFarmers, 0, 5) as $f): ?>
                        <tr class="hover:bg-surface-container-lowest">
                            <td class="p-4 font-bold">
                                <a href="/officer/farmers/view?id=<?php echo $f['id']; ?>" class="text-primary hover:underline">
                                    <?php echo htmlspecialchars($f['name']); ?>
                                </a>
                            </td>
                            <td class="p-4 text-on-surface-variant"><?php echo htmlspecialchars($f['phone']); ?></td>
                            <td class="p-4 text-on-surface-variant"><?php echo htmlspecialchars($f['village_name'] ?? '—'); ?></td>
                            <td class="p-4 text-xs text-outline"><?php echo date('d M Y', strtotime($f['registered_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    </div><!-- /grid -->
</div>

<!-- Quick Plan Visit Modal (simplified) -->
<div id="visitModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
    <div class="p-5 border-b border-outline-variant flex justify-between items-center">
      <h4 class="text-lg font-bold"><?php echo __('visit_schedule'); ?></h4>
      <button onclick="document.getElementById('visitModal').classList.add('hidden')">
        <span class="material-symbols-outlined text-outline">close</span>
      </button>
    </div>
    <div class="p-5">
      <p class="text-on-surface-variant text-sm mb-4"><?php echo __('visits_subtitle'); ?></p>
      <a href="/officer/visits" class="w-full bg-primary text-white font-bold py-3 rounded-xl flex items-center justify-center gap-2 hover:bg-primary-container transition-colors">
        <span class="material-symbols-outlined">calendar_month</span>
        <?php echo __('nav_visits'); ?>
      </a>
    </div>
  </div>
</div>
