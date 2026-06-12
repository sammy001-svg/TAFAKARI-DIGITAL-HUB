<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$country = trim($_GET['country'] ?? '');

$images         = [];
$countryOptions = [];

try {
    // Distinct countries for filter
    $cs = db()->query(
        "SELECT DISTINCT country FROM Post WHERE type='GALLERY_IMAGE' AND status='PUBLISHED' AND country IS NOT NULL AND country != '' ORDER BY country"
    );
    $countryOptions = $cs->fetchAll(PDO::FETCH_COLUMN);

    // Fetch images
    $where  = ["type = 'GALLERY_IMAGE'", "status = 'PUBLISHED'"];
    $params = [];
    if ($country !== '') {
        $where[]  = 'country = ?';
        $params[] = $country;
    }
    $whereStr = implode(' AND ', $where);
    $stmt = db()->prepare(
        "SELECT id, title, description, thumbnailUrl, country, region, createdAt
         FROM Post WHERE $whereStr ORDER BY createdAt DESC LIMIT 60"
    );
    $stmt->execute($params);
    $images = $stmt->fetchAll();
} catch (Exception $e) { /* ignore */ }

$pageTitle = 'Photo Gallery | Tafakari Digital Hub';
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex flex-col font-inter" style="background:#F7F3EC">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- ── Page header ──────────────────────────────────────────────────────────── -->
<div style="background:#0D0102" class="py-14 px-6">
  <div class="max-w-7xl mx-auto">
    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-4 text-amber-900" style="background:#D99F51">Visual Stories</span>
    <h1 class="font-outfit text-4xl md:text-5xl font-black text-white leading-tight mb-3">Photo Gallery</h1>
    <p class="text-white/60 max-w-xl">Moments captured from the field — communities, landscapes, and stories from across the African continent.</p>
  </div>
</div>

<main class="flex-grow max-w-7xl mx-auto px-6 py-10 w-full">

  <!-- ── Country filter bar ───────────────────────────────────────────────── -->
  <?php if (!empty($countryOptions)): ?>
    <div class="flex items-center gap-3 mb-8 overflow-x-auto pb-2">
      <a href="/gallery"
         class="shrink-0 px-4 py-2 rounded-xl text-sm font-bold transition-all <?= $country === '' ? 'text-slate-900 border-2' : 'bg-white border border-slate-200 text-slate-600 hover:bg-amber-50' ?>"
         style="<?= $country === '' ? 'border-color:#D99F51;background:#FBF5E6' : '' ?>">
        All Countries
      </a>
      <?php foreach ($countryOptions as $c): ?>
        <a href="/gallery?country=<?= urlencode($c) ?>"
           class="shrink-0 px-4 py-2 rounded-xl text-sm font-bold transition-all <?= $country === $c ? 'text-slate-900 border-2' : 'bg-white border border-slate-200 text-slate-600 hover:bg-amber-50' ?>"
           style="<?= $country === $c ? 'border-color:#D99F51;background:#FBF5E6' : '' ?>">
          <?= h($c) ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- ── Results count ────────────────────────────────────────────────────── -->
  <?php if (!empty($images)): ?>
    <p class="text-sm text-slate-500 mb-6">
      <?= count($images) ?> photo<?= count($images) !== 1 ? 's' : '' ?>
      <?= $country ? ' from <strong class="text-slate-700">' . h($country) . '</strong>' : ' across all countries' ?>
    </p>
  <?php endif; ?>

  <!-- ── Photo grid ───────────────────────────────────────────────────────── -->
  <?php if (empty($images)): ?>
    <div class="text-center py-24 bg-white rounded-3xl border border-amber-100">
      <div class="text-6xl mb-4">🖼️</div>
      <h3 class="font-outfit font-bold text-xl text-slate-900">No photos<?= $country ? ' from ' . h($country) : '' ?> yet</h3>
      <p class="text-slate-400 mt-2 text-sm mb-6">Images will appear here once published.</p>
      <?php if ($country): ?>
        <a href="/gallery" class="inline-block px-6 py-3 rounded-xl font-bold text-sm" style="background:#D99F51;color:#0D0102">
          View All Countries
        </a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3" id="gallery-grid">
      <?php foreach ($images as $idx => $img): ?>
        <div class="group relative rounded-2xl overflow-hidden cursor-pointer border border-amber-100 shadow-sm hover:shadow-md hover:border-amber-300 transition-all duration-300"
             style="aspect-ratio:4/5;background:#1a0203"
             onclick="openLightbox(<?= $idx ?>)">
          <?php if (!empty($img['thumbnailUrl'])): ?>
            <img src="<?= h($img['thumbnailUrl']) ?>" alt="<?= h($img['title']) ?>"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
          <?php else: ?>
            <div class="w-full h-full flex items-center justify-center" style="background:linear-gradient(135deg,#1a0203,#3B0708)">
              <svg width="32" height="32" fill="none" stroke="#D99F51" stroke-width="1.5" opacity=".3" viewBox="0 0 24 24"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
            </div>
          <?php endif; ?>

          <!-- Hover overlay -->
          <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-3">
            <p class="text-white font-bold text-xs leading-snug line-clamp-2"><?= h($img['title']) ?></p>
            <p class="text-white/60 text-[10px] mt-1"><?= h($img['region'] ?? $img['country']) ?></p>
          </div>

          <!-- Country chip -->
          <div class="absolute top-2 left-2 opacity-0 group-hover:opacity-100 transition-opacity">
            <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest text-slate-900" style="background:#D99F51">
              <?= h($img['country']) ?>
            </span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</main>

