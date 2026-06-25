<div class="p-6 md:p-8">
    <div class="mb-6 flex flex-wrap justify-between items-center gap-4">
        <div>
            <h3 class="text-3xl font-bold text-primary">Usimamizi wa Wakulima</h3>
            <p class="text-on-surface-variant">Wakulima <?php echo count($farmers); ?> katika eneo lako.</p>
        </div>
        <a href="/officer/farmers/add" class="bg-primary text-white px-6 py-2 rounded-lg font-bold flex items-center gap-2 hover:bg-primary-container transition-colors">
            <span class="material-symbols-outlined">person_add</span> Ongeza Mkulima
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="mb-4 bg-primary-fixed text-primary rounded-xl px-5 py-3 font-bold">Mkulima amesajiliwa!</div>
    <?php endif; ?>

    <!-- Search bar -->
    <form method="GET" action="/officer/farmers" class="mb-6 flex gap-3">
        <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>"
               placeholder="Tafuta jina au simu..."
               class="flex-1 bg-white border border-outline rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary">
        <button type="submit" class="bg-primary text-white px-5 py-2 rounded-lg font-bold">Tafuta</button>
        <?php if ($search): ?><a href="/officer/farmers" class="border border-outline rounded-lg px-4 py-2 text-on-surface-variant hover:bg-surface-container-low">Safi</a><?php endif; ?>
    </form>

    <div class="bg-white rounded-xl shadow-sm border border-outline-variant overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="text-xs text-on-surface-variant uppercase bg-surface-container">
                    <tr>
                        <th class="p-4">Jina</th>
                        <th class="p-4">Simu</th>
                        <th class="p-4">Kijiji</th>
                        <th class="p-4">Chanzo</th>
                        <th class="p-4">Tarehe</th>
                        <th class="p-4">Hatua</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    <?php if (empty($farmers)): ?>
                        <tr><td colspan="6" class="p-8 text-center text-outline">Hakuna wakulima <?php echo $search ? 'wanaolingana na utafutaji' : 'bado'; ?>.</td></tr>
                    <?php else: ?>
                        <?php foreach ($farmers as $f): ?>
                            <tr class="hover:bg-surface-container-lowest transition-colors">
                                <td class="p-4 font-bold text-on-surface"><?php echo htmlspecialchars($f['name']); ?></td>
                                <td class="p-4 text-on-surface-variant"><?php echo htmlspecialchars($f['phone']); ?></td>
                                <td class="p-4 text-on-surface-variant"><?php echo htmlspecialchars($f['village_name'] ?? '—'); ?></td>
                                <td class="p-4"><span class="bg-surface-container border border-outline-variant text-on-surface-variant text-xs px-2 py-1 rounded"><?php echo ucfirst($f['registered_via']); ?></span></td>
                                <td class="p-4 text-xs text-outline"><?php echo date('d M Y', strtotime($f['registered_at'])); ?></td>
                                <td class="p-4">
                                    <a href="/officer/farmers/view?id=<?php echo $f['id']; ?>" class="text-primary hover:underline font-bold text-sm">Angalia</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
