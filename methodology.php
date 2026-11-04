<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Every number on this page is read from the same settings and functions the
// heat map uses (includes/functions.php), so the published methodology cannot
// drift from the calculation actually performed.
$tax     = heatmap_taxonomy();
$agg     = hm_agg_settings();
$cycLen  = hm_cycle_days();
$groups  = hm_country_groups();
$peakPct = (int) round($agg['peak_weight'] * 100);

// Worked example: three reports on one country and category, same cycle
$exReports = [
    ['score' => 3, 'sources' => 1, 'age' => 10],
    ['score' => 4, 'sources' => 1, 'age' => 4],
    ['score' => 9, 'sources' => 3, 'age' => 1],
];
$exRef   = date('Y-m-d');
$exItems = [];
foreach ($exReports as $i => $r) {
    $c = hm_corroboration_factor($r['sources']);
    $t = hm_recency_factor(date('Y-m-d', strtotime("-{$r['age']} days")), $exRef);
    $exReports[$i]['c'] = $c;
    $exReports[$i]['t'] = $t;
    $exReports[$i]['w'] = $c * $t;
    $exItems[] = ['score' => $r['score'], 'w' => $c * $t];
}
$exCell    = hm_aggregate($exItems);
$exPlain   = round(array_sum(array_column($exReports, 'score')) / count($exReports), 1);

