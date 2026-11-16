<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/agent-articles.php';
require_once dirname(__DIR__) . '/includes/agent-videos.php';
require_once dirname(__DIR__) . '/includes/agent-updates.php';
require_once dirname(__DIR__) . '/includes/agent-nav.php';

$me      = require_auth();
$isSuper = is_super_admin();
$uid     = $me['id'];

ensure_settings_table();
ensure_agent_tables();
ensure_agent_update_table();

if (empty($_SESSION['agent_csrf'])) $_SESSION['agent_csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['agent_csrf'];

$id = (string) ($_GET['id'] ?? '');

// Word and Excel downloads: the current saved version
if ($id !== '' && in_array($_GET['file'] ?? '', ['word', 'excel'], true)) {
    $u = agent_update_get($id);
    if (!$u) { http_response_code(404); exit('Update not found.'); }
    try {
        $excel = $_GET['file'] === 'excel';
        export_send($excel ? agent_update_xlsx($u) : agent_update_docx($u), 'tafakari-update-' . $u['country'] . '-' . $u['periodStart'], $excel ? 'xlsx' : 'docx');
    } catch (Throwable $e) {
        error_log('[admin/agent-updates] file: ' . $e->getMessage());
        http_response_code(500);
        exit('The file could not be created. Check that the PHP zip extension is enabled.');
    }
}

$back = function (string $url, string $msg, bool $err = false): never {
    $_SESSION['update_flash'] = ['msg' => $msg, 'err' => $err];
    header('Location: ' . $url);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $self   = $id !== '' ? '/admin/agent/updates/' . rawurlencode($id) : '/admin/agent/updates';
    if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) $back($self, 'Your session expired. Please try again.', true);

    try {
        switch ($action) {
            case 'save':
                agent_update_save($id, $_POST, $uid);
                $back($self, 'Changes saved.');

            case 'publish':
                // Publishes what is on screen, including edits not yet saved
                if (!$isSuper) $back($self, 'Only a super admin can approve and publish a monitoring update. Save your edits, and a super admin will review it.', true);
                agent_update_save($id, $_POST, $uid);
                $postId = agent_update_publish($id, $uid, $isSuper, isset($_POST['checked']));
                $back($self, 'Approved and published. It now counts on the heat map and in the analysis reports.');

            case 'reject':
                agent_update_reject($id, $uid, (string) ($_POST['notes'] ?? ''));
                $back('/admin/agent/updates', 'Update rejected. It stays on record under Rejected.');

            case 'restore':
                agent_update_restore($id);
                $back($self, 'Returned for review.');

            case 'run':
                // One country now, outside the hourly rotation
                if (!$isSuper) $back($self, 'Only a super admin can start a run.', true);
                $country = (string) ($_POST['country'] ?? '');
                @set_time_limit(900);
                ignore_user_abort(true);
                session_write_close();
                $r = agent_update_run($country, $uid);
                session_start();
                $back($r['updateId'] ? '/admin/agent/updates/' . rawurlencode($r['updateId']) : '/admin/agent/updates', $r['message'], $r['status'] === 'FAILED');

            default:
                $back($self, 'Unknown action.', true);
        }
    } catch (Throwable $e) {
        error_log('[admin/agent-updates] ' . $action . ': ' . $e->getMessage());
        $back($self, $e->getMessage(), true);
    }
}

$flash = $_SESSION['update_flash'] ?? null;
unset($_SESSION['update_flash']);

$update = $id !== '' ? agent_update_get($id) : null;
if ($id !== '' && !$update) {
    $_SESSION['update_flash'] = ['msg' => 'Update not found.', 'err' => true];
    header('Location: /admin/agent/updates');
    exit;
}

$validTabs = ['PROPOSED' => 'To review', 'PUBLISHED' => 'Published', 'REJECTED' => 'Rejected'];
$tab = isset($validTabs[$_GET['status'] ?? '']) ? $_GET['status'] : 'PROPOSED';
$counts = ['PROPOSED' => 0, 'PUBLISHED' => 0, 'REJECTED' => 0];
$rows = [];
try {
    foreach (db()->query('SELECT status, COUNT(*) AS n FROM AgentCountryUpdate GROUP BY status')->fetchAll() as $r) $counts[$r['status']] = (int) $r['n'];
    if (!$update) {
        $q = db()->prepare('SELECT * FROM AgentCountryUpdate WHERE status = ? ORDER BY periodStart DESC, country LIMIT 200');
        $q->execute([$tab]);
        $rows = array_map('agent_update_decode', $q->fetchAll());
    }
} catch (Throwable $e) {}

