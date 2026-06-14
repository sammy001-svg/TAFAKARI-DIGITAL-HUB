<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

$user    = require_auth();
$isSuper = is_super_admin();
$uid     = $user['id'];

$createError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'create') {
    $c_title         = trim($_POST['title']         ?? '');
    $c_description   = trim($_POST['description']   ?? '');
    $c_content       = trim($_POST['content']       ?? '');
    $c_thumbnailUrl  = trim($_POST['thumbnailUrl']  ?? '');
    $c_mediaUrl      = trim($_POST['mediaUrl']      ?? '');
    $c_country       = trim($_POST['country']       ?? '');
    $c_region        = trim($_POST['region']        ?? '');
    $c_issueCategory = trim($_POST['issueCategory'] ?? '');
    if (!$c_title)             $createError = 'Title is required.';
    elseif (!$c_mediaUrl)      $createError = 'Document URL is required.';
    elseif (!$c_country)       $createError = 'Country is required.';
    elseif (!$c_region)        $createError = 'Region is required.';
    elseif (!$c_issueCategory) $createError = 'Issue category is required.';
    else {
        $id = generate_id();
        db()->prepare(
            'INSERT INTO Post (id,title,type,description,content,thumbnailUrl,mediaUrl,country,region,issueCategory,status,authorId,viewCount,downloadCount,createdAt,updatedAt)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,0,0,NOW(3),NOW(3))'
        )->execute([$id,$c_title,'DOCUMENT',$c_description,$c_content,$c_thumbnailUrl,$c_mediaUrl,$c_country,$c_region,$c_issueCategory,'DRAFT',$uid]);
        header('Location: /admin/content/documents?created=1');
        exit;
    }
}

