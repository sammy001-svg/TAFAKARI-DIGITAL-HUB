<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/agent.php';

$me      = require_auth();
$isSuper = is_super_admin();
$uid     = $me['id'];

ensure_settings_table();
ensure_agent_tables();

if (empty($_SESSION['agent_csrf'])) $_SESSION['agent_csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['agent_csrf'];

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
                $lines = fn(string $k) => array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($_POST[$k] ?? '')))));
                $canon = [];
                foreach (african_countries() as $c) $canon[mb_strtolower($c)] = $c;
                $countries = []; $unknown = [];
                foreach ($lines('countries') as $c) {
                    if (isset($canon[mb_strtolower($c)])) { if (!in_array($canon[mb_strtolower($c)], $countries, true)) $countries[] = $canon[mb_strtolower($c)]; }
                    else $unknown[] = $c;
                }
                $allowed = agent_clean_domains($lines('allowed_domains'));
                $blocked = agent_clean_domains($lines('blocked_domains'));
                $budget  = (float) str_replace(',', '.', (string) ($_POST['monthly_budget'] ?? '0'));
                if ($budget < 0 || $budget > 10000) $back('settings', 'Monthly budget must be between 0 and 10,000 USD.', true);
                if ($allowed && $blocked)             $back('settings', 'Use either allowed domains or blocked domains, not both.', true);

                set_setting('agent_enabled',         isset($_POST['enabled']) ? '1' : '0', $uid);
                set_setting('agent_countries',       json_encode($countries, JSON_UNESCAPED_UNICODE), $uid);
                set_setting('agent_lookback_days',   (string) max(1, min(31, (int) ($_POST['lookback_days'] ?? 14))), $uid);
                set_setting('agent_max_searches',    (string) max(1, min(20, (int) ($_POST['max_searches'] ?? 6))), $uid);
                set_setting('agent_max_fetches',     (string) max(0, min(20, (int) ($_POST['max_fetches'] ?? 4))), $uid);
                set_setting('agent_max_items',       (string) max(1, min(20, (int) ($_POST['max_items'] ?? 8))), $uid);
                set_setting('agent_effort',          in_array($_POST['effort'] ?? '', ['low', 'medium', 'high', 'xhigh'], true) ? $_POST['effort'] : 'high', $uid);
                set_setting('agent_monthly_budget',  (string) round($budget, 2), $uid);
                set_setting('agent_allowed_domains', json_encode($allowed), $uid);
                set_setting('agent_blocked_domains', json_encode($blocked), $uid);
                $back('settings', $unknown
                    ? 'Settings saved. Not recognised, and left out: ' . implode(', ', $unknown) . '.'
                    : 'Settings saved.', (bool) $unknown);

            case 'run_now':
                if (!$isSuper) $back('queue', 'Only a super admin can start a run.', true);
                $country = (string) ($_POST['run_country'] ?? '');
                @set_time_limit(900);
                ignore_user_abort(true);   // finish and log the run even if the browser gives up
                session_write_close();     // a run takes minutes: don't lock the user's other tabs
                $r = agent_run_country($country, 'MANUAL', $uid);
                session_start();
                $back($r['status'] === 'OK' ? 'queue' : 'runs',
                    sprintf('%s: %s %s (estimated cost $%.4f)', $country, $r['status'], $r['message'], $r['cost']),
                    $r['status'] !== 'OK');

            default:
                $back($retTab, 'Unknown action.', true);
        }
    } catch (Throwable $e) {
        error_log('[admin/agent] ' . $action . ': ' . $e->getMessage());
        $back($retTab, $e->getMessage(), true, $keep);
    }
}

$flash = $_SESSION['agent_flash'] ?? null;
unset($_SESSION['agent_flash']);

// ── Data ──────────────────────────────────────────────────────────
$settings = agent_settings();
$sdkOk    = agent_sdk_available();
$keyOk    = agent_api_key() !== '';
$spent    = agent_month_spend();

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
<body class="antialiased font-inter" style="background:#F4F6F8">
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
  <div class="mb-5 px-4 py-3 rounded-2xl text-sm font-medium border <?= $flash['err'] ? 'text-red-800 bg-red-50 border-red-200' : 'text-emerald-800 bg-emerald-50 border-emerald-200' ?>">
    <?= h($flash['msg']) ?>
  </div>
<?php endif; ?>

<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
  <div>
    <h1 class="font-outfit font-black text-2xl text-slate-900">Tafakari Agent</h1>
    <p class="text-sm text-slate-500 mt-1 max-w-2xl leading-relaxed">
      The agent searches public sources and proposes findings with intensity scores. Nothing here is public:
      a finding reaches the heat map only after an analyst turns it into a report and a super admin approves it.
    </p>
  </div>
  <div class="flex flex-wrap gap-2">
    <span class="ag-chip" style="<?= $settings['enabled'] ? 'background:#05966915;color:#059669' : 'background:#f1f5f9;color:#64748b' ?>">
      <?= $settings['enabled'] ? 'Scheduled runs on' : 'Scheduled runs off' ?></span>
    <?php if ($isSuper): ?>
      <span class="ag-chip" style="<?= $sdkOk && $keyOk ? 'background:#05966915;color:#059669' : 'background:#ED1C2415;color:#ED1C24' ?>">
        <?= !$sdkOk ? 'SDK not installed' : ($keyOk ? 'API key configured' : 'API key missing') ?></span>
      <span class="ag-chip" style="background:#E7952A1f;color:#9a5b0f">
        <?= h(sprintf('$%.2f of $%.2f this month (est.)', $spent, $settings['monthly_budget'])) ?></span>
    <?php endif; ?>
  </div>
