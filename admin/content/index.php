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
$page         = max(1, (int)($_GET['page'] ?? 1));
$pageSize     = 20;
$skip         = ($page - 1) * $pageSize;

$validStatuses = ['DRAFT','PENDING','PUBLISHED','REJECTED','ARCHIVED'];
if (!in_array($statusFilter, $validStatuses)) $statusFilter = 'ALL';

$whereParts = [];
if (!$isSuper) $whereParts[] = "p.authorId = '$uid'";
if ($statusFilter !== 'ALL') $whereParts[] = "p.status = '$statusFilter'";
$where = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';

$pdo   = db();
$total = (int)$pdo->query("SELECT COUNT(*) FROM Post p $where")->fetchColumn();
$posts = $pdo->query(
    "SELECT p.id, p.title, p.type, p.status, p.country, p.region, p.authorId, p.updatedAt,
            u.name AS authorName, u.username AS authorUsername
     FROM Post p LEFT JOIN User u ON p.authorId = u.id
     $where ORDER BY p.createdAt DESC LIMIT $pageSize OFFSET $skip"
)->fetchAll();

$totalPages = max(1, (int)ceil($total / $pageSize));

function pageHref(string $status, int $p): string {
    $q = [];
    if ($status !== 'ALL') $q['status'] = $status;
    if ($p > 1)            $q['page']   = $p;
    return '/admin/content' . ($q ? '?' . http_build_query($q) : '');
}

$tabs = ['ALL','DRAFT','PENDING','PUBLISHED','REJECTED','ARCHIVED'];
$pageTitle = 'Content | Tafakari Admin';
?>
<?php include dirname(__DIR__, 2) . '/includes/head.php'; ?>
<body class="antialiased bg-slate-100 font-inter">
<div class="flex h-screen overflow-hidden">
<?php include dirname(__DIR__, 2) . '/includes/admin-sidebar.php'; ?>

