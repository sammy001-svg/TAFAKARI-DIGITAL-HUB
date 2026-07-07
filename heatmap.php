<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// ── Country data (master list — overridable via Admin › Heatmap Config) ───
$_defaultCountriesData = [
    ['name'=>'Kenya',                    'code'=>'KE','flag'=>'🇰🇪','lat'=>0.023,  'lng'=>37.906],
    ['name'=>'Ethiopia',                 'code'=>'ET','flag'=>'🇪🇹','lat'=>9.145,  'lng'=>40.489],
    ['name'=>'DR Congo',                 'code'=>'CD','flag'=>'🇨🇩','lat'=>-4.038, 'lng'=>21.759],
    ['name'=>'South Sudan',              'code'=>'SS','flag'=>'🇸🇸','lat'=>6.877,  'lng'=>31.307],
    ['name'=>'Sudan',                    'code'=>'SD','flag'=>'🇸🇩','lat'=>12.862, 'lng'=>30.218],
    ['name'=>'Uganda',                   'code'=>'UG','flag'=>'🇺🇬','lat'=>1.373,  'lng'=>32.290],
    ['name'=>'Tanzania',                 'code'=>'TZ','flag'=>'🇹🇿','lat'=>-6.369, 'lng'=>34.889],
    ['name'=>'Rwanda',                   'code'=>'RW','flag'=>'🇷🇼','lat'=>-1.940, 'lng'=>29.874],
    ['name'=>'Burundi',                  'code'=>'BI','flag'=>'🇧🇮','lat'=>-3.373, 'lng'=>29.918],
    ['name'=>'Somalia',                  'code'=>'SO','flag'=>'🇸🇴','lat'=>5.152,  'lng'=>46.200],
    ['name'=>'Mozambique',               'code'=>'MZ','flag'=>'🇲🇿','lat'=>-18.665,'lng'=>35.530],
    ['name'=>'Burkina Faso',             'code'=>'BF','flag'=>'🇧🇫','lat'=>12.364, 'lng'=>-1.534],
    ['name'=>'Mali',                     'code'=>'ML','flag'=>'🇲🇱','lat'=>17.570, 'lng'=>-3.996],
    ['name'=>'Niger',                    'code'=>'NE','flag'=>'🇳🇪','lat'=>17.608, 'lng'=>8.082],
    ['name'=>'Nigeria',                  'code'=>'NG','flag'=>'🇳🇬','lat'=>9.082,  'lng'=>8.675],
    ['name'=>'Chad',                     'code'=>'TD','flag'=>'🇹🇩','lat'=>15.454, 'lng'=>18.732],
    ['name'=>'Central African Republic', 'code'=>'CF','flag'=>'🇨🇫','lat'=>6.611,  'lng'=>20.939],
    ['name'=>'Cameroon',                 'code'=>'CM','flag'=>'🇨🇲','lat'=>3.848,  'lng'=>11.502],
    ['name'=>'South Africa',             'code'=>'ZA','flag'=>'🇿🇦','lat'=>-30.559,'lng'=>22.938],
    ['name'=>'Ghana',                    'code'=>'GH','flag'=>'🇬🇭','lat'=>7.946,  'lng'=>-1.024],
    ['name'=>'Senegal',                  'code'=>'SN','flag'=>'🇸🇳','lat'=>14.497, 'lng'=>-14.452],
    ['name'=>'Egypt',                    'code'=>'EG','flag'=>'🇪🇬','lat'=>26.820, 'lng'=>30.802],
    ['name'=>'Morocco',                  'code'=>'MA','flag'=>'🇲🇦','lat'=>31.792, 'lng'=>-7.092],
    ['name'=>'Algeria',                  'code'=>'DZ','flag'=>'🇩🇿','lat'=>28.034, 'lng'=>1.659],
    ['name'=>'Angola',                   'code'=>'AO','flag'=>'🇦🇴','lat'=>-11.203,'lng'=>17.874],
    ['name'=>'Zambia',                   'code'=>'ZM','flag'=>'🇿🇲','lat'=>-13.134,'lng'=>27.849],
    ['name'=>'Zimbabwe',                 'code'=>'ZW','flag'=>'🇿🇼','lat'=>-19.015,'lng'=>29.154],
    ['name'=>'Malawi',                   'code'=>'MW','flag'=>'🇲🇼','lat'=>-13.254,'lng'=>34.302],
    ['name'=>'Madagascar',               'code'=>'MG','flag'=>'🇲🇬','lat'=>-18.767,'lng'=>46.869],
    ['name'=>'Liberia',                  'code'=>'LR','flag'=>'🇱🇷','lat'=>6.428,  'lng'=>-10.785],
    ['name'=>'Sierra Leone',             'code'=>'SL','flag'=>'🇸🇱','lat'=>8.460,  'lng'=>-11.780],
    ['name'=>'Guinea',                   'code'=>'GN','flag'=>'🇬🇳','lat'=>11.805, 'lng'=>-11.805],
];
$_dbCountries = json_decode(get_setting('heatmap_countries', ''), true);
$_countriesData = (is_array($_dbCountries) && count($_dbCountries) > 0) ? $_dbCountries : $_defaultCountriesData;

// Derive lookup tables from master list
$countryCoords = [];
$ctryList      = ['ALL' => ['All Countries', '🌍']];
$flagMap       = [];
$cnameMap      = [];
foreach ($_countriesData as $_c) {
    $countryCoords[$_c['name']] = [(float)$_c['lat'], (float)$_c['lng'], $_c['code']];
    $ctryList[$_c['code']]      = [$_c['name'], $_c['flag'] ?? '🏳'];
    $flagMap[$_c['code']]       = $_c['flag'] ?? '🏳';
    $cnameMap[$_c['code']]      = $_c['name'];
}
// Handle common alternate spellings
if (isset($countryCoords['DR Congo'])) {
    $countryCoords['Democratic Republic of Congo'] = $countryCoords['DR Congo'];
}

// ── Region/city coordinates ────────────────────────────────────────────
$regionCoords = [
    // Kenya
    'Nairobi'       => [ -1.286,  36.820], 'Mombasa'   => [ -4.043,  39.668],
    'Kisumu'        => [ -0.102,  34.761], 'Nakuru'    => [ -0.303,  36.080],
    'Eldoret'       => [  0.517,  35.270], 'Turkana'   => [  3.119,  35.597],
    'Mandera'       => [  3.936,  41.855], 'Garissa'   => [ -0.456,  39.646],
    'Rift Valley'   => [  0.517,  35.270], 'Nyanza'    => [ -0.670,  34.779],
    'Western Kenya' => [  0.283,  34.752], 'Central'   => [ -0.700,  36.870],
    'North Eastern' => [  1.500,  40.000], 'Coast'     => [ -3.200,  40.120],
    // Ethiopia
    'Addis Ababa'   => [  9.025,  38.747], 'Tigray'    => [ 14.032,  38.316],
    'Mekelle'       => [ 13.496,  39.477], 'Amhara'    => [ 11.340,  37.983],
    'Oromia'        => [  7.547,  39.647], 'SNNPR'     => [  6.875,  37.810],
    'Afar'          => [ 11.756,  41.173], 'Dire Dawa' => [  9.593,  41.867],
    'Bahir Dar'     => [ 11.593,  37.391], 'Harar'     => [  9.313,  42.118],
    'Jimma'         => [  7.678,  36.832], 'Jijiga'    => [  9.350,  42.795],
    // DRC
    'Kinshasa'      => [ -4.322,  15.322], 'Goma'      => [ -1.678,  29.217],
    'Bukavu'        => [ -2.508,  28.860], 'North Kivu'=> [ -0.786,  29.089],
    'South Kivu'    => [ -3.381,  29.361], 'Ituri'     => [  1.583,  29.872],
    'Kisangani'     => [  0.518,  25.196], 'Lubumbashi'=> [-11.663,  27.479],
    'Beni'          => [  0.491,  29.473], 'Katanga'   => [-10.000,  27.000],
    'Kasai'         => [ -5.000,  22.500], 'Maniema'   => [ -3.000,  26.500],
    'Bandundu'      => [ -4.500,  18.000],
    // Others
    'Juba'          => [  4.859,  31.571], 'Khartoum'  => [ 15.551,  32.532],
    'Mogadishu'     => [  2.046,  45.341], 'Kampala'   => [  0.347,  32.583],
    'Dar es Salaam' => [ -6.792,  39.208], 'Kigali'    => [ -1.944,  30.061],
    'Bamako'        => [ 12.650,  -8.000], 'Niamey'    => [ 13.513,   2.113],
    'Bangui'        => [  4.361,  18.555], "N'Djamena" => [ 12.105,  15.044],
    'Harare'        => [-17.828,  31.053], 'Lusaka'    => [-15.416,  28.283],
];
$_dbRegions = json_decode(get_setting('heatmap_regions', ''), true);
if (is_array($_dbRegions) && count($_dbRegions) > 0) {
    $regionCoords = [];
    foreach ($_dbRegions as $_r) {
        $regionCoords[$_r['name']] = [(float)$_r['lat'], (float)$_r['lng']];
    }
}

