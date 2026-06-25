<?php
// Farmer weather page
$swahiliDays = ['Jumapili', 'Jumatatu', 'Jumanne', 'Jumatano', 'Alhamisi', 'Ijumaa', 'Jumamosi'];
$englishDayNames = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];

// Process forecast data
$forecastDays = [];
if (!empty($weather['forecast']['list'])) {
    $byDate = [];
    foreach ($weather['forecast']['list'] as $item) {
        $date = date('Y-m-d', $item['dt']);
        if (!isset($byDate[$date])) {
            $byDate[$date] = [
                'high' => $item['main']['temp_max'],
                'low' => $item['main']['temp_min'],
                'icon' => $item['weather'][0]['icon'],
                'rain' => isset($item['rain']['3h']) ? round($item['rain']['3h'] * 10) : 0,
                'dt' => $item['dt']
            ];
        } else {
            $byDate[$date]['high'] = max($byDate[$date]['high'], $item['main']['temp_max']);
            $byDate[$date]['low'] = min($byDate[$date]['low'], $item['main']['temp_min']);
        }
    }
    $forecastDays = array_slice($byDate, 0, 7);
}

// Get weather icon mapping from OWM codes to Material Icons
function getWeatherIcon($owmIcon) {
    $map = [
        '01d' => 'wb_sunny', '01n' => 'nightlight',
        '02d' => 'partly_cloudy_day', '02n' => 'partly_cloudy_night',
        '03d' => 'wb_cloudy', '03n' => 'wb_cloudy',
        '04d' => 'cloud', '04n' => 'cloud',
        '09d' => 'rainy', '09n' => 'rainy',
        '10d' => 'rainy', '10n' => 'rainy',
        '11d' => 'thunderstorm', '11n' => 'thunderstorm',
        '13d' => 'ac_unit', '13n' => 'ac_unit',
        '50d' => 'foggy', '50n' => 'foggy',
    ];
    return $map[$owmIcon] ?? 'wb_cloudy';
}
?>