<!-- ── Lightbox ──────────────────────────────────────────────────────────────── -->
<?php if (!empty($images)): ?>
<div id="lightbox" class="fixed inset-0 z-50 hidden items-center justify-center" style="background:rgba(5,1,1,.95)">
  <!-- Controls -->
  <button onclick="closeLightbox()" class="absolute top-5 right-5 w-10 h-10 rounded-full flex items-center justify-center text-white/60 hover:text-white hover:bg-white/10 transition-all z-10 text-xl">&times;</button>
  <button onclick="prevImg()" class="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full flex items-center justify-center text-white/60 hover:text-white hover:bg-white/10 transition-all z-10 text-2xl select-none">&#8249;</button>
  <button onclick="nextImg()" class="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full flex items-center justify-center text-white/60 hover:text-white hover:bg-white/10 transition-all z-10 text-2xl select-none">&#8250;</button>

  <div class="flex flex-col items-center max-w-5xl w-full px-16 py-8">
    <img id="lb-img" src="" alt="" class="max-h-[65vh] max-w-full rounded-2xl object-contain shadow-2xl">
    <div class="mt-5 text-center">
      <p id="lb-title" class="text-white font-outfit font-bold text-lg"></p>
      <p id="lb-desc"  class="text-white/50 text-sm mt-1 max-w-xl"></p>
      <p id="lb-count" class="text-white/30 text-xs mt-2"></p>
    </div>
    <!-- Thumbnail strip -->
    <div class="flex gap-2 mt-5 overflow-x-auto max-w-full pb-2 px-2" style="scrollbar-width:thin;scrollbar-color:#D99F51 transparent">
      <?php foreach ($images as $idx => $img): ?>
        <img src="<?= h($img['thumbnailUrl'] ?? '') ?>" alt=""
             class="lb-thumb w-14 h-14 object-cover rounded-xl cursor-pointer border-2 border-transparent hover:border-amber-400 transition-all shrink-0 opacity-60 hover:opacity-100"
             data-index="<?= $idx ?>" onclick="goLightbox(<?= $idx ?>)">
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script>
var galleryData = <?= json_encode(array_values(array_map(fn($i) => [
    'title' => $i['title'],
    'desc'  => $i['description'] ?? '',
    'src'   => $i['thumbnailUrl'] ?? '',
    'meta'  => ($i['region'] ?? $i['country']) . ' · ' . date('M Y', strtotime($i['createdAt'])),
], $images))) ?>;

var curIdx = 0;

function openLightbox(idx) {
    curIdx = idx;
    document.getElementById('lightbox').classList.remove('hidden');
    document.getElementById('lightbox').classList.add('flex');
    document.body.style.overflow = 'hidden';
    updateLightbox();
}
function closeLightbox() {
    document.getElementById('lightbox').classList.add('hidden');
    document.getElementById('lightbox').classList.remove('flex');
    document.body.style.overflow = '';
}
function goLightbox(idx) { curIdx = idx; updateLightbox(); }
function prevImg() { curIdx = (curIdx - 1 + galleryData.length) % galleryData.length; updateLightbox(); }
function nextImg() { curIdx = (curIdx + 1) % galleryData.length; updateLightbox(); }

function updateLightbox() {
    var d = galleryData[curIdx];
    var img = document.getElementById('lb-img');
    img.src = d.src;
    img.alt = d.title;
    document.getElementById('lb-title').textContent = d.title;
    document.getElementById('lb-desc').textContent  = d.desc;
    document.getElementById('lb-count').textContent = (curIdx + 1) + ' / ' + galleryData.length + ' · ' + d.meta;
    document.querySelectorAll('.lb-thumb').forEach(function(t, i) {
        t.style.borderColor = i === curIdx ? '#D99F51' : 'transparent';
        t.style.opacity     = i === curIdx ? '1' : '0.6';
        if (i === curIdx) t.scrollIntoView({ block: 'nearest', inline: 'center' });
    });
}

document.addEventListener('keydown', function(e) {
    var lb = document.getElementById('lightbox');
    if (lb.classList.contains('hidden')) return;
    if (e.key === 'ArrowLeft')  prevImg();
    if (e.key === 'ArrowRight') nextImg();
    if (e.key === 'Escape')     closeLightbox();
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
