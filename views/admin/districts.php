<?php
/**
 * admin/districts.php
 * Admin: Manage Districts → Wards → Villages (full CRUD hierarchy)
 */
$db = \App\Core\Database::getInstance()->getConnection();

$districts = $db->query("
    SELECT d.*, COUNT(DISTINCT w.id) AS ward_count
    FROM districts d
    LEFT JOIN wards w ON w.district_id = d.id
    GROUP BY d.id
    ORDER BY d.name ASC
")->fetchAll();

// Load wards with village count for expanded view
$wards = $db->query("
    SELECT w.*, d.name AS district_name, COUNT(DISTINCT v.id) AS village_count
    FROM wards w
    JOIN districts d ON d.id = w.district_id
    LEFT JOIN villages v ON v.ward_id = w.id
    GROUP BY w.id
    ORDER BY d.name, w.name
")->fetchAll();
?>

<div class="p-6 md:p-8 max-w-[1400px] mx-auto">

  <!-- Header -->
  <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
    <div>
      <h1 class="text-3xl font-bold text-on-surface">Districts & Wards</h1>
      <p class="text-on-surface-variant mt-1">Manage geographical hierarchy: Districts → Wards → Villages</p>
    </div>
    <button onclick="document.getElementById('addDistrictModal').classList.remove('hidden')"
            class="flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-bold hover:bg-primary-container transition-colors shadow">
      <span class="material-symbols-outlined text-lg">add_location_alt</span> Add District
    </button>
  </div>

  <!-- Districts Grid -->
  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-10">
    <?php foreach ($districts as $d): ?>
    <div class="bg-white rounded-2xl border border-outline-variant shadow-sm overflow-hidden">
      <div class="bg-primary/5 border-b border-outline-variant px-5 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-primary text-white rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-lg">location_city</span>
          </div>
          <div>
            <h3 class="font-extrabold text-on-surface"><?php echo htmlspecialchars($d['name']); ?></h3>
            <p class="text-xs text-on-surface-variant"><?php echo htmlspecialchars($d['region']); ?> Region</p>
          </div>
        </div>
        <div class="flex gap-2">
          <button onclick="openAddWardModal(<?php echo $d['id']; ?>, '<?php echo htmlspecialchars($d['name']); ?>')"
                  class="p-2 rounded-lg bg-surface-container hover:bg-primary-fixed transition-colors" title="Add Ward">
            <span class="material-symbols-outlined text-sm text-primary">add</span>
          </button>
          <button onclick="editDistrict(<?php echo $d['id']; ?>, '<?php echo htmlspecialchars($d['name']); ?>', '<?php echo htmlspecialchars($d['region']); ?>')"
                  class="p-2 rounded-lg bg-surface-container hover:bg-surface-container-high transition-colors">
            <span class="material-symbols-outlined text-sm text-on-surface-variant">edit</span>
          </button>
        </div>
      </div>
      <div class="p-4">
        <p class="text-sm text-on-surface-variant mb-3">
          <span class="font-bold text-on-surface"><?php echo $d['ward_count']; ?></span> wards
          <span class="text-outline mx-1">|</span>
          Status: <span class="font-semibold <?php echo $d['is_active'] ? 'text-primary' : 'text-error'; ?>">
            <?php echo $d['is_active'] ? 'Active' : 'Inactive'; ?>
          </span>
        </p>
        <!-- Wards list -->
        <div class="space-y-1.5 max-h-48 overflow-y-auto">
          <?php
          $districtWards = array_filter($wards, fn($w) => $w['district_name'] === $d['name']);
          foreach ($districtWards as $w):
          ?>
          <div class="flex items-center justify-between bg-surface-container rounded-lg px-3 py-2 group">
            <div class="flex items-center gap-2 text-sm">
              <span class="material-symbols-outlined text-xs text-on-surface-variant">chevron_right</span>
              <span class="font-medium text-on-surface"><?php echo htmlspecialchars($w['name']); ?></span>
              <span class="text-xs text-on-surface-variant">(<?php echo $w['village_count']; ?> villages)</span>
            </div>
            <button onclick="openAddVillageModal(<?php echo $w['id']; ?>, '<?php echo htmlspecialchars($w['name']); ?>')"
                    class="hidden group-hover:flex items-center gap-1 text-xs text-primary font-bold hover:underline">
              <span class="material-symbols-outlined text-xs">add</span> Village
            </button>
          </div>
          <?php endforeach; ?>
          <?php if (empty($districtWards)): ?>
          <p class="text-sm text-on-surface-variant italic px-2">No wards added yet.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>

    <!-- Add District Placeholder Card -->
    <button onclick="document.getElementById('addDistrictModal').classList.remove('hidden')"
            class="border-2 border-dashed border-outline-variant rounded-2xl p-8 flex flex-col items-center justify-center gap-3 text-on-surface-variant hover:border-primary hover:text-primary transition-all group">
      <span class="material-symbols-outlined text-4xl group-hover:scale-110 transition-transform">add_location_alt</span>
      <span class="font-bold text-sm">Add New District</span>
    </button>
  </div>

</div>

<!-- ── Add District Modal ────────────────────────────────────────────── -->
<div id="addDistrictModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
    <div class="px-6 py-5 border-b border-outline-variant flex justify-between items-center">
      <h3 class="font-extrabold text-lg text-on-surface">Add New District</h3>
      <button onclick="document.getElementById('addDistrictModal').classList.add('hidden')"
              class="text-on-surface-variant hover:text-on-surface"><span class="material-symbols-outlined">close</span></button>
    </div>
    <form method="POST" action="/admin/districts/create" class="p-6 space-y-4">
      <div>
        <label class="block text-sm font-bold text-on-surface-variant mb-1.5">District Name</label>
        <input type="text" name="name" required placeholder="e.g. Kasulu"
               class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none font-medium">
      </div>
      <div>
        <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Region</label>
        <input type="text" name="region" required placeholder="e.g. Kigoma"
               class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none font-medium">
      </div>
      <div class="flex gap-3 pt-2">
        <button type="button" onclick="document.getElementById('addDistrictModal').classList.add('hidden')"
                class="flex-1 border border-outline py-2.5 rounded-xl font-bold text-on-surface-variant hover:bg-surface-container transition-colors">Cancel</button>
        <button type="submit" class="flex-1 bg-primary text-white py-2.5 rounded-xl font-bold hover:bg-primary-container transition-colors">Create District</button>
      </div>
    </form>
  </div>
</div>

<!-- ── Add Ward Modal ────────────────────────────────────────────────── -->
<div id="addWardModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
    <div class="px-6 py-5 border-b border-outline-variant flex justify-between items-center">
      <h3 class="font-extrabold text-lg text-on-surface">Add Ward to <span id="wardModalDistrictName" class="text-primary"></span></h3>
      <button onclick="document.getElementById('addWardModal').classList.add('hidden')"
              class="text-on-surface-variant hover:text-on-surface"><span class="material-symbols-outlined">close</span></button>
    </div>
    <form method="POST" action="/admin/wards/create" class="p-6 space-y-4">
      <input type="hidden" name="district_id" id="wardDistrictId">
      <div>
        <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Ward Name</label>
        <input type="text" name="name" required placeholder="e.g. Kibondo"
               class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none font-medium">
      </div>
      <div class="flex gap-3 pt-2">
        <button type="button" onclick="document.getElementById('addWardModal').classList.add('hidden')"
                class="flex-1 border border-outline py-2.5 rounded-xl font-bold text-on-surface-variant hover:bg-surface-container transition-colors">Cancel</button>
        <button type="submit" class="flex-1 bg-primary text-white py-2.5 rounded-xl font-bold hover:bg-primary-container transition-colors">Add Ward</button>
      </div>
    </form>
  </div>
</div>

<!-- ── Add Village Modal ─────────────────────────────────────────────── -->
<div id="addVillageModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
    <div class="px-6 py-5 border-b border-outline-variant flex justify-between items-center">
      <h3 class="font-extrabold text-lg text-on-surface">Add Village to <span id="villageModalWardName" class="text-primary"></span></h3>
      <button onclick="document.getElementById('addVillageModal').classList.add('hidden')"
              class="text-on-surface-variant hover:text-on-surface"><span class="material-symbols-outlined">close</span></button>
    </div>
    <form method="POST" action="/admin/villages/create" class="p-6 space-y-4">
      <input type="hidden" name="ward_id" id="villageWardId">
      <div>
        <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Village Name</label>
        <input type="text" name="name" required placeholder="e.g. Mgamba"
               class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none font-medium">
      </div>
      <div>
        <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Network Quality</label>
        <select name="network_quality" class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
          <option value="good">Good</option>
          <option value="average" selected>Average</option>
          <option value="poor">Poor</option>
        </select>
      </div>
      <div class="flex gap-3 pt-2">
        <button type="button" onclick="document.getElementById('addVillageModal').classList.add('hidden')"
                class="flex-1 border border-outline py-2.5 rounded-xl font-bold text-on-surface-variant hover:bg-surface-container transition-colors">Cancel</button>
        <button type="submit" class="flex-1 bg-primary text-white py-2.5 rounded-xl font-bold hover:bg-primary-container transition-colors">Add Village</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAddWardModal(districtId, districtName) {
    document.getElementById('wardDistrictId').value = districtId;
    document.getElementById('wardModalDistrictName').textContent = districtName;
    document.getElementById('addWardModal').classList.remove('hidden');
}
function openAddVillageModal(wardId, wardName) {
    document.getElementById('villageWardId').value = wardId;
    document.getElementById('villageModalWardName').textContent = wardName;
    document.getElementById('addVillageModal').classList.remove('hidden');
}
function editDistrict(id, name, region) {
    // Simple inline edit — can be extended
    const newName = prompt('District name:', name);
    if (newName && newName !== name) {
        const f = document.createElement('form');
        f.method = 'POST'; f.action = '/admin/districts/update';
        ['id', 'name', 'region'].forEach((k, i) => {
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = k; inp.value = [id, newName, region][i];
            f.appendChild(inp);
        });
        document.body.appendChild(f); f.submit();
    }
}
</script>
