<?php
$escByStatus = [];
foreach ($escalationStats as $row) {
    $escByStatus[$row['status']] = (int)$row['cnt'];
}
$channelMap = ['web' => 0, 'sms' => 0, 'ussd' => 0];
foreach ($channelTotals as $row) {
    $channelMap[$row['channel']] = (int)$row['cnt'];
}
?>

<div class="p-6 md:p-8 max-w-[1400px] mx-auto">
  <div class="mb-8">
    <h1 class="font-headline-md text-headline-md text-primary font-bold">System Performance Hub</h1>
    <p class="text-on-surface-variant">Live analytics from the Agri-Advisory database.</p>
  </div>

  <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-8">
    <?php
    $kpis = [
      ['label' => 'Total Farmers', 'value' => number_format($totalFarmers), 'trend' => '+' . number_format($farmersThisMonth) . ' this month', 'color' => 'text-primary', 'icon' => 'group'],
      ['label' => 'Active Officers', 'value' => number_format($totalOfficers), 'trend' => number_format($totalKb) . ' KB articles', 'color' => 'text-secondary', 'icon' => 'badge'],
      ['label' => 'AI High Confidence', 'value' => $aiAccuracy . '%', 'trend' => number_format($totalMsgs) . ' total messages', 'color' => 'text-tertiary', 'icon' => 'psychology'],
      ['label' => 'Pending Escalations', 'value' => number_format($pendingEsc), 'trend' => $activeWeatherAlerts . ' weather alerts', 'color' => 'text-error', 'icon' => 'warning'],
    ];
    foreach ($kpis as $k): ?>
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant p-6">
        <div class="flex items-center gap-4 mb-3">
          <div class="w-12 h-12 rounded-2xl bg-surface-container flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-xl <?php echo $k['color']; ?>"><?php echo $k['icon']; ?></span>
          </div>
          <div>
            <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider"><?php echo $k['label']; ?></p>
            <p class="text-3xl font-extrabold <?php echo $k['color']; ?> mt-0.5"><?php echo $k['value']; ?></p>
          </div>
        </div>
        <p class="text-xs text-outline font-medium"><?php echo $k['trend']; ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 mb-8">
    <div class="xl:col-span-8 space-y-6">

      <!-- Message activity chart (simple bars) -->
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant p-6">
        <h3 class="font-bold text-on-surface mb-4">Farmer Messages (30 days)</h3>
        <?php
        $byDay = [];
        foreach ($msgStats as $row) {
            $byDay[$row['day']][$row['channel']] = (int)$row['cnt'];
        }
        $maxDay = 1;
        foreach ($byDay as $channels) {
            $maxDay = max($maxDay, array_sum($channels));
        }
        ?>
        <div class="flex items-end gap-1 h-32 overflow-x-auto">
          <?php foreach ($byDay as $day => $channels): ?>
            <?php $total = array_sum($channels); $h = max(4, round(($total / $maxDay) * 100)); ?>
            <div class="flex flex-col items-center min-w-[24px]" title="<?php echo $day; ?>: <?php echo $total; ?>">
              <div class="w-4 bg-primary rounded-t" style="height:<?php echo $h; ?>px"></div>
              <span class="text-[9px] text-outline mt-1 rotate-45 origin-left"><?php echo substr($day, 5); ?></span>
            </div>
          <?php endforeach; ?>
          <?php if (empty($byDay)): ?>
            <p class="text-on-surface-variant text-sm">No message activity in the last 30 days.</p>
          <?php endif; ?>
        </div>
        <div class="flex gap-4 mt-4 text-xs flex-wrap">
          <span class="flex items-center gap-1"><span class="w-3 h-3 bg-primary rounded"></span> Web: <?php echo number_format($channelMap['web']); ?></span>
          <span class="flex items-center gap-1"><span class="w-3 h-3 bg-secondary rounded"></span> SMS: <?php echo number_format($channelMap['sms']); ?></span>
          <span class="flex items-center gap-1"><span class="w-3 h-3 bg-tertiary rounded"></span> USSD: <?php echo number_format($channelMap['ussd']); ?></span>
          <a href="/admin/channel_analytics" class="text-primary font-bold hover:underline ml-auto">Channel analytics →</a>
        </div>
      </div>

      <!-- District Performance -->
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant overflow-hidden">
        <div class="p-5 border-b border-outline-variant flex items-center justify-between">
          <h3 class="font-bold text-on-surface">District Performance</h3>
          <a href="/admin/districts" class="text-primary text-sm font-bold hover:underline">Manage Districts</a>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-surface-container text-xs text-on-surface-variant uppercase">
              <tr>
                <th class="p-4 text-left">District / Region</th>
                <th class="p-4 text-left">Officers</th>
                <th class="p-4 text-left">Farmers</th>
                <th class="p-4 text-left">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
              <?php foreach ($districtStats as $d):
                $ratio = $d['farmer_count'] > 0 && $d['officer_count'] > 0 ? $d['farmer_count'] / $d['officer_count'] : 999;
                $status = $ratio <= 300 ? 'Optimal' : ($ratio <= 600 ? 'Moderate' : 'Needs Support');
                $cls = $ratio <= 300 ? 'bg-primary-fixed text-primary' : ($ratio <= 600 ? 'bg-secondary-fixed text-secondary' : 'bg-error-container text-error');
              ?>
                <tr class="hover:bg-surface-container-lowest">
                  <td class="p-4">
                    <p class="font-bold text-on-surface"><?php echo htmlspecialchars($d['district_name']); ?></p>
                    <p class="text-xs text-on-surface-variant"><?php echo htmlspecialchars($d['region']); ?></p>
                  </td>
                  <td class="p-4"><?php echo (int)$d['officer_count']; ?></td>
                  <td class="p-4 font-medium"><?php echo number_format((int)$d['farmer_count']); ?></td>
                  <td class="p-4"><span class="text-xs font-bold px-2 py-1 rounded-full <?php echo $cls; ?>"><?php echo $status; ?></span></td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($districtStats)): ?>
                <tr><td colspan="4" class="p-6 text-center text-outline">No district data yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Top wards -->
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant overflow-hidden">
        <div class="p-5 border-b border-outline-variant"><h3 class="font-bold text-on-surface">Top Wards by Farmer Count</h3></div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-surface-container text-xs uppercase text-on-surface-variant">
              <tr><th class="p-4 text-left">Ward</th><th class="p-4 text-left">District</th><th class="p-4 text-left">Farmers</th><th class="p-4 text-left">Officers</th></tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
              <?php foreach ($wardStats as $w): ?>
                <tr>
                  <td class="p-4 font-bold"><?php echo htmlspecialchars($w['ward_name']); ?></td>
                  <td class="p-4"><?php echo htmlspecialchars($w['district_name']); ?></td>
                  <td class="p-4"><?php echo number_format((int)$w['farmer_count']); ?></td>
                  <td class="p-4"><?php echo (int)$w['officer_count']; ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="xl:col-span-4 space-y-6">
      <div class="bg-on-surface text-inverse-on-surface rounded-2xl shadow-sm p-6">
        <h3 class="font-bold mb-5 flex items-center gap-2 text-lg">
          <span class="material-symbols-outlined text-primary-fixed">analytics</span> Escalation Queue
        </h3>
        <div class="space-y-4">
          <div class="flex justify-between"><span class="text-white/70">Pending</span><span class="font-bold"><?php echo number_format($escByStatus['pending'] ?? 0); ?></span></div>
          <div class="flex justify-between"><span class="text-white/70">Responded</span><span class="font-bold"><?php echo number_format($escByStatus['responded'] ?? 0); ?></span></div>
          <div class="flex justify-between"><span class="text-white/70">Total KB Articles</span><span class="font-bold"><?php echo number_format($totalKb); ?></span></div>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant p-6">
        <h3 class="font-bold text-on-surface mb-4">Active Weather Advisories</h3>
        <div class="space-y-4">
          <?php if (empty($weatherAdvisories)): ?>
            <p class="text-sm text-on-surface-variant">No active weather alerts.</p>
          <?php else: ?>
            <?php foreach ($weatherAdvisories as $alert): ?>
              <div class="border-l-4 border-error pl-3">
                <p class="font-bold text-sm"><?php echo htmlspecialchars($alert['title']); ?></p>
                <p class="text-xs text-outline"><?php echo htmlspecialchars($alert['alert_type']); ?> · <?php echo date('M j', strtotime($alert['created_at'])); ?></p>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant overflow-hidden">
        <div class="p-5 border-b border-outline-variant"><h3 class="font-bold text-on-surface">Recent Escalations</h3></div>
        <div class="p-4 space-y-3">
          <?php foreach ($recentEscalations as $e): ?>
            <div class="text-sm border-b border-outline-variant pb-2">
              <p class="font-medium text-on-surface truncate"><?php echo htmlspecialchars(mb_substr($e['content'], 0, 80)); ?></p>
              <p class="text-xs text-outline"><?php echo htmlspecialchars($e['status']); ?> · <?php echo date('M j H:i', strtotime($e['escalated_at'])); ?></p>
            </div>
          <?php endforeach; ?>
          <?php if (empty($recentEscalations)): ?>
            <p class="text-sm text-outline">No escalations yet.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
