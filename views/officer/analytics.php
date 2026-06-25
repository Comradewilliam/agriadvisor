<?php
/**
 * views/officer/analytics.php  v1.4
 * – Real data from DB (no hardcoded arrays)
 * – CSV export removed; PDF only
 * – Translations applied
 */
$db = \App\Core\Database::getInstance()->getConnection();

// Scope
$officerId = (int)$_SESSION['officer_id'];
$role      = $_SESSION['role'] ?? '';

// Ward IDs for WAO scope
$wardIds = [];
if ($role === 'ward_officer') {
    $ws = $db->prepare('SELECT ward_id FROM officer_wards WHERE officer_id = ?');
    $ws->execute([$officerId]);
    $wardIds = array_column($ws->fetchAll(), 'ward_id');
}

// District ID for DAO scope
$districtId = null;
if ($role === 'dao') {
    $ds = $db->prepare('SELECT district_id FROM officer_districts WHERE officer_id = ? LIMIT 1');
    $ds->execute([$officerId]);
    $districtId = $ds->fetchColumn() ?: null;
}

// Build WHERE clause
$whereWard = '';
$whereDistrict = '';
if ($wardIds) {
    $in = implode(',', array_map('intval', $wardIds));
    $whereWard = " AND f.ward_id IN ({$in})";
}
if ($districtId) {
    $whereDistrict = " AND w.district_id = " . (int)$districtId;
}

// KPI 1: Total Farmers
$totalFarmers = (int)$db->query("SELECT COUNT(*) FROM farmers f LEFT JOIN wards w ON w.id=f.ward_id WHERE 1=1{$whereWard}{$whereDistrict}")->fetchColumn();

// KPI 2: AI Engagement Rate (farmers with at least 1 message)
$engaged = (int)$db->query("SELECT COUNT(DISTINCT m.farmer_id) FROM ai_messages m JOIN farmers f ON f.id=m.farmer_id LEFT JOIN wards w ON w.id=f.ward_id WHERE 1=1{$whereWard}{$whereDistrict}")->fetchColumn();
$aiEngagementRate = $totalFarmers > 0 ? round(($engaged / $totalFarmers) * 100, 1) : 0;

// KPI 3: Avg resolution time (hours)
$resRow = $db->query("SELECT AVG(TIMESTAMPDIFF(MINUTE, e.escalated_at, e.responded_at)) AS avg_min FROM escalations e WHERE e.status='responded' AND e.responded_at IS NOT NULL")->fetch();
$avgMinutes = (float)($resRow['avg_min'] ?? 0);
$avgResolution = $avgMinutes > 60 ? round($avgMinutes/60, 1).'h' : round($avgMinutes).'m';

// KPI 4: Weather alerts dispatched vs total
$totalAlerts      = (int)$db->query("SELECT COUNT(*) FROM weather_alerts")->fetchColumn();
$approvedAlerts   = (int)$db->query("SELECT COUNT(*) FROM weather_alerts WHERE approval_status='approved'")->fetchColumn();
$alertReachPct    = $totalAlerts > 0 ? round(($approvedAlerts/$totalAlerts)*100) : 0;

