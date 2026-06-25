<?php
/**
 * views/farmer/register.php  v1.4
 * Swahili-only web registration with full profile details.
 */
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sajili - Shamba Smart</title>
    <?php require dirname(__DIR__) . '/partials/head_assets.php'; ?>
</head>
<body class="bg-surface text-on-surface min-h-screen flex flex-col items-center justify-center p-4 py-8">

    <div class="w-full max-w-xl bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
        <div class="bg-primary p-8 text-center text-white relative">
            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCI+PGNpcmNsZSBjeD0iMTAiIGN5PSIxMCIgcj0iMSIgZmlsbD0iI2ZmZiIgb3BhY2l0eT0iMC4xIi8+PC9zdmc+')] opacity-20"></div>
            <span class="material-symbols-outlined text-5xl mb-2 relative z-10 text-primary-fixed">grass</span>
            <h1 class="text-2xl font-extrabold relative z-10">Shamba Smart</h1>
            <p class="text-sm text-primary-fixed font-medium mt-1 relative z-10">Fungua akaunti yako ya ukulima</p>
        </div>

        <div class="p-8">
            <form id="registerForm" class="space-y-6">
                <!-- Personal Info -->
                <div>
                    <h3 class="text-base font-bold text-primary mb-3">Taarifa Binafsi</h3>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Jina la Kwanza *</label>
                            <input type="text" name="first_name" required placeholder="Mf. Juma" 
                                   class="w-full bg-surface-container px-4 py-3 rounded-xl border border-transparent focus:border-primary outline-none text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Jina la Mwisho *</label>
                            <input type="text" name="last_name" required placeholder="Mf. Hamisi" 
                                   class="w-full bg-surface-container px-4 py-3 rounded-xl border border-transparent focus:border-primary outline-none text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Namba ya Simu *</label>
                        <input type="tel" name="phone" required placeholder="07XX XXX XXX" pattern="^0[6-7][0-9]{8}$"
                               class="w-full bg-surface-container px-4 py-3 rounded-xl border border-transparent focus:border-primary outline-none text-sm">
                        <p class="text-xs text-on-surface-variant mt-1">Inatumika kupokea SMS na kuingia kwenye mfumo.</p>
                    </div>
                </div>

                <hr class="border-surface-container">

                <!-- Location Info -->
                <div>
                    <h3 class="text-base font-bold text-primary mb-3">Eneo Lako</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Wilaya (District) *</label>
                            <select name="district_id" id="district_id" required onchange="loadWards(this.value)"
                                    class="w-full bg-surface-container px-4 py-3 rounded-xl border border-transparent focus:border-primary outline-none text-sm">
                                <option value="">— Chagua Wilaya —</option>
                                <?php foreach ($districts ?? [] as $d): ?>
                                    <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name'] . ($d['region'] ? ' (' . $d['region'] . ')' : '')); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Kata (Ward) *</label>
                            <select name="ward_id" id="ward_id" required onchange="loadVillages(this.value)"
                                    class="w-full bg-surface-container px-4 py-3 rounded-xl border border-transparent focus:border-primary outline-none text-sm">
                                <option value="">— Chagua Kata —</option>
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
                    Kamilisha Usajili <span class="material-symbols-outlined text-sm">check_circle</span>
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-on-surface-variant">Umeshasajiliwa? 
                    <a href="/farmer/login" class="text-primary font-bold hover:underline">Ingia hapa</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        async function loadWards(districtId) {
            const wardSel = document.getElementById('ward_id');
            const villageSel = document.getElementById('village_id');
            wardSel.innerHTML = '<option value="">Inapakia...</option>';
            villageSel.innerHTML = '<option value="">— Chagua Kijiji —</option>';
            if (!districtId) {
                wardSel.innerHTML = '<option value="">— Chagua Kata —</option>';
                return;
            }
            try {
                const res = await fetch('/ajax/wards?district_id=' + districtId);
                const data = await res.json();
                wardSel.innerHTML = '<option value="">— Chagua Kata —</option>';
                data.forEach(w => {
                    const opt = document.createElement('option');
                    opt.value = w.id;
                    opt.textContent = w.name;
                    wardSel.appendChild(opt);
                });
            } catch (e) {
                wardSel.innerHTML = '<option value="">Hitilafu</option>';
            }
        }

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
                    sel.appendChild(opt);
                });
            } catch(e) {
                sel.innerHTML = '<option value="">Error</option>';
            }
        }

        (function initDistrict() {
            const dist = document.getElementById('district_id');
            if (dist && dist.options.length === 2) {
                dist.selectedIndex = 1;
                loadWards(dist.value);
            }
        })();

        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            const err = document.getElementById('errorMsg');
            const errTxt = document.getElementById('errorText');
            
            btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-sm">progress_activity</span> Inasajili...';
            btn.disabled = true;
            err.classList.add('hidden');

            try {
                const res = await fetch('/api/auth/register', {
                    method: 'POST',
                    body: new FormData(e.target)
                });
                const data = await res.json();

                if (data.ok) {
                    window.location.href = data.redirect;
                } else {
                    errTxt.textContent = data.msg;
                    err.classList.remove('hidden');
                    btn.innerHTML = 'Kamilisha Usajili <span class="material-symbols-outlined text-sm">check_circle</span>';
                    btn.disabled = false;
                }
            } catch (error) {
                errTxt.textContent = 'Hitilafu ya mtandao.';
                err.classList.remove('hidden');
                btn.innerHTML = 'Kamilisha Usajili <span class="material-symbols-outlined text-sm">check_circle</span>';
                btn.disabled = false;
            }
        });
    </script>
</body>
</html>
