<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$images = [];
try {
    $stmt = db()->query(
        "SELECT id, title, description, thumbnailUrl, country, region, createdAt
         FROM Post WHERE type = 'GALLERY_IMAGE' AND status = 'PUBLISHED'
         ORDER BY createdAt DESC LIMIT 48"
    );
    $images = $stmt->fetchAll();
} catch (Exception $e) { /* ignore */ }

$pageTitle = 'Photo Gallery | Tafakari Digital Hub';
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex flex-col bg-slate-50 font-inter">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="flex-grow max-w-7xl mx-auto px-6 py-16">
  <div class="mb-12">
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-black uppercase tracking-widest mb-4">Visual Stories</div>
    <h1 class="font-outfit text-4xl font-black text-slate-900">Photo Gallery</h1>
    <p class="text-slate-500 mt-2">Moments captured across Kenya, Ethiopia, and DR Congo.</p>
  </div>

  <?php if (empty($images)): ?>
    <div class="text-center py-24">
      <div class="text-5xl mb-4">🖼️</div>
      <h3 class="font-outfit font-bold text-xl text-slate-900">No photos yet</h3>
      <p class="text-slate-400 mt-2 text-sm">Images will appear here once published.</p>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" id="gallery-grid">
      <?php foreach ($images as $idx => $img): ?>
        <div class="group relative rounded-2xl overflow-hidden cursor-pointer bg-slate-200"
             style="aspect-ratio:4/5"
             onclick="openLightbox(<?= $idx ?>)">
          <?php if (!empty($img['thumbnailUrl'])): ?>
            <img src="<?= h($img['thumbnailUrl']) ?>" alt="<?= h($img['title']) ?>"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
          <?php else: ?>
            <div class="w-full h-full flex items-center justify-center text-4xl" style="background:#9A1415;opacity:.3">🖼️</div>
          <?php endif; ?>
          <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-all duration-300 flex flex-col justify-end p-4 opacity-0 group-hover:opacity-100">
            <p class="text-white font-bold text-sm leading-tight line-clamp-2"><?= h($img['title']) ?></p>
            <p class="text-white/70 text-xs mt-1"><?= h($img['region'] ?? $img['country']) ?> &bull; <?= format_date($img['createdAt'], 'M Y') ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<!-- Lightbox -->
<?php if (!empty($images)): ?>
<div id="lightbox" class="fixed inset-0 z-50 hidden items-center justify-center" style="background:rgba(0,0,0,.92)">
  <button onclick="closeLightbox()" class="absolute top-6 right-6 text-white text-3xl hover:text-secondary z-10">&times;</button>
  <button onclick="prevImg()" class="absolute left-6 text-white text-4xl hover:text-secondary z-10 select-none">&#8249;</button>
  <button onclick="nextImg()" class="absolute right-16 text-white text-4xl hover:text-secondary z-10 select-none">&#8250;</button>

  <div class="flex flex-col items-center max-w-4xl w-full px-16">
    <img id="lb-img" src="" alt="" class="max-h-[70vh] max-w-full rounded-2xl object-contain">
    <div class="mt-4 text-center">
      <p id="lb-title" class="text-white font-outfit font-bold text-lg"></p>
      <p id="lb-desc"  class="text-white/60 text-sm mt-1"></p>
      <p id="lb-count" class="text-white/40 text-xs mt-2"></p>
    </div>
    <!-- Thumbnail strip -->
    <div class="flex gap-2 mt-6 overflow-x-auto max-w-full pb-2">
      <?php foreach ($images as $idx => $img): ?>
        <img src="<?= h($img['thumbnailUrl'] ?? '') ?>" alt=""
             class="lb-thumb w-12 h-12 object-cover rounded-lg cursor-pointer border-2 border-transparent hover:border-secondary transition-all shrink-0"
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
  document.getElementById('lb-img').src   = d.src;
  document.getElementById('lb-img').alt   = d.title;
  document.getElementById('lb-title').textContent = d.title;
  document.getElementById('lb-desc').textContent  = d.desc;
  document.getElementById('lb-count').textContent = (curIdx+1) + ' / ' + galleryData.length + ' · ' + d.meta;
  document.querySelectorAll('.lb-thumb').forEach(function(t,i){
    t.style.borderColor = i===curIdx ? '#D99F51' : 'transparent';
    if (i===curIdx) t.scrollIntoView({block:'nearest',inline:'center'});
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
