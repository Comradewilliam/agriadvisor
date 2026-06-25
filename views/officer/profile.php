<?php
// Officer Profile page
$db = \App\Core\Database::getInstance()->getConnection();

// Assigned wards
$wardStmt = $db->prepare("SELECT w.name FROM wards w JOIN officer_wards ow ON ow.ward_id = w.id WHERE ow.officer_id = :id");
$wardStmt->execute(['id' => $officer['id']]);
$assignedWards = $wardStmt->fetchAll(\PDO::FETCH_COLUMN);

// Activity stats
$farmerCount  = (int)$db->prepare("SELECT COUNT(*) FROM farmers WHERE ward_id IN (SELECT ward_id FROM officer_wards WHERE officer_id = ?) ")->execute([$officer['id']]) ? 0 : 0;
$visitsMonth  = (int)$db->query("SELECT COUNT(*) FROM visits WHERE officer_id = {$officer['id']} AND scheduled_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
$resolvedEsc  = (int)$db->query("SELECT COUNT(*) FROM sms_escalations WHERE status = 'closed'")->fetchColumn();
$allFarmers   = (int)$db->query("SELECT COUNT(*) FROM farmers")->fetchColumn();
?>

<div class="p-6 md:p-8 max-w-5xl mx-auto">
  <div class="mb-6">
    <h2 class="text-3xl font-bold text-on-surface">Officer Profile</h2>
    <p class="text-on-surface-variant">Manage your account details, assigned wards, and preferences.</p>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Left Column -->
    <div class="lg:col-span-2 space-y-6">

      <!-- Profile Card -->
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant p-6">
        <div class="flex flex-col sm:flex-row sm:items-start gap-5">
          <div class="relative">
            <div class="w-24 h-24 rounded-2xl overflow-hidden bg-surface-container-high shadow-sm">
              <img src="<?php echo \App\Helpers\Avatar::url($officer['name'], '154212', 96); ?>"
                   alt="<?php echo htmlspecialchars($officer['name']); ?>" class="w-full h-full object-cover">
            </div>
            <button class="absolute -bottom-2 -right-2 w-8 h-8 bg-white border border-outline-variant rounded-xl flex items-center justify-center shadow-sm hover:bg-surface-container transition-colors">
              <span class="material-symbols-outlined text-base text-on-surface-variant">photo_camera</span>
            </button>
          </div>
          <div class="flex-1">
            <h3 class="text-2xl font-extrabold text-on-surface mb-0.5"><?php echo htmlspecialchars($officer['name']); ?></h3>
            <p class="text-on-surface-variant mb-3">Ward Agricultural Officer (WAO)</p>
            <div class="flex flex-wrap gap-2">
              <?php foreach ($assignedWards as $w): ?>
                <span class="inline-flex items-center gap-1 bg-surface-container text-on-surface-variant text-xs font-bold px-3 py-1 rounded-full border border-outline-variant">
                  <span class="material-symbols-outlined text-xs">location_on</span>
                  <?php echo htmlspecialchars($w); ?> Ward
                </span>
              <?php endforeach; ?>
              <?php if (empty($assignedWards)): ?>
                <span class="text-xs text-outline">No wards assigned</span>
              <?php endif; ?>
            </div>
          </div>
          <a href="#" class="shrink-0 flex items-center gap-2 bg-primary text-white font-bold px-5 py-2.5 rounded-xl hover:bg-primary-container transition-colors text-sm">
            <span class="material-symbols-outlined text-base">edit</span> Edit Profile
          </a>
        </div>
      </div>

      <!-- Contact Details -->
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant p-6">
        <h4 class="flex items-center gap-2 font-bold text-on-surface mb-5 text-lg">
          <span class="material-symbols-outlined text-primary">contact_page</span> Contact Details
        </h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <?php
          $fields = [
            ['label'=>'EMAIL ADDRESS',   'value'=>$officer['email'] ?? 'officer@kilimo.go.tz'],
            ['label'=>'PHONE NUMBER',    'value'=>$officer['phone'] ?? '+255 7XX XXX XXX'],
            ['label'=>'OFFICE LOCATION', 'value'=>'District Headquarters'],
            ['label'=>'EMPLOYEE ID',     'value'=>'BS-2024-' . str_pad($officer['id'], 4, '0', STR_PAD_LEFT)],
          ];
          foreach ($fields as $f): ?>
            <div>
              <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1"><?php echo $f['label']; ?></p>
              <p class="text-on-surface font-medium"><?php echo htmlspecialchars($f['value']); ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Account Security -->
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant p-6">
        <h4 class="flex items-center gap-2 font-bold text-on-surface mb-5 text-lg">
          <span class="material-symbols-outlined text-primary">security</span> Account Security
        </h4>
        <div class="space-y-4">
          <div class="flex items-center justify-between p-4 bg-surface-container-low rounded-xl">
            <div>
              <p class="font-medium text-on-surface">Password</p>
              <p class="text-xs text-on-surface-variant">Last changed 4 months ago</p>
            </div>
            <button class="border border-outline-variant text-on-surface font-bold text-sm px-4 py-2 rounded-xl hover:bg-surface-container transition-colors">Change Password</button>
          </div>
          <div class="flex items-center justify-between p-4 bg-surface-container-low rounded-xl">
            <div>
              <p class="font-medium text-on-surface">Two-Factor Authentication</p>
              <p class="text-xs text-on-surface-variant">Currently disabled. Recommended for enhanced security.</p>
            </div>
            <button class="border border-primary text-primary font-bold text-sm px-4 py-2 rounded-xl hover:bg-primary hover:text-white transition-colors">Enable 2FA</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column -->
    <div class="space-y-6">

      <!-- Notification Preferences -->
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant p-5">
        <h4 class="flex items-center gap-2 font-bold text-on-surface mb-4">
          <span class="material-symbols-outlined text-primary">notifications</span> Notification Preferences
        </h4>
        <div class="space-y-4">
          <?php
          $prefs = [
            ['label'=>'Weather Alerts (SMS)', 'on'=>true],
            ['label'=>'Farmer Visit Requests','on'=>true],
            ['label'=>'AI Chat Escalations',  'on'=>true],
            ['label'=>'System Updates',       'on'=>false],
          ];
          foreach ($prefs as $p): ?>
            <div class="flex items-center justify-between">
              <p class="text-sm text-on-surface"><?php echo $p['label']; ?></p>
              <button class="relative w-11 h-6 rounded-full transition-colors <?php echo $p['on'] ? 'bg-primary' : 'bg-surface-container-highest'; ?>">
                <span class="absolute top-0.5 <?php echo $p['on'] ? 'right-0.5' : 'left-0.5'; ?> w-5 h-5 bg-white rounded-full shadow-sm transition-all"></span>
              </button>
            </div>
          <?php endforeach; ?>
        </div>
        <button class="w-full mt-4 bg-surface-container-high text-on-surface font-bold py-2.5 rounded-xl hover:bg-surface-container-highest transition-colors text-sm">Save Preferences</button>
      </div>

      <!-- Activity Summary -->
      <div class="bg-primary rounded-2xl p-5 text-white">
        <p class="text-xs text-white/70 uppercase tracking-wider font-bold mb-3">ACTIVITY SUMMARY</p>
        <p class="text-5xl font-extrabold mb-0.5"><?php echo number_format($allFarmers); ?></p>
        <p class="text-white/80 text-sm mb-4">Farmers in System</p>
        <div class="w-full bg-white/20 rounded-full h-2 mb-4">
          <div class="bg-white h-2 rounded-full" style="width:<?php echo min(100, round($allFarmers/200*100)); ?>%"></div>
        </div>
        <div class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-white/70">Visits this month</span>
            <span class="font-bold"><?php echo $visitsMonth; ?></span>
          </div>
          <div class="flex justify-between">
            <span class="text-white/70">Resolved escalations</span>
            <span class="font-bold"><?php echo $resolvedEsc; ?></span>
          </div>
        </div>
      </div>

      <!-- Recent Sessions -->
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant p-5">
        <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-3">RECENT ACTIVE SESSIONS</p>
        <div class="space-y-3">
          <?php
          $sessions = [
            ['device'=>'Chrome / Windows', 'detail'=>'Dar es Salaam &bull; Active now', 'icon'=>'laptop_mac', 'active'=>true],
            ['device'=>'Firefox / Android', 'detail'=>'Dodoma &bull; 3 hours ago', 'icon'=>'phone_android', 'active'=>false],
          ];
          foreach ($sessions as $s): ?>
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-on-surface-variant"><?php echo $s['icon']; ?></span>
              </div>
              <div>
                <p class="font-medium text-on-surface text-sm"><?php echo $s['device']; ?></p>
                <p class="text-xs <?php echo $s['active'] ? 'text-primary font-bold' : 'text-outline'; ?>"><?php echo $s['detail']; ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
