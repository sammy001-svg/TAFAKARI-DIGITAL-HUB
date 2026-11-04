<?php
declare(strict_types=1);
/**
 * Tafakari Agent - scheduled run (command line only).
 *
 * Runs the agent for the next country in the rotation set in
 * Admin > Tafakari Agent, or for the country given. Findings go to the review
 * queue; nothing is published.
 *
 *   php cron/agent-run.php                     next country in the rotation
 *   php cron/agent-run.php --country="Sudan"   one specific country
 *   php cron/agent-run.php --countries=3       the next three in the rotation
 *   php cron/agent-run.php --dry-run           print the prompt; no API call
 *
 * cPanel > Cron Jobs, e.g. four times a day:
 *   0 2,8,14,20 * * *  /usr/local/bin/php /home/USER/public_html/cron/agent-run.php >> /home/USER/agent.log 2>&1
 *
 * Exit codes: 0 ok or nothing to do, 1 a run failed, 2 misconfiguration.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
require_once $root . '/config.php';
require_once $root . '/includes/db.php';
require_once $root . '/includes/functions.php';
require_once $root . '/includes/agent.php';

$opt     = getopt('', ['country:', 'countries:', 'dry-run']);
$dryRun  = isset($opt['dry-run']);
$log     = fn(string $m) => fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . '] ' . $m . PHP_EOL);

ensure_agent_tables();
$settings = agent_settings();

if (!$dryRun && !$settings['enabled']) {
    $log('Agent is disabled in Admin > Tafakari Agent. Nothing to do.');
    exit(0);
}
if (!agent_sdk_available()) {
    $log('The Anthropic PHP SDK is not installed. Run "composer install --no-dev" in ' . $root . '.');
    exit(2);
}
if (!$dryRun && agent_api_key() === '') {
    $log('ANTHROPIC_API_KEY is not set (environment variable, or define it in config.php).');
    exit(2);
}

// Pick the countries for this invocation
if (isset($opt['country'])) {
    $countries = [trim((string) $opt['country'])];
} else {
    $n = max(1, min(10, (int) ($opt['countries'] ?? 1)));
    $countries = [];
    for ($i = 0; $i < $n; $i++) {
        $c = $dryRun ? ($settings['countries'][$i] ?? null) : agent_next_country($settings);
        if ($c !== null && !in_array($c, $countries, true)) $countries[] = $c;
    }
}
if (!$countries) {
    $log('No monitored countries are configured.');
    exit(2);
}

if ($dryRun) {
    foreach ($countries as $c) {
        echo "=== SYSTEM PROMPT ===\n", agent_system_prompt($settings), "\n\n";
        echo "=== TASK ({$c}) ===\n", agent_task_message($c, $settings, agent_known_items($c)), "\n\n";
        echo "=== TOOLS ===\n", json_encode(agent_tools($settings), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
    }
    exit(0);
}

// One run at a time, even if cron fires again before the last one finishes
$lock = fopen(sys_get_temp_dir() . '/tafakari-agent-' . md5($root) . '.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    $log('Another agent run is still in progress. Skipping.');
    exit(0);
}

$failed = false;
foreach ($countries as $c) {
    $log("Running for {$c} ...");
    try {
        $r = agent_run_country($c, 'CRON');
    } catch (Throwable $e) {
        $r = ['status' => 'FAILED', 'cost' => 0.0, 'message' => $e->getMessage()];
    }
    $log(sprintf('%s: %s (est. $%.4f) %s', $c, $r['status'], $r['cost'], $r['message']));
    if ($r['status'] === 'FAILED') $failed = true;
    if ($r['status'] === 'SKIPPED') break;   // budget reached: stop for this invocation
}

flock($lock, LOCK_UN);
exit($failed ? 1 : 0);