// This cycle's coverage: which monitored countries have an update
$window   = agent_update_window();
$coverage = [];
foreach (agent_settings()['countries'] as $c) $coverage[$c] = agent_update_existing('CYCLE', $c, $window['cycleStart']);

$reviewer = null;
if ($update && !empty($update['reviewedBy'])) {
    try {
        $q = db()->prepare('SELECT name FROM User WHERE id = ? LIMIT 1');
        $q->execute([$update['reviewedBy']]);
        $reviewer = $q->fetchColumn() ?: null;
    } catch (Throwable $e) {}
}

$confBadge  = ['high' => 'tk-badge--ok', 'medium' => 'tk-badge--warn', 'low' => 'tk-badge--danger'];
$factBadge  = ['verified' => 'tk-badge--ok', 'single_source' => 'tk-badge--warn', 'disputed' => 'tk-badge--danger', 'unverified' => 'tk-badge--muted'];
$factLabels = agent_update_fact_statuses();
$statusInfo = ['PROPOSED' => ['tk-badge--warn', 'Awaiting review'], 'PUBLISHED' => ['tk-badge--ok', 'Published'], 'REJECTED' => ['tk-badge--danger', 'Rejected']];

$pageTitle      = 'Country updates | Tafakari Agent';
$adminPageTitle = 'Tafakari Agent';
$adminPageSub   = 'Bi-weekly and quarterly country monitoring updates, awaiting review';
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

<?php if ($update):
    $open    = $update['status'] === 'PROPOSED';
    $si      = $statusInfo[$update['status']] ?? ['tk-badge--muted', $update['status']];
    $selfUrl = '/admin/agent/updates/' . rawurlencode($update['id']);
    $isQ     = $update['kind'] === 'QUARTER';
    $factTally = array_count_values(array_column($update['factCheck'], 'status'));