</div>

<!-- Tabs -->
<div class="flex flex-wrap gap-1 mb-6 p-1 rounded-2xl w-fit" style="background:rgba(0,0,0,.06)">
  <?php
  $tabLabels = [
      'queue'    => 'Review queue (' . $counts['PROPOSED'] . ')',
      'promoted' => 'Promoted (' . $counts['PROMOTED'] . ')',
      'rejected' => 'Rejected (' . ($counts['REJECTED'] + $counts['DUPLICATE']) . ')',
      'runs'     => 'Run log',
      'settings' => 'Settings',
  ];
  foreach ($validTabs as $t): ?>
    <a href="/admin/agent?<?= h(http_build_query(array_filter(['tab' => $t, 'country' => $filterCountry]))) ?>"
       class="px-4 py-2 rounded-xl text-sm font-bold whitespace-nowrap"
       style="<?= $t === $tab ? 'background:#750B25;color:#fff' : 'color:#64748b' ?>"><?= h($tabLabels[$t]) ?></a>
  <?php endforeach; ?>
</div>

<?php if (isset($statusFor[$tab])): ?>
  <!-- ── Findings ───────────────────────────────────────────────── -->
  <form method="GET" class="flex flex-wrap items-center gap-2 mb-5">
    <input type="hidden" name="tab" value="<?= h($tab) ?>">
    <select name="country" onchange="this.form.submit()" class="ag-input" style="width:auto;min-width:200px">
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
          <th class="px-4 py-3">Tokens in / out</th><th class="px-4 py-3">Est. cost</th><th class="px-4 py-3">Details</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$runs): ?>
          <tr><td colspan="9" class="px-4 py-10 text-center text-slate-400">No runs yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($runs as $r):
            $sc = ['OK' => '#059669', 'FAILED' => '#ED1C24', 'RUNNING' => '#9a5b0f'][$r['status']] ?? '#64748b'; ?>
          <tr class="border-t border-slate-100 align-top">
            <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600"><?= h(format_date($r['startedAt'], 'j M Y, H:i')) ?></td>
            <td class="px-4 py-3 font-bold text-slate-800"><?= h($r['country']) ?></td>
            <td class="px-4 py-3 text-xs text-slate-600"><?= h(match ($r['triggeredBy']) { 'MANUAL' => 'Manual' . (!empty($r['starterName']) ? ' (' . $r['starterName'] . ')' : ''), 'REPORT' => 'Report narrative', default => 'Scheduled' }) ?></td>
            <td class="px-4 py-3"><span class="ag-chip" style="background:<?= $sc ?>15;color:<?= $sc ?>"><?= h($r['status']) ?></span></td>
            <td class="px-4 py-3 text-xs text-slate-600 whitespace-nowrap">
              <?= (int) $r['itemsProposed'] ?> new · <?= (int) $r['itemsDuplicate'] ?> dup · <?= (int) $r['itemsDiscarded'] ?> discarded</td>
            <td class="px-4 py-3 text-xs text-slate-600"><?= (int) $r['webSearches'] ?> / <?= (int) $r['webFetches'] ?> fetch</td>
            <td class="px-4 py-3 text-xs text-slate-600 whitespace-nowrap">
              <?= h(number_format((int) $r['inputTokens'] + (int) $r['cacheReadTokens'] + (int) $r['cacheWriteTokens'])) ?> / <?= h(number_format((int) $r['outputTokens'])) ?></td>
            <td class="px-4 py-3 text-xs font-bold text-slate-700">$<?= h(number_format((float) $r['costUsd'], 4)) ?></td>
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
  <p class="text-xs text-slate-400 mt-3">Costs are estimates at Claude Opus 5.5 list prices plus $0.01 per web search. The Anthropic Console bill is authoritative.</p>

