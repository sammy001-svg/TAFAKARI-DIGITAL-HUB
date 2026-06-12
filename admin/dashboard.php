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

$whereOnly = $isSuper ? '1' : "authorId = '$uid'";

$totalPosts     = (int)$pdo->query("SELECT COUNT(*) FROM Post WHERE $whereOnly")->fetchColumn();
$publishedPosts = (int)$pdo->query("SELECT COUNT(*) FROM Post WHERE $whereOnly AND status='PUBLISHED'")->fetchColumn();
$pendingPosts   = (int)$pdo->query("SELECT COUNT(*) FROM Post WHERE $whereOnly AND status='PENDING'")->fetchColumn();
$draftPosts     = (int)$pdo->query("SELECT COUNT(*) FROM Post WHERE $whereOnly AND status='DRAFT'")->fetchColumn();

$viewsRow   = $pdo->query("SELECT COALESCE(SUM(viewCount),0) AS v, COALESCE(SUM(downloadCount),0) AS d FROM Post WHERE $whereOnly")->fetch();
$totalViews = (int)$viewsRow['v'];
$totalDL    = (int)$viewsRow['d'];

$flagged    = (int)$pdo->query("SELECT COUNT(*) FROM Comment WHERE isFlagged=1")->fetchColumn();
$totalUsers = $isSuper ? (int)$pdo->query("SELECT COUNT(*) FROM User")->fetchColumn() : 0;

$recentPosts = $pdo->query(
    "SELECT p.id, p.title, p.type, p.status, p.country, p.issueCategory, p.updatedAt,
            u.name AS authorName, u.username AS authorUsername
     FROM Post p LEFT JOIN User u ON p.authorId = u.id
     WHERE $whereOnly
     ORDER BY p.updatedAt DESC LIMIT 6"
)->fetchAll();

$hour = (int)date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

$pageTitle    = 'Dashboard Overview | Tafakari Admin';
$adminPageTitle = 'Dashboard Overview';
$adminPageSub   = $greeting . ', ' . ($user['name'] ?? $user['username']) . '. Here\'s what\'s happening.';
?>
<?php include dirname(__DIR__) . '/includes/head.php'; ?>

<style>
  .kpi-icon { transition: transform .2s; }
  .kpi-card:hover .kpi-icon { transform: scale(1.08); }
  .activity-row:hover { background: rgba(154,20,21,.025); }
</style>

<body class="antialiased font-inter" style="background:#F4F6F8">
<div class="flex h-screen overflow-hidden">
<?php include dirname(__DIR__) . '/includes/admin-sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden min-w-0">
<?php include dirname(__DIR__) . '/includes/admin-topbar.php'; ?>

