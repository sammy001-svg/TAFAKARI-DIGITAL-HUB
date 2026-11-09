<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/reports.php';
require_once __DIR__ . '/includes/charts.php';
require_once __DIR__ . '/includes/report-render.php';

$report = null;
try {
    $report = report_get((string) ($_GET['id'] ?? ''));
} catch (Throwable $e) {}

// Only published reports are public; drafts are not even confirmed to exist
if (!$report || $report['status'] !== 'PUBLISHED') {
    include __DIR__ . '/404.php';
    exit;
}

if (($_GET['format'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="tafakari-report-' . $report['periodStart'] . '.csv"');
    echo report_csv($report);
    exit;
}

$s = $report['snapshotData'];
$pageTitle = $report['title'] . ' | Tafakari Digital Hub';
$pageDesc  = report_kind_label($report['kind']) . ' for ' . $s['scope']['label'] . ', ' . $s['period']['label']
           . ($report['kind'] === 'OUTLOOK'
              ? ': projected intensity by category and country for the next monitoring cycle, with likely ranges and measured accuracy.'
              : ': intensity by category and country, changes from the previous period, critical flags and cross-border developments.');
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex flex-col font-inter" style="background:#F8F8F0">
<div class="no-print"><?php include __DIR__ . '/includes/navbar.php'; ?></div>

<style>
@media print {
  .no-print { display:none !important; }
  body { background:#fff !important; }
  .rp-head { background:#fff !important; color:#0f172a !important; padding:0 0 12px !important; }
  .rp-head * { color:#0f172a !important; }
  @page { margin:14mm; }
}
</style>

<main class="grow">
  <div class="rp-head text-white px-4 md:px-6 py-10" style="background:#750B25">
    <div class="max-w-6xl mx-auto">
      <a href="/reports" class="no-print text-xs font-bold" style="color:rgba(255,255,255,.75)">&larr; All reports</a>
      <p class="text-[11px] font-black uppercase tracking-[.14em] mt-4 mb-2" style="color:#E7952A"><?= h(report_kind_label($report['kind'])) ?> · CRTP, Hekima University College</p>
      <h1 class="font-outfit font-black text-3xl md:text-4xl leading-tight"><?= h($report['title']) ?></h1>
      <p class="mt-3 text-sm" style="color:rgba(255,255,255,.82)">
        <?= h($s['scope']['label']) ?> · <?= h($s['period']['label']) ?> · <?= $report['kind'] === 'OUTLOOK' ? 'based on cycles up to' : 'compared with' ?> <?= h($s['prev']['label']) ?>
        · published <?= h(format_date($report['publishedAt'])) ?>
      </p>
      <div class="no-print flex flex-wrap gap-3 mt-6">
        <button type="button" onclick="window.print()" class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-bold" style="background:#E7952A;color:#0D0102">Print or save as PDF</button>
        <a href="/reports/<?= h(rawurlencode($report['id'])) ?>?format=csv" class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-bold border" style="border-color:rgba(255,255,255,.35);color:#fff">Download data (CSV)</a>
      </div>
    </div>
  </div>

  <div class="max-w-6xl mx-auto px-4 md:px-6 py-8">
    <?php report_render($report, true); ?>
  </div>
</main>

<div class="no-print"><?php include __DIR__ . '/includes/footer.php'; ?></div>
</body>
</html>
