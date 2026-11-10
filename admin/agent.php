<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/agent.php';
require_once dirname(__DIR__) . '/includes/assistant.php';
require_once dirname(__DIR__) . '/includes/agent-auto.php';
require_once dirname(__DIR__) . '/includes/agent-nav.php';

$me      = require_auth();
$isSuper = is_super_admin();
$uid     = $me['id'];

ensure_settings_table();
ensure_agent_tables();
ensure_assistant_table();

if (empty($_SESSION['agent_csrf'])) $_SESSION['agent_csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['agent_csrf'];
$keyReady = agent_api_key() !== '';

$validTabs = $isSuper ? ['queue', 'promoted', 'rejected', 'runs', 'settings'] : ['queue', 'promoted', 'rejected'];
$tab = in_array($_GET['tab'] ?? '', $validTabs, true) ? $_GET['tab'] : 'queue';

/** Post/Redirect/Get: keep the message across the redirect. */
$back = function (string $tab, string $msg, bool $err = false, array $extra = []): never {
    $_SESSION['agent_flash'] = ['msg' => $msg, 'err' => $err];
    header('Location: /admin/agent?' . http_build_query(['tab' => $tab] + $extra));
    exit;
};

// ── Actions ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = (string) ($_POST['action'] ?? '');
    $itemId  = (string) ($_POST['item'] ?? '');
    $retTab  = in_array($_POST['tab'] ?? '', $validTabs, true) ? $_POST['tab'] : 'queue';
    $keep    = array_filter(['country' => (string) ($_POST['country_filter'] ?? '')]);

    if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
        $back($retTab, 'Your session expired. Please try again.', true);
    }

    try {
        switch ($action) {
            case 'promote':
                $postId = agent_promote_item($itemId, $uid);
                // Straight into the editor: the analyst reviews and adjusts
                // the AI-proposed report before submitting it for approval
                header('Location: /admin/content/' . rawurlencode($postId) . '/edit');
                exit;

            case 'reject':
                agent_set_item_status($itemId, 'REJECTED', $uid, (string) ($_POST['notes'] ?? ''));
                $back($retTab, 'Finding rejected. It stays on record under Rejected.', false, $keep);

            case 'duplicate':
                agent_set_item_status($itemId, 'DUPLICATE', $uid, (string) ($_POST['notes'] ?? ''));
                $back($retTab, 'Marked as a duplicate.', false, $keep);

            case 'restore':
                agent_set_item_status($itemId, 'PROPOSED', $uid);
                $back($retTab, 'Returned to the review queue.', false, $keep);

            case 'save_settings':
                if (!$isSuper) $back('queue', 'Only a super admin can change agent settings.', true);
                set_setting('agent_enabled',        isset($_POST['enabled']) ? '1' : '0', $uid);
                set_setting('assistant_enabled',    isset($_POST['assistant_enabled']) ? '1' : '0', $uid);
                // Options from the older, longer form must not keep applying unseen
                $old = ['agent_monthly_budget', 'agent_countries', 'agent_lookback_days', 'agent_max_searches', 'agent_max_fetches', 'agent_max_items', 'agent_effort', 'agent_allowed_domains', 'agent_blocked_domains',
                        'assistant_monthly_budget', 'assistant_per_hour', 'assistant_per_day'];
                db()->prepare('DELETE FROM SiteSetting WHERE `key` IN (' . implode(',', array_fill(0, count($old), '?')) . ')')->execute($old);
                $back('settings', 'Settings saved.');

            case 'save_api_key':
                if (!$isSuper) $back('queue', 'Only a super admin can change the API key.', true);
                $newKey = trim((string) ($_POST['api_key'] ?? ''));
                if ($newKey === '' && !$keyReady) throw new RuntimeException('Paste your Claude API key first.');
                if ($newKey !== '') agent_save_key($newKey, $uid);
                $ws = agent_save_workspace((string) ($_POST['workspace_id'] ?? ''), $uid);
                if (!agent_sdk_available()) $back('settings', 'Claude API key saved.');
                // get_setting() caches per request, so test the values just entered
                $check = agent_check_connection($newKey !== '' ? $newKey : null, $ws);
                $back('settings', $check['ok'] ? 'Saved. ' . $check['message'] . ' The agent is ready.' : 'Saved, but Claude did not accept it. ' . $check['message'], !$check['ok']);

            case 'remove_api_key':
                if (!$isSuper) $back('queue', 'Only a super admin can change the API key.', true);
                agent_remove_key($uid);
                $back('settings', 'Claude API key removed.');

            case 'run_now':
                if (!$isSuper) $back('queue', 'Only a super admin can start a run.', true);
                @set_time_limit(900);
                ignore_user_abort(true);   // finish and log the run even if the browser gives up
                session_write_close();     // a run takes minutes: don't lock the user's other tabs
                $r = agent_auto_tick(true);
                session_start();
                $back('runs', $r['message'], $r['status'] === 'FAILED');

            default:
                $back($retTab, 'Unknown action.', true);
        }
    } catch (Throwable $e) {
        error_log('[admin/agent] ' . $action . ': ' . $e->getMessage());
        $back($action === 'save_api_key' ? 'settings' : $retTab, $e->getMessage(), true, $keep);
    }
}

