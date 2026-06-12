<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$user   = require_auth();
$isSuper = is_super_admin();
$uid    = $user['id'];

$pdo = db();
$where     = $isSuper ? '' : "AND p.authorId = '$uid'";
$whereOnly = $isSuper ? '1' : "authorId = '$uid'";

$totalPosts     = (int)$pdo->query("SELECT COUNT(*) FROM Post WHERE $whereOnly")->fetchColumn();
$publishedPosts = (int)$pdo->query("SELECT COUNT(*) FROM Post WHERE $whereOnly AND status='PUBLISHED'")->fetchColumn();
$pendingPosts   = (int)$pdo->query("SELECT COUNT(*) FROM Post WHERE $whereOnly AND status='PENDING'")->fetchColumn();
$draftPosts     = (int)$pdo->query("SELECT COUNT(*) FROM Post WHERE $whereOnly AND status='DRAFT'")->fetchColumn();

$viewsRow   = $pdo->query("SELECT COALESCE(SUM(viewCount),0) AS v, COALESCE(SUM(downloadCount),0) AS d FROM Post WHERE $whereOnly")->fetch();
$totalViews = (int)$viewsRow['v'];
$totalDL    = (int)$viewsRow['d'];

$flagged = (int)$pdo->query("SELECT COUNT(*) FROM Comment WHERE isFlagged=1")->fetchColumn();

$recentPosts = $pdo->query(
    "SELECT p.id, p.title, p.type, p.status, p.country, p.issueCategory, p.updatedAt,
            u.name AS authorName, u.username AS authorUsername
     FROM Post p LEFT JOIN User u ON p.authorId = u.id
     WHERE $whereOnly
     ORDER BY p.updatedAt DESC LIMIT 5"
)->fetchAll();

$typeIcon = ['ARTICLE'=>'📄','GALLERY_IMAGE'=>'🖼️','PODCAST'=>'🎧','VIDEO'=>'🎬','DOCUMENT'=>'📁'];

$pageTitle = 'Dashboard | Tafakari Admin';
?>
<?php include dirname(__DIR__) . '/includes/head.php'; ?>
<body class="antialiased bg-slate-100 font-inter">
<div class="flex h-screen overflow-hidden">
<?php include dirname(__DIR__) . '/includes/admin-sidebar.php'; ?>