?>
  <!-- ── Review one update ──────────────────────────────────────── -->
  <header class="tk-page-head">
    <div>
      <a href="/admin/agent/updates" class="text-xs font-bold" style="color:#750B25">&larr; All country updates</a>
      <h1 class="tk-h1" style="margin-top:10px"><?= h($update['country']) ?>: <?= h(agent_update_label($update)) ?></h1>
      <p class="tk-lead"><?= $isQ ? 'Quarterly' : 'Bi-weekly' ?> Country Conflict and Peace Monitoring Update · drafted <?= h(format_relative_time($update['createdAt'])) ?></p>
    </div>
    <div class="tk-page-head__actions">
      <span class="tk-badge <?= h($si[0]) ?>"><?= h($si[1]) ?></span>
      <a href="<?= h($selfUrl) ?>/word" class="tk-btn tk-btn--outline">Word</a>
      <a href="<?= h($selfUrl) ?>/excel" class="tk-btn tk-btn--outline">Excel</a>
    </div>
  </header>

  <div class="grid xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2" style="display:flex;flex-direction:column;gap:16px">
      <?php if ($open): ?>
        <form method="POST" id="up-form" class="tk-card tk-card--pad" style="display:flex;flex-direction:column;gap:18px">
          <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
          <div class="grid sm:grid-cols-4 gap-4">
            <div class="tk-field">
              <label class="tk-label" for="up-score">Overall score (0–10)</label>
              <input id="up-score" name="overall_score" class="tk-input" inputmode="decimal" required value="<?= h((string) $update['overallScore']) ?>">
            </div>
            <div class="tk-field sm:col-span-3">
              <span class="tk-label">Tier</span>
              <p class="text-sm font-bold" style="color:<?= h(hm_tier_color($update['overallScore'])) ?>;padding-top:10px"><?= h(hm_tier_label($update['overallScore'])) ?></p>
            </div>
          </div>
          <div class="tk-field">
            <label class="tk-label" for="up-just">Justification</label>
            <textarea id="up-just" name="justification" class="tk-textarea" rows="4" required><?= h($update['justification']) ?></textarea>
          </div>
          <div class="tk-field">
            <label class="tk-label" for="up-sum">Executive summary</label>
            <textarea id="up-sum" name="executive_summary" class="tk-textarea" rows="5" required><?= h($update['executiveSummary']) ?></textarea>
          </div>
          <?php foreach (['conflict_dynamics' => 'Conflict dynamics', 'peace_dynamics' => 'Peace dynamics', 'risks_next_period' => 'Risks for the coming period'] as $k => $l): ?>
            <div class="tk-field">
              <label class="tk-label" for="up-<?= $k ?>"><?= h($l) ?></label>
              <textarea id="up-<?= $k ?>" name="<?= $k ?>" class="tk-textarea" rows="4"><?= h((string) ($update['analysis'][$k] ?? '')) ?></textarea>
            </div>
          <?php endforeach; ?>
          <?php if ($update['scores'] && !$isQ): ?>
            <fieldset class="tk-field">
              <legend class="tk-label">Proposed element scores (1–10)</legend>
              <div class="grid sm:grid-cols-2 gap-3 mt-2">
                <?php foreach ($update['scores'] as $el => $v): ?>
                  <label class="flex items-center gap-3 text-sm text-slate-700" title="<?= h((string) ($update['rationales'][$el] ?? '')) ?>">
                    <input type="number" min="1" max="10" name="scores[<?= h($el) ?>]" value="<?= (int) $v ?>" class="tk-input" style="width:72px">
                    <span><?= h($el) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
              <p class="tk-help">Hover an element to see the agent's reasoning. These scores reach the heat map only once the update is published.</p>
            </fieldset>
          <?php endif; ?>
          <?php if ($isSuper): ?>
            <label class="flex items-start gap-3 cursor-pointer" style="padding:14px 16px;border:1px solid var(--tk-line);border-radius:14px">
              <input type="checkbox" name="checked" value="1" id="up-ok" class="mt-1 w-4 h-4" style="accent-color:#750B25">
              <span class="text-sm text-slate-800"><strong>I have reviewed this update.</strong> I checked the fact check and opened the sources behind the key claims, the scores are justified, and no private individual is identified or put at risk.</span>
            </label>
          <?php endif; ?>
          <div class="flex flex-wrap gap-3">
            <?php if ($isSuper): ?><button type="submit" name="action" value="publish" id="up-pub" class="tk-btn tk-btn--primary" disabled>Approve and publish</button><?php endif; ?>
            <button type="submit" name="action" value="save" class="tk-btn tk-btn--outline">Save changes</button>
          </div>
          <?php if (!$isSuper): ?><p class="tk-help">A super admin approves and publishes monitoring updates. Save your edits, and they will see them.</p><?php endif; ?>
        </form>
        <form method="POST" class="tk-card tk-card--pad flex flex-wrap items-center gap-3">
          <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
          <input type="hidden" name="action" value="reject">
          <input type="text" name="notes" maxlength="1000" required class="tk-input" style="flex:1;min-width:220px" placeholder="Reason for rejecting (required)" aria-label="Reason for rejecting">
          <button type="submit" class="tk-btn tk-btn--danger">Reject</button>
        </form>
        <?php if ($isSuper): ?>
          <script>
            (function () {
              var ok = document.getElementById('up-ok'), pub = document.getElementById('up-pub');
              ok.addEventListener('change', function () { pub.disabled = !ok.checked; });
            })();
          </script>
        <?php endif; ?>
      <?php endif; ?>

      <article class="tk-card tk-card--pad">
        <h2 class="font-outfit font-black text-base text-slate-900" style="margin-bottom:8px"><?= $open ? 'The full update' : 'The update' ?></h2>
        <div class="prose max-w-none text-slate-700 leading-relaxed"><?= markdown_to_html(agent_update_markdown($update)) ?></div>
      </article>
    </div>

    <aside style="display:flex;flex-direction:column;gap:16px">
      <?php if (!$open): ?>
        <div class="tk-card tk-card--pad text-sm text-slate-700">
          <strong><?= h($si[1]) ?></strong><?php if ($reviewer): ?> by <?= h($reviewer) ?><?php endif; ?>
          <?php if (!empty($update['reviewedAt'])): ?> · <?= h(format_date($update['reviewedAt'], 'j M Y, H:i')) ?><?php endif; ?>
          <?php if (!empty($update['reviewNotes'])): ?><p class="mt-2 text-slate-500">“<?= h($update['reviewNotes']) ?>”</p><?php endif; ?>
          <?php if (!empty($update['postId'])): ?>
            <p class="mt-3"><a class="font-bold underline" style="color:#750B25" target="_blank" rel="noopener" href="/documents/<?= h(rawurlencode($update['postId'])) ?>">Open the public update</a></p>
          <?php endif; ?>
          <?php if ($update['status'] === 'REJECTED'): ?>
            <form method="POST" class="mt-3">
              <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
              <input type="hidden" name="action" value="restore">
              <button type="submit" class="tk-btn tk-btn--outline tk-btn--sm">Return for review</button>
            </form>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <div class="tk-card tk-card--pad">
        <h2 class="font-outfit font-black text-base text-slate-900">Fact check</h2>
        <div class="flex flex-wrap gap-2 mt-3">
          <span class="tk-badge <?= h($confBadge[$update['confidence']] ?? 'tk-badge--muted') ?>">Confidence: <?= h($update['confidence']) ?></span>
          <span class="tk-badge tk-badge--muted"><?= (int) $update['sourceCount'] ?> independent publishers</span>
          <?php foreach ($factLabels as $k => $l): if (empty($factTally[$k])) continue; ?>
            <span class="tk-badge <?= h($factBadge[$k]) ?>"><?= (int) $factTally[$k] ?> <?= h(strtolower($l)) ?></span>
          <?php endforeach; ?>
        </div>
        <?php if ($update['factCheck']): ?>
          <ul class="mt-3 text-sm text-slate-700 leading-relaxed" style="list-style:none;padding:0">
            <?php foreach ($update['factCheck'] as $f): ?>
              <li style="margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid var(--tk-line)">
                <span class="tk-badge <?= h($factBadge[$f['status']] ?? 'tk-badge--muted') ?>" style="margin-bottom:4px"><?= h($factLabels[$f['status']] ?? $f['status']) ?></span>
                <span class="block"><?= h($f['claim']) ?> <span class="text-slate-400"><?= h(agent_update_cites($f['sources'])) ?></span></span>
                <?php if ($f['note'] !== ''): ?><span class="block text-xs text-slate-500 mt-1"><?= h($f['note']) ?></span><?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
          <p class="tk-help">Check the "single source", "disputed" and "unverified" claims first.</p>
        <?php else: ?>
          <p class="tk-help" style="margin-top:12px">No fact-check entries. Check the main claims against the sources.</p>
        <?php endif; ?>
      </div>

      <?php if (!empty($update['notes'])): ?>
        <div class="tk-card tk-card--pad text-sm text-slate-700">
          <h2 class="font-outfit font-black text-base text-slate-900">Notes from the agent</h2>
          <p class="mt-2 whitespace-pre-line"><?= h($update['notes']) ?></p>
        </div>
      <?php endif; ?>

      <div class="tk-card tk-card--pad">
        <h2 class="font-outfit font-black text-base text-slate-900">Sources</h2>
        <p class="tk-help" style="margin-top:4px">Each was retrieved by the agent during this run. Numbers in the tables refer to this list.</p>
        <ol class="mt-3 text-xs leading-relaxed" style="list-style:decimal;padding-left:20px">
          <?php foreach ($update['sources'] as $s): ?>
            <li style="margin-bottom:9px">
              <a href="<?= h($s['url']) ?>" target="_blank" rel="noopener noreferrer nofollow" class="font-bold underline" style="color:#750B25"><?= h($s['publisher']) ?></a>
              <?php if ($s['title'] !== ''): ?><span class="text-slate-600"> · <?= h($s['title']) ?></span><?php endif; ?>
              <?php if ($s['published'] !== ''): ?><span class="text-slate-400"> · <?= h($s['published']) ?></span><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ol>
      </div>
    </aside>
  </div>

