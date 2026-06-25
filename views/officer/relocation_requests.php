<?php
/**
 * officer/relocation_requests.php
 * WAO: Request farmer relocations & approve incoming requests
 */
$db = \App\Core\Database::getInstance()->getConnection();
$officerId = (int)$_SESSION['officer_id'];

// Get wards this officer manages
$myWardStmt = $db->prepare("SELECT ward_id FROM officer_wards WHERE officer_id = ?");
$myWardStmt->execute([$officerId]);
$myWardIds = array_column($myWardStmt->fetchAll(), 'ward_id');

// Outgoing requests (requests I made)
$outgoing = [];
// Incoming requests (to my wards)
$incoming = [];

if (!empty($myWardIds)) {
    $ph = implode(',', $myWardIds);

    $outgoing = $db->prepare("
        SELECT r.*, CONCAT(f.first_name, ' ', f.last_name) AS farmer_name, f.phone AS farmer_phone,
               fw.name AS from_ward_name, tw.name AS to_ward_name
        FROM farmer_relocation_requests r
        JOIN farmers f ON f.id = r.farmer_id
        JOIN wards fw ON fw.id = r.from_ward_id
        JOIN wards tw ON tw.id = r.to_ward_id
        WHERE r.requested_by = :oid
        ORDER BY r.created_at DESC
    ");
    $outgoing->execute([':oid' => $officerId]);
    $outgoing = $outgoing->fetchAll();

    $incomingStmt = $db->prepare("
        SELECT r.*, CONCAT(f.first_name, ' ', f.last_name) AS farmer_name, f.phone AS farmer_phone,
               fw.name AS from_ward_name, tw.name AS to_ward_name,
               req.name AS requested_by_name
        FROM farmer_relocation_requests r
        JOIN farmers f ON f.id = r.farmer_id
        JOIN wards fw ON fw.id = r.from_ward_id
        JOIN wards tw ON tw.id = r.to_ward_id
        JOIN users req ON req.id = r.requested_by
        WHERE r.to_ward_id IN ({$ph})
          AND r.requested_by != :oid
          AND r.status = 'pending'
        ORDER BY r.created_at DESC
    ");
    $incomingStmt->execute([':oid' => $officerId]);
    $incoming = $incomingStmt->fetchAll();
}

// All farmers in my wards for the request form
$myFarmers = [];
if (!empty($myWardIds)) {
    $ph = implode(',', $myWardIds);
    $myFarmers = $db->query("
        SELECT f.id, CONCAT(f.first_name, ' ', f.last_name) AS name, f.phone, w.name AS ward_name
        FROM farmers f JOIN wards w ON w.id = f.ward_id
        WHERE f.ward_id IN ({$ph}) AND f.is_active = 1
        ORDER BY f.first_name, f.last_name
    ")->fetchAll();
}

// All wards (for relocation target)
$allWards = $db->query("
    SELECT w.id, w.name, d.name AS district_name
    FROM wards w JOIN districts d ON d.id = w.district_id
    ORDER BY d.name, w.name
")->fetchAll();
?>

<div class="p-6 md:p-8 max-w-[1200px] mx-auto">
  <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
    <div>
      <h1 class="text-3xl font-bold text-on-surface">Farmer Relocation Requests</h1>
      <p class="text-on-surface-variant mt-1">Request a farmer move to another ward or approve incoming requests.</p>
    </div>
    <button onclick="document.getElementById('newRelocationModal').classList.remove('hidden')"
            class="flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-bold hover:bg-primary-container shadow">
      <span class="material-symbols-outlined">transfer_within_a_station</span> New Request
    </button>
  </div>

  <!-- Incoming Approvals -->
  <?php if (!empty($incoming)): ?>
  <div class="mb-8">
    <h2 class="text-lg font-extrabold text-on-surface mb-4 flex items-center gap-2">
      <span class="w-2.5 h-2.5 rounded-full bg-error animate-pulse"></span>
      Pending Approval — Incoming to Your Ward (<?php echo count($incoming); ?>)
    </h2>
    <div class="space-y-3">
      <?php foreach ($incoming as $r): ?>
      <div class="bg-white rounded-2xl border border-outline-variant shadow-sm p-5 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <img src="<?php echo \App\Helpers\Avatar::url($r['farmer_name'], '154212', 40); ?>"
               class="w-10 h-10 rounded-full" alt="">
          <div>
            <p class="font-extrabold text-on-surface"><?php echo htmlspecialchars($r['farmer_name']); ?></p>
            <p class="text-sm text-on-surface-variant"><?php echo htmlspecialchars($r['farmer_phone']); ?></p>
            <p class="text-xs mt-1 text-on-surface-variant">
              From <strong class="text-on-surface"><?php echo htmlspecialchars($r['from_ward_name']); ?></strong>
              → To <strong class="text-primary"><?php echo htmlspecialchars($r['to_ward_name']); ?></strong>
              · Requested by <?php echo htmlspecialchars($r['requested_by_name']); ?>
            </p>
            <?php if ($r['notes']): ?>
            <p class="text-xs italic text-on-surface-variant mt-1">"<?php echo htmlspecialchars($r['notes']); ?>"</p>
            <?php endif; ?>
          </div>
        </div>
        <div class="flex gap-2">
          <form action="/officer/relocation/approve" method="POST">
            <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
            <button type="submit" class="flex items-center gap-1.5 bg-primary text-white px-4 py-2 rounded-xl text-sm font-bold hover:bg-primary-container transition-colors">
              <span class="material-symbols-outlined text-sm">check</span> Approve
            </button>
          </form>
          <form action="/officer/relocation/reject" method="POST">
            <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
            <button type="submit" class="flex items-center gap-1.5 border border-error text-error px-4 py-2 rounded-xl text-sm font-bold hover:bg-error-container transition-colors">
              <span class="material-symbols-outlined text-sm">close</span> Reject
            </button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Outgoing Requests I've Made -->
  <div>
    <h2 class="text-lg font-extrabold text-on-surface mb-4">My Outgoing Requests</h2>
    <?php if (!empty($outgoing)): ?>
    <div class="bg-white rounded-2xl border border-outline-variant shadow-sm overflow-hidden">
      <table class="w-full text-left">
        <thead class="text-xs uppercase text-on-surface-variant bg-surface-container border-b border-outline-variant">
          <tr>
            <th class="p-4">Farmer</th>
            <th class="p-4">From → To</th>
            <th class="p-4">Date</th>
            <th class="p-4">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-outline-variant">
          <?php foreach ($outgoing as $r): ?>
          <tr>
            <td class="p-4 font-bold text-sm"><?php echo htmlspecialchars($r['farmer_name']); ?></td>
            <td class="p-4 text-sm text-on-surface-variant">
              <?php echo htmlspecialchars($r['from_ward_name']); ?> → <strong><?php echo htmlspecialchars($r['to_ward_name']); ?></strong>
            </td>
            <td class="p-4 text-sm text-on-surface-variant"><?php echo date('d M Y', strtotime($r['created_at'])); ?></td>
            <td class="p-4">
              <?php
              $statusColors = ['pending' => 'bg-yellow-50 text-yellow-700', 'approved' => 'bg-green-50 text-green-700', 'rejected' => 'bg-red-50 text-red-700'];
              $sc = $statusColors[$r['status']] ?? 'bg-gray-100 text-gray-600';
              ?>
              <span class="text-xs font-bold px-2.5 py-1 rounded-full <?php echo $sc; ?>"><?php echo ucfirst($r['status']); ?></span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-2xl border border-outline-variant p-10 text-center text-on-surface-variant">
      <span class="material-symbols-outlined text-4xl mb-2 block">transfer_within_a_station</span>
      <p class="font-bold">No relocation requests made yet.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ── New Relocation Modal ───────────────────────────────────────────── -->
<div id="newRelocationModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
    <div class="px-6 py-5 border-b border-outline-variant flex justify-between items-center">
      <h4 class="text-xl font-extrabold">Request Farmer Relocation</h4>
      <button onclick="document.getElementById('newRelocationModal').classList.add('hidden')">
        <span class="material-symbols-outlined text-outline">close</span>
      </button>
    </div>
    <form action="/officer/relocation/request" method="POST" class="p-6 space-y-4">
      <div>
        <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Select Farmer *</label>
        <select name="farmer_id" required class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
          <option value="">— Select Farmer —</option>
          <?php foreach ($myFarmers as $f): ?>
          <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['name'] . ' (' . $f['phone'] . ') — ' . $f['ward_name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Move To Ward *</label>
        <select name="to_ward_id" required class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium">
          <option value="">— Select Destination Ward —</option>
          <?php foreach ($allWards as $w): ?>
          <?php if (!in_array($w['id'], $myWardIds)): // Can't relocate to own ward ?>
          <option value="<?php echo $w['id']; ?>"><?php echo htmlspecialchars($w['district_name'] . ' → ' . $w['name']); ?></option>
          <?php endif; ?>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-bold text-on-surface-variant mb-1.5">Reason / Notes</label>
        <textarea name="notes" rows="3" placeholder="Reason for relocation request..."
                  class="w-full bg-surface-container rounded-xl px-4 py-2.5 border border-transparent focus:border-primary outline-none font-medium resize-none"></textarea>
      </div>
      <p class="text-xs text-on-surface-variant bg-surface-container rounded-xl p-3">
        <span class="material-symbols-outlined text-xs align-middle">info</span>
        If both wards are managed by the same officer, the relocation will be approved automatically.
      </p>
      <div class="flex gap-3 pt-2">
        <button type="button" onclick="document.getElementById('newRelocationModal').classList.add('hidden')"
                class="flex-1 border border-outline py-3 rounded-xl font-bold text-on-surface-variant">Cancel</button>
        <button type="submit" class="flex-1 bg-primary text-white py-3 rounded-xl font-bold">Submit Request</button>
      </div>
    </form>
  </div>
</div>
