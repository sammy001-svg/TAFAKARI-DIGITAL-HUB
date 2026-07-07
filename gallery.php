<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 16;
$country = trim($_GET['country'] ?? '');

$images         = [];
$countryOptions = [];
$total          = 0;
$pages          = 1;

$where  = ["type = 'GALLERY_IMAGE'", "status = 'PUBLISHED'"];
$params = [];
if ($country !== '') {
    $where[]  = 'country = ?';
    $params[] = $country;
}
$whereStr = implode(' AND ', $where);

try {
    $cs = db()->query(
        "SELECT DISTINCT country FROM Post WHERE type='GALLERY_IMAGE' AND status='PUBLISHED' AND country IS NOT NULL AND country != '' ORDER BY country"
    );
    $countryOptions = $cs->fetchAll(PDO::FETCH_COLUMN);

    $countStmt = db()->prepare("SELECT COUNT(*) FROM Post WHERE $whereStr");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();
    $pages = max(1, (int) ceil($total / $perPage));
    $page  = min($page, $pages);
    $off   = ($page - 1) * $perPage;

    $stmt = db()->prepare(
        "SELECT id, title, description, thumbnailUrl, mediaUrl, country, region, createdAt
         FROM Post WHERE $whereStr ORDER BY createdAt DESC
         LIMIT $perPage OFFSET $off"
    );
    $stmt->execute($params);
    $images = $stmt->fetchAll();
} catch (Exception $e) { /* ignore */ }

function gallery_url(int $n, string $country): string {
    $p = array_filter(['country' => $country, 'page' => $n > 1 ? $n : ''],
        fn($v) => $v !== '' && $v !== null);
    return '/gallery' . ($p ? '?' . http_build_query($p) : '');
}

$pageTitle = 'Photo Gallery | Tafakari Digital Hub';
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex flex-col font-inter" style="background:#F8F8F0">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- ── Page header ──────────────────────────────────────────────────────────── -->
<div style="background:#0D0102" class="py-14 px-6">
  <div class="max-w-7xl mx-auto">
    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-4 text-amber-900" style="background:#E7952A" data-i18n="galleryPage.badge">Visual Stories</span>
    <h1 class="font-outfit text-4xl md:text-5xl font-black text-white leading-tight mb-3" data-i18n="galleryPage.title">Photo Gallery</h1>
    <p class="text-white/60 max-w-xl" data-i18n="galleryPage.desc">Moments captured from the field — communities, landscapes, and stories from across the African continent.</p>
  </div>
</div>

