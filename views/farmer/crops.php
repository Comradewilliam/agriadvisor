<?php
$cropName    = $cropProgress['crop_name'] ?? 'Mahindi';
$cropId      = $cropProgress['crop_id'] ?? 1;
$stageId     = $cropProgress['stage_id'] ?? null;
$stageLabel  = $cropProgress['stage_label'] ?? 'Ukuaji';
$plantedDate = $cropProgress['planted_date'] ?? date('Y-m-d', strtotime('-60 days'));
$stages      = $cropProgress['stages'] ?? [];
$dbStages    = $cropProgress['db_stages'] ?? [];
$weatherAlert = $weatherAlert ?? ['temp' => 28, 'message' => 'Hakuna taarifa za hali ya hewa.', 'level' => 'normal'];
$articles    = $articles ?? [];
$harvests    = $harvests ?? [];

$qualityMap = [
    'BORA'    => 'bg-primary-fixed text-primary',
    'KAWAIDA' => 'bg-surface-container text-on-surface-variant',
    'DHAIFU'  => 'bg-error-container text-error',
];
?>

<div class="p-6 md:p-8 w-full max-w-[1400px] mx-auto">

  <!-- Hero Banner -->
  <div class="bg-primary text-white rounded-2xl p-6 mb-8 flex items-center justify-between overflow-hidden relative">
    <div class="absolute right-0 top-0 bottom-0 w-48 bg-white/5 rounded-l-full transform translate-x-12"></div>
    <div>
      <h2 class="text-2xl font-extrabold mb-1">Mazao yako yanaonekana vizuri!</h2>
      <p class="text-white/80 text-sm max-w-lg">
        <?php echo htmlspecialchars($cropName); ?> yako kwa sasa yapo kwenye hatua ya <strong><?php echo htmlspecialchars($stageLabel); ?></strong>.
        Endelea kufuatilia ratiba yako ya mbolea kwa mavuno bora.
      </p>
    </div>
    <div class="shrink-0 hidden md:flex flex-col items-center gap-1 relative z-10">
      <span class="material-symbols-outlined text-white/80 text-4xl">verified</span>
      <span class="text-xs text-white/80 font-bold">Afya Bora</span>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Left Column -->
    <div class="lg:col-span-2 space-y-6">

      <!-- Crop Growth Cycle Timeline -->
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant p-6" id="cropTimeline">
        <div class="flex items-center justify-between mb-5">
          <h3 class="font-extrabold text-on-surface text-lg">Mzunguko wa <?php echo htmlspecialchars($cropName); ?></h3>
          <div class="flex items-center gap-2 text-sm text-on-surface-variant">
            <span class="material-symbols-outlined text-base">calendar_today</span>
            Kupandwa: <?php echo date('d M Y', strtotime($plantedDate)); ?>
          </div>
        </div>

        <div class="relative flex items-start gap-0 overflow-x-auto pb-2">
          <?php foreach ($stages as $i => $stage): ?>
            <div class="flex-1 min-w-[4.5rem] flex flex-col items-center relative" data-stage-col="<?php echo (int)$stage['id']; ?>">
              <?php if ($i < count($stages) - 1): ?>
                <div class="absolute top-5 left-1/2 w-full h-0.5 <?php echo $stage['done'] ? 'bg-primary' : 'bg-surface-container-highest'; ?>"></div>
              <?php endif; ?>
              <div class="relative z-10 w-10 h-10 rounded-full flex items-center justify-center mb-2
                <?php echo isset($stage['active']) ? 'bg-primary shadow-lg shadow-primary/30 ring-4 ring-primary/20' : ($stage['done'] ? 'bg-primary' : 'bg-surface-container-highest'); ?>">
                <span class="material-symbols-outlined text-lg <?php echo ($stage['done'] || isset($stage['active'])) ? 'text-white' : 'text-outline'; ?>">
                  <?php echo $stage['done'] && !isset($stage['active']) ? 'check' : $stage['icon']; ?>
                </span>
              </div>
              <p class="text-xs text-center font-medium px-1 <?php echo isset($stage['active']) ? 'text-primary font-bold' : ($stage['done'] ? 'text-on-surface' : 'text-outline'); ?>">
                <?php echo htmlspecialchars($stage['label']); ?>
              </p>
              <?php if (isset($stage['active'])): ?>
                <span class="text-xs bg-primary text-white px-2 py-0.5 rounded-full mt-1 font-bold whitespace-nowrap">Tuko Hapa</span>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Recommendations Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-tertiary-fixed rounded-2xl p-5 border border-tertiary-fixed-dim">
          <div class="flex items-center gap-2 mb-3">
            <span class="material-symbols-outlined text-tertiary">water_drop</span>
            <p class="font-bold text-tertiary text-sm">Hatua Inayofuata</p>
          </div>
          <h4 class="text-xl font-extrabold text-on-surface mb-2" id="nextActionTitle">Fuata ushauri wa hatua ya <?php echo htmlspecialchars($stageLabel); ?></h4>
          <p class="text-sm text-on-surface-variant leading-relaxed" id="heroStageText">
            Endelea kufuata ratiba ya kilimo kwa hatua ya sasa. Uliza BwanaShamba kwa maelezo zaidi kuhusu mbolea na dawa.
          </p>
          <a href="/farmer/chat?q=Jinsi+ya+kutumia+mbolea+msimu+huu" class="inline-flex items-center gap-1 mt-4 bg-tertiary text-white text-sm font-bold px-4 py-2 rounded-xl hover:opacity-90 transition-opacity">
            Soma Mwongozo <span class="material-symbols-outlined text-base">arrow_forward</span>
          </a>
        </div>

        <!-- Weather Warning (live) -->
        <div class="bg-error-container rounded-2xl p-5 border border-error/20">
          <div class="flex items-center gap-2 mb-3">
            <span class="material-symbols-outlined text-error">warning</span>
            <p class="font-bold text-error text-sm">Tahadhari ya Hali ya Hewa</p>
          </div>
          <p class="text-4xl font-extrabold text-on-surface mb-2"><?php echo (int)$weatherAlert['temp']; ?>°C</p>
          <p class="text-sm text-on-surface-variant leading-relaxed">
            <?php echo htmlspecialchars($weatherAlert['message']); ?>
          </p>
          <a href="/farmer/weather" class="inline-flex items-center gap-1 mt-4 text-error font-bold text-sm hover:underline">
            Tazama Utabiri <span class="material-symbols-outlined text-base">arrow_forward</span>
          </a>
        </div>
      </div>

      <!-- Resource Articles -->
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant p-6">
        <h3 class="font-extrabold text-on-surface text-lg mb-4">Maktaba ya Kilimo</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
          <?php foreach ($articles as $a): ?>
            <button type="button" onclick="openArticleModal(<?php echo (int)$a['id']; ?>)"
                    class="group block rounded-xl overflow-hidden border border-outline-variant hover:shadow-md transition-all text-left w-full">
              <div class="aspect-video bg-surface-container-highest relative overflow-hidden">
                <img src="<?php echo htmlspecialchars($a['img']); ?>" alt="<?php echo htmlspecialchars($a['tag']); ?>"
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                     onerror="this.parentNode.innerHTML='<div class=\'w-full h-full bg-primary-fixed flex items-center justify-center\'><span class=\'material-symbols-outlined text-primary\'>article</span></div>'">
              </div>
              <div class="p-2">
                <p class="text-xs font-extrabold text-primary uppercase tracking-wider"><?php echo htmlspecialchars($a['tag']); ?></p>
                <p class="text-xs text-on-surface leading-snug mt-0.5"><?php echo htmlspecialchars($a['title']); ?></p>
              </div>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Right Column -->
    <div class="space-y-6">

      <!-- Update Stage -->
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant p-5">
        <h4 class="font-bold text-on-surface mb-4">Badili Hatua ya Zao</h4>
        <label class="block text-sm text-on-surface-variant mb-1">Hatua ya Sasa</label>
        <select id="stageSelect" class="w-full bg-surface border border-outline rounded-xl px-4 py-3 text-on-surface focus:ring-2 focus:ring-primary outline-none mb-2">
          <?php foreach ($dbStages as $st): ?>
            <option value="<?php echo (int)$st['id']; ?>" <?php echo (int)$st['id'] === (int)$stageId ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($st['name_sw']); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <p class="text-xs text-on-surface-variant italic mb-4">Kubadilisha hatua kutasasisha ratiba yako ya msimu.</p>
        <button id="updateStageBtn" type="button" class="w-full bg-primary text-white font-bold py-3 rounded-xl hover:bg-primary-container transition-colors">Sasisha Progress</button>
        <p id="stageUpdateMsg" class="text-xs mt-2 hidden"></p>
      </div>

      <!-- Past Harvests -->
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant p-5">
        <div class="flex items-center justify-between mb-4">
          <h4 class="font-bold text-on-surface">Mavuno Yaliyopita</h4>
          <span class="material-symbols-outlined text-on-surface-variant">history</span>
        </div>
        <div class="space-y-3" id="harvestPreview">
          <?php if (empty($harvests)): ?>
            <div class="text-center py-6 text-on-surface-variant">
              <span class="material-symbols-outlined text-3xl mb-2 block text-outline">inventory_2</span>
              <p class="text-sm">Bado huna rekodi za mavuno yaliyopita.</p>
            </div>
          <?php else: ?>
            <?php foreach (array_slice($harvests, 0, 3) as $h):
              $label = htmlspecialchars($h['crop_name']) . ' - ' . (int)$h['harvest_year'];
              $yield = $h['yield_per_acre'] ?: (($h['yield_amount'] ?? '') . ' ' . ($h['yield_unit'] ?? 'gunia'));
              $cls = $qualityMap[$h['quality_status']] ?? $qualityMap['KAWAIDA'];
            ?>
              <div class="flex items-center justify-between p-3 bg-surface-container-low rounded-xl">
                <div>
                  <p class="font-bold text-on-surface text-sm"><?php echo $label; ?></p>
                  <p class="text-xs text-on-surface-variant"><?php echo htmlspecialchars($yield); ?></p>
                </div>
                <span class="text-xs font-extrabold px-2 py-1 rounded-full <?php echo $cls; ?>"><?php echo htmlspecialchars($h['quality_status']); ?></span>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <button type="button" onclick="openModal('harvestModal')" class="w-full mt-3 text-primary font-bold text-sm py-2 rounded-xl hover:bg-primary-fixed transition-colors">Tazama Rekodi Zote</button>
      </div>
    </div>
  </div>