// ── Issue category → heatmap category mapping ──────────────────────────
$issueToCat = [
    'Security & Conflict'         => 'Security',
    'Human Rights'                => 'Human Rights',
    'Health'                      => 'Health',
    'Policy & Governance'         => 'Policy',
    'Climate & Environment'       => 'Climate',
    'Education'                   => 'Education',
    'Agriculture & Food Security' => 'Agriculture',
    'Migration & Refugees'        => 'Displacement',
    'Economic Development'        => 'Policy',
    'Technology & Innovation'     => 'Policy',
    'Gender & Social Affairs'     => 'Human Rights',
    'Infrastructure & Energy'     => 'Policy',
];
$_dbIssueMap = json_decode(get_setting('heatmap_issue_map', ''), true);
if (is_array($_dbIssueMap) && count($_dbIssueMap) > 0) {
    $issueToCat = [];
    $first = reset($_dbIssueMap);
    if (is_array($first) && isset($first['from'])) {
        foreach ($_dbIssueMap as $_m) {
            if (!empty($_m['from']) && !empty($_m['to'])) $issueToCat[$_m['from']] = $_m['to'];
        }
    } else {
        $issueToCat = $_dbIssueMap;
    }
}

// ── Conflict categories (with display colors) ──────────────────────────
$cats = [
    'Security'     => '#ED1C24',
    'Displacement' => '#E7952A',
    'Human Rights' => '#F59E0B',
    'Health'       => '#10B981',
    'Policy'       => '#8B5CF6',
    'Climate'      => '#3B82F6',
    'Education'    => '#6366F1',
    'Agriculture'  => '#84CC16',
];
$_dbCategories = json_decode(get_setting('heatmap_categories', ''), true);
if (is_array($_dbCategories) && count($_dbCategories) > 0) {
    $cats = [];
    foreach ($_dbCategories as $_cat) {
        $cats[$_cat['name']] = $_cat['color'];
    }
}

$postTypeUrl = [
    'ARTICLE'       => '/news/',
    'PODCAST'       => '/podcasts/',
    'VIDEO'         => '/videos/',
    'DOCUMENT'      => '/documents/',
    'GALLERY_IMAGE' => '/gallery',
];

// ── Resolve coordinates for published posts ────────────────────────────
$postMarkers = [];
try {
    $posts = db()->query("
        SELECT id, title, type, description, country, region, issueCategory, createdAt, thumbnailUrl
        FROM Post WHERE status='PUBLISHED' AND country IS NOT NULL AND country != ''
        ORDER BY createdAt DESC LIMIT 300
    ")->fetchAll();

    foreach ($posts as $p) {
        $lat = $lng = null;
        $region = trim($p['region'] ?? '');

        // Try region string against lookup keys
        if ($region !== '') {
            foreach ($regionCoords as $rKey => [$rlat, $rlng]) {
                if (stripos($region, $rKey) !== false || stripos($rKey, $region) !== false) {
                    $lat = $rlat; $lng = $rlng; break;
                }
            }
        }

        // Fall back to country centre
        if ($lat === null) {
            $cn = $p['country'];
            if (isset($countryCoords[$cn])) {
                [$lat, $lng] = $countryCoords[$cn];
            }
        }

        if ($lat === null) continue;

        // Deterministic sub-degree jitter so stacked pins spread out
        $h    = crc32($p['id']);
        $lat += (($h % 200) - 100) / 800.0;
        $lng += ((($h >> 8) % 200) - 100) / 800.0;

        $url = ($postTypeUrl[$p['type']] ?? '/news/') . $p['id'];
        if (($p['type'] ?? '') === 'GALLERY_IMAGE') $url = '/gallery';

        $postMarkers[] = [
            'id'       => $p['id'],
            'title'    => $p['title'],
            'type'     => $p['type'] ?? 'ARTICLE',
            'desc'     => mb_substr($p['description'] ?? '', 0, 150),
            'country'  => $p['country'],
            'region'   => $region,
            'category' => $issueToCat[$p['issueCategory'] ?? ''] ?? 'Policy',
            'date'     => format_date($p['createdAt']),
            'thumb'    => $p['thumbnailUrl'] ?? '',
            'url'      => $url,
            'lat'      => round($lat, 5),
            'lng'      => round($lng, 5),
        ];
    }
} catch (Exception $e) {}

// ── Summary stats ──────────────────────────────────────────────────────
$totalReports   = count($postMarkers);
$monthlyReports = 0;
try {
    $monthlyReports = (int) db()->query("SELECT COUNT(*) FROM Post WHERE status='PUBLISHED' AND createdAt >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
} catch (Exception $e) { /* ignore */ }

$pageTitle = 'Conflict Monitoring Dashboard | Tafakari Digital Hub';
$pageDesc  = 'Real-time conflict intensity mapping and issue tracking across African nations experiencing fragility, displacement, and humanitarian crises.';
$extraHead = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">';
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex flex-col" style="background:#F8F8F0;font-family:'Inter',sans-serif">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<style>
/* ── Pulse animations ─────────────────────────────────────────────── */
@keyframes pulse-ring {
  0%   { transform: scale(1);   opacity: .55; }
  100% { transform: scale(5.5); opacity: 0;   }
}
/* ── Map tile bg before load ──────────────────────────────────────── */
.leaflet-container { background: #181422; }

/* ── Leaflet tooltip overrides ────────────────────────────────────── */
.leaflet-tooltip {
  background: rgba(10,2,4,.92) !important;
  color: #fff !important;
  border: 1px solid rgba(255,255,255,.12) !important;
  border-radius: 8px !important;
  font-family: 'Inter', sans-serif !important;
  font-size: 11px !important;
  font-weight: 600 !important;
  padding: 5px 10px !important;
  box-shadow: 0 4px 16px rgba(0,0,0,.4) !important;
  white-space: nowrap !important;
}
.leaflet-tooltip::before,
.leaflet-tooltip-left::before,
.leaflet-tooltip-right::before { display: none !important; }

/* ── Attribution ──────────────────────────────────────────────────── */
.leaflet-control-attribution {
  background: rgba(0,0,0,.6) !important;
  color: rgba(255,255,255,.4) !important;
  font-size: 9px !important;
}
.leaflet-control-attribution a { color: rgba(231,149,42,.7) !important; }

/* ── Zoom control ─────────────────────────────────────────────────── */
.leaflet-control-zoom a {
  background: rgba(10,2,4,.88) !important;
  color: rgba(255,255,255,.8) !important;
  border-color: rgba(255,255,255,.12) !important;
}
.leaflet-control-zoom a:hover { background: rgba(231,149,42,.2) !important; }

/* ── Control panel scrollbar ──────────────────────────────────────── */
#ctrl-panel {
  scrollbar-width: thin;
  scrollbar-color: rgba(255,255,255,.15) transparent;
}
#ctrl-panel::-webkit-scrollbar { width: 3px; }
#ctrl-panel::-webkit-scrollbar-thumb { background: rgba(255,255,255,.18); border-radius: 2px; }

/* ── Search input placeholder ─────────────────────────────────────── */
#map-search::placeholder { color: rgba(255,255,255,.28); }

/* ── Category checkboxes ──────────────────────────────────────────── */
.cat-check { display: none; }
.cat-label {
  display: flex; align-items: center; gap: 8px; cursor: pointer;
  padding: 5px 7px; border-radius: 8px; transition: background .15s;
}
.cat-label:hover { background: rgba(255,255,255,.07); }
.cat-dot {
  width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0;
  border: 2px solid transparent; transition: border-color .15s;
}
.cat-check:checked + .cat-label .cat-dot { border-color: rgba(255,255,255,.65); }
.cat-check:not(:checked) + .cat-label { opacity: .38; }

/* ── Intensity slider ─────────────────────────────────────────────── */
.int-slider {
  -webkit-appearance: none; width: 100%; height: 4px;
  border-radius: 2px; outline: none; cursor: pointer;
}
.int-slider::-webkit-slider-thumb {
  -webkit-appearance: none; width: 15px; height: 15px; border-radius: 50%;
  background: #E7952A; border: 2px solid #fff;
  box-shadow: 0 1px 4px rgba(0,0,0,.4); cursor: pointer;
}
.int-slider::-moz-range-thumb {
  width: 15px; height: 15px; border-radius: 50%;
  background: #E7952A; border: 2px solid #fff; cursor: pointer; border:none;
}

/* ── Detail panel (desktop) ───────────────────────────────────────── */
#detail-panel { transition: transform .32s cubic-bezier(.4,0,.2,1); }
#detail-panel.panel-hidden { transform: translateX(100%); }
#detail-panel.panel-open   { transform: translateX(0); }

/* ── Mobile filter toggle (hidden on desktop) ─────────────────────── */
#ctrl-toggle { display: none; }

/* ── Ctrl-panel backdrop (mobile only) ───────────────────────────── */
#ctrl-backdrop {
  position: absolute; inset: 0;
  background: rgba(0,0,0,.5); z-index: 999;
  display: none;
  backdrop-filter: blur(1px);
}

/* ── Mobile overrides ─────────────────────────────────────────────── */
@media (max-width: 767px) {
  /* Show the filter toggle button */
  #ctrl-toggle { display: flex; }

  /* Map height — leave room for browser chrome */
  #map-wrapper { height: 380px !important; }

  /* Full-width sliding filter panel */
  #ctrl-panel {
    width: 100% !important;
    transform: translateX(-100%);
    transition: transform .3s cubic-bezier(.4,0,.2,1);
    z-index: 1500 !important;
  }
  #ctrl-panel.ctrl-open { transform: translateX(0); }

  /* Detail panel becomes a bottom sheet */
  #detail-panel {
    top: auto !important;
    bottom: 0 !important;
    left: 0 !important;
    right: 0 !important;
    width: 100% !important;
    height: 65%;
    border-radius: 20px 20px 0 0;
    box-shadow: 0 -8px 48px rgba(0,0,0,.28) !important;
  }
  #detail-panel.panel-hidden { transform: translateY(100%); }
  #detail-panel.panel-open   { transform: translateY(0); }

  /* Larger tap targets on toolbar buttons */
  #map-wrapper .absolute.top-3.right-3 button { width: 40px !important; height: 40px !important; }

  /* Zoom controls bigger on touch */
  .leaflet-control-zoom a { width: 36px !important; height: 36px !important; line-height: 36px !important; font-size: 18px !important; }

  /* Active count pill — avoid gesture-bar overlap */
  #active-count { font-size: 11px; }
}
</style>

