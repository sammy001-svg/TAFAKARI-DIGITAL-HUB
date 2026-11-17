<?php
declare(strict_types=1);
/**
 * Tafakari Agent - runs by itself.
 *
 * One hourly job calls agent_auto_tick(), which does one piece of work: the
 * day's report drafts, a YouTube search, an article, or a monitoring run.
 * Daily limits keep the volume steady.
 * Nothing here publishes: everything lands in a queue for people to approve.
 */

require_once __DIR__ . '/reports.php';
require_once __DIR__ . '/agent-articles.php';
require_once __DIR__ . '/agent-videos.php';
require_once __DIR__ . '/agent-updates.php';
require_once __DIR__ . '/agent-health.php';

const AGENT_AUTO_UPDATES_PER_DAY  = 2;   // country monitoring updates: every monitored country once per cycle
const AGENT_AUTO_ARTICLES_PER_DAY = 2;
const AGENT_AUTO_FINDINGS_PER_DAY = 4;

/** How many runs of each kind have started today. */
function agent_auto_today(): array {
    $out = ['ARTICLE' => 0, 'CRON' => 0, 'VIDEO' => 0, 'UPDATE' => 0, 'QUARTER' => 0];
    try {
        $st = db()->prepare('SELECT triggeredBy, COUNT(*) AS n FROM AgentRun WHERE startedAt >= ? GROUP BY triggeredBy');
        $st->execute([date('Y-m-d 00:00:00')]);
        foreach ($st->fetchAll() as $r) $out[$r['triggeredBy']] = (int) $r['n'];
    } catch (Throwable $e) {}
    return $out;
}

function agent_auto_setting(string $key): string {
    try {
        $st = db()->prepare('SELECT `value` FROM SiteSetting WHERE `key` = ? LIMIT 1');
        $st->execute([$key]);
        return (string) $st->fetchColumn();
    } catch (Throwable $e) {
        return '';
    }
}

/** What to do next, most important first. */
function agent_auto_plan(bool $force): array {
    $today = agent_auto_today();
    $plan  = [];
    if (agent_auto_setting('agent_auto_reports_date') !== date('Y-m-d')) $plan[] = 'reports';
    if ($today['QUARTER'] === 0) $plan[] = 'quarterly';
    if ($force || $today['UPDATE'] < AGENT_AUTO_UPDATES_PER_DAY) $plan[] = 'update';
    if ($today['VIDEO'] === 0) $plan[] = 'videos';
    $article = $force || $today['ARTICLE'] < AGENT_AUTO_ARTICLES_PER_DAY;
    $finding = $force || $today['CRON'] < AGENT_AUTO_FINDINGS_PER_DAY;
    // One article for every two monitoring runs, through the day
    $order = $today['CRON'] >= $today['ARTICLE'] * 2 ? ['article' => $article, 'finding' => $finding] : ['finding' => $finding, 'article' => $article];
    foreach ($order as $task => $allowed) if ($allowed) $plan[] = $task;
    return $plan;
}

/**
 * Do one piece of work, looking after the agent's health around it: runs the
 * server killed are closed, a paused agent waits, and the outcome is recorded
 * so repeated problems pause it and alert the super admins once.
 *
 * @param bool $force true for the "Run now" button: ignores the off switch, a pause and daily limits
 * @return array{status:string,task:?string,message:string}
 */
function agent_auto_tick(bool $force = false, mixed $transporter = null): array {
    if (!$force) set_setting('agent_auto_last_tick', date('Y-m-d H:i:s'));
    agent_health_cleanup();
    if (!$force && !agent_settings()['enabled']) return ['status' => 'IDLE', 'task' => null, 'message' => 'The agent is switched off.'];
    $h = agent_health_status();
    if (!$force && $h['paused']) {
        return ['status' => 'IDLE', 'task' => null, 'message' => 'Paused until ' . $h['until'] . '. ' . $h['reason']];
    }
    $r = agent_auto_work($force, $transporter);
    agent_health_record($r);
    return $r;
}

