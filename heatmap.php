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
    // The remaining countries below are present so every option in the content-creation
    // country dropdown (see african_countries()) has a map coordinate — otherwise a
    // published post tagged with one of these is silently dropped from the heatmap.
    ['name'=>'Benin',                    'code'=>'BJ','flag'=>'🇧🇯','lat'=>9.307,  'lng'=>2.315],
    ['name'=>'Botswana',                 'code'=>'BW','flag'=>'🇧🇼','lat'=>-22.328,'lng'=>24.685],
    ['name'=>'Cabo Verde',               'code'=>'CV','flag'=>'🇨🇻','lat'=>16.539, 'lng'=>-23.042],
    ['name'=>'Comoros',                  'code'=>'KM','flag'=>'🇰🇲','lat'=>-11.645,'lng'=>43.333],
    ['name'=>'Congo',                    'code'=>'CG','flag'=>'🇨🇬','lat'=>-0.228, 'lng'=>15.827],
    ['name'=>'Djibouti',                 'code'=>'DJ','flag'=>'🇩🇯','lat'=>11.825, 'lng'=>42.590],
    ['name'=>'Equatorial Guinea',        'code'=>'GQ','flag'=>'🇬🇶','lat'=>1.651,  'lng'=>10.268],
    ['name'=>'Eritrea',                  'code'=>'ER','flag'=>'🇪🇷','lat'=>15.179, 'lng'=>39.782],
    ['name'=>'Eswatini',                 'code'=>'SZ','flag'=>'🇸🇿','lat'=>-26.523,'lng'=>31.466],
    ['name'=>'Gabon',                    'code'=>'GA','flag'=>'🇬🇦','lat'=>-0.804, 'lng'=>11.609],
    ['name'=>'Gambia',                   'code'=>'GM','flag'=>'🇬🇲','lat'=>13.443, 'lng'=>-15.310],
    ['name'=>'Guinea-Bissau',            'code'=>'GW','flag'=>'🇬🇼','lat'=>11.804, 'lng'=>-15.180],
    ['name'=>'Ivory Coast',              'code'=>'CI','flag'=>'🇨🇮','lat'=>7.540,  'lng'=>-5.547],
    ['name'=>'Lesotho',                  'code'=>'LS','flag'=>'🇱🇸','lat'=>-29.610,'lng'=>28.234],
    ['name'=>'Libya',                    'code'=>'LY','flag'=>'🇱🇾','lat'=>26.335, 'lng'=>17.228],
    ['name'=>'Mauritania',               'code'=>'MR','flag'=>'🇲🇷','lat'=>21.008, 'lng'=>-10.941],
    ['name'=>'Mauritius',                'code'=>'MU','flag'=>'🇲🇺','lat'=>-20.348,'lng'=>57.552],
    ['name'=>'Namibia',                  'code'=>'NA','flag'=>'🇳🇦','lat'=>-22.958,'lng'=>18.490],
    ['name'=>'São Tomé and Príncipe',    'code'=>'ST','flag'=>'🇸🇹','lat'=>0.186,  'lng'=>6.613],
    ['name'=>'Togo',                     'code'=>'TG','flag'=>'🇹🇬','lat'=>8.620,  'lng'=>0.825],
    ['name'=>'Tunisia',                  'code'=>'TN','flag'=>'🇹🇳','lat'=>33.887, 'lng'=>9.538],
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
    $_cleanCats = [];
    foreach ($_dbCategories as $_cat) {
        if (!is_array($_cat)) continue;
        $_n = trim((string)($_cat['name']  ?? ''));
        $_c = trim((string)($_cat['color'] ?? ''));
        if ($_n === '') continue;
        $_cleanCats[$_n] = $_c !== '' ? $_c : '#94a3b8';
    }
    // Keep the built-in defaults if the stored setting yields nothing usable
    if ($_cleanCats) $cats = $_cleanCats;
}

