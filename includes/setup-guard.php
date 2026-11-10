<?php
/**
 * Setup and account-recovery tools are closed to the public.
 *
 * They open only while an empty file named ENABLE_SETUP exists in the site
 * root, which needs access to the server itself (cPanel > File Manager):
 * create the file, use the page, then delete the file. ENABLE_SETUP is in
 * .gitignore, so it can never arrive through a git deployment.
 */

function setup_tools_open(): bool {
    return is_file(dirname(__DIR__) . '/ENABLE_SETUP');
}

/** Stop here, showing a normal "not found", unless the tools are open. */
function setup_guard(bool $json = false): void {
    if (setup_tools_open()) return;
    if ($json) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo '{"error":"Not found"}';
        exit;
    }
    require dirname(__DIR__) . '/404.php';
    exit;
}