// Monthly registrations (last 6 months)
$regData = $db->query("
    SELECT DATE_FORMAT(f.registered_at, '%b') AS month, COUNT(*) AS cnt
    FROM farmers f
    LEFT JOIN wards w ON w.id = f.ward_id
    WHERE f.registered_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    {$whereWard}{$whereDistrict}
    GROUP BY DATE_FORMAT(f.registered_at, '%Y-%m'), DATE_FORMAT(f.registered_at, '%b')
    ORDER BY MIN(f.registered_at)
")->fetchAll();

// Channel stats
$smsCount = (int)$db->query("SELECT COUNT(*) FROM ai_messages WHERE channel='sms'")->fetchColumn();
$webCount = (int)$db->query("SELECT COUNT(*) FROM ai_messages WHERE channel='web'")->fetchColumn();
$totalCh  = $smsCount + $webCount ?: 1;
$smsPct   = round(($smsCount / $totalCh) * 100);
$webPct   = round(($webCount / $totalCh) * 100);

// Crop distribution by ward (real)
$cropWardData = $db->query("
    SELECT c.name_en AS crop, w.name AS ward, COUNT(*) AS cnt
    FROM farmer_crops fc
    JOIN crops c ON c.id = fc.crop_id
    JOIN farmers f ON f.id = fc.farmer_id
    JOIN wards w ON w.id = f.ward_id
    WHERE fc.type = 'primary'
    GROUP BY c.id, w.id
    ORDER BY cnt DESC
    LIMIT 5
")->fetchAll();
$maxCnt = max(array_column($cropWardData, 'cnt') ?: [1]);

// Recent weather alerts
$recentAlerts = $db->query("
    SELECT alert_type, DATE_FORMAT(created_at,'%b %d') AS date,
           approval_status,
           NULL AS delivery_pct
    FROM weather_alerts wa
    ORDER BY created_at DESC LIMIT 5
")->fetchAll();

// Wards for filter
$wardList = $db->query("SELECT id, name FROM wards ORDER BY name")->fetchAll();
?>
<div class="p-6 md:p-8 max-w-6xl mx-auto">
  <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
      <h2 class="text-3xl font-bold text-on-surface"><?php echo __('analytics_title'); ?></h2>
      <p class="text-on-surface-variant"><?php echo __('analytics_subtitle'); ?></p>
    </div>
    <div class="flex flex-wrap gap-3">
      <div class="flex gap-1 bg-surface-container-low border border-outline-variant rounded-xl p-1">
        <button class="text-sm font-bold px-4 py-1.5 rounded-lg bg-white shadow-sm text-on-surface"><?php echo __('period_30d'); ?></button>
        <button class="text-sm font-bold px-4 py-1.5 rounded-lg text-on-surface-variant hover:bg-white transition-colors"><?php echo __('period_3m'); ?></button>
        <button class="text-sm font-bold px-4 py-1.5 rounded-lg text-on-surface-variant hover:bg-white transition-colors"><?php echo __('period_1y'); ?></button>
      </div>
      <!-- PDF Export only (CSV removed) -->
      <a href="/officer/analytics/export?format=pdf" target="_blank"
         class="flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-xl text-sm font-bold hover:bg-primary-container transition-colors">
        <span class="material-symbols-outlined text-base">picture_as_pdf</span> <?php echo __('export_pdf'); ?>
      </a>
    </div>
  </div>

  <!-- KPI Row -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <?php $kpis = [
      ['label'=>__('kpi_registrations'), 'value'=>number_format($totalFarmers), 'icon'=>'group_add',  'color'=>'text-primary',   'trend'=>null],
      ['label'=>__('kpi_ai_engagement'), 'value'=>$aiEngagementRate.'%',        'icon'=>'forum',       'color'=>'text-secondary', 'trend'=>null],
      ['label'=>__('kpi_resolution'),    'value'=>$avgResolution,               'icon'=>'timer',       'color'=>'text-tertiary',  'trend'=>null],
      ['label'=>__('kpi_alert_reach'),   'value'=>$alertReachPct.'%',           'icon'=>'campaign',    'color'=>'text-error',     'trend'=>null],
    ];
    foreach ($kpis as $k): ?>
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant p-5">
        <div class="flex items-center justify-between mb-3">
          <div class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center">
            <span class="material-symbols-outlined text-base <?php echo $k['color']; ?>"><?php echo $k['icon']; ?></span>
          </div>
        </div>
        <p class="text-xs text-on-surface-variant uppercase tracking-wider mb-1"><?php echo $k['label']; ?></p>
        <p class="text-3xl font-extrabold <?php echo $k['color']; ?>"><?php echo $k['value']; ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Charts Row -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-outline-variant p-5">
      <h3 class="font-bold text-on-surface mb-4"><?php echo __('chart_engagement'); ?></h3>
      <canvas id="engagementChart" height="200"></canvas>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant p-5">
      <h3 class="font-bold text-on-surface mb-4"><?php echo __('chart_channel'); ?></h3>
      <div class="flex items-center justify-center mb-4">
        <canvas id="channelChart" width="200" height="200"></canvas>
      </div>
      <div class="space-y-1.5">
        <div class="flex items-center justify-between text-sm">
          <span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-primary inline-block"></span><?php echo __('sms_usage'); ?></span>
          <span class="font-bold"><?php echo $smsPct; ?>%</span>
        </div>
        <div class="flex items-center justify-between text-sm">
          <span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-tertiary inline-block"></span><?php echo __('web_usage'); ?></span>
          <span class="font-bold"><?php echo $webPct; ?>%</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Tables Row -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Crop distribution -->
    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant overflow-hidden">
      <div class="p-5 border-b border-outline-variant">
        <h3 class="font-bold text-on-surface"><?php echo __('crop_trends'); ?></h3>
      </div>
      <div class="p-4 space-y-3">
        <?php if (empty($cropWardData)): ?>
          <p class="text-outline text-sm text-center py-4"><?php echo __('no_data'); ?></p>
        <?php else: foreach ($cropWardData as $c):
            $pct = round($c['cnt'] / $maxCnt * 100); ?>
          <div>
            <div class="flex justify-between text-sm mb-1">
              <span class="font-medium text-on-surface"><?php echo htmlspecialchars($c['crop']); ?> – <?php echo htmlspecialchars($c['ward']); ?></span>
              <span class="font-bold text-on-surface"><?php echo number_format($c['cnt']); ?> <?php echo __('farmers_count') ?: 'Farmers'; ?></span>
            </div>
            <div class="w-full bg-surface-container rounded-full h-2">
              <div class="bg-primary h-2 rounded-full transition-all duration-700" style="width:<?php echo $pct; ?>%"></div>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>

    <!-- Recent alerts -->
    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant overflow-hidden">
      <div class="p-5 border-b border-outline-variant">
        <h3 class="font-bold text-on-surface"><?php echo __('alert_effectiveness'); ?></h3>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-surface-container text-xs text-on-surface-variant uppercase">
            <tr>
              <th class="p-3 text-left"><?php echo __('alert_type'); ?></th>
              <th class="p-3 text-left"><?php echo __('date'); ?></th>
              <th class="p-3 text-left"><?php echo __('status'); ?></th>
              <th class="p-3 text-left"><?php echo __('delivery'); ?></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-outline-variant">
            <?php if (empty($recentAlerts)): ?>
              <tr><td colspan="4" class="p-6 text-center text-outline"><?php echo __('no_data'); ?></td></tr>
            <?php else: foreach ($recentAlerts as $al):
              $aCls = match($al['approval_status'] ?? 'pending') {
                'approved' => 'bg-primary-fixed text-primary',
                'rejected' => 'bg-error-container text-error',
                default    => 'bg-secondary-fixed text-secondary',
              };
            ?>
              <tr class="hover:bg-surface-container-lowest transition-colors">
                <td class="p-3 font-medium text-on-surface"><?php echo htmlspecialchars(str_replace('_',' ',ucfirst($al['alert_type'] ?? ''))); ?></td>
                <td class="p-3 text-on-surface-variant"><?php echo $al['date']; ?></td>
                <td class="p-3"><span class="text-xs font-bold px-2 py-0.5 rounded-full <?php echo $aCls; ?>"><?php echo ucfirst($al['approval_status'] ?? 'pending'); ?></span></td>
                <td class="p-3">
                  <?php if ($al['delivery_pct'] !== null): ?>
                  <div class="flex items-center gap-2">
                    <div class="w-16 bg-surface-container rounded-full h-1.5">
                      <div class="bg-primary h-1.5 rounded-full" style="width:<?php echo $al['delivery_pct']; ?>%"></div>
                    </div>
                    <span class="text-xs font-bold text-primary"><?php echo $al['delivery_pct']; ?>%</span>
                  </div>
                  <?php else: ?>
                    <span class="text-xs text-outline">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script src="/assets/js/chart.umd.min.js"></script>
<script>
const months = <?php echo json_encode(array_column($regData,'month') ?: ['—']); ?>;
const counts  = <?php echo json_encode(array_map('intval', array_column($regData,'cnt')) ?: [0]); ?>;

new Chart(document.getElementById('engagementChart'), {
  type: 'bar',
  data: {
    labels: months,
    datasets: [
      { label: '<?php echo __('registered'); ?>', data: counts, backgroundColor: '#154212', borderRadius: 6 },
      { label: '<?php echo __('active'); ?>',     data: counts.map(v => Math.round(v * <?php echo $totalFarmers>0?round($aiEngagementRate/100,2):0.7;?>)), backgroundColor: '#003c60', borderRadius: 6 },
    ]
  },
  options: { responsive:true, plugins:{legend:{display:false}}, scales:{x:{grid:{display:false}},y:{grid:{color:'#f0eded'}}} }
});

new Chart(document.getElementById('channelChart'), {
  type: 'doughnut',
  data: {
    labels: ['SMS','Web'],
    datasets: [{ data: [<?php echo $smsPct; ?>, <?php echo $webPct; ?>], backgroundColor:['#154212','#003c60'], borderWidth:0, hoverOffset:4 }]
  },
  options: { responsive:false, cutout:'72%', plugins:{legend:{display:false}} }
});
</script>
