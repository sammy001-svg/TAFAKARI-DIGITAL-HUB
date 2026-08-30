<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

$me  = require_super_admin();
$uid = $me['id'];
ensure_settings_table();

$saved   = '';
$saveErr = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $rawJson = trim($_POST[$action . '_json'] ?? '');
    $decoded = json_decode($rawJson, true);

    if ($action === 'countries' && is_array($decoded)) {
        $clean = [];
        foreach ($decoded as $c) {
            $name = trim($c['name'] ?? '');
            $code = strtoupper(trim($c['code'] ?? ''));
            if (!$name || !$code) continue;
            $clean[] = ['name'=>$name,'code'=>$code,'flag'=>trim($c['flag']??''),'lat'=>round((float)($c['lat']??0),5),'lng'=>round((float)($c['lng']??0),5)];
        }
        set_setting('heatmap_countries', json_encode($clean, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), $uid);
        $saved = 'countries';

    } elseif ($action === 'regions' && is_array($decoded)) {
        $clean = [];
        foreach ($decoded as $r) {
            $name = trim($r['name'] ?? '');
            if (!$name) continue;
            $clean[] = ['name'=>$name,'lat'=>round((float)($r['lat']??0),5),'lng'=>round((float)($r['lng']??0),5)];
        }
        set_setting('heatmap_regions', json_encode($clean, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), $uid);
        $saved = 'regions';

    } elseif ($action === 'categories' && is_array($decoded)) {
        $clean = [];
        foreach ($decoded as $cat) {
            $name = trim($cat['name'] ?? '');
            if (!$name) continue;
            // Component elements each carry their own 1-10 intensity score;
            // the category cell shows the average of them.
            $els = [];
            if (isset($cat['elements']) && is_array($cat['elements'])) {
                foreach ($cat['elements'] as $e) {
                    $e = trim((string)$e);
                    if ($e !== '' && !in_array($e, $els, true)) $els[] = $e;
                }
            }
            $clean[] = [
                'name'     => $name,
                'color'    => trim($cat['color'] ?? '#94a3b8'),
                'elements' => $els,
            ];
        }
        set_setting('heatmap_categories', json_encode($clean, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), $uid);
        $saved = 'categories';

    } elseif ($action === 'issue_map' && is_array($decoded)) {
        $clean = [];
        foreach ($decoded as $entry) {
            $from = trim($entry['from'] ?? '');
            $to   = trim($entry['to']   ?? '');
            if ($from && $to) $clean[] = ['from'=>$from,'to'=>$to];
        }
        set_setting('heatmap_issue_map', json_encode($clean, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), $uid);
        $saved = 'issue_map';

    } else {
        $saveErr = 'Invalid data — check your entries.';
    }
}

