<?php
/**
 * views/officer/ai_mentorship.php  v1.4
 * – Form moved into "Add New Article" modal
 * – Table rows clickable → Article Detail modal
 * – Edit & Delete CRUD from detail modal
 * – Translations applied
 */
?>
<div class="p-6 md:p-8">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h3 class="text-3xl font-bold text-primary"><?php echo __('kb_title'); ?></h3>
            <p class="text-on-surface-variant"><?php echo __('kb_subtitle'); ?></p>
        </div>
        <button onclick="openAddModal()"
                class="flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-bold hover:bg-primary-container transition-colors shadow-sm">
            <span class="material-symbols-outlined">add</span> <?php echo __('kb_add'); ?>
        </button>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="mb-4 bg-primary-fixed text-primary rounded-xl px-5 py-3 font-bold"><?php echo __('kb_saved'); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
        <div class="mb-4 bg-primary-fixed text-primary rounded-xl px-5 py-3 font-bold"><?php echo __('kb_updated'); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
        <div class="mb-4 bg-error-container text-error rounded-xl px-5 py-3 font-bold"><?php echo __('kb_deleted'); ?></div>
    <?php endif; ?>

    <!-- Search & filter -->
    <div class="mb-4 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-bold text-on-surface-variant mb-1">Search</label>
            <input type="text" id="kbSearch" value="<?php echo htmlspecialchars($search ?? ''); ?>" placeholder="Search title, crop, stage..."
                   class="w-full bg-white border border-outline-variant rounded-xl px-4 py-2 text-sm">
        </div>
        <div class="min-w-[140px]">
            <label class="block text-xs font-bold text-on-surface-variant mb-1"><?php echo __('kb_crop'); ?></label>
            <select id="kbCropFilter" class="w-full bg-white border border-outline-variant rounded-xl px-4 py-2 text-sm">
                <option value="">All crops</option>
                <?php foreach ($crops as $c): ?>
                    <option value="<?php echo (int)$c['id']; ?>" <?php echo ($cropId ?? null) == $c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name_en']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="min-w-[140px]">
            <label class="block text-xs font-bold text-on-surface-variant mb-1"><?php echo __('kb_stage'); ?></label>
            <select id="kbStageFilter" class="w-full bg-white border border-outline-variant rounded-xl px-4 py-2 text-sm">
                <option value="">All stages</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-on-surface-variant mb-1"><?php echo __('kb_status'); ?></label>
            <select id="kbStatusFilter" class="bg-white border border-outline-variant rounded-xl px-4 py-2 text-sm">
                <option value="">All</option>
                <option value="published" <?php echo ($status ?? '') === 'published' ? 'selected' : ''; ?>><?php echo __('kb_published'); ?></option>
                <option value="draft" <?php echo ($status ?? '') === 'draft' ? 'selected' : ''; ?>><?php echo __('kb_draft'); ?></option>
            </select>
        </div>
        <button type="button" id="kbClearFilters" class="hidden text-sm text-primary font-bold py-2">Clear</button>
    </div>

    <!-- Articles Table -->
    <div class="bg-white rounded-xl shadow-sm border border-outline-variant overflow-hidden">
        <div class="p-5 bg-surface-container-low border-b border-outline-variant flex items-center justify-between">
            <h4 class="text-lg font-bold text-on-surface"><?php echo __('kb_count'); ?> (<span id="kbCount"><?php echo count($entries); ?></span>)</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="text-xs text-on-surface-variant uppercase bg-surface-container">
                    <tr>
                        <th class="p-4"><?php echo __('kb_crop_stage'); ?></th>
                        <th class="p-4"><?php echo __('kb_title_col'); ?></th>
                        <th class="p-4"><?php echo __('kb_status'); ?></th>
                        <th class="p-4"><?php echo __('kb_date'); ?></th>
                        <th class="p-4"><?php echo __('actions'); ?></th>
                    </tr>
                </thead>
                <tbody id="kbTableBody" class="divide-y divide-outline-variant">
                    <?php if (empty($entries)): ?>
                        <tr><td colspan="5" class="p-8 text-center text-outline"><?php echo __('kb_no_entries'); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($entries as $e): ?>
                            <tr class="hover:bg-surface-container-lowest transition-colors cursor-pointer"
                                onclick="openViewModal(<?php echo htmlspecialchars(json_encode($e), ENT_QUOTES); ?>)">
                                <td class="p-4">
                                    <p class="font-bold text-primary text-sm"><?php echo htmlspecialchars($e['crop_name'] ?? '—'); ?></p>
                                    <p class="text-xs text-on-surface-variant"><?php echo htmlspecialchars($e['stage_name'] ?? '—'); ?></p>
                                </td>
                                <td class="p-4 text-sm text-on-surface max-w-xs truncate"><?php echo htmlspecialchars($e['title']); ?></td>
                                <td class="p-4">
                                    <?php if ($e['status'] === 'published'): ?>
                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-bold"><?php echo __('kb_published'); ?></span>
                                    <?php else: ?>
                                        <span class="bg-amber-100 text-amber-800 px-2 py-1 rounded text-xs font-bold"><?php echo __('kb_draft'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-xs text-outline"><?php echo date('d M Y', strtotime($e['updated_at'])); ?></td>
                                <td class="p-4">
                                    <div class="flex gap-2" onclick="event.stopPropagation()">
                                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($e), ENT_QUOTES); ?>)"
                                                class="p-1.5 rounded-lg hover:bg-surface-container text-on-surface-variant" title="<?php echo __('edit'); ?>">
                                            <span class="material-symbols-outlined text-base">edit</span>
                                        </button>
                                        <button onclick="confirmDelete(<?php echo (int)$e['id']; ?>)"
                                                class="p-1.5 rounded-lg hover:bg-error-container text-error" title="<?php echo __('delete'); ?>">
                                            <span class="material-symbols-outlined text-base">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ─── Add / Edit Modal ────────────────────────────────────────────────────── -->
