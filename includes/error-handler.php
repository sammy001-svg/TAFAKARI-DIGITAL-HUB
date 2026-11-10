<?php
/**
 * A clear error page instead of a blank white screen.
 *
 * When a page fails, visitors see a short message with a reference code, and
 * the full technical detail goes to the server's error log under the same
 * code, so an administrator can find it. Error details are never shown in the
 * browser. Command-line jobs (cron) are left alone.
 */

if (PHP_SAPI !== 'cli' && !defined('TK_ERROR_HANDLER')) {
    define('TK_ERROR_HANDLER', true);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    // Hold the page until it finishes, so a failure halfway can be replaced cleanly
    ob_start();
    set_exception_handler(function (Throwable $e): void {
        tk_fail(get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    });
    register_shutdown_function(function (): void {
        $e = error_get_last();
        if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
            tk_fail($e['message'] . ' in ' . $e['file'] . ':' . $e['line']);
        }
    });
}

function tk_fail(string $detail): void {
    $ref = strtoupper(bin2hex(random_bytes(4)));
    error_log("[Tafakari error {$ref}] {$detail}");
    while (ob_get_level() > 0) ob_end_clean();
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    if (!headers_sent()) {
        http_response_code(500);
        header('Cache-Control: no-store');
    }
    if (str_starts_with($uri, '/api/')) {
        if (!headers_sent()) header('Content-Type: application/json');
        echo json_encode(['error' => "Something went wrong. Reference {$ref}."]);
        return;
    }
    if (!headers_sent()) header('Content-Type: text/html; charset=utf-8');
    $back = str_starts_with($uri, '/admin') ? ['/admin/dashboard', 'Go to the dashboard'] : ['/', 'Go to the home page'];
    // Self-contained on purpose: the database or a shared include may be what failed
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<meta name="robots" content="noindex"><title>Something went wrong | Tafakari Digital Hub</title>'
       . '<style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#F8F8F0;color:#1f2937;font:16px/1.6 system-ui,-apple-system,"Segoe UI",sans-serif;padding:24px}'
       . 'main{max-width:520px;background:#fff;border-radius:20px;padding:36px 32px;border-top:6px solid #750B25;box-shadow:0 10px 30px rgba(13,1,2,.08)}'
       . 'h1{margin:0 0 10px;font-size:24px;color:#0D0102}p{margin:0 0 14px}.sw{color:#64748b;font-size:14px}'
       . 'code{background:#F8F8F0;border-radius:6px;padding:2px 8px;font-weight:700;color:#750B25}'
       . 'a{display:inline-block;margin-top:8px;background:#750B25;color:#fff;text-decoration:none;font-weight:700;padding:11px 20px;border-radius:999px}</style></head><body><main>'
       . '<h1>Something went wrong</h1>'
       . '<p>This page could not be shown just now. Please try again in a moment. If it keeps happening, contact CRTP and mention reference <code>' . $ref . '</code>.</p>'
       . '<p class="sw">Samahani, ukurasa huu haukuweza kuonyeshwa kwa sasa. Tafadhali jaribu tena baadaye.</p>'
       . '<a href="' . $back[0] . '">' . $back[1] . '</a></main></body></html>';
}
