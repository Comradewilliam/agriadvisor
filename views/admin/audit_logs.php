<?php
$totalEvents = (int)($totalAll ?? count($logs));
$securityCount = (int)($securityAlerts ?? 0);
$sensitiveCount = (int)($sensitiveCount ?? 0);
$todayCount = (int)($todayCount ?? 0);

function auditImpactLevel(string $action): array {
    $high = ['delete_officer', 'delete_ward', 'delete_farmer', 'login_failed'];
    $mod = ['create_officer', 'update_officer', 'toggle_officer_status', 'create_ward', 'update_ward'];
    if (in_array($action, $high, true) || str_contains($action, 'delete')) {
        return ['High', 'bg-error-container text-error', 'warning'];
    }
    if (in_array($action, $mod, true) || str_contains($action, 'toggle') || str_contains($action, 'create_')) {
        return ['Moderate', 'bg-tertiary-fixed text-tertiary', 'admin_panel_settings'];
    }
    return ['Low', 'bg-surface-container text-on-surface-variant', 'info'];
}
?>

<div class="p-6 md:p-8 max-w-6xl mx-auto">
  <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
      <h2 class="text-3xl font-bold text-on-surface">System Audit Logs</h2>
      <p class="text-on-surface-variant mt-1">Full administrative trail — officer changes, district edits, and security events.</p>
    </div>
    <div class="flex gap-3">
      <a href="/admin/audit_logs/export?format=pdf" class="flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-xl text-sm font-bold hover:bg-primary-container transition-colors shadow-sm">
        <span class="material-symbols-outlined text-base">picture_as_pdf</span> Export PDF
      </a>
      <a href="/admin/audit_logs/export?format=csv" class="flex items-center gap-2 border border-outline-variant bg-white text-on-surface px-4 py-2 rounded-xl text-sm font-bold hover:bg-surface-container transition-colors shadow-sm">
        <span class="material-symbols-outlined text-base">download</span> Export CSV
      </a>
    </div>
  </div>

  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-surface-container-low rounded-2xl border border-outline-variant p-4">
      <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-0.5">Total events</p>
      <p class="text-2xl font-extrabold text-on-surface"><?php echo number_format($totalEvents); ?></p>
    </div>
    <div class="bg-surface-container-low rounded-2xl border border-outline-variant p-4">
      <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-0.5">Today</p>
      <p class="text-2xl font-extrabold text-primary"><?php echo number_format($todayCount); ?></p>
    </div>
    <div class="bg-surface-container-low rounded-2xl border border-outline-variant p-4">
      <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-0.5">Security alerts</p>
      <p class="text-2xl font-extrabold text-error"><?php echo number_format($securityCount); ?></p>
    </div>
    <div class="bg-surface-container-low rounded-2xl border border-outline-variant p-4">
      <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-0.5">Sensitive changes</p>
      <p class="text-2xl font-extrabold text-on-surface"><?php echo number_format($sensitiveCount); ?></p>
    </div>
  </div>

  <?php if (!empty($actorBreakdown)): ?>
  <div class="mb-6 bg-white rounded-2xl border border-outline-variant p-4">
    <p class="text-xs font-bold text-on-surface-variant uppercase mb-2">Activity by role</p>
    <div class="flex flex-wrap gap-3">
      <?php foreach ($actorBreakdown as $ab): ?>
        <span class="text-sm bg-surface-container px-3 py-1 rounded-full">
          <strong><?php echo htmlspecialchars($ab['role']); ?></strong>: <?php echo (int)$ab['cnt']; ?>
        </span>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <form method="GET" action="/admin/audit_logs" class="mb-4 flex flex-wrap gap-3 items-center bg-white rounded-2xl border border-outline-variant p-4">
    <div class="flex items-center gap-2 flex-1 min-w-[200px]">
      <span class="material-symbols-outlined text-on-surface-variant">search</span>
      <input type="text" name="q" value="<?php echo htmlspecialchars($search ?? ''); ?>" placeholder="Search actor, action, entity…" class="bg-transparent outline-none text-sm flex-1">
    </div>
    <select name="action" class="border border-outline-variant rounded-xl px-3 py-2 text-sm">
      <option value="">All actions</option>
      <?php foreach ($actionTypes ?? [] as $act): ?>
        <option value="<?php echo htmlspecialchars($act); ?>" <?php echo ($actionFilter ?? '') === $act ? 'selected' : ''; ?>><?php echo htmlspecialchars($act); ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="bg-primary text-white px-4 py-2 rounded-xl text-sm font-bold">Filter</button>
    <?php if (!empty($search) || !empty($actionFilter)): ?>
      <a href="/admin/audit_logs" class="text-sm font-bold text-primary">Clear</a>
    <?php endif; ?>
  </form>

  <div class="bg-white rounded-2xl shadow-sm border border-outline-variant overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-surface-container-low text-xs text-on-surface-variant uppercase border-b border-outline-variant">
          <tr>
            <th class="p-4 text-left font-bold">Timestamp</th>
            <th class="p-4 text-left font-bold">Actor</th>
            <th class="p-4 text-left font-bold">Action</th>
            <th class="p-4 text-left font-bold">Entity</th>
            <th class="p-4 text-left font-bold">Impact</th>
            <th class="p-4 text-center font-bold">Details</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-outline-variant">
          <?php if (empty($logs)): ?>
            <tr><td colspan="6" class="p-8 text-center text-on-surface-variant">No logs match your filters.</td></tr>
          <?php else: foreach ($logs as $l):
            [$impactLabel, $impactClass, $impactIcon] = auditImpactLevel($l['action'] ?? '');
            $metaRaw = $l['meta'] ?? '{}';
            if (is_array($metaRaw)) $metaRaw = json_encode($metaRaw);
          ?>
            <tr class="hover:bg-surface-container-lowest transition-colors">
              <td class="p-4">
                <p class="text-on-surface font-medium"><?php echo date('M d, Y', strtotime($l['created_at'])); ?></p>
                <p class="text-xs text-outline"><?php echo date('H:i:s', strtotime($l['created_at'])); ?></p>
              </td>
              <td class="p-4">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center shrink-0">
                    <?php echo strtoupper(substr($l['actor_name'] ?? 'S', 0, 2)); ?>
                  </div>
                  <div>
                    <p class="font-bold text-on-surface text-sm"><?php echo htmlspecialchars($l['actor_name'] ?? 'System'); ?></p>
                    <p class="text-[10px] text-on-surface-variant uppercase"><?php echo htmlspecialchars($l['actor_role'] ?? 'system'); ?></p>
                  </div>
                </div>
              </td>
              <td class="p-4">
                <span class="text-[10px] font-extrabold tracking-wider px-2 py-1 rounded-md uppercase bg-surface-container text-on-surface-variant"><?php echo htmlspecialchars($l['action']); ?></span>
              </td>
              <td class="p-4 text-on-surface-variant text-xs">
                <?php echo htmlspecialchars(($l['entity_type'] ?? '—') . ' #' . ($l['entity_id'] ?? '—')); ?>
              </td>
              <td class="p-4">
                <span class="text-xs font-bold px-2 py-1 rounded-full <?php echo $impactClass; ?> flex items-center gap-1 w-fit">
                  <span class="material-symbols-outlined text-sm"><?php echo $impactIcon; ?></span><?php echo $impactLabel; ?>
                </span>
              </td>
              <td class="p-4 text-center">
                <button type="button" onclick="showAuditMeta(this)" data-meta="<?php echo htmlspecialchars($metaRaw, ENT_QUOTES); ?>"
                        class="w-8 h-8 rounded-lg text-on-surface-variant hover:bg-surface-container hover:text-primary transition-colors flex items-center justify-center mx-auto">
                  <span class="material-symbols-outlined text-base">code</span>
                </button>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div id="auditMetaModal" class="hidden modal-backdrop">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg modal-panel p-6">
    <div class="flex justify-between items-center mb-4">
      <h4 class="font-bold text-lg">Event metadata</h4>
      <button type="button" onclick="document.getElementById('auditMetaModal').classList.add('hidden')"><span class="material-symbols-outlined">close</span></button>
    </div>
    <pre id="auditMetaBody" class="text-xs bg-surface-container p-4 rounded-xl overflow-x-auto whitespace-pre-wrap"></pre>
  </div>
</div>

<script>
function showAuditMeta(btn) {
  const raw = btn.getAttribute('data-meta') || '{}';
  let formatted = raw;
  try { formatted = JSON.stringify(JSON.parse(raw), null, 2); } catch (e) {}
  document.getElementById('auditMetaBody').textContent = formatted;
  document.getElementById('auditMetaModal').classList.remove('hidden');
}
</script>