<main class="flex-grow">

  <!-- ── Page header ───────────────────────────────────────────────── -->
  <div style="background:#750B25" class="text-white px-4 md:px-6 py-6 md:py-8">
    <div class="max-w-7xl mx-auto flex flex-col lg:flex-row lg:items-end gap-4 md:gap-6">

      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 mb-2">
          <span class="relative flex h-2 w-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" style="background:#E7952A"></span>
            <span class="relative inline-flex rounded-full h-2 w-2" style="background:#E7952A"></span>
          </span>
          <span class="text-[10px] font-black uppercase tracking-[.14em]" style="color:rgba(231,149,42,.85)" data-i18n="heatmapPage.liveMonitoring">Live Conflict Monitoring</span>
        </div>
        <h1 class="font-outfit font-black text-2xl md:text-4xl leading-tight" data-i18n="heatmapPage.dashboardTitle">Regional Conflict Dashboard</h1>
        <p class="mt-1.5 text-xs md:text-sm leading-relaxed max-w-2xl hidden sm:block" style="color:rgba(248,248,240,.55)" data-i18n="heatmapPage.dashboardDesc">
          Mapping conflict intensity, displacement, and humanitarian crises across 14 African nations. Click any marker for detailed intelligence.
        </p>
      </div>

      <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 md:gap-3 shrink-0">
        <?php
        $kpis = [
          [format_number($totalReports),   'Total Reports',    '#E7952A', 'totalReports'],
          [format_number($monthlyReports), 'This Month',       '#E7952A', 'thisMonth'],
          ['—',                            'Active Hotspots',  '#ED1C24', 'activeHotspots'],
          ['14',                           'Countries',        '#E7952A', 'countriesKpi'],
        ];
        foreach ($kpis as $i => [$val, $lbl, $col, $lblKey]): ?>
          <div class="rounded-xl md:rounded-2xl px-3 md:px-4 py-2.5 md:py-3 text-center" style="background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.1)">
            <div class="font-outfit font-black text-xl md:text-2xl<?= $i===2?' text-red-400':'' ?>" id="<?= $i===2?'kpi-hotspots':'' ?>"><?= $val ?></div>
            <div class="text-[9px] md:text-[10px] font-bold uppercase tracking-widest mt-0.5" style="color:<?= $col ?>;opacity:.8" data-i18n="heatmapPage.kpi.<?= h($lblKey) ?>"><?= $lbl ?></div>
          </div>
        <?php endforeach; ?>
      </div>

    </div>
  </div>

  <!-- ── Map section ───────────────────────────────────────────────── -->
  <div id="map-wrapper" class="relative" style="height:620px">

    <!-- Mobile filter toggle -->
    <button id="ctrl-toggle"
            onclick="_ctrlOpen()"
            class="absolute top-3 left-3 z-[1100] items-center gap-2 px-3 py-2.5 rounded-xl text-xs font-bold text-white"
            style="background:rgba(10,2,4,.88);border:1px solid rgba(255,255,255,.14);min-height:40px">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" viewBox="0 0 24 24">
        <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
      </svg>
      <span data-i18n="heatmapPage.filters">Filters</span>
    </button>

    <!-- Ctrl-panel mobile backdrop -->
    <div id="ctrl-backdrop" onclick="_ctrlClose()"></div>

    <!-- ── Left control panel ───────────────────────────────────────── -->
    <div id="ctrl-panel"
         class="absolute top-0 left-0 bottom-0 z-[1000] overflow-y-auto"
         style="width:248px;background:rgba(10,2,4,.93);backdrop-filter:blur(12px);border-right:1px solid rgba(255,255,255,.07)">

      <!-- Mobile-only header with close button -->
      <div class="md:hidden flex items-center justify-between px-4 py-3.5" style="border-bottom:1px solid rgba(255,255,255,.1);flex-shrink:0">
        <span class="text-xs font-black uppercase tracking-[.12em]" style="color:rgba(231,149,42,.8)" data-i18n="heatmapPage.mapFilters">Map Filters</span>
        <button onclick="_ctrlClose()"
                style="width:32px;height:32px;border-radius:50%;border:none;background:rgba(255,255,255,.1);color:#fff;font-size:20px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center">&times;</button>
      </div>

      <div class="p-4 space-y-5">

        <!-- Search -->
        <div>
          <p class="text-[10px] font-black uppercase tracking-[.12em] mb-1.5" style="color:rgba(231,149,42,.7)" data-i18n="heatmapPage.searchLocations">Search Locations</p>
          <div class="relative">
            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 pointer-events-none" style="color:rgba(255,255,255,.3)" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
              <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input id="map-search" type="search" placeholder="Location, country…" oninput="applyFilters()"
                   data-i18n-placeholder="heatmapPage.searchPlaceholder"
                   class="w-full text-xs text-white rounded-xl border pl-8 pr-3 py-2.5 focus:outline-none focus:border-amber-400/40"
                   style="background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.1)">
          </div>
        </div>

        <!-- View mode -->
        <div>
          <p class="text-[10px] font-black uppercase tracking-[.12em] mb-1.5" style="color:rgba(231,149,42,.7)" data-i18n="heatmapPage.viewMode">View Mode</p>
          <div class="grid grid-cols-2 gap-1.5 p-1 rounded-xl" style="background:rgba(255,255,255,.05)">
            <button id="mode-markers" onclick="setMode('markers')"
                    class="mode-btn text-xs font-bold py-2 rounded-lg text-white transition-all"
                    style="background:rgba(231,149,42,.22);border:1px solid rgba(231,149,42,.35)">
              ● <span data-i18n="heatmapPage.markers">Markers</span>
            </button>
            <button id="mode-heat" onclick="setMode('heat')"
                    class="mode-btn text-xs font-bold py-2 rounded-lg transition-all"
                    style="color:rgba(255,255,255,.4);background:transparent;border:1px solid transparent">
              ◈ <span data-i18n="heatmapPage.heatmapMode">Heatmap</span>
            </button>
          </div>
        </div>

        <!-- Country filter -->
        <div>
          <div class="flex items-center justify-between mb-1.5">
            <p class="text-[10px] font-black uppercase tracking-[.12em]" style="color:rgba(231,149,42,.7)" data-i18n="heatmapPage.country">Country</p>
            <button onclick="setCountry('ALL')" class="text-[10px] font-bold" style="color:rgba(231,149,42,.45)" data-i18n="heatmapPage.reset">Reset</button>
          </div>
          <div class="space-y-px max-h-44 overflow-y-auto pr-0.5" style="scrollbar-width:thin;scrollbar-color:rgba(255,255,255,.1) transparent">
            <?php foreach ($ctryList as $code => [$name, $flag]): ?>
              <button onclick="setCountry('<?= $code ?>')" data-ctry="<?= $code ?>"
                      class="ctry-btn w-full flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-left transition-all"
                      style="color:rgba(255,255,255,.55)">
                <span style="font-size:14px;line-height:1"><?= $flag ?></span>
                <span class="text-xs font-medium" <?= $code === 'ALL' ? 'data-i18n="heatmapPage.allCountries"' : '' ?>><?= h($name) ?></span>
              </button>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Category filter -->
        <div>
          <div class="flex items-center justify-between mb-1.5">
            <p class="text-[10px] font-black uppercase tracking-[.12em]" style="color:rgba(231,149,42,.7)" data-i18n="heatmapPage.category">Category</p>
            <button onclick="toggleAllCats()" class="text-[10px] font-bold" style="color:rgba(231,149,42,.45)" data-i18n="heatmapPage.toggleAll">Toggle All</button>
          </div>
          <?php foreach ($cats as $cat => $col):
            $id = 'cat-' . str_replace(' ', '_', $cat); ?>
            <div>
              <input type="checkbox" class="cat-check" id="<?= $id ?>" value="<?= h($cat) ?>" checked onchange="applyFilters()">
              <label for="<?= $id ?>" class="cat-label">
                <span class="cat-dot" style="background:<?= $col ?>"></span>
                <span class="text-[11px]" style="color:rgba(255,255,255,.72)"><?= h($cat) ?></span>
                <span class="text-[10px] font-bold ml-auto cat-count" data-cat="<?= h($cat) ?>" style="color:rgba(255,255,255,.28)"></span>
              </label>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Intensity slider -->
        <div>
          <div class="flex items-center justify-between mb-1.5">
            <p class="text-[10px] font-black uppercase tracking-[.12em]" style="color:rgba(231,149,42,.7)" data-i18n="heatmapPage.minIntensity">Min Intensity</p>
            <span id="int-val" class="text-xs font-black" style="color:#E7952A">1</span>
          </div>
          <input type="range" class="int-slider" id="int-slider" min="1" max="10" value="1"
                 oninput="updateSlider();applyFilters()"
                 style="background:linear-gradient(to right,#E7952A 0%,rgba(255,255,255,.12) 0%)">
          <div class="flex justify-between mt-1">
            <span class="text-[9px]" style="color:rgba(255,255,255,.22)" data-i18n="heatmapPage.low">Low</span>
            <span class="text-[9px]" style="color:rgba(255,255,255,.22)" data-i18n="heatmapPage.critical">Critical</span>
          </div>
        </div>

        <!-- Intensity legend -->
        <div>
          <p class="text-[10px] font-black uppercase tracking-[.12em] mb-2.5" style="color:rgba(231,149,42,.7)" data-i18n="heatmapPage.intensityLegend">Intensity Legend</p>
          <div class="space-y-2">
            <div class="flex items-center gap-2.5">
              <div style="width:13px;height:13px;border-radius:50%;background:#ED1C24;box-shadow:0 0 8px #ED1C2499;flex-shrink:0"></div>
              <span class="text-[11px]" style="color:rgba(255,255,255,.55)" data-i18n="heatmapPage.legendCritical">8–10 · Critical / Active conflict</span>
            </div>
            <div class="flex items-center gap-2.5">
              <div style="width:11px;height:11px;border-radius:50%;background:#E7952A;box-shadow:0 0 6px #E7952A77;flex-shrink:0;margin-left:1px"></div>
              <span class="text-[11px]" style="color:rgba(255,255,255,.55)" data-i18n="heatmapPage.legendElevated">5–7 · Elevated risk</span>
            </div>
            <div class="flex items-center gap-2.5">
              <div style="width:8px;height:8px;border-radius:50%;background:#F4C87E;flex-shrink:0;margin-left:2.5px"></div>
              <span class="text-[11px]" style="color:rgba(255,255,255,.55)" data-i18n="heatmapPage.legendEmerging">1–4 · Emerging concern</span>
            </div>
          </div>
        </div>

        <!-- Published Reports toggle -->
        <div style="border-top:1px solid rgba(255,255,255,.08);padding-top:16px">
          <div class="flex items-center justify-between mb-2">
            <p class="text-[10px] font-black uppercase tracking-[.12em]" style="color:rgba(99,102,241,.85)" data-i18n="heatmapPage.publishedReports">Published Reports</p>
            <button id="reports-toggle-btn" onclick="toggleReports()"
                    class="text-[10px] font-black px-2.5 py-1 rounded-lg transition-all"
                    style="background:rgba(99,102,241,.25);color:#818CF8;border:1px solid rgba(99,102,241,.35)">
              ON
            </button>
          </div>
          <p class="text-[10px] mb-3" style="color:rgba(255,255,255,.28)"><?= count($postMarkers) ?> <span data-i18n="heatmapPage.reportsFromDb">reports from the database</span></p>
          <div class="space-y-1.5">
            <div class="flex items-center gap-2">
              <div style="width:10px;height:10px;background:#750B25;border:1.5px solid rgba(255,255,255,.5);border-radius:2px;flex-shrink:0"></div>
              <span class="text-[11px]" style="color:rgba(255,255,255,.45)" data-i18n="heatmapPage.legendArticle">Article</span>
            </div>
            <div class="flex items-center gap-2">
              <div style="width:10px;height:10px;background:#E7952A;border:1.5px solid rgba(255,255,255,.5);border-radius:2px;flex-shrink:0"></div>
              <span class="text-[11px]" style="color:rgba(255,255,255,.45)" data-i18n="heatmapPage.legendPodcast">Podcast</span>
            </div>
            <div class="flex items-center gap-2">
              <div style="width:10px;height:10px;background:#ED1C24;border:1.5px solid rgba(255,255,255,.5);border-radius:2px;flex-shrink:0"></div>
              <span class="text-[11px]" style="color:rgba(255,255,255,.45)" data-i18n="heatmapPage.legendVideo">Video</span>
            </div>
            <div class="flex items-center gap-2">
              <div style="width:10px;height:10px;background:#6366F1;border:1.5px solid rgba(255,255,255,.5);border-radius:2px;flex-shrink:0"></div>
              <span class="text-[11px]" style="color:rgba(255,255,255,.45)" data-i18n="heatmapPage.legendDocument">Document</span>
            </div>
          </div>
        </div>

      </div>
    </div><!-- /#ctrl-panel -->

    <!-- ── Map ───────────────────────────────────────────────────────── -->
    <div id="map" style="width:100%;height:100%;z-index:1"></div>

    <!-- ── Map toolbar (top-right) ──────────────────────────────────── -->
    <div class="absolute top-3 right-3 z-[1000] flex flex-col gap-2">
      <button onclick="resetView()" title="Reset map view" data-i18n-title="heatmapPage.resetMapView"
              class="w-9 h-9 flex items-center justify-center rounded-xl text-white hover:opacity-80 transition-opacity"
              style="background:rgba(10,2,4,.82);border:1px solid rgba(255,255,255,.12)">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
          <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>
        </svg>
      </button>
      <button onclick="toggleFullscreen()" title="Toggle fullscreen" data-i18n-title="heatmapPage.toggleFullscreen"
              class="w-9 h-9 flex items-center justify-center rounded-xl text-white hover:opacity-80 transition-opacity"
              style="background:rgba(10,2,4,.82);border:1px solid rgba(255,255,255,.12)">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
          <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
        </svg>
      </button>
    </div>

    <!-- ── Active count pill ─────────────────────────────────────────── -->
    <div class="absolute bottom-5 left-1/2 -translate-x-1/2 z-[1000]">
      <div class="flex items-center gap-2 px-4 py-2 rounded-full text-xs font-semibold text-white"
           style="background:rgba(10,2,4,.8);border:1px solid rgba(255,255,255,.1);backdrop-filter:blur(8px)">
        <span class="w-1.5 h-1.5 rounded-full animate-pulse" style="background:#10b981"></span>
        <span id="active-count">Loading…</span>
      </div>
    </div>

    <!-- ── Right detail panel ────────────────────────────────────────── -->
    <div id="detail-panel"
         class="absolute top-0 right-0 bottom-0 z-[1000] overflow-y-auto panel-hidden"
         style="width:300px;background:#fff;box-shadow:-4px 0 40px rgba(0,0,0,.18)">
      <div id="detail-content"></div>
    </div>

  </div><!-- /#map-wrapper -->

  <!-- ── Top Flashpoints table ─────────────────────────────────────── -->
  <div class="max-w-7xl mx-auto px-6 py-10">
    <div class="flex items-center justify-between mb-5">
      <div>
        <h2 class="font-outfit font-black text-xl text-slate-900" data-i18n="heatmapPage.topFlashpoints">Top Flashpoints</h2>
        <p class="text-sm text-slate-400 mt-0.5" data-i18n="heatmapPage.topFlashpointsDesc">Highest-intensity locations currently visible — click any row to zoom in</p>
      </div>
      <span class="text-[10px] font-black uppercase tracking-widest text-slate-300" data-i18n="heatmapPage.sortedBySeverity">Sorted by severity</span>
    </div>
    <div class="overflow-x-auto rounded-2xl border border-slate-100 shadow-sm">
      <table class="w-full text-sm">
        <thead>
          <tr style="background:#750B25;color:#fff">
            <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest w-10">#</th>
            <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest" data-i18n="heatmapPage.location">Location</th>
            <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest hidden md:table-cell" data-i18n="heatmapPage.country">Country</th>
            <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest hidden sm:table-cell" data-i18n="heatmapPage.category">Category</th>
            <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest w-36" data-i18n="heatmapPage.intensity">Intensity</th>
          </tr>
        </thead>
        <tbody id="hotspot-tbody">
          <tr><td colspan="5" class="text-center py-8 text-slate-300 text-sm" data-i18n="heatmapPage.loadingEllipsis">Loading…</td></tr>
        </tbody>
      </table>
    </div>
  </div>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<!-- Leaflet + heat plugin -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.heat/0.2.0/leaflet-heat.js"></script>

