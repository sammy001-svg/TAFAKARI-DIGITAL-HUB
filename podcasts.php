<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$podcasts = [];
try {
    $stmt = db()->query(
        "SELECT id, title, description, mediaUrl, country, issueCategory, createdAt
         FROM Post WHERE type = 'PODCAST' AND status = 'PUBLISHED'
         ORDER BY createdAt DESC LIMIT 20"
    );
    $podcasts = $stmt->fetchAll();
} catch (Exception $e) { /* ignore */ }

$pageTitle = 'Podcast Library | Tafakari Digital Hub';
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex flex-col bg-slate-50 font-inter">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="flex-grow max-w-4xl mx-auto px-6 py-16">
  <div class="mb-12">
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-black uppercase tracking-widest mb-4">Audio Stories</div>
    <h1 class="font-outfit text-4xl font-black text-slate-900">Podcast Library</h1>
    <p class="text-slate-500 mt-2">Interviews, field discussions, and community voices from across the region.</p>
  </div>

  <?php if (empty($podcasts)): ?>
    <div class="text-center py-24">
      <div class="text-5xl mb-4">🎧</div>
      <h3 class="font-outfit font-bold text-xl text-slate-900">No episodes yet</h3>
      <p class="text-slate-400 mt-2 text-sm">Podcast episodes will appear here once published.</p>
    </div>
  <?php else: ?>
    <div class="space-y-4 mb-16">
      <?php foreach ($podcasts as $ep): ?>
        <div class="bg-white rounded-3xl border border-slate-100 p-6 shadow-sm flex items-start gap-6 hover:shadow-md transition-shadow">
          <!-- Play button -->
          <div class="shrink-0">
            <?php if (!empty($ep['mediaUrl'])): ?>
              <a href="<?= h($ep['mediaUrl']) ?>" target="_blank" rel="noopener noreferrer"
                 class="w-14 h-14 rounded-2xl flex items-center justify-center text-white shadow-lg hover:scale-105 transition-transform"
                 style="background:#9A1415">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 ml-1" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
              </a>
            <?php else: ?>
              <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-white bg-slate-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 ml-1" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
              </div>
            <?php endif; ?>
          </div>

          <div class="flex-grow min-w-0">
            <div class="flex flex-wrap gap-2 mb-2">
              <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-amber-50 text-amber-700"><?= h($ep['issueCategory']) ?></span>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-slate-100 text-slate-600"><?= h($ep['country']) ?></span>
            </div>
            <h3 class="font-outfit font-bold text-lg text-slate-900 mb-1 line-clamp-2"><?= h($ep['title']) ?></h3>
            <?php if (!empty($ep['description'])): ?>
              <p class="text-sm text-slate-500 line-clamp-2 mb-3"><?= h($ep['description']) ?></p>
            <?php endif; ?>
            <div class="flex items-center gap-4">
              <span class="text-xs text-slate-400"><?= format_date($ep['createdAt']) ?></span>
              <?php if (!empty($ep['mediaUrl'])): ?>
                <a href="<?= h($ep['mediaUrl']) ?>" target="_blank" rel="noopener noreferrer"
                   class="text-xs font-bold hover:underline" style="color:#9A1415">
                  Listen &rarr;
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Subscribe section -->
  <div class="bg-white rounded-3xl border border-slate-100 p-8 text-center shadow-sm">
    <h3 class="font-outfit font-bold text-xl mb-2 text-slate-900">Subscribe to Our Podcast</h3>
    <p class="text-slate-500 text-sm mb-6">Never miss an episode. Available on all major platforms.</p>
    <div class="flex flex-wrap justify-center gap-3">
      <?php foreach (['Spotify', 'Apple Podcasts', 'Google Podcasts'] as $platform): ?>
        <span class="px-5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm font-bold text-slate-500 cursor-not-allowed">
          <?= h($platform) ?>
        </span>
      <?php endforeach; ?>
    </div>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
