<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$user    = require_auth();
$isSuper = is_super_admin();
$uid     = $user['id'];
$pdo     = db();

// Bound rather than interpolated, so this stays safe if the id source ever changes
$whereOnly   = $isSuper ? '1' : 'authorId = ?';
$whereParams = $isSuper ? []  : [$uid];

$run = function (string $sql) use ($pdo, $whereParams) {
    $st = $pdo->prepare($sql);
    $st->execute($whereParams);
    return $st;
};

$totalPosts     = (int)$run("SELECT COUNT(*) FROM Post WHERE $whereOnly")->fetchColumn();
$publishedPosts = (int)$run("SELECT COUNT(*) FROM Post WHERE $whereOnly AND status='PUBLISHED'")->fetchColumn();
$pendingPosts   = (int)$run("SELECT COUNT(*) FROM Post WHERE $whereOnly AND status='PENDING'")->fetchColumn();
$draftPosts     = (int)$run("SELECT COUNT(*) FROM Post WHERE $whereOnly AND status='DRAFT'")->fetchColumn();

$viewsRow   = $run("SELECT COALESCE(SUM(viewCount),0) AS v, COALESCE(SUM(downloadCount),0) AS d FROM Post WHERE $whereOnly")->fetch();
$totalViews = (int)$viewsRow['v'];
$totalDL    = (int)$viewsRow['d'];

$flagged    = (int)$pdo->query("SELECT COUNT(*) FROM Comment WHERE isFlagged=1")->fetchColumn();
$totalUsers = $isSuper ? (int)$pdo->query("SELECT COUNT(*) FROM User")->fetchColumn() : 0;

$recentPosts = $run(
    "SELECT p.id, p.title, p.type, p.status, p.country, p.issueCategory, p.updatedAt,
            u.name AS authorName, u.username AS authorUsername
     FROM Post p LEFT JOIN User u ON p.authorId = u.id
     WHERE $whereOnly
     ORDER BY p.updatedAt DESC LIMIT 6"
)->fetchAll();

// ── Chart: content by type ─────────────────────────────────────────────────
$typeStats = array_fill_keys(['ARTICLE','VIDEO','PODCAST','DOCUMENT','GALLERY_IMAGE','POLICY_BRIEF'], 0);
try {
    if ($isSuper) {
        $rows = $pdo->query("SELECT type, COUNT(*) as cnt FROM Post GROUP BY type")->fetchAll();
    } else {
        $st = $pdo->prepare("SELECT type, COUNT(*) as cnt FROM Post WHERE authorId=? GROUP BY type");
        $st->execute([$uid]); $rows = $st->fetchAll();
    }
    foreach ($rows as $r) if (isset($typeStats[$r['type']])) $typeStats[$r['type']] = (int)$r['cnt'];
} catch (Exception $e) {}

// ── Chart: monthly activity (last 6 months) ────────────────────────────────
$monthLabels = [];
$monthMap    = [];
for ($i = 5; $i >= 0; $i--) {
    $key = date('Y-m', strtotime("-{$i} months"));
    $monthLabels[] = date('M', strtotime("-{$i} months"));
    $monthMap[$key] = 0;
}
try {
    if ($isSuper) {
        $rows = $pdo->query(
            "SELECT DATE_FORMAT(createdAt,'%Y-%m') as ym, COUNT(*) as cnt
             FROM Post WHERE createdAt >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
             GROUP BY ym ORDER BY ym ASC"
        )->fetchAll();
    } else {
        $st = $pdo->prepare(
            "SELECT DATE_FORMAT(createdAt,'%Y-%m') as ym, COUNT(*) as cnt
             FROM Post WHERE authorId=? AND createdAt >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
             GROUP BY ym ORDER BY ym ASC"
        );
        $st->execute([$uid]); $rows = $st->fetchAll();
    }
    foreach ($rows as $r) if (isset($monthMap[$r['ym']])) $monthMap[$r['ym']] = (int)$r['cnt'];
} catch (Exception $e) {}
$monthCounts = array_values($monthMap);

// ── Top content by views ───────────────────────────────────────────────────
$topContent = [];
try {
    if ($isSuper) {
        $topContent = $pdo->query(
            "SELECT id, title, type, country, viewCount, downloadCount
             FROM Post WHERE status='PUBLISHED' AND (viewCount > 0 OR downloadCount > 0)
             ORDER BY (viewCount + downloadCount) DESC LIMIT 5"
        )->fetchAll();
    } else {
        $st = $pdo->prepare(
            "SELECT id, title, type, country, viewCount, downloadCount
             FROM Post WHERE authorId=? AND status='PUBLISHED' AND (viewCount > 0 OR downloadCount > 0)
             ORDER BY (viewCount + downloadCount) DESC LIMIT 5"
        );
        $st->execute([$uid]); $topContent = $st->fetchAll();
    }
} catch (Exception $e) {}

