<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/agent-auto.php';
require_once dirname(__DIR__) . '/includes/intensity-scoring.php';
require_once dirname(__DIR__) . '/includes/agent-nav.php';

$me      = require_auth();
$isSuper = is_super_admin();
$uid     = $me['id'];
ensure_settings_table();
ensure_agent_tables();
ensure_agent_grade_table();

if (empty($_SESSION['agent_csrf'])) $_SESSION['agent_csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['agent_csrf'];

// ── Background grading, asked for by an admin page (agent_grade_beacon) ──
if (isset($_GET['due'])) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) { http_response_code(204); exit; }
    session_write_close();   // the admin's other pages must not wait for this one
    $last = agent_setting_fresh('agent_grade_beacon_at');
    if (($last !== '' && strtotime($last) > time() - 60) || agent_grade_blocked() !== null) { http_response_code(204); exit; }
    set_setting('agent_grade_beacon_at', date('Y-m-d H:i:s'));
    $lock = agent_auto_lock();
    if ($lock === false) { http_response_code(204); exit; }
    agent_respond_and_continue(null);
    try {
        $r = agent_grade_scheduled(1);
        if ($r !== null) agent_health_record($r);
    } catch (Throwable $e) {
        error_log('[admin/agent-grades] background grading: ' . $e->getMessage());
    }
    agent_auto_unlock($lock);
    exit;
}

$tabs = ['PROPOSED' => 'To review', 'NOT' => 'Not graded', 'ACCEPTED' => 'Accepted', 'REJECTED' => 'Rejected'];
$statusFor = ['PROPOSED' => ['PROPOSED'], 'NOT' => ['DECLINED', 'FAILED', 'QUEUED'], 'ACCEPTED' => ['ACCEPTED'], 'REJECTED' => ['REJECTED', 'SUPERSEDED']];

// ── Actions ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = ''; $err = false;
    $id  = (string) ($_POST['grade'] ?? '');
    $tab = isset($tabs[$_POST['tab'] ?? '']) ? (string) $_POST['tab'] : 'PROPOSED';
    $ret = '/admin/agent/grades' . ($tab !== 'PROPOSED' ? '?status=' . rawurlencode($tab) : '');
    if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
        $msg = 'Your session expired. Please try again.'; $err = true;
    } else {
        try {
            switch ((string) ($_POST['action'] ?? '')) {
                case 'accept':
                    agent_grade_accept($id, $uid);
                    $msg = 'Grades accepted. They now count on the heat map.';
                    break;
                case 'adjust':
                    $ret = '/admin/agent/grades/' . rawurlencode($id);
                    $src = agent_grade_accept($id, $uid, agent_grade_scores_from_post($_POST), source_count_from_post($_POST));
                    $ret = '/admin/agent/grades';
                    $msg = $src === 'AI' ? 'Grades accepted as proposed. They now count on the heat map.' : 'Your adjusted grades are saved. They now count on the heat map.';
                    break;
                case 'reject':
                    agent_grade_reject($id, $uid, (string) ($_POST['notes'] ?? ''));
                    $msg = 'Grades rejected. The document has no scores: you can score it by hand on its edit page.';
                    break;
                case 'regrade':
                    agent_grade_requeue($id);
                    $msg = 'The AI will grade this document again within a few minutes.';
                    break;
                default:
                    $msg = 'Unknown action.'; $err = true;
            }
        } catch (Throwable $e) {
            error_log('[admin/agent-grades] ' . $e->getMessage());
            $msg = $e->getMessage(); $err = true;
        }
    }
    $_SESSION['grade_flash'] = ['msg' => $msg, 'err' => $err];
    header('Location: ' . $ret);
    exit;
}

$flash = $_SESSION['grade_flash'] ?? null;
unset($_SESSION['grade_flash']);

agent_grade_set_aside_scored();
$taxonomy = heatmap_taxonomy();
$catColor = [];
foreach ($taxonomy as $c) $catColor[$c['name']] = $c['color'];
$elementCat = heatmap_element_index();

/** Average of a set of element scores, or null. */
$avg = fn(array $s) => $s ? round(array_sum($s) / count($s), 1) : null;

/** Scores grouped by heat-map category, in the heat map's order. */
$byCategory = function (array $scores) use ($taxonomy): array {
    $out = [];
    foreach ($taxonomy as $c) {
        $els = array_intersect_key($scores, array_flip($c['elements']));
        if ($els) $out[$c['name']] = $els;
    }
    return $out;
};

