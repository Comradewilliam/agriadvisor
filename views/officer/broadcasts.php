<?php
// Officer Broadcasts — Send mass alert/notification to farmers
// $officer, $wards, $crops, $broadcasts are passed from BroadcastController
$success = htmlspecialchars($_GET['success'] ?? '');
$error   = htmlspecialchars($_GET['error']   ?? '');
?>
<div class="p-6 md:p-8 max-w-5xl mx-auto">

  <!-- Header -->
  <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
      <h2 class="text-3xl font-bold text-on-surface flex items-center gap-3">
        <span class="material-symbols-outlined text-primary text-4xl">campaign</span>
        Broadcast Message
      </h2>
      <p class="text-on-surface-variant mt-1">Send an alert or announcement to farmers via SMS and Web Chat.</p>
    </div>
  </div>

  <?php if ($success): ?>
    <div class="mb-6 bg-primary-fixed border border-primary/20 text-primary rounded-2xl p-4 flex items-center gap-3">
      <span class="material-symbols-outlined">check_circle</span>
      <span class="font-semibold"><?php echo $success; ?></span>
    </div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="mb-6 bg-error-container border border-error/20 text-error rounded-2xl p-4 flex items-center gap-3">
      <span class="material-symbols-outlined">error</span>
      <span class="font-semibold"><?php echo $error; ?></span>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Compose Form -->
    <div class="lg:col-span-2">
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant overflow-hidden">
        <div class="p-5 border-b border-outline-variant bg-surface-container-low">
          <h3 class="font-bold text-on-surface flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-base">edit_note</span>
            Compose Broadcast
          </h3>
        </div>
        <form method="POST" action="/officer/broadcasts/send" class="p-6 space-y-6">

          <!-- Message -->
          <div>
            <label class="block text-sm font-bold text-on-surface mb-2">Message (Ujumbe) *</label>
            <textarea name="message" rows="5" maxlength="160" required
              placeholder="Andika ujumbe wako hapa... (max 160 characters)"
              id="broadcastMsg"
              class="w-full border border-outline-variant rounded-xl px-4 py-3 text-sm text-on-surface bg-surface-container-lowest outline-none focus:ring-2 focus:ring-primary resize-none"
            ></textarea>
            <div class="flex justify-between mt-1">
              <p class="text-xs text-on-surface-variant">Ujumbe utaonekana kwenye SMS na Web Chat ya mkulima.</p>
              <span id="msgCount" class="text-xs font-bold text-on-surface-variant">160</span>
            </div>
          </div>

          <!-- Target: Wards -->
          <div>
            <label class="block text-sm font-bold text-on-surface mb-2">Target Wards (Kata)</label>
            <div class="border border-outline-variant rounded-xl divide-y divide-outline-variant overflow-hidden">
              <label class="flex items-center gap-3 px-4 py-3 hover:bg-surface-container cursor-pointer">
                <input type="checkbox" name="ward_ids[]" value="all" class="w-4 h-4 rounded accent-primary" id="allWards" onchange="toggleAllWards(this)">
                <span class="text-sm font-semibold text-primary">All Wards (Kata Zote)</span>
              </label>
              <?php foreach ($wards as $w): ?>
                <label class="ward-row flex items-center gap-3 px-4 py-3 hover:bg-surface-container cursor-pointer">
                  <input type="checkbox" name="ward_ids[]" value="<?php echo $w['id']; ?>" class="ward-cb w-4 h-4 rounded accent-primary">
                  <span class="text-sm text-on-surface"><?php echo htmlspecialchars($w['name']); ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Optional crop filter -->
          <div>
            <label class="block text-sm font-bold text-on-surface mb-2">Filter by Crop (Zao) <span class="text-on-surface-variant font-normal">— optional</span></label>
            <select name="crop_id" class="w-full border border-outline-variant rounded-xl px-4 py-3 text-sm text-on-surface bg-surface-container-lowest outline-none focus:ring-2 focus:ring-primary">
              <option value="0">— All Crops (Mazao Yote) —</option>
              <?php foreach ($crops as $c): ?>
                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name_sw']); ?> (<?php echo htmlspecialchars($c['name_en']); ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Channels info -->
          <div class="bg-primary-fixed/40 rounded-xl p-4 flex items-start gap-3">
            <span class="material-symbols-outlined text-primary mt-0.5">info</span>
            <div class="text-sm text-primary">
              <p class="font-bold mb-1">Delivery Channels</p>
              <p>This broadcast will be delivered via <strong>SMS</strong> (Africa's Talking) and will also appear in each farmer's <strong>Web Chat</strong> as an official notification labelled [TAARIFA RASMI].</p>
            </div>
          </div>

          <button type="submit"
            class="w-full bg-primary text-white py-3 rounded-xl text-sm font-bold hover:bg-primary-container transition-colors flex items-center justify-center gap-2 shadow-sm">
            <span class="material-symbols-outlined">send</span>
            Tuma Ujumbe (Send Broadcast)
          </button>
        </form>
      </div>
    </div>

    <!-- Sent History sidebar -->
    <div class="space-y-4">
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant overflow-hidden">
        <div class="p-4 border-b border-outline-variant bg-surface-container-low">
          <h3 class="font-bold text-on-surface text-sm">Recent Broadcasts</h3>
        </div>
        <div class="divide-y divide-outline-variant">
          <?php if (empty($broadcasts)): ?>
            <div class="p-6 text-center text-on-surface-variant text-sm">No broadcasts sent yet.</div>
          <?php else: ?>
            <?php foreach ($broadcasts as $b): ?>
              <div class="p-4">
                <div class="flex items-center justify-between mb-1">
                  <span class="text-xs font-bold text-primary bg-primary-fixed px-2 py-0.5 rounded-full">
                    <?php echo $b['sent_count']; ?>/<?php echo $b['target_count']; ?> farmers
                  </span>
                  <span class="text-[10px] text-on-surface-variant"><?php echo date('M d, H:i', strtotime($b['sent_at'])); ?></span>
                </div>
                <p class="text-sm text-on-surface line-clamp-2"><?php echo htmlspecialchars($b['message']); ?></p>
                <?php if ($b['target_description']): ?>
                  <p class="text-[10px] text-on-surface-variant mt-1"><?php echo htmlspecialchars($b['target_description']); ?></p>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Quick Tips -->
      <div class="bg-tertiary-fixed rounded-2xl p-4">
        <h4 class="font-bold text-tertiary text-sm mb-2 flex items-center gap-2">
          <span class="material-symbols-outlined text-sm">tips_and_updates</span> Broadcast Tips
        </h4>
        <ul class="text-xs text-tertiary space-y-1">
          <li>• Keep SMS messages under 160 characters</li>
          <li>• Use Kiswahili for best farmer comprehension</li>
          <li>• Always target the smallest relevant group</li>
          <li>• Broadcasts appear in Web Chat as [TAARIFA RASMI]</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<script>
const msgEl    = document.getElementById('broadcastMsg');
const countEl  = document.getElementById('msgCount');
msgEl.addEventListener('input', () => {
  countEl.textContent = 160 - msgEl.value.length;
  countEl.className = msgEl.value.length > 140
    ? 'text-xs font-bold text-error'
    : 'text-xs font-bold text-on-surface-variant';
});

function toggleAllWards(el) {
  document.querySelectorAll('.ward-cb').forEach(cb => cb.disabled = el.checked);
}
</script>
