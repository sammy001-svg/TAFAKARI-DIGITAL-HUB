<?php
declare(strict_types=1);
/**
 * Analysis reports - automatic drafts (command line only).
 *
 * When a monitoring cycle or a quarter has closed, creates the DRAFT report
 * for it (all monitored countries), from the data as it stands. Never
 * publishes: an analyst reviews the draft and a super admin publishes it.
 *
 *   php cron/report-generate.php              drafts for the cycle/quarter that just closed
 *   php cron/report-generate.php --narrative  ...and ask the Tafakari Agent for a first narrative
 *
 * cPanel > Cron Jobs, e.g. daily at 06:00 (safe to run often: one report per period):
 *   0 6 * * *  /usr/local/bin/php /home/USER/public_html/cron/report-generate.php --narrative >> /home/USER/reports.log 2>&1
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

$opt  = getopt('', ['narrative']);
$log  = fn(string $m) => fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . '] ' . $m . PHP_EOL);
ensure_report_tables();

// The most recent CLOSED cycle and quarter (the ones containing yesterday's
// predecessor period end); a period in progress is never drafted automatically.
$jobs = [];
$cycle = report_period('CYCLE', date('Y-m-d'));
$jobs[] = ['CYCLE', $cycle['prev']['start']];
$quarter = report_period('QUARTER', date('Y-m-d'));
$jobs[] = ['QUARTER', $quarter['prev']['start']];

$failed = false;
foreach ($jobs as [$kind, $start]) {
    try {
        $r = report_create($kind, $start, 'ALL', null);
        $p = report_period($kind, $start);
        if (!$r['created']) { $log(report_kind_label($kind) . " for {$p['label']} already exists."); continue; }
        $log('Created draft ' . report_kind_label($kind) . " for {$p['label']} (id {$r['id']}).");
        if (isset($opt['narrative'])) {
            $n = report_draft_narrative($r['id'], 'cron');
            $log('  Narrative: ' . $n['message'] . sprintf(' (est. $%.4f)', $n['cost']));
        }
    } catch (Throwable $e) {
        $failed = true;
        $log("FAILED {$kind} {$start}: " . $e->getMessage());
    }
}
exit($failed ? 1 : 0);
