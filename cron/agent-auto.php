<?php
declare(strict_types=1);
/**
 * Tafakari Agent - the one scheduled job (command line only).
 * Run hourly; the agent decides what is due. It never publishes anything.
 *
 * cPanel > Cron Jobs:
 *   0 * * * *  /usr/local/bin/php /home/USER/public_html/cron/agent-auto.php >> /home/USER/agent.log 2>&1
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
require_once $root . '/config.php';
require_once $root . '/includes/db.php';
require_once $root . '/includes/functions.php';
require_once $root . '/includes/agent-auto.php';

// One piece of work at a time, even if the last one is still running
$lock = fopen(sys_get_temp_dir() . '/tafakari-auto-' . md5($root) . '.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) exit(0);

try {
    $r = agent_auto_tick();
} catch (Throwable $e) {
    $r = ['status' => 'FAILED', 'task' => null, 'message' => $e->getMessage()];
}
fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . '] ' . ($r['task'] ?? '-') . ': ' . $r['status'] . ' ' . $r['message'] . PHP_EOL);
flock($lock, LOCK_UN);
exit($r['status'] === 'FAILED' ? 1 : 0);