$pageTitle = 'Heat Map Methodology | Tafakari Digital Hub';
$pageDesc  = 'How the Tafakari conflict heat map turns reports into 1-10 intensity scores: sources, classification, weighting, aggregation and human validation.';
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex flex-col font-inter" style="background:#F8F8F0">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<style>
.mt-step   { background:#fff; border:1px solid #e2e8f0; border-radius:24px; padding:28px; }
.mt-num    { width:34px; height:34px; border-radius:50%; background:#750B25; color:#fff; display:flex;
             align-items:center; justify-content:center; font-family:Outfit,sans-serif; font-weight:900; font-size:15px; flex-shrink:0; }
.mt-step h2 { font-family:Outfit,sans-serif; font-weight:900; font-size:20px; color:#0f172a; margin:0; }
.mt-step p, .mt-step li { font-size:15px; line-height:1.75; color:#334155; }
.mt-formula { background:#F8F8F0; border-left:4px solid #E7952A; border-radius:12px; padding:14px 18px;
              font-size:14px; line-height:1.9; color:#0f172a; }
.mt-table  { width:100%; border-collapse:collapse; font-size:13px; }
.mt-table th { text-align:left; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.08em;
               color:#94a3b8; padding:8px 10px; background:#f8fafc; }
.mt-table td { padding:8px 10px; border-top:1px solid #f1f5f9; color:#334155; }
</style>

<main class="grow">

  <div style="background:#750B25" class="text-white px-4 md:px-6 py-10 md:py-14">
    <div class="max-w-4xl mx-auto">
      <p class="text-[11px] font-black uppercase tracking-[.14em] mb-3" style="color:#E7952A">Regional Conflict Dashboard</p>
      <h1 class="font-outfit font-black text-3xl md:text-5xl leading-tight">Heat Map Methodology</h1>
      <p class="mt-4 text-base md:text-lg leading-relaxed" style="color:rgba(255,255,255,.82)">
        How every intensity score on the heat map is produced, so that researchers, peacebuilders
        and decision-makers can judge it, cite it and challenge it.
      </p>
      <a href="/heatmap" class="inline-flex items-center gap-2 mt-6 px-5 py-2.5 rounded-full text-sm font-bold"
         style="background:#E7952A;color:#0D0102">&larr; Back to the heat map</a>
    </div>
  </div>

  <div class="max-w-4xl mx-auto px-4 md:px-6 py-10 space-y-6">

    <div class="rounded-2xl px-5 py-4 text-sm leading-relaxed" style="background:rgba(231,149,42,.12);color:#0f172a;border:1px solid rgba(231,149,42,.35)">
      <strong style="color:#750B25">Current status.</strong>
      Every score published on the heat map today is assigned by a trained CRTP analyst and approved
      before publication. AI-assisted collection and scoring is in development. When it is introduced,
      every AI-proposed score will be labelled as such and reviewed by an analyst; the AI will not publish
      anything on its own.
    </div>

    <!-- 1. Data sources -->
    <section class="mt-step">
      <div class="flex items-center gap-3 mb-4"><span class="mt-num">1</span><h2>Data sources</h2></div>
      <p>
        The heat map is built from reports published in the Hub's document library. Each report names its
        author or issuing body and records how many <strong>independent sources</strong> corroborate it.
        Sources include institutional and official statements, news reporting, policy briefs, scholarly
        work and civil-society and faith-based reporting.
      </p>
      <p class="mt-3">
        Reports are read in <strong><?= (int)$cycLen ?>-day monitoring cycles</strong>. Scores are refreshed
        every cycle, and earlier cycles are kept, never overwritten: any past cycle can be reopened from the
        dashboard and compared side by side with another.
      </p>
    </section>

    <!-- 2. Classification -->
    <section class="mt-step">
      <div class="flex items-center gap-3 mb-4"><span class="mt-num">2</span><h2>Classification</h2></div>
      <p>
        Each report is assigned to one or more of <?= count($tax) ?> categories, and scored on the component
        elements of each category it addresses:
      </p>
      <div class="grid md:grid-cols-2 gap-3 mt-4">
        <?php foreach ($tax as $c): ?>
          <div class="rounded-2xl border border-slate-200 px-4 py-3" style="border-left:4px solid <?= h($c['color']) ?>">
            <p class="font-outfit font-black text-sm text-slate-900 mb-1"><?= h($c['name']) ?></p>
            <ul class="list-disc pl-5" style="margin:0">
              <?php foreach ($c['elements'] as $el): ?>
                <li style="font-size:13px;line-height:1.6"><?= h($el) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endforeach; ?>
      </div>
      <p class="mt-4">
        <strong>Cross-border items.</strong> A report concerning more than one country, such as a
        cross-border armed group, cross-border displacement or a regional peace initiative, is attributed
        to <em>every</em> country it concerns rather than forced into one, so transnational developments
        are not understated.
      </p>
    </section>

    <!-- 3. Severity and frequency weighting -->
    <section class="mt-step">
      <div class="flex items-center gap-3 mb-4"><span class="mt-num">3</span><h2>Severity and frequency weighting</h2></div>
      <p>
        Each element is scored on a ten-point scale, where 10 is severe or critical. Elements a report does
        not address are left unscored and excluded, never counted as zero. A report's score for a category
        is the average of its scored elements.
      </p>
      <div class="flex flex-wrap gap-2 my-4">
        <?php foreach ([['9 – 10', 9], ['7 – 8.9', 7], ['5 – 6.9', 5], ['2 – 4.9', 2], ['1 – 1.9', 1]] as [$range, $v]): ?>
          <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold border border-slate-200 bg-white">
            <span style="width:10px;height:10px;border-radius:3px;background:<?= h(hm_tier_color((float)$v)) ?>;display:inline-block"></span>
            <?= h(hm_tier_label((float)$v)) ?> <span class="text-slate-400 font-semibold"><?= h($range) ?></span>
          </span>
        <?php endforeach; ?>
      </div>
      <p>Reports are then weighted before they are combined:</p>
      <ul class="list-disc pl-6 mt-2 space-y-1">
        <li><strong>Corroboration.</strong> A report backed by more independent sources counts more:
            &times;1.0 for one source, rising by 0.25 for each further source, up to &times;2.0 at five or more.</li>
        <li><strong>Recency.</strong>
          <?php if ($agg['half_life'] > 0): ?>
            Within a cycle, a report <?= (int)$agg['half_life'] ?> days older than the cycle's end counts half
            as much as one from its last day, and never less than a quarter, so the reading reflects the
            most current situation. The &ldquo;all time&rdquo; view is an overall record and is not weighted
            by recency.
          <?php else: ?>
            Recency weighting is currently switched off: all reports in a cycle count equally.
          <?php endif; ?>
        </li>
      </ul>
    </section>

    <!-- 4. Aggregation -->
    <section class="mt-step">
      <div class="flex items-center gap-3 mb-4"><span class="mt-num">4</span><h2>Aggregation</h2></div>
      <p>Each cell of the heat map, one country and one category in one cycle, combines its reports in two parts:</p>
      <div class="mt-formula mt-3">
        Weighted mean = &Sigma;(weight &times; score) &divide; &Sigma;(weight)<br>
        Cell score = <?= 100 - $peakPct ?>% &times; weighted mean + <?= $peakPct ?>% &times; highest single report score
      </div>
      <p class="mt-3">
        The share given to the highest report keeps a single critical event from being averaged away by
        several routine reports. In addition, a cell is <strong style="color:#ED1C24">flagged critical</strong>
        whenever any report in it scores 9 or more, whatever its combined score. A country's
        <em>Overall</em> column is the average of its category scores.
      </p>

      <?php if ($exCell): ?>
        <p class="mt-5 font-bold text-slate-900" style="font-size:14px">Worked example</p>
        <div class="overflow-x-auto mt-2 rounded-xl border border-slate-200">
          <table class="mt-table">
            <thead><tr><th>Report</th><th>Score</th><th>Sources</th><th>Age</th><th>Corroboration</th><th>Recency</th><th>Weight</th></tr></thead>
            <tbody>
              <?php foreach ($exReports as $i => $r): ?>
                <tr>
                  <td><?= chr(65 + $i) ?></td>
                  <td><strong><?= (int)$r['score'] ?></strong></td>
                  <td><?= (int)$r['sources'] ?></td>
                  <td><?= (int)$r['age'] ?> days</td>
                  <td>&times;<?= h(number_format($r['c'], 2)) ?></td>
                  <td>&times;<?= h(number_format($r['t'], 2)) ?></td>
                  <td><?= h(number_format($r['w'], 2)) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <p class="mt-3" style="font-size:14px">
          A plain average would give <strong><?= h(number_format($exPlain, 1)) ?></strong>. The weighted mean is
          <strong><?= h(number_format($exCell['mean'], 1)) ?></strong>; blended with the highest report
          (<?= h(number_format($exCell['peak'], 0)) ?>), the cell scores
          <strong style="color:<?= h(hm_tier_color($exCell['score'])) ?>"><?= h(number_format($exCell['score'], 1)) ?>
          (<?= h(hm_tier_label($exCell['score'])) ?>)</strong><?= $exCell['critical'] ? ', and carries a critical flag because report C scores 9' : '' ?>.
        </p>
      <?php endif; ?>

      <?php if ($groups): ?>
        <p class="mt-5"><strong>Regional overview.</strong> Regional rows combine every report concerning any
          member country, counting each report once per region even when it names several members. Regions may overlap.</p>
        <div class="grid sm:grid-cols-2 gap-3 mt-3">
          <?php foreach ($groups as $g): ?>
            <div class="rounded-xl border border-slate-200 px-4 py-3">
              <p class="font-outfit font-black text-sm text-slate-900"><?= h($g['name']) ?></p>
              <p style="font-size:12px;line-height:1.6;color:#64748b;margin:2px 0 0"><?= h(implode(', ', $g['countries'])) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- 5. Human validation -->
    <section class="mt-step">
      <div class="flex items-center gap-3 mb-4"><span class="mt-num">5</span><h2>Human validation</h2></div>
      <p>
        No score reaches the public heat map without human approval. Reports are entered and scored by CRTP
        analysts, then reviewed and approved by a senior administrator before publication. An approved
        report that is later edited is withdrawn and re-enters review.
      </p>
      <p class="mt-3">
        The platform records how each report's scores were produced (analyst-scored, AI-proposed, or
        AI-proposed and then adjusted by an analyst), who last set them and when. That label appears with
        each report on the map.
      </p>
    </section>

    <!-- Limitations -->
    <section class="mt-step" style="background:#F8F8F0">
      <h2 class="mb-3">Limitations</h2>
      <ul class="list-disc pl-6 space-y-1">
        <li>Scores are structured expert judgements, not measurements. They are only as complete as the reporting behind them.</li>
        <li>A country or category with no score has no published report in that cycle; it does not mean the situation is calm.</li>
        <li>Coverage favours places and issues that are widely reported. Local and faith-based sources are sought to offset this.</li>
        <li>Changes to the weights apply to every cycle, past ones included, so comparisons stay like for like.</li>
      </ul>
      <p class="mt-4 text-sm text-slate-500">
        Questions about this methodology are welcome through the <a href="/contact" class="font-semibold underline" style="color:#750B25">contact page</a>.
      </p>
    </section>

  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
