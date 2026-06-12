<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$posts = [];
try {
    $stmt = db()->query(
        "SELECT p.id, p.title, p.description, p.thumbnailUrl, p.country,
                p.issueCategory, p.createdAt, p.viewCount,
                u.name AS authorName, u.username AS authorUsername
         FROM Post p LEFT JOIN User u ON p.authorId = u.id
         WHERE p.type = 'ARTICLE' AND p.status = 'PUBLISHED'
         ORDER BY p.createdAt DESC LIMIT 12"
    );
    $posts = $stmt->fetchAll();
} catch (Exception $e) { /* DB not ready */ }

$pageTitle = 'News & Articles | Tafakari Digital Hub';
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex flex-col bg-slate-50 font-inter">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="flex-grow max-w-7xl mx-auto px-6 py-16">
  <div class="mb-12">
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-black uppercase tracking-widest mb-4">News & Analysis</div>
    <h1 class="font-outfit text-4xl font-black text-slate-900">News & Articles</h1>
    <p class="text-slate-500 mt-2">In-depth reporting from Kenya, Ethiopia, and DR Congo.</p>
  </div>

  <?php if (empty($posts)): ?>
    <div class="text-center py-24">
      <div class="text-5xl mb-4">📰</div>
      <h3 class="font-outfit font-bold text-xl text-slate-900">No articles published yet</h3>
      <p class="text-slate-400 mt-2 text-sm">Check back soon for the latest news.</p>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <?php foreach ($posts as $p): ?>
        <a href="/news/<?= h($p['id']) ?>" class="group bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-sm hover:shadow-md hover:-translate-y-1 transition-all flex flex-col">
          <div class="h-48 bg-slate-100 overflow-hidden relative">
            <?php if (!empty($p['thumbnailUrl'])): ?>
              <img src="<?= h($p['thumbnailUrl']) ?>" alt="<?= h($p['title']) ?>"
                   class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
            <?php else: ?>
              <div class="w-full h-full flex items-center justify-center text-4xl opacity-10" style="background:#9A1415">📰</div>
            <?php endif; ?>
            <div class="absolute top-4 left-4">
              <span class="glass-dark px-2 py-1 rounded-full text-[10px] font-bold text-white uppercase" style="background:var(--glass-bg)">
                <?= h($p['country']) ?>
              </span>
            </div>
          </div>
          <div class="p-6 flex flex-col flex-grow">
            <span class="text-[10px] font-black uppercase tracking-widest mb-2 block" style="color:#D99F51">
              <?= h($p['issueCategory']) ?>
            </span>
            <h3 class="font-outfit font-bold text-lg leading-snug group-hover:text-primary transition-colors line-clamp-2 mb-2 flex-grow">
              <?= h($p['title']) ?>
            </h3>
            <?php if (!empty($p['description'])): ?>
              <p class="text-slate-500 text-sm leading-relaxed line-clamp-3 mb-4"><?= h($p['description']) ?></p>
            <?php endif; ?>
            <div class="flex items-center justify-between mt-auto pt-4 border-t border-slate-50">
              <span class="text-xs text-slate-400"><?= format_date($p['createdAt']) ?></span>
              <span class="text-xs font-bold" style="color:#9A1415">Read Full &rarr;</span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
