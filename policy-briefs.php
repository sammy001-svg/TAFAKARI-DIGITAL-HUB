<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// ── Filter params ──────────────────────────────────────────────────────────────
$q       = trim($_GET['q']       ?? '');
$country = trim($_GET['country'] ?? '');
$cat     = trim($_GET['cat']     ?? '');

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$pages   = 1;

$briefs         = [];
$countryOptions = [];
$total          = 0;

// ── Build query ────────────────────────────────────────────────────────────────
$where  = ["type = 'POLICY_BRIEF'", "status = 'PUBLISHED'"];
$params = [];
if ($q !== '') {
    $where[]  = '(title LIKE ? OR description LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}
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
    ensure_policy_brief_post_type();

    $cs = db()->query(
        "SELECT DISTINCT country FROM Post WHERE type='POLICY_BRIEF' AND status='PUBLISHED' AND country IS NOT NULL AND country != '' ORDER BY country"
    );
    $countryOptions = $cs->fetchAll(PDO::FETCH_COLUMN);

    $countStmt = db()->prepare("SELECT COUNT(*) FROM Post WHERE $whereStr");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();
    $pages = max(1, (int) ceil($total / $perPage));
    $page  = min($page, $pages);
    $off   = ($page - 1) * $perPage;

    $stmt = db()->prepare(
        "SELECT id, title, description, thumbnailUrl, mediaUrl, country, issueCategory, downloadCount, createdAt
         FROM Post WHERE $whereStr ORDER BY createdAt DESC LIMIT $perPage OFFSET $off"
    );
    $stmt->execute($params);
    $briefs = $stmt->fetchAll();
} catch (Exception $e) { /* DB not ready */ }

function pb_url(int $n, string $q, string $country, string $cat): string {
    $p = array_filter(['q' => $q, 'country' => $country, 'cat' => $cat, 'page' => $n > 1 ? $n : ''],
        fn($v) => $v !== '' && $v !== null);
    return '/policy-briefs' . ($p ? '?' . http_build_query($p) : '');
}

$hasFilter = $q !== '' || $country !== '' || $cat !== '';
$pageTitle = 'Policy Briefs | Tafakari Digital Hub';
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex flex-col font-inter" style="background:#F8F8F0">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- ── Page header ──────────────────────────────────────────────────────────── -->
<div style="background:#0D0102" class="py-16 px-6">
  <div class="max-w-7xl mx-auto">
    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-4 text-amber-900" style="background:#E7952A" data-i18n="policyBriefsPage.badge">Policy & Analysis</span>
    <h1 class="font-outfit text-4xl md:text-5xl font-black text-white leading-tight mb-3" data-i18n="policyBriefsPage.title">Policy Briefs</h1>
    <p class="text-white/60 max-w-xl" data-i18n="policyBriefsPage.desc">Concise, actionable analysis and recommendations for policymakers, drawn from our field research across the region.</p>
  </div>
</div>