$flash = $_SESSION['agent_flash'] ?? null;
unset($_SESSION['agent_flash']);

// ── Data ──────────────────────────────────────────────────────────
$settings = agent_settings();
$sdkOk    = agent_sdk_available();
$keyOk    = agent_api_key() !== '';
$savedKey = $isSuper ? agent_saved_key() : '';

$statusFor = ['queue' => ['PROPOSED'], 'promoted' => ['PROMOTED'], 'rejected' => ['REJECTED', 'DUPLICATE']];
$counts = ['PROPOSED' => 0, 'PROMOTED' => 0, 'REJECTED' => 0, 'DUPLICATE' => 0];
try {
    foreach (db()->query('SELECT status, COUNT(*) AS n FROM AgentItem GROUP BY status')->fetchAll() as $r) {
        $counts[$r['status']] = (int) $r['n'];
    }
} catch (Throwable $e) {}

$filterCountry = in_array($_GET['country'] ?? '', african_countries(), true) ? $_GET['country'] : '';
$items = [];
if (isset($statusFor[$tab])) {
    try {
        $st   = $statusFor[$tab];
        $sql  = 'SELECT i.*, u.name AS reviewerName FROM AgentItem i LEFT JOIN User u ON u.id = i.reviewedBy
                 WHERE i.status IN (' . implode(',', array_fill(0, count($st), '?')) . ')';
        $args = $st;
        if ($filterCountry !== '') { $sql .= ' AND i.countries LIKE ?'; $args[] = '%' . json_encode($filterCountry, JSON_UNESCAPED_UNICODE) . '%'; }
        $sql .= $tab === 'queue' ? ' ORDER BY i.createdAt DESC LIMIT 100' : ' ORDER BY i.reviewedAt DESC LIMIT 100';
        $q = db()->prepare($sql);
        $q->execute($args);
        $items = array_map('agent_item_decode', $q->fetchAll());
    } catch (Throwable $e) {}
}