<div id="articleModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 overflow-y-auto">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl my-4">
    <div class="p-5 border-b border-outline-variant flex justify-between items-center sticky top-0 bg-white rounded-t-2xl">
      <h4 id="articleModalTitle" class="text-lg font-bold text-on-surface"><?php echo __('kb_add'); ?></h4>
      <button onclick="closeArticleModal()"><span class="material-symbols-outlined text-outline">close</span></button>
    </div>
    <form id="articleForm" action="/officer/ai-mentorship" method="POST" class="p-6 space-y-4">
      <input type="hidden" name="_method" id="articleMethod" value="POST">
      <input type="hidden" name="id" id="articleEntryId" value="">

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-medium text-on-surface-variant mb-1 uppercase tracking-wider"><?php echo __('kb_crop'); ?> *</label>
          <select name="crop_id" id="cropSel" required class="w-full bg-surface border border-outline rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary text-sm">
            <option value=""><?php echo __('kb_select_crop'); ?></option>
            <?php foreach ($crops as $c): ?>
              <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name_en']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-on-surface-variant mb-1 uppercase tracking-wider"><?php echo __('kb_stage'); ?> *</label>
          <select name="stage_id" id="stageSel" required class="w-full bg-surface border border-outline rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary text-sm">
            <option value=""><?php echo __('kb_select_stage'); ?></option>
          </select>
        </div>
      </div>
      <div>
        <label class="block text-xs font-medium text-on-surface-variant mb-1 uppercase tracking-wider"><?php echo __('kb_topic'); ?> *</label>
        <select name="topic_id" id="topicSel" required class="w-full bg-surface border border-outline rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary text-sm">
          <option value=""><?php echo __('kb_select_topic'); ?></option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-medium text-on-surface-variant mb-1 uppercase tracking-wider"><?php echo __('kb_article_title'); ?> *</label>
        <input type="text" name="title" id="articleTitle" required class="w-full bg-surface border border-outline rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary text-sm">
      </div>
      <div>
        <label class="block text-xs font-medium text-on-surface-variant mb-1 uppercase tracking-wider"><?php echo __('kb_situation'); ?> *</label>
        <textarea name="situation" id="articleSituation" required maxlength="500" rows="2" class="w-full bg-surface border border-outline rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary text-sm" placeholder="<?php echo __('kb_situation'); ?>..."></textarea>
      </div>
      <div>
        <label class="block text-xs font-medium text-on-surface-variant mb-1 uppercase tracking-wider"><?php echo __('kb_solution'); ?> *</label>
        <textarea name="solution" id="articleSolution" required rows="4" class="w-full bg-surface border border-outline rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary text-sm" placeholder="<?php echo __('kb_solution'); ?>..."></textarea>
      </div>
      <div>
        <label class="block text-xs font-medium text-on-surface-variant mb-1 uppercase tracking-wider"><?php echo __('kb_language'); ?></label>
        <select name="language" id="articleLanguage" class="w-full bg-surface border border-outline rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary text-sm">
          <option value="sw">Kiswahili</option>
          <option value="en">English</option>
        </select>
      </div>
      <div class="flex gap-3 pt-2">
        <button type="submit" class="flex-1 bg-primary text-white py-3 rounded-xl font-bold flex items-center justify-center gap-2 hover:bg-primary-container transition-colors">
          <span class="material-symbols-outlined">auto_awesome</span> <?php echo __('kb_save'); ?>
        </button>
        <button type="button" onclick="closeArticleModal()" class="flex-1 border border-outline py-3 rounded-xl text-on-surface-variant font-bold hover:bg-surface-container-low transition-colors">
          <?php echo __('cancel'); ?>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ─── View Article Modal ───────────────────────────────────────────────────── -->