<main class="flex-grow max-w-7xl mx-auto px-6 py-10 w-full">

  <!-- ── Country filter bar ───────────────────────────────────────────────── -->
  <?php if (!empty($countryOptions)): ?>
    <div class="flex items-center gap-3 mb-8 overflow-x-auto pb-2">
      <a href="/gallery"
         class="shrink-0 px-4 py-2 rounded-xl text-sm font-bold transition-all <?= $country === '' ? 'text-slate-900 border-2' : 'bg-white border border-slate-200 text-slate-600 hover:bg-amber-50' ?>"
         style="<?= $country === '' ? 'border-color:#E7952A;background:#F8F8F0' : '' ?>" data-i18n="heatmapPage.allCountries">
        All Countries
      </a>
      <?php foreach ($countryOptions as $c): ?>
        <a href="/gallery?country=<?= urlencode($c) ?>"
           class="shrink-0 px-4 py-2 rounded-xl text-sm font-bold transition-all <?= $country === $c ? 'text-slate-900 border-2' : 'bg-white border border-slate-200 text-slate-600 hover:bg-amber-50' ?>"
           style="<?= $country === $c ? 'border-color:#E7952A;background:#F8F8F0' : '' ?>">
          <?= h($c) ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- ── Results count ────────────────────────────────────────────────────── -->
  <?php if ($total > 0): ?>
    <div class="flex items-center justify-between mb-6">
      <p class="text-sm text-slate-500">
        <span data-i18n="galleryPage.showing">Showing</span> <strong class="text-slate-800"><?= ($page - 1) * $perPage + 1 ?>–<?= min($page * $perPage, $total) ?></strong> <span data-i18n="galleryPage.of">of</span> <strong class="text-slate-800"><?= $total ?></strong> <span data-i18n="galleryPage.photos">photos</span>
        <?= $country ? ' <span data-i18n="galleryPage.from">from</span> <strong class="text-slate-700">' . h($country) . '</strong>' : ' <span data-i18n="galleryPage.acrossAllCountries">across all countries</span>' ?>
      </p>
      <?php if ($pages > 1): ?>
        <span class="text-xs text-slate-400"><span data-i18n="galleryPage.page">Page</span> <?= $page ?> <span data-i18n="galleryPage.of">of</span> <?= $pages ?></span>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- ── Photo grid ───────────────────────────────────────────────────────── -->
  <?php if (empty($images)): ?>
    <div class="text-center py-24 bg-white rounded-3xl border border-amber-100">
      <div class="text-6xl mb-4">🖼️</div>
      <h3 class="font-outfit font-bold text-xl text-slate-900"><span data-i18n="galleryPage.noPhotos">No photos</span><?= $country ? ' <span data-i18n="galleryPage.from">from</span> ' . h($country) : '' ?> <span data-i18n="galleryPage.yet">yet</span></h3>
      <p class="text-slate-400 mt-2 text-sm mb-6" data-i18n="galleryPage.imagesWillAppear">Images will appear here once published.</p>
      <?php if ($country): ?>
        <a href="/gallery" class="inline-block px-6 py-3 rounded-xl font-bold text-sm" style="background:#E7952A;color:#0D0102" data-i18n="galleryPage.viewAllCountries">
          View All Countries
        </a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3" id="gallery-grid">
      <?php foreach ($images as $idx => $img): ?>
        <div class="group relative rounded-2xl overflow-hidden cursor-pointer border border-amber-100 shadow-sm hover:shadow-md hover:border-amber-300 transition-all duration-300"
             style="aspect-ratio:4/5;background:#0D0102"
             onclick="openLightbox(<?= $idx ?>)">
          <?php if (!empty($img['thumbnailUrl'])): ?>
            <img src="<?= h($img['thumbnailUrl']) ?>" alt="<?= h($img['title']) ?>"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
          <?php else: ?>
            <div class="w-full h-full flex items-center justify-center" style="background:#0D0102">
              <svg width="32" height="32" fill="none" stroke="#E7952A" stroke-width="1.5" opacity=".3" viewBox="0 0 24 24"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
            </div>
          <?php endif; ?>

          <!-- Hover overlay -->
          <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-3" style="background:linear-gradient(to top, rgba(13,1,2,.85) 0%, transparent 60%)">
            <p class="text-white font-bold text-xs leading-snug line-clamp-2"><?= h($img['title']) ?></p>
            <p class="text-white/60 text-[10px] mt-1"><?= h($img['region'] ?? $img['country']) ?></p>
          </div>

          <!-- Country chip -->
          <div class="absolute top-2 left-2 opacity-0 group-hover:opacity-100 transition-opacity">
            <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest text-slate-900" style="background:#E7952A">
              <?= h($img['country']) ?>
            </span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- ── Pagination ──────────────────────────────────────────────────────── -->
    <?php if ($pages > 1): ?>
      <nav class="flex items-center justify-center gap-2 mt-12" aria-label="Pagination">
        <?php if ($page > 1): ?>
          <a href="<?= h(gallery_url($page - 1, $country)) ?>"
             class="w-10 h-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-600 hover:bg-amber-50 transition-colors text-lg font-light">&#8249;</a>
        <?php else: ?>
          <span class="w-10 h-10 rounded-xl border border-slate-100 flex items-center justify-center text-slate-300 text-lg cursor-not-allowed">&#8249;</span>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 2);
        $end   = min($pages, $page + 2);
        if ($start > 1): ?>
          <a href="<?= h(gallery_url(1, $country)) ?>" class="w-10 h-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-sm font-bold text-slate-600 hover:bg-amber-50 transition-colors">1</a>
          <?php if ($start > 2): ?><span class="text-slate-400 px-1">…</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
          <a href="<?= h(gallery_url($i, $country)) ?>"
             class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold transition-colors <?= $i === $page ? 'text-slate-900 border-2' : 'border border-slate-200 bg-white text-slate-600 hover:bg-amber-50' ?>"
             style="<?= $i === $page ? 'border-color:#E7952A;background:#F8F8F0' : '' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>

        <?php if ($end < $pages): ?>
          <?php if ($end < $pages - 1): ?><span class="text-slate-400 px-1">…</span><?php endif; ?>
          <a href="<?= h(gallery_url($pages, $country)) ?>" class="w-10 h-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-sm font-bold text-slate-600 hover:bg-amber-50 transition-colors"><?= $pages ?></a>
        <?php endif; ?>

        <?php if ($page < $pages): ?>
          <a href="<?= h(gallery_url($page + 1, $country)) ?>"
             class="w-10 h-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-600 hover:bg-amber-50 transition-colors text-lg font-light">&#8250;</a>
        <?php else: ?>
          <span class="w-10 h-10 rounded-xl border border-slate-100 flex items-center justify-center text-slate-300 text-lg cursor-not-allowed">&#8250;</span>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>

