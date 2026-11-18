<?php
declare(strict_types=1);
/**
 * Tafakari Agent - the one scheduled job (command line only).
 * Run every five minutes: documents posted five minutes earlier are graded,
 * and once an hour the agent does one piece of its other work. It never
 * publishes anything. The cPanel > Cron Jobs line is below.
 */
// */5 * * * *  /usr/local/bin/php /home/USER/public_html/cron/agent-auto.php >> /home/USER/agent.log 2>&1

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
require_once $root . '/config.php';
require_once $root . '/includes/db.php';
require_once $root . '/includes/functions.php';
require_once $root . '/includes/agent-auto.php';

// One piece of work at a time, including one started with Run now
$lock = agent_auto_lock();
if ($lock === false) {
    agent_auto_mark_tick();   // the job is alive, just waiting its turn
    exit(0);
}

try {
    $r = agent_auto_cron();
} catch (Throwable $e) {
    $r = ['status' => 'FAILED', 'task' => null, 'message' => $e->getMessage()];
}
// Every five minutes: write to the log only when something happened
if ($r['status'] !== 'IDLE' || $r['task'] !== null) {
    fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . '] ' . ($r['task'] ?? '-') . ': ' . $r['status'] . ' ' . $r['message'] . PHP_EOL);
}
agent_auto_unlock($lock);
exit($r['status'] === 'FAILED' ? 1 : 0);
