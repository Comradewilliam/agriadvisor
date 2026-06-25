<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Agri-Advisory - Modern Rural Agri-Advisory</title>
  <?php require __DIR__ . '/partials/head_assets.php'; ?>
</head>
<body class="bg-surface text-on-surface antialiased selection:bg-primary-fixed selection:text-primary">

  <!-- Navigation -->
  <nav class="fixed w-full z-50 bg-surface/80 backdrop-blur-md border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <div class="w-10 h-10 bg-primary rounded-xl flex items-center justify-center text-white">
          <span class="material-symbols-outlined text-xl">eco</span>
        </div>
        <span class="text-2xl font-extrabold text-primary tracking-tight">Agri-Advisory</span>
      </div>
      <div class="hidden md:flex gap-8 font-medium text-on-surface-variant">
        <a href="#features" class="hover:text-primary transition-colors"><?= htmlspecialchars($cms['nav_features'] ?? 'Features') ?></a>
        <a href="#impact" class="hover:text-primary transition-colors"><?= htmlspecialchars($cms['nav_impact'] ?? 'Impact') ?></a>
        <a href="#cta" class="hover:text-primary transition-colors"><?= htmlspecialchars($cms['nav_pricing'] ?? 'Pricing') ?></a>
      </div>
      <div class="flex items-center gap-4">
        <a href="/farmer/login" class="font-bold text-primary hover:text-primary-container transition-colors hidden sm:block">Farmer Login</a>
        <a href="/login" class="bg-primary text-white font-bold px-6 py-2.5 rounded-full hover:bg-primary-container hover:shadow-lg transition-all transform hover:-translate-y-0.5">Staff Portal</a>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="pt-32 pb-20 px-6 overflow-hidden">
    <div class="max-w-7xl mx-auto">
      <div class="flex flex-col lg:flex-row items-center gap-12">
        <div class="flex-1 text-center lg:text-left z-10">
          <div class="inline-flex items-center gap-2 bg-primary-fixed text-primary px-4 py-2 rounded-full font-bold text-sm mb-6 border border-green-300">
            <span class="relative flex h-3 w-3">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
            </span>
            <?= htmlspecialchars($cms['hero_badge'] ?? 'Now Serving 2,000+ Farmers in Tanzania') ?>
          </div>
          <h1 class="text-5xl md:text-6xl lg:text-7xl font-black text-on-surface leading-[1.1] mb-6 tracking-tight">
            <?= htmlspecialchars($cms['hero_title'] ?? 'Bridging technology and the field.') ?>
          </h1>
          <p class="text-xl text-on-surface-variant mb-8 max-w-2xl mx-auto lg:mx-0 leading-relaxed">
            <?= htmlspecialchars($cms['hero_subtitle'] ?? 'AI-powered agricultural advisory system. Connecting local farmers with expert guidance, real-time weather alerts, and precision crop scheduling via SMS and Web.') ?>
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
            <a href="/farmer/register" class="bg-primary text-white text-lg font-bold px-8 py-4 rounded-full hover:bg-primary-container hover:shadow-xl transition-all transform hover:-translate-y-1 flex items-center justify-center gap-2">
              Start Free Trial <span class="material-symbols-outlined text-xl">arrow_forward</span>
            </a>
            <a href="#demo" class="bg-surface-container text-on-surface text-lg font-bold px-8 py-4 rounded-full hover:bg-gray-200 transition-colors flex items-center justify-center gap-2">
              <span class="material-symbols-outlined text-xl">play_circle</span> Watch Demo
            </a>
          </div>
        </div>
        
        <div class="flex-1 relative w-full max-w-lg mx-auto">
          <!-- Decorative blobs -->
          <div class="absolute top-0 -left-4 w-72 h-72 bg-primary/10 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
          <div class="absolute top-0 -right-4 w-72 h-72 bg-tertiary-fixed/50 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
          <div class="absolute -bottom-8 left-20 w-72 h-72 bg-yellow-200/40 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>
          
          <!-- Image -->
          <div class="relative bg-white p-4 rounded-[2rem] shadow-2xl transform rotate-2 hover:rotate-0 transition-transform duration-500 border border-gray-100">
          <?php
            $heroImg = '/assets/images/hero.png';
            if (!empty($cms['hero_image']) && $cms['hero_image'] !== '/assets/images/hero.png') {
                $heroImg = $cms['hero_image'];
            }
          ?>
            <img src="<?php echo htmlspecialchars($heroImg); ?>" alt="Farmer in the field" class="rounded-[1.5rem] w-full h-[500px] object-cover">
            
            <!-- Floating cards -->
            <div class="absolute -left-12 top-24 bg-white p-4 rounded-2xl shadow-xl flex items-center gap-3 animate-bounce" style="animation-duration: 3s;">
              <div class="w-12 h-12 bg-tertiary-fixed text-tertiary rounded-xl flex items-center justify-center">
                <span class="material-symbols-outlined">wb_sunny</span>
              </div>
              <div>
                <p class="text-xs font-bold text-gray-500 uppercase"><?= htmlspecialchars($cms['hero_card_weather_label'] ?? 'Weather Alert') ?></p>
                <p class="font-bold text-on-surface"><?= htmlspecialchars($cms['hero_card_weather_text'] ?? 'Light Rain Today') ?></p>
              </div>
            </div>
            
            <div class="absolute -right-8 bottom-32 bg-white p-4 rounded-2xl shadow-xl flex items-center gap-3 animate-bounce" style="animation-duration: 4s; animation-delay: 1s;">
              <div class="w-12 h-12 bg-primary-fixed text-primary rounded-xl flex items-center justify-center">
                <span class="material-symbols-outlined">forum</span>
              </div>
              <div>
                <p class="text-xs font-bold text-gray-500 uppercase"><?= htmlspecialchars($cms['hero_card_ai_label'] ?? 'AI Advisor') ?></p>
                <p class="font-bold text-on-surface"><?= htmlspecialchars($cms['hero_card_ai_text'] ?? 'Mbolea iwekwe sasa.') ?></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Features Section -->
  <section id="features" class="py-24 bg-surface-container/30 border-y border-gray-100">
    <div class="max-w-7xl mx-auto px-6">
      <div class="text-center max-w-2xl mx-auto mb-16">
        <h2 class="text-primary font-bold tracking-wider uppercase text-sm mb-3"><?= htmlspecialchars($cms['features_section_label'] ?? 'How It Works') ?></h2>
        <h3 class="text-4xl font-extrabold text-on-surface"><?= htmlspecialchars($cms['features_title'] ?? 'Everything a modern rural farmer needs to succeed.') ?></h3>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Feature 1 -->
        <div class="bg-white rounded-3xl p-8 shadow-sm border border-gray-100 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
          <div class="w-14 h-14 bg-primary-fixed text-primary rounded-2xl flex items-center justify-center mb-6">
            <span class="material-symbols-outlined text-2xl">science</span>
          </div>
          <h4 class="text-xl font-bold mb-3 text-on-surface"><?= htmlspecialchars($cms['feature1_title'] ?? 'AI Soil & Crop Analysis') ?></h4>
          <p class="text-on-surface-variant leading-relaxed">
            <?= htmlspecialchars($cms['feature1_desc'] ?? 'Get instant answers via SMS or Web about crop diseases, soil health, and fertilizer schedules powered by localized AI models trained on Tanzanian data.') ?>
          </p>
        </div>
        
        <!-- Feature 2 -->
        <div class="bg-white rounded-3xl p-8 shadow-sm border border-gray-100 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
          <div class="w-14 h-14 bg-tertiary-fixed text-tertiary rounded-2xl flex items-center justify-center mb-6">
            <span class="material-symbols-outlined text-2xl">thunderstorm</span>
          </div>
          <h4 class="text-xl font-bold mb-3 text-on-surface"><?= htmlspecialchars($cms['feature2_title'] ?? 'Precision Weather') ?></h4>
          <p class="text-on-surface-variant leading-relaxed">
            <?= htmlspecialchars($cms['feature2_desc'] ?? 'Ward-level weather forecasting and alerts. Know exactly when to plant, irrigate, or harvest based on micro-climate data delivered straight to your phone.') ?>
          </p>
        </div>
        
        <!-- Feature 3 -->
        <div class="bg-white rounded-3xl p-8 shadow-sm border border-gray-100 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
          <div class="w-14 h-14 bg-secondary-fixed text-secondary rounded-2xl flex items-center justify-center mb-6">
            <span class="material-symbols-outlined text-2xl">support_agent</span>
          </div>
          <h4 class="text-xl font-bold mb-3 text-on-surface"><?= htmlspecialchars($cms['feature3_title'] ?? 'Expert Officer Visits') ?></h4>
          <p class="text-on-surface-variant leading-relaxed">
            <?= htmlspecialchars($cms['feature3_desc'] ?? 'If AI can\'t answer your question, it\'s instantly escalated to your local Ward Agricultural Officer who can schedule an in-person farm visit.') ?>
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Impact Section -->
  <section id="impact" class="py-24 bg-primary text-white overflow-hidden">
    <div class="max-w-7xl mx-auto px-6">
      <div class="flex flex-col md:flex-row items-center gap-16">
        <div class="flex-1">
          <h2 class="text-4xl lg:text-5xl font-extrabold mb-6 leading-tight"><?= htmlspecialchars($cms['impact_title'] ?? 'Measurable Impact Across The Nation') ?></h2>
          <p class="text-primary-fixed mb-8 text-lg leading-relaxed">
            <?= htmlspecialchars($cms['impact_subtitle'] ?? 'Since deploying Agri-Advisory in pilot districts, we\'ve seen significant improvements in crop yields and reduction in pest-related losses.') ?>
          </p>
          
          <div class="grid grid-cols-2 gap-6">
            <div>
              <p class="text-5xl font-black mb-1"><?= htmlspecialchars($cms['impact_stat1_value'] ?? '42%') ?></p>
              <p class="text-sm font-medium text-primary-fixed uppercase tracking-wide"><?= htmlspecialchars($cms['impact_stat1_label'] ?? 'Increase in Yield') ?></p>
            </div>
            <div>
              <p class="text-5xl font-black mb-1"><?= htmlspecialchars($cms['impact_stat2_value'] ?? '12k+') ?></p>
              <p class="text-sm font-medium text-primary-fixed uppercase tracking-wide"><?= htmlspecialchars($cms['impact_stat2_label'] ?? 'Active Farmers') ?></p>
            </div>
            <div>
              <p class="text-5xl font-black mb-1"><?= htmlspecialchars($cms['impact_stat3_value'] ?? '150k') ?></p>
              <p class="text-sm font-medium text-primary-fixed uppercase tracking-wide"><?= htmlspecialchars($cms['impact_stat3_label'] ?? 'AI Queries Answered') ?></p>
            </div>
            <div>
              <p class="text-5xl font-black mb-1"><?= htmlspecialchars($cms['impact_stat4_value'] ?? '31') ?></p>
              <p class="text-sm font-medium text-primary-fixed uppercase tracking-wide"><?= htmlspecialchars($cms['impact_stat4_label'] ?? 'Regions Covered') ?></p>
            </div>
          </div>
        </div>
        
        <div class="flex-1 w-full relative">
          <div class="absolute inset-0 bg-white/10 rounded-3xl transform rotate-3 scale-105"></div>
          <div class="bg-white text-on-surface rounded-3xl shadow-2xl overflow-hidden relative z-10 border-4 border-white/20">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-gray-50">
              <h4 class="font-bold"><?= htmlspecialchars($cms['impact_harvest_title'] ?? 'Recent Harvest Data') ?></h4>
              <span class="bg-primary-fixed text-primary text-xs font-bold px-3 py-1 rounded-full"><?= htmlspecialchars($cms['impact_harvest_badge'] ?? 'Live Sync') ?></span>
            </div>
            <div class="p-6">
              <table class="w-full text-left text-sm">
                <thead>
                  <tr class="text-gray-400 uppercase text-xs">
                    <th class="pb-3">Region</th>
                    <th class="pb-3">Crop</th>
                    <th class="pb-3 text-right">Avg. Yield</th>
                  </tr>
                </thead>
                <tbody class="font-medium">
                  <?php for ($i = 1; $i <= 4; $i++):
                    $border = $i < 4 ? ' border-b border-gray-50' : '';
                  ?>
                  <tr>
                    <td class="py-3<?= $border ?>"><?= htmlspecialchars($cms["harvest_row{$i}_region"] ?? '') ?></td>
                    <td class="py-3<?= $border ?>"><?= htmlspecialchars($cms["harvest_row{$i}_crop"] ?? '') ?></td>
                    <td class="py-3<?= $border ?> text-right text-primary"><?= htmlspecialchars($cms["harvest_row{$i}_yield"] ?? '') ?></td>
                  </tr>
                  <?php endfor; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="py-24 px-6">
    <div class="max-w-4xl mx-auto bg-surface-container rounded-[3rem] p-12 md:p-20 text-center relative overflow-hidden">
      <div class="absolute top-0 right-0 w-64 h-64 bg-primary-fixed/50 rounded-full blur-3xl -mr-20 -mt-20"></div>
      <div class="absolute bottom-0 left-0 w-64 h-64 bg-tertiary-fixed/50 rounded-full blur-3xl -ml-20 -mb-20"></div>
      
      <div class="relative z-10">
        <h2 class="text-4xl md:text-5xl font-black text-on-surface mb-6"><?= htmlspecialchars($cms['cta_title'] ?? 'Ready to transform your farming?') ?></h2>
        <p class="text-xl text-on-surface-variant mb-10 max-w-2xl mx-auto"><?= htmlspecialchars($cms['cta_subtitle'] ?? 'Join thousands of farmers making data-driven decisions every day. No smartphone required — our core services work entirely via standard SMS.') ?></p>
        <a href="/farmer/register" class="inline-block bg-primary text-white text-lg font-bold px-10 py-5 rounded-full hover:bg-primary-container shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-1">
          <?= htmlspecialchars($cms['cta_button'] ?? 'Create Free Account') ?>
        </a>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-white border-t border-gray-200 py-12 px-6">
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-6">
      <div class="flex items-center gap-2">
        <span class="material-symbols-outlined text-primary text-2xl">eco</span>
        <span class="text-xl font-extrabold text-on-surface"><?= htmlspecialchars($cms['footer_brand'] ?? 'Agri-Advisory') ?></span>
      </div>
      <div class="text-sm font-medium text-gray-500">
        <?= htmlspecialchars($cms['footer_copyright'] ?? '© 2024 Agri-Advisory System. All rights reserved.') ?>
      </div>
      <div class="flex gap-4">
        <a href="#" class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center text-gray-500 hover:bg-primary hover:text-white transition-colors">
          <span class="material-symbols-outlined text-sm">share</span>
        </a>
      </div>
    </div>
  </footer>

</body>
</html>
