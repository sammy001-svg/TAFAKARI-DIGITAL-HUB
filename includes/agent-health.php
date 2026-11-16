<?php
declare(strict_types=1);
/**
 * Tafakari Agent - looking after itself.
 *
 * The agent runs unattended, so it has to notice when it cannot work:
 *  - a run the server killed (timeout, restart) is closed, not left "running";
 *  - an account problem (no API credits, a rejected key) pauses the agent
 *    instead of failing every hour, and the super admins get one email;
 *  - temporary problems (rate limits, timeouts, an overloaded API) are
 *    retried next hour, and only after several in a row does it pause and
 *    tell anyone;
 *  - the first successful run after a pause clears it and says so.
 *
 * State is kept in SiteSetting, read fresh (get_setting() caches per request).
 * Requires agent.php.
 */

const AGENT_HEALTH_STALE_MINUTES    = 150;  // longer than any real run (10 calls x 10 minutes, worst case)
const AGENT_HEALTH_ACCOUNT_PAUSE    = 6;    // hours, after an account problem
const AGENT_HEALTH_TRANSIENT_PAUSE  = 2;    // hours, after repeated temporary problems
const AGENT_HEALTH_TRANSIENT_LIMIT  = 3;    // failures in a row before pausing
const AGENT_HEALTH_ALERT_AFTER      = 6;    // temporary failures in a row before emailing

/**
 * What kind of problem a run's message describes.
 *
 * account:   only a person can fix it (credits, key, permissions, setup)
 * transient: likely to clear by itself (rate limit, timeout, network, overload)
 * content:   the run worked but produced nothing usable; not a health problem
 */
function agent_health_classify(string $message): string {
    $m = strtolower($message);
    foreach (['no api credits', 'credit balance', 'api key was rejected', 'not permitted to use', 'needs a workspace', 'does not recognise the workspace',
              'claude software', 'add your claude api key', 'invalid x-api-key', 'permission'] as $p) {
        if (str_contains($m, $p)) return 'account';
    }
    foreach (['rate limit', 'timed out', 'could not reach', 'overloaded', 'api error', 'stopped unexpectedly', 'connection', 'internal server error', '529', '503', '502'] as $p) {
        if (str_contains($m, $p)) return 'transient';
    }
    return 'content';
}

/** Close runs the server ended without letting them finish. Returns how many. */
function agent_health_cleanup(): int {
    try {
        $st = db()->prepare("UPDATE AgentRun SET status='FAILED', finishedAt=?, error=? WHERE status='RUNNING' AND startedAt < ?");
        $st->execute([date('Y-m-d H:i:s'), 'The run stopped unexpectedly: the server ended it before it finished (usually a time limit or a restart). The next run carries on.',
                      date('Y-m-d H:i:s', time() - AGENT_HEALTH_STALE_MINUTES * 60)]);
        return $st->rowCount();
    } catch (Throwable $e) {
        return 0;
    }
}

/** The agent's current health, for the admin pages. */
function agent_health_status(): array {
    $until = agent_setting_fresh('agent_health_pause_until');
    $paused = $until !== '' && strtotime($until) > time();
    return [
        'paused'    => $paused,
        'until'     => $paused ? $until : null,
        'reason'    => $paused ? agent_setting_fresh('agent_health_reason') : '',
        'kind'      => agent_setting_fresh('agent_health_kind'),
        'failures'  => (int) agent_setting_fresh('agent_health_failures'),
        'lastOk'    => agent_setting_fresh('agent_health_last_ok'),
        'lastError' => agent_setting_fresh('agent_health_last_error'),
    ];
}

/** Clear any pause, for example after the API key is saved again. */
function agent_health_reset(): void {
    foreach (['agent_health_pause_until', 'agent_health_reason', 'agent_health_kind', 'agent_health_alerted'] as $k) set_setting($k, '');
    set_setting('agent_health_failures', '0');
}

/**
 * Learn from one piece of work: count failures, pause when needed, alert once,
 * and clear everything on success.
 *
 * @param array{status:string,task:?string,message:string} $r
 */
