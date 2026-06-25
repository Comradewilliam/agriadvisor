<?php
/**
 * views/officer/edit_farmer.php  v1.4
 * DAO-only form to edit farmer profile:
 * name, contact, ward, village, farm size, primary crop + up to 2 secondary
 */
$db       = \App\Core\Database::getInstance()->getConnection();
$farmerId = (int)($_GET['id'] ?? 0);

if (!$farmerId) { header('Location: /officer/farmers'); exit; }

$fStmt = $db->prepare("
    SELECT f.*, w.name AS ward_name, v.name AS village_name
    FROM farmers f
    LEFT JOIN wards w ON w.id=f.ward_id
    LEFT JOIN villages v ON v.id=f.village_id
    WHERE f.id=?
");
$fStmt->execute([$farmerId]);
$farmer = $fStmt->fetch();
if (!$farmer) { http_response_code(404); echo 'Farmer not found'; exit; }

$wards = $db->query("SELECT w.id,w.name,d.name AS district_name FROM wards w JOIN districts d ON d.id=w.district_id ORDER BY d.name,w.name")->fetchAll();
$crops = $db->query("SELECT * FROM crops WHERE is_active=1 ORDER BY name_en")->fetchAll();

// Existing crops
$fcStmt = $db->prepare("SELECT crop_id,type FROM farmer_crops WHERE farmer_id=?");
$fcStmt->execute([$farmerId]);
$farmerCrops = $fcStmt->fetchAll();
$primaryCropId    = null;
$secondaryCropIds = [];
foreach ($farmerCrops as $fc) {
    if ($fc['type'] === 'primary')   $primaryCropId      = (int)$fc['crop_id'];
    if ($fc['type'] === 'secondary') $secondaryCropIds[] = (int)$fc['crop_id'];
}
?>
<div class="p-6 md:p-8 max-w-3xl mx-auto">
  <div class="mb-6 flex items-center gap-4">
    <a href="/officer/farmers/view?id=<?php echo $farmerId; ?>" class="text-on-surface-variant hover:text-on-surface">
      <span class="material-symbols-outlined">arrow_back</span>
    </a>
    <div>
      <h2 class="text-3xl font-bold text-primary"><?php echo __('farmer_edit'); ?></h2>
      <p class="text-on-surface-variant"><?php echo htmlspecialchars($farmer['name'] ?? ($farmer['first_name'].' '.$farmer['last_name'])); ?></p>
    </div>
  </div>

  <?php if (isset($_GET['success'])): ?>
    <div class="mb-4 bg-primary-fixed text-primary rounded-xl px-5 py-3 font-bold"><?php echo __('success'); ?></div>
  <?php endif; ?>

  <form action="/officer/farmers/edit" method="POST" class="bg-white rounded-2xl border border-outline-variant shadow-sm overflow-hidden">
    <input type="hidden" name="farmer_id" value="<?php echo $farmerId; ?>">

    <!-- Personal Info -->
    <div class="p-6 border-b border-outline-variant">
      <h3 class="text-base font-bold text-on-surface mb-4 flex items-center gap-2">
        <span class="material-symbols-outlined text-primary">person</span> Personal Information
      </h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-bold text-on-surface-variant mb-1.5"><?php echo __('full_name'); ?> *</label>
          <input type="text" name="name" required
                 value="<?php echo htmlspecialchars($farmer['name'] ?? trim(($farmer['first_name']??'').' '.($farmer['last_name']??''))); ?>"
                 class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
        </div>
        <div>
          <label class="block text-sm font-bold text-on-surface-variant mb-1.5"><?php echo __('phone'); ?> *</label>
          <input type="tel" name="phone" required value="<?php echo htmlspecialchars($farmer['phone']); ?>"
                 class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
        </div>
      </div>
    </div>

    <!-- Location -->
    <div class="p-6 border-b border-outline-variant">
      <h3 class="text-base font-bold text-on-surface mb-4 flex items-center gap-2">
        <span class="material-symbols-outlined text-primary">location_on</span> Location
      </h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-bold text-on-surface-variant mb-1.5"><?php echo __('farmer_ward'); ?></label>
          <select name="ward_id" id="editWardSel" onchange="loadVillages(this.value, null)"
                  class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
            <option value="">— <?php echo __('farmer_ward'); ?> —</option>
            <?php foreach ($wards as $w): ?>
              <option value="<?php echo $w['id']; ?>" <?php echo $farmer['ward_id']==$w['id']?'selected':''; ?>>
                <?php echo htmlspecialchars($w['district_name'].' → '.$w['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-bold text-on-surface-variant mb-1.5"><?php echo __('farmer_village'); ?></label>
          <select name="village_id" id="editVillageSel"
                  class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
            <option value="">— <?php echo __('farmer_village'); ?> —</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Farm Details -->
    <div class="p-6 border-b border-outline-variant">
      <h3 class="text-base font-bold text-on-surface mb-4 flex items-center gap-2">
        <span class="material-symbols-outlined text-primary">agriculture</span> Farm Details
      </h3>
      <div class="mb-4">
        <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Farm Size (Acres)</label>
        <input type="number" step="0.25" name="farm_size_acres" value="<?php echo htmlspecialchars($farmer['farm_size_acres'] ?? ''); ?>"
               class="w-full md:w-48 bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium" placeholder="e.g. 2.5">
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Primary Crop</label>
          <select name="primary_crop_id" class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
            <option value="">— None —</option>
            <?php foreach ($crops as $c): ?>
              <option value="<?php echo $c['id']; ?>" <?php echo $primaryCropId==$c['id']?'selected':''; ?>>
                <?php echo htmlspecialchars($c['name_en']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Secondary Crop 1</label>
          <select name="secondary_crop_ids[]" class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
            <option value="">— None —</option>
            <?php foreach ($crops as $c): ?>
              <option value="<?php echo $c['id']; ?>" <?php echo in_array($c['id'],$secondaryCropIds)&&$secondaryCropIds[0]==$c['id']?'selected':''; ?>>
                <?php echo htmlspecialchars($c['name_en']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Secondary Crop 2</label>
          <select name="secondary_crop_ids[]" class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
            <option value="">— None —</option>
            <?php foreach ($crops as $c): ?>
              <option value="<?php echo $c['id']; ?>" <?php echo in_array($c['id'],$secondaryCropIds)&&($secondaryCropIds[1]??null)==$c['id']?'selected':''; ?>>
                <?php echo htmlspecialchars($c['name_en']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <div class="p-6 flex items-center justify-end gap-4">
      <a href="/officer/farmers/view?id=<?php echo $farmerId; ?>" class="border border-outline px-6 py-2.5 rounded-xl text-on-surface-variant font-bold hover:bg-surface-container-low"><?php echo __('cancel'); ?></a>
      <button type="submit" class="bg-primary text-white px-8 py-2.5 rounded-xl font-bold hover:bg-primary-container transition-colors"><?php echo __('save_changes'); ?></button>
    </div>
  </form>
</div>

<script>
const preselectedVillage = <?php echo json_encode($farmer['village_id'] ?: null); ?>;
async function loadVillages(wardId, preselect) {
    const sel = document.getElementById('editVillageSel');
    sel.innerHTML = '<option value="">Loading...</option>';
    if (!wardId) { sel.innerHTML = '<option value="">— <?php echo __('farmer_village'); ?> —</option>'; return; }
    const data = await fetch('/ajax/villages?ward_id='+wardId).then(r=>r.json());
    sel.innerHTML = '<option value="">— <?php echo __('farmer_village'); ?> —</option>';
    data.forEach(v => {
        const o = document.createElement('option');
        o.value = v.id; o.textContent = v.name;
        if ((preselect || preselectedVillage) && v.id == (preselect || preselectedVillage)) o.selected = true;
        sel.appendChild(o);
    });
}
const initWard = document.getElementById('editWardSel').value;
if (initWard) loadVillages(initWard, null);
</script>
