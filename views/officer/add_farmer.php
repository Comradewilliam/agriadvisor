<div class="p-6 md:p-8">
    <div class="mb-6">
        <h3 class="text-3xl font-bold text-primary">Sajili Mkulima Mpya</h3>
        <p class="text-on-surface-variant">Jaza fomu hapa chini kusajili mkulima.</p>
    </div>

    <?php if (isset($_SESSION['flash'])): ?>
        <div class="mb-4 bg-error-container text-on-error-container rounded-xl px-5 py-3 font-bold"><?php echo htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-sm border border-outline-variant p-8 max-w-2xl">
        <form action="/officer/farmers/add" method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-on-surface-variant mb-2">Jina Kamili *</label>
                    <input type="text" name="name" required class="w-full bg-surface border border-outline rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-on-surface-variant mb-2">Nambari ya Simu *</label>
                    <input type="tel" name="phone" required placeholder="07XXXXXXXX" class="w-full bg-surface border border-outline rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-on-surface-variant mb-2">Kata *</label>
                    <select name="ward_id" id="wardSel" required class="w-full bg-surface border border-outline rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary">
                        <option value="">— Chagua Kata —</option>
                        <?php foreach ($wards as $w): ?>
                            <option value="<?php echo $w['id']; ?>"><?php echo htmlspecialchars($w['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-on-surface-variant mb-2">Kijiji *</label>
                    <select name="village_id" id="villageSel" required class="w-full bg-surface border border-outline rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary">
                        <option value="">— Chagua Kata Kwanza —</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-on-surface-variant mb-2">Zao Kuu</label>
                <select name="crop_id" class="w-full bg-surface border border-outline rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary">
                    <option value="">— Chagua Zao (Hiari) —</option>
                    <?php foreach ($crops as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name_sw']); ?> (<?php echo htmlspecialchars($c['name_en']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex gap-4 pt-4">
                <button type="submit" class="flex-1 bg-primary text-white py-3 rounded-xl font-bold hover:bg-primary-container transition-colors">Sajili Mkulima</button>
                <a href="/officer/farmers" class="flex-1 text-center border border-outline py-3 rounded-xl font-bold text-on-surface-variant hover:bg-surface-container-low transition-colors">Rudi</a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('wardSel').addEventListener('change', async function() {
    const wardId = this.value;
    const villageSel = document.getElementById('villageSel');
    villageSel.innerHTML = '<option value="">Inapakia...</option>';
    if (!wardId) { villageSel.innerHTML = '<option value="">— Chagua Kata Kwanza —</option>'; return; }
    
    const res = await fetch('/ajax/villages?ward_id=' + wardId);
    const villages = await res.json();
    villageSel.innerHTML = '<option value="">— Chagua Kijiji —</option>';
    villages.forEach(v => {
        const opt = document.createElement('option');
        opt.value = v.id; opt.textContent = v.name;
        villageSel.appendChild(opt);
    });
});
</script>