<script>
/* ── Map data ─────────────────────────────────────────────────────── */
var mapData = [
  // ── Kenya
  {lat:1.286,  lng:36.817, name:"Nairobi",        country:"KE", category:"Health",       intensity:8,  desc:"Urban health services strain and informal settlement conditions."},
  {lat:-1.286, lng:36.823, name:"Kiambu",          country:"KE", category:"Education",    intensity:6,  desc:"School access challenges in peri-urban areas."},
  {lat:-4.043, lng:39.668, name:"Mombasa",         country:"KE", category:"Security",     intensity:7,  desc:"Coastal security incidents and radicalisation concerns."},
  {lat:0.517,  lng:35.270, name:"Eldoret",         country:"KE", category:"Agriculture",  intensity:5,  desc:"Crop failure due to drought in North Rift."},
  {lat:-0.102, lng:34.761, name:"Kisumu",          country:"KE", category:"Health",       intensity:7,  desc:"Malaria and waterborne disease prevalence."},
  {lat:0.283,  lng:37.454, name:"Meru",            country:"KE", category:"Climate",      intensity:6,  desc:"Deforestation and water catchment concerns."},
  {lat:-0.670, lng:37.147, name:"Thika",           country:"KE", category:"Policy",       intensity:4,  desc:"Land use disputes and urban expansion."},
  {lat:3.119,  lng:35.597, name:"Turkana",         country:"KE", category:"Human Rights", intensity:9,  desc:"Pastoralist resource conflict and drought displacement."},
  {lat:1.750,  lng:41.120, name:"Mandera",         country:"KE", category:"Security",     intensity:8,  desc:"Cross-border insecurity and Al-Shabaab incursions."},
  // ── Ethiopia
  {lat:9.024,  lng:38.747, name:"Addis Ababa",     country:"ET", category:"Policy",       intensity:7,  desc:"Political reforms and governance transition."},
  {lat:14.495, lng:39.475, name:"Mekelle",         country:"ET", category:"Human Rights", intensity:9,  desc:"Post-conflict humanitarian situation in Tigray."},
  {lat:9.593,  lng:41.867, name:"Dire Dawa",       country:"ET", category:"Security",     intensity:6,  desc:"Inter-ethnic tensions and border security."},
  {lat:7.678,  lng:36.832, name:"Hawassa",         country:"ET", category:"Education",    intensity:5,  desc:"Educational infrastructure gaps in SNNPR."},
  {lat:11.593, lng:37.391, name:"Bahir Dar",       country:"ET", category:"Climate",      intensity:7,  desc:"Lake Tana water levels and agricultural stress."},
  {lat:9.313,  lng:42.118, name:"Jijiga",          country:"ET", category:"Health",       intensity:8,  desc:"Healthcare access crisis in the Somali region."},
  {lat:8.550,  lng:39.268, name:"Adama",           country:"ET", category:"Agriculture",  intensity:5,  desc:"Irrigation infrastructure challenges."},
  {lat:6.033,  lng:37.559, name:"Amhara West",     country:"ET", category:"Security",     intensity:9,  desc:"Fano militia activity and displacement."},
  // ── DR Congo
  {lat:-4.322, lng:15.322, name:"Kinshasa",        country:"CD", category:"Policy",       intensity:7,  desc:"Urban governance challenges and political instability."},
  {lat:-1.678, lng:29.217, name:"Goma",            country:"CD", category:"Security",     intensity:10, desc:"M23 armed conflict and mass civilian displacement."},
  {lat:-11.663,lng:27.479, name:"Lubumbashi",      country:"CD", category:"Human Rights", intensity:8,  desc:"Mining sector human rights abuses and labour exploitation."},
  {lat:-3.381, lng:29.361, name:"Bukavu",          country:"CD", category:"Health",       intensity:8,  desc:"Healthcare crisis and gender-based violence."},
  {lat:4.316,  lng:18.033, name:"Kisangani",       country:"CD", category:"Education",    intensity:6,  desc:"Education access deficits in Orientale province."},
  {lat:-2.499, lng:28.860, name:"Uvira",           country:"CD", category:"Displacement", intensity:9,  desc:"Flooding and armed group-driven displacement."},
  {lat:0.518,  lng:25.196, name:"Beni",            country:"CD", category:"Security",     intensity:10, desc:"ADF militia attacks and civilian casualties."},
  // ── South Sudan
  {lat:4.859,  lng:31.571, name:"Juba",            country:"SS", category:"Policy",       intensity:8,  desc:"Political fragility and peace deal implementation stall."},
  {lat:7.699,  lng:28.031, name:"Wau",             country:"SS", category:"Displacement", intensity:9,  desc:"Intercommunal violence and IDP camps."},
  {lat:9.533,  lng:31.661, name:"Malakal",         country:"SS", category:"Security",     intensity:9,  desc:"Upper Nile conflict and UNMISS protection site."},
  {lat:9.209,  lng:29.796, name:"Bentiu",          country:"SS", category:"Health",       intensity:8,  desc:"Flooding, cholera outbreaks and food insecurity."},
  {lat:8.490,  lng:30.660, name:"Rumbek",          country:"SS", category:"Human Rights", intensity:7,  desc:"Cattle raiding and gender-based violence."},
  // ── Sudan
  {lat:15.551, lng:32.532, name:"Khartoum",        country:"SD", category:"Security",     intensity:10, desc:"RSF–SAF urban warfare with civilian mass casualties."},
  {lat:13.634, lng:25.349, name:"El Fasher",       country:"SD", category:"Human Rights", intensity:10, desc:"Darfur genocide warnings and siege conditions."},
  {lat:19.616, lng:37.217, name:"Port Sudan",      country:"SD", category:"Displacement", intensity:7,  desc:"IDP influx and humanitarian bottleneck at sea port."},
  {lat:12.861, lng:30.217, name:"Sennar",          country:"SD", category:"Health",       intensity:6,  desc:"Healthcare collapse and disease outbreak."},
  {lat:11.459, lng:27.912, name:"Nyala",           country:"SD", category:"Security",     intensity:9,  desc:"South Darfur ethnic violence and looting."},
  // ── Mozambique
  {lat:-25.891,lng:32.605, name:"Maputo",          country:"MZ", category:"Policy",       intensity:5,  desc:"Governance challenges and post-election tensions."},
  {lat:-11.706,lng:40.513, name:"Pemba",           country:"MZ", category:"Security",     intensity:9,  desc:"Cabo Delgado insurgency — Ansar al-Sunna jihadist attacks."},
  {lat:-13.368,lng:40.337, name:"Mocímboa",        country:"MZ", category:"Displacement", intensity:10, desc:"Insurgent occupation and mass displacement."},
  {lat:-15.116,lng:39.268, name:"Nampula",         country:"MZ", category:"Agriculture",  intensity:6,  desc:"Food insecurity and climate stress in the north."},
  {lat:-19.823,lng:34.838, name:"Beira",           country:"MZ", category:"Climate",      intensity:7,  desc:"Cyclone Idai legacy: infrastructure and recovery gaps."},
  // ── Burkina Faso
  {lat:12.362, lng:-1.534, name:"Ouagadougou",     country:"BF", category:"Security",     intensity:9,  desc:"Junta rule, jihadist attacks and press freedom crisis."},
  {lat:13.450, lng:-0.900, name:"Kaya",            country:"BF", category:"Displacement", intensity:9,  desc:"Sahel region displacement — over 2 million IDPs."},
  {lat:14.300, lng:-0.056, name:"Dori",            country:"BF", category:"Human Rights", intensity:10, desc:"JNIM siege — humanitarian access blocked."},
  {lat:11.177, lng:-4.297, name:"Bobo-Dioulasso",  country:"BF", category:"Security",     intensity:7,  desc:"Southern security deterioration and military incidents."},
  // ── Somalia
  {lat:2.046,  lng:45.341, name:"Mogadishu",       country:"SO", category:"Security",     intensity:9,  desc:"Al-Shabaab urban attacks and political instability."},
  {lat:-0.359, lng:42.545, name:"Kismayo",         country:"SO", category:"Human Rights", intensity:8,  desc:"Clan conflict and displacement in Jubbaland."},
  {lat:11.284, lng:49.183, name:"Bosaso",          country:"SO", category:"Security",     intensity:7,  desc:"Puntland ISIS activity and piracy networks."},
  {lat:2.044,  lng:45.341, name:"Lower Shabelle",  country:"SO", category:"Displacement", intensity:9,  desc:"Al-Shabaab territorial control and civilian displacement."},
  // ── Mali
  {lat:12.650, lng:-8.000, name:"Bamako",          country:"ML", category:"Policy",       intensity:7,  desc:"Military junta governance and anti-Western posture."},
  {lat:16.270, lng:-0.042, name:"Gao",             country:"ML", category:"Security",     intensity:9,  desc:"JNIM and IS Sahel armed group presence."},
  {lat:20.000, lng:-1.700, name:"Kidal",           country:"ML", category:"Security",     intensity:10, desc:"Tuareg CMA–Wagner conflict and army loss of control."},
  {lat:16.770, lng:-3.008, name:"Timbuktu",        country:"ML", category:"Displacement", intensity:8,  desc:"Jihadist blockade and mass civilian flight."},
  // ── Niger
  {lat:13.513, lng:2.113,  name:"Niamey",          country:"NE", category:"Policy",       intensity:8,  desc:"Post-coup junta instability and ECOWAS standoff."},
  {lat:16.964, lng:7.994,  name:"Agadez",          country:"NE", category:"Security",     intensity:8,  desc:"Sahara smuggling routes and IS Sahel recruitment."},
  {lat:14.212, lng:1.458,  name:"Tillabéri",       country:"NE", category:"Security",     intensity:9,  desc:"Tri-border area jihadist attacks on civilians."},
  // ── Central African Republic
  {lat:4.361,  lng:18.555, name:"Bangui",          country:"CF", category:"Security",     intensity:8,  desc:"Wagner-backed government and anti-Balaka/CPC conflict."},
  {lat:5.771,  lng:20.680, name:"Bambari",         country:"CF", category:"Human Rights", intensity:9,  desc:"UPC militia control and severe humanitarian crisis."},
  {lat:8.582,  lng:16.074, name:"Ndélé",           country:"CF", category:"Displacement", intensity:7,  desc:"FPRC armed group presence and displacement."},
  // ── Chad
  {lat:12.105, lng:15.044, name:"N'Djamena",       country:"TD", category:"Policy",       intensity:7,  desc:"Post-Déby transitional instability and rebel activity."},
  {lat:13.017, lng:13.450, name:"Lac Chad",        country:"TD", category:"Security",     intensity:9,  desc:"Boko Haram / ISWAP attacks across Lake Chad basin."},
  {lat:8.592,  lng:16.078, name:"Sarh",            country:"TD", category:"Displacement", intensity:7,  desc:"South Chad intercommunal violence and displacement."},
  // ── Nigeria (Northeast)
  {lat:11.843, lng:13.150, name:"Maiduguri",       country:"NG", category:"Security",     intensity:9,  desc:"Boko Haram / ISWAP epicentre — 13+ years of conflict."},
  {lat:11.996, lng:14.984, name:"Borno State",     country:"NG", category:"Displacement", intensity:9,  desc:"2+ million IDPs — largest displacement crisis in West Africa."},
  {lat:10.523, lng:7.440,  name:"Zamfara",         country:"NG", category:"Security",     intensity:8,  desc:"Bandit armed groups, mass kidnappings and farmer-herder crisis."},
  // ── Cameroon
  {lat:4.154,  lng:9.242,  name:"Buea",            country:"CM", category:"Security",     intensity:8,  desc:"Anglophone Ambazonia separatist conflict and ghost towns."},
  {lat:5.960,  lng:10.158, name:"Bamenda",         country:"CM", category:"Human Rights", intensity:9,  desc:"Ambazonia armed group atrocities and school shutdowns."},
];

