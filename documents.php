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

$docs           = [];
$countryOptions = [];
$total          = 0;

// ── Build query ────────────────────────────────────────────────────────────────
$where  = ["type = 'DOCUMENT'", "status = 'PUBLISHED'"];
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
    $cs = db()->query(
        "SELECT DISTINCT country FROM Post WHERE type='DOCUMENT' AND status='PUBLISHED' AND country IS NOT NULL AND country != '' ORDER BY country"
    );
    $countryOptions = $cs->fetchAll(PDO::FETCH_COLUMN);

    $countStmt = db()->prepare("SELECT COUNT(*) FROM Post WHERE $whereStr");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();
    $pages = max(1, (int) ceil($total / $perPage));
    $page  = min($page, $pages);
    $off   = ($page - 1) * $perPage;

    $stmt = db()->prepare(
        "SELECT id, title, description, mediaUrl, country, region, issueCategory, downloadCount, createdAt
         FROM Post WHERE $whereStr ORDER BY createdAt DESC LIMIT $perPage OFFSET $off"
    );
    $stmt->execute($params);
    $docs = $stmt->fetchAll();
} catch (Exception $e) { /* DB not ready */ }

function doc_url(int $n, string $q, string $country, string $cat): string {
    $p = array_filter(['q' => $q, 'country' => $country, 'cat' => $cat, 'page' => $n > 1 ? $n : ''],
        fn($v) => $v !== '' && $v !== null);
    return '/documents' . ($p ? '?' . http_build_query($p) : '');
}

$hasFilter = $q !== '' || $country !== '' || $cat !== '';
$pageTitle = 'Document Library | Tafakari Digital Hub';

// ── File type helper ───────────────────────────────────────────────────────────
function doc_ext(string $url): string {
    $path = parse_url($url, PHP_URL_PATH) ?? '';
    return strtoupper(pathinfo($path, PATHINFO_EXTENSION));
}

function doc_icon_color(string $ext): string {
    return match ($ext) {
        'PDF'  => '#E53E3E',
        'DOC', 'DOCX' => '#2B6CB0',
        'XLS', 'XLSX' => '#276749',
        'PPT', 'PPTX' => '#C05621',
        'ZIP', 'RAR'  => '#744210',
        default        => '#4A5568',
    };
}
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex flex-col font-inter" style="background:#F8F8F0">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- ── Page header ──────────────────────────────────────────────────────────── -->
<div style="background:#0D0102" class="py-14 px-6">
  <div class="max-w-7xl mx-auto">
    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-4 text-amber-900" style="background:#E7952A" data-i18n="documentsPage.badge">Knowledge Repository</span>
    <h1 class="font-outfit text-4xl md:text-5xl font-black text-white leading-tight mb-3" data-i18n="documentsPage.title">Document Library</h1>
    <p class="text-white/60 max-w-xl" data-i18n="documentsPage.desc">Research reports, policy briefs, field assessments, and actionable datasets from across the region.</p>
  </div>
</div>