// ── Views by country ────────────────────────────────────────────────────────
$viewsByCountry = [];
$viewsByCountryMax = 1;
try {
    $rows = $run(
        "SELECT country, SUM(viewCount) AS tv FROM Post
         WHERE $whereOnly AND status='PUBLISHED' AND viewCount > 0 AND country IS NOT NULL AND country != ''
         GROUP BY country ORDER BY tv DESC LIMIT 8"
    )->fetchAll();
    foreach ($rows as $r) $viewsByCountry[$r['country']] = (int)$r['tv'];
    if ($viewsByCountry) $viewsByCountryMax = max($viewsByCountry);
} catch (Exception $e) {}

// ── Recent unmoderated comments (super only) ────────────────────────────────
$recentComments = [];
if ($isSuper) {
    try {
        $recentComments = $pdo->query(
            "SELECT c.id, c.content, c.name, c.email, c.createdAt, c.isFlagged,
                    p.title AS postTitle, p.id AS postId
             FROM Comment c JOIN Post p ON c.postId = p.id
             WHERE c.isModerated = 0
             ORDER BY c.createdAt DESC LIMIT 5"
        )->fetchAll();
    } catch (Exception $e) {}
}

// ── Content engagement totals ───────────────────────────────────────────────
$totalComments    = 0;
$pendingComments  = 0;
try {
    $totalComments   = (int)$pdo->query("SELECT COUNT(*) FROM Comment")->fetchColumn();
    $pendingComments = (int)$pdo->query("SELECT COUNT(*) FROM Comment WHERE isModerated=0")->fetchColumn();
} catch (Exception $e) {}

// ── Real system health check ───────────────────────────────────────────────
$dbOk = false;
try { $pdo->query('SELECT 1'); $dbOk = true; } catch (Exception $e) {}

$hour     = (int)date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

$pageTitle      = 'Dashboard Overview | Tafakari Admin';
$adminPageTitle = 'Dashboard Overview';
$adminPageSub   = $greeting . ', ' . ($user['name'] ?? $user['username']) . '. Here\'s what\'s happening.';
?>
<?php include dirname(__DIR__) . '/includes/head.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
  .kpi-icon { transition: transform .2s; }
  .kpi-card:hover .kpi-icon { transform: scale(1.1) rotate(-3deg); }
  .kpi-card { cursor: pointer; }
</style>

<body class="antialiased font-inter" style="background:#F4F6F8">
<div class="flex h-screen overflow-hidden">
<?php include dirname(__DIR__) . '/includes/admin-sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden min-w-0">
<?php include dirname(__DIR__) . '/includes/admin-topbar.php'; ?>

