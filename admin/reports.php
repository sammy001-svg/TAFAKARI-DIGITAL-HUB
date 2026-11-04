<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/reports.php';

$me  = require_auth();
$uid = $me['id'];
ensure_settings_table();
ensure_report_tables();

if (empty($_SESSION['report_csrf'])) $_SESSION['report_csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['report_csrf'];

$cycles   = report_recent_periods('CYCLE', 12);
$quarters = report_recent_periods('QUARTER', 6);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
        $error = 'Your session expired. Please try again.';
    } else {
        $kind   = ($_POST['kind'] ?? '') === 'QUARTER' ? 'QUARTER' : 'CYCLE';
        $period = (string) ($_POST[$kind === 'QUARTER' ? 'quarter' : 'cycle'] ?? '');
        $valid  = array_column($kind === 'QUARTER' ? $quarters : $cycles, 'start');
        $scope  = (string) ($_POST['scope'] ?? 'ALL');
        if (!in_array($period, $valid, true))      $error = 'Choose a period from the list.';
        elseif (report_scope($scope) === null)     $error = 'Choose a scope from the list.';
        else {
            try {
                $r = report_create($kind, $period, $scope, $uid);
                $_SESSION['report_flash'] = $r['created'] ? 'Draft created from the current data.' : 'A report for this period and scope already exists, so it has been opened.';
                header('Location: /admin/reports/' . rawurlencode($r['id']));
                exit;
            } catch (Throwable $e) {
                error_log('[admin/reports] create: ' . $e->getMessage());
                $error = 'Could not create the report: ' . $e->getMessage();
            }
        }
    }
}

$status = in_array($_GET['status'] ?? '', ['DRAFT', 'PENDING', 'PUBLISHED'], true) ? $_GET['status'] : '';
$rows = [];
try {
    $sql = 'SELECT r.id, r.kind, r.periodStart, r.periodEnd, r.scope, r.title, r.status, r.narrativeSource, r.updatedAt, r.publishedAt, u.name AS creator
            FROM AnalysisReport r LEFT JOIN User u ON u.id = r.createdBy';
    $args = [];
    if ($status !== '') { $sql .= ' WHERE r.status = ?'; $args[] = $status; }
    $sql .= ' ORDER BY r.periodStart DESC, r.kind, r.scope LIMIT 200';
    $q = db()->prepare($sql);
    $q->execute($args);
    $rows = $q->fetchAll();
} catch (Throwable $e) {}

$badge = ['DRAFT' => ['#f1f5f9', '#475569', 'Draft'], 'PENDING' => ['#E7952A22', '#9a5b0f', 'Awaiting approval'], 'PUBLISHED' => ['#05966915', '#047857', 'Published']];

$pageTitle      = 'Analysis Reports | Tafakari Admin';
$adminPageTitle = 'Analysis Reports';
$adminPageSub   = 'Bi-weekly and quarterly reports built from the heat-map data';
?>
<?php include dirname(__DIR__) . '/includes/head.php'; ?>
<body class="antialiased font-inter" style="background:#F4F6F8">
<div class="flex h-screen overflow-hidden">
<?php include dirname(__DIR__) . '/includes/admin-sidebar.php'; ?>
<div class="flex-1 flex flex-col overflow-hidden min-w-0">
<?php include dirname(__DIR__) . '/includes/admin-topbar.php'; ?>
<main class="flex-1 overflow-y-auto p-6 md:p-8">

<div class="mb-6">
  <h1 class="font-outfit font-black text-2xl text-slate-900">Analysis Reports</h1>
  <p class="text-sm text-slate-500 mt-1 max-w-3xl leading-relaxed">
    Each report freezes the heat-map data for one period and scope, with charts, comparisons with the previous period,
    critical flags and country profiles. Add a narrative, written by hand or drafted by the Tafakari Agent, then submit it.
    A super admin publishes it to the public <a href="/reports" class="font-semibold underline" style="color:#750B25">Reports</a> page.
  </p>
</div>

<?php if ($error): ?>
  <div class="mb-5 px-4 py-3 rounded-2xl text-sm font-medium text-red-800 bg-red-50 border border-red-200"><?= h($error) ?></div>
<?php endif; ?>

