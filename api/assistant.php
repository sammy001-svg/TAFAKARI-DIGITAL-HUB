<?php
declare(strict_types=1);
/**
 * POST /api/assistant - the public chat assistant.
 *   {question, history, page, lang, token}    -> {ok, answer, sources, suggestions, ref, language}   lang: en, fr or sw
 *   {action: "feedback", ref, rating, token}   -> {ok}   rating 1 (helpful) or -1
 */
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

$ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');   // never a client-supplied header

if (($body['action'] ?? '') === 'feedback') {
    json_response(['ok' => assistant_feedback((string) ($body['ref'] ?? ''), assistant_ip_hash($ip), (int) ($body['rating'] ?? 0))]);
}

@set_time_limit(120);
$page = (string) ($body['page'] ?? '');
$r = assistant_answer((string) ($body['question'] ?? ''), assistant_ip_hash($ip), null, (array) ($body['history'] ?? []), mb_substr($page, 0, 300), (string) ($body['lang'] ?? ''));
json_response(['ok' => $r['ok'], 'answer' => $r['answer'], 'sources' => $r['sources'], 'suggestions' => $r['suggestions'], 'ref' => $r['ref'], 'language' => $r['language']]);
