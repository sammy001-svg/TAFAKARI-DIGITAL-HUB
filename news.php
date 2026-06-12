<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// ── Filter params ──────────────────────────────────────────────────────────────
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$q       = trim($_GET['q'] ?? '');
$country = trim($_GET['country'] ?? '');
$cat     = trim($_GET['cat'] ?? '');

// ── Build dynamic query ────────────────────────────────────────────────────────
$where  = ["p.type = 'ARTICLE'", "p.status = 'PUBLISHED'"];
$params = [];
if ($q !== '') {
    $where[]  = '(p.title LIKE ? OR p.description LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($country !== '') {
    $where[]  = 'p.country = ?';
    $params[] = $country;
}
if ($cat !== '') {
    $where[]  = 'p.issueCategory = ?';
    $params[] = $cat;
}
$whereStr = implode(' AND ', $where);

$total         = 0;
$pages         = 1;
$posts         = [];
$countryOptions = [];

try {
    // Distinct countries for filter dropdown
    $cs = db()->query(
        "SELECT DISTINCT country FROM Post WHERE type='ARTICLE' AND status='PUBLISHED' AND country IS NOT NULL AND country != '' ORDER BY country"
    );
    $countryOptions = $cs->fetchAll(PDO::FETCH_COLUMN);

    // Total count
    $countStmt = db()->prepare("SELECT COUNT(*) FROM Post p WHERE $whereStr");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();
    $pages = max(1, (int) ceil($total / $perPage));
    $page  = min($page, $pages);
    $off   = ($page - 1) * $perPage;

    // Fetch page
    $stmt = db()->prepare(
        "SELECT p.id, p.title, p.description, p.thumbnailUrl, p.country,
                p.issueCategory, p.createdAt, p.viewCount,
                u.name AS authorName
         FROM Post p LEFT JOIN User u ON p.authorId = u.id
         WHERE $whereStr ORDER BY p.createdAt DESC
         LIMIT $perPage OFFSET $off"
    );
    $stmt->execute($params);
    $posts = $stmt->fetchAll();
} catch (Exception $e) { /* DB not ready */ }

// ── URL builder helper ─────────────────────────────────────────────────────────
function news_url(int $n, string $q, string $country, string $cat): string {
    $p = array_filter(['q' => $q, 'country' => $country, 'cat' => $cat, 'page' => $n > 1 ? $n : ''],
        fn($v) => $v !== '' && $v !== null);
    return '/news' . ($p ? '?' . http_build_query($p) : '');
}

$hasFilter = $q !== '' || $country !== '' || $cat !== '';
$pageTitle = 'News & Articles | Tafakari Digital Hub';
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex flex-col font-inter" style="background:#F8F8F0">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- ── Page header ──────────────────────────────────────────────────────────── -->
<div style="background:#0D0102" class="py-16 px-6">
  <div class="max-w-7xl mx-auto">
    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-4 text-amber-900" style="background:#E7952A">News & Analysis</span>
    <h1 class="font-outfit text-4xl md:text-5xl font-black text-white leading-tight mb-3">News & Articles</h1>
    <p class="text-white/60 max-w-xl">In-depth reporting and analysis from across the African continent — conflict, governance, climate, and community voices.</p>
  </div>
</div>