</div>

<!-- Article modal -->
<div id="articleModal" class="hidden fixed inset-0 z-[100] modal-backdrop modal-overlay bg-black/50 flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full max-h-[85vh] overflow-y-auto border border-outline-variant">
    <div class="sticky top-0 bg-white border-b border-outline-variant px-5 py-4 flex items-center justify-between">
      <div>
        <p id="articleModalTag" class="text-xs font-extrabold text-primary uppercase tracking-wider"></p>
        <h3 id="articleModalTitle" class="font-bold text-on-surface text-lg mt-0.5"></h3>
      </div>
      <button type="button" data-close-modal class="w-9 h-9 rounded-xl bg-surface-container flex items-center justify-center hover:bg-surface-container-high">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>
    <img id="articleModalImg" src="" alt="" class="w-full max-h-48 object-cover hidden">
    <div id="articleModalBody" class="p-5 text-sm text-on-surface-variant leading-relaxed prose prose-sm max-w-none"></div>
  </div>
</div>

<!-- All harvests modal -->
<div id="harvestModal" class="hidden fixed inset-0 z-[100] modal-backdrop modal-overlay bg-black/50 flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-xl max-w-md w-full max-h-[85vh] overflow-y-auto border border-outline-variant">
    <div class="sticky top-0 bg-white border-b border-outline-variant px-5 py-4 flex items-center justify-between">
      <h3 class="font-bold text-on-surface text-lg">Rekodi Zote za Mavuno</h3>
      <button type="button" data-close-modal class="w-9 h-9 rounded-xl bg-surface-container flex items-center justify-center">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>
    <div class="p-5 space-y-3">
      <?php if (empty($harvests)): ?>
        <div class="text-center py-10 text-on-surface-variant">
          <span class="material-symbols-outlined text-4xl mb-3 block text-outline">inventory_2</span>
          <p class="font-medium text-on-surface mb-1">Hakuna rekodi za mavuno</p>
          <p class="text-sm">Mavuno yako yataonekana hapa baada ya kurekodiwa na afisa wa kilimo.</p>
        </div>
      <?php else: ?>
        <?php foreach ($harvests as $h):
          $label = htmlspecialchars($h['crop_name']) . ' - ' . (int)$h['harvest_year'];
          $yield = $h['yield_per_acre'] ?: (($h['yield_amount'] ?? '') . ' ' . ($h['yield_unit'] ?? 'gunia'));
          $cls = $qualityMap[$h['quality_status']] ?? $qualityMap['KAWAIDA'];
        ?>
          <div class="flex items-center justify-between p-3 bg-surface-container-low rounded-xl">
            <div>
              <p class="font-bold text-on-surface text-sm"><?php echo $label; ?></p>
              <p class="text-xs text-on-surface-variant"><?php echo htmlspecialchars($yield); ?></p>
            </div>
            <span class="text-xs font-extrabold px-2 py-1 rounded-full <?php echo $cls; ?>"><?php echo htmlspecialchars($h['quality_status']); ?></span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
