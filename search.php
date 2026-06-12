<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$q = trim($_GET['q'] ?? '');

// ── Type config: label, icon SVG path, link builder ──────────────────────────
$typeConfig = [
    'ARTICLE' => [
        'label' => 'Articles',
        'icon'  => 'M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z',
        'url'   => fn($r) => '/news/' . $r['id'],
        'color' => '#9A1415',
    ],
    'DOCUMENT' => [
        'label' => 'Documents',
        'icon'  => 'M7 21h10a2 2 0 0 0 2-2V9.414a1 1 0 0 0-.293-.707l-5.414-5.414A1 1 0 0 0 12.586 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2z',
        'url'   => fn($r) => $r['mediaUrl'] ?? '/documents',
        'color' => '#2B6CB0',
    ],
    'PODCAST' => [
        'label' => 'Podcasts',
        'icon'  => 'M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3',
        'url'   => fn($r) => '/podcasts',
        'color' => '#6B46C1',
    ],
    'VIDEO' => [
        'label' => 'Videos',
        'icon'  => 'M15 10l4.553-2.069A1 1 0 0 1 21 8.82v6.36a1 1 0 0 1-1.447.894L15 14M5 18h8a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2z',
        'url'   => fn($r) => '/videos',
        'color' => '#276749',
    ],
    'GALLERY_IMAGE' => [
        'label' => 'Gallery',
        'icon'  => 'M4 16l4.586-4.586a2 2 0 0 1 2.828 0L16 16m-2-2 1.586-1.586a2 2 0 0 1 2.828 0L20 14m-6-6h.01M6 20h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z',
        'url'   => fn($r) => '/gallery',
        'color' => '#C05621',
    ],
];

$results = [];
$total   = 0;

if ($q !== '') {
    try {
        $like = "%$q%";
        $stmt = db()->prepare(
            "SELECT id, title, description, thumbnailUrl, mediaUrl, type, country, issueCategory, createdAt
             FROM Post
             WHERE status = 'PUBLISHED'
               AND (title LIKE ? OR description LIKE ?)
             ORDER BY
               CASE type WHEN 'ARTICLE' THEN 1 WHEN 'DOCUMENT' THEN 2 WHEN 'PODCAST' THEN 3 WHEN 'VIDEO' THEN 4 ELSE 5 END,
               createdAt DESC
             LIMIT 60"
        );
        $stmt->execute([$like, $like]);
        $rows = $stmt->fetchAll();

        // Group by type
        foreach ($rows as $row) {
            $t = $row['type'];
            $results[$t][] = $row;
        }
        $total = count($rows);
    } catch (Exception $e) { /* DB not ready */ }
}

$pageTitle    = $q ? 'Search: ' . strip_tags($q) . ' | Tafakari Digital Hub' : 'Search | Tafakari Digital Hub';
$pageDesc     = 'Search across articles, research documents, podcasts, and videos on the Tafakari Digital Hub platform.';
$pageKeywords = 'search, Africa research, conflict news, peace studies';
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex flex-col font-inter" style="background:#F7F3EC">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- ── Search header ──────────────────────────────────────────────────────── -->
<div style="background:#0D0102" class="py-14 px-6">
  <div class="max-w-3xl mx-auto">
    <h1 class="font-outfit text-3xl md:text-4xl font-black text-white mb-6">
      <?= $q ? 'Search results for <em class="text-amber-400 not-italic">' . h($q) . '</em>' : 'Search Tafakari' ?>
    </h1>
    <!-- Search form -->
    <form method="GET" action="/search">
      <div class="relative">
        <svg class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
        <input type="text" name="q" value="<?= h($q) ?>"
               autofocus
               placeholder="Search articles, documents, podcasts, videos..."
               class="w-full pl-14 pr-32 py-4 rounded-2xl text-slate-900 text-base font-medium focus:outline-none shadow-xl"
               style="background:#fff">
        <button type="submit"
                class="absolute right-2 top-1/2 -translate-y-1/2 px-5 py-2.5 rounded-xl font-bold text-sm transition-all hover:brightness-110"
                style="background:#D99F51;color:#0D0102">
          Search
        </button>
      </div>
    </form>
  </div>
</div>

