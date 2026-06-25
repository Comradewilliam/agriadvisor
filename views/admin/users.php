<?php
/**
 * admin/users.php — Full Officer CRUD
 * Admin can: Create DAO/WAO, assign districts/wards, edit, toggle, delete
 */
$db = \App\Core\Database::getInstance()->getConnection();

$officers = $db->query("
    SELECT u.*,
           GROUP_CONCAT(DISTINCT w.name ORDER BY w.name SEPARATOR ', ') AS assigned_wards,
           GROUP_CONCAT(DISTINCT ow.ward_id ORDER BY ow.ward_id SEPARATOR ',') AS ward_id_list,
           GROUP_CONCAT(DISTINCT d2.name ORDER BY d2.name SEPARATOR ', ') AS assigned_districts
    FROM users u
    LEFT JOIN officer_wards ow ON ow.officer_id = u.id
    LEFT JOIN wards w ON w.id = ow.ward_id
    LEFT JOIN officer_districts od ON od.officer_id = u.id
    LEFT JOIN districts d2 ON d2.id = od.district_id
    WHERE u.role IN ('ward_officer','dao')
    GROUP BY u.id
    ORDER BY u.role, u.name
")->fetchAll();

$wards = $db->query("
    SELECT w.id, w.name, d.name AS district_name, d.id AS district_id
    FROM wards w JOIN districts d ON d.id = w.district_id
    ORDER BY d.name, w.name
")->fetchAll();

$districts = $db->query("SELECT id, name FROM districts ORDER BY name")->fetchAll();

$roleLabels = ['ward_officer' => 'Ward Officer (WAO)', 'dao' => 'District Officer (DAO)'];
?>

<div class="p-6 md:p-8 max-w-[1400px] mx-auto">

  <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
    <div>
      <h1 class="text-3xl font-bold text-on-surface">Officer Management</h1>
      <p class="text-on-surface-variant mt-1">Create and manage DAO & Ward Officer accounts, district and ward assignments.</p>
    </div>
    <button onclick="document.getElementById('addOfficerModal').classList.remove('hidden')"
            class="flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-bold hover:bg-primary-container transition-colors shadow">
      <span class="material-symbols-outlined text-lg">person_add</span> New Officer
    </button>
  </div>

  <?php if (isset($_GET['success'])): ?>
  <div class="mb-6 bg-primary-fixed text-primary rounded-xl px-5 py-3 font-bold flex items-center gap-2">
    <span class="material-symbols-outlined">check_circle</span> Action completed successfully.
  </div>
  <?php endif; ?>
  <?php if (isset($_GET['error'])): ?>
  <div class="mb-6 bg-error-container text-on-error-container rounded-xl px-5 py-3 font-bold flex items-center gap-2">
    <span class="material-symbols-outlined">error</span> <?php echo htmlspecialchars($_GET['error']); ?>
  </div>
  <?php endif; ?>

  <!-- Filter Tabs -->
  <div class="flex gap-2 mb-5">
    <button onclick="filterTable('all')" id="tab-all"
            class="px-4 py-2 rounded-xl text-sm font-bold bg-primary text-white transition-colors">All Officers</button>
    <button onclick="filterTable('dao')" id="tab-dao"
            class="px-4 py-2 rounded-xl text-sm font-bold bg-surface-container text-on-surface-variant hover:bg-primary-fixed transition-colors">DAOs</button>
    <button onclick="filterTable('ward_officer')" id="tab-wao"
            class="px-4 py-2 rounded-xl text-sm font-bold bg-surface-container text-on-surface-variant hover:bg-primary-fixed transition-colors">Ward Officers</button>
  </div>

  <!-- Table -->
  <div class="bg-white rounded-2xl border border-outline-variant shadow-sm overflow-hidden">
    <table class="w-full text-left" id="officerTable">
      <thead class="text-xs text-on-surface-variant uppercase bg-surface-container border-b border-outline-variant">
        <tr>
          <th class="p-4">Officer</th>
          <th class="p-4">Role</th>
          <th class="p-4">Email / Phone</th>
          <th class="p-4">Assigned To</th>
          <th class="p-4">Status</th>
          <th class="p-4">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-outline-variant">
        <?php foreach ($officers as $o): ?>
        <tr class="hover:bg-surface-container/50 transition-colors officer-row" data-role="<?php echo $o['role']; ?>">
          <td class="p-4">
            <div class="flex items-center gap-3">
              <img src="<?php echo \App\Helpers\Avatar::url($o['name'], '154212', 36); ?>"
                   class="w-9 h-9 rounded-full" alt="avatar">
              <div>
                <p class="font-bold text-on-surface text-sm"><?php echo htmlspecialchars($o['name']); ?></p>
                <p class="text-xs text-on-surface-variant">ID #<?php echo $o['id']; ?></p>
              </div>
            </div>
          </td>
          <td class="p-4">
            <?php $roleLabel = $roleLabels[$o['role']] ?? $o['role']; ?>
            <span class="text-xs font-bold px-2.5 py-1 rounded-full
              <?php echo $o['role'] === 'dao' ? 'bg-tertiary-fixed text-tertiary' : 'bg-secondary-fixed text-secondary'; ?>">
              <?php echo $roleLabel; ?>
            </span>
          </td>
          <td class="p-4 text-sm">
            <p class="text-on-surface"><?php echo htmlspecialchars($o['email']); ?></p>
            <p class="text-on-surface-variant"><?php echo htmlspecialchars($o['phone'] ?? '—'); ?></p>
          </td>
          <td class="p-4 text-sm text-on-surface-variant max-w-[200px]">
            <?php if ($o['role'] === 'dao'): ?>
              <span class="text-tertiary font-medium"><?php echo htmlspecialchars($o['assigned_districts'] ?? '—'); ?></span>
            <?php else: ?>
              <?php echo htmlspecialchars($o['assigned_wards'] ?? '—'); ?>
            <?php endif; ?>
          </td>
          <td class="p-4">
            <?php if ($o['is_active']): ?>
              <span class="bg-green-50 text-green-700 text-xs px-2.5 py-1 rounded-full font-bold">Active</span>
            <?php else: ?>
              <span class="bg-red-50 text-red-700 text-xs px-2.5 py-1 rounded-full font-bold">Inactive</span>
            <?php endif; ?>
          </td>
          <td class="p-4">
            <div class="flex items-center gap-2">
              <button onclick='openEditModal(<?php echo json_encode($o); ?>)'
                      class="p-1.5 rounded-lg hover:bg-surface-container transition-colors" title="Edit">
                <span class="material-symbols-outlined text-sm text-on-surface-variant">edit</span>
              </button>
              <form action="/admin/users/toggle" method="POST" class="inline">
                <input type="hidden" name="id" value="<?php echo $o['id']; ?>">
                <button type="submit" title="<?php echo $o['is_active'] ? 'Deactivate' : 'Activate'; ?>"
                        class="p-1.5 rounded-lg hover:bg-surface-container transition-colors">
                  <span class="material-symbols-outlined text-sm <?php echo $o['is_active'] ? 'text-error' : 'text-primary'; ?>">
                    <?php echo $o['is_active'] ? 'block' : 'check_circle'; ?>
                  </span>
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (empty($officers)): ?>
    <div class="text-center py-16 text-on-surface-variant">
      <span class="material-symbols-outlined text-5xl mb-3 block">group</span>
      <p class="font-bold">No officers found. Create the first one above.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ── Add Officer Modal ──────────────────────────────────────────────── -->
<div id="addOfficerModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 overflow-y-auto">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg my-4">
    <div class="px-6 py-5 border-b border-outline-variant flex justify-between items-center">
      <h4 class="text-xl font-extrabold text-on-surface">Create New Officer</h4>
      <button onclick="document.getElementById('addOfficerModal').classList.add('hidden')">
        <span class="material-symbols-outlined text-outline">close</span>
      </button>
    </div>
    <form action="/admin/users/create" method="POST" class="p-6 space-y-4">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Full Name *</label>
          <input type="text" name="name" required placeholder="Amani Juma"
                 class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
        </div>
        <div>
          <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Role *</label>
          <select name="role" id="createRole" onchange="toggleAssignmentField()"
                  class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
            <option value="ward_officer">Ward Officer (WAO)</option>
            <option value="dao">District Officer (DAO)</option>
          </select>
        </div>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Email *</label>
          <input type="email" name="email" required placeholder="officer@agriadvisory.co.tz"
                 class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
        </div>
        <div>
          <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Phone</label>
          <input type="tel" name="phone" placeholder="07XX XXX XXX"
                 class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
        </div>
      </div>
      <div>
        <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Password *</label>
        <input type="password" name="password" required placeholder="Minimum 8 characters"
               class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
      </div>

      <!-- Ward assignment (for WAO) -->
      <div id="wardAssignField">
        <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Assign Wards (max 3) *</label>
        <div class="max-h-48 overflow-y-auto border border-outline-variant rounded-xl p-3 space-y-2 bg-surface-container" id="addWardChecks">
          <?php foreach ($wards as $w): ?>
          <label class="flex items-center gap-2 text-sm cursor-pointer">
            <input type="checkbox" name="ward_ids[]" value="<?php echo (int)$w['id']; ?>" class="add-ward-cb rounded">
            <span><?php echo htmlspecialchars($w['district_name'] . ' → ' . $w['name']); ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- District assignment (for DAO) -->
      <div id="districtAssignField" class="hidden">
        <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Assign to District *</label>
        <select name="district_id"
                class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
          <option value="">— Select District —</option>
          <?php foreach ($districts as $d): ?>
          <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="flex gap-3 pt-2">
        <button type="button" onclick="document.getElementById('addOfficerModal').classList.add('hidden')"
                class="flex-1 border border-outline py-3 rounded-xl font-bold text-on-surface-variant hover:bg-surface-container">Cancel</button>
        <button type="submit" class="flex-1 bg-primary text-white py-3 rounded-xl font-bold hover:bg-primary-container">Create Officer</button>
      </div>
    </form>
  </div>
</div>

<!-- ── Edit Officer Modal ─────────────────────────────────────────────── -->
<div id="editOfficerModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 overflow-y-auto">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg my-4">
    <div class="px-6 py-5 border-b border-outline-variant flex justify-between items-center">
      <h4 class="text-xl font-extrabold text-on-surface">Edit Officer</h4>
      <button onclick="document.getElementById('editOfficerModal').classList.add('hidden')">
        <span class="material-symbols-outlined text-outline">close</span>
      </button>
    </div>
    <form action="/admin/users/update" method="POST" class="p-6 space-y-4">
      <input type="hidden" name="id" id="editId">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Full Name *</label>
          <input type="text" name="name" id="editName" required
                 class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
        </div>
        <div>
          <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Email *</label>
          <input type="email" name="email" id="editEmail" required
                 class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
        </div>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Phone</label>
          <input type="tel" name="phone" id="editPhone"
                 class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
        </div>
        <div>
          <label class="block text-sm font-bold text-on-surface-variant mb-1.5">New Password <span class="text-on-surface-variant font-normal">(leave blank to keep)</span></label>
          <input type="password" name="password" placeholder="••••••••"
                 class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
        </div>
      </div>
      <div id="editWardAssignField">
        <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Wards (max 3)</label>
        <div class="max-h-48 overflow-y-auto border border-outline-variant rounded-xl p-3 space-y-2 bg-surface-container" id="editWardChecks">
          <?php foreach ($wards as $w): ?>
          <label class="flex items-center gap-2 text-sm cursor-pointer">
            <input type="checkbox" name="ward_ids[]" value="<?php echo (int)$w['id']; ?>" class="edit-ward-cb rounded" data-ward="<?php echo (int)$w['id']; ?>">
            <span><?php echo htmlspecialchars($w['district_name'] . ' → ' . $w['name']); ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="flex gap-3 pt-2">
        <button type="button" onclick="document.getElementById('editOfficerModal').classList.add('hidden')"
                class="flex-1 border border-outline py-3 rounded-xl font-bold text-on-surface-variant hover:bg-surface-container">Cancel</button>
        <button type="submit" class="flex-1 bg-primary text-white py-3 rounded-xl font-bold hover:bg-primary-container">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function limitWardChecks(selector, max) {
    document.querySelectorAll(selector).forEach(function (cb) {
        cb.addEventListener('change', function () {
            const checked = document.querySelectorAll(selector + ':checked');
            if (checked.length > max) {
                cb.checked = false;
                alert('Afisa anaweza kuwa na kata zisizozidi ' + max + ' tu.');
            }
        });
    });
}
limitWardChecks('.add-ward-cb', 3);
limitWardChecks('.edit-ward-cb', 3);

function toggleAssignmentField() {
    const role = document.getElementById('createRole').value;
    document.getElementById('wardAssignField').classList.toggle('hidden', role !== 'ward_officer');
    document.getElementById('districtAssignField').classList.toggle('hidden', role !== 'dao');
}
function openEditModal(officer) {
    document.getElementById('editId').value = officer.id;
    document.getElementById('editName').value = officer.name;
    document.getElementById('editEmail').value = officer.email;
    document.getElementById('editPhone').value = officer.phone || '';
    const ids = (officer.ward_id_list || '').split(',').filter(Boolean);
    document.querySelectorAll('.edit-ward-cb').forEach(function (cb) {
        cb.checked = ids.includes(cb.value);
    });
    document.getElementById('editOfficerModal').classList.remove('hidden');
}
function filterTable(role) {
    const rows = document.querySelectorAll('.officer-row');
    rows.forEach(r => {
        r.classList.toggle('hidden', role !== 'all' && r.dataset.role !== role);
    });
    ['all','dao','ward_officer'].forEach(r => {
        const tab = document.getElementById('tab-' + (r === 'ward_officer' ? 'wao' : r));
        if (tab) tab.className = (r === role)
            ? 'px-4 py-2 rounded-xl text-sm font-bold bg-primary text-white transition-colors'
            : 'px-4 py-2 rounded-xl text-sm font-bold bg-surface-container text-on-surface-variant hover:bg-primary-fixed transition-colors';
    });
}
</script>
