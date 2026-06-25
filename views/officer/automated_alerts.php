<div class="p-6 md:p-8 max-w-5xl mx-auto">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold text-on-surface"><?php echo __('auto_alerts_title'); ?></h2>
            <p class="text-on-surface-variant"><?php echo __('auto_alerts_sub'); ?></p>
        </div>
        <button onclick="document.getElementById('alertModal').classList.remove('hidden')"
                class="bg-primary text-white px-5 py-2.5 rounded-xl font-bold flex items-center gap-2 hover:bg-primary-container">
            <span class="material-symbols-outlined">add</span> <?php echo __('auto_alert_add'); ?>
        </button>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="mb-4 bg-primary-fixed text-primary rounded-xl px-5 py-3 font-bold"><?php echo __('visit_saved'); ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-surface-container text-xs uppercase text-on-surface-variant">
                    <tr>
                        <th class="p-4"><?php echo __('auto_alert_type'); ?></th>
                        <th class="p-4"><?php echo __('alert_title'); ?></th>
                        <th class="p-4"><?php echo __('auto_alert_trigger'); ?></th>
                        <th class="p-4"><?php echo __('alert_ward'); ?></th>
                        <th class="p-4"><?php echo __('auto_alert_active'); ?></th>
                        <th class="p-4"><?php echo __('actions'); ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    <?php if (empty($alerts)): ?>
                        <tr><td colspan="6" class="p-8 text-center text-outline"><?php echo __('no_data'); ?></td></tr>
                    <?php else: foreach ($alerts as $a):
                        $typeLabel = match($a['alert_type']) {
                            'weather' => __('auto_alert_weather'),
                            'welcome' => __('auto_alert_welcome'),
                            'visit' => __('auto_alert_visit'),
                            'crop_advisory' => __('kb_crop'),
                            default => __('auto_alert_custom'),
                        };
                        $triggerLabel = match($a['trigger_event']) {
                            'on_register' => __('trigger_register'),
                            'on_visit_scheduled' => __('trigger_visit_sched'),
                            'on_visit_reminder' => __('trigger_visit_rem'),
                            'weather_daily' => __('trigger_weather'),
                            default => __('trigger_manual'),
                        };
                    ?>
                        <tr class="hover:bg-surface-container-lowest">
                            <td class="p-4 font-medium"><?php echo $typeLabel; ?></td>
                            <td class="p-4">
                                <p class="font-bold text-on-surface"><?php echo htmlspecialchars($a['title']); ?></p>
                                <p class="text-xs text-outline line-clamp-2 mt-1"><?php echo htmlspecialchars($a['message_template']); ?></p>
                            </td>
                            <td class="p-4 text-on-surface-variant"><?php echo $triggerLabel; ?></td>
                            <td class="p-4"><?php echo htmlspecialchars($a['ward_name'] ?? __('alert_district')); ?></td>
                            <td class="p-4">
                                <span class="text-xs font-bold px-2 py-1 rounded-full <?php echo $a['is_active'] ? 'bg-primary-fixed text-primary' : 'bg-surface-container text-outline'; ?>">
                                    <?php echo $a['is_active'] ? __('auto_alert_active') : 'Off'; ?>
                                </span>
                            </td>
                            <td class="p-4">
                                <div class="flex gap-2 flex-wrap">
                                    <button type="button" onclick='openEditAlertModal(<?php echo json_encode($a, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                            class="text-xs font-bold text-on-surface-variant hover:underline">Edit</button>
                                    <form method="POST" action="/officer/automated-alerts/toggle">
                                        <input type="hidden" name="id" value="<?php echo (int)$a['id']; ?>">
                                        <button type="submit" class="text-xs font-bold text-primary hover:underline"><?php echo $a['is_active'] ? 'Disable' : 'Enable'; ?></button>
                                    </form>
                                    <form method="POST" action="/officer/automated-alerts/delete" onsubmit="return confirm('Delete this alert rule?');">
                                        <input type="hidden" name="id" value="<?php echo (int)$a['id']; ?>">
                                        <button type="submit" class="text-xs font-bold text-error hover:underline"><?php echo __('delete'); ?></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 bg-surface-container-low rounded-2xl p-5 text-sm text-on-surface-variant">
        <p class="font-bold text-on-surface mb-2">Template variables</p>
        <p>Use <code class="bg-white px-1 rounded">{name}</code>, <code class="bg-white px-1 rounded">{village}</code>, <code class="bg-white px-1 rounded">{crop}</code>, <code class="bg-white px-1 rounded">{visit_date}</code> in messages.</p>
    </div>