<div id="viewModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 overflow-y-auto">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl my-4">
    <div class="p-5 border-b border-outline-variant flex justify-between items-center">
      <h4 class="text-lg font-bold text-on-surface"><?php echo __('kb_view_article'); ?></h4>
      <button onclick="document.getElementById('viewModal').classList.add('hidden')">
        <span class="material-symbols-outlined text-outline">close</span>
      </button>
    </div>
    <div class="p-6 space-y-4">
      <div class="flex gap-2 flex-wrap mb-2">
        <span id="vCropBadge" class="bg-primary-fixed text-primary text-xs font-bold px-3 py-1 rounded-full"></span>
        <span id="vStageBadge" class="bg-surface-container text-on-surface-variant text-xs font-bold px-3 py-1 rounded-full"></span>
        <span id="vLangBadge" class="bg-tertiary-fixed text-tertiary text-xs font-bold px-3 py-1 rounded-full"></span>
      </div>
      <h3 id="vTitle" class="text-xl font-extrabold text-on-surface"></h3>
      <div class="bg-surface-container-low rounded-xl p-4">
        <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1"><?php echo __('kb_situation'); ?></p>
        <p id="vSituation" class="text-sm text-on-surface"></p>
      </div>
      <div class="bg-primary-fixed/30 rounded-xl p-4">
        <p class="text-xs font-bold text-primary uppercase tracking-wider mb-1"><?php echo __('kb_solution'); ?></p>
        <p id="vSolution" class="text-sm text-on-surface whitespace-pre-line"></p>
      </div>
      <p id="vDate" class="text-xs text-outline"></p>
      <div class="flex gap-3 pt-2">
        <button id="vEditBtn" onclick="" class="flex-1 bg-primary text-white py-2.5 rounded-xl font-bold hover:bg-primary-container transition-colors flex items-center justify-center gap-2">
          <span class="material-symbols-outlined text-base">edit</span> <?php echo __('edit'); ?>
        </button>
        <button id="vDeleteBtn" onclick="" class="flex-1 bg-error text-white py-2.5 rounded-xl font-bold hover:opacity-90 transition-opacity flex items-center justify-center gap-2">
          <span class="material-symbols-outlined text-base">delete</span> <?php echo __('delete'); ?>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ─── Delete confirm form (hidden) ────────────────────────────────────────── -->
<form id="deleteForm" action="/officer/ai-mentorship/delete" method="POST" class="hidden">
  <input type="hidden" name="id" id="deleteEntryId">
</form>

<script>
const KB_LABELS = {
    published: <?php echo json_encode(__('kb_published')); ?>,
    draft: <?php echo json_encode(__('kb_draft')); ?>,
    noEntries: <?php echo json_encode(__('kb_no_entries')); ?>,
    edit: <?php echo json_encode(__('edit')); ?>,
    delete: <?php echo json_encode(__('delete')); ?>,
};

let kbSearchTimer = null;

function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str ?? '';
    return d.innerHTML;
}