<div class="flex-1 overflow-y-auto p-10">
  <!-- Header -->
  <div class="flex justify-between items-center mb-10">
    <div>
      <h1 class="font-outfit text-3xl font-bold text-slate-900">Dashboard Overview</h1>
      <p class="text-slate-500 mt-1">Welcome back,
        <strong class="text-primary"><?= h($user['name'] ?? $user['username']) ?></strong>
        <?php if ($isSuper): ?>
          <span class="ml-2 text-[10px] font-black uppercase tracking-widest text-rose-500 bg-rose-50 px-2 py-0.5 rounded-full">Super Admin</span>
        <?php endif; ?>
      </p>
    </div>
    <a href="/admin/content/new" class="btn-primary">+ Create Content</a>
  </div>

  <!-- Stat Cards -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <?php
    $stats = [
      ['label' => 'Published',        'value' => format_number($publishedPosts), 'sub' => 'of ' . $totalPosts . ' total',  'bar' => 'bg-indigo-500'],
      ['label' => 'Pending Approval', 'value' => $pendingPosts,                  'sub' => $pendingPosts > 0 ? 'Action Needed' : 'All clear', 'bar' => 'bg-rose-500'],
      ['label' => 'Total Views',      'value' => format_number($totalViews),     'sub' => format_number($totalDL) . ' downloads', 'bar' => 'bg-sky-500'],
      ['label' => 'Flagged Comments', 'value' => $flagged,                        'sub' => $flagged > 0 ? 'Needs review' : 'All clear', 'bar' => 'bg-amber-500'],
    ];
    foreach ($stats as $s): ?>
      <div class="bg-white rounded-3xl p-6 shadow-sm overflow-hidden relative border border-slate-100">
        <div class="absolute top-0 right-0 w-1.5 h-full <?= $s['bar'] ?> opacity-30 rounded-r-3xl"></div>
        <span class="text-xs font-black uppercase tracking-widest text-slate-400 mb-4 block"><?= h($s['label']) ?></span>
        <div class="flex justify-between items-end">
          <span class="text-4xl font-outfit font-black text-slate-900"><?= h((string)$s['value']) ?></span>
          <span class="text-[10px] font-bold px-2 py-1 rounded-full bg-slate-50 text-slate-500"><?= h($s['sub']) ?></span>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Super Admin Breakdown -->
  <?php if ($isSuper): ?>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <?php
    $breakdown = [
      ['label'=>'Draft',     'value'=>$draftPosts,     'href'=>'/admin/content?status=DRAFT',     'cls'=>'bg-slate-50 border-slate-200 text-slate-700'],
      ['label'=>'Pending',   'value'=>$pendingPosts,   'href'=>'/admin/content?status=PENDING',   'cls'=>'bg-amber-50 border-amber-200 text-amber-700'],
      ['label'=>'Published', 'value'=>$publishedPosts, 'href'=>'/admin/content?status=PUBLISHED', 'cls'=>'bg-emerald-50 border-emerald-200 text-emerald-700'],
      ['label'=>'Total',     'value'=>$totalPosts,     'href'=>'/admin/content',                  'cls'=>'bg-indigo-50 border-indigo-200 text-indigo-700'],
    ];
    foreach ($breakdown as $b): ?>
      <a href="<?= h($b['href']) ?>"
         class="p-5 rounded-2xl border text-center hover:scale-105 transition-transform <?= $b['cls'] ?>">
        <div class="text-2xl font-black"><?= $b['value'] ?></div>
        <div class="text-[10px] font-black uppercase tracking-widest mt-1"><?= h($b['label']) ?></div>
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Recent Activity -->
    <div class="lg:col-span-2 bg-white rounded-4xl p-8 shadow-sm border border-slate-100">
      <div class="flex justify-between items-center mb-8">
        <h3 class="font-outfit font-bold text-xl">Recent Activity</h3>
        <a href="/admin/content" class="text-xs font-bold hover:underline" style="color:#9A1415">View All</a>
      </div>
      <?php if (empty($recentPosts)): ?>
        <p class="text-slate-400 text-sm text-center py-8">No content yet.
          <a href="/admin/content/new" class="text-primary font-bold hover:underline">Create your first post.</a>
        </p>
      <?php else: ?>
        <div class="space-y-6">
          <?php foreach ($recentPosts as $p):
            $author = $p['authorName'] ?? $p['authorUsername'] ?? '—';
          ?>
            <div class="flex items-center gap-4 group">
              <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center text-lg shrink-0">
                <?= $typeIcon[$p['type']] ?? '📄' ?>
              </div>
              <div class="flex-grow min-w-0">
                <h4 class="text-sm font-bold text-slate-800 truncate"><?= h($p['title']) ?></h4>
                <p class="text-[10px] text-slate-400 mt-0.5 uppercase font-bold tracking-tighter">
                  <?= h($p['country']) ?> &bull; <?= h($p['issueCategory']) ?> &bull; <?= format_relative_time($p['updatedAt']) ?>
                  <?php if ($isSuper): ?> &bull; <?= h($author) ?><?php endif; ?>
                </p>
              </div>
              <?= status_badge($p['status']) ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Quick Tip -->
    <div class="bg-slate-950 rounded-4xl p-8 shadow-sm text-white flex flex-col justify-between">
      <div>
        <h3 class="font-outfit font-bold text-xl mb-4">Quick Tip</h3>
        <p class="text-slate-400 text-sm leading-relaxed">
          Always tag your content with a valid <strong>Region</strong> and <strong>Issue Category</strong>
          so it appears correctly on the regional heatmap.
        </p>
        <?php if ($isSuper && $pendingPosts > 0): ?>
          <div class="mt-6 p-4 rounded-2xl border" style="background:rgba(245,158,11,.1);border-color:rgba(245,158,11,.2)">
            <p class="text-amber-400 text-xs font-bold">
              <?= $pendingPosts ?> submission<?= $pendingPosts !== 1 ? 's' : '' ?> awaiting approval
            </p>
            <a href="/admin/super/approvals" class="text-xs text-amber-300 hover:text-white underline mt-1 block">
              Go to Approval Queue &rarr;
            </a>
          </div>
        <?php endif; ?>
      </div>
      <div class="mt-12 p-4 rounded-2xl border" style="background:rgba(255,255,255,.05);border-color:rgba(255,255,255,.1)">
        <h4 class="text-xs font-bold uppercase tracking-widest mb-2 opacity-60">System Health</h4>
        <div class="flex items-center gap-2">
          <div class="w-2 h-2 rounded-full animate-pulse" style="background:#D99F51"></div>
          <span class="text-sm font-medium">All systems operational</span>
        </div>
      </div>
    </div>
  </div>
</div>
</div>
</body>
</html>
