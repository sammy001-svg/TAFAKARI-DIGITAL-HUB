<?php
declare(strict_types=1);
/**
 * Renders an analysis report (snapshot + narrative) as HTML. Used by the
 * public report page and the admin preview, so both show the same thing.
 *
 * Requires functions.php, reports.php and charts.php.
 */

function rr_e(?string $s): string { return htmlspecialchars((string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8'); }

/** A score chip in the heat map's severity-tier style, with a critical flag. */
function rr_chip(?array $cell): string {
    if ($cell === null) return '<span class="rr-none">&mdash;</span>';
    $v = (float) $cell['score'];
    $tip = hm_tier_label($v) . ' · ' . $cell['n'] . ' report' . ($cell['n'] == 1 ? '' : 's') . ' · peak ' . number_format($cell['peak'], 1);
    $flag = $cell['critical'] && $v < 9;
    return '<span class="rr-chip" style="background:' . rr_e(hm_tier_color($v)) . ($flag ? ';box-shadow:0 0 0 2px #fff,0 0 0 4px #ED1C24' : '') . '" title="' . rr_e($tip) . '">'
         . rr_e(ch_num($v)) . '</span>' . ($flag ? '<span class="rr-flag" title="Includes a report scored 9 or more">&#9873; critical</span>' : '');
}

/** Change from the previous period, with direction in words as well as arrows. */
function rr_delta(?array $now, ?array $prev): string {
    if ($now === null && $prev === null) return '';
    if ($prev === null) return '<span class="rr-delta rr-muted">new</span>';
    if ($now === null)  return '<span class="rr-delta rr-muted">was ' . rr_e(ch_num((float) $prev['score'])) . '</span>';
    $d = round($now['score'] - $prev['score'], 1);
    if (abs($d) < 0.05) return '<span class="rr-delta rr-muted" title="No change">&rarr; 0</span>';
    return $d > 0
        ? '<span class="rr-delta rr-up" title="Escalating">&uarr; ' . rr_e(sprintf('%+.1f', $d)) . '</span>'
        : '<span class="rr-delta rr-down" title="De-escalating">&darr; ' . rr_e(sprintf('%+.1f', $d)) . '</span>';
}

/** Matrix of rows (countries or regions) x categories with deltas. */
function rr_matrix(array $rows, array $cats, string $firstCol, bool $showReports): string {
    $h = '<div class="rr-scroll"><table class="rr-matrix"><thead><tr><th scope="col">' . rr_e($firstCol) . '</th>';
    foreach ($cats as $c) $h .= '<th scope="col" class="c">' . rr_e($c['name']) . '</th>';
    $h .= '<th scope="col" class="c">Overall</th>' . ($showReports ? '<th scope="col" class="c">Reports</th>' : '') . '</tr></thead><tbody>';
    foreach ($rows as $r) {
        $h .= '<tr><th scope="row">' . rr_e($r['name']) . '</th>';
        foreach ($cats as $c) {
            $h .= '<td class="c">' . rr_chip($r['cells'][$c['name']] ?? null) . rr_delta($r['cells'][$c['name']] ?? null, $r['prev'][$c['name']] ?? null) . '</td>';
        }
        $ov = $r['overall'];
        $h .= '<td class="c">' . ($ov === null ? '<span class="rr-none">&mdash;</span>'
                : '<strong style="color:' . rr_e(hm_tier_color($ov)) . '">' . rr_e(number_format($ov, 1)) . '</strong>')
            . ($ov !== null && $r['overallPrev'] !== null ? rr_delta(['score' => $ov], ['score' => $r['overallPrev']]) : '') . '</td>';
        if ($showReports) $h .= '<td class="c">' . (int) $r['reports'] . '</td>';
        $h .= '</tr>';
    }
    return $h . '</tbody></table></div>';
}

function rr_report_list(array $items, int $limit = 0): string {
    if (!$items) return '<p class="rr-muted">None in this period.</p>';
    $h = '<ul class="rr-list">';
    foreach (($limit ? array_slice($items, 0, $limit) : $items) as $it) {
        $h .= '<li><a href="' . rr_e($it['url']) . '">' . rr_e($it['title']) . '</a><span class="rr-meta">'
            . rr_e(implode(', ', $it['countries'])) . ' · ' . rr_e(format_date($it['date']))
            . (!empty($it['category']) ? ' · ' . rr_e($it['category']) . ' ' . rr_e(ch_num((float) $it['score'])) . '/10' : '')
            . '</span></li>';
    }
    if ($limit && count($items) > $limit) $h .= '<li class="rr-muted">and ' . (count($items) - $limit) . ' more, listed under Sources.</li>';
    return $h . '</ul>';
}

/**
 * Render a stored report row (from report_get()).
 *
 * @param bool $public true on the public page (hides workflow details)
 */
function report_render_styles(): void {
    ?>
<style>
.rr { color:#0f172a; font-family:Inter,sans-serif; }
.rr h2 { font:900 20px/1.25 Outfit,sans-serif; margin:0 0 4px; color:#0f172a; }
.rr h3 { font:800 15px/1.3 Outfit,sans-serif; margin:0 0 10px; color:#0f172a; }
.rr-sec { background:#fff; border:1px solid #e2e8f0; border-radius:24px; padding:24px; margin-bottom:20px; break-inside:avoid-page; }
.rr-lead { font-size:13px; color:#64748b; line-height:1.6; margin:0 0 16px; max-width:760px; }
.rr-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px; margin-bottom:20px; }
.rr-kpi { background:#fff; border:1px solid #e2e8f0; border-radius:18px; padding:14px 16px; }
.rr-kpi p { margin:0; }
.rr-kpi .l { font-size:11px; font-weight:700; color:#64748b; }
.rr-kpi .v { font:800 26px/1.1 Inter,sans-serif; color:#0f172a; margin-top:4px; }
.rr-kpi .d { font-size:11px; color:#64748b; margin-top:2px; }
.rr-scroll { overflow-x:auto; }
.rr-matrix { border-collapse:collapse; width:100%; min-width:720px; font-size:13px; }
.rr-matrix th, .rr-matrix td { padding:8px 10px; border-top:1px solid #f1f5f9; text-align:left; vertical-align:top; }
.rr-matrix thead th { font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; background:#f8fafc; border-top:0; }
.rr-matrix tbody th { font-weight:700; white-space:nowrap; }
.rr-matrix .c { text-align:center; }
.rr-chip { display:inline-block; min-width:36px; padding:3px 8px; border-radius:8px; color:#fff; font:900 13px Outfit,sans-serif; }
.rr-flag { display:block; font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#ED1C24; margin-top:3px; }
.rr-delta { display:block; font-size:10px; font-weight:700; margin-top:3px; }
.rr-up { color:#b91c1c; } .rr-down { color:#047857; } .rr-muted, .rr-none { color:#94a3b8; }
.rr-cols { display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:20px; }
.rr-list { list-style:none; padding:0; margin:0; }
.rr-list li { padding:8px 0; border-top:1px solid #f1f5f9; font-size:13px; }
.rr-list li:first-child { border-top:0; }
.rr-list a { font-weight:700; color:#750B25; text-decoration:none; }
.rr-list a:hover { text-decoration:underline; }
.rr-meta { display:block; font-size:11px; color:#64748b; margin-top:2px; }
.rr-move { display:flex; justify-content:space-between; gap:10px; padding:7px 0; border-top:1px solid #f1f5f9; font-size:13px; }
.rr-move:first-child { border-top:0; }
.rr-narr { font-size:15px; line-height:1.75; color:#334155; max-width:820px; }
.rr-narr h1, .rr-narr h2 { font:900 17px/1.3 Outfit,sans-serif !important; color:#0f172a; margin:22px 0 8px !important; }
.rr-narr h2:first-child { margin-top:0 !important; }
.rr-prov { font-size:12px; color:#64748b; margin-top:16px; padding-top:12px; border-top:1px solid #f1f5f9; }
.rr-country { border-top:1px solid #e2e8f0; padding-top:20px; margin-top:20px; }
.rr-country:first-of-type { border-top:0; padding-top:0; margin-top:0; }
@media print {
  .rr-sec { border-color:#cbd5e1; padding:16px; border-radius:12px; }
  .rr a { color:#0f172a; }
}
</style>
<?php
}

function report_render(array $report, bool $public): void {
    if (($report['snapshotData']['kind'] ?? '') === 'OUTLOOK') { forecast_render($report, $public); return; }
    $s    = $report['snapshotData'];
    $cats = $s['categories'];
    $swatch = [];
    foreach ($cats as $c) $swatch[$c['name']] = $c['color'];
    $k = $s['kpis'];
    $prevLabel = 'Previous period (' . $s['prev']['label'] . ')';
    $narr = trim((string) ($report['narrative'] ?? ''));

    echo ch_styles();
    report_render_styles();
    ?>
<div class="rr">

  <div class="rr-kpis">
    <?php
    $kp = [
        ['Reports analysed', (string) $k['reports'], 'previous period: ' . $k['prevReports']],
        ['Countries scored', (string) $k['countries'], $s['scope']['type'] === 'COUNTRY' ? $s['scope']['label'] : 'in scope'],
        ['Critical flags', (string) $k['critical'], 'reports scored 9 or more'],
        ['Escalating', (string) $k['escalating'], 'country–category scores up 0.5+'],
        ['De-escalating', (string) $k['deescalating'], 'country–category scores down 0.5+'],
        ['Cross-border', (string) $k['crossBorder'], 'reports concerning several countries'],
    ];
    foreach ($kp as [$l, $v, $d]): ?>
      <div class="rr-kpi"><p class="l"><?= rr_e($l) ?></p><p class="v"><?= rr_e($v) ?></p><p class="d"><?= rr_e($d) ?></p></div>
    <?php endforeach; ?>
  </div>

  <?php if ($narr !== ''): ?>
    <section class="rr-sec">
      <div class="rr-narr"><?= markdown_to_html($narr) ?></div>
      <?php if ($public && ($lbl = report_narrative_label($report['narrativeSource'] ?? null)) !== ''): ?>
        <p class="rr-prov"><?= rr_e($lbl) ?></p>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <section class="rr-sec">
    <h3>Intensity by category</h3>
    <p class="rr-lead">Combined score for <?= rr_e($s['scope']['type'] === 'ALL' ? 'all monitored countries' : $s['scope']['label']) ?> in each category,
      against the previous period (<?= rr_e($s['prev']['label']) ?>).
      Each report counts once, however many countries it concerns.</p>
    <?= chart_score_bars(array_map(fn($c) => ['label' => $c['name'], 'value' => $s['total'][$c['name']]['score'] ?? null,
                                              'prev' => $s['totalPrev'][$c['name']]['score'] ?? null], $cats),
                         'Intensity by category, ' . $s['period']['label'], $prevLabel) ?>
  </section>

  <section class="rr-sec">
    <h3>Trend over <?= count($s['trend']['cycles']) ?> monitoring cycles</h3>
    <p class="rr-lead">One panel per category, on the same 0–10 scale. The red hairline marks the critical threshold (9).
      Gaps mean no scored report in that cycle.</p>
    <?= chart_small_multiples(array_column($s['trend']['cycles'], 'label'), $s['trend']['total'], $swatch,
                              'Trend by category per monitoring cycle, ' . $s['scope']['label']) ?>
  </section>

  <?php if ($s['regions']): ?>
    <section class="rr-sec">
      <h3>Regional overview</h3>
      <p class="rr-lead">Each report counts once per region, even when it concerns several member countries. Regions overlap.</p>
      <?= rr_matrix($s['regions'], $cats, 'Region', false) ?>
    </section>
  <?php endif; ?>

  <?php if ($s['countries'] && $s['scope']['type'] !== 'COUNTRY'): ?>
    <section class="rr-sec">
      <h3>By country</h3>
      <p class="rr-lead">Arrows show the change from <?= rr_e($s['prev']['label']) ?>. A red ring marks a cell that includes a report scored 9 or more.</p>
      <?= rr_matrix($s['countries'], $cats, 'Country', true) ?>
    </section>
  <?php endif; ?>

  <section class="rr-sec">
    <div class="rr-cols">
      <div>
        <h3>Escalating</h3>
        <?php if (!$s['movers']['up']): ?><p class="rr-muted" style="font-size:13px">No score rose by half a point or more.</p><?php endif; ?>
        <?php foreach ($s['movers']['up'] as $m): ?>
          <div class="rr-move"><span><strong><?= rr_e($m['country']) ?></strong> · <?= rr_e($m['category']) ?></span>
            <span><?= rr_e(ch_num($m['prev'])) ?> &rarr; <?= rr_e(ch_num($m['now'])) ?> <span class="rr-up">&uarr; <?= rr_e(sprintf('%+.1f', $m['delta'])) ?></span></span></div>
        <?php endforeach; ?>
      </div>
      <div>
        <h3>De-escalating</h3>
        <?php if (!$s['movers']['down']): ?><p class="rr-muted" style="font-size:13px">No score fell by half a point or more.</p><?php endif; ?>
        <?php foreach ($s['movers']['down'] as $m): ?>
          <div class="rr-move"><span><strong><?= rr_e($m['country']) ?></strong> · <?= rr_e($m['category']) ?></span>
            <span><?= rr_e(ch_num($m['prev'])) ?> &rarr; <?= rr_e(ch_num($m['now'])) ?> <span class="rr-down">&darr; <?= rr_e(sprintf('%+.1f', $m['delta'])) ?></span></span></div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="rr-sec">
    <div class="rr-cols">
      <div>
        <h3>&#9873; Critical flags</h3>
        <?= rr_report_list($s['critical']) ?>
      </div>
      <div>
        <h3>Cross-border developments</h3>
        <?= rr_report_list($s['crossBorder'], 12) ?>
      </div>
    </div>
  </section>

  <section class="rr-sec">
    <h3>Reports by category</h3>
    <p class="rr-lead">Share of the period's <?= (int) $k['reports'] ?> scored reports that address each category.
      A report that scores several categories counts in each, so the shares add up to more than 100%.</p>
    <?= chart_share_bars(array_map(fn($r) => ['label' => $r['category'], 'count' => $r['reports'], 'pct' => $r['pct']], $s['share']),
                         (int) $k['reports'], 'Reports by category, ' . $s['period']['label']) ?>
  </section>

  <?php if ($s['share']):
      $order = array_flip(array_column($s['categories'], 'name'));
      $slices = $s['share'];
      usort($slices, fn($a, $b) => ($order[$a['category']] ?? 99) <=> ($order[$b['category']] ?? 99));
  ?>
    <section class="rr-sec">
      <h3>How attention divides</h3>
      <p class="rr-lead">Each slice is that category's share of all category scores recorded in the period, so the slices add up to 100%.</p>
      <?= chart_pie(array_map(fn($r) => ['label' => $r['category'], 'count' => $r['reports'], 'slot' => $order[$r['category']] ?? 99], $slices),
                    'Share of category scores, ' . $s['period']['label']) ?>
    </section>
  <?php endif; ?>

  <?php if (!empty($s['elements'])): ?>
    <section class="rr-sec">
      <h3>Scores by element</h3>
      <p class="rr-lead">The component elements behind each category, on the same 1 to 10 scale. Only elements scored in the period are shown.</p>
      <div class="ch-grid" style="grid-template-columns:repeat(auto-fill,minmax(min(420px,100%),1fr))">
        <?php foreach ($s['categories'] as $cat): if (empty($s['elements'][$cat['name']])) continue;
            $els = $s['elements'][$cat['name']]; arsort($els); ?>
          <div class="ch-card">
            <h4 style="min-height:0;margin-bottom:8px"><?= rr_e($cat['name']) ?></h4>
            <?= chart_score_bars(array_map(fn($el, $c) => ['label' => $el, 'value' => $c['score'], 'prev' => $s['elementsPrev'][$cat['name']][$el]['score'] ?? null, 'critical' => $c['critical'] ?? false], array_keys($els), array_values($els)),
                                 'Scores by element, ' . $cat['name'], $s['prev']['label']) ?>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($s['countries']): ?>
    <section class="rr-sec">
      <h3><?= $s['scope']['type'] === 'COUNTRY' ? 'Country detail' : 'Country profiles' ?></h3>
      <?php foreach ($s['countries'] as $c): if (!$c['cells'] && !$c['prev']) continue;
          $mine = array_values(array_filter($s['reports'], fn($r) => in_array($c['name'], $r['countries'], true))); ?>
        <div class="rr-country">
          <h2><?= rr_e($c['name']) ?></h2>
          <p class="rr-lead"><?= (int) $c['reports'] ?> scored report<?= $c['reports'] == 1 ? '' : 's' ?> in <?= rr_e($s['period']['label']) ?><?php
            if ($c['overall'] !== null): ?> · overall <?= rr_e(number_format($c['overall'], 1)) ?> (<?= rr_e(hm_tier_label($c['overall'])) ?>)<?php endif; ?></p>
          <?= chart_score_bars(array_map(fn($cat) => ['label' => $cat['name'], 'value' => $c['cells'][$cat['name']]['score'] ?? null,
                                                      'prev' => $c['prev'][$cat['name']]['score'] ?? null], $cats),
                               $c['name'] . ': intensity by category, ' . $s['period']['label'], $prevLabel) ?>
          <div style="margin-top:14px">
            <?= chart_small_multiples(array_column($s['trend']['cycles'], 'label'), $s['trend']['byCountry'][$c['name']] ?? [], $swatch,
                                      $c['name'] . ': trend by category per monitoring cycle') ?>
          </div>
          <h3 style="margin-top:16px">Reports</h3>
          <?= rr_report_list($mine, 8) ?>
        </div>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>

  <section class="rr-sec">
    <h3>Sources</h3>
    <p class="rr-lead">Every published, scored report in this period and scope, newest first.</p>
    <?= rr_report_list($s['reports']) ?>
  </section>

  <section class="rr-sec" style="background:#F8F8F0">
    <h3>About these figures</h3>
    <p class="rr-lead" style="margin:0">
      Scores are CRTP's structured assessments of reported severity on a 1–10 scale, combined across reports by
      <?= rr_e($s['method']['weighting']) ?> and blended with the most severe report (peak weight
      <?= rr_e((string) $s['method']['peakWeight']) ?>). <?= $s['kind'] === 'QUARTER'
        ? 'Quarterly figures weight reports by corroboration only; the trend shows each monitoring cycle as it stood.'
        : 'Bi-weekly figures match the heat map for that monitoring cycle.' ?>
      The data was frozen on <?= rr_e(format_date($s['generatedAt'], 'j M Y, H:i')) ?><?= !empty($s['period']['inProgress']) ? ', before the period ended' : '' ?>.
      See the <a href="/methodology" style="color:#750B25;font-weight:700">full methodology</a>.
    </p>
  </section>
</div>
<?php
}

/** CSV of the country x category matrix (and regions), for download. */
function report_csv(array $report): string {
    if (($report['snapshotData']['kind'] ?? '') === 'OUTLOOK') return forecast_csv($report);
    $s = $report['snapshotData'];
    $cats = array_column($s['categories'], 'name');
    $fh = fopen('php://temp', 'r+');
    fputcsv($fh, [$report['title']]);
    fputcsv($fh, ['Period', $s['period']['label'], 'Previous', $s['prev']['label'], 'Scope', $s['scope']['label']]);
    fputcsv($fh, []);
    $head = ['Level', 'Name'];
    foreach ($cats as $c) { $head[] = $c; $head[] = $c . ' (previous)'; }
    $head[] = 'Overall'; $head[] = 'Overall (previous)';
    fputcsv($fh, $head);
    $emit = function (string $level, array $r) use ($fh, $cats) {
        $row = [$level, $r['name']];
        foreach ($cats as $c) { $row[] = $r['cells'][$c]['score'] ?? ''; $row[] = $r['prev'][$c]['score'] ?? ''; }
        $row[] = $r['overall'] ?? ''; $row[] = $r['overallPrev'] ?? '';
        fputcsv($fh, $row);
    };
    $emit('Total', ['name' => $s['scope']['label'], 'cells' => $s['total'], 'prev' => $s['totalPrev'], 'overall' => null, 'overallPrev' => null]);
    foreach ($s['regions'] as $r) $emit('Region', $r);
    foreach ($s['countries'] as $r) $emit('Country', $r);
    if (!empty($s['elements'])) {
        fputcsv($fh, []);
        fputcsv($fh, ['Category', 'Element', 'Score', 'Score (previous)', 'Reports']);
        foreach ($s['elements'] as $cat => $els) {
            foreach ($els as $el => $c) fputcsv($fh, [$cat, $el, $c['score'], $s['elementsPrev'][$cat][$el]['score'] ?? '', $c['n']]);
        }
    }
    rewind($fh);
    $csv = stream_get_contents($fh);
    fclose($fh);
    return "\xEF\xBB\xBF" . $csv;   // BOM so Excel reads UTF-8
}