<main class="flex-1 overflow-y-auto p-6 md:p-8">

  <!-- Welcome Banner -->
  <div class="rounded-2xl p-6 md:p-8 mb-8 flex flex-col md:flex-row items-start md:items-center justify-between gap-4"
       style="background:linear-gradient(115deg,#0D0102 0%,#3B0708 60%,#9A1415 100%)">
    <div>
      <p class="text-[11px] font-black uppercase tracking-[.15em] mb-1" style="color:#D99F51"><?= $greeting ?></p>
      <h2 class="font-outfit font-black text-2xl text-white leading-tight">
        <?= h($user['name'] ?? $user['username']) ?>
      </h2>
      <p class="text-white/50 text-sm mt-1">
        <?php if ($isSuper): ?>
          You have full administrative authority over this platform.
        <?php else: ?>
          You have <?= $totalPosts ?> content item<?= $totalPosts !== 1 ? 's' : '' ?> in your workspace.
        <?php endif; ?>
      </p>
    </div>
    <div class="flex items-center gap-3">
      <?php if ($isSuper && $pendingPosts > 0): ?>
        <a href="/admin/super/approvals"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-[12px] font-bold text-white border border-white/20 hover:bg-white/10 transition-colors">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
          <?= $pendingPosts ?> Pending
        </a>
      <?php endif; ?>
      <a href="/admin/content/new"
         class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-[12px] font-bold text-[#0D0102]"
         style="background:#D99F51">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
        Create Content
      </a>
    </div>
  </div>

  <!-- KPI Cards -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <?php
    $kpis = [
      [
        'label'  => 'Published',
        'value'  => format_number($publishedPosts),
        'meta'   => 'of ' . $totalPosts . ' total',
        'icon'   => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
        'ic_bg'  => 'rgba(16,185,129,.1)',
        'ic_col' => '#10B981',
        'trend'  => $publishedPosts > 0 ? 'Live' : 'None',
        'trendcls' => $publishedPosts > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500',
      ],
      [
        'label'  => 'Pending Review',
        'value'  => $pendingPosts,
        'meta'   => $pendingPosts > 0 ? 'Needs attention' : 'Queue clear',
        'icon'   => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'ic_bg'  => 'rgba(245,158,11,.1)',
        'ic_col' => '#F59E0B',
        'trend'  => $pendingPosts > 0 ? '!' : '✓',
        'trendcls' => $pendingPosts > 0 ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700',
      ],
      [
        'label'  => 'Total Views',
        'value'  => format_number($totalViews),
        'meta'   => format_number($totalDL) . ' downloads',
        'icon'   => 'M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        'ic_bg'  => 'rgba(99,102,241,.1)',
        'ic_col' => '#6366F1',
        'trend'  => 'Impressions',
        'trendcls' => 'bg-indigo-50 text-indigo-700',
      ],
      [
        'label'  => 'Flagged Comments',
        'value'  => $flagged,
        'meta'   => $flagged > 0 ? 'Needs moderation' : 'All clear',
        'icon'   => 'M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9',
        'ic_bg'  => 'rgba(239,68,68,.1)',
        'ic_col' => '#EF4444',
        'trend'  => $flagged > 0 ? 'Review' : 'Clean',
        'trendcls' => $flagged > 0 ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700',
      ],
    ];
    foreach ($kpis as $k): ?>
      <div class="kpi-card bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex flex-col gap-4">
        <div class="flex items-start justify-between">
          <div class="kpi-icon w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
               style="background:<?= $k['ic_bg'] ?>">
            <svg width="18" height="18" fill="none" stroke="<?= $k['ic_col'] ?>" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
              <path d="<?= $k['icon'] ?>"/>
            </svg>
          </div>
          <span class="text-[9px] font-black uppercase tracking-wide px-2 py-1 rounded-full <?= $k['trendcls'] ?>">
            <?= h($k['trend']) ?>
          </span>
        </div>
        <div>
          <div class="font-outfit font-black text-3xl text-slate-900 leading-none mb-1"><?= h((string)$k['value']) ?></div>
          <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-widest"><?= h($k['label']) ?></p>
          <p class="text-[10px] text-slate-400 mt-0.5"><?= h($k['meta']) ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Secondary Stats (Super Admin) -->
  <?php if ($isSuper): ?>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-8">
    <?php
    $breakdown = [
      ['label'=>'Draft',     'value'=>$draftPosts,     'href'=>'/admin/content?status=DRAFT',     'bg'=>'bg-slate-50',    'border'=>'border-slate-200',  'txt'=>'text-slate-700',  'num'=>'text-slate-900'],
      ['label'=>'Pending',   'value'=>$pendingPosts,   'href'=>'/admin/content?status=PENDING',   'bg'=>'bg-amber-50',    'border'=>'border-amber-200',  'txt'=>'text-amber-700',  'num'=>'text-amber-900'],
      ['label'=>'Published', 'value'=>$publishedPosts, 'href'=>'/admin/content?status=PUBLISHED', 'bg'=>'bg-emerald-50',  'border'=>'border-emerald-200','txt'=>'text-emerald-700','num'=>'text-emerald-900'],
      ['label'=>'Users',     'value'=>$totalUsers,     'href'=>'/admin/super/users',              'bg'=>'bg-indigo-50',   'border'=>'border-indigo-200', 'txt'=>'text-indigo-700', 'num'=>'text-indigo-900'],
    ];
    foreach ($breakdown as $b): ?>
      <a href="<?= h($b['href']) ?>"
         class="flex items-center justify-between px-4 py-3.5 rounded-xl border text-center hover:scale-[1.02] transition-transform <?= $b['bg'] ?> <?= $b['border'] ?>">
        <span class="text-[10px] font-black uppercase tracking-widest <?= $b['txt'] ?>"><?= h($b['label']) ?></span>
        <span class="font-outfit font-black text-xl <?= $b['num'] ?>"><?= $b['value'] ?></span>
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Main Content Grid -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

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
          <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-4" style="background:rgba(154,20,21,.06)">
            <svg width="22" height="22" fill="none" stroke="#9A1415" stroke-width="1.6" stroke-linecap="round" viewBox="0 0 24 24">
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
            'ARTICLE'       => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            'GALLERY_IMAGE' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
            'PODCAST'       => 'M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z',
            'VIDEO'         => 'M15 10l4.553-2.069A1 1 0 0121 8.87v6.26a1 1 0 01-1.447.9L15 14M3 8a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z',
            'DOCUMENT'      => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
          ];
          foreach ($recentPosts as $p):
            $author = $p['authorName'] ?? $p['authorUsername'] ?? '—';
            $svg    = $typeIcons[$p['type']] ?? $typeIcons['ARTICLE'];
          ?>
            <div class="activity-row flex items-center gap-4 px-6 py-4 transition-colors">
              <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                   style="background:rgba(154,20,21,.06)">
                <svg width="16" height="16" fill="none" stroke="#9A1415" stroke-width="1.8"
                     stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <path d="<?= $svg ?>"/>
                </svg>
              </div>
              <div class="flex-grow min-w-0">
                <a href="/admin/content/<?= h($p['id']) ?>/edit"
                   class="text-[13px] font-semibold text-slate-800 truncate block hover:text-primary transition-colors">
                  <?= h($p['title']) ?>
                </a>
                <p class="text-[10px] text-slate-400 mt-0.5 truncate">
                  <?= h($p['country']) ?> &bull; <?= h($p['issueCategory']) ?>
                  <?php if ($isSuper): ?> &bull; <?= h($author) ?><?php endif; ?>
                  &bull; <?= format_relative_time($p['updatedAt']) ?>
                </p>
              </div>
              <?= status_badge($p['status']) ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Right Column -->
    <div class="space-y-4">

      <!-- Quick Actions -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
        <h3 class="font-outfit font-bold text-[15px] text-slate-900 mb-4">Quick Actions</h3>
        <div class="space-y-2">
          <?php
          $actions = [
            ['href'=>'/admin/content/new',          'label'=>'Create New Content',    'sub'=>'Start drafting',     'svg'=>'M12 4v16m8-8H4'],
            ['href'=>'/admin/content',              'label'=>'Manage Content',        'sub'=>'View all posts',     'svg'=>'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
            ['href'=>'/admin/profile',              'label'=>'Edit Profile',          'sub'=>'Update your info',   'svg'=>'M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
          ];
          if ($isSuper) {
              $actions[] = ['href'=>'/admin/super/users', 'label'=>'Manage Users', 'sub'=>'Add or edit accounts', 'svg'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'];
          }
          foreach ($actions as $a): ?>
            <a href="<?= h($a['href']) ?>"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-50 transition-colors group">
              <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" style="background:rgba(154,20,21,.06)">
                <svg width="14" height="14" fill="none" stroke="#9A1415" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <path d="<?= $a['svg'] ?>"/>
                </svg>
              </div>
              <div class="flex-grow min-w-0">
                <p class="text-[12px] font-semibold text-slate-800 group-hover:text-primary transition-colors"><?= h($a['label']) ?></p>
                <p class="text-[10px] text-slate-400"><?= h($a['sub']) ?></p>
              </div>
              <svg width="12" height="12" fill="none" stroke="#CBD5E1" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24">
                <path d="M9 18l6-6-6-6"/>
              </svg>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- System Status -->
      <div class="rounded-2xl p-5 border" style="background:#0D0102;border-color:rgba(255,255,255,.06)">
        <h3 class="font-outfit font-bold text-[14px] text-white mb-4">System Status</h3>
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-[11px] text-white/50">Database</span>
            <span class="flex items-center gap-1.5 text-[11px] font-semibold text-emerald-400">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
              Operational
            </span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-[11px] text-white/50">Content API</span>
            <span class="flex items-center gap-1.5 text-[11px] font-semibold text-emerald-400">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
              Operational
            </span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-[11px] text-white/50">Session</span>
            <span class="flex items-center gap-1.5 text-[11px] font-semibold text-emerald-400">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
              Active
            </span>
          </div>
        </div>
        <?php if ($isSuper && $pendingPosts > 0): ?>
          <div class="mt-4 pt-4 border-t" style="border-color:rgba(255,255,255,.06)">
            <a href="/admin/super/approvals"
               class="flex items-center gap-2 text-[11px] font-bold hover:opacity-80 transition-opacity"
               style="color:#D99F51">
              <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
              <?= $pendingPosts ?> post<?= $pendingPosts !== 1 ? 's' : '' ?> awaiting approval
            </a>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>

</main>
</div>
</div>
</body>
</html>
