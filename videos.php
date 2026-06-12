<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$videos = [];
try {
    $stmt = db()->query(
        "SELECT id, title, description, thumbnailUrl, mediaUrl, country, issueCategory, viewCount, createdAt
         FROM Post WHERE type = 'VIDEO' AND status = 'PUBLISHED'
         ORDER BY createdAt DESC LIMIT 12"
    );
    $videos = $stmt->fetchAll();
} catch (Exception $e) { /* ignore */ }

$pageTitle = 'Video Library | Tafakari Digital Hub';
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex flex-col bg-slate-50 font-inter">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="flex-grow max-w-7xl mx-auto px-6 py-16">
  <div class="mb-12">
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-black uppercase tracking-widest mb-4">Video Archive</div>
    <h1 class="font-outfit text-4xl font-black text-slate-900">Video Library</h1>
    <p class="text-slate-500 mt-2">Documentaries, field reports, and community stories on video.</p>
  </div>

  <?php if (empty($videos)): ?>
    <div class="text-center py-24">
      <div class="text-5xl mb-4">🎬</div>
      <h3 class="font-outfit font-bold text-xl text-slate-900">No videos yet</h3>
      <p class="text-slate-400 mt-2 text-sm">Video content will appear here once published.</p>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
      <?php foreach ($videos as $v): ?>
        <?php $wrapper = !empty($v['mediaUrl']) ? '<a href="' . h($v['mediaUrl']) . '" target="_blank" rel="noopener noreferrer">' : '<div>'; ?>
        <?= $wrapper ?>
          <div class="group bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden hover:shadow-md hover:-translate-y-1 transition-all">
            <div class="relative h-52 bg-slate-900 overflow-hidden">
              <?php if (!empty($v['thumbnailUrl'])): ?>
                <img src="<?= h($v['thumbnailUrl']) ?>" alt="<?= h($v['title']) ?>"
                     class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition-opacity group-hover:scale-105 duration-500">
              <?php endif; ?>
              <!-- Play overlay -->
              <div class="absolute inset-0 flex items-center justify-center">
                <div class="w-16 h-16 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center border border-white/30 group-hover:scale-110 transition-transform">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white ml-1" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                </div>
              </div>
              <?php if ($v['viewCount'] > 0): ?>
                <div class="absolute top-4 right-4 px-2 py-1 rounded-full text-[10px] font-black text-white" style="background:rgba(0,0,0,.6)">
                  👁 <?= format_number((int)$v['viewCount']) ?>
                </div>
              <?php endif; ?>
            </div>
            <div class="p-6">
              <div class="flex flex-wrap gap-2 mb-3">
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-slate-100 text-slate-600"><?= h($v['country']) ?></span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-amber-50 text-amber-700"><?= h($v['issueCategory']) ?></span>
              </div>
              <h3 class="font-outfit font-bold text-base uppercase tracking-tight text-slate-900 mb-2 line-clamp-2"><?= h($v['title']) ?></h3>
              <?php if (!empty($v['description'])): ?>
                <p class="text-sm text-slate-500 line-clamp-3 mb-4"><?= h($v['description']) ?></p>
              <?php endif; ?>
              <span class="text-xs text-slate-400"><?= format_date($v['createdAt']) ?></span>
            </div>
          </div>
        <?= !empty($v['mediaUrl']) ? '</a>' : '</div>' ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
