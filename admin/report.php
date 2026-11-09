<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/reports.php';
require_once dirname(__DIR__) . '/includes/charts.php';
require_once dirname(__DIR__) . '/includes/report-render.php';

$me      = require_auth();
$isSuper = is_super_admin();
$uid     = $me['id'];
ensure_settings_table();
ensure_report_tables();

$id = (string) ($_GET['id'] ?? '');
$report = $id !== '' ? report_get($id) : null;
if (!$report) { header('Location: /admin/reports'); exit; }

if (($_GET['format'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="tafakari-report-' . $report['periodStart'] . '.csv"');
    echo report_csv($report);
    exit;
}

if (empty($_SESSION['report_csrf'])) $_SESSION['report_csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['report_csrf'];

$back = function (string $msg, bool $err = false) use ($id): never {
    $_SESSION['report_flash'] = $msg;
    $_SESSION['report_flash_err'] = $err;
    header('Location: /admin/reports/' . rawurlencode($id));
    exit;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) $back('Your session expired. Please try again.', true);
    $action = (string) ($_POST['action'] ?? '');
    try {
        switch ($action) {
            case 'save':
                report_save_narrative($id, (string) ($_POST['title'] ?? ''), (string) ($_POST['narrative'] ?? ''));
                $back('Saved.');
            case 'refresh':
                report_refresh($id);
                $back('Data refreshed from the latest published reports.');
            case 'draft_ai':
                // Save any typing first, so asking for a draft never loses work
                if (isset($_POST['narrative']) && trim((string) $_POST['narrative']) !== '') {
                    $back('Clear the narrative box first: the AI draft would replace your text.', true);
                }
                @set_time_limit(300);
                ignore_user_abort(true);
                session_write_close();
                $r = report_draft_narrative($id, $uid);
                session_start();
                $back($r['message'], !$r['ok']);
            case 'submit': case 'publish': case 'unpublish':
                report_transition($id, $action, $uid, $isSuper);
                $back(['submit' => 'Submitted for approval.', 'publish' => 'Published. It is now on the public Reports page.',
                       'unpublish' => 'Returned to draft and removed from the public site.'][$action]);
        }
        $back('Unknown action.', true);
    } catch (Throwable $e) {
        error_log('[admin/report] ' . $action . ': ' . $e->getMessage());
        $back($e->getMessage(), true);
    }
}

$flash    = $_SESSION['report_flash'] ?? null;
$flashErr = $_SESSION['report_flash_err'] ?? false;
unset($_SESSION['report_flash'], $_SESSION['report_flash_err']);

$locked = $report['status'] === 'PUBLISHED';
$snap   = $report['snapshotData'];
$badge  = ['DRAFT' => ['#f1f5f9', '#475569', 'Draft'], 'PENDING' => ['#E7952A22', '#9a5b0f', 'Awaiting approval'], 'PUBLISHED' => ['#05966915', '#047857', 'Published']][$report['status']];

$pageTitle      = $report['title'] . ' | Tafakari Admin';
$adminPageTitle = 'Analysis Report';
$adminPageSub   = report_kind_label($report['kind']);
?>
<?php include dirname(__DIR__) . '/includes/head.php'; ?>
<body class="antialiased font-inter tk-admin" style="background:var(--tk-canvas)">
<div class="flex h-screen overflow-hidden">
<?php include dirname(__DIR__) . '/includes/admin-sidebar.php'; ?>
<div class="flex-1 flex flex-col overflow-hidden min-w-0">
<?php include dirname(__DIR__) . '/includes/admin-topbar.php'; ?>
<main class="flex-1 overflow-y-auto p-6 md:p-8">

<a href="/admin/reports" class="text-xs font-bold text-slate-500 hover:underline">&larr; All reports</a>

<?php if ($flash): ?>
  <div class="tk-alert <?= $flashErr ? 'tk-alert--danger' : 'tk-alert--ok' ?>" role="status" style="margin-top:16px"><?= h($flash) ?></div>
<?php endif; ?>

<div class="flex flex-wrap items-start justify-between gap-4 mt-4 mb-6">
  <div class="min-w-0">
    <span class="text-[11px] font-bold px-2.5 py-1 rounded-full" style="background:<?= $badge[0] ?>;color:<?= $badge[1] ?>"><?= h($badge[2]) ?></span>
    <h1 class="font-outfit font-black text-2xl text-slate-900 mt-2"><?= h($report['title']) ?></h1>
    <p class="text-sm text-slate-500 mt-1">
      <?= h(report_kind_label($report['kind'])) ?> · <?= h($snap['scope']['label']) ?> · <?= h($snap['period']['label']) ?>
      · data frozen <?= h(format_date($report['snapshotAt'], 'j M Y, H:i')) ?>
      <?php if (!empty($snap['period']['inProgress'])): ?><span class="font-bold" style="color:#9a5b0f"> · period still in progress</span><?php endif; ?>
    </p>
  </div>
  <div class="flex flex-wrap gap-2">
    <a href="/admin/reports/<?= h(rawurlencode($id)) ?>?format=csv" class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-bold border border-slate-200 text-slate-700 bg-white hover:bg-slate-50">Download CSV</a>
    <?php if ($locked): ?>
      <a href="/reports/<?= h(rawurlencode($id)) ?>" target="_blank" rel="noopener" class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-bold border border-slate-200 text-slate-700 bg-white hover:bg-slate-50">View public page</a>
    <?php endif; ?>
    <?php foreach (array_filter([
        !$locked ? ['refresh', 'Refresh data', '#f1f5f9', '#334155', 'Recompute every figure from the latest published reports?'] : null,
        $report['status'] === 'DRAFT' ? ['submit', 'Submit for approval', '#750B25', '#fff', ''] : null,
        ($isSuper && !$locked) ? ['publish', 'Publish', '#047857', '#fff', 'Publish this report on the public site?'] : null,
        ($report['status'] !== 'DRAFT' && ($isSuper || $report['submittedBy'] === $uid)) ? ['unpublish', $locked ? 'Unpublish' : 'Return to draft', '#f1f5f9', '#334155', $locked ? 'Remove this report from the public site and return it to draft?' : ''] : null,
    ]) as [$a, $l, $bg, $fg, $confirm]): ?>
      <form method="POST"<?= $confirm ? ' onsubmit="return confirm(' . h(json_encode($confirm)) . ')"' : '' ?>>
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
        <input type="hidden" name="action" value="<?= h($a) ?>">
        <button type="submit" class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-bold" style="background:<?= $bg ?>;color:<?= $fg ?>"><?= h($l) ?></button>
      </form>
    <?php endforeach; ?>
  </div>
</div>

<section class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 mb-6">
  <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
    <div>
      <h2 class="font-outfit font-black text-base text-slate-900">Title and narrative</h2>
      <p class="text-xs text-slate-500 mt-1 max-w-2xl leading-relaxed">
        Markdown: <code>## Heading</code>, <code>**bold**</code>, <code>- list item</code>, <code>[link](https://…)</code>.
        <?php $src = $report['narrativeSource']; if ($src): ?>
          Current narrative: <strong><?= h(['AI' => 'AI draft, not yet edited', 'AI_REVIEWED' => 'AI draft, edited by an analyst', 'ANALYST' => 'written by an analyst'][$src] ?? $src) ?></strong>.
        <?php endif; ?>
      </p>
    </div>
    <?php if (!$locked && trim((string) $report['narrative']) === ''): ?>
      <form method="POST" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='Drafting… up to a minute';">
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
        <input type="hidden" name="action" value="draft_ai">
        <button type="submit" class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-bold" style="background:#E7952A;color:#0D0102"
                title="The Tafakari Agent writes a first draft from this report's data only. You then edit it.">Draft with Tafakari Agent</button>
      </form>
    <?php endif; ?>
  </div>
  <form method="POST" class="space-y-3">
    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
    <input type="hidden" name="action" value="save">
    <input type="text" name="title" maxlength="255" required value="<?= h($report['title']) ?>" <?= $locked ? 'disabled' : '' ?>
           class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm font-bold" aria-label="Report title">
    <textarea name="narrative" rows="14" <?= $locked ? 'disabled' : '' ?> aria-label="Narrative"
              class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm font-mono leading-relaxed"
              placeholder="Write the analysis here, or ask the Tafakari Agent for a first draft."><?= h((string) $report['narrative']) ?></textarea>
    <?php if ($locked): ?>
      <p class="text-xs text-slate-500">This report is published. <?= $isSuper ? 'Unpublish it to make changes.' : 'Ask a super admin to unpublish it to make changes.' ?></p>
    <?php else: ?>
      <p class="text-xs text-slate-500">Before submitting, check the narrative against the figures below and the cited reports. The AI draft uses only this report's data, but it can still misread it.</p>
      <button type="submit" class="btn-primary" style="padding:.6rem 1.4rem">Save</button>
    <?php endif; ?>
  </form>
</section>

<h2 class="font-outfit font-black text-lg text-slate-900 mb-3">Preview</h2>
<?php report_render($report, false); ?>

</main>
</div>
</div>
</body>
</html>
