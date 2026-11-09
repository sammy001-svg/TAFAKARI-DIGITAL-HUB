<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/agent-articles.php';
require_once dirname(__DIR__) . '/includes/agent-videos.php';
require_once dirname(__DIR__) . '/includes/agent-nav.php';

$me      = require_auth();
$isSuper = is_super_admin();
$uid     = $me['id'];
ensure_settings_table();
ensure_agent_tables();
ensure_agent_video_table();

if (empty($_SESSION['agent_csrf'])) $_SESSION['agent_csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['agent_csrf'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = ''; $err = false;
    if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
        $msg = 'Your session expired. Please try again.'; $err = true;
    } else {
        try {
            $id = (string) ($_POST['video'] ?? '');
            if (($_POST['action'] ?? '') === 'approve')     { agent_video_approve($id, $uid); $msg = 'Video posted on the site.'; }
            elseif (($_POST['action'] ?? '') === 'reject')  { agent_video_reject($id, $uid);  $msg = 'Video rejected. It will not be suggested again.'; }
            else { $msg = 'Unknown action.'; $err = true; }
        } catch (Throwable $e) {
            error_log('[admin/agent-videos] ' . $e->getMessage());
            $msg = $e->getMessage(); $err = true;
        }
    }
    $_SESSION['video_flash'] = ['msg' => $msg, 'err' => $err];
    header('Location: /admin/agent/videos');
    exit;
}

$flash = $_SESSION['video_flash'] ?? null;
unset($_SESSION['video_flash']);

$tabs = ['PROPOSED' => 'To approve', 'PUBLISHED' => 'Posted', 'REJECTED' => 'Rejected'];
$tab  = isset($tabs[$_GET['status'] ?? '']) ? $_GET['status'] : 'PROPOSED';
$rows = [];
try {
    $q = db()->prepare('SELECT * FROM AgentVideo WHERE status = ? ORDER BY createdAt DESC LIMIT 60');
    $q->execute([$tab]);
    $rows = $q->fetchAll();
} catch (Throwable $e) {}

$pageTitle      = 'Videos | Tafakari Agent';
$adminPageTitle = 'Tafakari Agent';
$adminPageSub   = 'Videos found by the agent';
?>
<?php include dirname(__DIR__) . '/includes/head.php'; ?>
<body class="antialiased font-inter tk-admin" style="background:var(--tk-canvas)">
<div class="flex h-screen overflow-hidden">
<?php include dirname(__DIR__) . '/includes/admin-sidebar.php'; ?>
<div class="flex-1 flex flex-col overflow-hidden min-w-0">
<?php include dirname(__DIR__) . '/includes/admin-topbar.php'; ?>
<main class="flex-1 overflow-y-auto p-6 md:p-8">

<?php if ($flash): ?>
  <div class="tk-alert <?= $flash['err'] ? 'tk-alert--danger' : 'tk-alert--ok' ?>" role="status" style="margin-bottom:16px"><?= h($flash['msg']) ?></div>
<?php endif; ?>

<header class="tk-page-head">
  <div>
    <span class="tk-eyebrow">Monitoring</span>
    <h1 class="tk-h1" style="margin-top:12px">Videos</h1>
    <p class="tk-lead">YouTube videos the agent found on your regions and indicators. Watch them, then approve the ones to post on the site.</p>
  </div>
</header>

<?php agent_nav('videos', $isSuper); ?>

<div class="flex flex-wrap gap-4 mb-5 text-sm font-bold">
  <?php foreach ($tabs as $k => $l): ?>
    <a href="/admin/agent/videos?status=<?= h($k) ?>" style="color:<?= $k === $tab ? '#750B25' : '#64748b' ?>"<?= $k === $tab ? ' aria-current="true"' : '' ?>><?= h($l) ?></a>
  <?php endforeach; ?>
</div>

<?php if (!$rows): ?>
  <div class="tk-card tk-empty"><p class="text-sm font-semibold text-slate-600"><?= $tab === 'PROPOSED' ? 'No videos are waiting for approval.' : 'Nothing here yet.' ?></p></div>
<?php else: ?>
  <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5">
    <?php foreach ($rows as $v): $watch = 'https://www.youtube.com/watch?v=' . $v['videoId']; ?>
      <article class="tk-card" style="overflow:hidden;display:flex;flex-direction:column">
        <a href="<?= h($watch) ?>" target="_blank" rel="noopener noreferrer" style="display:block;aspect-ratio:16/9;background:#0D0102">
          <img src="https://img.youtube.com/vi/<?= h($v['videoId']) ?>/hqdefault.jpg" alt="Watch on YouTube: <?= h($v['title']) ?>" loading="lazy" style="width:100%;height:100%;object-fit:cover">
        </a>
        <div style="padding:16px 18px;display:flex;flex-direction:column;gap:8px;flex:1">
          <h2 class="font-outfit font-black text-base text-slate-900 leading-snug"><?= h($v['title']) ?></h2>
          <p class="text-xs font-bold" style="color:#750B25"><?= h($v['channel']) ?></p>
          <p class="text-xs text-slate-500"><?= h($v['regionName']) ?> · <?= h($v['category']) ?></p>
          <p class="text-sm text-slate-700 leading-relaxed"><?= h($v['summary']) ?></p>
          <div class="flex flex-wrap gap-2" style="margin-top:auto;padding-top:8px">
            <a href="<?= h($watch) ?>" target="_blank" rel="noopener noreferrer" class="tk-btn tk-btn--outline tk-btn--sm">Watch</a>
            <?php if ($v['status'] === 'PROPOSED'): ?>
              <form method="POST"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="video" value="<?= h($v['id']) ?>"><button name="action" value="approve" class="tk-btn tk-btn--primary tk-btn--sm">Approve and post</button></form>
              <form method="POST"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="video" value="<?= h($v['id']) ?>"><button name="action" value="reject" class="tk-btn tk-btn--danger tk-btn--sm">Reject</button></form>
            <?php elseif (!empty($v['postId'])): ?>
              <a href="/admin/content/<?= h(rawurlencode($v['postId'])) ?>/edit" class="tk-btn tk-btn--outline tk-btn--sm">Edit post</a>
            <?php endif; ?>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

</main>
</div>
</div>
</body>
</html>