<main class="flex-grow max-w-5xl mx-auto px-6 py-10 w-full">

  <?php if ($q === ''): ?>
    <!-- ── No query yet — show suggestions ─────────────────────────────── -->
    <div class="text-center py-16">
      <div class="w-20 h-20 rounded-3xl mx-auto mb-6 flex items-center justify-center" style="background:#FBF5E6">
        <svg width="36" height="36" fill="none" stroke="#B07D2A" stroke-width="1.8" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
      </div>
      <h2 class="font-outfit font-bold text-2xl text-slate-900 mb-2">Search our knowledge base</h2>
      <p class="text-slate-500 mb-8 max-w-md mx-auto">Find articles, research reports, podcast episodes, and videos across all content on the platform.</p>
      <div class="flex flex-wrap justify-center gap-2">
        <?php foreach (['Kenya', 'Ethiopia', 'DR Congo', 'Sudan', 'Conflict', 'Governance', 'Climate', 'Peace'] as $suggestion): ?>
          <a href="/search?q=<?= urlencode($suggestion) ?>"
             class="px-4 py-2 rounded-xl bg-white border border-amber-100 text-sm font-bold text-slate-700 hover:bg-amber-50 hover:border-amber-200 transition-colors shadow-sm">
            <?= h($suggestion) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

  <?php elseif ($total === 0): ?>
    <!-- ── No results ──────────────────────────────────────────────────── -->
    <div class="text-center py-16">
      <div class="text-6xl mb-4">🔍</div>
      <h2 class="font-outfit font-bold text-2xl text-slate-900 mb-2">No results for "<?= h($q) ?>"</h2>
      <p class="text-slate-500 mb-8 max-w-md mx-auto">Try different keywords, or browse content directly from the navigation above.</p>
      <div class="flex flex-wrap justify-center gap-3">
        <a href="/news"      class="px-5 py-2.5 rounded-xl bg-white border border-amber-100 text-sm font-bold text-slate-700 hover:bg-amber-50 transition-colors">Browse News</a>
        <a href="/documents" class="px-5 py-2.5 rounded-xl bg-white border border-amber-100 text-sm font-bold text-slate-700 hover:bg-amber-50 transition-colors">Browse Documents</a>
        <a href="/podcasts"  class="px-5 py-2.5 rounded-xl bg-white border border-amber-100 text-sm font-bold text-slate-700 hover:bg-amber-50 transition-colors">Browse Podcasts</a>
        <a href="/videos"    class="px-5 py-2.5 rounded-xl bg-white border border-amber-100 text-sm font-bold text-slate-700 hover:bg-amber-50 transition-colors">Browse Videos</a>
      </div>
    </div>

  <?php else: ?>
    <!-- ── Results summary ─────────────────────────────────────────────── -->
    <div class="flex items-center justify-between mb-8">
      <p class="text-sm text-slate-500">
        Found <strong class="text-slate-900"><?= $total ?></strong> result<?= $total !== 1 ? 's' : '' ?> for
        "<strong class="text-slate-900"><?= h($q) ?></strong>"
      </p>
      <!-- Type jump links -->
      <div class="hidden md:flex items-center gap-2">
        <?php foreach ($results as $type => $items): ?>
          <a href="#results-<?= strtolower($type) ?>"
             class="px-3 py-1 rounded-xl text-[11px] font-black uppercase tracking-widest border border-amber-100 bg-white text-slate-600 hover:bg-amber-50 transition-colors">
            <?= h($typeConfig[$type]['label'] ?? $type) ?> (<?= count($items) ?>)
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ── Results by type ─────────────────────────────────────────────── -->
    <?php foreach ($results as $type => $items):
      $cfg = $typeConfig[$type] ?? ['label' => $type, 'icon' => '', 'url' => fn($r) => '#', 'color' => '#666'];
    ?>
      <section id="results-<?= strtolower($type) ?>" class="mb-12">
        <!-- Section header -->
        <div class="flex items-center gap-3 mb-5">
          <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:<?= $cfg['color'] ?>20">
            <svg width="16" height="16" fill="none" stroke="<?= $cfg['color'] ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
              <path d="<?= $cfg['icon'] ?>"/>
            </svg>
          </div>
          <h2 class="font-outfit font-bold text-xl text-slate-900"><?= h($cfg['label']) ?></h2>
          <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-amber-50 text-amber-800"><?= count($items) ?></span>
        </div>

        <!-- Result cards -->
        <div class="space-y-3">
          <?php foreach ($items as $r):
            $url = ($cfg['url'])($r);
            $isExternal = $type === 'DOCUMENT' && !empty($r['mediaUrl']) && str_starts_with($r['mediaUrl'], 'http');
          ?>
            <a href="<?= h($url) ?>"
               <?= $isExternal ? 'target="_blank" rel="noopener noreferrer"' : '' ?>
               class="flex items-start gap-4 bg-white rounded-2xl border border-amber-100 p-5 shadow-sm hover:shadow-md hover:border-amber-200 transition-all group">

              <!-- Thumbnail or icon -->
              <div class="shrink-0 w-14 h-14 rounded-xl overflow-hidden" style="background:#f1f5f9">
                <?php if (!empty($r['thumbnailUrl'])): ?>
                  <img src="<?= h($r['thumbnailUrl']) ?>" alt="" class="w-full h-full object-cover">
                <?php else: ?>
                  <div class="w-full h-full flex items-center justify-center" style="background:<?= $cfg['color'] ?>15">
                    <svg width="20" height="20" fill="none" stroke="<?= $cfg['color'] ?>" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                      <path d="<?= $cfg['icon'] ?>"/>
                    </svg>
                  </div>
                <?php endif; ?>
              </div>

              <!-- Content -->
              <div class="flex-grow min-w-0">
                <div class="flex flex-wrap gap-2 mb-1">
                  <?php if (!empty($r['country'])): ?>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest text-amber-900" style="background:#D99F51"><?= h($r['country']) ?></span>
                  <?php endif; ?>
                  <?php if (!empty($r['issueCategory'])): ?>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-slate-100 text-slate-600"><?= h($r['issueCategory']) ?></span>
                  <?php endif; ?>
                </div>
                <h3 class="font-outfit font-bold text-base text-slate-900 group-hover:text-amber-800 transition-colors line-clamp-1 mb-1"><?= h($r['title']) ?></h3>
                <?php if (!empty($r['description'])): ?>
                  <p class="text-sm text-slate-500 line-clamp-2"><?= h($r['description']) ?></p>
                <?php endif; ?>
              </div>

              <!-- Date + arrow -->
              <div class="shrink-0 text-right">
                <span class="text-xs text-slate-400 block mb-1"><?= format_date($r['createdAt']) ?></span>
                <svg width="16" height="16" fill="none" stroke="#B07D2A" stroke-width="2.5" viewBox="0 0 24 24" class="ml-auto group-hover:translate-x-0.5 transition-transform"><path d="m9 18 6-6-6-6"/></svg>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endforeach; ?>
  <?php endif; ?>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
