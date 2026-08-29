<?php
declare(strict_types=1);
http_response_code(404);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// ── Fetch 3 recent articles to suggest ──────────────────────────────────────
$recent = [];
try {
    $stmt = db()->query(
        "SELECT id, title, country, issueCategory, createdAt
         FROM Post WHERE type='ARTICLE' AND status='PUBLISHED'
         ORDER BY createdAt DESC LIMIT 3"
    );
    $recent = $stmt->fetchAll();
} catch (Exception $e) { /* ignore */ }

$pageTitle = '404 — Page Not Found | Tafakari Digital Hub';
$pageDesc  = 'The page you are looking for does not exist.';
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex flex-col font-inter" style="background:#F8F8F0">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="flex-grow flex flex-col items-center justify-center px-6 py-20 text-center">

  <!-- Error display -->
  <div class="max-w-lg mx-auto">
    <div class="relative mb-8 inline-block">
      <span class="font-outfit font-black text-9xl leading-none select-none" style="color:rgba(231,149,42,.15)">404</span>
      <div class="absolute inset-0 flex items-center justify-center">
        <div class="w-20 h-20 rounded-3xl flex items-center justify-center shadow-lg" style="background:#0D0102">
          <svg width="36" height="36" fill="none" stroke="#E7952A" stroke-width="1.8" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10"/>
            <path d="M12 8v4M12 16h.01"/>
          </svg>
        </div>
      </div>
    </div>

    <h1 class="font-outfit font-black text-3xl text-slate-900 mb-3">Page Not Found</h1>
    <p class="text-slate-500 mb-8 leading-relaxed">
      The page you are looking for may have been moved, renamed, or removed.
      Use the links below to find what you need.
    </p>

    <!-- Primary actions -->
    <div class="flex flex-wrap justify-center gap-3 mb-12">
      <a href="/" class="px-6 py-3 rounded-xl font-bold text-slate-900 transition-all hover:brightness-110" style="background:#E7952A">
        Go to Homepage
      </a>
      <a href="/news" class="px-6 py-3 rounded-xl font-bold text-slate-700 bg-white border border-amber-100 hover:bg-amber-50 transition-colors">
        Browse News
      </a>
      <a href="/search" class="px-6 py-3 rounded-xl font-bold text-slate-700 bg-white border border-amber-100 hover:bg-amber-50 transition-colors">
        Search
      </a>
    </div>

    <!-- Quick navigation -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-12 max-w-sm mx-auto sm:max-w-none">
      <?php
      $links = [
        ['href' => '/news',      'label' => 'News & Articles', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z'],
        ['href' => '/documents', 'label' => 'Documents',       'icon' => 'M7 21h10a2 2 0 0 0 2-2V9.414a1 1 0 0 0-.293-.707l-5.414-5.414A1 1 0 0 0 12.586 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2z'],
        ['href' => '/gallery',   'label' => 'Gallery',         'icon' => 'M4 16l4.586-4.586a2 2 0 0 1 2.828 0L16 16m-2-2 1.586-1.586a2 2 0 0 1 2.828 0L20 14m-6-6h.01M6 20h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z'],
        ['href' => '/podcasts',  'label' => 'Podcasts',        'icon' => 'M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3'],
        ['href' => '/videos',    'label' => 'Videos',          'icon' => 'M15 10l4.553-2.069A1 1 0 0 1 21 8.82v6.36a1 1 0 0 1-1.447.894L15 14M5 18h8a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2z'],
        ['href' => '/heatmap',   'label' => 'Heatmap',         'icon' => 'M3.055 11H5a2 2 0 0 1 2 2v1a2 2 0 0 0 2 2 2 2 0 0 1 2 2v2.945M8 3.935V5.5A2.5 2.5 0 0 0 10.5 8h.5a2 2 0 0 1 2 2 2 2 0 1 0 4 0 2 2 0 0 1 2-2h1.064'],
      ];
      foreach ($links as $l): ?>
        <a href="<?= h($l['href']) ?>" class="flex items-center gap-2.5 px-4 py-3 rounded-xl bg-white border border-amber-100 text-sm font-bold text-slate-700 hover:bg-amber-50 hover:border-amber-200 transition-colors shadow-sm">
          <svg width="15" height="15" fill="none" stroke="#750B25" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <path d="<?= $l['icon'] ?>"/>
          </svg>
          <?= h($l['label']) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Recent articles -->
    <?php if (!empty($recent)): ?>
      <div class="text-left">
        <p class="text-xs font-black uppercase tracking-widest text-slate-400 mb-4 text-center">Recent Articles</p>
        <div class="space-y-3">
          <?php foreach ($recent as $r): ?>
            <a href="/news/<?= h($r['id']) ?>"
               class="flex items-center justify-between gap-3 bg-white rounded-xl border border-amber-100 px-5 py-3.5 shadow-sm hover:shadow-md hover:border-amber-200 transition-all group">
              <div class="min-w-0">
                <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest text-amber-900 mr-2" style="background:#E7952A"><?= h($r['country']) ?></span>
                <span class="text-sm font-bold text-slate-800 group-hover:text-amber-800 transition-colors"><?= h($r['title']) ?></span>
              </div>
              <svg width="14" height="14" fill="none" stroke="#750B25" stroke-width="2.5" viewBox="0 0 24 24" class="shrink-0 group-hover:translate-x-0.5 transition-transform"><path d="m9 18 6-6-6-6"/></svg>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