/* ── Lookup maps ──────────────────────────────────────────────────── */
var CAT_COLORS = <?= json_encode($cats,    JSON_UNESCAPED_UNICODE) ?>;
var FLAG       = <?= json_encode($flagMap,  JSON_UNESCAPED_UNICODE) ?>;
var CNAME      = <?= json_encode($cnameMap, JSON_UNESCAPED_UNICODE) ?>;

/* ── Published post markers from DB ──────────────────────────────── */
var postData = <?= json_encode($postMarkers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

/* ── Map initialisation ───────────────────────────────────────────── */
var map = L.map('map', { zoomControl: false }).setView([5, 22], 4);
L.control.zoom({ position: 'bottomright' }).addTo(map);

L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
  attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
  subdomains: 'abcd',
  maxZoom: 19
}).addTo(map);

/* ── Marker icon factory ──────────────────────────────────────────── */
function makeIcon(d) {
  var hi  = d.intensity > 7, md = d.intensity > 4;
  var col = hi ? '#ED1C24' : md ? '#E7952A' : '#F4C87E';
  var dot = hi ? 15 : md ? 11 : 7;
  var box = hi ? 52 : md ? 38 : 26;
  var glow = hi
    ? '0 0 14px 4px ' + col + '88'
    : md ? '0 0 8px 2px ' + col + '55'
    : 'none';

  var rings = '';
  if (hi) {
    rings += wrap(dot, col, '1.8s', '0s');
    rings += wrap(dot, col, '1.8s', '.6s');
  } else if (md) {
    rings += wrap(dot, col, '2.6s', '0s');
  }

  return L.divIcon({
    className: '',
    iconSize:      [box, box],
    iconAnchor:    [box/2, box/2],
    tooltipAnchor: [box/2+3, 0],
    html: '<div style="position:relative;width:'+box+'px;height:'+box+'px;display:flex;align-items:center;justify-content:center;overflow:visible">'
        + rings
        + '<div style="width:'+dot+'px;height:'+dot+'px;border-radius:50%;background:'+col
        + ';border:1.5px solid rgba(255,255,255,.5);box-shadow:'+glow
        + ';position:relative;z-index:2;cursor:pointer"></div>'
        + '</div>',
  });
}