<?php elseif ($tab === 'settings'): ?>
  <!-- ── Settings + run now ─────────────────────────────────────── -->
  <div class="grid xl:grid-cols-3 gap-6">
    <form method="POST" class="xl:col-span-2 bg-white rounded-3xl border border-slate-100 shadow-sm p-6 space-y-6">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="hidden" name="action" value="save_settings">
      <h2 class="font-outfit font-black text-base text-slate-900">Settings</h2>

      <label class="flex items-start gap-3 cursor-pointer">
        <input type="checkbox" name="enabled" value="1" <?= $settings['enabled'] ? 'checked' : '' ?> class="mt-1 w-4 h-4" style="accent-color:#750B25">
        <span>
          <span class="block text-sm font-bold text-slate-800">Run on schedule</span>
          <span class="block text-xs text-slate-500">When on, the cron job runs the agent for the next country in the list each time it fires.
            Findings still need review; this never publishes anything.</span>
        </span>
      </label>

      <div class="grid md:grid-cols-2 gap-6">
        <div>
          <label class="ag-label">Monitored countries (one per line, in rotation order)</label>
          <textarea name="countries" rows="8" class="ag-input"><?= h(implode("\n", $settings['countries'])) ?></textarea>
        </div>
        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-3">
            <div><label class="ag-label">Look back (days)</label>
              <input type="number" name="lookback_days" min="1" max="31" value="<?= (int) $settings['lookback_days'] ?>" class="ag-input"></div>
            <div><label class="ag-label">Findings per run</label>
              <input type="number" name="max_items" min="1" max="20" value="<?= (int) $settings['max_items'] ?>" class="ag-input"></div>
            <div><label class="ag-label">Web searches per run</label>
              <input type="number" name="max_searches" min="1" max="20" value="<?= (int) $settings['max_searches'] ?>" class="ag-input"></div>
            <div><label class="ag-label">Page reads per run</label>
              <input type="number" name="max_fetches" min="0" max="20" value="<?= (int) $settings['max_fetches'] ?>" class="ag-input"></div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div><label class="ag-label">Monthly budget (USD)</label>
              <input type="number" name="monthly_budget" min="0" max="10000" step="1" value="<?= h((string) $settings['monthly_budget']) ?>" class="ag-input"></div>
            <div><label class="ag-label">Analysis depth</label>
              <select name="effort" class="ag-input">
                <?php foreach (['low' => 'Low (cheapest)', 'medium' => 'Medium', 'high' => 'High (recommended)', 'xhigh' => 'Extra high'] as $v => $l): ?>
                  <option value="<?= $v ?>" <?= $settings['effort'] === $v ? 'selected' : '' ?>><?= h($l) ?></option>
                <?php endforeach; ?>
              </select></div>
          </div>
          <p class="text-xs text-slate-400 leading-relaxed">When the estimated spend for the month reaches the budget, runs stop until the 1st. Set it to 0 to stop all runs.</p>
        </div>
      </div>

      <div class="grid md:grid-cols-2 gap-6">
        <div>
          <label class="ag-label">Allowed source domains (optional)</label>
          <textarea name="allowed_domains" rows="5" class="ag-input" placeholder="reliefweb.int&#10;un.org&#10;nation.africa"><?= h(implode("\n", $settings['allowed_domains'])) ?></textarea>
          <p class="text-xs text-slate-400 mt-1.5">If set, the agent searches and reads only these sites (and their subdomains).</p>
        </div>
        <div>
          <label class="ag-label">Blocked source domains (optional)</label>
          <textarea name="blocked_domains" rows="5" class="ag-input"><?= h(implode("\n", $settings['blocked_domains'])) ?></textarea>
          <p class="text-xs text-slate-400 mt-1.5">Used only when no allowed domains are set. Use one list or the other.</p>
        </div>
      </div>

      <button type="submit" class="ag-btn text-white" style="background:#750B25">Save settings</button>
    </form>

    <div class="space-y-6">
      <form method="POST" class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 space-y-4"
            onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='Running… this can take a few minutes';">
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
        <input type="hidden" name="action" value="run_now">
        <h2 class="font-outfit font-black text-base text-slate-900">Run now</h2>
        <p class="text-xs text-slate-500 leading-relaxed">Runs the agent once for one country, whether or not scheduled runs are on.
          It counts against the monthly budget and usually takes one to five minutes.</p>
        <select name="run_country" class="ag-input">
          <?php foreach ($settings['countries'] ?: african_countries() as $c): ?>
            <option value="<?= h($c) ?>"><?= h($c) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="ag-btn text-white w-full justify-center" style="background:#E7952A;color:#0D0102"
                <?= ($sdkOk && $keyOk) ? '' : 'disabled title="Install the SDK and set the API key first"' ?>>Run the agent</button>
      </form>

      <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 text-xs text-slate-600 leading-relaxed space-y-3">
        <h2 class="font-outfit font-black text-base text-slate-900">Server setup</h2>
        <p><strong>1. SDK.</strong> <?= $sdkOk ? 'Installed.' : 'Not installed: run <code>composer install --no-dev</code> in the site root.' ?></p>
        <p><strong>2. API key.</strong> <?= $keyOk ? 'Configured.' : 'Set <code>ANTHROPIC_API_KEY</code> as an environment variable, or add <code>define(\'ANTHROPIC_API_KEY\', \'…\');</code> to config.php on the server. Never commit it.' ?></p>
        <p><strong>3. Schedule.</strong> cPanel → Cron Jobs:<br>
          <code class="block mt-1 p-2 rounded-lg bg-slate-50 break-all">php <?= h(dirname(__DIR__)) ?>/cron/agent-run.php</code></p>
        <p class="text-slate-400">Model: <?= h(AGENT_MODEL) ?>, with automatic fallback if a request is declined.</p>
      </div>
    </div>
  </div>
<?php endif; ?>

</main>
</div>
</div>
</body>
</html>
