<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

$user    = require_auth();
$isSuper = is_super_admin();
$uid     = $user['id'];

$statusFilter = $_GET['status'] ?? 'ALL';
$search       = trim($_GET['q']      ?? '');
$typeFilter   = trim($_GET['type']   ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$pageSize     = 20;
$skip         = ($page - 1) * $pageSize;

$validStatuses = ['DRAFT','PENDING','PUBLISHED','REJECTED','ARCHIVED'];
if (!in_array($statusFilter, $validStatuses)) $statusFilter = 'ALL';
$validTypes = ['ARTICLE','VIDEO','PODCAST','DOCUMENT','GALLERY_IMAGE'];
if (!in_array($typeFilter, $validTypes)) $typeFilter = '';

$whereParts = [];
$params     = [];
if (!$isSuper) { $whereParts[] = 'p.authorId = ?'; $params[] = $uid; }
if ($statusFilter !== 'ALL') { $whereParts[] = 'p.status = ?'; $params[] = $statusFilter; }
if ($typeFilter  !== '')     { $whereParts[] = 'p.type = ?';   $params[] = $typeFilter;   }
if ($search      !== '')     { $whereParts[] = '(p.title LIKE ? OR p.country LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
$where = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';

$pdo = db();
$stCount = $pdo->prepare("SELECT COUNT(*) FROM Post p $where");
$stCount->execute($params);
$total = (int)$stCount->fetchColumn();

$stPosts = $pdo->prepare(
    "SELECT p.id, p.title, p.type, p.status, p.country, p.region, p.authorId, p.updatedAt,
            u.name AS authorName, u.username AS authorUsername
     FROM Post p LEFT JOIN User u ON p.authorId = u.id
     $where ORDER BY p.createdAt DESC LIMIT $pageSize OFFSET $skip"
);
$stPosts->execute($params);
$posts = $stPosts->fetchAll();

$totalPages = max(1, (int)ceil($total / $pageSize));

function pageHref(string $status, int $p, string $search = '', string $type = ''): string {
    $q = [];
    if ($status !== 'ALL') $q['status'] = $status;
    if ($p > 1)            $q['page']   = $p;
    if ($search !== '')    $q['q']      = $search;
    if ($type   !== '')    $q['type']   = $type;
    return '/admin/content' . ($q ? '?' . http_build_query($q) : '');
}

$tabs      = ['ALL','DRAFT','PENDING','PUBLISHED','REJECTED','ARCHIVED'];
$typeNames = ['ARTICLE'=>'Article','VIDEO'=>'Video','PODCAST'=>'Podcast','DOCUMENT'=>'Document','GALLERY_IMAGE'=>'Gallery'];

$pageTitle      = ($isSuper ? 'All Content' : 'My Content') . ' | Tafakari Admin';
$adminPageTitle = $isSuper ? 'All Content' : 'My Content';
$adminPageSub   = $total . ' item' . ($total !== 1 ? 's' : '') . ($statusFilter !== 'ALL' ? ' · ' . strtolower($statusFilter) : '');
?>
<?php include dirname(__DIR__, 2) . '/includes/head.php'; ?>
<body class="antialiased font-inter" style="background:#F4F6F8">
<div class="flex h-screen overflow-hidden">
<?php include dirname(__DIR__, 2) . '/includes/admin-sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden min-w-0">
<?php include dirname(__DIR__, 2) . '/includes/admin-topbar.php'; ?>

<main class="flex-1 overflow-y-auto p-6 md:p-8">

  <!-- Search + Filter bar -->
  <form method="GET" action="/admin/content" class="flex flex-wrap items-center gap-3 mb-5">
    <div class="relative flex-grow max-w-sm">
      <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search by title or country…"
             class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 shadow-sm"
             style="focus-ring-color:#750B25">
    </div>
    <select name="type" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none shadow-sm min-w-[130px]">
      <option value="">All Types</option>
      <?php foreach ($typeNames as $val => $label): ?>
        <option value="<?= h($val) ?>" <?= $typeFilter === $val ? 'selected' : '' ?>><?= h($label) ?></option>
      <?php endforeach; ?>
    </select>
    <?php if ($statusFilter !== 'ALL'): ?><input type="hidden" name="status" value="<?= h($statusFilter) ?>"><?php endif; ?>
    <button type="submit" class="px-4 py-2.5 rounded-xl text-[12px] font-bold text-white shadow-sm transition-opacity hover:opacity-90"
            style="background:#750B25">Search</button>
    <?php if ($search !== '' || $typeFilter !== ''): ?>
      <a href="<?= h(pageHref($statusFilter, 1)) ?>"
         class="px-4 py-2.5 rounded-xl text-[12px] font-bold border border-slate-200 text-slate-600 hover:bg-slate-50 bg-white shadow-sm transition-colors">Clear</a>
    <?php endif; ?>
    <div class="ml-auto">
      <a href="/admin/content/new" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-[12px] font-bold text-white shadow-sm"
         style="background:#E7952A;color:#0D0102">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
        New Content
      </a>
    </div>
  </form>

  <!-- Status Tabs -->
  <div class="flex items-center gap-1 p-1 bg-white rounded-xl border border-slate-100 shadow-sm w-fit flex-wrap mb-6">
    <?php foreach ($tabs as $tab): ?>
      <a href="<?= h(pageHref($tab, 1, $search, $typeFilter)) ?>"
         class="px-3.5 py-2 rounded-lg text-[11px] font-bold transition-all <?= $statusFilter === $tab ? 'text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50' ?>"
         <?= $statusFilter === $tab ? 'style="background:#750B25"' : '' ?>>
        <?= ucfirst(strtolower($tab)) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($posts)): ?>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-16 text-center">
      <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4" style="background:rgba(117,11,37,.06)">
        <svg width="24" height="24" fill="none" stroke="#750B25" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
          <path d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
        </svg>
      </div>
      <h3 class="font-outfit font-bold text-xl text-slate-900">No content found</h3>
      <p class="text-slate-500 mt-2 text-sm mb-6">
        <?= $search !== '' ? 'No results for "' . h($search) . '".' : ($statusFilter !== 'ALL' ? 'No ' . strtolower($statusFilter) . ' posts.' : 'Start by creating your first piece of content.') ?>
      </p>
      <a href="/admin/content/new" class="btn-primary" style="padding:.65rem 1.5rem">Create Content</a>
    </div>

  <?php else: ?>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden mb-5">
      <table class="w-full">
        <thead>
          <tr style="background:#FAFBFC;border-bottom:1px solid rgba(0,0,0,.06)">
            <th class="text-left px-6 py-4 text-[10px] font-black uppercase tracking-[.12em] text-slate-400">Title</th>
            <th class="text-left px-6 py-4 text-[10px] font-black uppercase tracking-[.12em] text-slate-400 hidden md:table-cell">Status</th>
            <th class="text-left px-6 py-4 text-[10px] font-black uppercase tracking-[.12em] text-slate-400 hidden lg:table-cell">Region</th>
            <th class="text-left px-6 py-4 text-[10px] font-black uppercase tracking-[.12em] text-slate-400 hidden lg:table-cell">Updated</th>
            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[.12em] text-slate-400 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          <?php
          $typeIcons = [
            'ARTICLE'       => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            'GALLERY_IMAGE' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
            'PODCAST'       => 'M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z',
            'VIDEO'         => 'M15 10l4.553-2.069A1 1 0 0121 8.87v6.26a1 1 0 01-1.447.9L15 14M3 8a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z',
            'DOCUMENT'      => 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z',
          ];
          foreach ($posts as $p):
            $isOwner    = ($p['authorId'] === $uid);
            $canDelete  = $isSuper || ($isOwner && in_array($p['status'], ['DRAFT','REJECTED']));
            $canSubmit  = !$isSuper && $isOwner && in_array($p['status'], ['DRAFT','REJECTED']);
            $canPublish = $isSuper && in_array($p['status'], ['DRAFT','PENDING','REJECTED']);
            $canArchive = $isSuper && in_array($p['status'], ['PUBLISHED','ARCHIVED']);
            $svg        = $typeIcons[$p['type']] ?? $typeIcons['ARTICLE'];
          ?>
            <tr class="hover:bg-slate-50/70 transition-colors group">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" style="background:rgba(117,11,37,.06)">
                    <svg width="14" height="14" fill="none" stroke="#750B25" stroke-width="1.8"
                         stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                      <path d="<?= $svg ?>"/>
                    </svg>
                  </div>
                  <div class="min-w-0">
                    <p class="text-[13px] font-semibold text-slate-900 truncate max-w-xs"><?= h($p['title']) ?></p>
                    <p class="text-[10px] text-slate-400 mt-0.5">
                      <?= h($typeNames[$p['type']] ?? $p['type']) ?>
                      <?php if ($isSuper): ?> &bull; <?= h($p['authorName'] ?? $p['authorUsername'] ?? '—') ?><?php endif; ?>
                    </p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 hidden md:table-cell"><?= status_badge($p['status']) ?></td>
              <td class="px-6 py-4 hidden lg:table-cell">
                <span class="text-[12px] font-semibold text-slate-700"><?= h($p['country']) ?></span>
                <span class="text-[11px] text-slate-400 ml-1">&bull; <?= h($p['region']) ?></span>
              </td>
              <td class="px-6 py-4 text-[11px] text-slate-400 hidden lg:table-cell"><?= format_relative_time($p['updatedAt']) ?></td>
              <td class="px-6 py-4">
                <div class="flex items-center justify-end gap-1.5">
                  <a href="/admin/content/<?= h($p['id']) ?>/edit"
                     class="inline-flex items-center gap-1 px-3 py-1.5 text-[10px] font-bold rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">
                    <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit
                  </a>
                  <?php if ($canPublish): ?>
                    <button onclick="postAction('/api/posts/<?= h($p['id']) ?>/publish','POST',this,null)"
                            class="px-3 py-1.5 text-[10px] font-bold rounded-lg bg-green-100 text-green-700 hover:bg-green-200 transition-colors">
                      Publish
                    </button>
                  <?php endif; ?>
                  <?php if ($canSubmit): ?>
                    <button onclick="postAction('/api/posts/<?= h($p['id']) ?>/submit','POST',this,'Submit for review?')"
                            class="px-3 py-1.5 text-[10px] font-bold rounded-lg bg-amber-100 text-amber-700 hover:bg-amber-200 transition-colors">
                      Submit
                    </button>
                  <?php endif; ?>
                  <?php if ($canArchive): ?>
                    <button onclick="postAction('/api/posts/<?= h($p['id']) ?>/archive','POST',this,null)"
                            class="px-3 py-1.5 text-[10px] font-bold rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">
                      <?= $p['status'] === 'PUBLISHED' ? 'Archive' : 'Restore' ?>
                    </button>
                  <?php endif; ?>
                  <?php if ($canDelete): ?>
                    <button onclick="postAction('/api/posts/<?= h($p['id']) ?>','DELETE',this,'Delete this post? This cannot be undone.')"
                            class="px-3 py-1.5 text-[10px] font-bold rounded-lg bg-rose-100 text-rose-700 hover:bg-rose-200 transition-colors">
                      Delete
                    </button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <div class="flex items-center justify-between">
        <p class="text-[11px] text-slate-400">Showing <?= $skip+1 ?>–<?= min($skip+$pageSize,$total) ?> of <?= $total ?></p>
        <div class="flex items-center gap-2">
          <?php if ($page > 1): ?>
            <a href="<?= h(pageHref($statusFilter, $page-1, $search, $typeFilter)) ?>"
               class="px-3 py-2 text-[11px] font-bold bg-white rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">&larr; Prev</a>
          <?php else: ?>
            <span class="px-3 py-2 text-[11px] font-bold rounded-xl text-slate-300">&larr; Prev</span>
          <?php endif; ?>
          <span class="px-3 py-2 text-[11px] font-bold text-slate-500"><?= $page ?> / <?= $totalPages ?></span>
          <?php if ($page < $totalPages): ?>
            <a href="<?= h(pageHref($statusFilter, $page+1, $search, $typeFilter)) ?>"
               class="px-3 py-2 text-[11px] font-bold bg-white rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">Next &rarr;</a>
          <?php else: ?>
            <span class="px-3 py-2 text-[11px] font-bold rounded-xl text-slate-300">Next &rarr;</span>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>

</main>
</div>
</div>

<script>
function postAction(url, method, btn, confirmMsg) {
  if (confirmMsg && !confirm(confirmMsg)) return;
  var origText = btn.textContent;
  btn.disabled = true; btn.textContent = '…';
  fetch(url, { method: method, headers: { 'Content-Type': 'application/json' } })
    .then(r => r.json())
    .then(function(d) {
      if (d.error) { alert(d.error); btn.disabled=false; btn.textContent=origText; }
      else { location.reload(); }
    })
    .catch(function() { alert('Request failed'); btn.disabled=false; btn.textContent=origText; });
}
</script>
</body>
</html>