<?php else: ?>
  <!-- ── Queue and coverage ─────────────────────────────────────── -->
  <header class="tk-page-head">
    <div>
      <span class="tk-eyebrow">Monitoring</span>
      <h1 class="tk-h1" style="margin-top:12px">Country updates</h1>
      <p class="tk-lead">The agent fills CRTP's Bi-weekly Country Conflict and Peace Monitoring template for every monitored country, each cycle, with a fact check of the key claims. A super admin approves each one before it is published.</p>
    </div>
  </header>

  <?php agent_nav('updates', $isSuper); ?>

  <div class="grid xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 tk-card" style="overflow:hidden">
      <nav class="tk-segment" aria-label="Filter by status" style="margin:16px">
        <?php foreach ($validTabs as $v => $l): ?>
          <a href="/admin/agent/updates?status=<?= $v ?>" class="<?= $tab === $v ? 'is-active' : '' ?>"><?= h($l) ?> (<?= (int) $counts[$v] ?>)</a>
        <?php endforeach; ?>
      </nav>
      <div class="tk-table-wrap">
        <table class="tk-table">
          <thead><tr><th>Country</th><th>Period</th><th>Overall</th><th>Fact check</th><th>Drafted</th></tr></thead>
          <tbody>
            <?php if (!$rows): ?>
              <tr><td colspan="5" style="text-align:center;padding:32px" class="text-slate-400"><?= $tab === 'PROPOSED' ? 'Nothing waiting. The agent writes about two country updates a day.' : 'None yet.' ?></td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): $t = array_count_values(array_column($r['factCheck'], 'status')); ?>
              <tr>
                <td><a href="/admin/agent/updates/<?= h(rawurlencode($r['id'])) ?>" class="font-bold text-slate-900 hover:underline"><?= h($r['country']) ?></a>
                    <span class="block text-xs text-slate-400"><?= $r['kind'] === 'QUARTER' ? 'Quarterly' : 'Bi-weekly' ?></span></td>
                <td class="text-xs whitespace-nowrap"><?= h(agent_update_label($r)) ?></td>
                <td><span class="tk-badge tk-badge--plain" style="background:<?= h(hm_tier_color($r['overallScore'])) ?>;color:#fff"><?= h((string) $r['overallScore']) ?></span></td>
                <td class="text-xs"><?= (int) ($t['verified'] ?? 0) ?> verified<?= !empty($t['disputed']) ? ' · ' . (int) $t['disputed'] . ' disputed' : '' ?><?= !empty($t['single_source']) ? ' · ' . (int) $t['single_source'] . ' single source' : '' ?></td>
                <td class="text-xs text-slate-500 whitespace-nowrap"><?= h(format_relative_time($r['createdAt'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <aside class="tk-card tk-card--pad">
      <h2 class="font-outfit font-black text-base text-slate-900">This cycle: <?= h($window['label']) ?></h2>
      <p class="tk-help" style="margin-top:4px">Each monitored country gets one update per cycle.</p>
      <ul class="mt-3 text-sm" style="list-style:none;padding:0">
        <?php foreach ($coverage as $c => $ex): ?>
          <li class="flex items-center justify-between gap-3" style="padding:6px 0;border-bottom:1px solid var(--tk-line)">
            <span><?= h($c) ?></span>
            <?php if ($ex): ?>
              <a href="/admin/agent/updates/<?= h(rawurlencode($ex['id'])) ?>" class="tk-badge <?= h($statusInfo[$ex['status']][0] ?? 'tk-badge--muted') ?>"><?= h($statusInfo[$ex['status']][1] ?? $ex['status']) ?></a>
            <?php elseif ($isSuper): ?>
              <form method="POST" onsubmit="var b=this.querySelector('button');b.disabled=true;b.textContent='Researching… a few minutes';">
                <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                <input type="hidden" name="action" value="run">
                <input type="hidden" name="country" value="<?= h($c) ?>">
                <button type="submit" class="tk-btn tk-btn--outline tk-btn--sm">Write now</button>
              </form>
            <?php else: ?>
              <span class="tk-badge tk-badge--muted">Queued</span>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </aside>
  </div>
<?php endif; ?>

</main>
</div>
</div>
</body>
</html>
