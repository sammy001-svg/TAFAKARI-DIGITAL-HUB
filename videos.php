<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;
$country = trim($_GET['country'] ?? '');
$cat     = trim($_GET['cat']     ?? '');

$videos         = [];
$countryOptions = [];
$total          = 0;
$pages          = 1;

$where  = ["type = 'VIDEO'", "status = 'PUBLISHED'"];
$params = [];
if ($country !== '') {
    $where[]  = 'country = ?';
    $params[] = $country;
}
if ($cat !== '') {
    $where[]  = 'FIND_IN_SET(?, issueCategory) > 0';
    $params[] = $cat;
}
$whereStr = implode(' AND ', $where);

try {
    $cs = db()->query(
        "SELECT DISTINCT country FROM Post WHERE type='VIDEO' AND status='PUBLISHED' AND country IS NOT NULL AND country != '' ORDER BY country"
    );
    $countryOptions = $cs->fetchAll(PDO::FETCH_COLUMN);

    $countStmt = db()->prepare("SELECT COUNT(*) FROM Post WHERE $whereStr");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();
    $pages = max(1, (int) ceil($total / $perPage));
    $page  = min($page, $pages);
    $off   = ($page - 1) * $perPage;

    $stmt = db()->prepare(
        "SELECT id, title, description, thumbnailUrl, mediaUrl, country, issueCategory, viewCount, createdAt
         FROM Post WHERE $whereStr ORDER BY createdAt DESC
         LIMIT $perPage OFFSET $off"
    );
    $stmt->execute($params);
    $videos = $stmt->fetchAll();
} catch (Exception $e) { /* DB not ready */ }

// youtube_id() and vimeo_id() are provided by includes/functions.php

function embed_html(string $url, string $title): string {
    $ytId = youtube_id($url);
    if ($ytId) {
        $src = 'https://www.youtube.com/embed/' . $ytId . '?rel=0&modestbranding=1';
        return '<iframe src="' . htmlspecialchars($src, ENT_QUOTES) . '" title="' . htmlspecialchars($title, ENT_QUOTES) . '"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen class="w-full h-full rounded-t-2xl border-0"></iframe>';
    }
    $vmId = vimeo_id($url);
    if ($vmId) {
        $src = 'https://player.vimeo.com/video/' . $vmId . '?title=0&byline=0&portrait=0&color=D99F51';
        return '<iframe src="' . htmlspecialchars($src, ENT_QUOTES) . '" title="' . htmlspecialchars($title, ENT_QUOTES) . '"
                    allow="autoplay; fullscreen; picture-in-picture"
                    allowfullscreen class="w-full h-full rounded-t-2xl border-0"></iframe>';
    }
    return '';
}

function video_url(int $n, string $country, string $cat): string {
    $p = array_filter(['country' => $country, 'cat' => $cat, 'page' => $n > 1 ? $n : ''],
        fn($v) => $v !== '' && $v !== null);
    return '/videos' . ($p ? '?' . http_build_query($p) : '');
}

$hasFilter = $country !== '' || $cat !== '';
$pageTitle = 'Video Library | Tafakari Digital Hub';
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex flex-col font-inter" style="background:#F8F8F0">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- ── Page header ──────────────────────────────────────────────────────────── -->
<div style="background:#0D0102" class="py-14 px-6">
  <div class="max-w-7xl mx-auto">
    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-4 text-amber-900" style="background:#E7952A" data-i18n="videosPage.badge">Video Archive</span>
    <h1 class="font-outfit text-4xl md:text-5xl font-black text-white leading-tight mb-3" data-i18n="videosPage.title">Video Library</h1>
    <p class="text-white/60 max-w-xl" data-i18n="videosPage.desc">Documentaries, field reports, community stories, and conference recordings — all in one place.</p>
  </div>
</div>