</main>

<!-- ── Lightbox ──────────────────────────────────────────────────────────────── -->
<?php if (!empty($images)): ?>
<style>
#lightbox {
  position: fixed; inset: 0; z-index: 9999;
  display: flex; align-items: center; justify-content: center;
  background: rgba(5,1,1,.96);
  opacity: 0; pointer-events: none;
  transition: opacity .22s ease;
}
#lightbox.lb-open { opacity: 1; pointer-events: all; }
#lb-inner {
  display: flex; flex-direction: column; align-items: center;
  max-width: 900px; width: 100%; padding: 4rem 4.5rem 1.5rem;
  transform: scale(.96);
  transition: transform .22s cubic-bezier(.34,1.56,.64,1);
}
#lightbox.lb-open #lb-inner { transform: scale(1); }
#lb-img-wrap {
  position: relative; display: flex; align-items: center; justify-content: center;
  width: 100%; min-height: 120px;
}
#lb-img {
  max-height: 62vh; max-width: 100%;
  border-radius: 16px; object-fit: contain;
  box-shadow: 0 32px 80px rgba(0,0,0,.6);
  transition: opacity .2s;
}
#lb-spinner {
  position: absolute;
  width: 36px; height: 36px;
  border: 3px solid rgba(255,255,255,.12);
  border-top-color: #E7952A;
  border-radius: 50%;
  animation: lb-spin .7s linear infinite;
  display: none;
}
@keyframes lb-spin { to { transform: rotate(360deg); } }
</style>