function wrap(dot, col, dur, delay) {
  return '<div style="position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);width:'+dot+'px;height:'+dot+'px;pointer-events:none">'
       + '<div style="width:100%;height:100%;border-radius:50%;background:'+col
       + ';animation:pulse-ring '+dur+' ease-out '+delay+' infinite;"></div></div>';
}

/* ── Post marker icon factory ─────────────────────────────────────── */
var POST_TYPE_COLORS = {
  ARTICLE:'#750B25', PODCAST:'#E7952A', VIDEO:'#ED1C24',
  DOCUMENT:'#6366F1', GALLERY_IMAGE:'#10B981'
};
function makePostIcon(d) {
  var col = POST_TYPE_COLORS[d.type] || '#750B25';
  return L.divIcon({
    className: '',
    iconSize:  [22, 22],
    iconAnchor:[11, 11],
    tooltipAnchor: [14, 0],
    html: '<div style="width:22px;height:22px;display:flex;align-items:center;justify-content:center">'
        + '<div style="width:13px;height:13px;background:'+col
        + ';border:2px solid rgba(255,255,255,.75);border-radius:3px;'
        + 'box-shadow:0 2px 10px rgba(0,0,0,.45);cursor:pointer"></div>'
        + '</div>',
  });
}

/* ── Toggle published reports layer ──────────────────────────────── */
function toggleReports() {
  showReports = !showReports;
  var btn = document.getElementById('reports-toggle-btn');
  if (btn) {
    btn.textContent = showReports ? 'ON' : 'OFF';
    btn.style.background    = showReports ? 'rgba(99,102,241,.25)' : 'rgba(255,255,255,.06)';
    btn.style.color         = showReports ? '#818CF8' : 'rgba(255,255,255,.35)';
    btn.style.borderColor   = showReports ? 'rgba(99,102,241,.35)' : 'rgba(255,255,255,.1)';
  }
  applyFilters();
}

/* ── State ────────────────────────────────────────────────────────── */
var markers      = [];
var postMarkers  = [];  // Leaflet markers for DB posts
var heatLayer    = null;
var viewMode     = 'markers';
var selCtry      = 'ALL';
var allCatsOn    = true;
var showReports  = true;

/* ── HTML escape ──────────────────────────────────────────────────── */
function esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── Intensity slider update ──────────────────────────────────────── */
function updateSlider() {
  var v = document.getElementById('int-slider').value;
  var pct = ((v-1)/9*100).toFixed(1);
  document.getElementById('int-val').textContent = v;
  document.getElementById('int-slider').style.background =
    'linear-gradient(to right,#E7952A '+pct+'%,rgba(255,255,255,.12) '+pct+'%)';
}

