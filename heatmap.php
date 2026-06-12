<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$totalReports  = 0;
$monthlyReports = 0;
try {
    $totalReports   = (int) db()->query("SELECT COUNT(*) FROM Post WHERE status='PUBLISHED'")->fetchColumn();
    $monthlyReports = (int) db()->query("SELECT COUNT(*) FROM Post WHERE status='PUBLISHED' AND createdAt >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
} catch (Exception $e) { /* ignore */ }

$pageTitle = 'Regional Issue Tracker | Tafakari Digital Hub';
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body class="antialiased min-h-screen flex flex-col bg-slate-50 font-inter">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="flex-grow max-w-7xl mx-auto px-6 py-16">
  <div class="mb-10">
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full mb-4 text-xs font-black uppercase tracking-widest" style="background:rgba(231,149,42,.12);color:#C47C1A;border:1px solid rgba(231,149,42,.3)">Live Data</div>
    <h1 class="font-outfit text-4xl font-black text-slate-900">Regional Issue Tracker</h1>
    <p class="text-slate-500 mt-2 max-w-2xl">Visualizing conflict intensity and reported issues across African nations experiencing active fragility, displacement, and humanitarian crises.</p>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
    <!-- Controls -->
    <div class="lg:col-span-1 space-y-6">
      <div class="bg-white rounded-3xl border border-slate-100 p-6 shadow-sm">
        <h3 class="font-outfit font-bold text-base mb-4 text-slate-900">Country Filter</h3>
        <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
          <?php
          $countries = [
            'ALL' => 'All Countries',
            'KE'  => 'Kenya',
            'ET'  => 'Ethiopia',
            'CD'  => 'DR Congo',
            'SS'  => 'South Sudan',
            'SD'  => 'Sudan',
            'MZ'  => 'Mozambique',
            'BF'  => 'Burkina Faso',
            'SO'  => 'Somalia',
            'ML'  => 'Mali',
            'NE'  => 'Niger',
            'CF'  => 'Central African Rep.',
            'TD'  => 'Chad',
            'NG'  => 'Nigeria (NE)',
            'CM'  => 'Cameroon',
          ];
          foreach ($countries as $code => $label): ?>
            <label class="flex items-center gap-3 cursor-pointer py-0.5">
              <input type="radio" name="country" value="<?= h($code) ?>" <?= $code === 'ALL' ? 'checked' : '' ?>
                     class="accent-secondary" onchange="filterMap()">
              <span class="text-sm font-medium text-slate-700"><?= h($label) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="bg-white rounded-3xl border border-slate-100 p-6 shadow-sm">
        <h3 class="font-outfit font-bold text-base mb-4 text-slate-900">Categories</h3>
        <div class="space-y-2">
          <?php foreach (['Health','Education','Security','Climate','Human Rights','Policy','Agriculture','Displacement'] as $cat): ?>
            <label class="flex items-center gap-3 cursor-pointer">
              <input type="checkbox" value="<?= h($cat) ?>" checked class="category-check accent-secondary" onchange="filterMap()">
              <span class="text-sm text-slate-600"><?= h($cat) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Intensity legend -->
      <div class="bg-white rounded-3xl border border-slate-100 p-6 shadow-sm">
        <h3 class="font-outfit font-bold text-base mb-4 text-slate-900">Intensity Scale</h3>
        <div class="space-y-2.5">
          <div class="flex items-center gap-3">
            <span class="w-4 h-4 rounded-full shrink-0" style="background:#ED1C24"></span>
            <span class="text-xs text-slate-600">High (8–10) &mdash; Active conflict</span>
          </div>
          <div class="flex items-center gap-3">
            <span class="w-4 h-4 rounded-full shrink-0" style="background:#E7952A"></span>
            <span class="text-xs text-slate-600">Medium (5–7) &mdash; Elevated risk</span>
          </div>
          <div class="flex items-center gap-3">
            <span class="w-4 h-4 rounded-full shrink-0" style="background:#F4C87E"></span>
            <span class="text-xs text-slate-600">Low (1–4) &mdash; Emerging concern</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Map -->
    <div class="lg:col-span-3">
      <div id="map" class="w-full rounded-3xl border border-slate-100 shadow-sm overflow-hidden" style="height:560px"></div>
    </div>
  </div>

  <!-- Stats -->
  <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-8">
    <div class="bg-white rounded-3xl border border-slate-100 p-6 shadow-sm text-center">
      <div class="text-3xl font-outfit font-black" style="color:#750B25"><?= format_number($totalReports) ?></div>
      <div class="text-xs font-black uppercase tracking-widest text-slate-400 mt-1">Total Validated Reports</div>
    </div>
    <div class="bg-white rounded-3xl border border-slate-100 p-6 shadow-sm text-center">
      <div class="text-3xl font-outfit font-black" style="color:#E7952A"><?= format_number($monthlyReports) ?></div>
      <div class="text-xs font-black uppercase tracking-widest text-slate-400 mt-1">New Reports This Month</div>
    </div>
    <div class="bg-white rounded-3xl border border-slate-100 p-6 shadow-sm text-center">
      <div class="text-3xl font-outfit font-black text-slate-700">15</div>
      <div class="text-xs font-black uppercase tracking-widest text-slate-400 mt-1">Countries Monitored</div>
    </div>
    <div class="bg-white rounded-3xl border border-slate-100 p-6 shadow-sm text-center">
      <div class="text-3xl font-outfit font-black text-slate-700">24h</div>
      <div class="text-xs font-black uppercase tracking-widest text-slate-400 mt-1">Data Refresh Cycle</div>
    </div>
  </div>
</main>

<script>
var mapData = [
  // ── Kenya ──────────────────────────────────────────────────────
  {lat:1.286,  lng:36.817, name:"Nairobi",     country:"KE", category:"Health",       intensity:8,  desc:"Urban health services strain and informal settlement conditions"},
  {lat:-1.286, lng:36.823, name:"Kiambu",      country:"KE", category:"Education",    intensity:6,  desc:"School access challenges in peri-urban areas"},
  {lat:-4.043, lng:39.668, name:"Mombasa",     country:"KE", category:"Security",     intensity:7,  desc:"Coastal security incidents and radicalization concerns"},
  {lat:0.517,  lng:35.270, name:"Eldoret",     country:"KE", category:"Agriculture",  intensity:5,  desc:"Crop failure due to drought in North Rift"},
  {lat:-0.102, lng:34.761, name:"Kisumu",      country:"KE", category:"Health",       intensity:7,  desc:"Malaria and waterborne disease prevalence"},
  {lat:0.283,  lng:37.454, name:"Meru",        country:"KE", category:"Climate",      intensity:6,  desc:"Deforestation and water catchment concerns"},
  {lat:-0.670, lng:37.147, name:"Thika",       country:"KE", category:"Policy",       intensity:4,  desc:"Land use disputes and urban expansion"},
  {lat:3.119,  lng:35.597, name:"Turkana",     country:"KE", category:"Human Rights", intensity:9,  desc:"Pastoralist resource conflict and drought displacement"},
  {lat:1.750,  lng:41.120, name:"Mandera",     country:"KE", category:"Security",     intensity:8,  desc:"Cross-border insecurity and Al-Shabaab incursions"},

  // ── Ethiopia ───────────────────────────────────────────────────
  {lat:9.024,  lng:38.747, name:"Addis Ababa", country:"ET", category:"Policy",       intensity:7,  desc:"Political reforms and governance transition"},
  {lat:14.495, lng:39.475, name:"Mekelle",     country:"ET", category:"Human Rights", intensity:9,  desc:"Post-conflict humanitarian situation in Tigray"},
  {lat:9.593,  lng:41.867, name:"Dire Dawa",   country:"ET", category:"Security",     intensity:6,  desc:"Inter-ethnic tensions and border security"},
  {lat:7.678,  lng:36.832, name:"Hawassa",     country:"ET", category:"Education",    intensity:5,  desc:"Educational infrastructure gaps in SNNPR"},
  {lat:11.593, lng:37.391, name:"Bahir Dar",   country:"ET", category:"Climate",      intensity:7,  desc:"Lake Tana water levels and agricultural stress"},
  {lat:9.313,  lng:42.118, name:"Jijiga",      country:"ET", category:"Health",       intensity:8,  desc:"Healthcare access in Somali region"},
  {lat:8.550,  lng:39.268, name:"Adama",       country:"ET", category:"Agriculture",  intensity:5,  desc:"Irrigation infrastructure challenges"},
  {lat:6.033,  lng:37.559, name:"Amhara West", country:"ET", category:"Security",     intensity:9,  desc:"Fano militia activity and displacement"},

  // ── DR Congo ───────────────────────────────────────────────────
  {lat:-4.322, lng:15.322, name:"Kinshasa",    country:"CD", category:"Policy",       intensity:7,  desc:"Urban governance and political instability"},
  {lat:-1.678, lng:29.217, name:"Goma",        country:"CD", category:"Security",     intensity:10, desc:"M23 armed conflict and mass displacement"},
  {lat:-11.663,lng:27.479, name:"Lubumbashi",  country:"CD", category:"Human Rights", intensity:8,  desc:"Mining sector human rights abuses"},
  {lat:-3.381, lng:29.361, name:"Bukavu",      country:"CD", category:"Health",       intensity:8,  desc:"Healthcare crisis and gender-based violence"},
  {lat:4.316,  lng:18.033, name:"Kisangani",   country:"CD", category:"Education",    intensity:6,  desc:"Education access in Orientale province"},
  {lat:-2.499, lng:28.860, name:"Uvira",       country:"CD", category:"Displacement", intensity:9,  desc:"Flooding and armed group displacement"},
  {lat:0.518,  lng:25.196, name:"Beni",        country:"CD", category:"Security",     intensity:10, desc:"ADF militia attacks and civilian casualties"},

  // ── South Sudan ────────────────────────────────────────────────
  {lat:4.859,  lng:31.571, name:"Juba",        country:"SS", category:"Policy",       intensity:8,  desc:"Political fragility and peace deal implementation"},
  {lat:7.699,  lng:28.031, name:"Wau",         country:"SS", category:"Displacement", intensity:9,  desc:"Intercommunal violence and IDP camps"},
  {lat:9.533,  lng:31.661, name:"Malakal",     country:"SS", category:"Security",     intensity:9,  desc:"Upper Nile conflict and UNMISS protection site"},
  {lat:9.209,  lng:29.796, name:"Bentiu",      country:"SS", category:"Health",       intensity:8,  desc:"Flooding, cholera outbreaks and food insecurity"},
  {lat:8.490,  lng:30.660, name:"Rumbek",      country:"SS", category:"Human Rights", intensity:7,  desc:"Cattle raiding and gender-based violence"},

  // ── Sudan ──────────────────────────────────────────────────────
  {lat:15.551, lng:32.532, name:"Khartoum",    country:"SD", category:"Security",     intensity:10, desc:"RSF–SAF urban warfare and civilian mass casualties"},
  {lat:13.634, lng:25.349, name:"El Fasher",   country:"SD", category:"Human Rights", intensity:10, desc:"Darfur genocide warnings and siege conditions"},
  {lat:19.616, lng:37.217, name:"Port Sudan",  country:"SD", category:"Displacement", intensity:7,  desc:"IDP influx and humanitarian bottleneck"},
  {lat:12.861, lng:30.217, name:"Sennar",      country:"SD", category:"Health",       intensity:6,  desc:"Healthcare collapse and disease outbreak"},
  {lat:11.459, lng:27.912, name:"Nyala",       country:"SD", category:"Security",     intensity:9,  desc:"South Darfur ethnic violence and looting"},

  // ── Mozambique ─────────────────────────────────────────────────
  {lat:-25.891,lng:32.605, name:"Maputo",      country:"MZ", category:"Policy",       intensity:5,  desc:"Governance challenges and post-election tensions"},
  {lat:-11.706,lng:40.513, name:"Pemba",       country:"MZ", category:"Security",     intensity:9,  desc:"Cabo Delgado insurgency — Ansar al-Sunna jihadist attacks"},
  {lat:-13.368,lng:40.337, name:"Mocímboa",    country:"MZ", category:"Displacement", intensity:10, desc:"Insurgent occupation and mass displacement"},
  {lat:-15.116,lng:39.268, name:"Nampula",     country:"MZ", category:"Agriculture",  intensity:6,  desc:"Food insecurity and climate stress in north"},
  {lat:-19.823,lng:34.838, name:"Beira",       country:"MZ", category:"Climate",      intensity:7,  desc:"Cyclone Idai legacy: infrastructure and recovery gaps"},

  // ── Burkina Faso ───────────────────────────────────────────────
  {lat:12.362, lng:-1.534, name:"Ouagadougou", country:"BF", category:"Security",     intensity:9,  desc:"Junta rule, jihadist attacks and press freedom crisis"},
  {lat:13.450, lng:-0.900, name:"Kaya",        country:"BF", category:"Displacement", intensity:9,  desc:"Sahel region displacement — over 2 million IDPs"},
  {lat:14.300, lng:-0.056, name:"Dori",        country:"BF", category:"Human Rights", intensity:10, desc:"JNIM siege of Dori — humanitarian access blocked"},
  {lat:11.177, lng:-4.297, name:"Bobo-Dioulasso",country:"BF",category:"Security",    intensity:7,  desc:"Southern security deterioration and military incidents"},

  // ── Somalia ────────────────────────────────────────────────────
  {lat:2.046,  lng:45.341, name:"Mogadishu",   country:"SO", category:"Security",     intensity:9,  desc:"Al-Shabaab urban attacks and political instability"},
  {lat:-0.359, lng:42.545, name:"Kismayo",     country:"SO", category:"Human Rights", intensity:8,  desc:"Clan conflict and displacement in Jubbaland"},
  {lat:11.284, lng:49.183, name:"Bosaso",      country:"SO", category:"Security",     intensity:7,  desc:"Puntland ISIS activity and piracy networks"},
  {lat:2.044,  lng:45.341, name:"Lower Shabelle",country:"SO",category:"Displacement",intensity:9,  desc:"Al-Shabaab territorial control and civilian displacement"},

  // ── Mali ───────────────────────────────────────────────────────
  {lat:12.650, lng:-8.000, name:"Bamako",      country:"ML", category:"Policy",       intensity:7,  desc:"Military junta governance and anti-Western posture"},
  {lat:16.270, lng:-0.042, name:"Gao",         country:"ML", category:"Security",     intensity:9,  desc:"JNIM and IS Sahel armed group presence in Gao region"},
  {lat:20.000, lng:-1.700, name:"Kidal",       country:"ML", category:"Security",     intensity:10, desc:"Tuareg CMA–Wagner conflict and loss of army control"},
  {lat:16.770, lng:-3.008, name:"Timbuktu",    country:"ML", category:"Displacement", intensity:8,  desc:"Jihadist blockade of Timbuktu and mass civilian flight"},

  // ── Niger ──────────────────────────────────────────────────────
  {lat:13.513, lng:2.113,  name:"Niamey",      country:"NE", category:"Policy",       intensity:8,  desc:"Post-coup junta instability and ECOWAS standoff"},
  {lat:16.964, lng:7.994,  name:"Agadez",      country:"NE", category:"Security",     intensity:8,  desc:"Sahara smuggling routes and IS Sahel recruitment"},
  {lat:14.212, lng:1.458,  name:"Tillabéri",   country:"NE", category:"Security",     intensity:9,  desc:"Tri-border area jihadist attacks on civilians"},

  // ── Central African Republic ───────────────────────────────────
  {lat:4.361,  lng:18.555, name:"Bangui",      country:"CF", category:"Security",     intensity:8,  desc:"Wagner-backed government and anti-Balaka/CPC conflict"},
  {lat:5.771,  lng:20.680, name:"Bambari",     country:"CF", category:"Human Rights", intensity:9,  desc:"UPC militia control and severe humanitarian crisis"},
  {lat:8.582,  lng:16.074, name:"Ndélé",       country:"CF", category:"Displacement", intensity:7,  desc:"FPRC armed group presence and displacement"},

  // ── Chad ───────────────────────────────────────────────────────
  {lat:12.105, lng:15.044, name:"N'Djamena",   country:"TD", category:"Policy",       intensity:7,  desc:"Post-Déby transitional instability and rebel activity"},
  {lat:13.017, lng:13.450, name:"Lac Chad",    country:"TD", category:"Security",     intensity:9,  desc:"Boko Haram/ISWAP attacks across Lake Chad basin"},
  {lat:8.592,  lng:16.078, name:"Sarh",        country:"TD", category:"Displacement", intensity:7,  desc:"South Chad intercommunal violence and displacement"},

  // ── Nigeria (Northeast) ────────────────────────────────────────
  {lat:11.843, lng:13.150, name:"Maiduguri",   country:"NG", category:"Security",     intensity:9,  desc:"Boko Haram/ISWAP epicentre — 13+ years of conflict"},
  {lat:11.996, lng:14.984, name:"Borno State", country:"NG", category:"Displacement", intensity:9,  desc:"2+ million IDPs — largest displacement crisis in West Africa"},
  {lat:10.523, lng:7.440,  name:"Zamfara",     country:"NG", category:"Security",     intensity:8,  desc:"Bandit armed groups, mass kidnappings and farmer-herder crisis"},

  // ── Cameroon (Anglophone) ──────────────────────────────────────
  {lat:4.154,  lng:9.242,  name:"Buea",        country:"CM", category:"Security",     intensity:8,  desc:"Anglophone Ambazonia separatist conflict and ghost towns"},
  {lat:5.960,  lng:10.158, name:"Bamenda",     country:"CM", category:"Human Rights", intensity:9,  desc:"Ambazonia armed group atrocities and school shutdowns"},
];

var map = L.map('map').setView([5, 22], 4);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
  attribution:'&copy; OpenStreetMap contributors', maxZoom:18
}).addTo(map);

var circles = [];

function getColor(i){ return i>7?'#ED1C24':i>4?'#E7952A':'rgba(231,149,42,.4)'; }

function filterMap(){
  var country = document.querySelector('input[name=country]:checked').value;
  var cats = Array.from(document.querySelectorAll('.category-check:checked')).map(function(c){return c.value;});
  circles.forEach(function(c){ map.removeLayer(c); });
  circles = [];
  mapData.forEach(function(d){
    if(country!=='ALL' && d.country!==country) return;
    if(!cats.includes(d.category)) return;
    var c = L.circle([d.lat,d.lng],{
      radius: (d.intensity*1.5+4)*8000,
      color: getColor(d.intensity),
      fillColor: getColor(d.intensity),
      fillOpacity: 0.55,
      weight:1.5
    }).addTo(map)
     .bindPopup('<strong>'+d.name+'</strong><br><em>'+d.category+'</em><br>'+d.desc+'<br><small>Intensity: '+d.intensity+'/10</small>');
    circles.push(c);
  });
}

filterMap();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