<div id="lightbox" role="dialog" aria-modal="true" aria-label="Photo viewer">
  <!-- Backdrop click closes -->
  <div id="lb-backdrop" style="position:absolute;inset:0;cursor:zoom-out" onclick="closeLightbox()"></div>

  <!-- Close -->
  <button onclick="closeLightbox()" title="Close (Esc)" data-i18n-title="galleryPage.closeEsc"
          style="position:absolute;top:16px;right:16px;z-index:10;width:38px;height:38px;border-radius:50%;border:none;
                 background:rgba(255,255,255,.08);color:rgba(255,255,255,.65);cursor:pointer;font-size:20px;line-height:1;
                 display:flex;align-items:center;justify-content:center;transition:background .15s,color .15s"
          onmouseover="this.style.background='rgba(255,255,255,.16)';this.style.color='#fff'"
          onmouseout="this.style.background='rgba(255,255,255,.08)';this.style.color='rgba(255,255,255,.65)'">&times;</button>

  <!-- Prev -->
  <button onclick="prevImg()" title="Previous (←)" data-i18n-title="galleryPage.previousArrow"
          style="position:absolute;left:12px;top:50%;transform:translateY(-50%);z-index:10;
                 width:46px;height:46px;border-radius:50%;border:none;
                 background:rgba(255,255,255,.08);color:rgba(255,255,255,.65);cursor:pointer;font-size:26px;line-height:1;
                 display:flex;align-items:center;justify-content:center;transition:background .15s,color .15s;user-select:none"
          onmouseover="this.style.background='rgba(255,255,255,.16)';this.style.color='#fff'"
          onmouseout="this.style.background='rgba(255,255,255,.08)';this.style.color='rgba(255,255,255,.65)'">&#8249;</button>

  <!-- Next -->
  <button onclick="nextImg()" title="Next (→)" data-i18n-title="galleryPage.nextArrow"
          style="position:absolute;right:12px;top:50%;transform:translateY(-50%);z-index:10;
                 width:46px;height:46px;border-radius:50%;border:none;
                 background:rgba(255,255,255,.08);color:rgba(255,255,255,.65);cursor:pointer;font-size:26px;line-height:1;
                 display:flex;align-items:center;justify-content:center;transition:background .15s,color .15s;user-select:none"
          onmouseover="this.style.background='rgba(255,255,255,.16)';this.style.color='#fff'"
          onmouseout="this.style.background='rgba(255,255,255,.08)';this.style.color='rgba(255,255,255,.65)'">&#8250;</button>

  <div id="lb-inner">
    <!-- Main image -->
    <div id="lb-img-wrap">
      <div id="lb-spinner"></div>
      <img id="lb-img" src="" alt="" style="opacity:0">
    </div>

    <!-- Caption -->
    <div style="margin-top:18px;text-align:center;width:100%">
      <p id="lb-title" style="font-family:Outfit,sans-serif;font-weight:800;font-size:17px;color:#fff;margin:0 0 5px;line-height:1.3"></p>
      <p id="lb-desc"  style="font-size:13px;color:rgba(255,255,255,.45);margin:0 0 6px;max-width:560px;margin-inline:auto;line-height:1.6"></p>
      <p id="lb-count" style="font-size:11px;color:rgba(255,255,255,.25);margin:0"></p>
    </div>

    <!-- Thumbnail strip -->
    <div style="display:flex;gap:8px;margin-top:18px;overflow-x:auto;max-width:100%;padding-bottom:6px;
                scrollbar-width:thin;scrollbar-color:#E7952A transparent">
      <?php foreach ($images as $idx => $img): ?>
        <img src="<?= h($img['thumbnailUrl'] ?? '') ?>" alt="<?= h($img['title']) ?>"
             class="lb-thumb"
             data-index="<?= $idx ?>" onclick="goLightbox(<?= $idx ?>)"
             style="width:56px;height:56px;object-fit:cover;border-radius:10px;cursor:pointer;
                    border:2px solid transparent;flex-shrink:0;opacity:.5;
                    transition:border-color .15s,opacity .15s">
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script>
var galleryData = <?= json_encode(array_values(array_map(fn($i) => [
    'title' => $i['title'],
    'desc'  => $i['description'] ?? '',
    'src'   => $i['mediaUrl'] ?: ($i['thumbnailUrl'] ?? ''),
    'thumb' => $i['thumbnailUrl'] ?? '',
    'meta'  => trim(($i['region'] ? $i['region'] . ', ' : '') . $i['country'])
               . ' · ' . date('M Y', strtotime($i['createdAt'])),
], $images)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

var curIdx = 0;
var lbOpen = false;
var touchStartX = 0;

function openLightbox(idx) {
    curIdx  = idx;
    lbOpen  = true;
    var lb  = document.getElementById('lightbox');
    lb.classList.add('lb-open');
    document.body.style.overflow = 'hidden';
    updateLightbox();
}

function closeLightbox() {
    lbOpen = false;
    document.getElementById('lightbox').classList.remove('lb-open');
    document.body.style.overflow = '';
}

function goLightbox(idx) {
    if (idx === curIdx) return;
    curIdx = idx;
    updateLightbox();
}

function prevImg() { curIdx = (curIdx - 1 + galleryData.length) % galleryData.length; updateLightbox(); }
function nextImg() { curIdx = (curIdx + 1)                      % galleryData.length; updateLightbox(); }

function updateLightbox() {
    var d      = galleryData[curIdx];
    var img    = document.getElementById('lb-img');
    var spin   = document.getElementById('lb-spinner');

    // Show spinner, hide image while loading
    img.style.opacity = '0';
    spin.style.display = 'block';

    var tmp    = new Image();
    tmp.onload = function() {
        img.src           = d.src;
        img.alt           = d.title;
        img.style.opacity = '1';
        spin.style.display = 'none';
    };
    tmp.onerror = function() {
        img.src           = d.thumb;
        img.alt           = d.title;
        img.style.opacity = '1';
        spin.style.display = 'none';
    };
    tmp.src = d.src;

    document.getElementById('lb-title').textContent = d.title;
    document.getElementById('lb-desc').textContent  = d.desc;
    document.getElementById('lb-count').textContent = (curIdx + 1) + ' of ' + galleryData.length + ' · ' + d.meta;

    document.querySelectorAll('.lb-thumb').forEach(function(t, i) {
        var active = (i === curIdx);
        t.style.borderColor = active ? '#E7952A' : 'transparent';
        t.style.opacity     = active ? '1' : '0.5';
        if (active) t.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
    });
}

/* Keyboard */
document.addEventListener('keydown', function(e) {
    if (!lbOpen) return;
    if (e.key === 'ArrowLeft'  || e.key === 'a') prevImg();
    if (e.key === 'ArrowRight' || e.key === 'd') nextImg();
    if (e.key === 'Escape')                       closeLightbox();
});

/* Touch swipe */
document.getElementById('lightbox').addEventListener('touchstart', function(e) {
    touchStartX = e.touches[0].clientX;
}, { passive: true });
document.getElementById('lightbox').addEventListener('touchend', function(e) {
    var dx = e.changedTouches[0].clientX - touchStartX;
    if (Math.abs(dx) > 50) {
        if (dx < 0) nextImg(); else prevImg();
    }
}, { passive: true });
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