<div class="flex-1 overflow-y-auto p-10">
  <div class="flex justify-between items-start mb-8">
    <div>
      <h1 class="font-outfit text-3xl font-bold text-slate-900"><?= $isSuper ? 'All Content' : 'My Content' ?></h1>
      <p class="text-slate-500 mt-1 italic">
        <?= $total ?> item<?= $total !== 1 ? 's' : '' ?>
        <?= $statusFilter !== 'ALL' ? ' · ' . strtolower($statusFilter) : '' ?>
        <?= $totalPages > 1 ? ' · page ' . $page . ' of ' . $totalPages : '' ?>
      </p>
    </div>
    <a href="/admin/content/new" class="btn-primary">+ Create New</a>
  </div>

  <!-- Status Tabs -->
  <div class="flex gap-1 p-1 bg-white/60 rounded-xl w-fit flex-wrap mb-8 border border-slate-200 shadow-sm">
    <?php foreach ($tabs as $tab): ?>
      <a href="<?= h(pageHref($tab, 1)) ?>"
         class="px-4 py-2 rounded-lg text-xs font-bold transition-colors <?= $statusFilter === $tab ? 'text-white shadow-lg' : 'text-slate-500 hover:text-slate-900 hover:bg-white' ?>"
         <?= $statusFilter === $tab ? 'style="background:#9A1415"' : '' ?>>
        <?= ucfirst(strtolower($tab)) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($posts)): ?>
    <div class="bg-white rounded-4xl p-16 shadow-sm border border-slate-100 text-center">
      <div class="text-4xl mb-4">📭</div>
      <h3 class="font-outfit font-bold text-xl text-slate-900">No content found</h3>
      <p class="text-slate-500 mt-2 text-sm"><?= $statusFilter !== 'ALL' ? 'No ' . strtolower($statusFilter) . ' posts.' : 'Start by creating new content.' ?></p>
      <a href="/admin/content/new" class="btn-primary inline-block mt-6 px-6 py-3 text-sm">Create Content</a>
    </div>
  <?php else: ?>
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden mb-6">
      <table class="w-full">
        <thead>
          <tr class="border-b border-slate-100">
            <th class="text-left px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Content</th>
            <th class="text-left px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 hidden md:table-cell">Status</th>
            <th class="text-left px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 hidden lg:table-cell">Region</th>
            <th class="text-left px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 hidden lg:table-cell">Updated</th>
            <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          <?php foreach ($posts as $p):
            $isOwner   = ($p['authorId'] === $uid);
            $canDelete = $isSuper || ($isOwner && in_array($p['status'], ['DRAFT','REJECTED']));
            $canSubmit = !$isSuper && $isOwner && in_array($p['status'], ['DRAFT','REJECTED']);
            $canArchive = $isSuper && in_array($p['status'], ['PUBLISHED','ARCHIVED']);
          ?>
            <tr class="hover:bg-slate-50/50 transition-colors">
              <td class="px-8 py-5">
                <?= type_badge($p['type']) ?>
                <p class="text-sm font-bold text-slate-900 mt-1 line-clamp-1"><?= h($p['title']) ?></p>
                <?php if ($isSuper): ?>
                  <p class="text-[10px] text-slate-400 mt-0.5">by <?= h($p['authorName'] ?? $p['authorUsername'] ?? '—') ?></p>
                <?php endif; ?>
              </td>
              <td class="px-8 py-5 hidden md:table-cell"><?= status_badge($p['status']) ?></td>
              <td class="px-8 py-5 hidden lg:table-cell">
                <span class="text-xs font-bold text-slate-700"><?= h($p['country']) ?></span>
                <span class="text-xs text-slate-400 ml-1">&bull; <?= h($p['region']) ?></span>
              </td>
              <td class="px-8 py-5 text-xs text-slate-400 hidden lg:table-cell"><?= format_relative_time($p['updatedAt']) ?></td>
              <td class="px-8 py-5">
                <div class="flex items-center justify-end gap-2 flex-wrap">
                  <a href="/admin/content/<?= h($p['id']) ?>/edit"
                     class="px-4 py-2 text-[10px] font-black uppercase tracking-widest bg-slate-100 text-slate-600 rounded-xl hover:bg-slate-200 transition-colors">
                    Edit
                  </a>
                  <?php if ($canSubmit): ?>
                    <button onclick="postAction('/api/posts/<?= h($p['id']) ?>/submit','POST',this,'Submit for review?')"
                            class="px-3 py-2 text-[10px] font-black uppercase tracking-widest bg-amber-100 text-amber-700 rounded-xl hover:bg-amber-200 transition-colors">
                      Submit
                    </button>
                  <?php endif; ?>
                  <?php if ($canArchive): ?>
                    <button onclick="postAction('/api/posts/<?= h($p['id']) ?>/archive','POST',this,null)"
                            class="px-3 py-2 text-[10px] font-black uppercase tracking-widest bg-slate-100 text-slate-600 rounded-xl hover:bg-slate-200 transition-colors">
                      <?= $p['status'] === 'PUBLISHED' ? 'Archive' : 'Restore' ?>
                    </button>
                  <?php endif; ?>
                  <?php if ($canDelete): ?>
                    <button onclick="postAction('/api/posts/<?= h($p['id']) ?>','DELETE',this,'Delete this post? This cannot be undone.')"
                            class="px-3 py-2 text-[10px] font-black uppercase tracking-widest bg-rose-100 text-rose-700 rounded-xl hover:bg-rose-200 transition-colors">
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
      <div class="flex items-center justify-between px-2">
        <p class="text-xs text-slate-400">Showing <?= $skip+1 ?>–<?= min($skip+$pageSize,$total) ?> of <?= $total ?></p>
        <div class="flex items-center gap-2">
          <?php if ($page > 1): ?>
            <a href="<?= h(pageHref($statusFilter, $page-1)) ?>"
               class="px-4 py-2 text-xs font-bold bg-white rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">&larr; Previous</a>
          <?php else: ?>
            <span class="px-4 py-2 text-xs font-bold rounded-xl text-slate-300 cursor-not-allowed">&larr; Previous</span>
          <?php endif; ?>
          <span class="px-4 py-2 text-xs font-bold text-slate-500"><?= $page ?> / <?= $totalPages ?></span>
          <?php if ($page < $totalPages): ?>
            <a href="<?= h(pageHref($statusFilter, $page+1)) ?>"
               class="px-4 py-2 text-xs font-bold bg-white rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">Next &rarr;</a>
          <?php else: ?>
            <span class="px-4 py-2 text-xs font-bold rounded-xl text-slate-300 cursor-not-allowed">Next &rarr;</span>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
</div>

<script>
function postAction(url, method, btn, confirmMsg) {
  if (confirmMsg && !confirm(confirmMsg)) return;
  btn.disabled = true;
  btn.textContent = '…';
  fetch(url, { method: method, headers: { 'Content-Type': 'application/json' } })
    .then(function(r){ return r.json(); })
    .then(function(d){ if (d.error) { alert(d.error); btn.disabled=false; btn.textContent=btn.textContent; } else { location.reload(); } })
    .catch(function(){ alert('Request failed'); btn.disabled=false; });
}
</script>
</body>
</html>
