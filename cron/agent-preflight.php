<?php
declare(strict_types=1);
/**
 * Tafakari Agent - readiness check against the real server and the real API.
 * Command line only. Publishes nothing and stores no findings.
 *
 *   php cron/agent-preflight.php          free checks: PHP, SDK, key, database,
 *                                         and that the key can see the model
 *   php cron/agent-preflight.php --live   also one real agent request (one web
 *                                         search, a few US cents), logged in
 *                                         the run log and counted in the budget
 *
 * Exit codes: 0 all passed, 1 something failed.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
require_once $root . '/config.php';
require_once $root . '/includes/db.php';
require_once $root . '/includes/functions.php';
require_once $root . '/includes/reports.php';
require_once $root . '/includes/agent.php';
require_once $root . '/includes/agent-articles.php';
require_once $root . '/includes/agent-videos.php';
require_once $root . '/includes/assistant.php';

$opt = getopt('', ['live', 'country:']);
exit(agent_preflight(isset($opt['live']), (string) ($opt['country'] ?? 'Kenya')) ? 0 : 1);