/* ── Country filter ───────────────────────────────────────────────── */
function setCountry(code) {
  selCtry = code;
  document.querySelectorAll('.ctry-btn').forEach(function(b) {
    var active = b.dataset.ctry === code;
    b.style.background  = active ? 'rgba(231,149,42,.18)' : '';
    b.style.color       = active ? '#E7952A' : 'rgba(255,255,255,.5)';
    b.style.fontWeight  = active ? '700' : '500';
  });
  applyFilters();
  /* Auto-close filter panel after country select on mobile */
  if (window.innerWidth < 768 && code !== 'ALL') {
    setTimeout(function(){ _ctrlClose(); }, 250);
  }
}

/* ── Category toggle ──────────────────────────────────────────────── */
function toggleAllCats() {
  allCatsOn = !allCatsOn;
  document.querySelectorAll('.cat-check').forEach(function(c){ c.checked = allCatsOn; });
  applyFilters();
}

/* ── Apply all filters ────────────────────────────────────────────── */
function applyFilters() {
  var q      = (document.getElementById('map-search').value || '').toLowerCase().trim();
  var minInt = parseInt(document.getElementById('int-slider').value, 10);
  var cats   = Array.from(document.querySelectorAll('.cat-check:checked')).map(function(c){ return c.value; });

  // Remove existing conflict markers & heat
  markers.forEach(function(m){ map.removeLayer(m); });
  markers = [];
  if (heatLayer) { map.removeLayer(heatLayer); heatLayer = null; }
  // Remove existing post markers
  postMarkers.forEach(function(m){ map.removeLayer(m); });
  postMarkers = [];

  var catCounts = {};
  var visible   = [];

  mapData.forEach(function(d) {
    if (selCtry !== 'ALL' && d.country !== selCtry) return;
    if (cats.indexOf(d.category) === -1) return;
    if (d.intensity < minInt) return;
    if (q) {
      var haystack = d.name.toLowerCase() + ' ' + (CNAME[d.country] || '').toLowerCase() + ' ' + d.category.toLowerCase();
      if (haystack.indexOf(q) === -1) return;
    }
    visible.push(d);
    catCounts[d.category] = (catCounts[d.category] || 0) + 1;
  });

  // Update category counts in sidebar
  document.querySelectorAll('.cat-count').forEach(function(el) {
    var n = catCounts[el.dataset.cat] || 0;
    el.textContent = n > 0 ? n : '';
  });

  // Render conflict markers or heatmap
  if (viewMode === 'markers') {
    visible.forEach(function(d) {
      var m = L.marker([d.lat, d.lng], { icon: makeIcon(d) })
        .bindTooltip(esc(d.name) + ' &bull; ' + d.intensity + '/10', { direction:'right', opacity:1 });
      m.on('click', function() { openDetail(d); });
      m.addTo(map);
      markers.push(m);
    });
  } else {
    if (typeof L.heatLayer !== 'function') {
      /* leaflet-heat plugin not loaded — fall back to marker view */
      viewMode = 'markers';
      document.getElementById('mode-markers').click();
      return;
    }
    var pts = visible.map(function(d){ return [d.lat, d.lng, d.intensity/10]; });
    if (pts.length) {
      heatLayer = L.heatLayer(pts, {
        radius: 38, blur: 28, maxZoom: 9,
        gradient: { '0.0':'#F4C87E', '0.42':'#E7952A', '0.72':'#ED1C24', '1.0':'#750B25' }
      }).addTo(map);
    }
  }

  // Render post pins (always on top of heat or markers layer)
  var visiblePosts = 0;
  if (showReports && postData && postData.length) {
    postData.forEach(function(d) {
      // Country filter: map code → name for comparison
      if (selCtry !== 'ALL' && CNAME[selCtry] !== d.country) return;
      // Category filter
      if (cats.indexOf(d.category) === -1) return;
      // Search filter
      if (q) {
        var haystack = d.title.toLowerCase() + ' ' + d.country.toLowerCase() + ' ' + (d.region || '').toLowerCase() + ' ' + d.category.toLowerCase() + ' ' + d.type.toLowerCase();
        if (haystack.indexOf(q) === -1) return;
      }
      visiblePosts++;
      var m = L.marker([d.lat, d.lng], {
        icon: makePostIcon(d),
        zIndexOffset: 500
      }).bindTooltip(
        '<strong>' + esc(d.title) + '</strong><br><small>' + esc(d.type) + ' · ' + esc(d.country) + '</small>',
        { direction: 'right', opacity: 1 }
      );
      m.on('click', function() { openPostDetail(d); });
      m.addTo(map);
      postMarkers.push(m);
    });
  }

  // Active count pill
  var critical = visible.filter(function(d){ return d.intensity >= 8; }).length;
  var pill = visible.length + ' location' + (visible.length !== 1 ? 's' : '') + ' · ' + critical + ' critical';
  if (visiblePosts > 0) pill += ' · ' + visiblePosts + ' report' + (visiblePosts !== 1 ? 's' : '');
  document.getElementById('active-count').textContent = pill;

  // KPI hotspots (always from full dataset)
  var allHot = mapData.filter(function(d){ return d.intensity >= 8; }).length;
  var kpiEl  = document.getElementById('kpi-hotspots');
  if (kpiEl) kpiEl.textContent = allHot;

  // Table
  renderTable(visible);
}

/* ── Render flashpoints table ─────────────────────────────────────── */
function renderTable(data) {
  var sorted = data.slice().sort(function(a,b){ return b.intensity - a.intensity; }).slice(0, 15);
  var tbody  = document.getElementById('hotspot-tbody');

  if (!sorted.length) {
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-10 text-slate-300 text-sm">No locations match the current filters.</td></tr>';
    return;
  }

  tbody.innerHTML = sorted.map(function(d, i) {
    var rank = i + 1;
    var col  = d.intensity > 7 ? '#ED1C24' : d.intensity > 4 ? '#E7952A' : '#94a3b8';
    var catC = CAT_COLORS[d.category] || '#94a3b8';
    var bg   = i % 2 === 0 ? '#fff' : '#fafafa';
    var rankDisplay = rank <= 3
      ? '<span style="font-family:Outfit,sans-serif;font-weight:900;font-size:14px;color:'
        + (rank===1?'#ED1C24':rank===2?'#E7952A':'#94a3b8') + '">' + rank + '</span>'
      : '<span style="font-size:11px;color:#cbd5e1;font-weight:600">' + rank + '</span>';

    return '<tr style="background:'+bg+';border-bottom:1px solid #f1f5f9;cursor:pointer;transition:background .1s"'
      + ' onmouseover="this.style.background=\'#fef3f2\'" onmouseout="this.style.background=\''+bg+'\'"'
      + ' onclick="zoomTo('+d.lat+','+d.lng+')">'
      + '<td class="px-4 py-3">' + rankDisplay + '</td>'
      + '<td class="px-4 py-3"><span style="font-weight:600;color:#0f172a">' + esc(d.name) + '</span></td>'
      + '<td class="px-4 py-3 hidden md:table-cell"><span style="color:#64748b;font-size:12px">'
      + FLAG[d.country] + ' ' + esc(CNAME[d.country]) + '</span></td>'
      + '<td class="px-4 py-3 hidden sm:table-cell">'
      + '<span style="background:'+catC+'18;color:'+catC+';border:1px solid '+catC+'35;border-radius:20px;padding:2px 9px;font-size:10px;font-weight:700;white-space:nowrap">'
      + esc(d.category) + '</span></td>'
      + '<td class="px-4 py-3">'
      + '<div style="display:flex;align-items:center;gap:8px">'
      + '<div style="flex:1;height:5px;background:#e2e8f0;border-radius:3px;overflow:hidden">'
      + '<div style="width:'+(d.intensity*10)+'%;height:100%;background:'+col+';border-radius:3px"></div></div>'
      + '<span style="font-size:12px;font-weight:700;color:'+col+';width:18px;text-align:right">'+d.intensity+'</span>'
      + '</div></td>'
      + '</tr>';
  }).join('');
}

/* ── View mode toggle ─────────────────────────────────────────────── */
function setMode(mode) {
  viewMode = mode;
  document.querySelectorAll('.mode-btn').forEach(function(b) {
    var on = b.id === 'mode-' + mode;
    b.style.background   = on ? 'rgba(231,149,42,.22)' : 'transparent';
    b.style.borderColor  = on ? 'rgba(231,149,42,.35)' : 'transparent';
    b.style.color        = on ? '#fff' : 'rgba(255,255,255,.38)';
  });
  applyFilters();
}

/* ── Zoom to location (from table) ───────────────────────────────── */
function zoomTo(lat, lng) {
  map.setView([lat, lng], 8, { animate:true, duration:0.7 });
  var d = mapData.find(function(p){ return p.lat === lat && p.lng === lng; });
  if (d) openDetail(d);
}

