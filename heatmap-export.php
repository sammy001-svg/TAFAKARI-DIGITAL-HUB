<?php
declare(strict_types=1);
/**
 * Word and Excel versions of the heat map: /heatmap/export?cycle=<start|all>&format=docx|xlsx
 * Built from the same published, scored reports as the map itself.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/reports.php';
require_once __DIR__ . '/includes/exports.php';

$format = (string) ($_GET['format'] ?? 'xlsx');
if (!in_array($format, ['docx', 'xlsx'], true)) $format = 'xlsx';
$cycle = trim((string) ($_GET['cycle'] ?? ''));
$start = null;
if ($cycle !== 'all') {
    $d = preg_match('/^\d{4}-\d{2}-\d{2}$/', $cycle) ? $cycle : date('Y-m-d');
    $start = hm_cycle_bounds($d)['start'];
}

try {
    $data = export_heatmap_data($start);
    $name = 'tafakari-heat-map-' . ($start ?? 'all-time');
    export_send($format === 'xlsx' ? export_heatmap_xlsx($data) : export_heatmap_docx($data), $name, $format);
} catch (Throwable $e) {
    error_log('[heatmap export] ' . $e->getMessage());
    http_response_code(500);
    exit('The file could not be created. Check that the PHP zip extension is enabled.');
}