function agent_health_record(array $r): void {
    if ($r['status'] === 'OK') {
        $wasPaused = agent_setting_fresh('agent_health_alerted') !== '';
        set_setting('agent_health_last_ok', date('Y-m-d H:i:s'));
        if ((int) agent_setting_fresh('agent_health_failures') > 0 || agent_setting_fresh('agent_health_pause_until') !== '') agent_health_reset();
        if ($wasPaused) {
            agent_mail_super_admins('Tafakari Agent is working again',
                "The Tafakari Agent has completed a piece of work successfully, so it is running normally again.\n\nLatest: " . $r['message'] . "\n");
        }
        return;
    }
    if ($r['status'] !== 'FAILED') return;

    $kind = agent_health_classify($r['message']);
    if ($kind === 'content') return;
    $n = (int) agent_setting_fresh('agent_health_failures') + 1;
    set_setting('agent_health_failures', (string) $n);
    set_setting('agent_health_last_error', date('Y-m-d H:i:s') . ' · ' . mb_substr($r['message'], 0, 500));

    $pauseHours = 0;
    if ($kind === 'account') $pauseHours = AGENT_HEALTH_ACCOUNT_PAUSE;
    elseif ($n >= AGENT_HEALTH_TRANSIENT_LIMIT) $pauseHours = AGENT_HEALTH_TRANSIENT_PAUSE;
    if ($pauseHours > 0) {
        set_setting('agent_health_pause_until', date('Y-m-d H:i:s', time() + $pauseHours * 3600));
        set_setting('agent_health_reason', mb_substr($r['message'], 0, 500));
        set_setting('agent_health_kind', $kind);
    }

    // One email per problem, not one per hour
    $alertKey = $kind . ':' . substr(sha1(agent_health_classify_key($r['message'])), 0, 12);
    $shouldAlert = $kind === 'account' || $n >= AGENT_HEALTH_ALERT_AFTER;
    if ($shouldAlert && agent_setting_fresh('agent_health_alerted') !== $alertKey) {
        set_setting('agent_health_alerted', $alertKey);
        $base = defined('APP_URL') ? rtrim((string) constant('APP_URL'), '/') : '';
        $body = "The Tafakari Agent cannot do its work at the moment.\n\nReason: " . $r['message'] . "\n\n"
              . ($kind === 'account'
                  ? "This needs a person to fix it. The agent has paused for {$pauseHours} hours and will then try again by itself. Once it is fixed, press Run now in Tafakari Agent > Settings to restart it straight away.\n"
                  : "It has failed {$n} times in a row. This is usually temporary; the agent will keep trying by itself.\n")
              . "\nSettings: {$base}/admin/agent?tab=settings\nActivity: {$base}/admin/agent?tab=runs\n";
        agent_mail_super_admins('Tafakari Agent needs attention', $body);
    }
}

/** The stable part of a message, so the same problem is not alerted twice. */
function agent_health_classify_key(string $message): string {
    return strtolower(preg_replace('/[0-9]+/', '#', mb_substr($message, 0, 120)) ?? '');
}

/** One line for the admin pages, or null when all is well. */
function agent_health_banner(): ?array {
    $h = agent_health_status();
    if ($h['paused']) {
        $mins = max(1, (int) round((strtotime((string) $h['until']) - time()) / 60));
        $in = $mins < 90 ? "about {$mins} minutes" : 'about ' . round($mins / 60) . ' hours';
        return ['level' => $h['kind'] === 'account' ? 'danger' : 'warn',
                'text' => 'The agent has paused and will try again at ' . date('H:i', strtotime((string) $h['until'])) . " (in {$in}). Reason: " . $h['reason']];
    }
    if ($h['failures'] >= 2) {
        return ['level' => 'warn', 'text' => "The agent's last {$h['failures']} runs failed. Latest: " . preg_replace('/^[^·]*· /', '', $h['lastError'])];
    }
    return null;
}