function renderKbRows(entries) {
    window.kbEntries = entries;
    const tbody = document.getElementById('kbTableBody');
    document.getElementById('kbCount').textContent = entries.length;

    if (!entries.length) {
        tbody.innerHTML = `<tr><td colspan="5" class="p-8 text-center text-outline">${KB_LABELS.noEntries}</td></tr>`;
        return;
    }

    tbody.innerHTML = entries.map((e, i) => {
        const statusBadge = e.status === 'published'
            ? `<span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-bold">${KB_LABELS.published}</span>`
            : `<span class="bg-amber-100 text-amber-800 px-2 py-1 rounded text-xs font-bold">${KB_LABELS.draft}</span>`;
        const date = e.updated_at ? e.updated_at.substring(0, 10) : '';
        return `<tr class="hover:bg-surface-container-lowest transition-colors cursor-pointer" onclick="openViewModal(window.kbEntries[${i}])">
            <td class="p-4">
                <p class="font-bold text-primary text-sm">${escapeHtml(e.crop_name || '—')}</p>
                <p class="text-xs text-on-surface-variant">${escapeHtml(e.stage_name || '—')}</p>
            </td>
            <td class="p-4 text-sm text-on-surface max-w-xs truncate">${escapeHtml(e.title)}</td>
            <td class="p-4">${statusBadge}</td>
            <td class="p-4 text-xs text-outline">${date}</td>
            <td class="p-4">
                <div class="flex gap-2" onclick="event.stopPropagation()">
                    <button onclick="openEditModal(window.kbEntries[${i}])"
                            class="p-1.5 rounded-lg hover:bg-surface-container text-on-surface-variant" title="${KB_LABELS.edit}">
                        <span class="material-symbols-outlined text-base">edit</span>
                    </button>
                    <button onclick="confirmDelete(${e.id})"
                            class="p-1.5 rounded-lg hover:bg-error-container text-error" title="${KB_LABELS.delete}">
                        <span class="material-symbols-outlined text-base">delete</span>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

function updateClearButton() {
    const hasFilter = document.getElementById('kbSearch').value.trim()
        || document.getElementById('kbStatusFilter').value
        || document.getElementById('kbCropFilter').value
        || document.getElementById('kbStageFilter').value;
    document.getElementById('kbClearFilters').classList.toggle('hidden', !hasFilter);
}

async function loadKbStages(cropId, selectedStageId) {
    const stageSel = document.getElementById('kbStageFilter');
    stageSel.innerHTML = '<option value="">All stages</option>';
    if (!cropId) return;
    const res = await fetch('/ajax/stages?crop_id=' + cropId);
    const stages = await res.json();
    stages.forEach(s => {
        const o = document.createElement('option');
        o.value = s.id;
        o.textContent = s.name_sw + ' (' + s.name_en + ')';
        if (selectedStageId && s.id == selectedStageId) o.selected = true;
        stageSel.appendChild(o);
    });
}

async function fetchKbEntries() {
    const params = new URLSearchParams();
    const q = document.getElementById('kbSearch').value.trim();
    const status = document.getElementById('kbStatusFilter').value;
    const cropId = document.getElementById('kbCropFilter').value;
    const stageId = document.getElementById('kbStageFilter').value;
    if (q) params.set('q', q);
    if (status) params.set('status', status);
    if (cropId) params.set('crop_id', cropId);
    if (stageId) params.set('stage_id', stageId);

    const res = await fetch('/officer/ai-mentorship/search?' + params.toString());
    const data = await res.json();
    renderKbRows(data.entries || []);
    updateClearButton();
}

function scheduleKbSearch() {
    clearTimeout(kbSearchTimer);
    kbSearchTimer = setTimeout(fetchKbEntries, 300);
}

document.getElementById('kbSearch').addEventListener('input', scheduleKbSearch);
document.getElementById('kbStatusFilter').addEventListener('change', fetchKbEntries);
document.getElementById('kbCropFilter').addEventListener('change', async function() {
    await loadKbStages(this.value, null);
    fetchKbEntries();
});
document.getElementById('kbStageFilter').addEventListener('change', fetchKbEntries);
document.getElementById('kbClearFilters').addEventListener('click', async function() {
    document.getElementById('kbSearch').value = '';
    document.getElementById('kbStatusFilter').value = '';
    document.getElementById('kbCropFilter').value = '';
    document.getElementById('kbStageFilter').innerHTML = '<option value="">All stages</option>';
    fetchKbEntries();
});

<?php if (!empty($cropId)): ?>
loadKbStages(<?php echo (int)$cropId; ?>, <?php echo (int)($stageId ?? 0) ?: 'null'; ?>);
<?php endif; ?>
updateClearButton();
window.kbEntries = <?php echo json_encode($entries); ?>;

// ── Cascade dropdowns ──────────────────────────────────────────────────────
document.getElementById('cropSel').addEventListener('change', async function() {
    const cropId   = this.value;
    const stageSel = document.getElementById('stageSel');
    const topicSel = document.getElementById('topicSel');
    stageSel.innerHTML = '<option value="">Loading...</option>';
    topicSel.innerHTML = '<option value=""><?php echo __('kb_select_topic'); ?></option>';
    if (!cropId) { stageSel.innerHTML = '<option value=""><?php echo __('kb_select_stage'); ?></option>'; return; }
    const res    = await fetch('/ajax/stages?crop_id=' + cropId);
    const stages = await res.json();
    stageSel.innerHTML = '<option value=""><?php echo __('kb_select_stage'); ?></option>';
    stages.forEach(s => {
        const o = document.createElement('option');
        o.value = s.id; o.textContent = s.name_sw + ' (' + s.name_en + ')';
        stageSel.appendChild(o);
    });
});

// ── Modal helpers ──────────────────────────────────────────────────────────
function openAddModal() {
    document.getElementById('articleModalTitle').textContent = '<?php echo __('kb_add'); ?>';
    document.getElementById('articleForm').action = '/officer/ai-mentorship';
    document.getElementById('articleEntryId').value = '';
    document.getElementById('articleTitle').value = '';
    document.getElementById('articleSituation').value = '';
    document.getElementById('articleSolution').value = '';
    document.getElementById('cropSel').value = '';
    document.getElementById('stageSel').innerHTML = '<option value=""><?php echo __('kb_select_stage'); ?></option>';
    document.getElementById('topicSel').innerHTML = '<option value=""><?php echo __('kb_select_topic'); ?></option>';
    document.getElementById('articleLanguage').value = 'sw';
    document.getElementById('articleModal').classList.remove('hidden');
}

function openEditModal(e) {
    document.getElementById('articleModalTitle').textContent = '<?php echo __('kb_edit_article'); ?>';
    document.getElementById('articleForm').action = '/officer/ai-mentorship/update';
    document.getElementById('articleEntryId').value = e.id;
    document.getElementById('articleTitle').value = e.title || '';
    document.getElementById('articleSituation').value = e.situation || '';
    document.getElementById('articleSolution').value = e.solution || '';
    document.getElementById('articleLanguage').value = e.language || 'sw';
    // Set crop then cascade-load stages
    document.getElementById('cropSel').value = e.crop_id || '';
    if (e.crop_id) {
        fetch('/ajax/stages?crop_id=' + e.crop_id).then(r => r.json()).then(stages => {
            const stageSel = document.getElementById('stageSel');
            stageSel.innerHTML = '<option value=""><?php echo __('kb_select_stage'); ?></option>';
            stages.forEach(s => {
                const o = document.createElement('option');
                o.value = s.id; o.textContent = s.name_sw + ' (' + s.name_en + ')';
                if (s.id == e.stage_id) o.selected = true;
                stageSel.appendChild(o);
            });
        });
    }
    document.getElementById('viewModal').classList.add('hidden');
    document.getElementById('articleModal').classList.remove('hidden');
}

function closeArticleModal() {
    document.getElementById('articleModal').classList.add('hidden');
}

function openViewModal(e) {
    document.getElementById('vCropBadge').textContent  = e.crop_name  || '—';
    document.getElementById('vStageBadge').textContent = e.stage_name || '—';
    document.getElementById('vLangBadge').textContent  = e.language === 'sw' ? 'Kiswahili' : 'English';
    document.getElementById('vTitle').textContent      = e.title      || '';
    document.getElementById('vSituation').textContent  = e.situation  || '—';
    document.getElementById('vSolution').textContent   = e.solution   || '—';
    document.getElementById('vDate').textContent       = e.updated_at ? e.updated_at.substring(0,10) : '';
    document.getElementById('vEditBtn').onclick   = () => openEditModal(e);
    document.getElementById('vDeleteBtn').onclick  = () => confirmDelete(e.id);
    document.getElementById('viewModal').classList.remove('hidden');
}

function confirmDelete(id) {
    if (!confirm('<?php echo __('kb_delete_confirm'); ?>')) return;
    document.getElementById('deleteEntryId').value = id;
    document.getElementById('deleteForm').submit();
}
</script>