<main class="flex-1 overflow-y-auto p-6 md:p-8">

  <!-- Welcome Banner -->
  <?php $_initials = strtoupper(substr($user['name'] ?? $user['username'] ?? 'U', 0, 1)); ?>
  <div class="rounded-2xl bg-white border border-slate-100 shadow-sm overflow-hidden mb-8">
    <div class="flex flex-col md:flex-row items-start md:items-center gap-5 p-6 md:p-8"
         style="border-left:4px solid #750B25">
      <!-- Avatar -->
      <div class="w-14 h-14 rounded-2xl flex items-center justify-center font-outfit font-black text-2xl text-white shrink-0"
           style="background:#750B25"><?= h($_initials) ?></div>
      <!-- Text -->
      <div class="flex-1 min-w-0">
        <p class="text-[10px] font-black uppercase tracking-[.14em] mb-0.5" style="color:#E7952A"><?= $greeting ?></p>
        <h2 class="font-outfit font-black text-xl text-slate-900 leading-tight">
          <?= h($user['name'] ?? $user['username']) ?>
        </h2>
        <p class="text-slate-400 text-sm mt-0.5">
          <?php if ($isSuper): ?>
            Super Administrator &bull; <?= format_number($totalPosts) ?> total posts &bull; <?= format_number($publishedPosts) ?> published
          <?php else: ?>
            <?= $totalPosts ?> post<?= $totalPosts !== 1 ? 's' : '' ?> in workspace &mdash; <?= $publishedPosts ?> published<?= $pendingPosts > 0 ? ', ' . $pendingPosts . ' pending' : '' ?>
          <?php endif; ?>
        </p>
      </div>
      <!-- Actions -->
      <div class="flex items-center gap-3 shrink-0 flex-wrap">
        <?php if ($isSuper && $pendingPosts > 0): ?>
          <a href="/admin/super/approvals"
             class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-[12px] font-bold border transition-colors hover:bg-amber-50"
             style="color:#92400E;border-color:#FDE68A;background:rgba(254,243,199,.6)">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse shrink-0"></span>
            <?= $pendingPosts ?> Pending Review
          </a>
        <?php endif; ?>
        <a href="/admin/content/new"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-[12px] font-bold text-white transition-all hover:opacity-90 active:scale-95"
           style="background:#750B25">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
          New Content
        </a>
      </div>
    </div>
    <?php if ($totalPosts > 0): ?>
    <div class="px-8 pb-5 pt-1">
      <?php $pubPct = $totalPosts > 0 ? (int)round(($publishedPosts / $totalPosts) * 100) : 0; ?>
      <div class="flex items-center justify-between mb-1.5">
        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Publication Progress</p>
        <p class="text-[10px] font-bold text-slate-500"><?= $publishedPosts ?> / <?= $totalPosts ?> published &mdash; <?= $pubPct ?>%</p>
      </div>
      <div class="h-1 bg-slate-100 rounded-full overflow-hidden">
        <div class="h-full rounded-full transition-all duration-700" style="width:<?= $pubPct ?>%;background:#750B25"></div>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- KPI Cards -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <?php
    $kpis = [
      [
        'label'    => 'Published',
        'value'    => format_number($publishedPosts),
        'meta'     => 'of ' . $totalPosts . ' total',
        'href'     => '/admin/content?status=PUBLISHED',
        'icon'     => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
        'ic_bg'    => 'rgba(16,185,129,.1)',
        'ic_col'   => '#10B981',
        'trend'    => $publishedPosts > 0 ? 'Live' : 'None',
        'trendcls' => $publishedPosts > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500',
      ],
      [
        'label'    => 'Pending Review',
        'value'    => $pendingPosts,
        'meta'     => $pendingPosts > 0 ? 'Needs attention' : 'Queue clear',
        'href'     => '/admin/content?status=PENDING',
        'icon'     => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'ic_bg'    => 'rgba(245,158,11,.1)',
        'ic_col'   => '#F59E0B',
        'trend'    => $pendingPosts > 0 ? '!' : '✓',
        'trendcls' => $pendingPosts > 0 ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700',
      ],
      [
        'label'    => 'Total Views',
        'value'    => format_number($totalViews),
        'meta'     => format_number($totalDL) . ' downloads',
        'href'     => '/admin/content',
        'icon'     => 'M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        'ic_bg'    => 'rgba(99,102,241,.1)',
        'ic_col'   => '#6366F1',
        'trend'    => 'Impressions',
        'trendcls' => 'bg-indigo-50 text-indigo-700',
      ],
      [
        'label'    => 'Comments',
        'value'    => format_number($totalComments),
        'meta'     => $pendingComments > 0 ? $pendingComments . ' pending moderation' : ($flagged > 0 ? $flagged . ' flagged' : 'All moderated'),
        'href'     => '/admin/super/moderation',
        'icon'     => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
        'ic_bg'    => ($pendingComments > 0 || $flagged > 0) ? 'rgba(239,68,68,.1)' : 'rgba(16,185,129,.1)',
        'ic_col'   => ($pendingComments > 0 || $flagged > 0) ? '#EF4444' : '#10B981',
        'trend'    => $pendingComments > 0 ? '!' : ($flagged > 0 ? 'Flagged' : '✓'),
        'trendcls' => ($pendingComments > 0 || $flagged > 0) ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700',
      ],
    ];
    foreach ($kpis as $k): ?>
      <a href="<?= h($k['href']) ?>"
         class="kpi-card relative bg-white rounded-2xl p-6 border border-slate-100 shadow-sm flex flex-col gap-4 hover:shadow-lg hover:-translate-y-1 transition-all duration-200 overflow-hidden">
        <div class="absolute inset-x-0 top-0 h-[3px] rounded-t-2xl" style="background:<?= $k['ic_col'] ?>"></div>
        <div class="flex items-start justify-between">
          <div class="kpi-icon w-11 h-11 rounded-2xl flex items-center justify-center shrink-0"
               style="background:<?= $k['ic_bg'] ?>">
            <svg width="20" height="20" fill="none" stroke="<?= $k['ic_col'] ?>" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
              <path d="<?= $k['icon'] ?>"/>
            </svg>
          </div>
          <span class="text-[9px] font-black uppercase tracking-wide px-2.5 py-1 rounded-full <?= $k['trendcls'] ?>">
            <?= h($k['trend']) ?>
          </span>
        </div>
        <div>
          <div class="font-outfit font-black text-4xl text-slate-900 leading-none"><?= h((string)$k['value']) ?></div>
          <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mt-2"><?= h($k['label']) ?></p>
          <p class="text-[11px] text-slate-400 mt-0.5"><?= h($k['meta']) ?></p>
        </div>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Secondary Stats (Super Admin) -->
  <?php if ($isSuper): ?>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-8">
    <?php
    $breakdown = [
      ['label'=>'Draft',     'value'=>$draftPosts,     'href'=>'/admin/content?status=DRAFT',     'bg'=>'bg-slate-50',   'border'=>'border-slate-200',  'txt'=>'text-slate-700',  'num'=>'text-slate-900'],
      ['label'=>'Pending',   'value'=>$pendingPosts,   'href'=>'/admin/content?status=PENDING',   'bg'=>'bg-amber-50',   'border'=>'border-amber-200',  'txt'=>'text-amber-700',  'num'=>'text-amber-900'],
      ['label'=>'Published', 'value'=>$publishedPosts, 'href'=>'/admin/content?status=PUBLISHED', 'bg'=>'bg-emerald-50', 'border'=>'border-emerald-200','txt'=>'text-emerald-700','num'=>'text-emerald-900'],
      ['label'=>'Users',     'value'=>$totalUsers,     'href'=>'/admin/super/users',              'bg'=>'bg-indigo-50',  'border'=>'border-indigo-200', 'txt'=>'text-indigo-700', 'num'=>'text-indigo-900'],
    ];
    foreach ($breakdown as $b): ?>
      <a href="<?= h($b['href']) ?>"
         class="flex items-center justify-between px-4 py-3.5 rounded-xl border hover:scale-[1.02] transition-transform <?= $b['bg'] ?> <?= $b['border'] ?>">
        <span class="text-[10px] font-black uppercase tracking-widest <?= $b['txt'] ?>"><?= h($b['label']) ?></span>
        <span class="font-outfit font-black text-xl <?= $b['num'] ?>"><?= $b['value'] ?></span>
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Main Content Grid -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    <!-- Recent Activity -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
      <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
        <h3 class="font-outfit font-bold text-[15px] text-slate-900">Recent Activity</h3>
        <a href="/admin/content" class="text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-primary transition-colors">
          View All &rsaquo;
        </a>
      </div>

      <?php if (empty($recentPosts)): ?>
        <div class="flex flex-col items-center justify-center py-16 px-8 text-center">
          <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-4" style="background:rgba(117,11,37,.06)">
            <svg width="22" height="22" fill="none" stroke="#750B25" stroke-width="1.6" stroke-linecap="round" viewBox="0 0 24 24">
              <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
          </div>
          <p class="text-sm font-semibold text-slate-700">No content yet</p>
          <p class="text-xs text-slate-400 mt-1">
            <a href="/admin/content/new" class="text-primary hover:underline font-bold">Create your first post</a> to get started.
          </p>
        </div>
      <?php else: ?>
        <div class="divide-y divide-slate-50">
          <?php
          $typeIcons = [
            'ARTICLE'       => ['path'=>'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 12h6M7 8h2', 'ic'=>'#750B25', 'ib'=>'rgba(117,11,37,.07)'],
            'GALLERY_IMAGE' => ['path'=>'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z', 'ic'=>'#10B981', 'ib'=>'rgba(16,185,129,.07)'],
            'PODCAST'       => ['path'=>'M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z', 'ic'=>'#E7952A', 'ib'=>'rgba(231,149,42,.07)'],
            'VIDEO'         => ['path'=>'M15 10l4.553-2.069A1 1 0 0121 8.87v6.26a1 1 0 01-1.447.9L15 14M3 8a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z', 'ic'=>'#ED1C24', 'ib'=>'rgba(237,28,36,.07)'],
            'DOCUMENT'      => ['path'=>'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z', 'ic'=>'#6366F1', 'ib'=>'rgba(99,102,241,.07)'],
            'POLICY_BRIEF'  => ['path'=>'M9 17h6M9 13h6M9 9h1m3-6H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2', 'ic'=>'#8B5CF6', 'ib'=>'rgba(139,92,246,.07)'],
          ];
          foreach ($recentPosts as $p):
            $author  = $p['authorName'] ?? $p['authorUsername'] ?? '—';
            $typeInfo = $typeIcons[$p['type']] ?? $typeIcons['ARTICLE'];
            // Show first category only
            $catDisplay = explode(',', $p['issueCategory'] ?? '')[0];
          ?>
            <a href="/admin/content/edit?id=<?= h($p['id']) ?>"
               class="activity-row flex items-center gap-4 px-6 py-4 transition-colors hover:bg-slate-50/70">
              <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                   style="background:<?= $typeInfo['ib'] ?>">
                <svg width="15" height="15" fill="none" stroke="<?= $typeInfo['ic'] ?>" stroke-width="1.8"
                     stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <path d="<?= $typeInfo['path'] ?>"/>
                </svg>
              </div>
              <div class="grow min-w-0">
                <p class="text-[13px] font-semibold text-slate-800 truncate hover:text-primary transition-colors">
                  <?= h($p['title']) ?>
                </p>
                <p class="text-[10px] text-slate-400 mt-0.5 truncate">
                  <?= h($p['country']) ?><?= $catDisplay ? ' &bull; ' . h($catDisplay) : '' ?>
                  <?php if ($isSuper): ?> &bull; <?= h($author) ?><?php endif; ?>
                  <span class="text-slate-300">&bull;</span> <?= format_relative_time($p['updatedAt']) ?>
                </p>
              </div>
              <?= status_badge($p['status']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Right Column -->
    <div class="space-y-4">

      <!-- Quick Actions -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
        <h3 class="font-outfit font-bold text-[15px] text-slate-900 mb-4">Quick Actions</h3>
        <?php
        $actions = [
          ['href'=>'/admin/content/new?type=ARTICLE', 'label'=>'Write Article', 'sub'=>'New article draft', 'svg'=>'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 12h6M7 8h2', 'ic'=>'#750B25', 'ib'=>'rgba(117,11,37,.08)'],
          ['href'=>'/admin/content/new',               'label'=>'New Content',   'sub'=>'Any content type',  'svg'=>'M12 4v16m8-8H4', 'ic'=>'#ED1C24', 'ib'=>'rgba(237,28,36,.08)'],
          ['href'=>'/admin/content',                   'label'=>'All Content',   'sub'=>'Browse & manage',   'svg'=>'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'ic'=>'#6366F1', 'ib'=>'rgba(99,102,241,.08)'],
          ['href'=>'/admin/profile',                   'label'=>'My Profile',    'sub'=>'Update info',       'svg'=>'M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'ic'=>'#64748b', 'ib'=>'rgba(100,116,139,.08)'],
        ];
        if ($isSuper) {
            $actions[] = ['href'=>'/admin/super/users',     'label'=>'Users',         'sub'=>'Manage accounts',  'svg'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', 'ic'=>'#E7952A', 'ib'=>'rgba(231,149,42,.08)'];
            $actions[] = ['href'=>'/admin/super/approvals', 'label'=>'Approvals',     'sub'=>$pendingPosts . ' waiting',  'svg'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', 'ic'=>($pendingPosts > 0 ? '#EF4444' : '#10B981'), 'ib'=>($pendingPosts > 0 ? 'rgba(239,68,68,.08)' : 'rgba(16,185,129,.08)')];
        }
        ?>
        <div class="grid grid-cols-2 gap-2">
          <?php foreach ($actions as $a): ?>
            <a href="<?= h($a['href']) ?>"
               class="group flex flex-col gap-3 p-3.5 rounded-xl border border-slate-100 hover:border-slate-200 hover:shadow-sm transition-all">
              <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                   style="background:<?= $a['ib'] ?>">
                <svg width="16" height="16" fill="none" stroke="<?= $a['ic'] ?>" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <path d="<?= $a['svg'] ?>"/>
                </svg>
              </div>
              <div>
                <p class="text-[12px] font-bold text-slate-800 group-hover:text-primary transition-colors leading-tight"><?= h($a['label']) ?></p>
                <p class="text-[10px] text-slate-400 mt-0.5"><?= h($a['sub']) ?></p>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- System Status -->
      <div class="rounded-2xl p-5 border" style="background:#0D0102;border-color:rgba(255,255,255,.07)">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-outfit font-bold text-[14px] text-white">System Status</h3>
          <span class="flex items-center gap-1.5 text-[9px] font-black uppercase tracking-widest px-2 py-1 rounded-full <?= $dbOk ? 'text-emerald-400 bg-emerald-400/10' : 'text-rose-400 bg-rose-400/10' ?>">
            <span class="w-1.5 h-1.5 rounded-full <?= $dbOk ? 'bg-emerald-400 animate-pulse' : 'bg-rose-400' ?>"></span>
            <?= $dbOk ? 'All OK' : 'Error' ?>
          </span>
        </div>
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-[11px] text-white/40">Database</span>
            <span class="text-[11px] font-semibold <?= $dbOk ? 'text-emerald-400' : 'text-rose-400' ?>"><?= $dbOk ? 'Connected' : 'Disconnected' ?></span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-[11px] text-white/40">Session</span>
            <span class="text-[11px] font-semibold text-emerald-400">Active</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-[11px] text-white/40">PHP</span>
            <span class="text-[11px] font-mono text-white/35"><?= phpversion() ?></span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-[11px] text-white/40">Local Time</span>
            <span class="text-[11px] font-mono text-white/35"><?= date('H:i T') ?></span>
          </div>
          <div class="pt-2 border-t" style="border-color:rgba(255,255,255,.05)">
            <div class="flex items-center justify-between mb-1">
              <span class="text-[10px] text-white/40 uppercase tracking-widest">Published</span>
              <span class="text-[10px] font-bold text-white/50"><?= $publishedPosts ?>/<?= $totalPosts ?></span>
            </div>
            <div class="h-1 rounded-full overflow-hidden" style="background:rgba(255,255,255,.06)">
              <?php $_sp = $totalPosts > 0 ? (int)round(($publishedPosts/$totalPosts)*100) : 0; ?>
              <div class="h-full rounded-full" style="width:<?= $_sp ?>%;background:#750B25"></div>
            </div>
          </div>
        </div>
        <?php if ($isSuper && $pendingPosts > 0): ?>
          <div class="mt-4 pt-3 border-t" style="border-color:rgba(255,255,255,.06)">
            <a href="/admin/super/approvals"
               class="flex items-center gap-2 text-[11px] font-bold hover:opacity-80 transition-opacity"
               style="color:#E7952A">
              <span class="w-4 h-4 flex items-center justify-center rounded-full shrink-0" style="background:rgba(231,149,42,.2)">
                <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01"/></svg>
              </span>
              <?= $pendingPosts ?> post<?= $pendingPosts !== 1 ? 's' : '' ?> awaiting approval
            </a>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>

  <!-- Analytics Charts -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Monthly Activity Bar Chart -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h3 class="font-outfit font-bold text-[15px] text-slate-900">Monthly Activity</h3>
          <p class="text-[11px] text-slate-400 mt-0.5">Posts created — last 6 months</p>
        </div>
        <div class="text-right">
          <div class="font-outfit font-black text-2xl text-slate-900"><?= array_sum($monthCounts) ?></div>
          <p class="text-[10px] text-slate-400 uppercase tracking-widest">Total</p>
        </div>
      </div>
      <canvas id="monthlyChart" height="100"></canvas>
    </div>

    <!-- Content Types Doughnut Chart -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
      <div class="mb-4">
        <h3 class="font-outfit font-bold text-[15px] text-slate-900">Content Types</h3>
        <p class="text-[11px] text-slate-400 mt-0.5">Distribution by category</p>
      </div>
      <?php $hasTypeData = array_sum($typeStats) > 0; ?>
      <?php if ($hasTypeData): ?>
        <canvas id="typeChart" height="180"></canvas>
      <?php else: ?>
        <div class="flex flex-col items-center justify-center h-40 text-center">
          <p class="text-sm text-slate-400">No content yet</p>
          <a href="/admin/content/new" class="text-[11px] font-bold mt-2" style="color:#750B25">Create your first post &rarr;</a>
        </div>
      <?php endif; ?>
    </div>

  </div>

  <!-- ── Row 2: Top Content + Views by Country ────────────────────────────── -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">

    <!-- Top Performing Content -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
      <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
        <div>
          <h3 class="font-outfit font-bold text-[15px] text-slate-900">Top Performing Content</h3>
          <p class="text-[11px] text-slate-400 mt-0.5">Ranked by combined views + downloads</p>
        </div>
        <a href="/admin/content?status=PUBLISHED" class="text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-primary transition-colors">View All &rsaquo;</a>
      </div>
      <?php if (empty($topContent)): ?>
        <div class="flex flex-col items-center justify-center py-14 text-center px-6">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:rgba(99,102,241,.08)">
            <svg width="18" height="18" fill="none" stroke="#6366F1" stroke-width="1.8" stroke-linecap="round" viewBox="0 0 24 24">
              <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
          </div>
          <p class="text-sm text-slate-500">No views recorded yet</p>
          <p class="text-[11px] text-slate-400 mt-1">Published content will appear here once it receives views.</p>
        </div>
      <?php else:
        $maxEngagement = max(array_map(fn($r) => (int)$r['viewCount'] + (int)$r['downloadCount'], $topContent));
        $maxEngagement = max($maxEngagement, 1);
        $typeColors = ['ARTICLE'=>'#750B25','VIDEO'=>'#ED1C24','PODCAST'=>'#E7952A','DOCUMENT'=>'#6366F1','GALLERY_IMAGE'=>'#10B981','POLICY_BRIEF'=>'#8B5CF6'];
        $typeLabels = ['ARTICLE'=>'Article','VIDEO'=>'Video','PODCAST'=>'Podcast','DOCUMENT'=>'Doc','GALLERY_IMAGE'=>'Gallery','POLICY_BRIEF'=>'Brief'];
      ?>
        <div class="divide-y divide-slate-50">
          <?php foreach ($topContent as $rank => $tc):
            $engagement = (int)$tc['viewCount'] + (int)$tc['downloadCount'];
            $pct        = (int)round(($engagement / $maxEngagement) * 100);
            $tcol       = $typeColors[$tc['type']] ?? '#750B25';
            $tlabel     = $typeLabels[$tc['type']] ?? $tc['type'];
          ?>
            <div class="flex items-center gap-4 px-6 py-4 hover:bg-slate-50/60 transition-colors">
              <!-- Rank -->
              <span class="w-5 text-center text-[11px] font-black text-slate-300 shrink-0"><?= $rank + 1 ?></span>
              <!-- Type badge -->
              <span class="text-[8px] font-black uppercase tracking-wider px-2 py-1 rounded-lg shrink-0 text-white" style="background:<?= $tcol ?>"><?= $tlabel ?></span>
              <!-- Title + bar -->
              <div class="flex-1 min-w-0">
                <p class="text-[12px] font-semibold text-slate-800 truncate mb-1.5"><?= h($tc['title']) ?></p>
                <div class="flex items-center gap-2">
                  <div class="flex-1 h-1 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-700" style="width:<?= $pct ?>%;background:<?= $tcol ?>"></div>
                  </div>
                </div>
              </div>
              <!-- Country -->
              <span class="text-[9px] font-bold text-slate-400 shrink-0 hidden sm:block"><?= h($tc['country']) ?></span>
              <!-- Stats -->
              <div class="text-right shrink-0 space-y-0.5">
                <?php if ((int)$tc['viewCount'] > 0): ?>
                  <div class="flex items-center gap-1 justify-end text-[10px] text-slate-500">
                    <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="font-bold text-slate-700"><?= format_number((int)$tc['viewCount']) ?></span>
                  </div>
                <?php endif; ?>
                <?php if ((int)$tc['downloadCount'] > 0): ?>
                  <div class="flex items-center gap-1 justify-end text-[10px] text-slate-400">
                    <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span><?= format_number((int)$tc['downloadCount']) ?></span>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Views by Country -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
      <div class="mb-5">
        <h3 class="font-outfit font-bold text-[15px] text-slate-900">Views by Country</h3>
        <p class="text-[11px] text-slate-400 mt-0.5">Published content engagement</p>
      </div>
      <?php if (empty($viewsByCountry)): ?>
        <div class="flex flex-col items-center justify-center h-36 text-center">
          <p class="text-sm text-slate-400">No view data yet</p>
        </div>
      <?php else: ?>
        <div class="space-y-3">
          <?php foreach ($viewsByCountry as $cName => $cViews):
            $cpct = $viewsByCountryMax > 0 ? (int)round(($cViews / $viewsByCountryMax) * 100) : 0;
          ?>
            <div>
              <div class="flex items-center justify-between mb-1">
                <span class="text-[11px] font-semibold text-slate-700"><?= h($cName) ?></span>
                <span class="text-[11px] font-black text-slate-500"><?= format_number($cViews) ?></span>
              </div>
              <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-700" style="width:<?= $cpct ?>%;background:#750B25"></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="mt-5 pt-4 border-t border-slate-100">
          <p class="text-[10px] text-slate-400 uppercase tracking-widest">Total views across all countries</p>
          <p class="font-outfit font-black text-xl text-slate-900 mt-0.5"><?= format_number(array_sum($viewsByCountry)) ?></p>
        </div>
      <?php endif; ?>
    </div>

  </div>

  <!-- ── Row 3: Engagement Stats + Recent Comments (super admin) ────────── -->
  <?php if ($isSuper): ?>
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6 mb-2">

    <!-- Engagement Summary Cards -->
    <div class="grid grid-cols-1 gap-4 content-start">

      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
        <h3 class="font-outfit font-bold text-[15px] text-slate-900 mb-4">Engagement Overview</h3>
        <div class="space-y-4">
          <?php
          $avgViews = $publishedPosts > 0 ? round($totalViews / $publishedPosts, 1) : 0;
          $engagementItems = [
            ['label'=>'Avg. Views per Post',  'value'=> number_format($avgViews, 1), 'icon'=>'M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z M15 12a3 3 0 11-6 0 3 3 0 016 0z', 'col'=>'#6366F1'],
            ['label'=>'Total Downloads',       'value'=> format_number($totalDL),    'icon'=>'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4',                                                                                                                                                                                                                                        'col'=>'#10B981'],
            ['label'=>'Total Comments',        'value'=> format_number($totalComments),   'icon'=>'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z', 'col'=>'#F59E0B'],
            ['label'=>'Pending Moderation',    'value'=> $pendingComments,            'icon'=>'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',                                                                                                                                                                 'col'=>'#EF4444'],
          ];
          foreach ($engagementItems as $ei): ?>
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" style="background:<?= $ei['col'] ?>15">
                <svg width="14" height="14" fill="none" stroke="<?= $ei['col'] ?>" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="<?= $ei['icon'] ?>"/></svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-[10px] text-slate-400 uppercase tracking-widest"><?= h($ei['label']) ?></p>
                <p class="font-outfit font-black text-base text-slate-900"><?= h((string)$ei['value']) ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div>

    <!-- Recent Comments Pending Moderation -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
      <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
        <div>
          <h3 class="font-outfit font-bold text-[15px] text-slate-900">Comments Pending Moderation</h3>
          <p class="text-[11px] text-slate-400 mt-0.5"><?= $pendingComments ?> awaiting review</p>
        </div>
        <a href="/admin/super/moderation" class="text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-primary transition-colors">Moderate All &rsaquo;</a>
      </div>
      <?php if (empty($recentComments)): ?>
        <div class="flex flex-col items-center justify-center py-12 text-center px-6">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:rgba(16,185,129,.08)">
            <svg width="18" height="18" fill="none" stroke="#10B981" stroke-width="1.8" stroke-linecap="round" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <p class="text-sm font-semibold text-slate-700">All clear</p>
          <p class="text-[11px] text-slate-400 mt-1">No comments are awaiting moderation.</p>
        </div>
      <?php else: ?>
        <div class="divide-y divide-slate-50">
          <?php foreach ($recentComments as $cm): ?>
            <div class="px-6 py-4 hover:bg-slate-50/60 transition-colors">
              <div class="flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 mb-1 flex-wrap">
                    <span class="text-[11px] font-bold text-slate-800"><?= h($cm['name'] ?: 'Anonymous') ?></span>
                    <?php if ($cm['isFlagged']): ?>
                      <span class="text-[8px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full text-white" style="background:#EF4444">Flagged</span>
                    <?php endif; ?>
                    <span class="text-[10px] text-slate-400"><?= format_relative_time($cm['createdAt']) ?></span>
                  </div>
                  <p class="text-[12px] text-slate-600 line-clamp-2 mb-1.5"><?= h(mb_substr($cm['content'], 0, 120)) ?><?= mb_strlen($cm['content']) > 120 ? '…' : '' ?></p>
                  <p class="text-[10px] text-slate-400 truncate">On: <span class="text-slate-500"><?= h($cm['postTitle']) ?></span></p>
                </div>
                <a href="/admin/super/moderation"
                   class="shrink-0 text-[9px] font-black uppercase tracking-widest px-3 py-1.5 rounded-lg border hover:scale-105 transition-transform"
                   style="color:#750B25;border-color:rgba(117,11,37,.2)">Review</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>
  <?php endif; ?>

</main>
</div>
</div>

<script>
<?php if (array_sum($monthCounts) > 0): ?>
new Chart(document.getElementById('monthlyChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($monthLabels, JSON_UNESCAPED_UNICODE) ?>,
    datasets: [{
      label: 'Posts',
      data: <?= json_encode($monthCounts) ?>,
      backgroundColor: 'rgba(117,11,37,.85)',
      hoverBackgroundColor: '#ED1C24',
      borderRadius: 8,
      borderSkipped: false,
    }]
  },
  options: {
    plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ' ' + ctx.parsed.y + ' post' + (ctx.parsed.y !== 1 ? 's' : '') } } },
    scales: {
      y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: 'rgba(0,0,0,.04)' } },
      x: { ticks: { font: { size: 11 } }, grid: { display: false } }
    },
    responsive: true, maintainAspectRatio: true
  }
});
<?php else: ?>
var ctx = document.getElementById('monthlyChart').getContext('2d');
ctx.font = '13px Inter, sans-serif'; ctx.fillStyle = '#94a3b8'; ctx.textAlign = 'center';
ctx.fillText('No posts in the last 6 months', ctx.canvas.width / 2, 60);
<?php endif; ?>

<?php if ($hasTypeData): ?>
new Chart(document.getElementById('typeChart'), {
  type: 'doughnut',
  data: {
    labels: ['Article','Video','Podcast','Document','Gallery','Policy Brief'],
    datasets: [{
      data: [
        <?= $typeStats['ARTICLE'] ?>,
        <?= $typeStats['VIDEO'] ?>,
        <?= $typeStats['PODCAST'] ?>,
        <?= $typeStats['DOCUMENT'] ?>,
        <?= $typeStats['GALLERY_IMAGE'] ?>,
        <?= $typeStats['POLICY_BRIEF'] ?>
      ],
      backgroundColor: ['#750B25','#ED1C24','#E7952A','#6366F1','#10B981','#8B5CF6'],
      borderWidth: 0,
      hoverOffset: 8
    }]
  },
  options: {
    cutout: '68%',
    plugins: {
      legend: {
        position: 'bottom',
        labels: { boxWidth: 10, boxHeight: 10, padding: 16, font: { size: 11 }, color: '#64748b' }
      }
    },
    responsive: true, maintainAspectRatio: false
  }
});
<?php endif; ?>
</script>
</body>
</html>