$runs = [];
if ($tab === 'runs') {
    try {
        $runs = db()->query('SELECT r.*, u.name AS starterName FROM AgentRun r LEFT JOIN User u ON u.id = r.startedBy
                             ORDER BY r.startedAt DESC LIMIT 50')->fetchAll();
    } catch (Throwable $e) {}
}

$taxonomy = heatmap_taxonomy();
$catColor = [];
foreach ($taxonomy as $c) $catColor[$c['name']] = $c['color'];
$elementIndex = heatmap_element_index();

$pageTitle      = 'Tafakari Agent | Tafakari Admin';
$adminPageTitle = 'Tafakari Agent';
$adminPageSub   = 'AI-proposed findings awaiting analyst review';
?>
<?php include dirname(__DIR__) . '/includes/head.php'; ?>
<body class="antialiased font-inter tk-admin" style="background:var(--tk-canvas)">
<div class="flex h-screen overflow-hidden">
<?php include dirname(__DIR__) . '/includes/admin-sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden min-w-0">
<?php include dirname(__DIR__) . '/includes/admin-topbar.php'; ?>

<main class="flex-1 overflow-y-auto p-6 md:p-8">

<style>
.ag-chip  { display:inline-flex; align-items:center; gap:6px; font-size:11px; font-weight:700; padding:3px 10px; border-radius:999px; }
.ag-btn   { display:inline-flex; align-items:center; gap:6px; padding:9px 16px; border-radius:12px; font-size:13px; font-weight:700; cursor:pointer; border:0; }
.ag-input { width:100%; padding:9px 12px; border:1.5px solid #e2e8f0; border-radius:12px; background:#f8fafc; font-size:13px; outline:none; }
.ag-input:focus { border-color:#750B25; background:#fff; }
.ag-label { display:block; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.12em; color:#94a3b8; margin-bottom:6px; }
</style>

<?php if ($flash): ?>
  <div class="tk-alert <?= $flash['err'] ? 'tk-alert--danger' : 'tk-alert--ok' ?>" role="status">
    <?= h($flash['msg']) ?>
  </div>
<?php endif; ?>

<header class="tk-page-head">
  <div>
    <span class="tk-eyebrow">Monitoring</span>
    <h1 class="tk-h1" style="margin-top:12px">Tafakari Agent</h1>
    <p class="tk-lead">The agent works by itself: it monitors your regions, writes articles and finds videos. People only approve what goes on the site.</p>
  </div>
  <div class="tk-page-head__actions">
    <span class="tk-badge <?= $settings['enabled'] ? 'tk-badge--ok' : 'tk-badge--muted' ?>"><?= $settings['enabled'] ? 'Agent on' : 'Agent off' ?></span>
  </div>
</header>

<?php agent_nav(isset($statusFor[$tab]) ? 'findings' : $tab, $isSuper); ?>

<?php if (isset($statusFor[$tab])): ?>
  <!-- ── Findings ───────────────────────────────────────────────── -->
  <div class="flex flex-wrap gap-4 mb-4 text-sm font-bold">
    <?php foreach (['queue' => 'Waiting (' . $counts['PROPOSED'] . ')', 'promoted' => 'Turned into reports (' . $counts['PROMOTED'] . ')', 'rejected' => 'Rejected (' . ($counts['REJECTED'] + $counts['DUPLICATE']) . ')'] as $_t => $_l): ?>
      <a href="/admin/agent?<?= h(http_build_query(array_filter(['tab' => $_t, 'country' => $filterCountry]))) ?>" style="color:<?= $_t === $tab ? '#750B25' : '#64748b' ?>"><?= h($_l) ?></a>
    <?php endforeach; ?>
  </div>
  <form method="GET" class="flex flex-wrap items-center gap-2 mb-5">
    <input type="hidden" name="tab" value="<?= h($tab) ?>">
    <select name="country" onchange="this.form.submit()" class="tk-select" style="width:auto;min-width:220px" aria-label="Filter by country">
      <option value="">All countries</option>
      <?php foreach (african_countries() as $c): ?>
        <option value="<?= h($c) ?>" <?= $c === $filterCountry ? 'selected' : '' ?>><?= h($c) ?></option>
      <?php endforeach; ?>
    </select>
    <?php if ($filterCountry !== ''): ?><a href="/admin/agent?tab=<?= h($tab) ?>" class="text-xs font-bold text-slate-500 underline">Clear</a><?php endif; ?>
  </form>

  <?php if (!$items): ?>
    <div class="bg-white rounded-3xl border border-slate-100 px-6 py-12 text-center">
      <p class="text-sm font-semibold text-slate-600">
        <?= $tab === 'queue' ? 'No findings are waiting for review.' : 'Nothing here yet.' ?>
      </p>
      <?php if ($tab === 'queue'): ?>
        <p class="text-xs text-slate-400 mt-1.5">
          <?= $settings['enabled'] ? 'New findings appear after the next scheduled run.' : 'Scheduled runs are off.' ?>
          <?= $isSuper ? 'A super admin can start a run from the Settings tab.' : '' ?>
        </p>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="space-y-5">
  <?php foreach ($items as $it):
      // Group scores by category, in taxonomy order
      $byCat = [];
      foreach ($it['scores'] as $el => $v) $byCat[$elementIndex[$el] ?? 'Other'][$el] = $v;
      $vals    = array_values($it['scores']);
      $overall = $vals ? round(array_sum($vals) / count($vals), 1) : null;
      $confCol = ['high' => '#059669', 'medium' => '#9a5b0f', 'low' => '#ED1C24'][$it['confidence']] ?? '#64748b';
  ?>
    <article class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
      <div style="height:4px;background:<?= h($overall !== null ? hm_tier_color($overall) : '#cbd5e1') ?>"></div>
      <div class="p-6">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-2">
          <h2 class="font-outfit font-black text-lg text-slate-900 leading-snug max-w-3xl"><?= h($it['title']) ?></h2>
          <?php if ($overall !== null): ?>
            <span class="ag-chip" style="background:<?= h(hm_tier_color($overall)) ?>;color:#fff"
                  title="Mean of the proposed element scores"><?= h(number_format($overall, 1)) ?> · <?= h(hm_tier_label($overall)) ?></span>
          <?php endif; ?>
        </div>

        <div class="flex flex-wrap gap-2 mb-4">
          <span class="ag-chip" style="background:#f1f5f9;color:#475569">📍 <?= h(implode(', ', $it['countries'])) ?><?= $it['region'] !== '' ? ' · ' . h($it['region']) : '' ?></span>
          <?php if ($it['eventDate']): ?><span class="ag-chip" style="background:#f1f5f9;color:#475569"><?= h(format_date($it['eventDate'])) ?></span><?php endif; ?>
          <span class="ag-chip" style="background:<?= $confCol ?>15;color:<?= $confCol ?>">Confidence: <?= h($it['confidence']) ?></span>
          <span class="ag-chip" style="background:#f1f5f9;color:#475569"><?= (int) $it['sourceCount'] ?> independent source<?= (int) $it['sourceCount'] === 1 ? '' : 's' ?></span>
          <span class="ag-chip" style="background:#750B2512;color:#750B25">AI-proposed · <?= h(format_relative_time($it['createdAt'])) ?></span>
        </div>

        <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line mb-5"><?= h($it['summary']) ?></p>

        <div class="grid lg:grid-cols-2 gap-5">
          <div>
            <p class="ag-label">Proposed scores</p>
            <div class="space-y-3">
              <?php foreach ($taxonomy as $c): if (empty($byCat[$c['name']])) continue; ?>
                <div class="rounded-2xl border border-slate-100 px-4 py-3" style="border-left:4px solid <?= h($c['color']) ?>">
                  <p class="text-xs font-black text-slate-800 mb-2"><?= h($c['name']) ?></p>
                  <?php foreach ($byCat[$c['name']] as $el => $v): ?>
                    <div class="flex items-start gap-3 mb-2 last:mb-0">
                      <span class="font-outfit font-black text-xs rounded-lg px-2 py-0.5 shrink-0"
                            style="background:<?= h(hm_tier_color((float) $v)) ?>;color:#fff;min-width:30px;text-align:center"><?= (int) $v ?></span>
                      <div class="min-w-0">
                        <p class="text-xs font-bold text-slate-700"><?= h($el) ?></p>
                        <p class="text-xs text-slate-500 leading-relaxed"><?= h($it['rationales'][$el] ?? '') ?></p>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <div>
            <p class="ag-label">Sources (each retrieved by the agent in this run)</p>
            <ul class="space-y-2">
              <?php foreach ($it['sources'] as $s): ?>
                <li class="text-xs leading-relaxed">
                  <a href="<?= h($s['url']) ?>" target="_blank" rel="noopener noreferrer nofollow"
                     class="font-bold underline" style="color:#750B25"><?= h($s['publisher']) ?></a>
                  <?php if ($s['title'] !== ''): ?><span class="text-slate-600"> · <?= h($s['title']) ?></span><?php endif; ?>
                  <?php if ($s['published'] !== ''): ?><span class="text-slate-400"> · <?= h($s['published']) ?></span><?php endif; ?>
                  <span class="block text-slate-400 truncate"><?= h(agent_url_host($s['url'])) ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
            <p class="text-[11px] text-slate-400 mt-3 leading-relaxed">
              Open the sources before promoting. Check that they say what the summary says, that dates fall in the
              monitoring window, and that no private individual is identified.
            </p>
          </div>
        </div>

        <?php if ($it['status'] !== 'PROPOSED'): ?>
          <p class="mt-5 text-xs text-slate-500 px-3 py-2 rounded-xl bg-slate-50 border border-slate-100">
            <strong><?= h(ucfirst(strtolower($it['status']))) ?></strong>
            <?php if (!empty($it['reviewerName'])): ?> by <?= h($it['reviewerName']) ?><?php endif; ?>
            <?php if (!empty($it['reviewedAt'])): ?> · <?= h(format_date($it['reviewedAt'], 'j M Y, H:i')) ?><?php endif; ?>
            <?php if (!empty($it['reviewNotes'])): ?> · “<?= h($it['reviewNotes']) ?>”<?php endif; ?>
            <?php if ($it['status'] === 'PROMOTED' && $it['postId']): ?>
              · <a class="font-bold underline" style="color:#750B25" href="/admin/content/<?= h(rawurlencode($it['postId'])) ?>/edit">Open the report</a>
            <?php endif; ?>
          </p>
        <?php endif; ?>

        <div class="mt-5 pt-5 border-t border-slate-100 flex flex-wrap items-start gap-3">
          <?php if ($it['status'] === 'PROPOSED'): ?>
            <form method="POST">
              <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
              <input type="hidden" name="action" value="promote">
              <input type="hidden" name="item" value="<?= h($it['id']) ?>">
              <button type="submit" class="ag-btn text-white" style="background:#750B25"
                      title="Creates a DRAFT report with these scores and opens it for editing">Create draft report</button>
            </form>
            <form method="POST" class="flex flex-wrap items-start gap-2 grow">
              <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
              <input type="hidden" name="item" value="<?= h($it['id']) ?>">
              <input type="hidden" name="tab" value="<?= h($tab) ?>">
              <input type="hidden" name="country_filter" value="<?= h($filterCountry) ?>">
              <input type="text" name="notes" maxlength="1000" class="ag-input" style="max-width:360px"
                     placeholder="Reason (required to reject)">
              <button type="submit" name="action" value="reject" class="ag-btn" style="background:#ED1C2412;color:#ED1C24">Reject</button>
              <button type="submit" name="action" value="duplicate" class="ag-btn" style="background:#f1f5f9;color:#475569">Duplicate</button>
            </form>
          <?php elseif ($it['status'] !== 'PROMOTED'): ?>
            <form method="POST">
              <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
              <input type="hidden" name="action" value="restore">
              <input type="hidden" name="item" value="<?= h($it['id']) ?>">
              <input type="hidden" name="tab" value="<?= h($tab) ?>">
              <button type="submit" class="ag-btn" style="background:#f1f5f9;color:#475569">Return to queue</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
  </div>

<?php elseif ($tab === 'runs'): ?>
  <!-- ── Run log ────────────────────────────────────────────────── -->
  <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-x-auto">
    <table class="w-full text-sm" style="border-collapse:collapse;min-width:980px">
      <thead style="background:#f8fafc">
        <tr class="text-left text-[10px] font-black uppercase tracking-wider text-slate-400">
          <th class="px-4 py-3">Started</th><th class="px-4 py-3">Country</th><th class="px-4 py-3">Trigger</th>
          <th class="px-4 py-3">Status</th><th class="px-4 py-3">Findings</th><th class="px-4 py-3">Searches</th>
          <th class="px-4 py-3">Tokens in / out</th><th class="px-4 py-3">Details</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$runs): ?>
          <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400">No runs yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($runs as $r):
            $sc = ['OK' => '#059669', 'FAILED' => '#ED1C24', 'RUNNING' => '#9a5b0f'][$r['status']] ?? '#64748b'; ?>
          <tr class="border-t border-slate-100 align-top">
            <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600"><?= h(format_date($r['startedAt'], 'j M Y, H:i')) ?></td>
            <td class="px-4 py-3 font-bold text-slate-800"><?= h($r['country']) ?></td>
            <td class="px-4 py-3 text-xs text-slate-600"><?= h(match ($r['triggeredBy']) { 'MANUAL' => 'Manual' . (!empty($r['starterName']) ? ' (' . $r['starterName'] . ')' : ''), 'REPORT' => 'Report narrative', 'PREFLIGHT' => 'Readiness check', 'ARTICLE' => 'Article writer', 'VIDEO' => 'Video search', default => 'Scheduled' }) ?></td>
            <td class="px-4 py-3"><span class="ag-chip" style="background:<?= $sc ?>15;color:<?= $sc ?>"><?= h($r['status']) ?></span></td>
            <td class="px-4 py-3 text-xs text-slate-600 whitespace-nowrap">
              <?= (int) $r['itemsProposed'] ?> new · <?= (int) $r['itemsDuplicate'] ?> dup · <?= (int) $r['itemsDiscarded'] ?> discarded</td>
            <td class="px-4 py-3 text-xs text-slate-600"><?= (int) $r['webSearches'] ?> / <?= (int) $r['webFetches'] ?> fetch</td>
            <td class="px-4 py-3 text-xs text-slate-600 whitespace-nowrap">
              <?= h(number_format((int) $r['inputTokens'] + (int) $r['cacheReadTokens'] + (int) $r['cacheWriteTokens'])) ?> / <?= h(number_format((int) $r['outputTokens'])) ?></td>
            <td class="px-4 py-3 text-xs text-slate-500 max-w-sm">
              <?php if (!empty($r['servedBy']) && $r['servedBy'] !== $r['model']): ?>
                <span class="block">Served by <?= h($r['servedBy']) ?> (fallback)</span>
              <?php endif; ?>
              <?php if (!empty($r['error'])): ?><span class="block text-red-700"><?= h($r['error']) ?></span><?php endif; ?>
              <?php if (!empty($r['notes'])): ?><span class="block whitespace-pre-line"><?= h($r['notes']) ?></span><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

<?php elseif ($tab === 'settings'):
    $_as = assistant_settings();
    $_auto = agent_auto_status();
    $_ready = $sdkOk && $keyOk;
?>
  <div class="grid lg:grid-cols-2 gap-6" style="max-width:980px">
    <div class="space-y-6">
      <form method="POST" class="tk-card tk-card--pad space-y-5">
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
        <input type="hidden" name="action" value="save_settings">
        <label class="flex items-start gap-3 cursor-pointer">
          <input type="checkbox" name="enabled" value="1" <?= $settings['enabled'] ? 'checked' : '' ?> class="mt-1 w-4 h-4" style="accent-color:#750B25">
          <span>
            <span class="block text-sm font-bold text-slate-800">Agent on</span>
            <span class="block text-xs text-slate-500">It monitors your regions, writes articles and finds YouTube videos by itself. It never publishes.</span>
          </span>
        </label>
        <label class="flex items-start gap-3 cursor-pointer">
          <input type="checkbox" name="assistant_enabled" value="1" <?= $_as['enabled'] ? 'checked' : '' ?> class="mt-1 w-4 h-4" style="accent-color:#750B25">
          <span>
            <span class="block text-sm font-bold text-slate-800">Chat button on the website</span>
            <span class="block text-xs text-slate-500">Welcomes visitors and answers in English or Kiswahili, from your published work only.</span>
          </span>
        </label>
        <button type="submit" class="ag-btn text-white" style="background:#750B25">Save</button>
      </form>
    </div>

    <div class="space-y-6">
      <div class="tk-card tk-card--pad space-y-4">
        <h2 class="font-outfit font-black text-base text-slate-900">Claude API key</h2>
        <p class="text-xs text-slate-500 leading-relaxed">
          <?php if ($savedKey !== ''): ?>
            A key is saved: <span class="font-bold text-slate-700"><?= h(agent_key_hint($savedKey)) ?></span>. Enter a new one to replace it.
          <?php elseif ($keyOk): ?>
            A key is set on the server. A key saved here will be used instead.
          <?php else: ?>
            Paste your key from the Anthropic Console. It is stored encrypted and never shown again in full.
          <?php endif; ?>
        </p>
        <form method="POST" class="space-y-3">
          <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
          <input type="hidden" name="action" value="save_api_key">
          <label class="ag-label" for="ag-key">API key</label>
          <input id="ag-key" type="password" name="api_key" <?= $keyOk ? '' : 'required' ?> autocomplete="new-password" spellcheck="false" class="ag-input" placeholder="<?= $keyOk ? 'Leave blank to keep the current key' : 'sk-ant-...' ?>">
          <label class="ag-label" for="ag-ws">Workspace ID <span style="text-transform:none;letter-spacing:0;font-weight:600">(optional)</span></label>
          <input id="ag-ws" type="text" name="workspace_id" spellcheck="false" class="ag-input" value="<?= h($isSuper ? agent_workspace_id() : '') ?>" placeholder="wrkspc_... (found automatically when possible)">
          <p class="text-xs text-slate-400">Leave this blank and the system finds it for you.</p>
          <button type="submit" class="ag-btn text-white w-full justify-center" style="background:#750B25">Save and test</button>
        </form>
        <?php if ($savedKey !== ''): ?>
          <form method="POST">
            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
            <input type="hidden" name="action" value="remove_api_key">
            <button type="submit" class="text-xs font-bold underline" style="color:#ED1C24;background:none;border:0;cursor:pointer">Remove saved key</button>
          </form>
        <?php endif; ?>
      </div>

      <div class="tk-card tk-card--pad space-y-3 text-sm text-slate-700">
        <h2 class="font-outfit font-black text-base text-slate-900">Status</h2>
        <?php if (!$sdkOk): ?>
          <p>The Claude software still has to be installed on the server before the agent can work.</p>
        <?php elseif (!$keyOk): ?>
          <p>Add your Claude API key to finish.</p>
        <?php endif; ?>
        <?php if ($_auto['running']): ?>
          <p>The hourly job is running. Last checked in <?= h(format_relative_time($_auto['lastTick'])) ?>.</p>
        <?php else: ?>
          <p>To let it run by itself, add this one line in cPanel &rarr; Cron Jobs, once:</p>
          <code class="block p-2 rounded-lg bg-slate-50 break-all text-xs">0 * * * * /usr/local/bin/php <?= h(dirname(__DIR__)) ?>/cron/agent-auto.php</code>
        <?php endif; ?>
        <form method="POST" onsubmit="var b=this.querySelector('button');b.disabled=true;b.textContent='Working… this can take a few minutes';">
          <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
          <input type="hidden" name="action" value="run_now">
          <button type="submit" class="ag-btn w-full justify-center" style="background:#E7952A;color:#0D0102" <?= $_ready ? '' : 'disabled' ?>>Run now</button>
        </form>
        <p class="text-xs text-slate-500">Software version <?= h(AGENT_CODE_VERSION) ?></p>
      </div>
    </div>
  </div>
<?php endif; ?>

</main>
</div>
</div>
</body>
</html>
