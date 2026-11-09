<?php
declare(strict_types=1);
/** POST /api/assistant - public question endpoint for the Ask Tafakari widget. */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/reports.php';
require_once dirname(__DIR__) . '/includes/agent.php';
require_once dirname(__DIR__) . '/includes/assistant.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') json_error('Method not allowed.', 405);

$body = request_body();
$sent = (string) ($body['token'] ?? '');
if (empty($_SESSION['assistant_csrf']) || !hash_equals($_SESSION['assistant_csrf'], $sent)) {
    json_error('Please reload the page and try again.', 403);
}
// The model call can take a while: do not hold the visitor's session lock
session_write_close();
@set_time_limit(120);

$ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');   // never a client-supplied header
$r  = assistant_answer((string) ($body['question'] ?? ''), assistant_ip_hash($ip));
json_response(['ok' => $r['ok'], 'answer' => $r['answer'], 'sources' => $r['sources']]);
