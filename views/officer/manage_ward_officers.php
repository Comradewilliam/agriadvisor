<?php
/**
 * views/officer/manage_ward_officers.php
 * Data from OfficerController::manageWardOfficers()
 */
$search = $search ?? '';
$letter = $letter ?? '';
$wardFilter = $wardFilter ?? 0;
$myDistricts = $myDistricts ?? [];
$wardOfficers = $wardOfficers ?? [];
$wards = $wards ?? [];
$letters = range('A', 'Z');
$queryBase = '/officer/officers';
?>
<div class="p-6 md:p-8 max-w-[1200px] mx-auto">
  <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
    <div>
      <h1 class="text-3xl font-bold text-on-surface"><?php echo __('officers_title'); ?></h1>
      <p class="text-on-surface-variant mt-1">
        <?php echo __('officers_managing'); ?>
        <strong class="text-primary"><?php echo htmlspecialchars(implode(', ', array_column($myDistricts,'name'))); ?> District</strong>
      </p>
    </div>
    <button onclick="document.getElementById('addWaoModal').classList.remove('hidden')"
            class="flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-bold hover:bg-primary-container shadow">
      <span class="material-symbols-outlined">person_add</span> <?php echo __('new_officer'); ?>
    </button>
  </div>

  <?php if (isset($_GET['success'])): ?>
    <div class="mb-4 bg-primary-fixed text-primary rounded-xl px-5 py-3 font-bold"><?php echo __('success'); ?></div>
  <?php endif; ?>
  <?php if (isset($_GET['error'])): ?>
    <div class="mb-4 bg-error-container text-error rounded-xl px-5 py-3 font-bold"><?php echo htmlspecialchars($_GET['error']); ?></div>
  <?php endif; ?>

  <!-- Filters -->
  <form method="GET" action="<?php echo $queryBase; ?>" class="mb-6 space-y-4">
    <div class="flex flex-wrap gap-3 items-center">
      <div class="flex items-center gap-2 bg-white border border-outline-variant rounded-xl px-4 py-2 flex-1 min-w-[200px] max-w-md shadow-sm">
        <span class="material-symbols-outlined text-on-surface-variant text-base">search</span>
        <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search name, email, phone…" class="bg-transparent outline-none text-sm flex-1">
      </div>
      <select name="ward_id" onchange="this.form.submit()" class="border border-outline-variant rounded-xl px-3 py-2 text-sm bg-white">
        <option value="">All wards</option>
        <?php foreach ($wards as $w): ?>
          <option value="<?php echo (int)$w['id']; ?>" <?php echo $wardFilter === (int)$w['id'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($w['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php if ($search || $letter || $wardFilter): ?>
        <a href="<?php echo $queryBase; ?>" class="text-sm font-bold text-primary hover:underline">Clear filters</a>
      <?php endif; ?>
    </div>
    <div class="flex flex-wrap gap-1">
      <a href="<?php echo $queryBase . ($wardFilter ? '?ward_id=' . $wardFilter : ''); ?>"
         class="text-xs font-bold px-2.5 py-1 rounded-lg <?php echo $letter === '' ? 'bg-primary text-white' : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high'; ?>">All</a>
      <?php foreach ($letters as $L): ?>
        <?php $href = $queryBase . '?letter=' . $L . ($wardFilter ? '&ward_id=' . $wardFilter : '') . ($search ? '&q=' . urlencode($search) : ''); ?>
        <a href="<?php echo $href; ?>"
           class="text-xs font-bold w-7 h-7 flex items-center justify-center rounded-lg <?php echo $letter === $L ? 'bg-primary text-white' : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high'; ?>">
          <?php echo $L; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </form>

  <!-- Officers Table -->
  <div class="bg-white rounded-2xl border border-outline-variant shadow-sm overflow-hidden">
    <table class="w-full text-left">
      <thead class="text-xs uppercase text-on-surface-variant bg-surface-container border-b border-outline-variant">
        <tr>
          <th class="p-4"><?php echo __('officer'); ?></th>
          <th class="p-4"><?php echo __('email'); ?> / <?php echo __('phone'); ?></th>
          <th class="p-4"><?php echo __('working_office'); ?></th>
          <th class="p-4"><?php echo __('assigned_wards'); ?></th>
          <th class="p-4"><?php echo __('status'); ?></th>
          <th class="p-4"><?php echo __('actions'); ?></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-outline-variant">
        <?php foreach ($wardOfficers as $o): ?>
        <tr class="hover:bg-surface-container/40 transition-colors">
          <td class="p-4">
            <div class="flex items-center gap-3">
              <a href="/officer/officers/view?id=<?php echo (int)$o['id']; ?>" class="flex items-center gap-3 hover:opacity-80">
                <img src="<?php echo \App\Helpers\Avatar::url($o['name'], '77574d', 36); ?>" class="w-9 h-9 rounded-full" alt="">
                <p class="font-bold text-sm text-on-surface"><?php echo htmlspecialchars($o['name']); ?></p>
              </a>
            </div>
          </td>
          <td class="p-4 text-sm">
            <p><?php echo htmlspecialchars($o['email']); ?></p>
            <p class="text-on-surface-variant"><?php echo htmlspecialchars($o['phone'] ?? '—'); ?></p>
          </td>
          <td class="p-4 text-sm text-on-surface-variant"><?php echo htmlspecialchars($o['working_office'] ?? '—'); ?></td>
          <td class="p-4 text-sm text-on-surface-variant">
            <?php if ($o['assigned_wards']): ?>
              <?php foreach (explode(', ', $o['assigned_wards']) as $wn): ?>
                <span class="inline-block bg-primary-fixed text-primary text-xs font-bold px-2 py-0.5 rounded-full mr-1 mb-1"><?php echo htmlspecialchars($wn); ?></span>
              <?php endforeach; ?>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td class="p-4">
            <span class="text-xs font-bold px-2.5 py-1 rounded-full <?php echo $o['is_active'] ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'; ?>">
              <?php echo $o['is_active'] ? __('active') : __('inactive'); ?>
            </span>
          </td>
          <td class="p-4">
            <div class="flex gap-2">
              <a href="/officer/officers/view?id=<?php echo (int)$o['id']; ?>" class="p-1.5 rounded-lg hover:bg-surface-container" title="View profile">
                <span class="material-symbols-outlined text-sm text-primary">visibility</span>
              </a>
              <button onclick='openEditWaoModal(<?php echo json_encode($o); ?>)'
                      class="p-1.5 rounded-lg hover:bg-surface-container" title="<?php echo __('edit'); ?>">
                <span class="material-symbols-outlined text-sm text-on-surface-variant">edit</span>
              </button>
              <form action="/officer/officers/toggle" method="POST" class="inline">
                <input type="hidden" name="id" value="<?php echo $o['id']; ?>">
                <button type="submit" class="p-1.5 rounded-lg hover:bg-surface-container"
                        title="<?php echo $o['is_active'] ? __('deactivate') : __('activate'); ?>">
                  <span class="material-symbols-outlined text-sm <?php echo $o['is_active'] ? 'text-error' : 'text-primary'; ?>">
                    <?php echo $o['is_active'] ? 'block' : 'check_circle'; ?>
                  </span>
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($wardOfficers)): ?>
        <tr><td colspan="6" class="text-center py-16 text-on-surface-variant">
          <span class="material-symbols-outlined text-5xl mb-3 block">person_search</span>
          <p class="font-bold"><?php echo __('no_officers'); ?></p>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── Add WAO Modal ─────────────────────────────────────────────────────────── -->
<div id="addWaoModal" class="hidden modal-backdrop">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg modal-panel">
    <div class="px-6 py-5 border-b border-outline-variant flex justify-between items-center">
      <h4 class="text-xl font-extrabold"><?php echo __('create_officer'); ?></h4>
      <button onclick="document.getElementById('addWaoModal').classList.add('hidden')">
        <span class="material-symbols-outlined text-outline">close</span>
      </button>
    </div>
    <form action="/officer/officers/create" method="POST" class="p-6 space-y-4">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-bold text-on-surface-variant mb-1.5"><?php echo __('full_name'); ?> *</label>
          <input type="text" name="name" required class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
        </div>
        <div>
          <label class="block text-sm font-bold text-on-surface-variant mb-1.5"><?php echo __('email'); ?> *</label>
          <input type="email" name="email" required class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
        </div>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-bold text-on-surface-variant mb-1.5"><?php echo __('phone'); ?></label>
          <input type="tel" name="phone" class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
        </div>
        <div>
          <label class="block text-sm font-bold text-on-surface-variant mb-1.5"><?php echo __('password'); ?> *</label>
          <input type="password" name="password" required class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
        </div>
      </div>
      <div>
        <label class="block text-sm font-bold text-on-surface-variant mb-1.5"><?php echo __('working_office'); ?></label>
        <input type="text" name="working_office" placeholder="e.g. Kilosa Ward Office" class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
      </div>
      <div>
        <label class="block text-sm font-bold text-on-surface-variant mb-1.5"><?php echo __('assign_wards'); ?></label>
        <p class="text-xs text-on-surface-variant mb-2" id="addWardCount">0/3 <?php echo __('assigned_wards'); ?></p>
        <div class="space-y-2 max-h-44 overflow-y-auto border border-outline-variant rounded-xl p-3">
          <?php foreach ($wards as $w): ?>
          <label class="flex items-center gap-2 cursor-pointer hover:bg-surface-container-low rounded-lg px-2 py-1">
            <input type="checkbox" name="ward_ids[]" value="<?php echo $w['id']; ?>"
                   class="ward-check-add w-4 h-4 text-primary rounded border-outline-variant focus:ring-primary"
                   onchange="limitWardChecks('add',3)">
            <span class="text-sm text-on-surface"><?php echo htmlspecialchars($w['district_name'].' → '.$w['name']); ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="flex gap-3 pt-2">
        <button type="button" onclick="document.getElementById('addWaoModal').classList.add('hidden')"
                class="flex-1 border border-outline py-3 rounded-xl font-bold text-on-surface-variant"><?php echo __('cancel'); ?></button>
        <button type="submit" class="flex-1 bg-primary text-white py-3 rounded-xl font-bold"><?php echo __('create_btn'); ?></button>
      </div>
    </form>
  </div>
</div>

<!-- ── Edit WAO Modal ─────────────────────────────────────────────────────────── -->
<div id="editWaoModal" class="hidden modal-backdrop">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg modal-panel">
    <div class="px-6 py-5 border-b border-outline-variant flex justify-between items-center">
      <h4 class="text-xl font-extrabold"><?php echo __('edit_officer'); ?></h4>
      <button onclick="document.getElementById('editWaoModal').classList.add('hidden')">
        <span class="material-symbols-outlined text-outline">close</span>
      </button>
    </div>
    <form action="/officer/officers/update" method="POST" class="p-6 space-y-4">
      <input type="hidden" name="id" id="editWaoId">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-bold text-on-surface-variant mb-1.5"><?php echo __('full_name'); ?> *</label>
          <input type="text" name="name" id="editWaoName" required class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
        </div>
        <div>
          <label class="block text-sm font-bold text-on-surface-variant mb-1.5"><?php echo __('email'); ?> *</label>
          <input type="email" name="email" id="editWaoEmail" required class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
        </div>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-bold text-on-surface-variant mb-1.5"><?php echo __('phone'); ?></label>
          <input type="tel" name="phone" id="editWaoPhone" class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
        </div>
        <div>
          <label class="block text-sm font-bold text-on-surface-variant mb-1.5"><?php echo __('new_password'); ?></label>
          <input type="password" name="password" placeholder="<?php echo __('new_password'); ?>" class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
        </div>
      </div>
      <div>
        <label class="block text-sm font-bold text-on-surface-variant mb-1.5"><?php echo __('working_office'); ?></label>
        <input type="text" name="working_office" id="editWaoOffice" class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
      </div>
      <div>
        <label class="block text-sm font-bold text-on-surface-variant mb-1.5"><?php echo __('assign_wards'); ?></label>
        <p class="text-xs text-on-surface-variant mb-2" id="editWardCount">0/3 <?php echo __('assigned_wards'); ?></p>
        <div class="space-y-2 max-h-44 overflow-y-auto border border-outline-variant rounded-xl p-3" id="editWardList">
          <?php foreach ($wards as $w): ?>
          <label class="flex items-center gap-2 cursor-pointer hover:bg-surface-container-low rounded-lg px-2 py-1">
            <input type="checkbox" name="ward_ids[]" value="<?php echo $w['id']; ?>"
                   class="ward-check-edit w-4 h-4 text-primary rounded border-outline-variant focus:ring-primary"
                   onchange="limitWardChecks('edit',3)">
            <span class="text-sm text-on-surface"><?php echo htmlspecialchars($w['district_name'].' → '.$w['name']); ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="flex gap-3 pt-2">
        <button type="button" onclick="document.getElementById('editWaoModal').classList.add('hidden')"
                class="flex-1 border border-outline py-3 rounded-xl font-bold text-on-surface-variant"><?php echo __('cancel'); ?></button>
        <button type="submit" class="flex-1 bg-primary text-white py-3 rounded-xl font-bold"><?php echo __('save_changes'); ?></button>
      </div>
    </form>
  </div>
</div>

<script>
function limitWardChecks(prefix, max) {
    const boxes   = document.querySelectorAll('.ward-check-' + prefix);
    const checked = [...boxes].filter(b => b.checked);
    const counter = document.getElementById(prefix === 'add' ? 'addWardCount' : 'editWardCount');
    counter.textContent = checked.length + '/' + max + ' <?php echo __('assigned_wards'); ?>';
    if (checked.length >= max) {
        boxes.forEach(b => { if (!b.checked) b.disabled = true; });
    } else {
        boxes.forEach(b => b.disabled = false);
    }
}

function openEditWaoModal(o) {
    document.getElementById('editWaoId').value    = o.id;
    document.getElementById('editWaoName').value  = o.name    || '';
    document.getElementById('editWaoEmail').value = o.email   || '';
    document.getElementById('editWaoPhone').value = o.phone   || '';
    document.getElementById('editWaoOffice').value= o.working_office || '';

    // Pre-tick assigned wards
    const assignedIds = (o.assigned_ward_ids || '').split(',').map(s => s.trim());
    document.querySelectorAll('.ward-check-edit').forEach(cb => {
        cb.checked  = assignedIds.includes(cb.value);
        cb.disabled = false;
    });
    limitWardChecks('edit', 3);

    document.getElementById('editWaoModal').classList.remove('hidden');
}

<?php if (!empty($editOfficer)): ?>
document.addEventListener('DOMContentLoaded', function() {
    openEditWaoModal(<?php echo json_encode($editOfficer); ?>);
});
<?php endif; ?>
</script>