<main class="flex-grow max-w-7xl mx-auto px-6 py-12 w-full">

  <!-- ── Filter panel ─────────────────────────────────────────────────────── -->
  <div class="bg-white rounded-2xl border border-amber-100 shadow-sm p-5 mb-8">
    <form method="GET" action="/news" class="flex flex-wrap gap-3 items-end">
      <div class="flex-1 min-w-[200px]">
        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Search</label>
        <div class="relative">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search articles..."
                 class="w-full pl-9 pr-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2"
                 style="--tw-ring-color:#E7952A">
        </div>
      </div>
      <div>
        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Country</label>
        <select name="country" class="px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none appearance-none pr-8"
                style="background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23666' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E\");background-repeat:no-repeat;background-position:right 12px center">
          <option value="">All Countries</option>
          <?php foreach ($countryOptions as $c): ?>
            <option value="<?= h($c) ?>" <?= $country === $c ? 'selected' : '' ?>><?= h($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Category</label>
        <select name="cat" class="px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none appearance-none pr-8"
                style="background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23666' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E\");background-repeat:no-repeat;background-position:right 12px center">
          <option value="">All Categories</option>
          <?php foreach (issue_categories() as $c): ?>
            <option value="<?= h($c) ?>" <?= $cat === $c ? 'selected' : '' ?>><?= h($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="px-6 py-3 rounded-xl font-bold text-sm transition-all hover:brightness-110"
              style="background:#E7952A;color:#0D0102">
        Apply Filters
      </button>
      <?php if ($hasFilter): ?>
        <a href="/news" class="px-6 py-3 rounded-xl font-bold text-sm border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
          Clear
        </a>
      <?php endif; ?>
    </form>
  </div>

  <!-- ── Results meta ──────────────────────────────────────────────────────── -->
  <div class="flex items-center justify-between mb-6">
    <p class="text-sm text-slate-500">
      <?php if ($total === 0): ?>
        No articles found<?= $hasFilter ? ' — try different filters' : '' ?>.
      <?php elseif ($hasFilter): ?>
        <strong class="text-slate-800"><?= $total ?></strong> result<?= $total !== 1 ? 's' : '' ?> found
        <?php if ($q): ?> for "<em><?= h($q) ?></em>"<?php endif; ?>
      <?php else: ?>
        Showing <strong class="text-slate-800"><?= min($off + 1, $total) ?>–<?= min($off + $perPage, $total) ?></strong> of <strong class="text-slate-800"><?= $total ?></strong> articles
      <?php endif; ?>
    </p>
    <?php if ($pages > 1): ?>
      <span class="text-xs text-slate-400">Page <?= $page ?> of <?= $pages ?></span>
    <?php endif; ?>
  </div>

  <!-- ── Article grid ──────────────────────────────────────────────────────── -->
  <?php if (empty($posts)): ?>
    <div class="text-center py-24 bg-white rounded-3xl border border-slate-100">
      <div class="text-6xl mb-4">📰</div>
      <h3 class="font-outfit font-bold text-xl text-slate-900">No articles found</h3>
      <p class="text-slate-400 mt-2 text-sm mb-6">
        <?= $hasFilter ? 'Try broadening your search or clearing filters.' : 'Check back soon for the latest news.' ?>
      </p>
      <?php if ($hasFilter): ?>
        <a href="/news" class="inline-block px-6 py-3 rounded-xl font-bold text-sm" style="background:#E7952A;color:#0D0102">
          Clear Filters
        </a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-7">
      <?php foreach ($posts as $p): ?>
        <a href="/news/<?= h($p['id']) ?>"
           class="group bg-white rounded-3xl border border-amber-100 overflow-hidden shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 flex flex-col">

          <!-- Thumbnail -->
          <div class="h-48 overflow-hidden relative" style="background:#0D0102">
            <?php if (!empty($p['thumbnailUrl'])): ?>
              <img src="<?= h($p['thumbnailUrl']) ?>" alt="<?= h($p['title']) ?>"
                   class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 opacity-90 group-hover:opacity-100">
            <?php else: ?>
              <div class="w-full h-full flex items-center justify-center" style="background:#0D0102">
                <svg width="40" height="40" fill="none" stroke="#E7952A" stroke-width="1.5" opacity=".4" viewBox="0 0 24 24">
                  <path d="M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z"/>
                  <path d="M9 3v18M9 9h10"/>
                </svg>
              </div>
            <?php endif; ?>
            <!-- Country badge -->
            <div class="absolute top-3 left-3">
              <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest text-slate-900" style="background:#E7952A">
                <?= h($p['country']) ?>
              </span>
            </div>
          </div>

          <!-- Content -->
          <div class="p-6 flex flex-col flex-grow">
            <span class="text-[10px] font-black uppercase tracking-widest mb-2 block" style="color:#C47C1A">
              <?= h($p['issueCategory']) ?>
            </span>
            <h3 class="font-outfit font-bold text-lg leading-snug text-slate-900 group-hover:text-amber-800 transition-colors line-clamp-2 mb-2 flex-grow">
              <?= h($p['title']) ?>
            </h3>
            <?php if (!empty($p['description'])): ?>
              <p class="text-slate-500 text-sm leading-relaxed line-clamp-2 mb-4"><?= h($p['description']) ?></p>
            <?php endif; ?>
            <div class="flex items-center justify-between mt-auto pt-4 border-t border-amber-50">
              <span class="text-xs text-slate-400"><?= format_date($p['createdAt']) ?></span>
              <span class="text-xs font-bold" style="color:#C47C1A">Read Full &rarr;</span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- ── Pagination ──────────────────────────────────────────────────────── -->
    <?php if ($pages > 1): ?>
      <nav class="flex items-center justify-center gap-2 mt-12" aria-label="Pagination">
        <!-- Prev -->
        <?php if ($page > 1): ?>
          <a href="<?= h(news_url($page - 1, $q, $country, $cat)) ?>"
             class="w-10 h-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-600 hover:bg-amber-50 transition-colors text-lg font-light">
            &#8249;
          </a>
        <?php else: ?>
          <span class="w-10 h-10 rounded-xl border border-slate-100 flex items-center justify-center text-slate-300 text-lg cursor-not-allowed">&#8249;</span>
        <?php endif; ?>

        <!-- Pages window -->
        <?php
        $start = max(1, $page - 2);
        $end   = min($pages, $page + 2);
        if ($start > 1): ?>
          <a href="<?= h(news_url(1, $q, $country, $cat)) ?>" class="w-10 h-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-sm font-bold text-slate-600 hover:bg-amber-50 transition-colors">1</a>
          <?php if ($start > 2): ?><span class="text-slate-400 px-1">…</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
          <a href="<?= h(news_url($i, $q, $country, $cat)) ?>"
             class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold transition-colors
                    <?= $i === $page ? 'text-slate-900 border-2' : 'border border-slate-200 bg-white text-slate-600 hover:bg-amber-50' ?>"
             style="<?= $i === $page ? 'border-color:#E7952A;background:#F8F8F0' : '' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>

        <?php if ($end < $pages): ?>
          <?php if ($end < $pages - 1): ?><span class="text-slate-400 px-1">…</span><?php endif; ?>
          <a href="<?= h(news_url($pages, $q, $country, $cat)) ?>" class="w-10 h-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-sm font-bold text-slate-600 hover:bg-amber-50 transition-colors"><?= $pages ?></a>
        <?php endif; ?>

        <!-- Next -->
        <?php if ($page < $pages): ?>
          <a href="<?= h(news_url($page + 1, $q, $country, $cat)) ?>"
             class="w-10 h-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-600 hover:bg-amber-50 transition-colors text-lg font-light">
            &#8250;
          </a>
        <?php else: ?>
          <span class="w-10 h-10 rounded-xl border border-slate-100 flex items-center justify-center text-slate-300 text-lg cursor-not-allowed">&#8250;</span>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
