<div class="p-6 md:p-8 max-w-5xl mx-auto">

    <div class="mb-6">
        <h2 class="text-3xl font-extrabold text-on-surface">Omba Ziara</h2>
        <p class="text-on-surface-variant mt-1">Omba afisa wa kilimo akutembelee shambani.</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant p-6 mb-8">
        <h3 class="text-lg font-bold text-on-surface mb-4">Tuma Ombi la Ziara</h3>
        <?php if (isset($_SESSION['flash'])): ?>
            <div class="mb-4 p-3 bg-primary-fixed text-primary rounded-xl text-sm font-bold">
                <?php echo htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="/farmer/visits/request" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-3">
                <label class="block text-sm font-bold text-on-surface-variant mb-2">Sababu ya Ziara *</label>
                <textarea name="reason" required rows="4" placeholder="Eleza kwa nini unahitaji afisa akutembelee..."
                          class="w-full bg-surface-container rounded-xl px-4 py-3 border border-outline-variant focus:border-primary outline-none"></textarea>
            </div>
            <div>
                <label class="block text-sm font-bold text-on-surface-variant mb-2">Tarehe Unayopendelea</label>
                <input type="date" name="preferred_date" class="w-full bg-surface-container rounded-xl px-4 py-3 border border-outline-variant focus:border-primary outline-none">
            </div>
            <div>
                <label class="block text-sm font-bold text-on-surface-variant mb-2">Muda Unayopendelea</label>
                <select name="preferred_time" class="w-full bg-surface-container rounded-xl px-4 py-3 border border-outline-variant focus:border-primary outline-none">
                    <option value="">Wakati wowote</option>
                    <option value="morning">Asubuhi (8–12)</option>
                    <option value="afternoon">Mchana (12–16)</option>
                    <option value="evening">Jioni (16–18)</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-primary text-white font-bold rounded-xl px-4 py-3 hover:bg-primary-container transition-colors">
                    Tuma Ombi
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant overflow-hidden">
        <div class="p-4 border-b border-outline-variant">
            <h3 class="font-bold text-on-surface">Maombi Yako</h3>
        </div>
        <?php if (empty($requests)): ?>
            <div class="p-8 text-center text-on-surface-variant">Hakuna maombi ya ziara bado.</div>
        <?php else: ?>
            <div class="divide-y divide-outline-variant">
                <?php foreach ($requests as $r): ?>
                    <div class="p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h4 class="font-bold text-on-surface">Ombi #<?php echo (int)$r['id']; ?></h4>
                                <p class="text-sm text-on-surface-variant mt-1"><?php echo date('d M Y, H:i', strtotime($r['requested_at'])); ?></p>
                                <p class="mt-2 text-on-surface"><?php echo htmlspecialchars($r['request_reason']); ?></p>
                                <?php if (!empty($r['preferred_date'])): ?>
                                    <p class="mt-2 text-sm text-on-surface-variant">
                                        Unayopendelea: <?php echo date('d M Y', strtotime($r['preferred_date'])); ?>
                                        <?php echo $r['preferred_time'] ? ' (' . ucfirst($r['preferred_time']) . ')' : ''; ?>
                                    </p>
                                <?php endif; ?>
                                <?php if (!empty($r['scheduled_at'])): ?>
                                    <p class="mt-2 text-sm font-bold text-primary">
                                        Imepangwa: <?php echo date('d M Y H:i', strtotime($r['scheduled_at'])); ?>
                                    </p>
                                <?php endif; ?>
                                <?php if (!empty($r['notes'])): ?>
                                    <p class="mt-2 text-sm bg-surface-container p-3 rounded-lg">
                                        <span class="font-bold">Maelezo ya Afisa:</span> <?php echo htmlspecialchars($r['notes']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php
                                $badge = match($r['status']) {
                                    'pending' => 'bg-amber-100 text-amber-800',
                                    'scheduled' => 'bg-primary-fixed text-primary',
                                    'postponed' => 'bg-orange-100 text-orange-800',
                                    'completed' => 'bg-green-100 text-green-800',
                                    'cancelled' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-700',
                                };
                                ?>
                                <span class="inline-block px-3 py-1 rounded-full text-sm font-bold <?php echo $badge; ?>">
                                    <?php echo ucfirst($r['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