<main class="flex-grow max-w-7xl mx-auto px-6 py-12 w-full">

  <!-- ── Filter panel ─────────────────────────────────────────────────────── -->
  <div class="bg-white rounded-2xl border border-amber-100 shadow-sm p-5 mb-8">
    <form method="GET" action="/policy-briefs" class="flex flex-wrap gap-3 items-end">
      <div class="flex-1 min-w-[200px]">
        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5" data-i18n="policyBriefsPage.searchLabel">Search</label>
        <div class="relative">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search policy briefs..."
                 data-i18n-placeholder="policyBriefsPage.searchPlaceholder"
                 class="w-full pl-9 pr-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2"
                 style="--tw-ring-color:#E7952A">
        </div>
      </div>
      <?php if (!empty($countryOptions)): ?>
        <div>
          <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5" data-i18n="heatmapPage.country">Country</label>
          <select name="country" class="px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none">
            <option value="" data-i18n="heatmapPage.allCountries">All Countries</option>
            <?php foreach ($countryOptions as $c): ?>
              <option value="<?= h($c) ?>" <?= $country === $c ? 'selected' : '' ?>><?= h($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>
      <div>
        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5" data-i18n="heatmapPage.category">Category</label>
        <select name="cat" class="px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none">
          <option value="" data-i18n="listPage.allCategories">All Categories</option>
          <?php foreach (issue_categories() as $c): ?>
            <option value="<?= h($c) ?>" <?= $cat === $c ? 'selected' : '' ?>><?= h($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="px-6 py-3 rounded-xl font-bold text-sm transition-all hover:brightness-110"
              style="background:#E7952A;color:#0D0102" data-i18n="policyBriefsPage.search">
        Search
      </button>
      <?php if ($hasFilter): ?>
        <a href="/policy-briefs" class="px-6 py-3 rounded-xl font-bold text-sm border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors" data-i18n="listPage.clear">Clear</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- ── Results meta ──────────────────────────────────────────────────────── -->
  <div class="flex items-center justify-between mb-6">
    <p class="text-sm text-slate-500">
      <?php if ($total === 0): ?>
        <span data-i18n="policyBriefsPage.noBriefsFound">No policy briefs found</span><?= $hasFilter ? ' — <span data-i18n="documentsPage.tryDifferentFilters">try different filters</span>' : '' ?>.
      <?php elseif ($hasFilter): ?>
        <strong class="text-slate-800"><?= $total ?></strong> <span data-i18n="policyBriefsPage.briefsFound">briefs found</span>
        <?php if ($q): ?> <span data-i18n="documentsPage.forQuery">for</span> "<em><?= h($q) ?></em>"<?php endif; ?>
      <?php else: ?>
        <span data-i18n="galleryPage.showing">Showing</span> <strong class="text-slate-800"><?= min($off + 1, $total) ?>–<?= min($off + $perPage, $total) ?></strong> <span data-i18n="galleryPage.of">of</span> <strong class="text-slate-800"><?= $total ?></strong> <span data-i18n="policyBriefsPage.briefsPlural">policy briefs</span>
      <?php endif; ?>
    </p>
    <?php if ($pages > 1): ?>
      <span class="text-xs text-slate-400"><span data-i18n="galleryPage.page">Page</span> <?= $page ?> <span data-i18n="galleryPage.of">of</span> <?= $pages ?></span>
    <?php endif; ?>
  </div>

  <!-- ── Policy brief cards ───────────────────────────────────────────────── -->
  <?php if (empty($briefs)): ?>
    <div class="text-center py-24 bg-white rounded-3xl border border-amber-100">
      <div class="text-6xl mb-4">📜</div>
      <h3 class="font-outfit font-bold text-xl text-slate-900" data-i18n="policyBriefsPage.noBriefsFound">No policy briefs found</h3>
      <p class="text-slate-400 mt-2 text-sm mb-6" data-i18n="<?= $hasFilter ? 'documentsPage.tryBroadening' : 'policyBriefsPage.willAppear' ?>">
        <?= $hasFilter ? 'Try broadening your search or clearing filters.' : 'Policy briefs will appear here once published.' ?>
      </p>
      <?php if ($hasFilter): ?>
        <a href="/policy-briefs" class="inline-block px-6 py-3 rounded-xl font-bold text-sm" style="background:#E7952A;color:#0D0102" data-i18n="documentsPage.clearFilters">Clear Filters</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-7">
      <?php foreach ($briefs as $b): ?>
        <div class="group bg-white rounded-3xl border border-amber-100 overflow-hidden shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 flex flex-col">

          <a href="/policy-briefs/<?= h($b['id']) ?>" class="block h-48 overflow-hidden relative" style="background:#0D0102">
            <?php if (!empty($b['thumbnailUrl'])): ?>
              <img src="<?= h($b['thumbnailUrl']) ?>" alt="<?= h($b['title']) ?>"
                   class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 opacity-90 group-hover:opacity-100">
            <?php else: ?>
              <div class="w-full h-full flex items-center justify-center" style="background:#0D0102">
                <svg width="40" height="40" fill="none" stroke="#E7952A" stroke-width="1.5" opacity=".4" viewBox="0 0 24 24">
                  <path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                  <path d="M13 3v6h6M9 13h6M9 17h6M9 9h1"/>
                </svg>
              </div>
            <?php endif; ?>
            <div class="absolute top-3 left-3">
              <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest text-slate-900" style="background:#E7952A">
                <?= h($b['country']) ?>
              </span>
            </div>
          </a>

          <!-- Content -->
          <div class="p-6 flex flex-col flex-grow">
            <span class="text-[10px] font-black uppercase tracking-widest mb-2 block" style="color:#C47C1A">
              <?= h($b['issueCategory']) ?>
            </span>
            <h3 class="font-outfit font-bold text-lg leading-snug text-slate-900 mb-2 flex-grow">
              <a href="/policy-briefs/<?= h($b['id']) ?>" class="group-hover:text-amber-800 transition-colors line-clamp-2"><?= h($b['title']) ?></a>
            </h3>
            <?php if (!empty($b['description'])): ?>
              <p class="text-slate-500 text-sm leading-relaxed line-clamp-2 mb-4"><?= h($b['description']) ?></p>
            <?php endif; ?>
            <div class="flex items-center justify-between mt-auto pt-4 border-t border-amber-50 gap-3">
              <span class="text-xs text-slate-400 shrink-0"><?= format_date($b['createdAt']) ?></span>
              <div class="flex items-center gap-3 shrink-0">
                <?php if (!empty($b['mediaUrl'])): ?>
                  <a href="/policy-briefs/<?= h($b['id']) ?>?download=1"
                     title="Download"
                     class="flex items-center gap-1 text-xs font-bold hover:brightness-110 transition-all" style="color:#C47C1A">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7,10 12,15 17,10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    <span data-i18n="documentsPage.download">Download</span>
                  </a>
                <?php endif; ?>
                <a href="/policy-briefs/<?= h($b['id']) ?>" class="text-xs font-bold" style="color:#C47C1A">
                  <span data-i18n="policyBriefsPage.readMore">Read More</span> &rarr;
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- ── Pagination ────────────────────────────────────────────────────── -->
    <?php if ($pages > 1): ?>
      <nav class="flex items-center justify-center gap-2 mt-12" aria-label="Pagination">
        <?php if ($page > 1): ?>
          <a href="<?= h(pb_url($page - 1, $q, $country, $cat)) ?>"
             class="w-10 h-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-600 hover:bg-amber-50 transition-colors text-lg font-light">&#8249;</a>
        <?php else: ?>
          <span class="w-10 h-10 rounded-xl border border-slate-100 flex items-center justify-center text-slate-300 text-lg cursor-not-allowed">&#8249;</span>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 2);
        $end   = min($pages, $page + 2);
        if ($start > 1): ?>
          <a href="<?= h(pb_url(1, $q, $country, $cat)) ?>" class="w-10 h-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-sm font-bold text-slate-600 hover:bg-amber-50 transition-colors">1</a>
          <?php if ($start > 2): ?><span class="text-slate-400 px-1">…</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
          <a href="<?= h(pb_url($i, $q, $country, $cat)) ?>"
             class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold transition-colors <?= $i === $page ? 'text-slate-900 border-2' : 'border border-slate-200 bg-white text-slate-600 hover:bg-amber-50' ?>"
             style="<?= $i === $page ? 'border-color:#E7952A;background:#F8F8F0' : '' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>

        <?php if ($end < $pages): ?>
          <?php if ($end < $pages - 1): ?><span class="text-slate-400 px-1">…</span><?php endif; ?>
          <a href="<?= h(pb_url($pages, $q, $country, $cat)) ?>" class="w-10 h-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-sm font-bold text-slate-600 hover:bg-amber-50 transition-colors"><?= $pages ?></a>
        <?php endif; ?>

        <?php if ($page < $pages): ?>
          <a href="<?= h(pb_url($page + 1, $q, $country, $cat)) ?>"
             class="w-10 h-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-600 hover:bg-amber-50 transition-colors text-lg font-light">&#8250;</a>
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
