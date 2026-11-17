<?php
declare(strict_types=1);
/**
 * POST /api/classify - suggest issue categories for a document being written.
 * Signed-in staff only. {title, description, content, token} -> {ok, categories:[{name, reason}], message}
 * Suggests only: nothing is saved.
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/agent-classify.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') json_error('Method not allowed.', 405);
$user = api_require_auth();
$body = request_body();
if (empty($_SESSION['classify_csrf']) || !hash_equals($_SESSION['classify_csrf'], (string) ($body['token'] ?? ''))) {
    json_error('Your session expired. Reload the page and try again.', 403);
}
session_write_close();   // the request takes a few seconds: do not lock the user's other tabs
@set_time_limit(120);

$r = agent_classify_issue_categories((string) ($body['title'] ?? ''), (string) ($body['description'] ?? ''), (string) ($body['content'] ?? ''), (string) ($user['id'] ?? null));
json_response($r);