</div>

<div id="alertModal" class="hidden modal-backdrop">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl modal-panel">
        <div class="p-5 border-b border-outline-variant flex justify-between items-center sticky top-0 bg-white">
            <h4 class="text-lg font-bold"><?php echo __('auto_alert_add'); ?></h4>
            <button type="button" onclick="document.getElementById('alertModal').classList.add('hidden')"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form method="POST" action="/officer/automated-alerts" class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase"><?php echo __('auto_alert_type'); ?></label>
                    <select name="alert_type" class="w-full border border-outline-variant rounded-xl px-3 py-2 text-sm">
                        <option value="welcome"><?php echo __('auto_alert_welcome'); ?></option>
                        <option value="weather"><?php echo __('auto_alert_weather'); ?></option>
                        <option value="visit"><?php echo __('auto_alert_visit'); ?></option>
                        <option value="crop_advisory"><?php echo __('kb_crop'); ?> Advisory</option>
                        <option value="custom"><?php echo __('auto_alert_custom'); ?></option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase"><?php echo __('auto_alert_trigger'); ?></label>
                    <select name="trigger_event" class="w-full border border-outline-variant rounded-xl px-3 py-2 text-sm">
                        <option value="on_register"><?php echo __('trigger_register'); ?></option>
                        <option value="on_visit_scheduled"><?php echo __('trigger_visit_sched'); ?></option>
                        <option value="on_visit_reminder"><?php echo __('trigger_visit_rem'); ?></option>
                        <option value="weather_daily"><?php echo __('trigger_weather'); ?></option>
                        <option value="manual"><?php echo __('trigger_manual'); ?></option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase"><?php echo __('alert_title'); ?> *</label>
                <input type="text" name="title" required maxlength="255" class="w-full border border-outline-variant rounded-xl px-3 py-2 text-sm" placeholder="e.g. Welcome new farmers">
            </div>
            <div>
                <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase"><?php echo __('auto_alert_message'); ?> *</label>
                <textarea name="message_template" required rows="4" class="w-full border border-outline-variant rounded-xl px-3 py-2 text-sm" placeholder="Karibu {name}! Afisa wa kilimo atakutembelea {visit_date}."></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase"><?php echo __('alert_ward'); ?></label>
                    <select name="ward_id" class="w-full border border-outline-variant rounded-xl px-3 py-2 text-sm">
                        <option value=""><?php echo __('alert_district'); ?></option>
                        <?php foreach ($wards as $w): ?>
                            <option value="<?php echo (int)$w['id']; ?>"><?php echo htmlspecialchars($w['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase">Reminder (hours before visit)</label>
                    <input type="number" name="trigger_offset_hours" value="24" min="1" max="168" class="w-full border border-outline-variant rounded-xl px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase">Channel</label>
                <select name="channel" class="w-full border border-outline-variant rounded-xl px-3 py-2 text-sm">
                    <option value="sms">SMS</option>
                    <option value="both">SMS + Web</option>
                </select>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 bg-primary text-white py-3 rounded-xl font-bold"><?php echo __('save_changes'); ?></button>
                <button type="button" onclick="document.getElementById('alertModal').classList.add('hidden')" class="flex-1 border border-outline py-3 rounded-xl font-bold text-on-surface-variant"><?php echo __('cancel'); ?></button>
            </div>
        </form>
    </div>
</div>

<div id="editAlertModal" class="hidden modal-backdrop">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl modal-panel">
        <div class="p-5 border-b border-outline-variant flex justify-between items-center sticky top-0 bg-white">
            <h4 class="text-lg font-bold">Edit alert rule</h4>
            <button type="button" onclick="document.getElementById('editAlertModal').classList.add('hidden')"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form method="POST" action="/officer/automated-alerts/update" class="p-6 space-y-4" id="editAlertForm">
            <input type="hidden" name="id" id="editAlertId">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase"><?php echo __('auto_alert_type'); ?></label>
                    <select name="alert_type" id="editAlertType" class="w-full border border-outline-variant rounded-xl px-3 py-2 text-sm">
                        <option value="welcome"><?php echo __('auto_alert_welcome'); ?></option>
                        <option value="weather"><?php echo __('auto_alert_weather'); ?></option>
                        <option value="visit"><?php echo __('auto_alert_visit'); ?></option>
                        <option value="crop_advisory"><?php echo __('kb_crop'); ?> Advisory</option>
                        <option value="custom"><?php echo __('auto_alert_custom'); ?></option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase"><?php echo __('auto_alert_trigger'); ?></label>
                    <select name="trigger_event" id="editAlertTrigger" class="w-full border border-outline-variant rounded-xl px-3 py-2 text-sm">
                        <option value="on_register"><?php echo __('trigger_register'); ?></option>
                        <option value="on_visit_scheduled"><?php echo __('trigger_visit_sched'); ?></option>
                        <option value="on_visit_reminder"><?php echo __('trigger_visit_rem'); ?></option>
                        <option value="weather_daily"><?php echo __('trigger_weather'); ?></option>
                        <option value="manual"><?php echo __('trigger_manual'); ?></option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase"><?php echo __('alert_title'); ?> *</label>
                <input type="text" name="title" id="editAlertTitle" required maxlength="255" class="w-full border border-outline-variant rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase"><?php echo __('auto_alert_message'); ?> *</label>
                <textarea name="message_template" id="editAlertMessage" required rows="4" class="w-full border border-outline-variant rounded-xl px-3 py-2 text-sm"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase"><?php echo __('alert_ward'); ?></label>
                    <select name="ward_id" id="editAlertWard" class="w-full border border-outline-variant rounded-xl px-3 py-2 text-sm">
                        <option value=""><?php echo __('alert_district'); ?></option>
                        <?php foreach ($wards as $w): ?>
                            <option value="<?php echo (int)$w['id']; ?>"><?php echo htmlspecialchars($w['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase">Reminder (hours)</label>
                    <input type="number" name="trigger_offset_hours" id="editAlertOffset" value="24" min="1" max="168" class="w-full border border-outline-variant rounded-xl px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-on-surface-variant mb-1 uppercase">Channel</label>
                <select name="channel" id="editAlertChannel" class="w-full border border-outline-variant rounded-xl px-3 py-2 text-sm">
                    <option value="sms">SMS</option>
                    <option value="both">SMS + Web</option>
                </select>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 bg-primary text-white py-3 rounded-xl font-bold"><?php echo __('save_changes'); ?></button>
                <button type="button" onclick="document.getElementById('editAlertModal').classList.add('hidden')" class="flex-1 border border-outline py-3 rounded-xl font-bold text-on-surface-variant"><?php echo __('cancel'); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditAlertModal(a) {
    document.getElementById('editAlertId').value = a.id;
    document.getElementById('editAlertType').value = a.alert_type || 'custom';
    document.getElementById('editAlertTrigger').value = a.trigger_event || 'manual';
    document.getElementById('editAlertTitle').value = a.title || '';
    document.getElementById('editAlertMessage').value = a.message_template || '';
    document.getElementById('editAlertWard').value = a.ward_id || '';
    document.getElementById('editAlertOffset').value = a.trigger_offset_hours || 24;
    document.getElementById('editAlertChannel').value = a.channel || 'sms';
    document.getElementById('editAlertModal').classList.remove('hidden');
}
</script>