const cropArticles = <?php echo json_encode($articles, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS); ?>;
const cropId = <?php echo (int)$cropId; ?>;
const stageTimeline = <?php echo json_encode($stages, JSON_UNESCAPED_UNICODE); ?>;

function openArticleModal(id) {
  const a = cropArticles.find(x => x.id === id);
  if (!a) return;
  document.getElementById('articleModalTag').textContent = a.tag;
  document.getElementById('articleModalTitle').textContent = a.title;
  const img = document.getElementById('articleModalImg');
  if (a.img) { img.src = a.img; img.classList.remove('hidden'); } else { img.classList.add('hidden'); }
  document.getElementById('articleModalBody').innerHTML = a.body || '<p>Hakuna maelezo zaidi.</p>';
  openModal('articleModal');
}

function renderTimeline(activeStageId) {
  const cols = document.querySelectorAll('[data-stage-col]');
  let activeIdx = 0;
  cols.forEach((col, i) => {
    if (parseInt(col.dataset.stageCol, 10) === activeStageId) activeIdx = i;
  });

  cols.forEach((col, i) => {
    const stage = stageTimeline.find(s => s.id === parseInt(col.dataset.stageCol, 10)) || stageTimeline[i];
    const circle = col.querySelector('.relative.z-10');
    const label = col.querySelector('p.text-xs');
    col.querySelector('.whitespace-nowrap')?.remove();

    const isActive = i === activeIdx;
    const isDone = i < activeIdx;

    circle.className = 'relative z-10 w-10 h-10 rounded-full flex items-center justify-center mb-2 ' +
      (isActive ? 'bg-primary shadow-lg shadow-primary/30 ring-4 ring-primary/20' : (isDone ? 'bg-primary' : 'bg-surface-container-highest'));

    const icon = circle.querySelector('.material-symbols-outlined');
    icon.className = 'material-symbols-outlined text-lg ' + ((isDone || isActive) ? 'text-white' : 'text-outline');
    icon.textContent = isDone ? 'check' : (stage?.icon || 'eco');

    label.className = 'text-xs text-center font-medium px-1 ' + (isActive ? 'text-primary font-bold' : (isDone ? 'text-on-surface' : 'text-outline'));
    label.textContent = stage?.label || label.textContent;

    if (isActive) {
      const span = document.createElement('span');
      span.className = 'text-xs bg-primary text-white px-2 py-0.5 rounded-full mt-1 font-bold whitespace-nowrap';
      span.textContent = 'Tuko Hapa';
      col.appendChild(span);
      document.getElementById('nextActionTitle').textContent = 'Hatua ya sasa: ' + stage.label;
      const heroStrong = document.querySelector('.bg-primary .text-white\\/80 strong');
      if (heroStrong) heroStrong.textContent = stage.label;
    }

    const line = col.querySelector('.absolute.top-5');
    if (line) line.className = 'absolute top-5 left-1/2 w-full h-0.5 ' + (isDone ? 'bg-primary' : 'bg-surface-container-highest');
  });
}