<main class="flex-grow max-w-7xl mx-auto px-6 py-12 w-full">

  <!-- ── Filter panel ─────────────────────────────────────────────────────── -->
  <div class="bg-white rounded-2xl border border-amber-100 shadow-sm p-5 mb-8">
    <form method="GET" action="/documents" class="flex flex-wrap gap-3 items-end">
      <div class="flex-1 min-w-[200px]">
        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5" data-i18n="documentsPage.searchDocuments">Search Documents</label>
        <div class="relative">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search by title or description..."
                 data-i18n-placeholder="documentsPage.searchPlaceholder"
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
              style="background:#E7952A;color:#0D0102" data-i18n="documentsPage.search">
        Search
      </button>
      <?php if ($hasFilter): ?>
        <a href="/documents" class="px-6 py-3 rounded-xl font-bold text-sm border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors" data-i18n="listPage.clear">Clear</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- ── Results meta ──────────────────────────────────────────────────────── -->
  <div class="flex items-center justify-between mb-6">
    <p class="text-sm text-slate-500">
      <?php if ($total === 0): ?>
        <span data-i18n="documentsPage.noDocumentsFound">No documents found</span><?= $hasFilter ? ' — <span data-i18n="documentsPage.tryDifferentFilters">try different filters</span>' : '' ?>.
      <?php elseif ($hasFilter): ?>
        <strong class="text-slate-800"><?= $total ?></strong> <span data-i18n="documentsPage.documentsFound">documents found</span>
        <?php if ($q): ?> <span data-i18n="documentsPage.forQuery">for</span> "<em><?= h($q) ?></em>"<?php endif; ?>
      <?php else: ?>
        <span data-i18n="galleryPage.showing">Showing</span> <strong class="text-slate-800"><?= ($page - 1) * $perPage + 1 ?>–<?= min($page * $perPage, $total) ?></strong> <span data-i18n="galleryPage.of">of</span> <strong class="text-slate-800"><?= $total ?></strong> <span data-i18n="documentsPage.documentsPlural">documents</span>
      <?php endif; ?>
    </p>
    <?php if ($pages > 1): ?>
      <span class="text-xs text-slate-400"><span data-i18n="galleryPage.page">Page</span> <?= $page ?> <span data-i18n="galleryPage.of">of</span> <?= $pages ?></span>
    <?php endif; ?>
  </div>

  <!-- ── Document cards ───────────────────────────────────────────────────── -->
  <?php if (empty($docs)): ?>
    <div class="text-center py-24 bg-white rounded-3xl border border-amber-100">
      <div class="text-6xl mb-4">📁</div>
      <h3 class="font-outfit font-bold text-xl text-slate-900" data-i18n="documentsPage.noDocumentsFound">No documents found</h3>
      <p class="text-slate-400 mt-2 text-sm mb-6" data-i18n="<?= $hasFilter ? 'documentsPage.tryBroadening' : 'documentsPage.willAppear' ?>">
        <?= $hasFilter ? 'Try broadening your search or clearing filters.' : 'Research reports and briefs will appear here once published.' ?>
      </p>
      <?php if ($hasFilter): ?>
        <a href="/documents" class="inline-block px-6 py-3 rounded-xl font-bold text-sm" style="background:#E7952A;color:#0D0102" data-i18n="documentsPage.clearFilters">Clear Filters</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <?php foreach ($docs as $doc):
        $ext   = !empty($doc['mediaUrl']) ? doc_ext($doc['mediaUrl']) : '';
        $color = doc_icon_color($ext);
      ?>
        <div class="bg-white rounded-2xl border border-amber-100 p-6 shadow-sm hover:shadow-md hover:border-amber-200 transition-all duration-200 flex gap-5">

          <!-- File type icon -->
          <div class="shrink-0">
            <div class="w-14 h-14 rounded-2xl flex flex-col items-center justify-center shadow-sm border border-slate-100"
                 style="background:<?= $ext ? $color . '15' : '#f1f5f9' ?>">
              <?php if ($ext): ?>
                <span class="text-[11px] font-black" style="color:<?= $color ?>"><?= h($ext) ?></span>
                <svg width="16" height="16" fill="none" stroke="<?= $color ?>" stroke-width="2" viewBox="0 0 24 24" class="mt-0.5">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/>
                  <path d="M14 2v6h6"/>
                </svg>
              <?php else: ?>
                <svg width="24" height="24" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/>
                  <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>
                </svg>
              <?php endif; ?>
            </div>
          </div>

          <!-- Content -->
          <div class="flex-grow min-w-0 flex flex-col">
            <h3 class="font-outfit font-bold text-base text-slate-900 leading-snug mb-1">
              <a href="/documents/<?= h($doc['id']) ?>" class="hover:text-primary transition-colors"><?= h($doc['title']) ?></a>
            </h3>
            <?php if (!empty($doc['description'])): ?>
              <p class="text-sm text-slate-500 line-clamp-2 mb-3"><?= h($doc['description']) ?></p>
            <?php endif; ?>

            <!-- Tags row -->
            <div class="flex flex-wrap gap-2 mb-4">
              <?php if (!empty($doc['country'])): ?>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-amber-50 text-amber-800"><?= h($doc['country']) ?></span>
              <?php endif; ?>
              <?php if (!empty($doc['issueCategory'])): ?>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-slate-100 text-slate-600"><?= h($doc['issueCategory']) ?></span>
              <?php endif; ?>
            </div>

            <!-- Footer row -->
            <div class="flex items-center justify-between mt-auto pt-3 border-t border-amber-50">
              <div class="flex items-center gap-3 text-xs text-slate-400">
                <span><?= format_date($doc['createdAt']) ?></span>
                <?php if (!empty($doc['downloadCount']) && (int)$doc['downloadCount'] > 0): ?>
                  <span>&bull; <?= format_number((int)$doc['downloadCount']) ?> <span data-i18n="documentsPage.downloads">downloads</span></span>
                <?php endif; ?>
              </div>
              <?php if (!empty($doc['mediaUrl'])): ?>
                <a href="<?= h($doc['mediaUrl']) ?>" target="_blank" rel="noopener noreferrer"
                   onclick="trackDownload('<?= h($doc['id']) ?>')"
                   class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold transition-all hover:brightness-110"
                   style="background:#E7952A;color:#0D0102">
                  <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7,10 12,15 17,10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                  <span data-i18n="documentsPage.download">Download</span>
                </a>
              <?php else: ?>
                <span class="text-xs text-slate-300 italic" data-i18n="documentsPage.fileUnavailable">File unavailable</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- ── Pagination ────────────────────────────────────────────────────── -->
    <?php if ($pages > 1): ?>
      <nav class="flex items-center justify-center gap-2 mt-10" aria-label="Pagination">
        <?php if ($page > 1): ?>
          <a href="<?= h(doc_url($page - 1, $q, $country, $cat)) ?>"
             class="w-10 h-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-600 hover:bg-amber-50 transition-colors text-lg font-light">&#8249;</a>
        <?php else: ?>
          <span class="w-10 h-10 rounded-xl border border-slate-100 flex items-center justify-center text-slate-300 text-lg cursor-not-allowed">&#8249;</span>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 2);
        $end   = min($pages, $page + 2);
        if ($start > 1): ?>
          <a href="<?= h(doc_url(1, $q, $country, $cat)) ?>" class="w-10 h-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-sm font-bold text-slate-600 hover:bg-amber-50 transition-colors">1</a>
          <?php if ($start > 2): ?><span class="text-slate-400 px-1">…</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
          <a href="<?= h(doc_url($i, $q, $country, $cat)) ?>"
             class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold transition-colors <?= $i === $page ? 'text-slate-900 border-2' : 'border border-slate-200 bg-white text-slate-600 hover:bg-amber-50' ?>"
             style="<?= $i === $page ? 'border-color:#E7952A;background:#F8F8F0' : '' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>

        <?php if ($end < $pages): ?>
          <?php if ($end < $pages - 1): ?><span class="text-slate-400 px-1">…</span><?php endif; ?>
          <a href="<?= h(doc_url($pages, $q, $country, $cat)) ?>" class="w-10 h-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-sm font-bold text-slate-600 hover:bg-amber-50 transition-colors"><?= $pages ?></a>
        <?php endif; ?>

        <?php if ($page < $pages): ?>
          <a href="<?= h(doc_url($page + 1, $q, $country, $cat)) ?>"
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
function trackDownload(id) {
    // Non-blocking download count increment
    fetch('/api/posts/' + id, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ incrementDownload: true })
    }).catch(function(){});
}
</script>
</body>
</html>
