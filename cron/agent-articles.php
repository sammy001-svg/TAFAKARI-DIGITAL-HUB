<?php
declare(strict_types=1);
/**
 * Tafakari Agent - scheduled article writing (command line only).
 *
 * Works through every monitoring region and indicator in turn. Drafts go to
 * Admin > AI Articles; nothing is published without human verification.
 *
 *   php cron/agent-articles.php                  the next article in the rotation
 *   php cron/agent-articles.php --count=3        the next three
 *   php cron/agent-articles.php --all            one pass over every combination
 *   php cron/agent-articles.php --region="Sahel" --category="Early Warning Signals"
 *   php cron/agent-articles.php --dry-run        print the prompt; no API call
 *
 * cPanel > Cron Jobs, e.g. three articles each morning:
 *   0 5 * * *  /usr/local/bin/php /home/USER/public_html/cron/agent-articles.php --count=3 >> /home/USER/articles.log 2>&1
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
require_once $root . '/includes/agent-articles.php';

$opt    = getopt('', ['count:', 'all', 'region:', 'category:', 'dry-run']);
$dryRun = isset($opt['dry-run']);
$log    = fn(string $m) => fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . '] ' . $m . PHP_EOL);

ensure_agent_tables();
ensure_agent_article_table();
$pairs = agent_article_pairs();
if (!$pairs) { $log('No monitoring regions or indicators are configured.'); exit(2); }

$one = null;
if (isset($opt['region']) || isset($opt['category'])) {
    $one = agent_article_find_pair((string) ($opt['region'] ?? ''), (string) ($opt['category'] ?? ''));
    if (!$one) { $log('That region and indicator combination does not exist.'); exit(2); }
}

if ($dryRun) {
    $p = $one ?? $pairs[0];
    echo "=== SYSTEM PROMPT ===\n", agent_article_system_prompt(), "\n\n";
    echo "=== TASK ===\n", agent_article_task_message($p, agent_article_previous_titles($p)), "\n\n";
    echo "=== TOOLS ===\n", json_encode(agent_article_tools(agent_settings(), agent_article_settings()), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
    exit(0);
}

if (!$one && !agent_article_settings()['enabled']) {
    $log('Scheduled writing is off in Admin > AI Articles. Nothing to do.');
    exit(0);
}
if (!agent_sdk_available()) { $log('The Anthropic PHP SDK is not installed. Run "composer install --no-dev" in ' . $root . '.'); exit(2); }
if (agent_api_key() === '') { $log('No Claude API key is set. Add it in Admin > Tafakari Agent > Settings.'); exit(2); }

$lock = fopen(sys_get_temp_dir() . '/tafakari-articles-' . md5($root) . '.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    $log('Another article run is still in progress. Skipping.');
    exit(0);
}

$target = $one ? 1 : (isset($opt['all']) ? count($pairs) : max(1, min(count($pairs), (int) ($opt['count'] ?? 1))));
$failed = false; $done = 0;
// Combinations with a draft waiting are passed over, so visit each at most once
for ($i = 0; $i < count($pairs) && $done < $target; $i++) {
    $p = $one ?? agent_article_next_pair();
    if ($p === null) break;
    $name = "{$p['region']} / {$p['category']}";
    try {
        $r = agent_article_run($p);
    } catch (Throwable $e) {
        $r = ['status' => 'FAILED', 'cost' => 0.0, 'message' => $e->getMessage()];
    }
    $log(sprintf('%s: %s (est. $%.4f) %s', $name, $r['status'], $r['cost'], $r['message']));
    if ($r['status'] === 'SKIPPED') break;   // budget reached
    if ($r['status'] === 'FAILED') $failed = true;
    if ($r['status'] !== 'HELD') $done++;
    if ($one) break;
}

flock($lock, LOCK_UN);
exit($failed ? 1 : 0);