/* ── Reset view ───────────────────────────────────────────────────── */
function resetView() {
  map.setView([5, 22], 4, { animate:true, duration:0.7 });
}

/* ── Fullscreen ───────────────────────────────────────────────────── */
function toggleFullscreen() {
  var el = document.getElementById('map-wrapper');
  if (!document.fullscreenElement) {
    el.requestFullscreen && el.requestFullscreen();
    el.style.height = '100vh';
  } else {
    document.exitFullscreen && document.exitFullscreen();
    el.style.height = '620px';
  }
  setTimeout(function(){ map.invalidateSize(); }, 350);
}
document.addEventListener('fullscreenchange', function() {
  if (!document.fullscreenElement) {
    document.getElementById('map-wrapper').style.height = '620px';
    setTimeout(function(){ map.invalidateSize(); }, 100);
  }
});

/* ── Detail panel ─────────────────────────────────────────────────── */
function openDetail(d) {
  var col   = d.intensity > 7 ? '#ED1C24' : d.intensity > 4 ? '#E7952A' : '#64748b';
  var label = d.intensity > 7 ? 'Critical Alert' : d.intensity > 4 ? 'Elevated Risk' : 'Emerging Concern';
  var catC  = CAT_COLORS[d.category] || '#64748b';

  if (window.innerWidth < 768) {
    document.getElementById('ctrl-panel').classList.remove('ctrl-open');
  }

  document.getElementById('detail-content').innerHTML =
    '<div style="height:4px;background:'+col+'"></div>'
    + '<div style="padding:18px 18px 24px">'
    + '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">'
    + '<span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:'+col+'">'
    + esc(label)+'</span>'
    + '<button onclick="closeDetail()" style="width:28px;height:28px;border-radius:50%;border:none;'
    + 'background:#f1f5f9;color:#64748b;cursor:pointer;font-size:18px;line-height:1;'
    + 'display:flex;align-items:center;justify-content:center">&times;</button>'
    + '</div>'
    + '<div style="font-size:26px;margin-bottom:4px">'+(FLAG[d.country]||'')+'</div>'
    + '<h3 style="font-family:Outfit,sans-serif;font-weight:900;font-size:21px;color:#0f172a;margin:0 0 3px">'
    + esc(d.name)+'</h3>'
    + '<p style="font-size:12px;color:#94a3b8;margin:0 0 14px;font-weight:600">'
    + esc(CNAME[d.country]||d.country)+'</p>'
    + '<span style="background:'+catC+'18;color:'+catC+';border:1px solid '+catC+'38;border-radius:20px;'
    + 'padding:4px 13px;font-size:11px;font-weight:700">'+esc(d.category)+'</span>'
    + '<div style="margin-top:22px">'
    + '<div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:7px">'
    + '<span style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.07em">Intensity Score</span>'
    + '<span style="font-size:18px;font-weight:900;color:'+col+'">'+d.intensity
    + '<span style="font-size:11px;font-weight:500;color:#cbd5e1">&thinsp;/&thinsp;10</span></span>'
    + '</div>'
    + '<div style="height:9px;background:#e2e8f0;border-radius:5px;overflow:hidden">'
    + '<div style="width:'+(d.intensity*10)+'%;height:100%;background:'+col
    + ';border-radius:5px;transition:width .6s cubic-bezier(.4,0,.2,1)"></div>'
    + '</div>'
    + '</div>'
    + '<div style="margin-top:18px;padding:14px 15px;background:#f8fafc;border-radius:12px;border-left:3px solid '+col+'">'
    + '<p style="font-size:13px;color:#374151;line-height:1.7;margin:0">'+esc(d.desc)+'</p>'
    + '</div>'
    + '<a href="/news?q='+encodeURIComponent(d.name)+'"'
    + ' style="display:flex;align-items:center;gap:7px;margin-top:18px;text-decoration:none;'
    + 'padding:12px 16px;border-radius:12px;background:#750B25;color:#fff;'
    + 'font-size:13px;font-weight:700;justify-content:center">'
    + '<svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">'
    + '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>'
    + '<polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>'
    + '</svg>View Related Reports</a>'
    + '</div>';

  document.getElementById('detail-panel').classList.add('panel-open');
  document.getElementById('detail-panel').classList.remove('panel-hidden');
}

function closeDetail() {
  document.getElementById('detail-panel').classList.remove('panel-open');
  document.getElementById('detail-panel').classList.add('panel-hidden');
}

/* ── Post detail panel ────────────────────────────────────────────── */
function openPostDetail(d) {
  var TYPE_LABEL  = {ARTICLE:'Article', PODCAST:'Podcast', VIDEO:'Video', DOCUMENT:'Document', GALLERY_IMAGE:'Photo Gallery'};
  var TYPE_ACTION = {ARTICLE:'Read Article', PODCAST:'Listen to Episode', VIDEO:'Watch Video', DOCUMENT:'View Document', GALLERY_IMAGE:'View Gallery'};
  var col    = POST_TYPE_COLORS[d.type] || '#750B25';
  var label  = TYPE_LABEL[d.type]  || 'Report';
  var action = TYPE_ACTION[d.type] || 'Read More';
  var catC   = CAT_COLORS[d.category] || '#64748b';

  if (window.innerWidth < 768) {
    document.getElementById('ctrl-panel').classList.remove('ctrl-open');
  }

  document.getElementById('detail-content').innerHTML =
    '<div style="height:4px;background:'+col+'"></div>'
    + '<div style="padding:18px 18px 28px">'
    + '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">'
    + '<span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:'+col+'">'
    + esc(label)+'</span>'
    + '<button onclick="closeDetail()" style="width:28px;height:28px;border-radius:50%;border:none;'
    + 'background:#f1f5f9;color:#64748b;cursor:pointer;font-size:18px;line-height:1;'
    + 'display:flex;align-items:center;justify-content:center">&times;</button>'
    + '</div>'
    + (d.thumb
       ? '<img src="'+esc(d.thumb)+'" style="width:100%;height:120px;object-fit:cover;border-radius:10px;margin-bottom:14px" onerror="this.style.display=\'none\'">'
       : '')
    + '<h3 style="font-family:Outfit,sans-serif;font-weight:900;font-size:18px;color:#0f172a;margin:0 0 10px;line-height:1.35">'
    + esc(d.title)+'</h3>'
    + '<div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px">'
    + '<span style="background:#f1f5f9;color:#64748b;border-radius:20px;padding:3px 10px;font-size:10px;font-weight:700">'
    + '📍 '+esc(d.country)+(d.region ? ', '+esc(d.region) : '')+'</span>'
    + '<span style="background:'+catC+'18;color:'+catC+';border:1px solid '+catC+'38;border-radius:20px;padding:3px 10px;font-size:10px;font-weight:700">'
    + esc(d.category)+'</span>'
    + '</div>'
    + (d.desc
       ? '<div style="margin-bottom:14px;padding:12px 14px;background:#f8fafc;border-radius:10px;border-left:3px solid '+col+'">'
         + '<p style="font-size:13px;color:#374151;line-height:1.7;margin:0">'+esc(d.desc)+(d.desc.length >= 150 ? '…' : '')+'</p>'
         + '</div>'
       : '')
    + '<p style="font-size:11px;color:#94a3b8;margin:0 0 18px">Published '+esc(d.date)+'</p>'
    + '<a href="'+esc(d.url)+'"'
    + ' style="display:flex;align-items:center;gap:7px;text-decoration:none;'
    + 'padding:12px 16px;border-radius:12px;background:'+col+';color:#fff;'
    + 'font-size:13px;font-weight:700;justify-content:center">'
    + '<svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">'
    + '<path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>'
    + '</svg>'+action+'</a>'
    + '</div>';

  document.getElementById('detail-panel').classList.add('panel-open');
  document.getElementById('detail-panel').classList.remove('panel-hidden');
}

/* ── Keyboard shortcuts ───────────────────────────────────────────── */
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') { closeDetail(); _ctrlClose(); }
});

/* ── Ctrl-panel open/close (with mobile backdrop) ─────────────────── */
function _ctrlOpen() {
  document.getElementById('ctrl-panel').classList.add('ctrl-open');
  if (window.innerWidth < 768) {
    document.getElementById('ctrl-backdrop').style.display = 'block';
  }
}
function _ctrlClose() {
  document.getElementById('ctrl-panel').classList.remove('ctrl-open');
  document.getElementById('ctrl-backdrop').style.display = 'none';
}

/* ── Init ─────────────────────────────────────────────────────────── */
try {
  setCountry('ALL');
  applyFilters();
} catch (e) {
  console.error('[Heatmap] Init failed:', e);
  document.getElementById('active-count').textContent = 'Map error — check console';
}
</script>

</body>
</html>
