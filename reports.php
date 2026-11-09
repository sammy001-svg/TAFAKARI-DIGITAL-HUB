<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/reports.php';

$kind = in_array($_GET['type'] ?? '', ['CYCLE', 'QUARTER', 'OUTLOOK'], true) ? $_GET['type'] : '';
$rows = [];
try {
    $sql = "SELECT id, kind, periodStart, periodEnd, scope, title, snapshot, publishedAt
            FROM AnalysisReport WHERE status = 'PUBLISHED'" . ($kind ? ' AND kind = ?' : '') . '
            ORDER BY periodStart DESC, publishedAt DESC LIMIT 120';
    $q = db()->prepare($sql);
    $q->execute($kind ? [$kind] : []);
    $rows = $q->fetchAll();
} catch (Throwable $e) {}

$pageTitle = 'Analysis Reports | Tafakari Digital Hub';
$pageDesc  = 'Bi-weekly and quarterly conflict analysis reports and forward-looking outlooks from CRTP: intensity by category and country, changes over time, critical flags and cross-border developments.';
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex flex-col font-inter" style="background:#F8F8F0">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="grow">
  <div style="background:#750B25" class="text-white px-4 md:px-6 py-10 md:py-12">
    <div class="max-w-6xl mx-auto">
      <p class="text-[11px] font-black uppercase tracking-[.14em] mb-3" style="color:#E7952A">Regional Conflict Dashboard</p>
      <h1 class="font-outfit font-black text-3xl md:text-5xl leading-tight">Analysis Reports</h1>
      <p class="mt-4 text-base md:text-lg leading-relaxed max-w-3xl" style="color:rgba(255,255,255,.82)">
        Bi-weekly updates for each monitoring cycle, quarterly analyses and forward-looking outlooks, built from the heat-map data and reviewed by CRTP.
      </p>
      <div class="flex flex-wrap gap-3 mt-6">
        <a href="/heatmap" class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-bold" style="background:#E7952A;color:#0D0102">Open the heat map</a>
        <a href="/methodology" class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-bold border" style="border-color:rgba(255,255,255,.35);color:#fff">How scores are calculated</a>
      </div>
    </div>
  </div>

  <div class="max-w-6xl mx-auto px-4 md:px-6 py-10">
    <div class="flex flex-wrap gap-1 mb-6 p-1 rounded-2xl w-fit" style="background:rgba(0,0,0,.06)">
      <?php foreach (['' => 'All reports', 'CYCLE' => 'Bi-weekly', 'QUARTER' => 'Quarterly', 'OUTLOOK' => 'Outlook'] as $v => $l): ?>
        <a href="/reports<?= $v ? '?type=' . $v : '' ?>" class="px-4 py-2 rounded-xl text-sm font-bold"
           style="<?= $kind === $v ? 'background:#750B25;color:#fff' : 'color:#475569' ?>"><?= h($l) ?></a>
      <?php endforeach; ?>
    </div>

    <?php if (!$rows): ?>
      <div class="bg-white rounded-3xl border border-slate-200 px-6 py-14 text-center">
        <p class="text-sm font-semibold text-slate-600">No reports have been published yet.</p>
        <p class="text-xs text-slate-400 mt-1.5">The live picture is on the <a href="/heatmap" class="underline" style="color:#750B25">heat map</a>.</p>
      </div>
    <?php endif; ?>

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
      <?php foreach ($rows as $r):
          $s = json_decode((string) $r['snapshot'], true) ?: [];
          $k = $s['kpis'] ?? [];
          // The highest category score in the period, as the card's headline figure
          $top = null;
          foreach (($s['total'] ?? []) as $cat => $cell) if ($top === null || $cell['score'] > $top[1]) $top = [$cat, (float) $cell['score']];
      ?>
        <a href="/reports/<?= h(rawurlencode($r['id'])) ?>" class="block bg-white rounded-3xl border border-slate-200 p-6 hover:shadow-md transition-shadow">
          <p class="text-[10px] font-black uppercase tracking-[.12em]" style="color:#750B25"><?= h(report_kind_label($r['kind'])) ?></p>
          <h2 class="font-outfit font-black text-lg text-slate-900 leading-snug mt-2"><?= h($r['title']) ?></h2>
          <p class="text-xs text-slate-500 mt-1"><?= h(report_scope($r['scope'])['label'] ?? '') ?> · <?= h(format_date($r['periodStart'], 'j M')) ?> – <?= h(format_date($r['periodEnd'], 'j M Y')) ?></p>
          <div class="flex flex-wrap gap-x-5 gap-y-1 mt-4 text-xs text-slate-600">
            <span><strong class="text-slate-900"><?= (int) ($k['reports'] ?? 0) ?></strong> reports</span>
            <span><strong class="text-slate-900"><?= (int) ($k['countries'] ?? 0) ?></strong> countries</span>
            <?php if ($r['kind'] === 'OUTLOOK'): ?>
              <span><strong class="text-slate-900"><?= (int) ($k['rising'] ?? 0) ?></strong> rising</span>
              <span><strong class="text-slate-900"><?= (int) ($k['signals'] ?? 0) ?></strong> unusual patterns</span>
            <?php else: ?>
              <span><strong class="text-slate-900"><?= (int) ($k['critical'] ?? 0) ?></strong> critical flags</span>
            <?php endif; ?>
          </div>
          <?php if ($top): ?>
            <p class="text-xs text-slate-500 mt-3">Highest: <?= h($top[0]) ?>
              <span class="inline-block ml-1 px-2 py-0.5 rounded-md text-white font-black" style="background:<?= h(hm_tier_color($top[1])) ?>"><?= h(number_format($top[1], 1)) ?></span></p>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