/** The work itself: the first task in the plan that has something to do. */
function agent_auto_work(bool $force, mixed $transporter): array {
    $settings = agent_settings();
    if (!agent_sdk_available())                 return ['status' => 'FAILED', 'task' => null, 'message' => 'The Claude software (the vendor folder) is missing from the server. Deploy the latest code from git.'];
    if (agent_api_key() === '')                 return ['status' => 'FAILED', 'task' => null, 'message' => 'Add your Claude API key in Tafakari Agent > Settings.'];
    ensure_agent_tables();
    ensure_agent_article_table();
    ensure_agent_video_table();
    ensure_agent_update_table();
    if ($transporter === null) agent_ensure_workspace();

    foreach (agent_auto_plan($force) as $task) {
        switch ($task) {
            case 'reports':
                set_setting('agent_auto_reports_date', date('Y-m-d'));
                $lines = [];
                $ok = report_generate_due(true, function (string $m) use (&$lines) { $lines[] = $m; }, $transporter);
                return ['status' => $ok ? 'OK' : 'FAILED', 'task' => $task, 'message' => 'Report drafts checked. ' . implode(' ', $lines)];

            case 'quarterly':
                // One country's quarterly update a day, once the quarter has closed and its bi-weekly updates were approved
                $q = agent_update_next_quarterly();
                if ($q === null) break;
                $r = agent_update_quarterly_run($q['country'], $q['quarter'], null, $transporter);
                return ['status' => $r['status'] === 'HELD' ? 'OK' : $r['status'], 'task' => $task, 'message' => $r['message']];

            case 'update':
                // Every monitored country gets its monitoring update once per cycle
                $c = agent_update_next_country();
                if ($c === null) break;
                $r = agent_update_run($c, null, $transporter);
                return ['status' => $r['status'] === 'HELD' ? 'OK' : $r['status'], 'task' => $task, 'message' => $r['message']];

            case 'videos':
                $g = agent_video_next_group();
                if ($g === null) break;
                $r = agent_video_run($g, $transporter);
                return ['status' => $r['status'], 'task' => $task, 'message' => $r['message']];

            case 'article':
                // A combination with a draft waiting is passed over, so unreviewed work does not pile up
                $pairs = agent_article_pairs();
                for ($i = 0; $i < count($pairs); $i++) {
                    $p = agent_article_next_pair();
                    if ($p === null) break;
                    $r = agent_article_run($p, null, $transporter);
                    if ($r['status'] !== 'HELD') return ['status' => $r['status'], 'task' => $task, 'message' => $r['message']];
                }
                break;

            case 'finding':
                $c = agent_next_country($settings);
                if ($c === null) break;
                $r = agent_run_country($c, 'CRON', null, $transporter);
                return ['status' => $r['status'], 'task' => $task, 'message' => "{$c}: {$r['message']}"];
        }
    }
    return ['status' => 'IDLE', 'task' => null, 'message' => "Today's work is done."];
}

// ── Running in the background ────────────────────────────────────

/**
 * One piece of work at a time. Returns a lock handle, null when no lock file
 * can be made (carry on), or false when work is already under way. The hourly
 * job uses the same lock, and a run recorded as running in the database
 * counts too, since the web server and the command line may not share files.
 */
function agent_auto_lock(): mixed {
    try {
        $st = db()->prepare("SELECT COUNT(*) FROM AgentRun WHERE status='RUNNING' AND startedAt >= ?");
        $st->execute([date('Y-m-d H:i:s', time() - 20 * 60)]);
        if ((int) $st->fetchColumn() > 0) return false;
    } catch (Throwable $e) {}
    $fh = @fopen(sys_get_temp_dir() . '/tafakari-auto-' . md5(dirname(__DIR__)) . '.lock', 'c');
    if (!$fh) return null;
    if (!flock($fh, LOCK_EX | LOCK_NB)) { fclose($fh); return false; }
    return $fh;
}

function agent_auto_unlock(mixed $fh): void {
    if (is_resource($fh)) { flock($fh, LOCK_UN); fclose($fh); }
}

/**
 * Send the browser on its way now, and keep working after the response is
 * closed, so long research never hangs the page or meets the web server's
 * request time limit.
 */
function agent_respond_and_continue(string $location): void {
    ignore_user_abort(true);
    @set_time_limit(900);
    while (ob_get_level() > 0) @ob_end_clean();
    if (!headers_sent()) {
        header('Location: ' . $location, true, 303);
        header('Connection: close');
        header('Content-Length: 0');
    }
    if (function_exists('fastcgi_finish_request')) { fastcgi_finish_request(); return; }
    if (function_exists('litespeed_finish_request')) { litespeed_finish_request(); return; }
    flush();
}

/** What Run now will do first, in words. */
function agent_auto_next_label(bool $force): string {
    $task = agent_auto_plan($force)[0] ?? null;
    return [
        'reports'   => 'checking the analysis report drafts',
        'quarterly' => 'writing a quarterly country update',
        'update'    => 'researching the next country monitoring update',
        'videos'    => 'looking for videos',
        'article'   => 'writing an article',
        'finding'   => 'looking for new developments',
    ][$task] ?? 'looking for work to do';
}

/** @return array{lastTick:string,running:bool} running = the hourly job has fired recently */
function agent_auto_status(): array {
    $last = agent_auto_setting('agent_auto_last_tick');
    return ['lastTick' => $last, 'running' => $last !== '' && strtotime($last) >= time() - 3 * 3600];
}
