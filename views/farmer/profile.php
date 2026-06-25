<?php
$farmerId = (int)$_SESSION['farmer_id'];
$db = \App\Core\Database::getInstance()->getConnection();

$stmt = $db->prepare("
    SELECT f.*, w.name AS ward_name, v.name AS village_name 
    FROM farmers f
    LEFT JOIN wards w ON w.id = f.ward_id
    LEFT JOIN villages v ON v.id = f.village_id
    WHERE f.id = ?
");
$stmt->execute([$farmerId]);
$farmer = $stmt->fetch();

$wards = $db->query("SELECT w.id, w.name, d.name AS district_name FROM wards w JOIN districts d ON d.id = w.district_id ORDER BY d.name, w.name")->fetchAll();
$crops = $db->query("SELECT * FROM crops WHERE is_active = 1")->fetchAll();

$myCropsStmt = $db->prepare("SELECT crop_id FROM farmer_crops WHERE farmer_id = ?");
$myCropsStmt->execute([$farmerId]);
$myCrops = array_column($myCropsStmt->fetchAll(), 'crop_id');
?>

<div class="p-6 md:p-8 max-w-4xl mx-auto">
  <div class="mb-8">
    <h1 class="text-3xl font-bold text-on-surface">Wasifu Wako (Profile)</h1>
    <p class="text-on-surface-variant mt-1">Dhibiti taarifa zako za kilimo na mawasiliano.</p>
  </div>

    <?php if (isset($_GET['success'])): ?>
  <div class="mb-6 bg-green-50 text-green-800 rounded-2xl px-5 py-4 font-bold flex items-center gap-3">
    <span class="material-symbols-outlined">check_circle</span>
    Taarifa zimesasishwa kikamilifu!
  </div>
  <?php endif; ?>
  <?php if (isset($_GET['error'])): ?>
  <div class="mb-6 bg-error-container text-error rounded-2xl px-5 py-4 font-bold flex items-center gap-3">
    <span class="material-symbols-outlined">error</span>
    <?php echo htmlspecialchars($_GET['error']); ?>
  </div>
  <?php endif; ?>

  <div class="bg-white rounded-3xl border border-outline-variant shadow-sm overflow-hidden">
    <!-- Header -->
    <div class="bg-primary/5 px-6 py-8 border-b border-outline-variant flex items-center gap-6">
      <img src="<?php echo \App\Helpers\Avatar::url($farmer['name'] ?? 'Mkulima', '154212', 80); ?>"
           class="w-20 h-20 rounded-2xl shadow-sm" alt="Avatar">
      <div>
        <h2 class="text-2xl font-extrabold text-on-surface"><?php echo htmlspecialchars($farmer['name']); ?></h2>
        <p class="text-on-surface-variant flex items-center gap-1 mt-1">
          <span class="material-symbols-outlined text-sm">phone_iphone</span> <?php echo htmlspecialchars($farmer['phone']); ?>
        </p>
      </div>
    </div>

    <!-- Edit Form -->
    <form action="/farmer/profile" method="POST" class="p-6 md:p-8 space-y-8">
      
      <!-- Personal Info (Name disabled) -->
      <section>
        <h3 class="text-lg font-bold text-on-surface mb-4 flex items-center gap-2">
          <span class="material-symbols-outlined text-primary">person</span> Taarifa Binafsi
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-bold text-on-surface-variant mb-2">Jina la Kwanza <span class="font-normal text-xs">(Haiwezi kubadilishwa)</span></label>
            <input type="text" value="<?php echo htmlspecialchars($farmer['first_name'] ?? ''); ?>" disabled
                   class="w-full bg-surface-container/50 text-on-surface-variant rounded-xl px-4 py-3 border border-transparent cursor-not-allowed font-medium">
          </div>
          <div>
            <label class="block text-sm font-bold text-on-surface-variant mb-2">Jina la Mwisho <span class="font-normal text-xs">(Haiwezi kubadilishwa)</span></label>
            <input type="text" value="<?php echo htmlspecialchars($farmer['last_name'] ?? ''); ?>" disabled
                   class="w-full bg-surface-container/50 text-on-surface-variant rounded-xl px-4 py-3 border border-transparent cursor-not-allowed font-medium">
          </div>
          <div>
            <label class="block text-sm font-bold text-on-surface-variant mb-2">Namba ya Simu *</label>
            <input type="tel" name="phone" value="<?php echo htmlspecialchars($farmer['phone']); ?>" required
                   class="w-full bg-surface-container rounded-xl px-4 py-3 border border-transparent focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none font-medium transition-all">
          </div>
        </div>
      </section>

      <hr class="border-outline-variant">

      <!-- Location Info -->
      <section>
        <h3 class="text-lg font-bold text-on-surface mb-4 flex items-center gap-2">
          <span class="material-symbols-outlined text-primary">location_on</span> Eneo Lako
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-bold text-on-surface-variant mb-2">Kata (Ward)</label>
            <select name="ward_id" id="ward_id" onchange="loadVillages(this.value)"
                    class="w-full bg-surface-container rounded-xl px-4 py-3 border border-transparent focus:border-primary outline-none font-medium">
              <option value="">— Chagua Kata —</option>
              <?php foreach ($wards as $w): ?>
              <option value="<?php echo $w['id']; ?>" <?php echo $farmer['ward_id'] == $w['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($w['district_name'] . ' → ' . $w['name']); ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-sm font-bold text-on-surface-variant mb-2">Kijiji (Village)</label>
            <select name="village_id" id="village_id"
                    class="w-full bg-surface-container rounded-xl px-4 py-3 border border-transparent focus:border-primary outline-none font-medium">
              <option value="">— Chagua Kijiji —</option>
              <!-- Will be populated by JS -->
            </select>
          </div>
        </div>
      </section>

      <hr class="border-outline-variant">

      <!-- Farm Details -->
      <section>
        <h3 class="text-lg font-bold text-on-surface mb-4 flex items-center gap-2">
          <span class="material-symbols-outlined text-primary">agriculture</span> Shamba Lako
        </h3>
        <div class="mb-6">
          <label class="block text-sm font-bold text-on-surface-variant mb-2">Ukubwa wa Shamba (Ekari)</label>
          <input type="number" step="0.25" name="farm_size_acres" value="<?php echo htmlspecialchars($farmer['farm_size_acres'] ?? ''); ?>" placeholder="Mfn: 2.5"
                 class="w-full md:w-1/2 bg-surface-container rounded-xl px-4 py-3 border border-transparent focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none font-medium transition-all">
        </div>

        <div>
          <label class="block text-sm font-bold text-on-surface-variant mb-3">Mazao Yako Makuu</label>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <?php foreach ($crops as $c): ?>
            <label class="relative flex items-center gap-3 p-3 rounded-xl border-2 border-surface-container cursor-pointer hover:border-primary-fixed transition-colors <?php echo in_array($c['id'], $myCrops) ? 'bg-primary/5 border-primary' : 'bg-surface'; ?>">
              <input type="checkbox" name="crops[]" value="<?php echo $c['id']; ?>" <?php echo in_array($c['id'], $myCrops) ? 'checked' : ''; ?>
                     class="w-4 h-4 text-primary rounded border-outline-variant focus:ring-primary">
              <span class="font-bold text-on-surface text-sm"><?php echo htmlspecialchars($c['name_sw']); ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </section>

      <div class="pt-4 flex items-center justify-end gap-4">
        <button type="submit" class="bg-primary text-white px-8 py-3.5 rounded-xl font-bold text-lg hover:bg-primary-container transition-colors shadow">
          Hifadhi Taarifa (Save)
        </button>
      </div>
    </form>
  </div>
</div>

<script>
const preselectedVillage = <?php echo json_encode($farmer['village_id'] ?: null); ?>;

async function loadVillages(wardId) {
    const sel = document.getElementById('village_id');
    sel.innerHTML = '<option value="">— Loading... —</option>';
    if (!wardId) { sel.innerHTML = '<option value="">— Chagua Kijiji —</option>'; return; }
    
    try {
        const res = await fetch('/ajax/villages?ward_id=' + wardId);
        const data = await res.json();
        sel.innerHTML = '<option value="">— Chagua Kijiji —</option>';
        data.forEach(v => {
            const opt = document.createElement('option');
            opt.value = v.id;
            opt.textContent = v.name;
            if (preselectedVillage && v.id == preselectedVillage) opt.selected = true;
            sel.appendChild(opt);
        });
    } catch(e) {
        sel.innerHTML = '<option value="">Error loading</option>';
    }
}

// Initial load if ward is preselected
const initialWard = document.getElementById('ward_id').value;
if (initialWard) {
    loadVillages(initialWard);
}

// Visual toggle for crop checkboxes
document.querySelectorAll('input[name="crops[]"]').forEach(chk => {
    chk.addEventListener('change', function() {
        const lbl = this.closest('label');
        if (this.checked) {
            lbl.classList.remove('border-surface-container', 'bg-surface');
            lbl.classList.add('border-primary', 'bg-primary/5');
        } else {
            lbl.classList.add('border-surface-container', 'bg-surface');
            lbl.classList.remove('border-primary', 'bg-primary/5');
        }
    });
});
</script>