<div class="grid xl:grid-cols-3 gap-6">
  <form method="POST" class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 space-y-4 h-fit">
    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
    <h2 class="font-outfit font-black text-base text-slate-900">New report</h2>

    <fieldset class="flex gap-2" role="radiogroup" aria-label="Report type">
      <?php foreach (['CYCLE' => 'Bi-weekly', 'QUARTER' => 'Quarterly'] as $v => $l): ?>
        <label class="flex-1 cursor-pointer">
          <input type="radio" name="kind" value="<?= $v ?>" class="peer sr-only" <?= $v === 'CYCLE' ? 'checked' : '' ?> onchange="rpKind()">
          <span class="block text-center px-3 py-2 rounded-xl border border-slate-200 text-sm font-bold text-slate-600 peer-checked:text-white peer-checked:border-transparent"
                style="--tw-bg-opacity:1"><?= h($l) ?></span>
        </label>
      <?php endforeach; ?>
    </fieldset>

    <div id="rp-cycle">
      <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2" for="cycle">Monitoring cycle</label>
      <select name="cycle" id="cycle" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm">
        <?php foreach ($cycles as $c): ?>
          <option value="<?= h($c['start']) ?>"><?= h($c['label']) ?><?= $c['inProgress'] ? ' (in progress)' : '' ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div id="rp-quarter" style="display:none">
      <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2" for="quarter">Quarter</label>
      <select name="quarter" id="quarter" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm">
        <?php foreach ($quarters as $q): ?>
          <option value="<?= h($q['start']) ?>"><?= h($q['label']) ?><?= $q['inProgress'] ? ' (in progress)' : '' ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2" for="scope">Scope</label>
      <select name="scope" id="scope" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm">
        <option value="ALL">All monitored countries</option>
        <optgroup label="Monitoring region">
          <?php foreach (hm_country_groups() as $g): ?><option value="REGION:<?= h($g['name']) ?>"><?= h($g['name']) ?></option><?php endforeach; ?>
        </optgroup>
        <optgroup label="Country">
          <?php foreach (african_countries() as $c): ?><option value="COUNTRY:<?= h($c) ?>"><?= h($c) ?></option><?php endforeach; ?>
        </optgroup>
      </select>
    </div>
    <p class="text-xs text-slate-400 leading-relaxed">The data is frozen when the draft is created; you can refresh it until the report is published.</p>
    <button type="submit" class="btn-primary w-full justify-center" style="padding:.7rem 1rem">Create draft</button>
  </form>

  <div class="xl:col-span-2 bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-b border-slate-100">
      <h2 class="font-outfit font-black text-base text-slate-900">Reports</h2>
      <div class="flex flex-wrap gap-1">
        <?php foreach (['' => 'All', 'DRAFT' => 'Drafts', 'PENDING' => 'Awaiting approval', 'PUBLISHED' => 'Published'] as $v => $l): ?>
          <a href="/admin/reports<?= $v ? '?status=' . $v : '' ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold"
             style="<?= $status === $v ? 'background:#750B25;color:#fff' : 'color:#64748b' ?>"><?= h($l) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm" style="border-collapse:collapse;min-width:680px">
        <thead style="background:#f8fafc">
          <tr class="text-left text-[10px] font-black uppercase tracking-wider text-slate-400">
            <th class="px-6 py-3">Report</th><th class="px-4 py-3">Period</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Narrative</th><th class="px-4 py-3">Updated</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="5" class="px-6 py-10 text-center text-slate-400">No reports yet. Create the first one on the left.</td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $r): [$bg, $fg, $bl] = $badge[$r['status']] ?? ['#f1f5f9', '#475569', $r['status']]; ?>
            <tr class="border-t border-slate-100 hover:bg-slate-50">
              <td class="px-6 py-3">
                <a href="/admin/reports/<?= h(rawurlencode($r['id'])) ?>" class="font-bold text-slate-900 hover:underline"><?= h($r['title']) ?></a>
                <span class="block text-xs text-slate-400"><?= h(report_kind_label($r['kind'])) ?> · <?= h(report_scope($r['scope'])['label'] ?? $r['scope']) ?></span>
              </td>
              <td class="px-4 py-3 text-xs text-slate-600 whitespace-nowrap"><?= h(format_date($r['periodStart'], 'j M')) ?> – <?= h(format_date($r['periodEnd'], 'j M Y')) ?></td>
              <td class="px-4 py-3"><span class="text-[11px] font-bold px-2.5 py-1 rounded-full" style="background:<?= $bg ?>;color:<?= $fg ?>"><?= h($bl) ?></span></td>
              <td class="px-4 py-3 text-xs text-slate-600"><?= h(['AI' => 'AI draft', 'AI_REVIEWED' => 'AI draft, edited', 'ANALYST' => 'Analyst'][$r['narrativeSource'] ?? ''] ?? 'None yet') ?></td>
              <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap"><?= h(format_relative_time($r['updatedAt'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

</main>
</div>
</div>
<style>
.peer:checked + span { background:#750B25; }
.sr-only { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); border:0; }
.peer:focus-visible + span { outline:2px solid #750B25; outline-offset:2px; }
</style>
<script>
function rpKind() {
  var q = document.querySelector('input[name=kind]:checked').value === 'QUARTER';
  document.getElementById('rp-cycle').style.display = q ? 'none' : '';
  document.getElementById('rp-quarter').style.display = q ? '' : 'none';
}
</script>
</body>
</html>
