<?php
/**
 * views/farmer/complete_profile.php  v1.4
 * Gate page for USSD-registered farmers logging in for the first time on web.
 */
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kamilisha Wasifu - Shamba Smart</title>
    <?php require dirname(__DIR__) . '/partials/head_assets.php'; ?>
</head>
<body class="bg-surface text-on-surface min-h-screen flex flex-col items-center justify-center p-4 py-8">

    <div class="w-full max-w-xl bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
        <div class="bg-secondary p-8 text-center text-white relative">
            <span class="material-symbols-outlined text-5xl mb-2 text-secondary-fixed">assignment_ind</span>
            <h1 class="text-2xl font-extrabold">Kamilisha Wasifu Wako</h1>
            <p class="text-sm text-secondary-fixed font-medium mt-1">Karibu! Tunahitaji taarifa zaidi kuhusu shamba lako.</p>
        </div>

        <div class="p-8">
            <form id="completeProfileForm" class="space-y-6">
                <!-- Location Info -->
                <div>
                    <h3 class="text-base font-bold text-primary mb-3">Eneo Lako</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Kata (Ward) *</label>
                            <select name="ward_id" id="ward_id" required onchange="loadVillages(this.value)"
                                    class="w-full bg-surface-container px-4 py-3 rounded-xl border border-transparent focus:border-primary outline-none text-sm">
                                <option value="">— Chagua Kata —</option>
                                <?php foreach ($wards ?? [] as $w): ?>
                                    <option value="<?php echo $w['id']; ?>" <?php echo ($farmer['ward_id']??0) == $w['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($w['district_name'].' → '.$w['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Kijiji (Village) *</label>
                            <select name="village_id" id="village_id" required
                                    class="w-full bg-surface-container px-4 py-3 rounded-xl border border-transparent focus:border-primary outline-none text-sm">
                                <option value="">— Chagua Kijiji —</option>
                            </select>
                        </div>
                    </div>
                </div>

                <hr class="border-surface-container">

                <!-- Farm Details -->
                <div>
                    <h3 class="text-base font-bold text-primary mb-3">Maelezo ya Shamba</h3>
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Ukubwa wa Shamba (Ekari) *</label>
                        <input type="number" step="0.25" name="farm_size_acres" required placeholder="Mf. 2.5"
                               class="w-full bg-surface-container px-4 py-3 rounded-xl border border-transparent focus:border-primary outline-none text-sm">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Zao Kuu *</label>
                            <select name="primary_crop_id" required class="w-full bg-surface-container px-4 py-3 rounded-xl border border-transparent focus:border-primary outline-none text-sm">
                                <option value="">— Chagua —</option>
                                <?php foreach ($crops ?? [] as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name_sw']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Zao la Pili (Hiari)</label>
                            <select name="secondary_crop_ids[]" class="w-full bg-surface-container px-4 py-3 rounded-xl border border-transparent focus:border-primary outline-none text-sm">
                                <option value="">— Hakuna —</option>
                                <?php foreach ($crops ?? [] as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name_sw']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Zao la Tatu (Hiari)</label>
                            <select name="secondary_crop_ids[]" class="w-full bg-surface-container px-4 py-3 rounded-xl border border-transparent focus:border-primary outline-none text-sm">
                                <option value="">— Hakuna —</option>
                                <?php foreach ($crops ?? [] as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name_sw']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="errorMsg" class="hidden bg-red-50 text-red-600 text-sm font-bold p-3 rounded-xl border border-red-100 flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">error</span>
                    <span id="errorText"></span>
                </div>

                <button type="submit" class="w-full bg-primary text-white font-bold py-3.5 rounded-xl hover:bg-primary-container shadow-md transition-all flex items-center justify-center gap-2">
                    Hifadhi na Uendelee <span class="material-symbols-outlined text-sm">check_circle</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        const preselectedVillage = <?php echo json_encode($farmer['village_id'] ?? null); ?>;
        
        async function loadVillages(wardId) {
            const sel = document.getElementById('village_id');
            sel.innerHTML = '<option value="">Inapakia...</option>';
            if (!wardId) { sel.innerHTML = '<option value="">— Chagua Kijiji —</option>'; return; }
            try {
                const res = await fetch('/ajax/villages?ward_id=' + wardId);
                const data = await res.json();
                sel.innerHTML = '<option value="">— Chagua Kijiji —</option>';
                data.forEach(v => {
                    const opt = document.createElement('option');
                    opt.value = v.id; opt.textContent = v.name;
                    if (preselectedVillage && v.id == preselectedVillage) opt.selected = true;
                    sel.appendChild(opt);
                });
            } catch(e) {
                sel.innerHTML = '<option value="">Error</option>';
            }
        }

        const initialWard = document.getElementById('ward_id').value;
        if (initialWard) {
            loadVillages(initialWard);
        }

        document.getElementById('completeProfileForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            const err = document.getElementById('errorMsg');
            const errTxt = document.getElementById('errorText');
            
            btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-sm">progress_activity</span> Inahifadhi...';
            btn.disabled = true;
            err.classList.add('hidden');

            try {
                const res = await fetch('/farmer/profile/complete', {
                    method: 'POST',
                    body: new FormData(e.target)
                });
                const data = await res.json();

                if (data.ok) {
                    window.location.href = data.redirect;
                } else {
                    errTxt.textContent = data.msg;
                    err.classList.remove('hidden');
                    btn.innerHTML = 'Hifadhi na Uendelee <span class="material-symbols-outlined text-sm">check_circle</span>';
                    btn.disabled = false;
                }
            } catch (error) {
                errTxt.textContent = 'Hitilafu ya mtandao.';
                err.classList.remove('hidden');
                btn.innerHTML = 'Hifadhi na Uendelee <span class="material-symbols-outlined text-sm">check_circle</span>';
                btn.disabled = false;
            }
        });
    </script>
</body>
</html>