$detail = null;
if (($_GET['id'] ?? '') !== '') {
    $detail = agent_grade_get((string) $_GET['id']);
    if (!$detail || $detail['title'] === null) {
        $_SESSION['grade_flash'] = ['msg' => 'Those grades no longer exist.', 'err' => true];
        header('Location: /admin/agent/grades');
        exit;
    }
}

$tab = isset($tabs[$_GET['status'] ?? '']) ? (string) $_GET['status'] : 'PROPOSED';
$rows = [];
$counts = array_fill_keys(array_keys($tabs), 0);
if (!$detail) {
    try {
        foreach (db()->query('SELECT g.status, COUNT(*) AS n FROM AgentGrade g JOIN Post p ON p.id = g.postId GROUP BY g.status')->fetchAll() as $r) {
            foreach ($statusFor as $t => $sts) if (in_array($r['status'], $sts, true)) $counts[$t] += (int) $r['n'];
        }
        $sts = $statusFor[$tab];
        $q = db()->prepare('SELECT g.*, p.title, p.country, p.region, p.additionalCountries, p.issueCategory, p.createdAt AS postCreatedAt, p.status AS postStatus,
                                   p.intensityScores, u.name AS reviewerName
                            FROM AgentGrade g JOIN Post p ON p.id = g.postId LEFT JOIN User u ON u.id = g.reviewedBy
                            WHERE g.status IN (' . implode(',', array_fill(0, count($sts), '?')) . ')
                            ORDER BY ' . ($tab === 'PROPOSED' ? 'p.createdAt DESC' : 'g.updatedAt DESC') . ' LIMIT 60');
        $q->execute($sts);
        $rows = array_map('agent_grade_decode', $q->fetchAll());
    } catch (Throwable $e) {
        error_log('[admin/agent-grades] ' . $e->getMessage());
    }
}
$blocked = agent_grade_blocked();
$backlog = agent_grade_backlog();

$pageTitle      = 'Document grades | Tafakari Agent';
$adminPageTitle = 'Tafakari Agent';
$adminPageSub   = 'Intensity grades proposed for posted documents';
$statusLabel = ['DRAFT' => 'Draft', 'PENDING' => 'Awaiting approval', 'PUBLISHED' => 'Published', 'REJECTED' => 'Rejected', 'ARCHIVED' => 'Archived'];

/** One document's heading: title, place, date, status and categories. */
$docHead = function (array $g) use ($statusLabel): void { ?>
  <h2 class="font-outfit font-black text-base text-slate-900 leading-snug">
    <a href="/admin/content/<?= h(rawurlencode((string) $g['postId'])) ?>/edit" class="hover:underline"><?= h((string) $g['title']) ?></a>
  </h2>
  <p class="text-xs text-slate-500 mt-1">
    <?= h(implode(', ', post_countries($g))) ?><?= trim((string) $g['region']) !== '' ? ' · ' . h((string) $g['region']) : '' ?>
    · posted <?= h(format_date((string) $g['postCreatedAt'], 'j M Y')) ?> · <?= h($statusLabel[$g['postStatus']] ?? (string) $g['postStatus']) ?>
  </p>
  <div class="flex flex-wrap gap-1 mt-2"><?= issue_category_pills($g, 'text-[10px] font-bold px-2 py-0.5 rounded-full', 'background:rgba(117,11,37,.08);color:#750B25') ?></div>
<?php };

/** The scores, by category, each with the AI's reason. */
$scoreTable = function (array $scores, array $reasons, ?array $proposed = null) use ($byCategory, $catColor, $avg): void { ?>
  <div class="gr-scores">
    <?php foreach ($byCategory($scores) as $cat => $els): $col = $catColor[$cat] ?? '#64748b'; $a = $avg($els); ?>
      <div class="gr-cat" style="border-left-color:<?= h($col) ?>">
        <p class="gr-cat__name"><span style="color:<?= h($col) ?>"><?= h($cat) ?></span> <span class="gr-cat__avg"><?= h(number_format((float) $a, 1)) ?> / 10</span></p>
        <?php foreach ($els as $el => $v): $tc = hm_tier_color((float) $v); ?>
          <div class="gr-row">
            <span class="gr-score" style="background:<?= h($tc) ?>1f;color:<?= h($tc) ?>" title="<?= h(hm_tier_label((float) $v)) ?>"><?= (int) $v ?></span>
            <div class="min-w-0">
              <p class="text-sm font-bold text-slate-800"><?= h($el) ?>
                <?php if ($proposed !== null && isset($proposed[$el]) && (int) $proposed[$el] !== (int) $v): ?><span class="text-xs font-semibold text-slate-400">(AI proposed <?= (int) $proposed[$el] ?>)</span><?php endif; ?>
                <?php if ($proposed !== null && !isset($proposed[$el])): ?><span class="text-xs font-semibold text-slate-400">(added by the reviewer)</span><?php endif; ?>
              </p>
              <?php if (($reasons[$el] ?? '') !== ''): ?><p class="text-xs text-slate-600 leading-relaxed"><?= h($reasons[$el]) ?></p><?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php };
?>
<?php include dirname(__DIR__) . '/includes/head.php'; ?>
<body class="antialiased font-inter tk-admin" style="background:var(--tk-canvas)">
<div class="flex h-screen overflow-hidden">
<?php include dirname(__DIR__) . '/includes/admin-sidebar.php'; ?>
<div class="flex-1 flex flex-col overflow-hidden min-w-0">
<?php include dirname(__DIR__) . '/includes/admin-topbar.php'; ?>
<main class="flex-1 overflow-y-auto p-6 md:p-8">
<style>
.gr-chip { display:inline-flex; align-items:center; gap:6px; font-size:11px; font-weight:800; padding:3px 10px; border-radius:999px; white-space:nowrap; }
.gr-scores { display:flex; flex-direction:column; gap:10px; margin-top:14px; }
.gr-cat { border-left:4px solid #94a3b8; padding:4px 0 4px 12px; display:flex; flex-direction:column; gap:8px; }
.gr-cat__name { font-size:12px; font-weight:800; }
.gr-cat__avg { color:#64748b; font-weight:700; margin-left:4px; }
.gr-row { display:flex; gap:10px; align-items:flex-start; }
.gr-score { flex-shrink:0; width:30px; height:30px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:13px; }
.gr-actions { display:flex; flex-wrap:wrap; gap:8px; align-items:center; margin-top:16px; padding-top:14px; border-top:1px solid var(--tk-line, #e2e8f0); }
.gr-actions input[type=text] { flex:1; min-width:180px; padding:7px 10px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:12px; background:#f8fafc; }
</style>

<?php if ($flash): ?>
  <div class="tk-alert <?= $flash['err'] ? 'tk-alert--danger' : 'tk-alert--ok' ?>" role="status" style="margin-bottom:16px"><?= h($flash['msg']) ?></div>
<?php endif; ?>

<header class="tk-page-head">
  <div>
    <span class="tk-eyebrow">Monitoring</span>
    <h1 class="tk-h1" style="margin-top:12px">Document grades</h1>
    <p class="tk-lead">The AI reads each document about five minutes after it is posted without intensity scores, and proposes a score for each element it reports on. The scores count on the heat map only once you accept them.</p>
  </div>
</header>

<?php agent_nav('grades', $isSuper); ?>

<?php if ($detail): $g = $detail; $open = $g['status'] === 'PROPOSED'; ?>
  <p class="text-xs font-bold mb-4"><a href="/admin/agent/grades" style="color:#750B25">&larr; All document grades</a></p>
  <div class="grid xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2">
      <?php if ($open): ?>
        <form method="POST" class="space-y-4">
          <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
          <input type="hidden" name="grade" value="<?= h((string) $g['id']) ?>">
          <input type="hidden" name="action" value="adjust">
          <?php intensity_scoring_fields($g['scores'], max(1, (int) ($g['sourceCount'] ?? 1)), ['source' => 'AI']); ?>
          <div class="tk-card tk-card--pad flex flex-wrap items-center gap-3">
            <button type="submit" class="tk-btn tk-btn--primary">Save and accept</button>
            <a href="/admin/agent/grades" class="tk-btn tk-btn--outline">Cancel</a>
            <span class="text-xs text-slate-500">The sliders start at the AI's grades. Change any you disagree with, then save: the scores count on the heat map straight away.</span>
          </div>
        </form>
      <?php else: ?>
        <div class="tk-card tk-card--pad">
          <?php $stateText = ['ACCEPTED' => 'These grades were accepted', 'REJECTED' => 'These grades were rejected', 'SUPERSEDED' => 'These grades were set aside: the document was scored by hand',
                              'DECLINED' => 'This document was not graded', 'FAILED' => 'Grading this document failed', 'QUEUED' => 'This document is waiting to be graded again']; ?>
          <p class="text-sm text-slate-700"><?= h($stateText[$g['status']] ?? $g['status']) ?><?= !empty($g['reviewerName']) ? ' (' . h((string) $g['reviewerName']) . ', ' . h(format_date((string) $g['reviewedAt'], 'j M Y')) . ')' : '' ?>.
            <?= in_array($g['status'], ['DECLINED', 'FAILED'], true) && ($g['notes'] ?? '') !== '' ? h((string) $g['notes']) : '' ?></p>
          <?php if ($g['finalScores']) $scoreTable($g['finalScores'], $g['reasons'], $g['scores']); elseif ($g['scores']) $scoreTable($g['scores'], $g['reasons']); ?>
        </div>
      <?php endif; ?>
    </div>
    <aside class="space-y-4">
      <div class="tk-card tk-card--pad">
        <?php $docHead($g); ?>
        <?php if (($g['summary'] ?? '') !== ''): ?><p class="text-sm text-slate-700 mt-3 leading-relaxed"><?= h((string) $g['summary']) ?></p><?php endif; ?>
        <p class="text-xs text-slate-500 mt-3"><strong>Read:</strong> <?= h((string) $g['basis'] ?: 'the title only') ?>.</p>
        <?php if (($g['confidence'] ?? '') !== ''): ?><p class="text-xs text-slate-500 mt-1"><strong>Confidence:</strong> <?= h(ucfirst((string) $g['confidence'])) ?></p><?php endif; ?>
        <?php if (($g['notes'] ?? '') !== '' && $g['status'] !== 'DECLINED'): ?><p class="text-xs text-slate-500 mt-1"><strong>Notes:</strong> <?= h((string) $g['notes']) ?></p><?php endif; ?>
        <?php if (!empty($g['mediaUrl'])): ?><p class="text-xs mt-3"><a href="<?= h((string) $g['mediaUrl']) ?>" target="_blank" rel="noopener" class="font-bold underline" style="color:#750B25">Open the document file</a></p><?php endif; ?>
      </div>
      <?php if ($open && $g['scores']): ?>
        <div class="tk-card tk-card--pad">
          <h2 class="font-outfit font-black text-sm text-slate-900">The AI's grades and reasons</h2>
          <?php $scoreTable($g['scores'], $g['reasons']); ?>
        </div>
      <?php endif; ?>
    </aside>
  </div>

<?php else: ?>
  <?php if ($blocked !== null): ?>
    <div class="tk-alert tk-alert--warn" role="status" style="margin-bottom:16px"><span><strong>Documents are not being graded at the moment.</strong> <?= h($blocked) ?>
      <?= $isSuper ? '<a href="/admin/agent?tab=settings" class="font-bold underline">Settings</a>' : 'A super admin can fix this under Tafakari Agent &gt; Settings.' ?></span></div>
  <?php elseif ($backlog['waiting'] > 0): ?>
    <p class="text-sm text-slate-600 mb-4"><?= (int) $backlog['waiting'] ?> document<?= $backlog['waiting'] === 1 ? ' is' : 's are' ?> waiting to be graded.
      New documents are graded about five minutes after they are posted; older ones up to <?= AGENT_GRADE_OLD_PER_DAY ?> a day, newest first.</p>
  <?php endif; ?>

  <div class="flex flex-wrap gap-4 mb-5 text-sm font-bold">
    <?php foreach ($tabs as $k => $l): ?>
      <a href="/admin/agent/grades?status=<?= h($k) ?>" style="color:<?= $k === $tab ? '#750B25' : '#64748b' ?>"<?= $k === $tab ? ' aria-current="true"' : '' ?>><?= h($l) ?><?= $counts[$k] ? ' (' . $counts[$k] . ')' : '' ?></a>
    <?php endforeach; ?>
  </div>

  <?php if (!$rows): ?>
    <div class="tk-card tk-empty"><p class="text-sm font-semibold text-slate-600"><?= $tab === 'PROPOSED' ? 'No grades are waiting for review.' : 'Nothing here yet.' ?></p></div>
  <?php else: ?>
    <div class="grid xl:grid-cols-2 gap-5">
      <?php foreach ($rows as $g): $shown = $g['finalScores'] ?: $g['scores']; $a = $avg($shown); ?>
        <article class="tk-card tk-card--pad">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0"><?php $docHead($g); ?></div>
            <?php if ($a !== null): $tc = hm_tier_color($a); ?>
              <span class="gr-chip" style="background:<?= h($tc) ?>1f;color:<?= h($tc) ?>"><?= h(number_format($a, 1)) ?> / 10 · <?= h(hm_tier_label($a)) ?></span>
            <?php endif; ?>
          </div>

          <?php if (in_array($g['status'], ['DECLINED', 'FAILED', 'QUEUED'], true)): ?>
            <p class="text-sm mt-3 leading-relaxed" style="color:<?= $g['status'] === 'FAILED' ? '#B91C1C' : '#334155' ?>">
              <?= $g['status'] === 'QUEUED' ? 'Waiting to be graded again.' : h((string) ($g['notes'] ?: 'Not graded.')) ?></p>
            <?php if ($g['status'] === 'FAILED'): ?><p class="text-xs text-slate-500 mt-1"><?= (int) $g['attempts'] < AGENT_GRADE_MAX_ATTEMPTS ? 'It will be tried again by itself.' : 'It failed ' . (int) $g['attempts'] . ' times, so it waits for you.' ?></p><?php endif; ?>
            <div class="gr-actions">
              <a href="/admin/content/<?= h(rawurlencode((string) $g['postId'])) ?>/edit" class="tk-btn tk-btn--outline tk-btn--sm">Score by hand</a>
              <?php if ($g['status'] !== 'QUEUED'): ?>
                <form method="POST"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="grade" value="<?= h((string) $g['id']) ?>"><input type="hidden" name="tab" value="<?= h($tab) ?>">
                  <button name="action" value="regrade" class="tk-btn tk-btn--outline tk-btn--sm">Grade again</button></form>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <?php if (($g['summary'] ?? '') !== ''): ?><p class="text-sm text-slate-700 mt-3 leading-relaxed"><?= h((string) $g['summary']) ?></p><?php endif; ?>
            <?php $scoreTable($shown, $g['reasons'], $g['finalScores'] ? $g['scores'] : null); ?>
            <p class="text-xs text-slate-400 mt-3">Read: <?= h((string) $g['basis'] ?: 'the title only') ?>.<?= ($g['confidence'] ?? '') !== '' ? ' Confidence: ' . h((string) $g['confidence']) . '.' : '' ?></p>
            <?php if ($g['status'] === 'PROPOSED'): ?>
              <div class="gr-actions">
                <form method="POST"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="grade" value="<?= h((string) $g['id']) ?>">
                  <button name="action" value="accept" class="tk-btn tk-btn--primary tk-btn--sm">Accept grades</button></form>
                <a href="/admin/agent/grades/<?= h(rawurlencode((string) $g['id'])) ?>" class="tk-btn tk-btn--outline tk-btn--sm">Adjust</a>
                <form method="POST" class="flex flex-wrap gap-2 items-center" style="flex:1">
                  <input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="grade" value="<?= h((string) $g['id']) ?>">
                  <input type="text" name="notes" maxlength="1000" placeholder="Why reject? (helps the AI learn)" aria-label="Reason for rejecting">
                  <button name="action" value="reject" class="tk-btn tk-btn--danger tk-btn--sm">Reject</button>
                </form>
              </div>
            <?php else: ?>
              <p class="text-xs text-slate-500 mt-2"><?= h($g['status'] === 'SUPERSEDED' ? 'Set aside: the document was scored by hand.' : ($g['status'] === 'ACCEPTED' ? 'Accepted' : 'Rejected') . (!empty($g['reviewerName']) ? ' by ' . $g['reviewerName'] : '') . (!empty($g['reviewedAt']) ? ', ' . format_date((string) $g['reviewedAt'], 'j M Y') : '') . '.') ?>
                <?= ($g['reviewNotes'] ?? '') !== '' && $g['reviewNotes'] !== null ? ' ' . h((string) $g['reviewNotes']) : '' ?></p>
            <?php endif; ?>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

</main>
</div>
</div>
<?php if ($detail && $detail['status'] === 'PROPOSED') intensity_scoring_scripts(); ?>
</body>
</html>