<main class="flex-grow max-w-7xl mx-auto px-6 py-12 w-full">

  <!-- ── Filter bar ───────────────────────────────────────────────────────── -->
  <div class="bg-white rounded-2xl border border-amber-100 shadow-sm p-5 mb-8">
    <form method="GET" action="/videos" class="flex flex-wrap gap-3 items-end">
      <div>
        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5" data-i18n="heatmapPage.country">Country</label>
        <select name="country" class="px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none min-w-[150px]">
          <option value="" data-i18n="heatmapPage.allCountries">All Countries</option>
          <?php foreach ($countryOptions as $c): ?>
            <option value="<?= h($c) ?>" <?= $country === $c ? 'selected' : '' ?>><?= h($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5" data-i18n="heatmapPage.category">Category</label>
        <select name="cat" class="px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none min-w-[180px]">
          <option value="" data-i18n="listPage.allCategories">All Categories</option>
          <?php foreach (issue_categories() as $c): ?>
            <option value="<?= h($c) ?>" <?= $cat === $c ? 'selected' : '' ?>><?= h($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="px-6 py-3 rounded-xl font-bold text-sm transition-all hover:brightness-110"
              style="background:#E7952A;color:#0D0102" data-i18n="listPage.filter">
        Filter
      </button>
      <?php if ($hasFilter): ?>
        <a href="/videos" class="px-6 py-3 rounded-xl font-bold text-sm border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors" data-i18n="listPage.clear">Clear</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- ── Results meta ──────────────────────────────────────────────────────── -->
  <?php if ($total > 0): ?>
    <div class="flex items-center justify-between mb-6">
      <p class="text-sm text-slate-500">
        <span data-i18n="galleryPage.showing">Showing</span> <strong class="text-slate-800"><?= ($page - 1) * $perPage + 1 ?>–<?= min($page * $perPage, $total) ?></strong> <span data-i18n="galleryPage.of">of</span> <strong class="text-slate-800"><?= $total ?></strong> <span data-i18n="videosPage.videos">videos</span>
        <?= $country ? ' <span data-i18n="galleryPage.from">from</span> <strong class="text-slate-700">' . h($country) . '</strong>' : '' ?>
      </p>
      <?php if ($pages > 1): ?>
        <span class="text-xs text-slate-400"><span data-i18n="galleryPage.page">Page</span> <?= $page ?> <span data-i18n="galleryPage.of">of</span> <?= $pages ?></span>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- ── Video grid ───────────────────────────────────────────────────────── -->
  <?php if (empty($videos)): ?>
    <div class="text-center py-24 bg-white rounded-3xl border border-amber-100">
      <div class="text-6xl mb-4">🎬</div>
      <h3 class="font-outfit font-bold text-xl text-slate-900" data-i18n="videosPage.noVideosFound">No videos found</h3>
      <p class="text-slate-400 mt-2 text-sm mb-6" data-i18n="<?= $hasFilter ? 'listPage.tryDifferentFilters' : 'videosPage.willAppear' ?>">
        <?= $hasFilter ? 'Try different filter options.' : 'Video content will appear here once published.' ?>
      </p>
      <?php if ($hasFilter): ?>
        <a href="/videos" class="inline-block px-6 py-3 rounded-xl font-bold text-sm" style="background:#E7952A;color:#0D0102" data-i18n="videosPage.viewAllVideos">View All Videos</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-7">
      <?php foreach ($videos as $v):
        $ytId      = !empty($v['mediaUrl']) ? youtube_id($v['mediaUrl']) : '';
        $vmId      = !empty($v['mediaUrl']) ? vimeo_id($v['mediaUrl']) : '';
        $isEmbed   = ($ytId !== '' || $vmId !== '');
        $embedCode = $isEmbed && !empty($v['mediaUrl']) ? embed_html($v['mediaUrl'], $v['title']) : '';
      ?>
        <div class="group bg-white rounded-2xl border border-amber-100 shadow-sm overflow-hidden hover:shadow-lg hover:border-amber-200 transition-all duration-300 flex flex-col">

          <!-- Video / thumbnail area -->
          <div class="relative overflow-hidden" style="aspect-ratio:16/9;background:#0D0102">

            <?php if ($isEmbed): ?>
              <div id="vid-thumb-<?= $v['id'] ?>" class="absolute inset-0 cursor-pointer" onclick="playEmbed('<?= h($v['id']) ?>')">
                <?php if (!empty($v['thumbnailUrl'])): ?>
                  <img src="<?= h($v['thumbnailUrl']) ?>" alt="<?= h($v['title']) ?>"
                       class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition-opacity duration-300 group-hover:scale-105 transition-transform">
                <?php elseif ($ytId): ?>
                  <img src="https://img.youtube.com/vi/<?= h($ytId) ?>/hqdefault.jpg" alt="<?= h($v['title']) ?>"
                       class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition-opacity duration-300">
                <?php else: ?>
                  <div class="w-full h-full flex items-center justify-center" style="background:#0D0102">
                    <svg width="40" height="40" fill="none" stroke="#ED1C24" stroke-width="1.5" opacity=".4" viewBox="0 0 24 24"><polygon points="5,3 19,12 5,21 5,3"/></svg>
                  </div>
                <?php endif; ?>
                <div class="absolute inset-0 flex items-center justify-center">
                  <div class="w-16 h-16 rounded-full flex items-center justify-center border-2 border-white/30 backdrop-blur-sm group-hover:scale-110 transition-transform shadow-xl"
                       style="background:rgba(237,28,36,.9)">
                    <svg class="w-7 h-7 ml-0.5 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                  </div>
                </div>
                <?php if ($ytId): ?>
                  <div class="absolute top-3 right-3 px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest text-white" style="background:rgba(255,0,0,.85)">YouTube</div>
                <?php elseif ($vmId): ?>
                  <div class="absolute top-3 right-3 px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest text-white" style="background:rgba(26,183,234,.85)">Vimeo</div>
                <?php endif; ?>
              </div>
              <div id="vid-frame-<?= h($v['id']) ?>" class="hidden absolute inset-0">
                <?= $embedCode ?>
              </div>

            <?php elseif (!empty($v['mediaUrl'])): ?>
              <a href="<?= h($v['mediaUrl']) ?>" target="_blank" rel="noopener noreferrer" class="block w-full h-full">
                <?php if (!empty($v['thumbnailUrl'])): ?>
                  <img src="<?= h($v['thumbnailUrl']) ?>" alt="<?= h($v['title']) ?>"
                       class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition-opacity duration-300">
                <?php else: ?>
                  <div class="w-full h-full flex items-center justify-center" style="background:#0D0102">
                    <svg width="40" height="40" fill="none" stroke="#ED1C24" stroke-width="1.5" opacity=".4" viewBox="0 0 24 24"><polygon points="5,3 19,12 5,21 5,3"/></svg>
                  </div>
                <?php endif; ?>
                <div class="absolute inset-0 flex items-center justify-center">
                  <div class="w-16 h-16 rounded-full flex items-center justify-center border-2 border-white/30 backdrop-blur-sm group-hover:scale-110 transition-transform shadow-xl"
                       style="background:rgba(237,28,36,.9)">
                    <svg class="w-7 h-7 ml-0.5 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                  </div>
                </div>
              </a>

            <?php else: ?>
              <div class="w-full h-full flex items-center justify-center" style="background:#0D0102">
                <svg width="40" height="40" fill="none" stroke="#ED1C24" stroke-width="1.5" opacity=".3" viewBox="0 0 24 24"><polygon points="5,3 19,12 5,21 5,3"/></svg>
              </div>
            <?php endif; ?>

            <?php if (!empty($v['viewCount']) && (int)$v['viewCount'] > 0): ?>
              <div class="absolute bottom-3 left-3 px-2 py-0.5 rounded text-[10px] font-black text-white" style="background:rgba(0,0,0,.65)">
                <?= format_number((int)$v['viewCount']) ?> <span data-i18n="videosPage.views">views</span>
              </div>
            <?php endif; ?>
          </div>

          <!-- Content block -->
          <div class="p-5 flex flex-col flex-grow">
            <div class="flex flex-wrap gap-2 mb-3">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest text-amber-900" style="background:#E7952A"><?= h($v['country']) ?></span>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-slate-100 text-slate-600"><?= h($v['issueCategory']) ?></span>
            </div>
            <h3 class="font-outfit font-bold text-base text-slate-900 leading-snug mb-2 line-clamp-2 flex-grow">
              <a href="/videos/<?= h($v['id']) ?>" class="hover:text-primary transition-colors"><?= h($v['title']) ?></a>
            </h3>
            <?php if (!empty($v['description'])): ?>
              <p class="text-sm text-slate-500 line-clamp-2 mb-3"><?= h($v['description']) ?></p>
            <?php endif; ?>
            <div class="flex items-center justify-between mt-auto pt-3 border-t border-amber-50">
              <span class="text-xs text-slate-400"><?= format_date($v['createdAt']) ?></span>
              <?php if (!empty($v['mediaUrl'])): ?>
                <?php if ($isEmbed): ?>
                  <button onclick="playEmbed('<?= h($v['id']) ?>')"
                          class="text-xs font-bold transition-colors" style="color:#C47C1A">
                    <span data-i18n="videosPage.watch">Watch</span> &rarr;
                  </button>
                <?php else: ?>
                  <a href="<?= h($v['mediaUrl']) ?>" target="_blank" rel="noopener noreferrer"
                     class="text-xs font-bold transition-colors" style="color:#C47C1A">
                    <span data-i18n="videosPage.watch">Watch</span> &rarr;
                  </a>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- ── Pagination ──────────────────────────────────────────────────────── -->
    <?php if ($pages > 1): ?>
      <nav class="flex items-center justify-center gap-2 mt-12" aria-label="Pagination">
        <?php if ($page > 1): ?>
          <a href="<?= h(video_url($page - 1, $country, $cat)) ?>"
             class="w-10 h-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-600 hover:bg-amber-50 transition-colors text-lg font-light">&#8249;</a>
        <?php else: ?>
          <span class="w-10 h-10 rounded-xl border border-slate-100 flex items-center justify-center text-slate-300 text-lg cursor-not-allowed">&#8249;</span>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 2);
        $end   = min($pages, $page + 2);
        if ($start > 1): ?>
          <a href="<?= h(video_url(1, $country, $cat)) ?>" class="w-10 h-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-sm font-bold text-slate-600 hover:bg-amber-50 transition-colors">1</a>
          <?php if ($start > 2): ?><span class="text-slate-400 px-1">…</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
          <a href="<?= h(video_url($i, $country, $cat)) ?>"
             class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold transition-colors <?= $i === $page ? 'text-slate-900 border-2' : 'border border-slate-200 bg-white text-slate-600 hover:bg-amber-50' ?>"
             style="<?= $i === $page ? 'border-color:#E7952A;background:#F8F8F0' : '' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>

        <?php if ($end < $pages): ?>
          <?php if ($end < $pages - 1): ?><span class="text-slate-400 px-1">…</span><?php endif; ?>
          <a href="<?= h(video_url($pages, $country, $cat)) ?>" class="w-10 h-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-sm font-bold text-slate-600 hover:bg-amber-50 transition-colors"><?= $pages ?></a>
        <?php endif; ?>

        <?php if ($page < $pages): ?>
          <a href="<?= h(video_url($page + 1, $country, $cat)) ?>"
             class="w-10 h-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-600 hover:bg-amber-50 transition-colors text-lg font-light">&#8250;</a>
        <?php else: ?>
          <span class="w-10 h-10 rounded-xl border border-slate-100 flex items-center justify-center text-slate-300 text-lg cursor-not-allowed">&#8250;</span>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
function playEmbed(id) {
    var thumb = document.getElementById('vid-thumb-' + id);
    var frame = document.getElementById('vid-frame-' + id);
    if (!thumb || !frame) return;
    thumb.classList.add('hidden');
    frame.classList.remove('hidden');
    var iframe = frame.querySelector('iframe');
    if (iframe) {
        var src = iframe.src;
        if (!src) {
            iframe.src = iframe.getAttribute('data-src') || src;
        }
    }
}
</script>
</body>
</html>
