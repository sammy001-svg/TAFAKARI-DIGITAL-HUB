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
$search       = trim($_GET['q'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$pageSize     = 20;
$skip         = ($page - 1) * $pageSize;

$validStatuses = ['DRAFT','PENDING','PUBLISHED','REJECTED','ARCHIVED'];
if (!in_array($statusFilter, $validStatuses)) $statusFilter = 'ALL';

$whereParts = ['p.type = ?'];
$params     = ['ARTICLE'];
if (!$isSuper)               { $whereParts[] = 'p.authorId = ?'; $params[] = $uid; }
if ($statusFilter !== 'ALL') { $whereParts[] = 'p.status = ?';   $params[] = $statusFilter; }
if ($search !== '')          { $whereParts[] = '(p.title LIKE ? OR p.country LIKE ? OR p.issueCategory LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
$where = 'WHERE ' . implode(' AND ', $whereParts);

$pdo = db();
$stCount = $pdo->prepare("SELECT COUNT(*) FROM Post p $where");
$stCount->execute($params);
$total = (int)$stCount->fetchColumn();

$stPosts = $pdo->prepare(
    "SELECT p.id, p.title, p.status, p.country, p.region, p.issueCategory,
            p.viewCount, p.thumbnailUrl, p.authorId, p.updatedAt, p.createdAt,
            u.name AS authorName, u.username AS authorUsername
     FROM Post p LEFT JOIN User u ON p.authorId = u.id
     $where ORDER BY p.createdAt DESC LIMIT $pageSize OFFSET $skip"
);
$stPosts->execute($params);
$posts = $stPosts->fetchAll();
$totalPages = max(1, (int)ceil($total / $pageSize));

// Status counts
$stCounts = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM Post WHERE type='ARTICLE'" . (!$isSuper ? " AND authorId=?" : "") . " GROUP BY status");
$stCounts->execute(!$isSuper ? [$uid] : []);
$statusCounts = ['ALL' => $total];
foreach ($stCounts->fetchAll() as $row) $statusCounts[$row['status']] = (int)$row['cnt'];

function artHref(string $status, int $p, string $search = ''): string {
    $q = [];
    if ($status !== 'ALL') $q['status'] = $status;
    if ($p > 1)            $q['page']   = $p;
    if ($search !== '')    $q['q']      = $search;
    return '/admin/content/articles' . ($q ? '?' . http_build_query($q) : '');
}

$pageTitle      = 'Articles | Tafakari Admin';
$adminPageTitle = 'Articles';
$adminPageSub   = $total . ' article' . ($total !== 1 ? 's' : '') . ($statusFilter !== 'ALL' ? ' · ' . strtolower($statusFilter) : '');
?>
<?php include dirname(__DIR__, 2) . '/includes/head.php'; ?>
<body class="antialiased font-inter" style="background:#F4F6F8">
<div class="flex h-screen overflow-hidden">
<?php include dirname(__DIR__, 2) . '/includes/admin-sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden min-w-0">
<?php include dirname(__DIR__, 2) . '/includes/admin-topbar.php'; ?>

<main class="flex-1 overflow-y-auto p-6 md:p-8">

  <!-- Page Header -->
  <div class="flex items-center justify-between mb-6">
    <div>
      <div class="flex items-center gap-2 mb-1">
        <a href="/admin/content" class="text-[11px] font-bold text-slate-400 hover:text-primary transition-colors">All Content</a>
        <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
        <span class="text-[11px] font-bold text-slate-600">Articles</span>
      </div>
      <h1 class="font-outfit font-black text-2xl text-slate-900">Articles</h1>
    </div>
    <a href="/admin/content/new?type=ARTICLE"
       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-[13px] font-bold shadow-sm transition-opacity hover:opacity-90"
       style="background:#750B25;color:#fff">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
      New Article
    </a>
  </div>

  <!-- Stats Row -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <?php
    $stats = [
      ['label'=>'Total',     'val'=>$total,                             'color'=>'#750B25'],
      ['label'=>'Published', 'val'=>$statusCounts['PUBLISHED'] ?? 0,   'color'=>'#16a34a'],
      ['label'=>'Pending',   'val'=>$statusCounts['PENDING'] ?? 0,     'color'=>'#E7952A'],
      ['label'=>'Drafts',    'val'=>($statusCounts['DRAFT'] ?? 0) + ($statusCounts['REJECTED'] ?? 0), 'color'=>'#64748b'],
    ];
    foreach ($stats as $s): ?>
      <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm">
        <p class="text-[10px] font-black uppercase tracking-widest mb-1" style="color:<?= $s['color'] ?>"><?= $s['label'] ?></p>
        <p class="font-outfit font-black text-2xl text-slate-900"><?= $s['val'] ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Search + Filter -->
  <form method="GET" action="/admin/content/articles" class="flex flex-wrap items-center gap-3 mb-5">
    <div class="relative flex-grow max-w-sm">
      <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search articles…"
             class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none shadow-sm">
    </div>
    <?php if ($statusFilter !== 'ALL'): ?><input type="hidden" name="status" value="<?= h($statusFilter) ?>"><?php endif; ?>
    <button type="submit" class="px-4 py-2.5 rounded-xl text-[12px] font-bold text-white shadow-sm" style="background:#750B25">Search</button>
    <?php if ($search !== ''): ?>
      <a href="<?= h(artHref($statusFilter, 1)) ?>" class="px-4 py-2.5 rounded-xl text-[12px] font-bold border border-slate-200 text-slate-600 hover:bg-slate-50 bg-white shadow-sm">Clear</a>
    <?php endif; ?>
  </form>

  <!-- Status Tabs -->
  <div class="flex items-center gap-1 p-1 bg-white rounded-xl border border-slate-100 shadow-sm w-fit flex-wrap mb-6">
    <?php foreach (['ALL','DRAFT','PENDING','PUBLISHED','REJECTED','ARCHIVED'] as $tab): ?>
      <a href="<?= h(artHref($tab, 1, $search)) ?>"
         class="px-3.5 py-2 rounded-lg text-[11px] font-bold transition-all <?= $statusFilter === $tab ? 'text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50' ?>"
         <?= $statusFilter === $tab ? 'style="background:#750B25"' : '' ?>>
        <?= ucfirst(strtolower($tab)) ?>
        <?php if (isset($statusCounts[$tab]) && $tab !== 'ALL'): ?>
          <span class="ml-1 text-[9px] opacity-70">(<?= $statusCounts[$tab] ?>)</span>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($posts)): ?>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-16 text-center">
      <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4" style="background:rgba(117,11,37,.06)">
        <svg width="24" height="24" fill="none" stroke="#750B25" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
          <path d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 12h6M7 8h2"/>
        </svg>
      </div>
      <h3 class="font-outfit font-bold text-xl text-slate-900">No articles found</h3>
      <p class="text-slate-500 mt-2 text-sm mb-6"><?= $search !== '' ? 'No results for "' . h($search) . '".' : 'Start by writing your first article.' ?></p>
      <a href="/admin/content/new?type=ARTICLE" class="btn-primary" style="padding:.65rem 1.5rem">Write an Article</a>
    </div>
  <?php else: ?>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden mb-5">
      <table class="w-full">
        <thead>
          <tr style="background:#FAFBFC;border-bottom:1px solid rgba(0,0,0,.06)">
            <th class="text-left px-6 py-4 text-[10px] font-black uppercase tracking-[.12em] text-slate-400">Article</th>
            <th class="text-left px-6 py-4 text-[10px] font-black uppercase tracking-[.12em] text-slate-400 hidden md:table-cell">Status</th>
            <th class="text-left px-6 py-4 text-[10px] font-black uppercase tracking-[.12em] text-slate-400 hidden lg:table-cell">Views</th>
            <th class="text-left px-6 py-4 text-[10px] font-black uppercase tracking-[.12em] text-slate-400 hidden lg:table-cell">Region</th>
            <th class="text-left px-6 py-4 text-[10px] font-black uppercase tracking-[.12em] text-slate-400 hidden xl:table-cell">Updated</th>
            <th class="px-6 py-4 text-right text-[10px] font-black uppercase tracking-[.12em] text-slate-400">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          <?php foreach ($posts as $p):
            $isOwner   = ($p['authorId'] === $uid);
            $canDelete = $isSuper || ($isOwner && in_array($p['status'], ['DRAFT','REJECTED']));
            $canSubmit = !$isSuper && $isOwner && in_array($p['status'], ['DRAFT','REJECTED']);
            $canArch   = $isSuper && in_array($p['status'], ['PUBLISHED','ARCHIVED']);
          ?>
          <tr class="hover:bg-slate-50/70 transition-colors group">
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <?php if (!empty($p['thumbnailUrl'])): ?>
                  <img src="<?= h($p['thumbnailUrl']) ?>" alt=""
                       class="w-10 h-10 rounded-lg object-cover shrink-0 bg-slate-100"
                       onerror="this.style.display='none'">
                <?php else: ?>
                  <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" style="background:rgba(117,11,37,.06)">
                    <svg width="16" height="16" fill="none" stroke="#750B25" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 12h6M7 8h2"/></svg>
                  </div>
                <?php endif; ?>
                <div class="min-w-0">
                  <p class="text-[13px] font-semibold text-slate-900 truncate max-w-xs leading-snug"><?= h($p['title']) ?></p>
                  <div class="flex items-center gap-2 mt-0.5">
                    <?php if (!empty($p['issueCategory'])): ?>
                      <span class="text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full" style="background:rgba(117,11,37,.08);color:#750B25"><?= h($p['issueCategory']) ?></span>
                    <?php endif; ?>
                    <?php if ($isSuper): ?>
                      <span class="text-[10px] text-slate-400"><?= h($p['authorName'] ?? $p['authorUsername'] ?? '—') ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 hidden md:table-cell"><?= status_badge($p['status']) ?></td>
            <td class="px-6 py-4 hidden lg:table-cell">
              <span class="text-[12px] font-semibold text-slate-700"><?= number_format((int)($p['viewCount'] ?? 0)) ?></span>
            </td>
            <td class="px-6 py-4 hidden lg:table-cell">
              <span class="text-[12px] font-semibold text-slate-700"><?= h($p['country']) ?></span>
              <span class="text-[11px] text-slate-400 ml-1">&bull; <?= h($p['region']) ?></span>
            </td>
            <td class="px-6 py-4 text-[11px] text-slate-400 hidden xl:table-cell"><?= format_relative_time($p['updatedAt']) ?></td>
            <td class="px-6 py-4">
              <div class="flex items-center justify-end gap-1.5">
                <a href="/admin/content/<?= h($p['id']) ?>/edit"
                   class="inline-flex items-center gap-1 px-3 py-1.5 text-[10px] font-bold rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">
                  <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                  Edit
                </a>
                <?php if ($canSubmit): ?>
                  <button onclick="postAction('/api/posts/<?= h($p['id']) ?>/submit','POST',this,'Submit for review?')"
                          class="px-3 py-1.5 text-[10px] font-bold rounded-lg bg-amber-100 text-amber-700 hover:bg-amber-200 transition-colors">Submit</button>
                <?php endif; ?>
                <?php if ($canArch): ?>
                  <button onclick="postAction('/api/posts/<?= h($p['id']) ?>/archive','POST',this,null)"
                          class="px-3 py-1.5 text-[10px] font-bold rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">
                    <?= $p['status'] === 'PUBLISHED' ? 'Archive' : 'Restore' ?>
                  </button>
                <?php endif; ?>
                <?php if ($canDelete): ?>
                  <button onclick="postAction('/api/posts/<?= h($p['id']) ?>','DELETE',this,'Delete this article? This cannot be undone.')"
                          class="px-3 py-1.5 text-[10px] font-bold rounded-lg bg-rose-100 text-rose-700 hover:bg-rose-200 transition-colors">Delete</button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
      <div class="flex items-center justify-between">
        <p class="text-[11px] text-slate-400">Showing <?= $skip+1 ?>–<?= min($skip+$pageSize,$total) ?> of <?= $total ?></p>
        <div class="flex items-center gap-2">
          <?php if ($page > 1): ?>
            <a href="<?= h(artHref($statusFilter, $page-1, $search)) ?>" class="px-3 py-2 text-[11px] font-bold bg-white rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">&larr; Prev</a>
          <?php else: ?>
            <span class="px-3 py-2 text-[11px] font-bold rounded-xl text-slate-300">&larr; Prev</span>
          <?php endif; ?>
          <span class="px-3 py-2 text-[11px] font-bold text-slate-500"><?= $page ?> / <?= $totalPages ?></span>
          <?php if ($page < $totalPages): ?>
            <a href="<?= h(artHref($statusFilter, $page+1, $search)) ?>" class="px-3 py-2 text-[11px] font-bold bg-white rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">Next &rarr;</a>
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
  var t = btn.textContent; btn.disabled=true; btn.textContent='…';
  fetch(url,{method:method,headers:{'Content-Type':'application/json'}})
    .then(r=>r.json()).then(function(d){
      if(d.error){alert(d.error);btn.disabled=false;btn.textContent=t;}
      else{location.reload();}
    }).catch(function(){alert('Request failed');btn.disabled=false;btn.textContent=t;});
}
</script>
</body>
</html>
