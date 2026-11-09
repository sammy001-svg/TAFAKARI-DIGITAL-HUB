<?php
declare(strict_types=1);
/**
 * Tafakari Agent - runs by itself.
 *
 * One hourly job calls agent_auto_tick(), which does one piece of work: the
 * day's report drafts, a YouTube search, an article, or a monitoring run.
 * Spending is paced across the month, so the budget is never used up early.
 * Nothing here publishes: everything lands in a queue for people to approve.
 */

require_once __DIR__ . '/reports.php';
require_once __DIR__ . '/agent-articles.php';
require_once __DIR__ . '/agent-videos.php';

const AGENT_AUTO_ARTICLES_PER_DAY = 2;
const AGENT_AUTO_FINDINGS_PER_DAY = 4;

/** True when this month's estimated spend is ahead of an even daily share of the budget. */
function agent_auto_ahead_of_budget(float $spent, float $budget): bool {
    return $spent >= $budget * ((int) date('j') / (int) date('t'));
}

/** How many runs of each kind have started today. */
function agent_auto_today(): array {
    $out = ['ARTICLE' => 0, 'CRON' => 0, 'VIDEO' => 0];
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
    if ($today['VIDEO'] === 0) $plan[] = 'videos';
    $article = $force || $today['ARTICLE'] < AGENT_AUTO_ARTICLES_PER_DAY;
    $finding = $force || $today['CRON'] < AGENT_AUTO_FINDINGS_PER_DAY;
    // One article for every two monitoring runs, through the day
    $order = $today['CRON'] >= $today['ARTICLE'] * 2 ? ['article' => $article, 'finding' => $finding] : ['finding' => $finding, 'article' => $article];
    foreach ($order as $task => $allowed) if ($allowed) $plan[] = $task;
    return $plan;
}

/**
 * Do one piece of work.
 *
 * @param bool $force true for the "Run now" button: ignores the off switch and daily pacing, never the monthly budget
 * @return array{status:string,task:?string,message:string}
 */
function agent_auto_tick(bool $force = false, mixed $transporter = null): array {
    if (!$force) set_setting('agent_auto_last_tick', date('Y-m-d H:i:s'));
    $settings = agent_settings();
    if (!$force && !$settings['enabled'])       return ['status' => 'IDLE', 'task' => null, 'message' => 'The agent is switched off.'];
    if (!agent_sdk_available())                 return ['status' => 'FAILED', 'task' => null, 'message' => 'The Claude software (SDK) is not installed on the server.'];
    if (agent_api_key() === '')                 return ['status' => 'FAILED', 'task' => null, 'message' => 'Add your Claude API key in Tafakari Agent > Settings.'];
    ensure_agent_tables();
    ensure_agent_article_table();
    ensure_agent_video_table();

    $spent = agent_month_spend();
    if ($settings['monthly_budget'] <= 0 || $spent >= $settings['monthly_budget']) {
        return ['status' => 'SKIPPED', 'task' => null, 'message' => 'The monthly budget has been reached. The agent resumes on the 1st.'];
    }
    if (!$force && agent_auto_ahead_of_budget($spent, $settings['monthly_budget'])) {
        return ['status' => 'IDLE', 'task' => null, 'message' => "Today's share of the budget is used. The agent continues tomorrow."];
    }

    foreach (agent_auto_plan($force) as $task) {
        switch ($task) {
            case 'reports':
                set_setting('agent_auto_reports_date', date('Y-m-d'));
                $lines = [];
                $ok = report_generate_due(true, function (string $m) use (&$lines) { $lines[] = $m; }, $transporter);
                return ['status' => $ok ? 'OK' : 'FAILED', 'task' => $task, 'message' => 'Report drafts checked. ' . implode(' ', $lines)];

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

/** @return array{lastTick:string,running:bool} running = the hourly job has fired recently */
function agent_auto_status(): array {
    $last = agent_auto_setting('agent_auto_last_tick');
    return ['lastTick' => $last, 'running' => $last !== '' && strtotime($last) >= time() - 3 * 3600];
}
