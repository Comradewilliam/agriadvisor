<?php
// Regions & Districts Management
$db = \App\Core\Database::getInstance()->getConnection();
$totalRegions = 31;
$districtsManaged = 184;
$villagesMapping = "12,318";
$leadOfficers = 162;
$pendingAssignments = 22;
?>

<div class="p-6 md:p-8 max-w-6xl mx-auto">
  <div class="mb-8">
    <h2 class="text-3xl font-bold text-on-surface">Regions &amp; Districts</h2>
    <p class="text-on-surface-variant">Manage Tanzania's agricultural zoning. Define regions, add districts, and assign strategic leadership to drive productivity.</p>
  </div>

  <!-- KPI Row -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-8">
    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant p-5">
      <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">TOTAL REGIONS</p>
      <p class="text-3xl font-extrabold text-on-surface mb-1"><?php echo $totalRegions; ?></p>
      <p class="text-xs font-medium text-on-surface-variant flex items-center gap-1">
        <span class="material-symbols-outlined text-sm text-primary">trending_up</span> Active Coverage
      </p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant p-5 border-l-4 border-l-secondary">
      <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">DISTRICTS MANAGED</p>
      <p class="text-3xl font-extrabold text-on-surface mb-1"><?php echo $districtsManaged; ?></p>
      <p class="text-xs font-medium text-secondary">+3 added this month</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant p-5 border-l-4 border-l-tertiary">
      <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">VILLAGES MAPPING</p>
      <p class="text-3xl font-extrabold text-on-surface mb-1"><?php echo $villagesMapping; ?></p>
      <p class="text-xs font-medium text-tertiary">98.4% Geo-tagged</p>
    </div>
    <div class="bg-primary rounded-2xl shadow-sm p-5 text-white flex flex-col justify-between relative overflow-hidden">
      <span class="material-symbols-outlined text-[80px] absolute -right-4 -bottom-4 opacity-20">psychology</span>
      <div class="relative z-10">
        <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-1">LEAD OFFICERS</p>
        <p class="text-3xl font-extrabold mb-1"><?php echo $leadOfficers; ?></p>
        <p class="text-xs font-medium text-primary-fixed"><?php echo $pendingAssignments; ?> pending assignments</p>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

    <!-- Left: Regions Table -->
    <div class="xl:col-span-8 space-y-6">
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant overflow-hidden">
        
        <!-- Toolbar -->
        <div class="p-4 border-b border-outline-variant flex flex-col sm:flex-row items-center justify-between gap-4">
          <div class="flex gap-2 bg-surface-container-low p-1 rounded-xl border border-outline-variant w-full sm:w-auto">
            <button class="flex-1 sm:flex-none text-sm font-bold px-4 py-1.5 rounded-lg bg-white shadow-sm text-on-surface">Coastal</button>
            <button class="flex-1 sm:flex-none text-sm font-bold px-4 py-1.5 rounded-lg text-on-surface-variant hover:text-on-surface">Lake Zone</button>
          </div>
          <div class="flex items-center gap-3 w-full sm:w-auto">
            <span class="text-sm font-medium text-on-surface-variant">Filter by Status:</span>
            <select class="bg-white border border-outline-variant rounded-xl px-4 py-1.5 text-sm font-medium text-on-surface outline-none focus:ring-2 focus:ring-primary min-w-[120px]">
              <option>Active</option>
              <option>Pending</option>
            </select>
            <button class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-surface-container border border-outline-variant transition-colors shrink-0">
              <span class="material-symbols-outlined text-base">filter_list</span>
            </button>
          </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-surface-container text-xs text-on-surface-variant uppercase">
              <tr>
                <th class="p-4 text-left font-bold">Geography Name</th>
                <th class="p-4 text-left font-bold">Lead Officer</th>
                <th class="p-4 text-left font-bold">Wards / Villages</th>
                <th class="p-4 text-left font-bold">Status</th>
                <th class="p-4 text-center font-bold">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
              <!-- Kigoma Region (Expanded) -->
              <tr class="bg-surface-container-lowest">
                <td class="p-4 flex items-center gap-3">
                  <span class="material-symbols-outlined text-on-surface-variant text-sm cursor-pointer">expand_more</span>
                  <span class="material-symbols-outlined text-primary text-xl">domain</span>
                  <div>
                    <p class="font-extrabold text-on-surface text-base">Kigoma Region</p>
                    <p class="text-xs text-on-surface-variant">Western Zone &bull; 8 Districts</p>
                  </div>
                </td>
                <td class="p-4 text-on-surface-variant italic">— (Regional Level)</td>
                <td class="p-4">
                  <p class="font-bold text-on-surface">92 Wards</p>
                  <p class="text-xs text-on-surface-variant">642 Villages</p>
                </td>
                <td class="p-4"><span class="text-[10px] font-extrabold tracking-wider border border-outline-variant px-2 py-1 rounded-md text-on-surface uppercase">Operational</span></td>
                <td class="p-4 text-center"><button class="text-on-surface-variant hover:text-primary"><span class="material-symbols-outlined">more_vert</span></button></td>
              </tr>
              <!-- Kakonko District (Child) -->
              <tr class="bg-white">
                <td class="p-4 pl-12 flex items-center gap-2">
                  <span class="material-symbols-outlined text-outline text-sm">subdirectory_arrow_right</span>
                  <p class="font-medium text-on-surface">Kakonko District</p>
                </td>
                <td class="p-4">
                  <div class="flex items-center gap-2">
                    <img src="<?php echo \App\Helpers\Avatar::url('Amani Malila', '003c60', 24); ?>" class="w-6 h-6 rounded-full" alt="avatar">
                    <span class="font-bold text-on-surface text-sm">Amani Malila</span>
                  </div>
                </td>
                <td class="p-4 text-on-surface text-sm">11 Wards / 68 Villages</td>
                <td class="p-4"><span class="text-[10px] font-extrabold tracking-wider bg-tertiary-fixed text-tertiary px-2 py-1 rounded-md uppercase">In Progress</span></td>
                <td class="p-4 text-center"><a href="#" class="text-primary text-xs font-bold hover:underline">Edit Profile</a></td>
              </tr>
              <!-- Kibondo District (Child - Unassigned) -->
              <tr class="bg-white">
                <td class="p-4 pl-12 flex items-center gap-2">
                  <span class="material-symbols-outlined text-outline text-sm">subdirectory_arrow_right</span>
                  <p class="font-medium text-on-surface">Kibondo District</p>
                </td>
                <td class="p-4">
                  <div class="flex items-center gap-2 text-error">
                    <span class="material-symbols-outlined text-base">warning</span>
                    <span class="font-bold italic text-sm">Unassigned</span>
                  </div>
                </td>
                <td class="p-4 text-on-surface text-sm">13 Wards / 74 Villages</td>
                <td class="p-4"><span class="text-[10px] font-extrabold tracking-wider bg-error-container text-error px-2 py-1 rounded-md uppercase">Action Req.</span></td>
                <td class="p-4 text-center"><a href="#" class="text-on-surface text-xs font-bold hover:underline">Assign Lead</a></td>
              </tr>

              <!-- Dodoma Region (Collapsed) -->
              <tr class="bg-surface-container-lowest">
                <td class="p-4 flex items-center gap-3">
                  <span class="material-symbols-outlined text-on-surface-variant text-sm cursor-pointer">chevron_right</span>
                  <span class="material-symbols-outlined text-secondary text-xl">landscape</span>
                  <div>
                    <p class="font-extrabold text-on-surface text-base">Dodoma Region</p>
                    <p class="text-xs text-on-surface-variant">Central Zone &bull; 7 Districts</p>
                  </div>
                </td>
                <td class="p-4 text-on-surface-variant italic">—</td>
                <td class="p-4">
                  <p class="font-bold text-on-surface">190 Wards</p>
                  <p class="text-xs text-on-surface-variant">580 Villages</p>
                </td>
                <td class="p-4"><span class="text-[10px] font-extrabold tracking-wider border border-outline-variant px-2 py-1 rounded-md text-on-surface uppercase">Operational</span></td>
                <td class="p-4 text-center"><button class="text-on-surface-variant hover:text-primary"><span class="material-symbols-outlined">more_vert</span></button></td>
              </tr>
            </tbody>
          </table>
        </div>
        <!-- Pagination -->
        <div class="p-4 border-t border-outline-variant flex items-center justify-between text-sm">
          <p class="text-on-surface-variant">Showing 1-10 of 31 regions</p>
          <div class="flex gap-2">
            <button class="px-4 py-2 border border-outline-variant rounded-lg text-on-surface-variant hover:bg-surface-container font-medium transition-colors">Previous</button>
            <button class="px-4 py-2 border border-outline-variant rounded-lg text-on-surface font-medium hover:bg-surface-container transition-colors">Next</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Right: Map + Assignments -->
    <div class="xl:col-span-4 space-y-6">

      <!-- Map -->
      <div class="bg-surface-container-low rounded-2xl border border-outline-variant p-6 h-[400px] flex flex-col items-center justify-center relative overflow-hidden bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCI+PGNpcmNsZSBjeD0iMTAiIGN5PSIxMCIgcj0iMSIgZmlsbD0iI2QxZDVkYiIvPjwvc3ZnPg==')]">
        <div class="absolute inset-0 p-5 z-10 pointer-events-none">
          <h4 class="font-bold text-on-surface mb-1">Geographic Density Map</h4>
          <p class="text-xs text-on-surface-variant mb-4">Visual representation of ward distribution and field officer activity logs across Tanzanian districts.</p>
        </div>
        <span class="material-symbols-outlined text-outline text-6xl mb-2">map</span>
        <p class="text-on-surface-variant font-medium relative z-10">Map Visualization Placeholder</p>
        <div class="absolute bottom-5 left-5 z-10 flex items-center gap-4 text-xs font-bold text-on-surface">
          <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-primary inline-block"></span> High Density</span>
          <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-tertiary inline-block"></span> Emerging Zone</span>
        </div>
      </div>

      <!-- Recent Assignments -->
      <div class="bg-surface-container-low rounded-2xl border border-outline-variant overflow-hidden">
        <div class="p-5 border-b border-outline-variant">
          <h4 class="font-bold text-on-surface uppercase tracking-wider text-xs">Recent Assignments</h4>
        </div>
        <div class="p-5 space-y-5">
          <div class="flex items-start gap-4">
            <img src="<?php echo \App\Helpers\Avatar::url('Zuberi Bakari', '154212', 40); ?>" class="w-10 h-10 rounded-full shrink-0" alt="avatar">
            <div>
              <p class="font-bold text-on-surface text-sm">Zuberi Bakari</p>
              <p class="text-xs text-on-surface-variant mt-0.5">Promoted to Lead: Hai District</p>
              <p class="text-[10px] text-outline mt-1 font-bold"><span class="material-symbols-outlined text-[12px] align-middle">check</span> 2 hours ago</p>
            </div>
          </div>
          <div class="flex items-start gap-4">
            <img src="<?php echo \App\Helpers\Avatar::url('Lulu Hassan', '003c60', 40); ?>" class="w-10 h-10 rounded-full shrink-0" alt="avatar">
            <div>
              <p class="font-bold text-on-surface text-sm">Lulu Hassan</p>
              <p class="text-xs text-on-surface-variant mt-0.5">Transfer requested: Geita Region</p>
              <p class="text-[10px] text-outline mt-1 font-bold"><span class="material-symbols-outlined text-[12px] align-middle text-error">info</span> Yesterday</p>
            </div>
          </div>
        </div>
        <a href="#" class="block p-4 border-t border-outline-variant bg-surface-container-lowest hover:bg-surface-container transition-colors flex items-center gap-3">
          <div class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-on-surface-variant text-sm">history</span>
          </div>
          <div>
            <p class="font-bold text-on-surface text-sm">View History</p>
            <p class="text-[10px] text-on-surface-variant">Audit logs for geography changes</p>
          </div>
        </a>
      </div>

    </div>
  </div>
</div>
