<?php
declare(strict_types=1);
/**
 * Word and Excel versions of a published country monitoring update:
 * /updates/{id}/word and /updates/{id}/excel. Drafts are not served here.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/agent-updates.php';

$u = null;
try {
    $u = agent_update_get((string) ($_GET['id'] ?? ''));
} catch (Throwable $e) {}
if (!$u || $u['status'] !== 'PUBLISHED') {
    include __DIR__ . '/404.php';
    exit;
}
$file = ($_GET['file'] ?? '') === 'excel' ? 'xlsx' : 'docx';
try {
    export_send($file === 'xlsx' ? agent_update_xlsx($u) : agent_update_docx($u), 'tafakari-update-' . $u['country'] . '-' . $u['periodStart'], $file);
} catch (Throwable $e) {
    error_log('[update export] ' . $e->getMessage());
    http_response_code(500);
    exit('The file could not be created. Check that the PHP zip extension is enabled.');
}
