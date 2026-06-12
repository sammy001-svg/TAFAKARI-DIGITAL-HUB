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
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-black uppercase tracking-widest mb-4">Live Data</div>
    <h1 class="font-outfit text-4xl font-black text-slate-900">Regional Issue Tracker</h1>
    <p class="text-slate-500 mt-2">Visualizing reported issues across Kenya, Ethiopia, and DR Congo.</p>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
    <!-- Controls -->
    <div class="lg:col-span-1 space-y-6">
      <div class="bg-white rounded-3xl border border-slate-100 p-6 shadow-sm">
        <h3 class="font-outfit font-bold text-base mb-4 text-slate-900">Country Filter</h3>
        <div class="space-y-2">
          <?php foreach (['ALL' => 'All Countries', 'KE' => 'Kenya', 'ET' => 'Ethiopia', 'CD' => 'DR Congo'] as $code => $label): ?>
            <label class="flex items-center gap-3 cursor-pointer">
              <input type="radio" name="country" value="<?= h($code) ?>" <?= $code === 'ALL' ? 'checked' : '' ?>
                     class="accent-primary" onchange="filterMap()">
              <span class="text-sm font-medium text-slate-700"><?= h($label) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="bg-white rounded-3xl border border-slate-100 p-6 shadow-sm">
        <h3 class="font-outfit font-bold text-base mb-4 text-slate-900">Categories</h3>
        <div class="space-y-2">
          <?php foreach (['Health','Education','Security','Climate','Human Rights','Policy'] as $cat): ?>
            <label class="flex items-center gap-3 cursor-pointer">
              <input type="checkbox" value="<?= h($cat) ?>" checked class="category-check accent-primary" onchange="filterMap()">
              <span class="text-sm text-slate-600"><?= h($cat) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Map -->
    <div class="lg:col-span-3">
      <div id="map" class="w-full rounded-3xl border border-slate-100 shadow-sm overflow-hidden" style="height:520px"></div>
    </div>
  </div>

  <!-- Stats -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
    <div class="bg-white rounded-3xl border border-slate-100 p-6 shadow-sm text-center">
      <div class="text-3xl font-outfit font-black" style="color:#9A1415"><?= format_number($totalReports) ?></div>
      <div class="text-xs font-black uppercase tracking-widest text-slate-400 mt-1">Total Validated Reports</div>
    </div>
    <div class="bg-white rounded-3xl border border-slate-100 p-6 shadow-sm text-center">
      <div class="text-3xl font-outfit font-black" style="color:#D99F51"><?= format_number($monthlyReports) ?></div>
      <div class="text-xs font-black uppercase tracking-widest text-slate-400 mt-1">New Reports This Month</div>
    </div>
    <div class="bg-white rounded-3xl border border-slate-100 p-6 shadow-sm text-center">
      <div class="text-3xl font-outfit font-black text-slate-700">24h</div>
      <div class="text-xs font-black uppercase tracking-widest text-slate-400 mt-1">Data Refresh Cycle</div>
    </div>
  </div>
</main>

<script>
var mapData = [
  // Kenya
  {lat:1.286,lng:36.817,name:"Nairobi",country:"KE",category:"Health",intensity:8,desc:"Urban health services strain"},
  {lat:-1.286,lng:36.823,name:"Kiambu",country:"KE",category:"Education",intensity:6,desc:"School access challenges"},
  {lat:-4.043,lng:39.668,name:"Mombasa",country:"KE",category:"Security",intensity:7,desc:"Coastal security incidents"},
  {lat:0.517,lng:35.270,name:"Eldoret",country:"KE",category:"Agriculture",intensity:5,desc:"Crop failure due to drought"},
  {lat:-0.102,lng:34.761,name:"Kisumu",country:"KE",category:"Health",intensity:7,desc:"Malaria prevalence"},
  {lat:0.283,lng:37.454,name:"Meru",country:"KE",category:"Climate",intensity:6,desc:"Deforestation concerns"},
  {lat:-0.670,lng:37.147,name:"Thika",country:"KE",category:"Policy",intensity:4,desc:"Land use disputes"},
  {lat:3.119,lng:35.597,name:"Turkana",country:"KE",category:"Human Rights",intensity:9,desc:"Resource conflict"},
  // Ethiopia
  {lat:9.024,lng:38.747,name:"Addis Ababa",country:"ET",category:"Policy",intensity:7,desc:"Policy reform discussions"},
  {lat:14.495,lng:39.475,name:"Mekelle",country:"ET",category:"Human Rights",intensity:9,desc:"Humanitarian situation"},
  {lat:9.593,lng:41.867,name:"Dire Dawa",country:"ET",category:"Security",intensity:6,desc:"Border security"},
  {lat:7.678,lng:36.832,name:"Hawassa",country:"ET",category:"Education",intensity:5,desc:"Educational infrastructure"},
  {lat:11.593,lng:37.391,name:"Bahir Dar",country:"ET",category:"Climate",intensity:7,desc:"Lake Tana water levels"},
  {lat:9.313,lng:42.118,name:"Jijiga",country:"ET",category:"Health",intensity:8,desc:"Healthcare access"},
  {lat:8.550,lng:39.268,name:"Adama",country:"ET",category:"Agriculture",intensity:5,desc:"Irrigation challenges"},
  // DRC
  {lat:-4.322,lng:15.322,name:"Kinshasa",country:"CD",category:"Policy",intensity:7,desc:"Urban governance"},
  {lat:-1.678,lng:29.217,name:"Goma",country:"CD",category:"Security",intensity:10,desc:"Armed conflict"},
  {lat:-11.663,lng:27.479,name:"Lubumbashi",country:"CD",category:"Human Rights",intensity:8,desc:"Mining sector abuses"},
  {lat:-3.381,lng:29.361,name:"Bukavu",country:"CD",category:"Health",intensity:8,desc:"Healthcare crisis"},
  {lat:4.316,lng:18.033,name:"Kisangani",country:"CD",category:"Education",intensity:6,desc:"Education access"},
  {lat:-2.499,lng:28.860,name:"Uvira",country:"CD",category:"Climate",intensity:7,desc:"Flooding incidents"},
];

var map = L.map('map').setView([2,33],5);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
  attribution:'&copy; OpenStreetMap contributors', maxZoom:18
}).addTo(map);

var circles = [];

function getColor(i){ return i>7?'#9A1415':i>4?'#D99F51':'#EAD2AC'; }

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
      weight:1
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