document.getElementById('updateStageBtn')?.addEventListener('click', async () => {
  const stageId = parseInt(document.getElementById('stageSelect').value, 10);
  const msg = document.getElementById('stageUpdateMsg');
  const btn = document.getElementById('updateStageBtn');
  btn.disabled = true;
  msg.classList.add('hidden');

  const fd = new FormData();
  fd.append('crop_id', cropId);
  fd.append('stage_id', stageId);

  try {
    const res = await fetch('/farmer/crops/stage', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
      renderTimeline(stageId);
      const opt = document.getElementById('stageSelect').selectedOptions[0];
      if (opt) {
        document.getElementById('nextActionTitle').textContent = 'Hatua ya sasa: ' + opt.textContent.trim();
        const heroStrong = document.querySelector('.bg-primary .text-white\\/80 strong');
        if (heroStrong) heroStrong.textContent = opt.textContent.trim();
      }
      msg.textContent = 'Hatua imesasishwa kwa mafanikio!';
      msg.className = 'text-xs mt-2 text-primary font-medium';
      msg.classList.remove('hidden');
      if (window.showToast) showToast('Hatua ya zao imesasishwa', 'success');
    } else {
      msg.textContent = data.error || 'Imeshindikana kusasisha.';
      msg.className = 'text-xs mt-2 text-error';
      msg.classList.remove('hidden');
    }
  } catch (e) {
    msg.textContent = 'Hitilafu ya mtandao. Jaribu tena.';
    msg.className = 'text-xs mt-2 text-error';
    msg.classList.remove('hidden');
  } finally {
    btn.disabled = false;
  }
});
</script>