// ── Default datasets ──────────────────────────────────────────────────────
$defaultCountries = [
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

$defaultRegions = [
    ['name'=>'Nairobi',       'lat'=>-1.286, 'lng'=>36.820],
    ['name'=>'Mombasa',       'lat'=>-4.043, 'lng'=>39.668],
    ['name'=>'Kisumu',        'lat'=>-0.102, 'lng'=>34.761],
    ['name'=>'Nakuru',        'lat'=>-0.303, 'lng'=>36.080],
    ['name'=>'Eldoret',       'lat'=>0.517,  'lng'=>35.270],
    ['name'=>'Turkana',       'lat'=>3.119,  'lng'=>35.597],
    ['name'=>'Mandera',       'lat'=>3.936,  'lng'=>41.855],
    ['name'=>'Garissa',       'lat'=>-0.456, 'lng'=>39.646],
    ['name'=>'Rift Valley',   'lat'=>0.517,  'lng'=>35.270],
    ['name'=>'Nyanza',        'lat'=>-0.670, 'lng'=>34.779],
    ['name'=>'Western Kenya', 'lat'=>0.283,  'lng'=>34.752],
    ['name'=>'Central',       'lat'=>-0.700, 'lng'=>36.870],
    ['name'=>'North Eastern', 'lat'=>1.500,  'lng'=>40.000],
    ['name'=>'Coast',         'lat'=>-3.200, 'lng'=>40.120],
    ['name'=>'Addis Ababa',   'lat'=>9.025,  'lng'=>38.747],
    ['name'=>'Tigray',        'lat'=>14.032, 'lng'=>38.316],
    ['name'=>'Mekelle',       'lat'=>13.496, 'lng'=>39.477],
    ['name'=>'Amhara',        'lat'=>11.340, 'lng'=>37.983],
    ['name'=>'Oromia',        'lat'=>7.547,  'lng'=>39.647],
    ['name'=>'SNNPR',         'lat'=>6.875,  'lng'=>37.810],
    ['name'=>'Afar',          'lat'=>11.756, 'lng'=>41.173],
    ['name'=>'Dire Dawa',     'lat'=>9.593,  'lng'=>41.867],
    ['name'=>'Bahir Dar',     'lat'=>11.593, 'lng'=>37.391],
    ['name'=>'Harar',         'lat'=>9.313,  'lng'=>42.118],
    ['name'=>'Jimma',         'lat'=>7.678,  'lng'=>36.832],
    ['name'=>'Jijiga',        'lat'=>9.350,  'lng'=>42.795],
    ['name'=>'Kinshasa',      'lat'=>-4.322, 'lng'=>15.322],
    ['name'=>'Goma',          'lat'=>-1.678, 'lng'=>29.217],
    ['name'=>'Bukavu',        'lat'=>-2.508, 'lng'=>28.860],
    ['name'=>'North Kivu',    'lat'=>-0.786, 'lng'=>29.089],
    ['name'=>'South Kivu',    'lat'=>-3.381, 'lng'=>29.361],
    ['name'=>'Ituri',         'lat'=>1.583,  'lng'=>29.872],
    ['name'=>'Kisangani',     'lat'=>0.518,  'lng'=>25.196],
    ['name'=>'Lubumbashi',    'lat'=>-11.663,'lng'=>27.479],
    ['name'=>'Beni',          'lat'=>0.491,  'lng'=>29.473],
    ['name'=>'Katanga',       'lat'=>-10.000,'lng'=>27.000],
    ['name'=>'Kasai',         'lat'=>-5.000, 'lng'=>22.500],
    ['name'=>'Maniema',       'lat'=>-3.000, 'lng'=>26.500],
    ['name'=>'Bandundu',      'lat'=>-4.500, 'lng'=>18.000],
    ['name'=>'Juba',          'lat'=>4.859,  'lng'=>31.571],
    ['name'=>'Khartoum',      'lat'=>15.551, 'lng'=>32.532],
    ['name'=>'Mogadishu',     'lat'=>2.046,  'lng'=>45.341],
    ['name'=>'Kampala',       'lat'=>0.347,  'lng'=>32.583],
    ['name'=>'Dar es Salaam', 'lat'=>-6.792, 'lng'=>39.208],
    ['name'=>'Kigali',        'lat'=>-1.944, 'lng'=>30.061],
    ['name'=>'Bamako',        'lat'=>12.650, 'lng'=>-8.000],
    ['name'=>'Niamey',        'lat'=>13.513, 'lng'=>2.113],
    ['name'=>'Bangui',        'lat'=>4.361,  'lng'=>18.555],
    ['name'=>"N'Djamena",     'lat'=>12.105, 'lng'=>15.044],
    ['name'=>'Harare',        'lat'=>-17.828,'lng'=>31.053],
    ['name'=>'Lusaka',        'lat'=>-15.416,'lng'=>28.283],
];

$defaultCategories = [
    ['name'=>'Conflict & Security Watch',          'color'=>'#ED1C24', 'elements'=>['Armed conflict & security incidents','Armed group statements & activity','Civilian harm & protection']],
    ['name'=>'Peace & Political Track',            'color'=>'#750B25', 'elements'=>['Key peace & conflict developments','Peace initiatives & mediation','Government positions & actions']],
    ['name'=>'Humanitarian & Emergency Situation', 'color'=>'#E7952A', 'elements'=>['Humanitarian situation & access','Displacement & returns','Natural disasters & health emergencies']],
    ['name'=>'Actors & Institutional Engagement',  'color'=>'#3B82F6', 'elements'=>['International & regional actors','Religious institutions & civil society','Local governance & institutions']],
    ['name'=>'Early Warning Signals',              'color'=>'#65A30D', 'elements'=>['Escalation indicators','Social tension & hate speech','Resource & economic stress']],
];

$defaultIssueMap = [
    ['from'=>'Security & Conflict',         'to'=>'Security'],
    ['from'=>'Human Rights',                'to'=>'Human Rights'],
    ['from'=>'Health',                      'to'=>'Health'],
    ['from'=>'Policy & Governance',         'to'=>'Policy'],
    ['from'=>'Climate & Environment',       'to'=>'Climate'],
    ['from'=>'Education',                   'to'=>'Education'],
    ['from'=>'Agriculture & Food Security', 'to'=>'Agriculture'],
    ['from'=>'Migration & Refugees',        'to'=>'Displacement'],
    ['from'=>'Economic Development',        'to'=>'Policy'],
    ['from'=>'Technology & Innovation',     'to'=>'Policy'],
    ['from'=>'Gender & Social Affairs',     'to'=>'Human Rights'],
    ['from'=>'Infrastructure & Energy',     'to'=>'Policy'],
];

// Load from DB with fallbacks
$countries  = json_decode(get_setting('heatmap_countries',  ''), true) ?: $defaultCountries;
$regions    = json_decode(get_setting('heatmap_regions',    ''), true) ?: $defaultRegions;
$categories = json_decode(get_setting('heatmap_categories', ''), true) ?: $defaultCategories;

$issueMapRaw = json_decode(get_setting('heatmap_issue_map', ''), true);
if (is_array($issueMapRaw) && !empty($issueMapRaw)) {
    // Normalize: accept both [{from,to}] and {key:value} formats
    $first = reset($issueMapRaw);
    $issueMap = is_array($first) ? $issueMapRaw : array_map(
        fn($k, $v) => ['from'=>$k,'to'=>$v],
        array_keys($issueMapRaw), $issueMapRaw
    );
} else {
    $issueMap = $defaultIssueMap;
}

$pageTitle      = 'Heatmap Configuration | Tafakari Admin';
$adminPageTitle = 'Heatmap Configuration';
$adminPageSub   = 'Manage countries, regions and categories for the conflict monitoring map';
?>
<?php include dirname(__DIR__, 2) . '/includes/head.php'; ?>
<body class="antialiased font-inter" style="background:#F4F6F8">
<div class="flex h-screen overflow-hidden">
<?php include dirname(__DIR__, 2) . '/includes/admin-sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden min-w-0">
<?php include dirname(__DIR__, 2) . '/includes/admin-topbar.php'; ?>

<main class="flex-1 overflow-y-auto p-6 md:p-8">

<?php if ($saved): ?>
<div id="save-toast" style="position:fixed;bottom:24px;right:24px;z-index:9999;background:#0D0102;color:#fff;font-size:13px;font-weight:600;padding:12px 20px;border-radius:50px;border:1px solid rgba(255,255,255,.1);box-shadow:0 8px 32px rgba(0,0,0,.25);display:flex;align-items:center;gap:8px">
  <svg width="14" height="14" fill="none" stroke="#10B981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
  <?= ['countries'=>'Countries saved','regions'=>'Regions saved','categories'=>'Categories saved','issue_map'=>'Issue mapping saved'][$saved] ?? 'Saved' ?>
</div>
<script>setTimeout(function(){ var t=document.getElementById('save-toast'); if(t){t.style.transition='opacity .4s';t.style.opacity='0';} }, 2500);</script>
<?php endif; ?>
<?php if ($saveErr): ?>
<div class="mb-5 px-4 py-3 rounded-2xl text-sm font-medium text-red-800 bg-red-50 border border-red-200"><?= h($saveErr) ?></div>
<?php endif; ?>

<div class="mb-6">
  <h1 class="font-outfit font-black text-2xl text-slate-900">Heatmap Configuration</h1>
  <p class="text-sm text-slate-400 mt-1">Manage countries, regions and conflict categories shown on the public monitoring map</p>
</div>

<!-- Tab bar -->
<div class="flex gap-1 mb-6 p-1 rounded-2xl w-fit" style="background:rgba(0,0,0,.06)">
  <?php
  $tabs = [
    'countries'  => 'Countries ('  . count($countries)  . ')',
    'regions'    => 'Regions ('    . count($regions)    . ')',
    'categories' => 'Categories (' . count($categories) . ')',
    'issue_map'  => 'Issue Mapping (' . count($issueMap) . ')',
  ];
  foreach ($tabs as $id => $label): ?>
    <button id="tab-btn-<?= $id ?>" onclick="showTab('<?= $id ?>')"
            class="px-4 py-2 rounded-xl text-sm font-bold transition-all whitespace-nowrap"
            style="color:#64748b">
      <?= h($label) ?>
    </button>
  <?php endforeach; ?>
</div>

<!-- ── COUNTRIES ────────────────────────────────────────────────── -->
<div id="tab-countries" class="tab-panel">
  <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
      <div>
        <h2 class="font-outfit font-black text-base text-slate-900">Countries</h2>
        <p class="text-xs text-slate-400">Used for coordinate lookup and the heatmap country filter</p>
      </div>
      <div class="flex gap-2">
        <button onclick="resetTab('countries')"
                class="px-3 py-2 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-100 transition-colors">
          Reset Defaults
        </button>
        <button onclick="addRow('countries')"
                class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white"
                style="background:#750B25">
          <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
          Add Country
        </button>
      </div>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm" style="border-collapse:collapse">
        <thead style="background:#f8fafc">
          <tr>
            <th class="text-left px-4 py-2.5 text-[10px] font-black uppercase tracking-wider text-slate-400">Country Name</th>
            <th class="text-left px-4 py-2.5 text-[10px] font-black uppercase tracking-wider text-slate-400 w-20">ISO Code</th>
            <th class="text-left px-4 py-2.5 text-[10px] font-black uppercase tracking-wider text-slate-400 w-16">Flag</th>
            <th class="text-left px-4 py-2.5 text-[10px] font-black uppercase tracking-wider text-slate-400 w-28">Latitude</th>
            <th class="text-left px-4 py-2.5 text-[10px] font-black uppercase tracking-wider text-slate-400 w-28">Longitude</th>
            <th class="w-12"></th>
          </tr>
        </thead>
        <tbody id="countries-tbody"></tbody>
      </table>
    </div>
    <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between">
      <span id="countries-count" class="text-xs text-slate-400"></span>
      <form method="POST" onsubmit="return serializeTab('countries')">
        <input type="hidden" name="action" value="countries">
        <input type="hidden" name="countries_json" id="countries-json">
        <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-bold text-white hover:opacity-90 transition-opacity" style="background:#750B25">
          Save Countries
        </button>
      </form>
    </div>
  </div>
</div>

<!-- ── REGIONS ──────────────────────────────────────────────────── -->
<div id="tab-regions" class="tab-panel hidden">
  <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
      <div>
        <h2 class="font-outfit font-black text-base text-slate-900">Regions &amp; Cities</h2>
        <p class="text-xs text-slate-400">Matched against the post <code class="text-xs bg-slate-100 px-1 rounded">region</code> field for precise pin placement</p>
      </div>
      <div class="flex gap-2">
        <button onclick="resetTab('regions')" class="px-3 py-2 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-100 transition-colors">Reset Defaults</button>
        <button onclick="addRow('regions')" class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white" style="background:#750B25">
          <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
          Add Region
        </button>
      </div>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm" style="border-collapse:collapse">
        <thead style="background:#f8fafc">
          <tr>
            <th class="text-left px-4 py-2.5 text-[10px] font-black uppercase tracking-wider text-slate-400">Region / City Name</th>
            <th class="text-left px-4 py-2.5 text-[10px] font-black uppercase tracking-wider text-slate-400 w-32">Latitude</th>
            <th class="text-left px-4 py-2.5 text-[10px] font-black uppercase tracking-wider text-slate-400 w-32">Longitude</th>
            <th class="w-12"></th>
          </tr>
        </thead>
        <tbody id="regions-tbody"></tbody>
      </table>
    </div>
    <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between">
      <span id="regions-count" class="text-xs text-slate-400"></span>
      <form method="POST" onsubmit="return serializeTab('regions')">
        <input type="hidden" name="action" value="regions">
        <input type="hidden" name="regions_json" id="regions-json">
        <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-bold text-white hover:opacity-90 transition-opacity" style="background:#750B25">
          Save Regions
        </button>
      </form>
    </div>
  </div>
</div>

<!-- ── CATEGORIES ───────────────────────────────────────────────── -->
<div id="tab-categories" class="tab-panel hidden">
  <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
      <div>
        <h2 class="font-outfit font-black text-base text-slate-900">Conflict Categories</h2>
        <p class="text-xs text-slate-400">Shown in the map legend and filter checkboxes</p>
      </div>
      <div class="flex gap-2">
        <button onclick="resetTab('categories')" class="px-3 py-2 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-100 transition-colors">Reset Defaults</button>
        <button onclick="addRow('categories')" class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white" style="background:#750B25">
          <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
          Add Category
        </button>
      </div>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm" style="border-collapse:collapse">
        <thead style="background:#f8fafc">
          <tr>
            <th class="text-left px-4 py-2.5 text-[10px] font-black uppercase tracking-wider text-slate-400 w-64">Category Name</th>
            <th class="text-left px-4 py-2.5 text-[10px] font-black uppercase tracking-wider text-slate-400">Component Elements <span class="normal-case font-semibold text-slate-300">(one per line — each scored 1–10)</span></th>
            <th class="text-left px-4 py-2.5 text-[10px] font-black uppercase tracking-wider text-slate-400 w-44">Color</th>
            <th class="text-left px-4 py-2.5 text-[10px] font-black uppercase tracking-wider text-slate-400 w-16">Preview</th>
            <th class="w-12"></th>
          </tr>
        </thead>
        <tbody id="categories-tbody"></tbody>
      </table>
    </div>
    <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between">
      <span id="categories-count" class="text-xs text-slate-400"></span>
      <form method="POST" onsubmit="return serializeTab('categories')">
        <input type="hidden" name="action" value="categories">
        <input type="hidden" name="categories_json" id="categories-json">
        <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-bold text-white hover:opacity-90 transition-opacity" style="background:#750B25">
          Save Categories
        </button>
      </form>
    </div>
  </div>
</div>

<!-- ── ISSUE MAPPING ─────────────────────────────────────────────── -->
<div id="tab-issue_map" class="tab-panel hidden">
  <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
      <div>
        <h2 class="font-outfit font-black text-base text-slate-900">Issue Category Mapping</h2>
        <p class="text-xs text-slate-400">Maps the post <code class="text-xs bg-slate-100 px-1 rounded">issueCategory</code> DB value → heatmap category</p>
      </div>
      <div class="flex gap-2">
        <button onclick="resetTab('issue_map')" class="px-3 py-2 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-100 transition-colors">Reset Defaults</button>
        <button onclick="addRow('issue_map')" class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white" style="background:#750B25">
          <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
          Add Mapping
        </button>
      </div>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm" style="border-collapse:collapse">
        <thead style="background:#f8fafc">
          <tr>
            <th class="text-left px-4 py-2.5 text-[10px] font-black uppercase tracking-wider text-slate-400">Post issueCategory (DB value)</th>
            <th class="text-left px-4 py-2.5 text-[10px] font-black uppercase tracking-wider text-slate-400">→ Heatmap Category</th>
            <th class="w-12"></th>
          </tr>
        </thead>
        <tbody id="issue_map-tbody"></tbody>
      </table>
    </div>
    <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between">
      <span class="text-xs text-slate-400">Unmatched issueCategories fall back to "Policy"</span>
      <form method="POST" onsubmit="return serializeTab('issue_map')">
        <input type="hidden" name="action" value="issue_map">
        <input type="hidden" name="issue_map_json" id="issue_map-json">
        <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-bold text-white hover:opacity-90 transition-opacity" style="background:#750B25">
          Save Mapping
        </button>
      </form>
    </div>
  </div>
</div>

<!-- Hidden datalist for category autocomplete -->
<datalist id="cat-names-dl"></datalist>

</main>
</div>
</div>

<style>
.cell-input {
  width: 100%;
  padding: 5px 9px;
  border: 1px solid #e2e8f0;
  border-radius: 7px;
  font-size: 12px;
  color: #1e293b;
  background: #f8fafc;
  transition: border-color .15s;
}
.cell-input:focus { outline: none; border-color: #750B25; background: #fff; }
tbody tr { border-bottom: 1px solid #f1f5f9; }
tbody tr:hover { background: #fafafa; }
.del-btn {
  width: 28px; height: 28px;
  border: 1px solid #fecaca; border-radius: 7px;
  background: #fff; color: #dc2626;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  transition: background .15s;
}
.del-btn:hover { background: #fef2f2; }
</style>

<script>
var DB = {
  countries:  <?= json_encode($countries,  JSON_UNESCAPED_UNICODE) ?>,
  regions:    <?= json_encode($regions,    JSON_UNESCAPED_UNICODE) ?>,
  categories: <?= json_encode($categories, JSON_UNESCAPED_UNICODE) ?>,
  issue_map:  <?= json_encode($issueMap,   JSON_UNESCAPED_UNICODE) ?>,
};
var DEFAULTS = {
  countries:  <?= json_encode($defaultCountries,  JSON_UNESCAPED_UNICODE) ?>,
  regions:    <?= json_encode($defaultRegions,    JSON_UNESCAPED_UNICODE) ?>,
  categories: <?= json_encode($defaultCategories, JSON_UNESCAPED_UNICODE) ?>,
  issue_map:  <?= json_encode($defaultIssueMap,   JSON_UNESCAPED_UNICODE) ?>,
};

function esc(s) {
  return String(s ?? '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function ci(val, ph, type) {
  var t = type || 'text';
  var extra = t === 'number' ? ' step="any"' : '';
  return '<input type="' + t + '" class="cell-input" placeholder="' + esc(ph) + '" value="' + esc(val ?? '') + '"' + extra + '>';
}
function delBtn(tab, i) {
  return '<button type="button" class="del-btn" onclick="removeRow(\'' + tab + '\',' + i + ')" title="Remove">'
       + '<svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>'
       + '</button>';
}

/* ── Tab switching ──────────────────────────────────────────────── */
function showTab(name) {
  document.querySelectorAll('.tab-panel').forEach(function(p){ p.classList.add('hidden'); });
  document.querySelectorAll('[id^="tab-btn-"]').forEach(function(b){
    b.style.background = ''; b.style.color = '#64748b';
  });
  var panel = document.getElementById('tab-' + name);
  if (panel) panel.classList.remove('hidden');
  var btn = document.getElementById('tab-btn-' + name);
  if (btn) { btn.style.background = '#750B25'; btn.style.color = '#fff'; }
}

/* ── Renderers ──────────────────────────────────────────────────── */
function renderCountries() {
  var tbody = document.getElementById('countries-tbody');
  tbody.innerHTML = DB.countries.map(function(c, i) {
    return '<tr>'
      + '<td class="px-4 py-2">' + ci(c.name, 'Country name') + '</td>'
      + '<td class="px-4 py-2">' + ci(c.code, 'KE') + '</td>'
      + '<td class="px-4 py-2">' + ci(c.flag, '🏳') + '</td>'
      + '<td class="px-4 py-2">' + ci(c.lat,  '0.000', 'number') + '</td>'
      + '<td class="px-4 py-2">' + ci(c.lng,  '0.000', 'number') + '</td>'
      + '<td class="px-4 py-2">' + delBtn('countries', i) + '</td>'
      + '</tr>';
  }).join('');
  document.getElementById('countries-count').textContent = DB.countries.length + ' countries';
}

function renderRegions() {
  var tbody = document.getElementById('regions-tbody');
  tbody.innerHTML = DB.regions.map(function(r, i) {
    return '<tr>'
      + '<td class="px-4 py-2">' + ci(r.name, 'Region or city') + '</td>'
      + '<td class="px-4 py-2">' + ci(r.lat,  '0.000', 'number') + '</td>'
      + '<td class="px-4 py-2">' + ci(r.lng,  '0.000', 'number') + '</td>'
      + '<td class="px-4 py-2">' + delBtn('regions', i) + '</td>'
      + '</tr>';
  }).join('');
  document.getElementById('regions-count').textContent = DB.regions.length + ' regions';
}

function renderCategories() {
  var tbody = document.getElementById('categories-tbody');
  tbody.innerHTML = DB.categories.map(function(cat, i) {
    var col = esc(cat.color || '#94a3b8');
    return '<tr>'
      + '<td class="px-4 py-2 align-top">' + ci(cat.name, 'Category name') + '</td>'
      + '<td class="px-4 py-2 align-top">'
      +   '<textarea class="cat-elements cell-input" rows="3" placeholder="One element per line"'
      +   ' style="width:100%;resize:vertical;line-height:1.5;font-family:inherit">'
      +   esc((cat.elements || []).join('\n')) + '</textarea>'
      +   '<p style="font-size:10px;color:#94a3b8;margin:3px 0 0">'
      +   'Category score = average of these elements</p>'
      + '</td>'
      + '<td class="px-4 py-2 align-top"><div style="display:flex;gap:8px;align-items:center">'
      +   '<input type="color" value="' + col + '" style="width:36px;height:32px;border-radius:7px;border:1px solid #e2e8f0;padding:2px;cursor:pointer" oninput="syncColor(this)">'
      +   '<input type="text" class="cell-input hex-input" value="' + col + '" placeholder="#rrggbb" maxlength="7" oninput="syncHex(this)" style="flex:1">'
      + '</div></td>'
      + '<td class="px-4 py-2 align-top"><div class="color-swatch" style="width:28px;height:28px;border-radius:7px;background:' + col + ';border:1px solid rgba(0,0,0,.1)"></div></td>'
      + '<td class="px-4 py-2 align-top">' + delBtn('categories', i) + '</td>'
      + '</tr>';
  }).join('');
  document.getElementById('categories-count').textContent = DB.categories.length + ' categories';
}

function renderIssueMap() {
  var catNames = DB.categories.map(function(c){ return c.name; });
  var dl = document.getElementById('cat-names-dl');
  dl.innerHTML = catNames.map(function(n){ return '<option value="' + esc(n) + '">'; }).join('');

  var tbody = document.getElementById('issue_map-tbody');
  tbody.innerHTML = DB.issue_map.map(function(m, i) {
    return '<tr>'
      + '<td class="px-4 py-2">' + ci(m.from, 'e.g. Security & Conflict') + '</td>'
      + '<td class="px-4 py-2"><input type="text" class="cell-input" list="cat-names-dl" placeholder="Heatmap category" value="' + esc(m.to) + '"></td>'
      + '<td class="px-4 py-2">' + delBtn('issue_map', i) + '</td>'
      + '</tr>';
  }).join('');
}

function renderTab(tab) {
  if (tab === 'countries')  renderCountries();
  if (tab === 'regions')    renderRegions();
  if (tab === 'categories') { renderCategories(); renderIssueMap(); }
  if (tab === 'issue_map')  renderIssueMap();
}

/* ── Color sync ─────────────────────────────────────────────────── */
function syncColor(picker) {
  var tr   = picker.closest('tr');
  var hex  = tr.querySelector('.hex-input');
  var sw   = tr.querySelector('.color-swatch');
  if (hex) hex.value = picker.value;
  if (sw)  sw.style.background = picker.value;
}
function syncHex(hexInput) {
  var v  = hexInput.value;
  var tr = hexInput.closest('tr');
  var cp = tr.querySelector('input[type="color"]');
  var sw = tr.querySelector('.color-swatch');
  if (/^#[0-9A-Fa-f]{6}$/.test(v)) {
    if (cp) cp.value = v;
    if (sw) sw.style.background = v;
  }
}

/* ── Add / remove rows ──────────────────────────────────────────── */
var emptyRow = {
  countries: {name:'',code:'',flag:'',lat:0,lng:0},
  regions:   {name:'',lat:0,lng:0},
  categories:{name:'',color:'#94a3b8',elements:[]},
  issue_map: {from:'',to:''},
};
function addRow(tab) {
  DB[tab].push(JSON.parse(JSON.stringify(emptyRow[tab])));
  renderTab(tab);
  var tbody = document.getElementById(tab + '-tbody');
  if (tbody && tbody.lastElementChild) {
    var inp = tbody.lastElementChild.querySelector('input');
    if (inp) { inp.focus(); inp.scrollIntoView({block:'nearest'}); }
  }
}
function removeRow(tab, i) {
  DB[tab].splice(i, 1);
  renderTab(tab);
}
function resetTab(tab) {
  if (!confirm('Reset to built-in defaults? Unsaved changes will be lost.')) return;
  DB[tab] = JSON.parse(JSON.stringify(DEFAULTS[tab]));
  renderTab(tab);
}

/* ── Serialize on submit ────────────────────────────────────────── */
function serializeTab(tab) {
  var arr  = [];
  var rows = document.querySelectorAll('#' + tab + '-tbody tr');
  rows.forEach(function(tr) {
    var inputs = Array.from(tr.querySelectorAll('input:not([type="color"])'));
    if (tab === 'countries') {
      arr.push({name:inputs[0].value.trim(), code:inputs[1].value.trim().toUpperCase(), flag:inputs[2].value.trim(), lat:parseFloat(inputs[3].value)||0, lng:parseFloat(inputs[4].value)||0});
    } else if (tab === 'regions') {
      arr.push({name:inputs[0].value.trim(), lat:parseFloat(inputs[1].value)||0, lng:parseFloat(inputs[2].value)||0});
    } else if (tab === 'categories') {
      var cp = tr.querySelector('input[type="color"]');
      var ta = tr.querySelector('textarea.cat-elements');
      var els = ta ? ta.value.split('\n').map(function(x){ return x.trim(); }).filter(function(x){ return x !== ''; }) : [];
      arr.push({
        name: inputs[0].value.trim(),
        color: inputs[1].value.trim() || (cp ? cp.value : '#94a3b8'),
        elements: els
      });
    } else if (tab === 'issue_map') {
      arr.push({from:inputs[0].value.trim(), to:inputs[1].value.trim()});
    }
  });
  document.getElementById(tab + '-json').value = JSON.stringify(arr);
  return true;
}

/* ── Init ───────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function() {
  renderCountries();
  renderRegions();
  renderCategories();
  renderIssueMap();
  showTab('countries');
});
</script>

</body>
</html>
