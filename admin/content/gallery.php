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
$pageSize     = 24;
$skip         = ($page - 1) * $pageSize;
$viewMode     = $_GET['view'] ?? 'grid';

$validStatuses = ['DRAFT','PENDING','PUBLISHED','REJECTED','ARCHIVED'];
if (!in_array($statusFilter, $validStatuses)) $statusFilter = 'ALL';

$whereParts = ['p.type = ?'];
$params     = ['GALLERY_IMAGE'];
if (!$isSuper)               { $whereParts[] = 'p.authorId = ?'; $params[] = $uid; }
if ($statusFilter !== 'ALL') { $whereParts[] = 'p.status = ?';   $params[] = $statusFilter; }
if ($search !== '')          { $whereParts[] = '(p.title LIKE ? OR p.country LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
$where = 'WHERE ' . implode(' AND ', $whereParts);

$pdo = db();
$stCount = $pdo->prepare("SELECT COUNT(*) FROM Post p $where");
$stCount->execute($params);
$total = (int)$stCount->fetchColumn();

$stPosts = $pdo->prepare(
    "SELECT p.id, p.title, p.status, p.country, p.updatedAt, p.authorId,
            p.thumbnailUrl, p.mediaUrl, p.description,
            u.name AS authorName
     FROM Post p LEFT JOIN User u ON p.authorId = u.id
     $where ORDER BY p.createdAt DESC LIMIT $pageSize OFFSET $skip"
);
$stPosts->execute($params);
$posts = $stPosts->fetchAll();
$totalPages = max(1, (int)ceil($total / $pageSize));

$stCounts = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM Post WHERE type='GALLERY_IMAGE'" . (!$isSuper ? " AND authorId=?" : "") . " GROUP BY status");
$stCounts->execute(!$isSuper ? [$uid] : []);
$statusCounts = ['ALL' => $total];
foreach ($stCounts->fetchAll() as $row) $statusCounts[$row['status']] = (int)$row['cnt'];

function galHref(string $status, int $p, string $search = '', string $view = 'grid'): string {
    $q = [];
    if ($status !== 'ALL') $q['status'] = $status;
    if ($p > 1)            $q['page']   = $p;
    if ($search !== '')    $q['q']      = $search;
    if ($view !== 'grid')  $q['view']   = $view;
    return '/admin/content/gallery' . ($q ? '?' . http_build_query($q) : '');
}

$pageTitle      = 'Gallery | Tafakari Admin';
$adminPageTitle = 'Gallery';
$adminPageSub   = $total . ' image' . ($total !== 1 ? 's' : '') . ($statusFilter !== 'ALL' ? ' · ' . strtolower($statusFilter) : '');
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
        <span class="text-[11px] font-bold text-slate-600">Gallery</span>
      </div>
      <h1 class="font-outfit font-black text-2xl text-slate-900">Photo Gallery</h1>
    </div>
    <div class="flex items-center gap-3">
      <!-- View Toggle -->
      <div class="flex items-center gap-1 p-1 bg-white rounded-xl border border-slate-200 shadow-sm">
        <a href="<?= h(galHref($statusFilter, $page, $search, 'grid')) ?>"
           class="w-8 h-8 flex items-center justify-center rounded-lg transition-colors <?= $viewMode==='grid' ? 'text-white' : 'text-slate-400 hover:text-slate-600' ?>"
           <?= $viewMode==='grid' ? 'style="background:#750B25"' : '' ?>>
          <svg width="13" height="13" fill="currentColor" viewBox="0 0 16 16"><path d="M1 2.5A1.5 1.5 0 012.5 1h3A1.5 1.5 0 017 2.5v3A1.5 1.5 0 015.5 7h-3A1.5 1.5 0 011 5.5v-3zm8 0A1.5 1.5 0 0110.5 1h3A1.5 1.5 0 0115 2.5v3A1.5 1.5 0 0113.5 7h-3A1.5 1.5 0 019 5.5v-3zm-8 8A1.5 1.5 0 012.5 9h3A1.5 1.5 0 017 10.5v3A1.5 1.5 0 015.5 15h-3A1.5 1.5 0 011 13.5v-3zm8 0A1.5 1.5 0 0110.5 9h3A1.5 1.5 0 0115 10.5v3A1.5 1.5 0 0113.5 15h-3A1.5 1.5 0 019 13.5v-3z"/></svg>
        </a>
        <a href="<?= h(galHref($statusFilter, $page, $search, 'list')) ?>"
           class="w-8 h-8 flex items-center justify-center rounded-lg transition-colors <?= $viewMode==='list' ? 'text-white' : 'text-slate-400 hover:text-slate-600' ?>"
           <?= $viewMode==='list' ? 'style="background:#750B25"' : '' ?>>
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
        </a>
      </div>
      <a href="/admin/content/new?type=GALLERY_IMAGE"
         class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-[13px] font-bold shadow-sm transition-opacity hover:opacity-90"
         style="background:#750B25;color:#fff">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
        Upload Image
      </a>
    </div>
  </div>

  <!-- Stats Row -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <?php
    $stats = [
      ['label'=>'Total',     'val'=>$total,                           'color'=>'#750B25'],
      ['label'=>'Published', 'val'=>$statusCounts['PUBLISHED'] ?? 0, 'color'=>'#16a34a'],
      ['label'=>'Pending',   'val'=>$statusCounts['PENDING'] ?? 0,   'color'=>'#E7952A'],
      ['label'=>'Drafts',    'val'=>$statusCounts['DRAFT'] ?? 0,     'color'=>'#64748b'],
    ];
    foreach ($stats as $s): ?>
      <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm">
        <p class="text-[10px] font-black uppercase tracking-widest mb-1" style="color:<?= $s['color'] ?>"><?= $s['label'] ?></p>
        <p class="font-outfit font-black text-2xl text-slate-900"><?= $s['val'] ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Search -->
  <form method="GET" action="/admin/content/gallery" class="flex flex-wrap items-center gap-3 mb-5">
    <?php if ($viewMode !== 'grid'): ?><input type="hidden" name="view" value="<?= h($viewMode) ?>"><?php endif; ?>
    <div class="relative flex-grow max-w-sm">
      <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search gallery…"
             class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none shadow-sm">
    </div>
    <?php if ($statusFilter !== 'ALL'): ?><input type="hidden" name="status" value="<?= h($statusFilter) ?>"><?php endif; ?>
    <button type="submit" class="px-4 py-2.5 rounded-xl text-[12px] font-bold text-white shadow-sm" style="background:#750B25">Search</button>
    <?php if ($search !== ''): ?>
      <a href="<?= h(galHref($statusFilter, 1, '', $viewMode)) ?>" class="px-4 py-2.5 rounded-xl text-[12px] font-bold border border-slate-200 text-slate-600 hover:bg-slate-50 bg-white shadow-sm">Clear</a>
    <?php endif; ?>
  </form>

  <!-- Status Tabs -->
  <div class="flex items-center gap-1 p-1 bg-white rounded-xl border border-slate-100 shadow-sm w-fit flex-wrap mb-6">
    <?php foreach (['ALL','DRAFT','PENDING','PUBLISHED','REJECTED','ARCHIVED'] as $tab): ?>
      <a href="<?= h(galHref($tab, 1, $search, $viewMode)) ?>"
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
          <path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
      </div>
      <h3 class="font-outfit font-bold text-xl text-slate-900">No images found</h3>
      <p class="text-slate-500 mt-2 text-sm mb-6"><?= $search !== '' ? 'No results for "' . h($search) . '".' : 'Upload your first gallery image to get started.' ?></p>
      <a href="/admin/content/new?type=GALLERY_IMAGE" class="btn-primary" style="padding:.65rem 1.5rem">Upload Image</a>
    </div>

  <?php elseif ($viewMode === 'grid'): ?>
    <!-- Grid View -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 mb-5">
      <?php foreach ($posts as $p):
        $imgSrc     = $p['mediaUrl'] ?: $p['thumbnailUrl'];
        $isOwner    = ($p['authorId'] === $uid);
        $canDelete  = $isSuper || ($isOwner && in_array($p['status'], ['DRAFT','REJECTED']));
        $canSubmit  = !$isSuper && $isOwner && in_array($p['status'], ['DRAFT','REJECTED']);
        $canPublish = $isSuper && in_array($p['status'], ['DRAFT','PENDING','REJECTED']);
      ?>
      <div class="group relative bg-white rounded-2xl overflow-hidden border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
        <!-- Image -->
        <div class="aspect-square bg-slate-100 overflow-hidden">
          <?php if ($imgSrc): ?>
            <img src="<?= h($imgSrc) ?>" alt="<?= h($p['title']) ?>"
                 class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                 loading="lazy" onerror="this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center\'><svg width=\'32\' height=\'32\' fill=\'none\' stroke=\'#cbd5e1\' stroke-width=\'1.5\' viewBox=\'0 0 24 24\'><path d=\'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z\'/></svg></div>'">
          <?php else: ?>
            <div class="w-full h-full flex items-center justify-center">
              <svg width="32" height="32" fill="none" stroke="#cbd5e1" stroke-width="1.5" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
          <?php endif; ?>
        </div>
        <!-- Status badge -->
        <div class="absolute top-2 right-2"><?= status_badge($p['status']) ?></div>
        <!-- Info -->
        <div class="p-3">
          <p class="text-[12px] font-semibold text-slate-900 truncate leading-snug"><?= h($p['title']) ?></p>
          <p class="text-[10px] text-slate-400 mt-0.5"><?= h($p['country']) ?></p>
        </div>
        <!-- Hover actions -->
        <div class="absolute inset-x-0 bottom-0 translate-y-full group-hover:translate-y-0 transition-transform duration-200 bg-white border-t border-slate-100 p-2 flex items-center justify-between gap-1">
          <a href="/admin/content/<?= h($p['id']) ?>/edit"
             class="flex-1 text-center px-2 py-1.5 text-[10px] font-bold rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">Edit</a>
          <?php if ($canPublish): ?>
            <button onclick="postAction('/api/posts/<?= h($p['id']) ?>/publish','POST',this,null)"
                    class="flex-1 px-2 py-1.5 text-[10px] font-bold rounded-lg bg-green-100 text-green-700 hover:bg-green-200 transition-colors">Pub</button>
          <?php endif; ?>
          <?php if ($canSubmit): ?>
            <button onclick="postAction('/api/posts/<?= h($p['id']) ?>/submit','POST',this,'Submit for review?')"
                    class="flex-1 px-2 py-1.5 text-[10px] font-bold rounded-lg bg-amber-100 text-amber-700 hover:bg-amber-200 transition-colors">Submit</button>
          <?php endif; ?>
          <?php if ($canDelete): ?>
            <button onclick="postAction('/api/posts/<?= h($p['id']) ?>','DELETE',this,'Delete this image?')"
                    class="px-2 py-1.5 text-[10px] font-bold rounded-lg bg-rose-100 text-rose-700 hover:bg-rose-200 transition-colors">Del</button>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

  <?php else: ?>
    <!-- List View -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden mb-5">
      <table class="w-full">
        <thead>
          <tr style="background:#FAFBFC;border-bottom:1px solid rgba(0,0,0,.06)">
            <th class="text-left px-6 py-4 text-[10px] font-black uppercase tracking-[.12em] text-slate-400">Image</th>
            <th class="text-left px-6 py-4 text-[10px] font-black uppercase tracking-[.12em] text-slate-400 hidden md:table-cell">Status</th>
            <th class="text-left px-6 py-4 text-[10px] font-black uppercase tracking-[.12em] text-slate-400 hidden lg:table-cell">Country</th>
            <th class="text-left px-6 py-4 text-[10px] font-black uppercase tracking-[.12em] text-slate-400 hidden xl:table-cell">Updated</th>
            <th class="px-6 py-4 text-right text-[10px] font-black uppercase tracking-[.12em] text-slate-400">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          <?php foreach ($posts as $p):
            $imgSrc     = $p['mediaUrl'] ?: $p['thumbnailUrl'];
            $isOwner    = ($p['authorId'] === $uid);
            $canDelete  = $isSuper || ($isOwner && in_array($p['status'], ['DRAFT','REJECTED']));
            $canSubmit  = !$isSuper && $isOwner && in_array($p['status'], ['DRAFT','REJECTED']);
            $canPublish = $isSuper && in_array($p['status'], ['DRAFT','PENDING','REJECTED']);
            $canArch    = $isSuper && in_array($p['status'], ['PUBLISHED','ARCHIVED']);
          ?>
          <tr class="hover:bg-slate-50/70 transition-colors">
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="w-14 h-14 rounded-xl overflow-hidden bg-slate-100 shrink-0">
                  <?php if ($imgSrc): ?>
                    <img src="<?= h($imgSrc) ?>" alt="" class="w-full h-full object-cover" loading="lazy" onerror="this.style.display='none'">
                  <?php endif; ?>
                </div>
                <div class="min-w-0">
                  <p class="text-[13px] font-semibold text-slate-900 truncate max-w-xs"><?= h($p['title']) ?></p>
                  <?php if (!empty($p['description'])): ?>
                    <p class="text-[10px] text-slate-400 truncate max-w-xs"><?= h(mb_substr($p['description'], 0, 80)) ?></p>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 hidden md:table-cell"><?= status_badge($p['status']) ?></td>
            <td class="px-6 py-4 hidden lg:table-cell text-[12px] font-semibold text-slate-700"><?= h($p['country']) ?></td>
            <td class="px-6 py-4 text-[11px] text-slate-400 hidden xl:table-cell"><?= format_relative_time($p['updatedAt']) ?></td>
            <td class="px-6 py-4">
              <div class="flex items-center justify-end gap-1.5">
                <a href="/admin/content/<?= h($p['id']) ?>/edit"
                   class="inline-flex items-center gap-1 px-3 py-1.5 text-[10px] font-bold rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">Edit</a>
                <?php if ($canPublish): ?>
                  <button onclick="postAction('/api/posts/<?= h($p['id']) ?>/publish','POST',this,null)"
                          class="px-3 py-1.5 text-[10px] font-bold rounded-lg bg-green-100 text-green-700 hover:bg-green-200 transition-colors">Publish</button>
                <?php endif; ?>
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
                  <button onclick="postAction('/api/posts/<?= h($p['id']) ?>','DELETE',this,'Delete this image?')"
                          class="px-3 py-1.5 text-[10px] font-bold rounded-lg bg-rose-100 text-rose-700 hover:bg-rose-200 transition-colors">Delete</button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <?php if ($totalPages > 1): ?>
    <div class="flex items-center justify-between">
      <p class="text-[11px] text-slate-400">Showing <?= $skip+1 ?>–<?= min($skip+$pageSize,$total) ?> of <?= $total ?></p>
      <div class="flex items-center gap-2">
        <?php if ($page > 1): ?>
          <a href="<?= h(galHref($statusFilter, $page-1, $search, $viewMode)) ?>" class="px-3 py-2 text-[11px] font-bold bg-white rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">&larr; Prev</a>
        <?php else: ?>
          <span class="px-3 py-2 text-[11px] font-bold rounded-xl text-slate-300">&larr; Prev</span>
        <?php endif; ?>
        <span class="px-3 py-2 text-[11px] font-bold text-slate-500"><?= $page ?> / <?= $totalPages ?></span>
        <?php if ($page < $totalPages): ?>
          <a href="<?= h(galHref($statusFilter, $page+1, $search, $viewMode)) ?>" class="px-3 py-2 text-[11px] font-bold bg-white rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">Next &rarr;</a>
        <?php else: ?>
          <span class="px-3 py-2 text-[11px] font-bold rounded-xl text-slate-300">Next &rarr;</span>
        <?php endif; ?>
      </div>
    </div>
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