$postTypeUrl = [
    'ARTICLE'       => '/news/',
    'PODCAST'       => '/podcasts/',
    'VIDEO'         => '/videos/',
    'DOCUMENT'      => '/documents/',
    'GALLERY_IMAGE' => '/gallery',
    'POLICY_BRIEF'  => '/policy-briefs/',
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

        // issueCategory can hold a comma-separated list from the multi-select,
        // so map each entry individually — a whole-string lookup would miss and
        // silently colour every multi-category post as 'Policy'.
        $rawIssues  = array_filter(array_map('trim', explode(',', (string)($p['issueCategory'] ?? ''))));
        $mappedCats = [];
        foreach ($rawIssues as $ri) {
            // Unmapped names are kept as-is: custom Heatmap Config categories
            // are already heatmap-category names.
            $mc = $issueToCat[$ri] ?? $ri;
            if ($mc !== '' && !in_array($mc, $mappedCats, true)) $mappedCats[] = $mc;
        }
        if (!$mappedCats) $mappedCats = ['Policy'];

        $postMarkers[] = [
            'id'       => $p['id'],
            'title'    => $p['title'],
            'type'     => $p['type'] ?? 'ARTICLE',
            'desc'     => mb_substr($p['description'] ?? '', 0, 150),
            'country'  => $p['country'],
            'region'   => $region,
            'category'   => $mappedCats[0],
            'categories' => $mappedCats,
            'date'     => format_date($p['createdAt']),
            'dateRaw'  => date('Y-m-d', strtotime($p['createdAt'])),
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

// ── Per-country DB stats — powering the hover summary panels ──────────
$countryStats = [];
try {
    $csRows = db()->query(
        "SELECT country,
                COUNT(*) AS reportCount,
                MAX(createdAt) AS latestDate,
                GROUP_CONCAT(DISTINCT issueCategory ORDER BY issueCategory SEPARATOR '|||') AS allCats
         FROM Post
         WHERE status='PUBLISHED' AND country IS NOT NULL AND country <> ''
         GROUP BY country"
    )->fetchAll();
    foreach ($csRows as $cr) {
        // NOTE: must not be named $cats — that holds the category→colour map
        $_ccats = [];
        foreach (explode('|||', $cr['allCats'] ?? '') as $cs) {
            foreach (explode(',', $cs) as $c) {
                $c = trim($c);
                if ($c !== '') $_ccats[] = $c;
            }
        }
        $countryStats[$cr['country']] = [
            'reports'    => (int)$cr['reportCount'],
            'latestDate' => $cr['latestDate'] ? date('Y-m-d', strtotime($cr['latestDate'])) : null,
            'latestFmt'  => $cr['latestDate'] ? format_date($cr['latestDate']) : null,
            'cats'       => array_values(array_unique($_ccats)),
        ];
    }
} catch (\Exception $e) {}

$pageTitle = 'Conflict Monitoring Dashboard | Tafakari Digital Hub';
$pageDesc  = 'Real-time conflict intensity mapping and issue tracking across African nations experiencing fragility, displacement, and humanitarian crises.';
$extraHead = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">';
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
.leaflet-container { background: #eceae5; }

/* ── Leaflet tooltip overrides ────────────────────────────────────── */
.leaflet-tooltip {
  background: #ffffff !important;
  color: #0f172a !important;
  border: 1px solid #e2e8f0 !important;
  border-radius: 8px !important;
  font-family: 'Inter', sans-serif !important;
  font-size: 11px !important;
  font-weight: 600 !important;
  padding: 5px 10px !important;
  box-shadow: 0 4px 16px rgba(15,23,42,.15) !important;
  white-space: nowrap !important;
}
.leaflet-tooltip::before,
.leaflet-tooltip-left::before,
.leaflet-tooltip-right::before { display: none !important; }

/* ── Attribution ──────────────────────────────────────────────────── */
.leaflet-control-attribution {
  background: rgba(255,255,255,.85) !important;
  color: #64748b !important;
  font-size: 9px !important;
}
.leaflet-control-attribution a { color: #750B25 !important; }

/* ── Zoom control ─────────────────────────────────────────────────── */
.leaflet-control-zoom a {
  background: #ffffff !important;
  color: #475569 !important;
  border-color: #e2e8f0 !important;
}
.leaflet-control-zoom a:hover { background: #F8F8F0 !important; color: #750B25 !important; }

/* ── Control panel scrollbar ──────────────────────────────────────── */
#ctrl-panel {
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 transparent;
}
#ctrl-panel::-webkit-scrollbar { width: 3px; }
#ctrl-panel::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 2px; }

/* ── Search input placeholder ─────────────────────────────────────── */
#map-search::placeholder { color: #94a3b8; }

/* ── Category checkboxes ──────────────────────────────────────────── */
.cat-check { display: none; }
.cat-label {
  display: flex; align-items: center; gap: 8px; cursor: pointer;
  padding: 5px 7px; border-radius: 8px; transition: background .15s;
}
.cat-label:hover { background: #f1f5f9; }
.cat-dot {
  width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0;
  border: 2px solid transparent; transition: border-color .15s;
}
.cat-check:checked + .cat-label .cat-dot { border-color: rgba(15,23,42,.4); }
.cat-check:not(:checked) + .cat-label { opacity: .42; }

/* ── Intensity slider ─────────────────────────────────────────────── */
.int-slider {
  -webkit-appearance: none; width: 100%; height: 4px;
  border-radius: 2px; outline: none; cursor: pointer;
}
.int-slider::-webkit-slider-thumb {
  -webkit-appearance: none; width: 15px; height: 15px; border-radius: 50%;
  background: #E7952A; border: 2px solid #fff;
  box-shadow: 0 1px 4px rgba(15,23,42,.3); cursor: pointer;
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
  background: rgba(15,23,42,.38); z-index: 999;
  display: none;
  backdrop-filter: blur(1px);
}

/* ── Country hover tooltip (floating card) ─────────────────────────── */
#ctry-hover-card {
  position: absolute; z-index: 1200; pointer-events: none;
  background: rgba(255,255,255,.97); border: 1px solid #e2e8f0;
  border-radius: 14px; padding: 12px 14px; min-width: 190px; max-width: 240px;
  backdrop-filter: blur(10px); box-shadow: 0 8px 32px rgba(15,23,42,.18);
  transition: opacity .15s; font-family: 'Inter', sans-serif;
}
#ctry-hover-card.hidden { opacity: 0; }

/* ── Choropleth country polygon hover ─────────────────────────────── */
.leaflet-interactive:focus { outline: none; }

/* ── Date filter inputs ───────────────────────────────────────────── */
.date-inp {
  width: 100%; padding: 7px 10px; border-radius: 10px;
  border: 1px solid #e2e8f0; background: #f8fafc;
  color: #0f172a; font-size: 11px; outline: none; transition: border-color .15s;
  color-scheme: light;
}
.date-inp:focus { border-color: #E7952A; }

/* ── Intensity legend tiers ───────────────────────────────────────── */
.int-tier { display:flex; align-items:center; gap:10px; padding:4px 6px; border-radius:8px; transition:background .12s; }
.int-tier:hover { background:#f1f5f9; }

/* ── Country panel stats grid ─────────────────────────────────────── */
.cstat { background:#f8fafc; border-radius:10px; padding:9px 11px; text-align:center; }
.cstat-lbl { font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#94a3b8; margin:0 0 3px; }
.cstat-val { font-size:14px; font-weight:900; margin:0; }

/* ── Detail panel wider + smooth scroll ─────────────────────────────── */
#detail-panel { transition: transform .3s cubic-bezier(.4,0,.2,1); }

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
          [format_number($totalReports),        'Total Reports',    '#E7952A', 'totalReports'],
          [format_number($monthlyReports),       'This Month',       '#E7952A', 'thisMonth'],
          [format_number(count($countryStats)),  'Active Hotspots',  '#ED1C24', 'activeHotspots'],
          [format_number(count($_countriesData)),'Countries',        '#E7952A', 'countriesKpi'],
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
            class="absolute top-3 left-3 z-[1100] items-center gap-2 px-3 py-2.5 rounded-xl text-xs font-bold"
            style="background:#ffffff;color:#750B25;border:1px solid #e2e8f0;box-shadow:0 2px 10px rgba(15,23,42,.12);min-height:40px">
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
         style="width:248px;background:rgba(255,255,255,.96);backdrop-filter:blur(12px);border-right:1px solid #e2e8f0">

      <!-- Mobile-only header with close button -->
      <div class="md:hidden flex items-center justify-between px-4 py-3.5" style="border-bottom:1px solid #e2e8f0;flex-shrink:0">
        <span class="text-xs font-black uppercase tracking-[.12em]" style="color:#750B25" data-i18n="heatmapPage.mapFilters">Map Filters</span>
        <button onclick="_ctrlClose()"
                style="width:32px;height:32px;border-radius:50%;border:none;background:#f1f5f9;color:#475569;font-size:20px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center">&times;</button>
      </div>

      <div class="p-4 space-y-5">

        <!-- Search -->
        <div>
          <p class="text-[10px] font-black uppercase tracking-[.12em] mb-1.5" style="color:#750B25" data-i18n="heatmapPage.searchLocations">Search Locations</p>
          <div class="relative">
            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 pointer-events-none" style="color:#94a3b8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
              <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input id="map-search" type="search" placeholder="Location, country…" oninput="applyFilters()"
                   data-i18n-placeholder="heatmapPage.searchPlaceholder"
                   class="w-full text-xs rounded-xl border pl-8 pr-3 py-2.5 focus:outline-none"
                   style="background:#f8fafc;border-color:#e2e8f0;color:#0f172a">
          </div>
        </div>

        <!-- View mode -->
        <div>
          <p class="text-[10px] font-black uppercase tracking-[.12em] mb-1.5" style="color:#750B25" data-i18n="heatmapPage.viewMode">View Mode</p>
          <div class="grid grid-cols-3 gap-1 p-1 rounded-xl" style="background:#f1f5f9">
            <button id="mode-markers" onclick="setMode('markers')"
                    class="mode-btn text-[10px] font-bold py-2 rounded-lg transition-all"
                    style="color:#750B25;background:#ffffff;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(15,23,42,.08)">
              ● <span data-i18n="heatmapPage.markers">Markers</span>
            </button>
            <button id="mode-heat" onclick="setMode('heat')"
                    class="mode-btn text-[10px] font-bold py-2 rounded-lg transition-all"
                    style="color:#64748b;background:transparent;border:1px solid transparent">
              ◈ <span data-i18n="heatmapPage.heatmapMode">Heat</span>
            </button>
            <button id="mode-countries" onclick="setMode('countries')"
                    class="mode-btn text-[10px] font-bold py-2 rounded-lg transition-all"
                    style="color:#64748b;background:transparent;border:1px solid transparent">
              ⬡ Countries
            </button>
          </div>
        </div>

        <!-- Country filter -->
        <div>
          <div class="flex items-center justify-between mb-1.5">
            <p class="text-[10px] font-black uppercase tracking-[.12em]" style="color:#750B25" data-i18n="heatmapPage.country">Country</p>
            <button onclick="setCountry('ALL')" class="text-[10px] font-bold" style="color:rgba(117,11,37,.62)" data-i18n="heatmapPage.reset">Reset</button>
          </div>
          <div class="space-y-px max-h-44 overflow-y-auto pr-0.5" style="scrollbar-width:thin;scrollbar-color:#cbd5e1 transparent">
            <?php foreach ($ctryList as $code => [$name, $flag]): ?>
              <button onclick="setCountry('<?= $code ?>')" data-ctry="<?= $code ?>"
                      class="ctry-btn w-full flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-left transition-all"
                      style="color:#475569">
                <span style="font-size:14px;line-height:1"><?= $flag ?></span>
                <span class="text-xs font-medium" <?= $code === 'ALL' ? 'data-i18n="heatmapPage.allCountries"' : '' ?>><?= h($name) ?></span>
              </button>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Category filter -->
        <div>
          <div class="flex items-center justify-between mb-1.5">
            <p class="text-[10px] font-black uppercase tracking-[.12em]" style="color:#750B25" data-i18n="heatmapPage.category">Category</p>
            <button onclick="toggleAllCats()" class="text-[10px] font-bold" style="color:rgba(117,11,37,.62)" data-i18n="heatmapPage.toggleAll">Toggle All</button>
          </div>
          <?php foreach ($cats as $cat => $col):
            // Cast: PHP turns numeric category names into int keys, which
            // would fatal under strict_types in str_replace()/h()
            $cat = (string)$cat;
            $col = (string)$col;
            $id  = 'cat-' . str_replace(' ', '_', $cat); ?>
            <div>
              <input type="checkbox" class="cat-check" id="<?= $id ?>" value="<?= h($cat) ?>" checked onchange="applyFilters()">
              <label for="<?= $id ?>" class="cat-label">
                <span class="cat-dot" style="background:<?= $col ?>"></span>
                <span class="text-[11px]" style="color:#334155"><?= h($cat) ?></span>
                <span class="text-[10px] font-bold ml-auto cat-count" data-cat="<?= h($cat) ?>" style="color:#94a3b8"></span>
              </label>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Intensity slider -->
        <div>
          <div class="flex items-center justify-between mb-1.5">
            <p class="text-[10px] font-black uppercase tracking-[.12em]" style="color:#750B25" data-i18n="heatmapPage.minIntensity">Min Intensity</p>
            <span id="int-val" class="text-xs font-black" style="color:#E7952A">1</span>
          </div>
          <input type="range" class="int-slider" id="int-slider" min="1" max="10" value="1"
                 oninput="updateSlider();applyFilters()"
                 style="background:linear-gradient(to right,#E7952A 0%,#e2e8f0 0%)">
          <div class="flex justify-between mt-1">
            <span class="text-[9px]" style="color:#94a3b8" data-i18n="heatmapPage.low">Low</span>
            <span class="text-[9px]" style="color:#94a3b8" data-i18n="heatmapPage.critical">Critical</span>
          </div>
        </div>

        <!-- Date range filter -->
        <div>
          <p class="text-[10px] font-black uppercase tracking-[.12em] mb-1.5" style="color:#750B25">Date Range (Reports)</p>
          <div class="space-y-1.5">
            <div>
              <label class="text-[9px]" style="color:#64748b">From</label>
              <input type="date" id="date-from" class="date-inp" oninput="applyFilters()">
            </div>
            <div>
              <label class="text-[9px]" style="color:#64748b">To</label>
              <input type="date" id="date-to" class="date-inp" oninput="applyFilters()">
            </div>
          </div>
          <button onclick="document.getElementById('date-from').value='';document.getElementById('date-to').value='';applyFilters()"
                  class="mt-1.5 text-[9px] font-bold" style="color:rgba(117,11,37,.62)">Clear dates</button>
        </div>

        <!-- Intensity legend -->
        <div>
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
            <p class="text-[10px] font-black uppercase tracking-[.12em]" style="color:#750B25">Intensity Legend</p>
            <div style="display:flex;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0">
              <button id="leg-tab-esc" onclick="setLegTab('escalating')"
                      style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;padding:4px 8px;border:none;cursor:pointer;background:rgba(237,28,36,.12);color:#ED1C24;line-height:1;white-space:nowrap">
                ↑ Escalating
              </button>
              <button id="leg-tab-desc" onclick="setLegTab('deescalating')"
                      style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;padding:4px 8px;border:none;cursor:pointer;background:transparent;color:#94a3b8;line-height:1;white-space:nowrap">
                ↓ De-escalating
              </button>
            </div>
          </div>
          <!-- Escalating legend (5-tier) -->
          <div id="legend-escalating" class="space-y-1">
            <div class="int-tier">
              <div style="width:14px;height:14px;border-radius:50%;background:#ED1C24;box-shadow:0 0 8px #ED1C2499;flex-shrink:0"></div>
              <div>
                <p style="font-size:10px;font-weight:700;color:#ED1C24;margin:0">Very High</p>
                <p style="font-size:9px;color:#64748b;margin:0">9–10 · Active conflict</p>
              </div>
            </div>
            <div class="int-tier">
              <div style="width:12px;height:12px;border-radius:50%;background:#E7952A;box-shadow:0 0 6px #E7952A77;flex-shrink:0;margin-left:1px"></div>
              <div>
                <p style="font-size:10px;font-weight:700;color:#E7952A;margin:0">High</p>
                <p style="font-size:9px;color:#64748b;margin:0">7–8 · Elevated risk</p>
              </div>
            </div>
            <div class="int-tier">
              <div style="width:10px;height:10px;border-radius:50%;background:#F59E0B;flex-shrink:0;margin-left:2px"></div>
              <div>
                <p style="font-size:10px;font-weight:700;color:#F59E0B;margin:0">Moderate</p>
                <p style="font-size:9px;color:#64748b;margin:0">5–6 · Significant concern</p>
              </div>
            </div>
            <div class="int-tier">
              <div style="width:8px;height:8px;border-radius:50%;background:#65A30D;flex-shrink:0;margin-left:3px"></div>
              <div>
                <p style="font-size:10px;font-weight:700;color:#65A30D;margin:0">Low</p>
                <p style="font-size:9px;color:#64748b;margin:0">2–4 · Emerging situation</p>
              </div>
            </div>
            <div class="int-tier">
              <div style="width:8px;height:8px;border-radius:50%;background:#059669;flex-shrink:0;margin-left:3px"></div>
              <div>
                <p style="font-size:10px;font-weight:700;color:#059669;margin:0">Stable / Peaceful</p>
                <p style="font-size:9px;color:#64748b;margin:0">1 · Minimal risk</p>
              </div>
            </div>
          </div>
          <!-- De-escalating legend (hidden by default) -->
          <div id="legend-deescalating" style="display:none" class="space-y-2">
            <div class="flex items-center gap-2.5">
              <div style="width:16px;height:16px;border-radius:50%;background:#10B981;box-shadow:0 0 8px #10B98188;flex-shrink:0;display:flex;align-items:center;justify-content:center">
                <svg width="8" height="8" viewBox="0 0 12 12" fill="none"><path d="M6 2v8M3 7l3 3 3-3" stroke="rgba(0,0,0,.7)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </div>
              <span class="text-[11px]" style="color:#334155">Major improvement</span>
            </div>
            <div class="flex items-center gap-2.5">
              <div style="width:13px;height:13px;border-radius:50%;background:#34D399;flex-shrink:0;margin-left:1.5px;display:flex;align-items:center;justify-content:center">
                <svg width="7" height="7" viewBox="0 0 12 12" fill="none"><path d="M6 2v8M3 7l3 3 3-3" stroke="rgba(0,0,0,.7)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </div>
              <span class="text-[11px]" style="color:#334155">Moderate progress</span>
            </div>
            <div class="flex items-center gap-2.5">
              <div style="width:9px;height:9px;border-radius:50%;background:#6EE7B7;flex-shrink:0;margin-left:3.5px"></div>
              <span class="text-[11px]" style="color:#334155">Early / fragile improvement</span>
            </div>
          </div>
        </div>

        <!-- Published Reports toggle -->
        <div style="border-top:1px solid #e2e8f0;padding-top:16px">
          <div class="flex items-center justify-between mb-2">
            <p class="text-[10px] font-black uppercase tracking-[.12em]" style="color:rgba(99,102,241,.85)" data-i18n="heatmapPage.publishedReports">Published Reports</p>
            <button id="reports-toggle-btn" onclick="toggleReports()"
                    class="text-[10px] font-black px-2.5 py-1 rounded-lg transition-all"
                    style="background:rgba(99,102,241,.25);color:#818CF8;border:1px solid rgba(99,102,241,.35)">
              ON
            </button>
          </div>
          <p class="text-[10px] mb-3" style="color:#94a3b8"><?= count($postMarkers) ?> <span data-i18n="heatmapPage.reportsFromDb">reports from the database</span></p>
          <p class="text-[11px] mb-2.5" style="color:#475569;line-height:1.55">
            Each dot is one <strong>category</strong> in a country. Its colour is the
            category colour set in Heatmap Config; it <strong>grows</strong> with the
            number of published reports.
          </p>
          <!-- Size scale -->
          <div class="flex items-end gap-3 mb-2.5" style="padding:2px 2px 0">
            <?php foreach ([[15,'1'],[21,'5'],[29,'20+']] as [$sz,$lbl]): ?>
              <div class="flex flex-col items-center gap-1">
                <div style="width:<?= $sz ?>px;height:<?= $sz ?>px;border-radius:50%;background:#750B25;border:2px solid #fff;box-shadow:0 1px 5px rgba(15,23,42,.28)"></div>
                <span class="text-[9px]" style="color:#94a3b8"><?= $lbl ?></span>
              </div>
            <?php endforeach; ?>
            <span class="text-[9px] ml-0.5 mb-1" style="color:#94a3b8">reports</span>
          </div>
          <!-- Category colours currently configured -->
          <div class="flex flex-wrap gap-x-3 gap-y-1">
            <?php foreach ($cats as $cLbl => $cCol): $cLbl = (string)$cLbl; ?>
              <div class="flex items-center gap-1.5">
                <span style="width:9px;height:9px;border-radius:50%;background:<?= h((string)$cCol) ?>;flex-shrink:0;display:inline-block"></span>
                <span class="text-[10px]" style="color:#475569"><?= h($cLbl) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

      </div>
    </div><!-- /#ctrl-panel -->

    <!-- ── Map ───────────────────────────────────────────────────────── -->
    <div id="map" style="width:100%;height:100%;z-index:1"></div>

    <!-- ── Map toolbar (top-right) ──────────────────────────────────── -->
    <div class="absolute top-3 right-3 z-[1000] flex flex-col gap-2">
      <button onclick="resetView()" title="Reset map view" data-i18n-title="heatmapPage.resetMapView"
              class="w-9 h-9 flex items-center justify-center rounded-xl hover:opacity-80 transition-opacity"
              style="background:#ffffff;color:#475569;border:1px solid #e2e8f0;box-shadow:0 2px 8px rgba(15,23,42,.1)">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
          <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>
        </svg>
      </button>
      <button onclick="toggleFullscreen()" title="Toggle fullscreen" data-i18n-title="heatmapPage.toggleFullscreen"
              class="w-9 h-9 flex items-center justify-center rounded-xl hover:opacity-80 transition-opacity"
              style="background:#ffffff;color:#475569;border:1px solid #e2e8f0;box-shadow:0 2px 8px rgba(15,23,42,.1)">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
          <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
        </svg>
      </button>
    </div>

    <!-- ── Active count pill ─────────────────────────────────────────── -->
    <div class="absolute bottom-5 left-1/2 -translate-x-1/2 z-[1000]">
      <div class="flex items-center gap-2 px-4 py-2 rounded-full text-xs font-semibold"
           style="background:rgba(255,255,255,.95);color:#0f172a;border:1px solid #e2e8f0;box-shadow:0 2px 12px rgba(15,23,42,.12);backdrop-filter:blur(8px)">
        <span class="w-1.5 h-1.5 rounded-full animate-pulse" style="background:#10b981"></span>
        <span id="active-count">Loading…</span>
      </div>
    </div>

    <!-- ── Right detail panel ────────────────────────────────────────── -->
    <div id="detail-panel"
         class="absolute top-0 right-0 bottom-0 z-[1000] overflow-y-auto panel-hidden"
         style="width:340px;background:#fff;box-shadow:-4px 0 40px rgba(0,0,0,.18)">
      <div id="detail-content"></div>
    </div>

    <!-- ── Country hover card (appears near mouse over choropleth) ─── -->
    <div id="ctry-hover-card" class="hidden" style="top:0;left:0"></div>

  </div><!-- /#map-wrapper -->

  <!-- ── Top Flashpoints table ─────────────────────────────────────── -->
  <div class="max-w-7xl mx-auto px-6 py-10">
    <div class="flex items-center justify-between mb-5">
      <div>
        <h2 id="table-title" class="font-outfit font-black text-xl text-slate-900">Top Flashpoints</h2>
        <p id="table-sub" class="text-sm text-slate-400 mt-0.5">Highest-intensity locations currently visible — click any row to zoom in</p>
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

<!-- Leaflet + heat plugin (jsdelivr — most reliable global CDN) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.heat/0.2.0/leaflet-heat.js"></script>

<script>
/* ── Guard: show clear error if Leaflet CDN failed to load ─────── */
if (typeof L === 'undefined') {
  var _me = document.getElementById('map');
  if (_me) _me.innerHTML = '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;background:#f8fafc;gap:14px">'
    + '<svg width="36" height="36" fill="none" stroke="#750B25" stroke-width="1.5" viewBox="0 0 24 24"><path d="M12 9v4M12 17h.01"/><path d="M3 12a9 9 0 1 0 18 0 9 9 0 0 0-18 0"/></svg>'
    + '<p style="font-family:Inter,sans-serif;color:#475569;font-size:13px;margin:0;text-align:center">Map library failed to load.<br>Please check your internet connection and refresh.</p>'
    + '</div>';
  throw new Error('[Heatmap] Leaflet.js not loaded');
}

/* ── Map + tile layer — must be first so base map always renders ─── */
var map = L.map('map', { zoomControl: false }).setView([5, 22], 4);
L.control.zoom({ position: 'bottomright' }).addTo(map);
L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
  attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
  subdomains: 'abcd',
  maxZoom: 19
}).addTo(map);

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

/* ── De-escalating situations data ────────────────────────────────── */
var deescData = [
  {lat:-1.940,  lng:29.874, name:"Rwanda",               country:"RW", category:"Policy",       deescLevel:3, desc:"Post-genocide reconciliation model — sustained governance stability and a regional leadership benchmark recognised internationally."},
  {lat:14.032,  lng:38.316, name:"Tigray (Ceasefire)",   country:"ET", category:"Security",     deescLevel:2, desc:"2022 Pretoria Agreement cessation of hostilities; humanitarian corridors reopening and reconstruction gradually underway."},
  {lat:-11.706, lng:40.513, name:"Cabo Delgado",         country:"MZ", category:"Security",     deescLevel:2, desc:"SAMIM and FADM joint operations have recaptured key towns; insurgent activity declining across the province since 2022."},
  {lat:4.154,   lng:9.242,  name:"SW Cameroon (Talks)",  country:"CM", category:"Human Rights", deescLevel:1, desc:"Localised dialogue initiatives and amnesty schemes are reducing some armed group activity in Anglophone regions."},
  {lat:8.490,   lng:30.660, name:"Lakes State (SS)",     country:"SS", category:"Displacement", deescLevel:1, desc:"Ceasefire monitoring showing reduced inter-communal cattle-raiding violence around Rumbek; IDP returns beginning."},
  {lat:7.946,   lng:-1.024, name:"Ghana",                country:"GH", category:"Policy",       deescLevel:3, desc:"Stable democratic transfers of power — a benchmark for West African governance and institutional resilience over two decades."},
  {lat:-15.416, lng:28.283, name:"Lusaka",               country:"ZM", category:"Policy",       deescLevel:2, desc:"Zambia debt restructuring progress improving fiscal governance; economic stabilisation reducing social tension indicators."},
  {lat:0.347,   lng:32.583, name:"Kampala",              country:"UG", category:"Policy",       deescLevel:1, desc:"Post-election tensions cooling; civil society dialogue channels restored and media environment gradually stabilising."},
];

/* ── Lookup maps ──────────────────────────────────────────────────── */
var CAT_COLORS = <?= json_encode($cats,    JSON_UNESCAPED_UNICODE) ?>;
var FLAG       = <?= json_encode($flagMap,  JSON_UNESCAPED_UNICODE) ?>;
var CNAME      = <?= json_encode($cnameMap, JSON_UNESCAPED_UNICODE) ?>;

/* ── Published post markers from DB ──────────────────────────────── */
var postData = <?= json_encode($postMarkers, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;

/* ── Per-country DB stats (reports, latest date, categories) ─────── */
var COUNTRY_STATS = <?= json_encode($countryStats, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;

/* ── Master country list (name, code, flag, centroid) ────────────── */
/* Declared here with the other PHP data: it is consumed further down by
   CTRY_BY_NAME and makeCountryIcon, and a later `var` would hoist as
   undefined and throw at first use. */
var COUNTRY_DATA_LIST = <?= json_encode($_countriesData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;

/* ── Alpha-2 → ISO 3166-1 numeric (for GeoJSON choropleth filter) ── */
var A2_TO_NUM = {
  'DZ':'012','AO':'024','BI':'108','BJ':'204','BF':'854','CM':'120','CF':'140','TD':'148',
  'KM':'174','CG':'178','CD':'180','CI':'384','DJ':'262','EG':'818','GQ':'226','ER':'232',
  'ET':'231','GA':'266','GM':'270','GH':'288','GN':'324','KE':'404','LS':'426','LR':'430',
  'LY':'434','MG':'450','MW':'454','ML':'466','MR':'478','MA':'504','MZ':'508','NA':'516',
  'NE':'562','NG':'566','RW':'646','ST':'678','SN':'686','SL':'694','SO':'706','ZA':'710',
  'SS':'728','SD':'729','TZ':'834','TG':'768','TN':'788','UG':'800','ZM':'894','ZW':'716',
  'BW':'072','CV':'132','GW':'624','MU':'480','SC':'690','SZ':'748','ZR':'180',
};
/* Reverse: numeric → alpha-2 */
var NUM_TO_A2 = {};
Object.keys(A2_TO_NUM).forEach(function(k) { NUM_TO_A2[A2_TO_NUM[k]] = k; });

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

/* ── Country centroid lookup (name → lat/lng/code) ────────────────── */
var CTRY_BY_NAME = {};
(function () {
  for (var i = 0; i < COUNTRY_DATA_LIST.length; i++) {
    var c = COUNTRY_DATA_LIST[i];
    CTRY_BY_NAME[c.name] = { lat: +c.lat, lng: +c.lng, code: c.code };
  }
  // Common alternate spelling used by some posts
  if (CTRY_BY_NAME['DR Congo']) {
    CTRY_BY_NAME['Democratic Republic of Congo'] = CTRY_BY_NAME['DR Congo'];
  }
})();

/* ── Category dot: one per country+category, grows with post count ── */
function makeCategoryDotIcon(col, count, highlight) {
  // 1 report = 15px, easing off via sqrt so busy countries stay usable.
  // Range is roughly 15px (1 report) to 29px (18+).
  var dot = Math.round(11 + Math.min(Math.sqrt(count), 4.2) * 4.3);
  var box = dot + 18;                       // room for the pulse ring
  var ring = '<div style="position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);'
           + 'width:' + dot + 'px;height:' + dot + 'px;pointer-events:none">'
           + '<div style="width:100%;height:100%;border-radius:50%;background:' + col
           + ';animation:pulse-ring 2.8s ease-out infinite"></div></div>';

  // Only label once the dot is big enough to hold a readable number
  var label = (count > 1 && dot >= 19)
    ? '<span style="position:relative;z-index:3;font-family:Outfit,sans-serif;font-weight:900;'
      + 'font-size:' + (dot >= 25 ? 10 : 9) + 'px;color:#fff;line-height:1;'
      + 'text-shadow:0 1px 3px rgba(0,0,0,.45);pointer-events:none">' + count + '</span>'
    : '';

  return L.divIcon({
    className: '',
    iconSize:      [box, box],
    iconAnchor:    [box / 2, box / 2],
    tooltipAnchor: [box / 2 + 2, 0],
    html: '<div style="position:relative;width:' + box + 'px;height:' + box + 'px;'
        + 'display:flex;align-items:center;justify-content:center;overflow:visible">'
        + ring
        + '<div style="width:' + dot + 'px;height:' + dot + 'px;border-radius:50%;background:' + col
        + ';border:2px solid #fff;box-shadow:0 1px 7px rgba(15,23,42,.32),0 0 0 1px rgba(15,23,42,.08)'
        + (highlight ? ',0 0 0 3px rgba(117,11,37,.55)' : '') + ';'
        + 'position:relative;z-index:2;cursor:pointer;display:flex;align-items:center;justify-content:center">'
        + label + '</div></div>',
  });
}

/* ── Post marker icon factory ─────────────────────────────────────── */
var POST_TYPE_COLORS = {
  ARTICLE:'#750B25', PODCAST:'#E7952A', VIDEO:'#ED1C24',
  DOCUMENT:'#6366F1', GALLERY_IMAGE:'#10B981', POLICY_BRIEF:'#8B5CF6'
};
/* ── De-escalating icon factory ───────────────────────────────────── */
function makeDeescIcon(d) {
  var col   = d.deescLevel >= 3 ? '#047857' : d.deescLevel >= 2 ? '#059669' : '#10B981';
  var size  = d.deescLevel >= 3 ? 32 : d.deescLevel >= 2 ? 24 : 18;
  var glow  = d.deescLevel >= 3 ? '0 0 10px 3px '+col+'66' : d.deescLevel >= 2 ? '0 0 6px 2px '+col+'44' : 'none';
  var inner = size - 6;
  var arr   = Math.max(6, inner - 4);
  return L.divIcon({
    className: '',
    iconSize:      [size, size],
    iconAnchor:    [size/2, size/2],
    tooltipAnchor: [size/2+3, 0],
    html: '<div style="width:'+size+'px;height:'+size+'px;display:flex;align-items:center;justify-content:center">'
        + '<div style="width:'+inner+'px;height:'+inner+'px;border-radius:50%;background:'+col
        + ';border:1.5px solid rgba(255,255,255,.65);box-shadow:'+glow
        + ';display:flex;align-items:center;justify-content:center">'
        + '<svg width="'+arr+'" height="'+arr+'" viewBox="0 0 12 12" fill="none">'
        + '<path d="M6 2v8M3 7l3 3 3-3" stroke="rgba(0,0,0,.65)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>'
        + '</svg></div></div>',
  });
}

/* ── Legend tab switch ────────────────────────────────────────────── */
function setLegTab(tab) {
  legendTab = tab;
  var isDesc = tab === 'deescalating';

  var btnEsc  = document.getElementById('leg-tab-esc');
  var btnDesc = document.getElementById('leg-tab-desc');
  if (btnEsc) {
    btnEsc.style.background = isDesc ? 'transparent' : 'rgba(237,28,36,.12)';
    btnEsc.style.color      = isDesc ? '#94a3b8' : '#ED1C24';
  }
  if (btnDesc) {
    btnDesc.style.background = isDesc ? 'rgba(5,150,105,.14)' : 'transparent';
    btnDesc.style.color      = isDesc ? '#059669' : '#94a3b8';
  }
  var legEsc  = document.getElementById('legend-escalating');
  var legDesc = document.getElementById('legend-deescalating');
  if (legEsc)  legEsc.style.display  = isDesc ? 'none' : '';
  if (legDesc) legDesc.style.display = isDesc ? '' : 'none';

  var titleEl = document.getElementById('table-title');
  var subEl   = document.getElementById('table-sub');
  if (titleEl) titleEl.textContent = isDesc ? 'Improving Situations' : 'Top Flashpoints';
  if (subEl)   subEl.textContent   = isDesc
    ? 'De-escalating situations currently visible — click any row to zoom in'
    : 'Highest-intensity locations currently visible — click any row to zoom in';

  applyFilters();
}

/* ── De-escalating detail panel ───────────────────────────────────── */
function openDeescDetail(d) {
  var col        = d.deescLevel >= 3 ? '#047857' : d.deescLevel >= 2 ? '#059669' : '#10B981';
  var levelLabel = d.deescLevel >= 3 ? 'Major Improvement' : d.deescLevel >= 2 ? 'Moderate Progress' : 'Early Progress';
  var catC       = CAT_COLORS[d.category] || '#64748b';
  if (window.innerWidth < 768) document.getElementById('ctrl-panel').classList.remove('ctrl-open');
  document.getElementById('detail-content').innerHTML =
    '<div style="height:4px;background:'+col+'"></div>'
    + '<div style="padding:18px 18px 24px">'
    + '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">'
    + '<span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:'+col+'">'
    + esc(levelLabel)+'</span>'
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
    + '<span style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.07em">Improvement Level</span>'
    + '<span style="font-size:18px;font-weight:900;color:'+col+'">' + d.deescLevel
    + '<span style="font-size:11px;font-weight:500;color:#cbd5e1">&thinsp;/&thinsp;3</span></span>'
    + '</div>'
    + '<div style="height:9px;background:#e2e8f0;border-radius:5px;overflow:hidden">'
    + '<div style="width:'+(d.deescLevel/3*100).toFixed(0)+'%;height:100%;background:'+col
    + ';border-radius:5px;transition:width .6s cubic-bezier(.4,0,.2,1)"></div>'
    + '</div>'
    + '</div>'
    + '<div style="margin-top:18px;padding:14px 15px;background:#f0fdf4;border-radius:12px;border-left:3px solid '+col+'">'
    + '<p style="font-size:13px;color:#374151;line-height:1.7;margin:0">'+esc(d.desc)+'</p>'
    + '</div>'
    + '<a href="/news?q='+encodeURIComponent(d.name)+'"'
    + ' style="display:flex;align-items:center;gap:7px;margin-top:18px;text-decoration:none;'
    + 'padding:12px 16px;border-radius:12px;background:'+col+';color:#fff;'
    + 'font-size:13px;font-weight:700;justify-content:center">'
    + '<svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">'
    + '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>'
    + '<polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>'
    + '</svg>View Related Reports</a>'
    + '</div>';
  document.getElementById('detail-panel').classList.add('panel-open');
  document.getElementById('detail-panel').classList.remove('panel-hidden');
}

/* ── De-escalating table ──────────────────────────────────────────── */
function renderDeescTable(data) {
  var sorted = data.slice().sort(function(a,b){ return b.deescLevel - a.deescLevel; });
  var tbody  = document.getElementById('hotspot-tbody');
  if (!sorted.length) {
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-10 text-slate-300 text-sm">No improving situations match the current filters.</td></tr>';
    return;
  }
  tbody.innerHTML = sorted.map(function(d, i) {
    var col  = d.deescLevel >= 3 ? '#047857' : d.deescLevel >= 2 ? '#059669' : '#10B981';
    var catC = CAT_COLORS[d.category] || '#94a3b8';
    var bg   = i % 2 === 0 ? '#fff' : '#fafafa';
    var pct  = (d.deescLevel / 3 * 100).toFixed(0);
    var lvl  = d.deescLevel >= 3 ? 'Major' : d.deescLevel >= 2 ? 'Moderate' : 'Early';
    return '<tr style="background:'+bg+';border-bottom:1px solid #f1f5f9;cursor:pointer;transition:background .1s"'
      + ' onmouseover="this.style.background=\'#f0fdf4\'" onmouseout="this.style.background=\''+bg+'\'"'
      + ' onclick="zoomTo('+d.lat+','+d.lng+')">'
      + '<td class="px-4 py-3"><span style="font-family:Outfit,sans-serif;font-weight:900;font-size:14px;color:'+col+'">'+(i+1)+'</span></td>'
      + '<td class="px-4 py-3"><span style="font-weight:600;color:#0f172a">'+esc(d.name)+'</span></td>'
      + '<td class="px-4 py-3 hidden md:table-cell"><span style="color:#64748b;font-size:12px">'
      + (FLAG[d.country]||'') + ' ' + esc(CNAME[d.country]||d.country) + '</span></td>'
      + '<td class="px-4 py-3 hidden sm:table-cell">'
      + '<span style="background:'+catC+'18;color:'+catC+';border:1px solid '+catC+'35;border-radius:20px;padding:2px 9px;font-size:10px;font-weight:700">'
      + esc(d.category)+'</span></td>'
      + '<td class="px-4 py-3"><div style="display:flex;align-items:center;gap:8px">'
      + '<div style="flex:1;height:5px;background:#e2e8f0;border-radius:3px;overflow:hidden">'
      + '<div style="width:'+pct+'%;height:100%;background:'+col+';border-radius:3px"></div></div>'
      + '<span style="font-size:11px;font-weight:700;color:'+col+';white-space:nowrap">'+lvl+'</span>'
      + '</div></td></tr>';
  }).join('');
}

/* ── Toggle published reports layer ──────────────────────────────── */
function toggleReports() {
  showReports = !showReports;
  var btn = document.getElementById('reports-toggle-btn');
  if (btn) {
    btn.textContent = showReports ? 'ON' : 'OFF';
    btn.style.background    = showReports ? 'rgba(99,102,241,.14)' : '#f1f5f9';
    btn.style.color         = showReports ? '#4F46E5' : '#94a3b8';
    btn.style.borderColor   = showReports ? 'rgba(99,102,241,.35)' : '#e2e8f0';
  }
  applyFilters();
}

/* ── State ────────────────────────────────────────────────────────── */
var markers           = [];
var postMarkers       = [];
var deescMarkersLayer = [];
var countryMarkers    = [];
var heatLayer         = null;
var choroplethLayer   = null;
var viewMode          = 'markers';
var selCtry           = 'ALL';
var allCatsOn         = true;
var showReports       = true;
var legendTab         = 'escalating';

/* ── HTML escape ──────────────────────────────────────────────────── */
function esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── Country intensity helpers ────────────────────────────────────── */
function getCountryMaxIntensity(code) {
  var pts = mapData.filter(function(d){ return d.country === code; });
  return pts.length ? Math.max.apply(null, pts.map(function(d){ return d.intensity; })) : 0;
}
function getCountryCategories(code) {
  var name    = CNAME[code] || code;
  var mapCats = mapData.filter(function(d){ return d.country === code; }).map(function(d){ return d.category; });
  var dbCats  = (COUNTRY_STATS[name] && COUNTRY_STATS[name].cats) ? COUNTRY_STATS[name].cats : [];
  var seen = {}, result = [];
  mapCats.concat(dbCats).forEach(function(c) { if (c && !seen[c]) { seen[c] = 1; result.push(c); } });
  return result;
}
function getCountryTrend(code) {
  if (deescData.some(function(d){ return d.country === code; })) return 'Decreasing';
  var mx = getCountryMaxIntensity(code);
  if (mx >= 8) return 'Increasing';
  if (mx >= 4) return 'Stable';
  return 'Stable';
}
function intensityColor(v) {
  if (v >= 9) return '#ED1C24';
  if (v >= 7) return '#E7952A';
  if (v >= 5) return '#F59E0B';
  if (v >= 2) return '#65A30D';
  return '#059669';
}
function intensityLabel(v) {
  if (v >= 9) return 'Very High';
  if (v >= 7) return 'High';
  if (v >= 5) return 'Moderate';
  if (v >= 2) return 'Low';
  return 'Stable';
}

/* ── Stat mini-card HTML ──────────────────────────────────────────── */
function _stat(label, value, col) {
  return '<div class="cstat">'
    + '<p class="cstat-lbl">' + esc(label) + '</p>'
    + '<p class="cstat-val" style="color:' + (col||'#0f172a') + '">' + esc(String(value)) + '</p>'
    + '</div>';
}

/* ── Country summary panel ─────────────────────────────────────────── */
function openCountryPanel(code) {
  if (!code || code === 'ALL') return;
  var name    = CNAME[code] || code;
  var flag    = FLAG[code] || '';
  var dbS     = COUNTRY_STATS[name] || {};
  var latestF = dbS.latestFmt || null;

  var locations  = mapData.filter(function(d){ return d.country === code; });
  var maxInt     = locations.length ? Math.max.apply(null, locations.map(function(d){ return d.intensity; })) : 0;
  var avgInt     = locations.length ? (locations.reduce(function(s,d){ return s+d.intensity; },0)/locations.length).toFixed(1) : 0;
  var allCats    = getCountryCategories(code);
  var trend      = getCountryTrend(code);
  var trendCol   = trend === 'Decreasing' ? '#059669' : trend === 'Increasing' ? '#ED1C24' : '#E7952A';
  var trendIcon  = trend === 'Decreasing' ? '↓' : trend === 'Increasing' ? '↑' : '→';
  var intCol     = intensityColor(maxInt);
  var intLbl     = maxInt > 0 ? intensityLabel(maxInt) : 'No data';

  var topLoc = locations.length
    ? locations.slice().sort(function(a,b){ return b.intensity - a.intensity; })[0]
    : null;

  // Real published posts for this country, of ANY content type (article, document,
  // podcast, video, gallery) — postData already carries the correct per-type detail
  // URL for each item, so linking straight to these avoids the old bug where
  // "View All Reports" always pointed at /news (articles only) even when the
  // country's published content was actually a document/video/podcast.
  var countryPosts = postData.filter(function(d){ return d.country === name; });
  var TYPE_LABEL = { ARTICLE:'News', PODCAST:'Podcast', VIDEO:'Video', DOCUMENT:'Document', GALLERY_IMAGE:'Gallery', POLICY_BRIEF:'Policy Brief' };

  var catHtml = allCats.map(function(c) {
    var cc = CAT_COLORS[c] || '#94a3b8';
    return '<span style="background:'+cc+'18;color:'+cc+';border:1px solid '+cc+'35;border-radius:20px;padding:2px 9px;font-size:9px;font-weight:700;white-space:nowrap">'
      + esc(c) + '</span>';
  }).join('');

  // Build issue summary from top incident
  var issueSummary = topLoc
    ? esc(topLoc.name) + ': ' + esc(topLoc.desc)
    : 'No specific incident data available.';

  var html =
    '<div style="height:4px;background:' + intCol + '"></div>'
    + '<div style="padding:20px 20px 28px">'
    // Header
    + '<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px">'
    + '<span style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:'+trendCol+'">'+trendIcon+' Conflict '+esc(trend)+'</span>'
    + '<button onclick="closeDetail()" style="width:28px;height:28px;border-radius:50%;border:none;background:#f1f5f9;color:#64748b;cursor:pointer;font-size:18px;line-height:1;display:flex;align-items:center;justify-content:center">&times;</button>'
    + '</div>'
    // Flag + name
    + '<div style="font-size:40px;margin-bottom:6px">' + flag + '</div>'
    + '<h3 style="font-family:Outfit,sans-serif;font-weight:900;font-size:23px;color:#0f172a;margin:0 0 4px">' + esc(name) + '</h3>'
    + '<p style="font-size:11px;color:#94a3b8;font-weight:600;margin:0 0 18px">Country Overview</p>'
    // Stats grid
    + '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:18px">'
    + _stat('Reports', countryPosts.length, intCol)
    + _stat('Locations', locations.length || '—', '#64748b')
    + _stat('Peak Intensity', maxInt > 0 ? intLbl : '—', intCol)
    + _stat('Trend', trendIcon + ' ' + trend, trendCol)
    + '</div>'
    // Latest date
    + (latestF ? '<p style="font-size:11px;color:#94a3b8;margin:0 0 16px;display:flex;align-items:center;gap:5px">'
      + '<svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>'
      + 'Latest report: ' + esc(latestF) + '</p>' : '')
    // Intensity bar
    + '<div style="margin-bottom:16px">'
    + '<div style="display:flex;justify-content:space-between;margin-bottom:5px">'
    + '<span style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8">Intensity Indicator</span>'
    + '<span style="font-size:15px;font-weight:900;color:'+intCol+'">'+(maxInt>0?maxInt+'/10':'—')+'</span>'
    + '</div>'
    + '<div style="height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden">'
    + '<div style="width:'+(maxInt*10)+'%;height:100%;background:'+intCol+';border-radius:4px;transition:width .6s"></div>'
    + '</div>'
    + '</div>'
    // Issue summary
    + '<div style="margin-bottom:16px;padding:13px 14px;background:#f8fafc;border-radius:12px;border-left:3px solid '+intCol+'">'
    + '<p style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#94a3b8;margin:0 0 5px">Key Incident</p>'
    + '<p style="font-size:12px;color:#374151;line-height:1.7;margin:0">' + issueSummary + '</p>'
    + '</div>'
    // Categories
    + '<div style="margin-bottom:20px">'
    + '<p style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#94a3b8;margin:0 0 8px">Issue Categories</p>'
    + '<div style="display:flex;flex-wrap:wrap;gap:5px">' + (catHtml || '<span style="font-size:11px;color:#cbd5e1">No data</span>') + '</div>'
    + '</div>'
    // Published reports — direct links to the actual posts (any content type)
    + '<div style="margin-bottom:12px">'
    + '<p style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#94a3b8;margin:0 0 8px">Published Reports (' + countryPosts.length + ')</p>'
    + (countryPosts.length > 0
      ? ('<div style="max-height:230px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:12px">'
        + countryPosts.map(function(p, i) {
            var tlbl = TYPE_LABEL[p.type] || p.type;
            return '<a href="' + esc(p.url) + '"'
              + ' style="display:block;padding:10px 12px;text-decoration:none;color:#0f172a;'
              + (i < countryPosts.length - 1 ? 'border-bottom:1px solid #f1f5f9;' : '') + '">'
              + '<span style="display:flex;align-items:center;gap:6px;margin-bottom:2px">'
              + '<span style="font-size:8px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#750B25;background:#750B2514;border-radius:8px;padding:1px 6px">' + esc(tlbl) + '</span>'
              + '<span style="font-size:9px;color:#94a3b8">' + esc(p.date || '') + '</span>'
              + '</span>'
              + '<span style="display:block;font-size:12px;font-weight:700;line-height:1.4">' + esc(p.title) + '</span>'
              + '</a>';
          }).join('')
        + '</div>')
      : ('<div style="padding:11px 16px;border-radius:12px;background:#f1f5f9;color:#94a3b8;font-size:12px;font-weight:600;text-align:center">'
        + 'No published reports yet for ' + esc(name) + '</div>'))
    + '</div>'
    + '<a href="/heatmap?country=' + encodeURIComponent(code) + '"'
    + ' onclick="setCountry(\'' + esc(code) + '\');return false"'
    + ' style="display:flex;align-items:center;gap:7px;text-decoration:none;padding:10px 16px;border-radius:12px;border:1.5px solid #e2e8f0;color:#475569;font-size:12px;font-weight:700;justify-content:center">'
    + 'Filter Map to ' + esc(name) + '</a>'
    + '</div>';

  if (window.innerWidth < 768) {
    document.getElementById('ctrl-panel').classList.remove('ctrl-open');
  }
  document.getElementById('detail-content').innerHTML = html;
  document.getElementById('detail-panel').classList.add('panel-open');
  document.getElementById('detail-panel').classList.remove('panel-hidden');
}

/* ── Choropleth hover tooltip ─────────────────────────────────────── */
function showCountryHoverCard(e, code) {
  var card = document.getElementById('ctry-hover-card');
  var name   = CNAME[code] || code;
  var flag   = FLAG[code] || '';
  var maxInt = getCountryMaxIntensity(code);
  var col    = intensityColor(maxInt);
  var lbl    = maxInt > 0 ? intensityLabel(maxInt) : 'No data';
  var trend  = getCountryTrend(code);
  var trendIcon = trend === 'Decreasing' ? '↓ ' : trend === 'Increasing' ? '↑ ' : '→ ';
  var dbS    = COUNTRY_STATS[name] || {};
  var rep    = dbS.reports || 0;
  var cats   = getCountryCategories(code).slice(0,3).map(function(c){
    var cc = CAT_COLORS[c]||'#94a3b8';
    return '<span style="background:'+cc+'28;color:'+cc+';border-radius:20px;padding:1px 7px;font-size:9px;font-weight:700">'+esc(c)+'</span>';
  }).join(' ');

  card.innerHTML =
    '<div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">'
    + '<span style="font-size:20px">' + flag + '</span>'
    + '<div>'
    + '<p style="font-family:Outfit,sans-serif;font-weight:900;font-size:13px;color:#0f172a;margin:0">' + esc(name) + '</p>'
    + '<p style="font-size:9px;font-weight:700;color:'+col+';margin:0">' + lbl + ' · ' + trendIcon + trend + '</p>'
    + '</div>'
    + '</div>'
    + (cats ? '<div style="margin-bottom:8px;display:flex;flex-wrap:wrap;gap:3px">' + cats + '</div>' : '')
    + '<div style="display:flex;justify-content:space-between;align-items:center">'
    + '<span style="font-size:9px;color:#64748b">' + (rep > 0 ? rep + ' reports' : mapData.filter(function(d){ return d.country===code; }).length + ' locations') + '</span>'
    + '<span style="font-size:9px;font-weight:700;color:#750B25">Click for details →</span>'
    + '</div>';

  // Position near the mouse
  var wrapper = document.getElementById('map-wrapper');
  var rect    = wrapper.getBoundingClientRect();
  var lm      = e.originalEvent || e;
  var cx      = (lm.clientX - rect.left) + 12;
  var cy      = (lm.clientY - rect.top)  - 10;
  if (cx + 250 > rect.width) cx -= 260;
  if (cy + 120 > rect.height) cy -= 130;
  card.style.left = cx + 'px';
  card.style.top  = cy + 'px';
  card.classList.remove('hidden');
}
function hideCountryHoverCard() {
  document.getElementById('ctry-hover-card').classList.add('hidden');
}

/* ── GeoJSON choropleth ────────────────────────────────────────────── */
function getChoroplethColor(code, activeCats) {
  var pts = mapData.filter(function(d){
    return d.country === code
      && (activeCats.length === 0 || activeCats.indexOf(d.category) !== -1);
  });
  if (!pts.length) return 'rgba(255,255,255,0)';
  var mx = Math.max.apply(null, pts.map(function(d){ return d.intensity; }));
  return intensityColor(mx);
}

function loadChoropleth() {
  function _initChoropleth() {
    fetch('https://cdn.jsdelivr.net/npm/world-atlas@2.0.2/countries-110m.json')
      .then(function(r){ return r.json(); })
      .then(function(topo) {
        var geojson = topojson.feature(topo, topo.objects.countries);
        var activeCats = Array.from(document.querySelectorAll('.cat-check:checked')).map(function(c){ return c.value; });

        choroplethLayer = L.geoJSON(geojson, {
          filter: function(f) { return !!NUM_TO_A2[f.id]; },
          style: function(f) {
            var code = NUM_TO_A2[f.id] || '';
            var col  = getChoroplethColor(code, activeCats);
            var hasData = col !== 'rgba(255,255,255,0)';
            return {
              fillColor:   col,
              fillOpacity: hasData ? 0.17 : 0.04,
              color:       'rgba(15,23,42,0.18)',
              weight:      0.7,
            };
          },
          onEachFeature: function(f, layer) {
            var code = NUM_TO_A2[f.id] || '';
            if (!code) return;
            layer.on({
              mouseover: function(e) {
                layer.setStyle({ fillOpacity: 0.42, weight: 1.8, color: 'rgba(117,11,37,0.65)' });
                showCountryHoverCard(e, code);
              },
              mousemove: function(e) {
                showCountryHoverCard(e, code);
              },
              mouseout: function() {
                if (choroplethLayer) choroplethLayer.resetStyle(layer);
                hideCountryHoverCard();
              },
              click: function() {
                openCountryPanel(code);
                setCountry(code);
              }
            });
          }
        }).addTo(map);
        choroplethLayer.bringToBack();
      })
      .catch(function(e){ console.warn('[Heatmap] Choropleth load failed:', e); });
  }

  if (typeof topojson !== 'undefined') {
    _initChoropleth();
    return;
  }

  var _tjs = document.createElement('script');
  _tjs.src = 'https://cdn.jsdelivr.net/npm/topojson-client@3.1.0/dist/topojson-client.min.js';
  _tjs.onload = _initChoropleth;
  _tjs.onerror = function() { console.warn('[Heatmap] topojson-client failed to load — choropleth disabled'); };
  document.head.appendChild(_tjs);
}

/* ── Refresh choropleth colors after filter change ─────────────────── */
function refreshChoropleth(activeCats) {
  if (!choroplethLayer) return;
  choroplethLayer.eachLayer(function(layer) {
    var f    = layer.feature;
    var code = NUM_TO_A2[f.id] || '';
    if (!code) return;
    var col     = getChoroplethColor(code, activeCats);
    var hasData = col !== 'rgba(255,255,255,0)';
    layer.setStyle({
      fillColor:   col,
      fillOpacity: hasData ? 0.17 : 0.04,
    });
  });
}

/* ── Country-aggregate markers (Countries view mode) ─────────────── */
function makeCountryIcon(code, intensity) {
  var col  = intensity > 0 ? intensityColor(intensity) : '#475569';
  var size = intensity >= 8 ? 44 : intensity >= 5 ? 36 : 28;
  var flag = FLAG[code] || '';
  return L.divIcon({
    className: '',
    iconSize:  [size, size],
    iconAnchor:[size/2, size/2],
    tooltipAnchor: [size/2+4, 0],
    html: '<div style="width:'+size+'px;height:'+size+'px;border-radius:50%;background:'+col
        + ';border:2px solid rgba(255,255,255,.55);display:flex;align-items:center;justify-content:center;'
        + 'box-shadow:0 0 '+(intensity>=8?14:8)+'px '+col+(intensity>=8?'99':'44')+';cursor:pointer;'
        + 'font-size:'+(size-12)+'px;line-height:1">' + flag + '</div>',
  });
}

/* ── Intensity slider update ──────────────────────────────────────── */
function updateSlider() {
  var v = document.getElementById('int-slider').value;
  var pct = ((v-1)/9*100).toFixed(1);
  document.getElementById('int-val').textContent = v;
  document.getElementById('int-slider').style.background =
    'linear-gradient(to right,#E7952A '+pct+'%,#e2e8f0 '+pct+'%)';
}

/* ── Country filter ───────────────────────────────────────────────── */
function setCountry(code) {
  selCtry = code;
  document.querySelectorAll('.ctry-btn').forEach(function(b) {
    var active = b.dataset.ctry === code;
    b.style.background  = active ? 'rgba(117,11,37,.09)' : '';
    b.style.color       = active ? '#750B25' : '#475569';
    b.style.fontWeight  = active ? '700' : '500';
  });
  applyFilters();
  if (window.innerWidth < 768 && code !== 'ALL') {
    setTimeout(function(){ _ctrlClose(); }, 250);
  }
  // Show country summary panel when a specific country is selected
  if (code !== 'ALL') {
    openCountryPanel(code);
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
  var q        = (document.getElementById('map-search').value || '').toLowerCase().trim();
  var minInt   = parseInt(document.getElementById('int-slider').value, 10);
  var cats     = Array.from(document.querySelectorAll('.cat-check:checked')).map(function(c){ return c.value; });
  var dateFrom = (document.getElementById('date-from') || {}).value || '';
  var dateTo   = (document.getElementById('date-to')   || {}).value || '';

  // Remove all map layers
  markers.forEach(function(m){ map.removeLayer(m); });
  markers = [];
  if (heatLayer) { map.removeLayer(heatLayer); heatLayer = null; }
  postMarkers.forEach(function(m){ map.removeLayer(m); });
  postMarkers = [];
  deescMarkersLayer.forEach(function(m){ map.removeLayer(m); });
  deescMarkersLayer = [];
  countryMarkers.forEach(function(m){ map.removeLayer(m); });
  countryMarkers = [];

  // Refresh choropleth colors for selected categories
  refreshChoropleth(cats);

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

  // De-escalating tab: render improving-situation markers, skip conflict layer
  if (legendTab === 'deescalating') {
    var visibleDesc = [];
    deescData.forEach(function(d) {
      if (selCtry !== 'ALL' && d.country !== selCtry) return;
      if (cats.indexOf(d.category) === -1) return;
      if (q) {
        var hs = d.name.toLowerCase() + ' ' + (CNAME[d.country]||'').toLowerCase()
               + ' ' + d.category.toLowerCase() + ' ' + d.desc.toLowerCase();
        if (hs.indexOf(q) === -1) return;
      }
      visibleDesc.push(d);
      var m = L.marker([d.lat, d.lng], { icon: makeDeescIcon(d) })
        .bindTooltip(esc(d.name) + ' · Improving', { direction: 'right', opacity: 1 });
      m.on('click', (function(dd){ return function(){ openDeescDetail(dd); }; })(d));
      m.addTo(map);
      deescMarkersLayer.push(m);
    });
    document.getElementById('active-count').textContent =
      visibleDesc.length + ' improving situation' + (visibleDesc.length !== 1 ? 's' : '') + ' visible';
    var allHot = Object.keys(COUNTRY_STATS).length;
    var kpiEl  = document.getElementById('kpi-hotspots');
    if (kpiEl) kpiEl.textContent = allHot;
    renderDeescTable(visibleDesc);
    return;
  }

  // Countries view mode: one aggregate marker per country
  if (viewMode === 'countries') {
    var seenCountries = {};
    visible.forEach(function(d) { seenCountries[d.country] = true; });
    Object.keys(seenCountries).forEach(function(code) {
      var pts = visible.filter(function(d){ return d.country === code; });
      var mx  = Math.max.apply(null, pts.map(function(d){ return d.intensity; }));
      var lat = pts.reduce(function(s,d){ return s+d.lat; },0)/pts.length;
      var lng = pts.reduce(function(s,d){ return s+d.lng; },0)/pts.length;
      var m = L.marker([lat, lng], { icon: makeCountryIcon(code, mx), zIndexOffset: 100 })
        .bindTooltip(esc(CNAME[code]||code) + ' · ' + pts.length + ' locations · Peak: ' + mx + '/10',
                     { direction: 'right', opacity: 1 });
      m.on('click', (function(c){ return function(){ openCountryPanel(c); }; })(code));
      m.addTo(map);
      countryMarkers.push(m);
    });
    var pill = Object.keys(seenCountries).length + ' countries';
    document.getElementById('active-count').textContent = pill;
    var allHot = Object.keys(COUNTRY_STATS).length;
    var kpiEl  = document.getElementById('kpi-hotspots');
    if (kpiEl) kpiEl.textContent = allHot;
    renderTable(visible);
    return;
  }

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
        gradient: { '0.0':'#E7952A', '0.40':'#ED1C24', '0.75':'#A50E1F', '1.0':'#750B25' }
      }).addTo(map);
    }
  }

  // Render published content as one growing, category-coloured dot per
  // country+category, so several categories in the same country stay legible.
  var visiblePosts = 0;
  if (showReports && postData && postData.length) {
    var groups = {};                       // "Country||Category" -> [posts]
    postData.forEach(function(d) {
      // NOTE: deliberately NOT filtered by selCtry. Selecting a country opens
      // its panel and focuses the conflict layer, but every country's content
      // dots stay on the map so the overall picture is never lost.
      // Match on ANY of the post's categories so a multi-category post stays
      // visible when filtering by a secondary one
      var dCats = d.categories && d.categories.length ? d.categories : [d.category];
      var catHit = false;
      for (var ci = 0; ci < dCats.length; ci++) {
        if (cats.indexOf(dCats[ci]) !== -1) { catHit = true; break; }
      }
      if (!catHit) return;
      // Date range filter
      if (dateFrom && d.dateRaw && d.dateRaw < dateFrom) return;
      if (dateTo   && d.dateRaw && d.dateRaw > dateTo)   return;
      if (q) {
        var haystack = d.title.toLowerCase() + ' ' + d.country.toLowerCase() + ' ' + (d.region || '').toLowerCase() + ' ' + d.category.toLowerCase() + ' ' + d.type.toLowerCase();
        if (haystack.indexOf(q) === -1) return;
      }
      visiblePosts++;
      var key = d.country + '||' + d.category;
      (groups[key] = groups[key] || []).push(d);
    });

    // Count categories per country so we can fan them out around the centroid
    var perCountry = {};
    Object.keys(groups).forEach(function(k) {
      var ctry = k.split('||')[0];
      perCountry[ctry] = (perCountry[ctry] || 0) + 1;
    });

    var seenIdx = {};
    Object.keys(groups).forEach(function(key) {
      var parts = key.split('||');
      var ctry  = parts[0], cat = parts[1];
      var items = groups[key];
      var col   = CAT_COLORS[cat] || '#94a3b8';

      // Anchor on the country centroid; fall back to the posts' own coords
      var base = CTRY_BY_NAME[ctry];
      var lat, lng;
      if (base) {
        lat = base.lat; lng = base.lng;
      } else {
        lat = items.reduce(function(s,d){ return s + d.lat; }, 0) / items.length;
        lng = items.reduce(function(s,d){ return s + d.lng; }, 0) / items.length;
      }

      // Fan multiple categories evenly around the centroid so none overlap
      var total = perCountry[ctry] || 1;
      var idx   = (seenIdx[ctry] = (seenIdx[ctry] === undefined ? 0 : seenIdx[ctry] + 1));
      if (total > 1) {
        var ang = (2 * Math.PI * idx) / total - Math.PI / 2;
        var rad = 1.15 + Math.min(total, 6) * 0.12;   // degrees
        lat += Math.sin(ang) * rad * 0.72;
        lng += Math.cos(ang) * rad;
      }

      // Ring the selected country's dots instead of hiding everyone else's
      var isSelCtry = (selCtry !== 'ALL' && CNAME[selCtry] === ctry);

      var m = L.marker([lat, lng], {
        icon: makeCategoryDotIcon(col, items.length, isSelCtry),
        zIndexOffset: isSelCtry ? 700 : 600
      }).bindTooltip(
        '<strong>' + esc(ctry) + '</strong><br>'
        + '<span style="color:' + col + ';font-weight:700">' + esc(cat) + '</span>'
        + ' &middot; ' + items.length + ' ' + (items.length === 1 ? 'report' : 'reports'),
        { direction: 'right', opacity: 1 }
      );
      m.on('click', (function(c, k, list) {
        return function() {
          if (list.length === 1) openPostDetail(list[0]);
          else openCategoryCluster(c, k, list);
        };
      })(ctry, cat, items));
      m.addTo(map);
      postMarkers.push(m);
    });
  }

  // Active count pill
  var critical = visible.filter(function(d){ return d.intensity >= 8; }).length;
  var pill = visible.length + ' location' + (visible.length !== 1 ? 's' : '') + ' · ' + critical + ' critical';
  if (visiblePosts > 0) pill += ' · ' + visiblePosts + ' report' + (visiblePosts !== 1 ? 's' : '');
  document.getElementById('active-count').textContent = pill;

  // KPI hotspots: distinct countries with at least one real published report
  // (always from the full COUNTRY_STATS snapshot, not the current filter selection)
  var allHot = Object.keys(COUNTRY_STATS).length;
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
    b.style.background   = on ? '#ffffff' : 'transparent';
    b.style.borderColor  = on ? '#e2e8f0' : 'transparent';
    b.style.color        = on ? '#750B25' : '#64748b';
    b.style.boxShadow    = on ? '0 1px 3px rgba(15,23,42,.08)' : 'none';
  });
  applyFilters();
}
/* ── Countries mode button ────────────────────────────────────────── */
var _modeCountriesBtn = document.getElementById('mode-countries');
if (_modeCountriesBtn) _modeCountriesBtn.style.border = '1px solid transparent';

/* ── Zoom to location (from table) ───────────────────────────────── */
function zoomTo(lat, lng) {
  map.setView([lat, lng], 8, { animate:true, duration:0.7 });
  if (legendTab === 'deescalating') {
    var d = deescData.find(function(p){ return p.lat === lat && p.lng === lng; });
    if (d) openDeescDetail(d);
  } else {
    var d = mapData.find(function(p){ return p.lat === lat && p.lng === lng; });
    if (d) openDetail(d);
  }
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
/* ── Cluster panel: every report behind one category dot ──────────── */
function openCategoryCluster(country, category, items) {
  var col = CAT_COLORS[category] || '#94a3b8';
  var TYPE_LABEL = {ARTICLE:'Article', PODCAST:'Podcast', VIDEO:'Video', DOCUMENT:'Document', GALLERY_IMAGE:'Photo Gallery'};

  var rows = items.slice().sort(function(a, b) {
    return (b.dateRaw || '').localeCompare(a.dateRaw || '');
  }).map(function(d, i) {
    return '<button onclick="_clusterOpen(' + i + ')" style="display:block;width:100%;text-align:left;'
      + 'background:#fff;border:1px solid #e2e8f0;border-left:3px solid ' + col + ';border-radius:10px;'
      + 'padding:11px 13px;margin-bottom:7px;cursor:pointer">'
      + '<p style="font-family:Outfit,sans-serif;font-weight:800;font-size:13px;color:#0f172a;margin:0 0 3px;line-height:1.35">'
      + esc(d.title) + '</p>'
      + '<p style="font-size:10px;color:#94a3b8;margin:0;font-weight:600">'
      + esc(TYPE_LABEL[d.type] || d.type) + ' &middot; ' + esc(d.date || '') + '</p>'
      + '</button>';
  }).join('');

  window._clusterItems = items.slice().sort(function(a, b) {
    return (b.dateRaw || '').localeCompare(a.dateRaw || '');
  });

  var html =
    '<div style="height:4px;background:' + col + '"></div>'
    + '<div style="padding:20px 20px 28px">'
    + '<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:14px">'
    + '<span style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:' + col + '">'
    + esc(category) + '</span>'
    + '<button onclick="closeDetail()" style="width:28px;height:28px;border-radius:50%;border:none;'
    + 'background:#f1f5f9;color:#64748b;cursor:pointer;font-size:18px;line-height:1;'
    + 'display:flex;align-items:center;justify-content:center">&times;</button>'
    + '</div>'
    + '<h3 style="font-family:Outfit,sans-serif;font-weight:900;font-size:21px;color:#0f172a;margin:0 0 3px">'
    + esc(country) + '</h3>'
    + '<p style="font-size:12px;color:#94a3b8;margin:0 0 18px;font-weight:600">'
    + items.length + ' published ' + (items.length === 1 ? 'report' : 'reports') + ' in this category</p>'
    + rows
    + '</div>';

  document.getElementById('detail-content').innerHTML = html;
  document.getElementById('detail-panel').classList.add('panel-open');
  document.getElementById('detail-panel').classList.remove('panel-hidden');
}

function _clusterOpen(i) {
  if (window._clusterItems && window._clusterItems[i]) openPostDetail(window._clusterItems[i]);
}

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
  // Load choropleth layer asynchronously (non-blocking)
  setTimeout(loadChoropleth, 600);
} catch (e) {
  console.error('[Heatmap] Init failed:', e);
  document.getElementById('active-count').textContent = 'Map error — check console';
}
</script>

</body>
</html>