$statusFilter = $_GET['status'] ?? 'ALL';
$search       = trim($_GET['q'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$pageSize     = 20;
$skip         = ($page - 1) * $pageSize;

$validStatuses = ['DRAFT','PENDING','PUBLISHED','REJECTED','ARCHIVED'];
if (!in_array($statusFilter, $validStatuses)) $statusFilter = 'ALL';

$whereParts = ['p.type = ?'];
$params     = ['DOCUMENT'];
if (!$isSuper)               { $whereParts[] = 'p.authorId = ?'; $params[] = $uid; }
if ($statusFilter !== 'ALL') { $whereParts[] = 'p.status = ?';   $params[] = $statusFilter; }
if ($search !== '')          { $whereParts[] = '(p.title LIKE ? OR p.country LIKE ? OR p.issueCategory LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
$where = 'WHERE ' . implode(' AND ', $whereParts);

$pdo = db();
$stCount = $pdo->prepare("SELECT COUNT(*) FROM Post p $where");
$stCount->execute($params);
$total = (int)$stCount->fetchColumn();

$stPosts = $pdo->prepare(
    "SELECT p.id, p.title, p.status, p.country, p.updatedAt, p.authorId,
            p.thumbnailUrl, p.mediaUrl, p.description, p.downloadCount, p.issueCategory,
            u.name AS authorName
     FROM Post p LEFT JOIN User u ON p.authorId = u.id
     $where ORDER BY p.createdAt DESC LIMIT $pageSize OFFSET $skip"
);
$stPosts->execute($params);
$posts = $stPosts->fetchAll();
$totalPages = max(1, (int)ceil($total / $pageSize));

$stCounts = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM Post WHERE type='DOCUMENT'" . (!$isSuper ? " AND authorId=?" : "") . " GROUP BY status");
$stCounts->execute(!$isSuper ? [$uid] : []);
$statusCounts = ['ALL' => $total];
foreach ($stCounts->fetchAll() as $row) $statusCounts[$row['status']] = (int)$row['cnt'];

$totalDownloads = 0;
try {
    $dStmt = $pdo->prepare("SELECT SUM(downloadCount) FROM Post WHERE type='DOCUMENT'" . (!$isSuper ? " AND authorId=?" : ""));
    $dStmt->execute(!$isSuper ? [$uid] : []);
    $totalDownloads = (int)$dStmt->fetchColumn();
} catch (Exception $e) {}

function docHref(string $status, int $p, string $search = ''): string {
    $q = [];
    if ($status !== 'ALL') $q['status'] = $status;
    if ($p > 1)            $q['page']   = $p;
    if ($search !== '')    $q['q']      = $search;
    return '/admin/content/documents' . ($q ? '?' . http_build_query($q) : '');
}

function docFileType(string $url): string {
    $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
    return match($ext) {
        'pdf'  => 'PDF',
        'doc'  => 'DOC',
        'docx' => 'DOCX',
        'xls','xlsx' => 'XLS',
        'ppt','pptx' => 'PPT',
        default => strtoupper($ext) ?: 'DOC',
    };
}

function docFileColor(string $type): string {
    return match($type) {
        'PDF'  => '#dc2626',
        'DOC','DOCX' => '#2563eb',
        'XLS','XLSX' => '#16a34a',
        'PPT','PPTX' => '#ea580c',
        default => '#64748b',
    };
}

$pageTitle      = 'Documents | Tafakari Admin';
$adminPageTitle = 'Documents';
$adminPageSub   = $total . ' document' . ($total !== 1 ? 's' : '') . ($statusFilter !== 'ALL' ? ' · ' . strtolower($statusFilter) : '');
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
        <span class="text-[11px] font-bold text-slate-600">Documents</span>
      </div>
      <h1 class="font-outfit font-black text-2xl text-slate-900">Document Library</h1>
    </div>
    <button type="button" onclick="openCreateDrawer()"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-[13px] font-bold shadow-sm transition-opacity hover:opacity-90"
            style="background:#750B25;color:#fff;border:none;cursor:pointer">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
      Upload Document
    </button>
  </div>

  <!-- Stats -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <?php
    $stats = [
      ['label'=>'Documents',  'val'=>$total,                           'color'=>'#750B25'],
      ['label'=>'Published',  'val'=>$statusCounts['PUBLISHED'] ?? 0, 'color'=>'#16a34a'],
      ['label'=>'Downloads',  'val'=>number_format($totalDownloads),  'color'=>'#E7952A'],
      ['label'=>'Drafts',     'val'=>$statusCounts['DRAFT'] ?? 0,     'color'=>'#64748b'],
    ];
    foreach ($stats as $s): ?>
      <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm">
        <p class="text-[10px] font-black uppercase tracking-widest mb-1" style="color:<?= $s['color'] ?>"><?= $s['label'] ?></p>
        <p class="font-outfit font-black text-2xl text-slate-900"><?= $s['val'] ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Search -->
  <form method="GET" action="/admin/content/documents" class="flex flex-wrap items-center gap-3 mb-5">
    <div class="relative flex-grow max-w-sm">
      <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search documents…"
             class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none shadow-sm">
    </div>
    <?php if ($statusFilter !== 'ALL'): ?><input type="hidden" name="status" value="<?= h($statusFilter) ?>"><?php endif; ?>
    <button type="submit" class="px-4 py-2.5 rounded-xl text-[12px] font-bold text-white shadow-sm" style="background:#750B25">Search</button>
    <?php if ($search !== ''): ?>
      <a href="<?= h(docHref($statusFilter, 1)) ?>" class="px-4 py-2.5 rounded-xl text-[12px] font-bold border border-slate-200 text-slate-600 hover:bg-slate-50 bg-white shadow-sm">Clear</a>
    <?php endif; ?>
  </form>

  <!-- Status Tabs -->
  <div class="flex items-center gap-1 p-1 bg-white rounded-xl border border-slate-100 shadow-sm w-fit flex-wrap mb-6">
    <?php foreach (['ALL','DRAFT','PENDING','PUBLISHED','REJECTED','ARCHIVED'] as $tab): ?>
      <a href="<?= h(docHref($tab, 1, $search)) ?>"
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
          <path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
        </svg>
      </div>
      <h3 class="font-outfit font-bold text-xl text-slate-900">No documents yet</h3>
      <p class="text-slate-500 mt-2 text-sm mb-6"><?= $search !== '' ? 'No results for "' . h($search) . '".' : 'Upload research reports, policy briefs, or field notes.' ?></p>
      <button type="button" onclick="openCreateDrawer()" class="btn-primary" style="padding:.65rem 1.5rem;border:none;cursor:pointer">Upload Document</button>
    </div>
  <?php else: ?>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden mb-5">
      <table class="w-full">
        <thead>
          <tr style="background:#FAFBFC;border-bottom:1px solid rgba(0,0,0,.06)">
            <th class="text-left px-6 py-4 text-[10px] font-black uppercase tracking-[.12em] text-slate-400">Document</th>
            <th class="text-left px-6 py-4 text-[10px] font-black uppercase tracking-[.12em] text-slate-400 hidden md:table-cell">Status</th>
            <th class="text-left px-6 py-4 text-[10px] font-black uppercase tracking-[.12em] text-slate-400 hidden lg:table-cell">Downloads</th>
            <th class="text-left px-6 py-4 text-[10px] font-black uppercase tracking-[.12em] text-slate-400 hidden lg:table-cell">Country</th>
            <th class="text-left px-6 py-4 text-[10px] font-black uppercase tracking-[.12em] text-slate-400 hidden xl:table-cell">Updated</th>
            <th class="px-6 py-4 text-right text-[10px] font-black uppercase tracking-[.12em] text-slate-400">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          <?php foreach ($posts as $p):
            $isOwner    = ($p['authorId'] === $uid);
            $canDelete  = $isSuper || ($isOwner && in_array($p['status'], ['DRAFT','REJECTED']));
            $canSubmit  = !$isSuper && $isOwner && in_array($p['status'], ['DRAFT','REJECTED']);
            $canArch    = $isSuper && in_array($p['status'], ['PUBLISHED','ARCHIVED']);
            $fileType   = !empty($p['mediaUrl']) ? docFileType($p['mediaUrl']) : 'DOC';
            $fileColor  = docFileColor($fileType);
          ?>
          <tr class="hover:bg-slate-50/70 transition-colors group">
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <!-- File type icon -->
                <div class="w-10 h-12 rounded-lg flex flex-col items-center justify-center shrink-0 shadow-sm"
                     style="background:<?= $fileColor ?>15;border:1px solid <?= $fileColor ?>30">
                  <svg width="14" height="14" fill="none" stroke="<?= $fileColor ?>" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                  </svg>
                  <span class="text-[8px] font-black mt-0.5" style="color:<?= $fileColor ?>"><?= $fileType ?></span>
                </div>
                <div class="min-w-0">
                  <p class="text-[13px] font-semibold text-slate-900 truncate max-w-xs"><?= h($p['title']) ?></p>
                  <div class="flex items-center gap-2 mt-0.5">
                    <?php if (!empty($p['issueCategory'])): ?>
                      <span class="text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full" style="background:rgba(117,11,37,.08);color:#750B25"><?= h($p['issueCategory']) ?></span>
                    <?php endif; ?>
                    <?php if ($isSuper): ?>
                      <span class="text-[10px] text-slate-400"><?= h($p['authorName'] ?? '—') ?></span>
                    <?php endif; ?>
                    <?php if (empty($p['mediaUrl'])): ?>
                      <span class="text-[9px] font-black px-1.5 py-0.5 rounded" style="background:rgba(239,68,68,.08);color:#dc2626">No File</span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 hidden md:table-cell"><?= status_badge($p['status']) ?></td>
            <td class="px-6 py-4 hidden lg:table-cell">
              <div class="flex items-center gap-1">
                <svg width="11" height="11" fill="none" stroke="#E7952A" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span class="text-[12px] font-semibold text-slate-700"><?= number_format((int)($p['downloadCount'] ?? 0)) ?></span>
              </div>
            </td>
            <td class="px-6 py-4 hidden lg:table-cell text-[12px] font-semibold text-slate-700"><?= h($p['country']) ?></td>
            <td class="px-6 py-4 text-[11px] text-slate-400 hidden xl:table-cell"><?= format_relative_time($p['updatedAt']) ?></td>
            <td class="px-6 py-4">
              <div class="flex items-center justify-end gap-1.5">
                <?php if (!empty($p['mediaUrl'])): ?>
                  <a href="<?= h($p['mediaUrl']) ?>" target="_blank" rel="noopener"
                     class="inline-flex items-center gap-1 px-3 py-1.5 text-[10px] font-bold rounded-lg transition-colors"
                     style="background:rgba(231,149,42,.1);color:#b45309">
                    <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    View
                  </a>
                <?php endif; ?>
                <a href="/admin/content/<?= h($p['id']) ?>/edit"
                   class="inline-flex items-center gap-1 px-3 py-1.5 text-[10px] font-bold rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">Edit</a>
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
                  <button onclick="postAction('/api/posts/<?= h($p['id']) ?>','DELETE',this,'Delete this document?')"
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
            <a href="<?= h(docHref($statusFilter, $page-1, $search)) ?>" class="px-3 py-2 text-[11px] font-bold bg-white rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">&larr; Prev</a>
          <?php else: ?>
            <span class="px-3 py-2 text-[11px] font-bold rounded-xl text-slate-300">&larr; Prev</span>
          <?php endif; ?>
          <span class="px-3 py-2 text-[11px] font-bold text-slate-500"><?= $page ?> / <?= $totalPages ?></span>
          <?php if ($page < $totalPages): ?>
            <a href="<?= h(docHref($statusFilter, $page+1, $search)) ?>" class="px-3 py-2 text-[11px] font-bold bg-white rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">Next &rarr;</a>
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

<!-- ── Create Document Drawer ─────────────────────────────────────── -->
<div id="create-backdrop" onclick="closeCreateDrawer()"
     style="position:fixed;inset:0;z-index:400;background:rgba(0,0,0,.45);opacity:0;pointer-events:none;transition:opacity .25s"></div>

<div id="create-drawer"
     style="position:fixed;top:0;right:0;bottom:0;z-index:500;width:min(540px,100vw);
            background:#fff;transform:translateX(100%);transition:transform .3s cubic-bezier(.4,0,.2,1);
            overflow-y:auto;display:flex;flex-direction:column;box-shadow:-8px 0 48px rgba(0,0,0,.14)">

  <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid #f1f5f9;flex-shrink:0;position:sticky;top:0;background:#fff;z-index:1">
    <div>
      <p style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.14em;color:#750B25;margin:0 0 3px">Upload Document</p>
      <p style="font-size:13px;font-weight:600;color:#0f172a;margin:0">Saved as draft — publish after review</p>
    </div>
    <button onclick="closeCreateDrawer()" style="width:36px;height:36px;border-radius:50%;border:none;background:#f1f5f9;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#64748b;font-size:20px;line-height:1">&times;</button>
  </div>

  <?php if ($createError): ?>
    <div style="margin:16px 24px 0;padding:12px 16px;background:#fef2f2;border:1px solid #fecaca;border-radius:12px;color:#dc2626;font-size:13px;font-weight:500"><?= h($createError) ?></div>
  <?php endif; ?>

  <form method="POST" action="/admin/content/documents" style="flex:1;padding:24px;display:flex;flex-direction:column;gap:18px">
    <input type="hidden" name="_action" value="create">

    <div>
      <label style="display:block;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:#94a3b8;margin-bottom:7px">Document Title *</label>
      <input type="text" name="title" required value="<?= h(($createError ? $_POST['title'] : '') ?? '') ?>"
             style="width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#f8fafc;font-size:14px;box-sizing:border-box;outline:none;font-family:inherit"
             placeholder="e.g. Annual Peace Report 2024">
    </div>

    <div>
      <label style="display:block;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:#94a3b8;margin-bottom:7px">Short Description</label>
      <textarea name="description" rows="2"
                style="width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#f8fafc;font-size:14px;box-sizing:border-box;outline:none;resize:none;font-family:inherit"
                placeholder="Brief description shown in listings"><?= h(($createError ? $_POST['description'] : '') ?? '') ?></textarea>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <div>
        <label style="display:block;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:#94a3b8;margin-bottom:7px">Cover Image URL</label>
        <input type="url" name="thumbnailUrl" value="<?= h(($createError ? $_POST['thumbnailUrl'] : '') ?? '') ?>"
               style="width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#f8fafc;font-size:14px;box-sizing:border-box;outline:none;font-family:inherit"
               placeholder="https://…">
      </div>
      <div>
        <label style="display:block;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:#94a3b8;margin-bottom:7px">Document URL *</label>
        <input type="url" name="mediaUrl" required value="<?= h(($createError ? $_POST['mediaUrl'] : '') ?? '') ?>"
               style="width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#f8fafc;font-size:14px;box-sizing:border-box;outline:none;font-family:inherit"
               placeholder="https://…/report.pdf">
      </div>
    </div>

    <div>
      <label style="display:block;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:#94a3b8;margin-bottom:7px">Abstract / Summary</label>
      <textarea name="content" rows="5"
                style="width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#f8fafc;font-size:14px;box-sizing:border-box;outline:none;resize:vertical;font-family:inherit"
                placeholder="Key findings, scope, or executive summary…"><?= h(($createError ? $_POST['content'] : '') ?? '') ?></textarea>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <div>
        <label style="display:block;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:#94a3b8;margin-bottom:7px">Country *</label>
        <select name="country" required style="width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#f8fafc;font-size:14px;box-sizing:border-box;outline:none;font-family:inherit">
          <option value="">Select</option>
          <?php foreach (african_countries() as $c): ?>
            <option value="<?= h($c) ?>" <?= (($_POST['country'] ?? '') === $c && $createError) ? 'selected' : '' ?>><?= h($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="display:block;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:#94a3b8;margin-bottom:7px">Region *</label>
        <input type="text" name="region" required value="<?= h(($createError ? $_POST['region'] : '') ?? '') ?>"
               style="width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#f8fafc;font-size:14px;box-sizing:border-box;outline:none;font-family:inherit"
               placeholder="e.g. Nairobi">
      </div>
    </div>

    <div>
      <label style="display:block;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:#94a3b8;margin-bottom:7px">Issue Category *</label>
      <select name="issueCategory" required style="width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#f8fafc;font-size:14px;box-sizing:border-box;outline:none;font-family:inherit">
        <option value="">Select category</option>
        <?php foreach (issue_categories() as $c): ?>
          <option value="<?= h($c) ?>" <?= (($_POST['issueCategory'] ?? '') === $c && $createError) ? 'selected' : '' ?>><?= h($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="display:flex;gap:12px;padding-top:4px;padding-bottom:8px">
      <button type="submit"
              style="flex:1;padding:13px;border:none;border-radius:12px;background:#750B25;color:#fff;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;transition:opacity .15s"
              onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
        Save as Draft
      </button>
      <button type="button" onclick="closeCreateDrawer()"
              style="padding:13px 20px;border:1.5px solid #e2e8f0;border-radius:12px;background:#fff;color:#64748b;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit">
        Cancel
      </button>
    </div>
  </form>
</div>

<script>
function openCreateDrawer() {
  document.getElementById('create-drawer').style.transform = 'translateX(0)';
  var bd = document.getElementById('create-backdrop');
  bd.style.opacity = '1'; bd.style.pointerEvents = 'auto';
  document.body.style.overflow = 'hidden';
}
function closeCreateDrawer() {
  document.getElementById('create-drawer').style.transform = 'translateX(100%)';
  var bd = document.getElementById('create-backdrop');
  bd.style.opacity = '0'; bd.style.pointerEvents = 'none';
  document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeCreateDrawer(); });
<?php if ($createError): ?>document.addEventListener('DOMContentLoaded', openCreateDrawer);<?php endif; ?>
<?php if (isset($_GET['created'])): ?>
document.addEventListener('DOMContentLoaded', function(){
  var b = document.createElement('div');
  b.textContent = 'Document saved as draft.';
  b.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:999;background:#0f172a;color:#fff;padding:12px 20px;border-radius:12px;font-size:13px;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,.2)';
  document.body.appendChild(b);
  setTimeout(function(){ b.remove(); }, 3500);
});
<?php endif; ?>
</script>
</body>
</html>
