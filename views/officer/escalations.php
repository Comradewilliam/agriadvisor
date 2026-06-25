<?php
// Officer escalations - 3-column design matching UI spec
?>

<div class="flex h-[calc(100vh-64px)] overflow-hidden">

  <!-- Column 1: Escalation List -->
  <aside class="w-72 shrink-0 flex flex-col border-r border-outline-variant bg-surface-container-low">
    <div class="p-4 border-b border-outline-variant">
      <div class="flex items-center gap-2 bg-white border border-outline-variant rounded-xl px-3 py-2">
        <span class="material-symbols-outlined text-on-surface-variant text-base">search</span>
        <input type="text" placeholder="Search escalations..." class="bg-transparent outline-none text-sm flex-1 text-on-surface placeholder-on-surface-variant/60">
      </div>
    </div>
    <div class="px-4 py-2 border-b border-outline-variant">
      <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Active Escalations</p>
    </div>
    <div class="flex-1 overflow-y-auto divide-y divide-outline-variant">
      <?php if (empty($escalations)): ?>
        <div class="p-6 text-center text-outline text-sm">
          <span class="material-symbols-outlined text-3xl mb-2 block text-primary-fixed-dim">check_circle</span>
          Hakuna maswali yanayosubiri jibu. Hongera!
        </div>
      <?php else: ?>
        <?php foreach ($escalations as $i => $esc):
          $priorityMap = ['urgent' => ['bg-error text-white', 'Urgent'], 'high' => ['bg-secondary-fixed text-secondary', 'High'], 'medium' => ['bg-tertiary-fixed text-tertiary', 'Medium']];
          [$priorityCls, $priorityLabel] = $priorityMap[$esc['priority'] ?? 'medium'] ?? ['bg-surface-container text-on-surface-variant', 'Normal'];
          $active = $i === 0;
        ?>
          <div class="p-4 cursor-pointer transition-colors <?php echo $active ? 'bg-secondary-fixed/50 border-l-4 border-secondary' : 'hover:bg-surface-container'; ?>"
               onclick="selectEscalation(<?php echo $esc['id']; ?>)">
            <div class="flex items-start justify-between mb-1.5">
              <p class="font-bold text-on-surface text-sm"><?php echo htmlspecialchars($esc['farmer_name']); ?></p>
              <span class="text-xs font-bold px-2 py-0.5 rounded-full <?php echo $priorityCls; ?>"><?php echo $priorityLabel; ?></span>
            </div>
            <p class="text-xs text-on-surface-variant mb-2">AI Uncertainty: <?php echo htmlspecialchars(mb_substr($esc['question'] ?? '', 0, 35)) . '…'; ?></p>
            <p class="text-xs text-outline flex items-center gap-1">
              <span class="material-symbols-outlined text-xs">schedule</span>
              <?php
                $diff = time() - strtotime($esc['sent_at']);
                echo $diff < 3600 ? floor($diff/60).' mins ago' : floor($diff/3600).' hrs ago';
              ?>
            </p>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </aside>

  <!-- Column 2: Chat Thread -->
  <div class="flex-1 flex flex-col min-w-0 border-r border-outline-variant">
    <?php if (!empty($escalations)): $esc = $escalations[0]; ?>
      <!-- Chat Header -->
      <div class="px-5 py-3 border-b border-outline-variant flex items-center justify-between bg-white shrink-0">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-2xl bg-primary flex items-center justify-center text-white font-bold text-sm">
            <?php echo strtoupper(substr($esc['farmer_name'], 0, 1)); ?>
          </div>
          <div>
            <p class="font-bold text-on-surface"><?php echo htmlspecialchars($esc['farmer_name']); ?></p>
            <p class="text-xs text-on-surface-variant">SMS Channel &bull; <?php echo htmlspecialchars($esc['ward_name'] ?? 'Ward'); ?></p>
          </div>
        </div>
        <button class="flex items-center gap-2 border border-outline-variant rounded-xl px-4 py-2 text-sm font-bold text-on-surface hover:bg-surface-container transition-colors">
          <span class="material-symbols-outlined text-base">call</span> Call Farmer
        </button>
      </div>

      <!-- Messages -->
      <div class="flex-1 overflow-y-auto p-5 space-y-4 bg-surface-container-lowest">
        <!-- AI message that triggered escalation -->
        <div class="flex gap-3">
          <div class="w-9 h-9 rounded-2xl bg-primary flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-white text-base">eco</span>
          </div>
          <div class="bg-white border border-outline-variant rounded-2xl rounded-tl-sm px-4 py-3 max-w-md shadow-sm">
            <p class="text-sm text-on-surface leading-relaxed"><?php echo nl2br(htmlspecialchars($esc['ai_response'] ?? 'AI ilitoa jibu — inaonekana halikuwa na uhakika wa kutosha.')); ?></p>
            <div class="mt-2 bg-error-container border border-error/20 rounded-lg p-2.5 text-xs text-on-error-container">
              <strong>[AI CONFIDENCE LOW]:</strong> Tafadhali, je unaweza kueleza zaidi au kutoa picha ili nipate uhakika?
            </div>
            <p class="text-xs mt-1.5 text-on-surface-variant"><?php echo date('H:i', strtotime($esc['sent_at'])); ?> &bull; BwanaShamba AI</p>
          </div>
        </div>

        <!-- Farmer's question -->
        <div class="flex justify-end">
          <div class="bg-surface-container border border-outline-variant rounded-2xl rounded-tr-sm px-4 py-3 max-w-md">
            <p class="text-sm text-on-surface"><?php echo htmlspecialchars($esc['question']); ?></p>
            <p class="text-xs mt-1 text-on-surface-variant"><?php echo date('H:i', strtotime($esc['sent_at'])); ?> &bull; SMS</p>
          </div>
        </div>
      </div>

      <!-- Reply Area -->
      <div class="p-4 bg-white border-t border-outline-variant shrink-0">
        <?php if (isset($_GET['success'])): ?>
          <div class="mb-3 bg-primary-fixed text-primary rounded-xl px-4 py-2 font-bold text-sm">Jibu limetumwa kwa mkulima.</div>
        <?php endif; ?>
        <form action="/officer/escalations/reply" method="POST" class="flex gap-3">
          <input type="hidden" name="escalation_id" value="<?php echo $esc['id']; ?>">
          <input type="hidden" name="farmer_id" value="<?php echo $esc['farmer_id'] ?? ''; ?>">
          <input type="hidden" name="farmer_phone" value="<?php echo htmlspecialchars($esc['farmer_phone']); ?>">
          <button type="button" class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high transition-colors shrink-0">
            <span class="material-symbols-outlined text-base">attach_file</span>
          </button>
          <div class="flex-1 flex items-center bg-surface-container border border-outline rounded-xl px-4 focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20 transition-all">
            <textarea name="reply" required rows="1" maxlength="155"
                      class="flex-1 bg-transparent outline-none text-sm py-2.5 resize-none text-on-surface placeholder-on-surface-variant/60"
                      placeholder="Andika ujumbe wako hapa..."></textarea>
          </div>
          <button type="submit" class="w-10 h-10 bg-primary rounded-xl flex items-center justify-center text-white hover:bg-primary-container transition-colors shrink-0">
            <span class="material-symbols-outlined text-base">send</span>
          </button>
        </form>
      </div>
    <?php else: ?>
      <div class="flex-1 flex items-center justify-center text-center text-outline p-8">
        <div>
          <span class="material-symbols-outlined text-5xl mb-3 block text-primary-fixed-dim">check_circle</span>
          <p class="text-lg font-bold">Hakuna maswali yanayosubiri!</p>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Column 3: Farmer Details -->
  <aside class="w-72 shrink-0 hidden xl:flex flex-col bg-white border-l border-outline-variant">
    <?php if (!empty($escalations)): $esc = $escalations[0]; ?>
      <div class="p-5 border-b border-outline-variant">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-14 h-14 rounded-2xl bg-surface-container-highest flex items-center justify-center shrink-0 text-2xl font-bold text-primary">
            <?php echo strtoupper(substr($esc['farmer_name'], 0, 1)); ?>
          </div>
          <div>
            <p class="font-bold text-on-surface"><?php echo htmlspecialchars($esc['farmer_name']); ?></p>
            <p class="text-xs text-on-surface-variant flex items-center gap-1">
              <span class="material-symbols-outlined text-xs">location_on</span>
              <?php echo htmlspecialchars($esc['ward_name'] ?? 'Ward'); ?>
            </p>
          </div>
        </div>
        <div class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-on-surface-variant">Primary Crop</span>
            <span class="font-bold text-on-surface">Maize (Mhindi)</span>
          </div>
          <div class="flex justify-between">
            <span class="text-on-surface-variant">Farm Size</span>
            <span class="font-bold text-on-surface">2.5 Acres</span>
          </div>
          <div class="flex justify-between">
            <span class="text-on-surface-variant">Language</span>
            <span class="font-bold text-on-surface">Swahili</span>
          </div>
          <div class="flex justify-between">
            <span class="text-on-surface-variant">Phone</span>
            <span class="font-bold text-on-surface"><?php echo htmlspecialchars($esc['farmer_phone']); ?></span>
          </div>
        </div>
      </div>

      <div class="p-5 border-b border-outline-variant flex-1">
        <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-3">INTERNAL NOTES (NOT VISIBLE TO FARMER)</p>
        <textarea class="w-full bg-surface-container-low border border-outline-variant rounded-xl p-3 text-sm text-on-surface placeholder-on-surface-variant/60 resize-none outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" rows="5" placeholder="Add diagnosis notes or follow-up tasks..."></textarea>
      </div>

      <div class="p-5 space-y-3">
        <form action="/officer/escalations/reply" method="POST">
          <input type="hidden" name="escalation_id" value="<?php echo $esc['id']; ?>">
          <input type="hidden" name="farmer_id" value="<?php echo $esc['farmer_id'] ?? ''; ?>">
          <input type="hidden" name="farmer_phone" value="<?php echo htmlspecialchars($esc['farmer_phone']); ?>">
          <input type="hidden" name="reply" value="[Resolved by officer]">
          <button type="submit" class="w-full flex items-center justify-center gap-2 bg-primary text-white font-bold py-3 rounded-xl hover:bg-primary-container transition-colors">
            <span class="material-symbols-outlined text-base">check_circle</span> Mark as Resolved
          </button>
        </form>
        <button class="w-full flex items-center justify-center gap-2 border border-outline-variant text-on-surface font-bold py-3 rounded-xl hover:bg-surface-container transition-colors">
          <span class="material-symbols-outlined text-base">transfer_within_a_station</span> Assign to Ward Officer
        </button>
      </div>
    <?php endif; ?>
  </aside>
</div>

<script>
function selectEscalation(id) {
  // In a real app this would load the escalation via AJAX
  console.log('Selected escalation:', id);
}
</script>