<div class="p-6 md:p-8 max-w-5xl mx-auto">

  <div class="mb-6">
    <h2 class="text-3xl font-extrabold text-on-surface">Hali ya Hewa</h2>
    <p class="text-on-surface-variant mt-1">Kata yako: <?php echo htmlspecialchars($weather['village']['ward_name'] ?? 'Kata Yako'); ?></p>
    <?php if (($weather['source'] ?? '') === 'fallback'): ?>
    <div class="mt-3 bg-amber-50 text-amber-900 border border-amber-200 rounded-xl px-4 py-3 text-sm flex items-center gap-2">
      <span class="material-symbols-outlined">info</span>
      Taarifa za hali ya hewa zinatoka makadirio ya msimu (API haipatikani kwa sasa).
    </div>
    <?php endif; ?>
  </div>

  <!-- Current Weather -->
  <?php if (!empty($weather['current'])): ?>
  <div class="bg-white rounded-2xl shadow-sm border border-outline-variant overflow-hidden mb-6">
    <div class="p-6 md:p-8 flex flex-col md:flex-row items-center gap-8">
      <div class="text-center md:text-left">
        <p class="text-6xl font-black text-on-surface"><?php echo round($weather['current']['main']['temp']); ?>°C</p>
        <p class="text-xl font-bold text-on-surface-variant mt-2"><?php echo htmlspecialchars($weather['current']['weather'][0]['description'] ?? 'Hali ya hewa'); ?></p>
        <p class="text-sm text-outline mt-1">
          Hii: <?php echo round($weather['current']['main']['temp_min']); ?>°C · Juu: <?php echo round($weather['current']['main']['temp_max']); ?>°C
        </p>
      </div>
      <div class="flex-1 grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-surface-container p-4 rounded-xl text-center">
          <span class="material-symbols-outlined text-3xl text-tertiary">water_drop</span>
          <p class="text-xs font-bold text-on-surface-variant uppercase mt-1">Unyevu</p>
          <p class="text-xl font-extrabold text-on-surface"><?php echo $weather['current']['main']['humidity']; ?>%</p>
        </div>
        <div class="bg-surface-container p-4 rounded-xl text-center">
          <span class="material-symbols-outlined text-3xl text-tertiary">air</span>
          <p class="text-xs font-bold text-on-surface-variant uppercase mt-1">Uwezo wa Mvua</p>
          <p class="text-xl font-extrabold text-on-surface"><?php echo round($weather['current']['wind']['speed']); ?> m/s</p>
        </div>
        <div class="bg-surface-container p-4 rounded-xl text-center">
          <span class="material-symbols-outlined text-3xl text-tertiary">visibility</span>
          <p class="text-xs font-bold text-on-surface-variant uppercase mt-1">Uwezo wa Kuzungumzia</p>
          <p class="text-xl font-extrabold text-on-surface"><?php echo round(($weather['current']['visibility'] ?? 10000) / 1000, 1); ?> km</p>
        </div>
        <div class="bg-surface-container p-4 rounded-xl text-center">
          <span class="material-symbols-outlined text-3xl text-tertiary">compress</span>
          <p class="text-xs font-bold text-on-surface-variant uppercase mt-1">Shinikizo la Hewa</p>
          <p class="text-xl font-extrabold text-on-surface"><?php echo $weather['current']['main']['pressure']; ?> hPa</p>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- 7-Day Forecast -->
  <div class="bg-white rounded-2xl shadow-sm border border-outline-variant overflow-hidden mb-6">
    <div class="p-4 border-b border-outline-variant">
      <h3 class="font-bold text-on-surface">Utabiri wa Siku 7</h3>
    </div>
    <div class="grid grid-cols-7 divide-x divide-outline-variant">
      <?php $i = 0; foreach ($forecastDays as $date => $day): ?>
        <?php 
          $dayOfWeek = date('w', strtotime($date));
          $isToday = ($i === 0);
        ?>
        <div class="p-3 text-center <?php echo $isToday ? 'bg-tertiary text-white' : 'hover:bg-surface-container-low'; ?> transition-colors">
          <p class="text-xs font-bold uppercase tracking-wider <?php echo $isToday ? 'text-white/80' : 'text-on-surface-variant'; ?>"><?php echo $englishDayNames[$dayOfWeek]; ?></p>
          <?php if ($isToday): ?>
            <p class="text-white font-bold text-sm mt-1"><?php echo date('M j', strtotime($date)); ?></p>
          <?php endif; ?>
          <span class="material-symbols-outlined text-3xl my-2 block <?php echo $isToday ? 'text-white' : 'text-tertiary'; ?>"><?php echo getWeatherIcon($day['icon']); ?></span>
          <p class="font-extrabold <?php echo $isToday ? 'text-white text-2xl' : 'text-on-surface'; ?>"><?php echo round($day['high']); ?>°</p>
          <p class="text-xs <?php echo $isToday ? 'text-white/60' : 'text-outline'; ?>"><?php echo round($day['low']); ?>°</p>
          <?php if ($day['rain'] > 0): ?>
            <p class="text-xs <?php echo $isToday ? 'bg-white/20' : 'bg-tertiary-fixed text-tertiary'; ?> rounded-full px-2 py-0.5 mt-1 font-bold">
              <?php echo $day['rain']; ?>% mvua
            </p>
          <?php endif; ?>
        </div>
      <?php $i++; endforeach; ?>
    </div>
  </div>

  <!-- Active Alerts -->
  <div class="bg-white rounded-2xl shadow-sm border border-outline-variant overflow-hidden mb-6">
    <div class="p-5 border-b border-outline-variant flex items-center justify-between">
      <h3 class="font-bold text-on-surface">Tahadhari Zinazofanya Kazi</h3>
      <?php
        $db = \App\Core\Database::getInstance()->getConnection();
        $alerts = $db->query("SELECT wa.* FROM weather_alerts wa WHERE wa.active = 1 AND wa.expires_at > NOW() ORDER BY wa.created_at DESC")->fetchAll();
      ?>
      <?php if (count($alerts) > 0): ?>
        <span class="bg-error text-white text-xs font-bold px-2 py-1 rounded-full"><?php echo count($alerts); ?> mpya</span>
      <?php endif; ?>
    </div>
    <div class="p-5 space-y-4">
      <?php if (count($alerts) > 0): ?>
        <?php foreach ($alerts as $alert): ?>
          <div class="flex items-start gap-4 p-4 bg-<?php echo $alert['severity'] === 'high' ? 'error' : ($alert['severity'] === 'medium' ? 'warning' : 'info'); ?>-container rounded-xl border border-<?php echo $alert['severity'] === 'high' ? 'error' : ($alert['severity'] === 'medium' ? 'warning' : 'info'); ?>/20">
            <span class="material-symbols-outlined text-<?php echo $alert['severity'] === 'high' ? 'error' : ($alert['severity'] === 'medium' ? 'warning' : 'info'); ?> text-2xl shrink-0">
              <?php echo $alert['alert_type'] === 'rain' ? 'thunderstorm' : ($alert['alert_type'] === 'drought' ? 'sunny' : 'warning'); ?>
            </span>
            <div>
              <p class="font-bold text-on-surface"><?php echo htmlspecialchars($alert['title']); ?></p>
              <p class="text-sm text-on-surface-variant mt-1"><?php echo htmlspecialchars($alert['message']); ?></p>
              <p class="text-xs text-outline mt-1">Imetolewa na Mfumo · <?php echo date('M j', strtotime($alert['created_at'])); ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="text-center py-8 text-on-surface-variant">
          <span class="material-symbols-outlined text-4xl mb-2">check_circle</span>
          <p class="font-bold">Hakuna tahadhari za hali ya hewa kwa sasa</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Agricultural Tips Based on Weather -->
  <div class="bg-primary text-white rounded-2xl p-6">
    <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
      <span class="material-symbols-outlined">tips_and_updates</span>
      Vidokezo vya Kilimo vya Wiki Hii
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <?php
      $temp = $weather['current']['main']['temp'] ?? 25;
      $humidity = $weather['current']['main']['humidity'] ?? 50;
      
      $tips = [];
      if ($humidity > 70) {
          $tips[] = ['icon' => 'bug_report', 'title' => 'Wadudu na Magonjwa', 'text' => 'Unyevu wa juu unaweza kusababisha magonjwa ya ukungu. Angalia majani kila siku.'];
      }
      if ($temp > 30) {
          $tips[] = ['icon' => 'water_drop', 'title' => 'Umwagiliaji', 'text' => 'Mwagilia asubuhi mapema au jioni ili kupunguza upotevu wa maji.'];
      }
      if ($temp < 20) {
          $tips[] = ['icon' => 'eco', 'title' => 'Uteuzi wa Mazao', 'text' => 'Joto la chini laweza kupunguza ukuaji wa mimea. Angalia kwamba mbegu zimeweza kupanda vizuri.'];
      }
      
      if (empty($tips)) {
          $tips = [
              ['icon' => 'water_drop', 'title' => 'Umwagiliaji', 'text' => 'Mwagilia asubuhi mapema au jioni ili kupunguza upotevu wa maji.'],
              ['icon' => 'eco', 'title' => 'Mbolea', 'text' => 'Tumia mbolea ya kuchambua (compost) ili kuboresha uwezo wa udongo kuhifadhi maji.'],
              ['icon' => 'agriculture', 'title' => 'Uchambua', 'text' => 'Piga mbavu ya shamba ili kupunguza upatikanaji wa joto kwenye udongo.'],
          ];
      }
      foreach ($tips as $t): ?>
        <div class="bg-white/10 rounded-xl p-4">
          <span class="material-symbols-outlined text-white/80 text-2xl mb-2 block"><?php echo $t['icon']; ?></span>
          <p class="font-bold text-sm mb-1"><?php echo $t['title']; ?></p>
          <p class="text-white/80 text-xs leading-relaxed"><?php echo $t['text']; ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>