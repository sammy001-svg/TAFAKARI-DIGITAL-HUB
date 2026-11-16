<?php
declare(strict_types=1);
/**
 * Word and Excel versions of the heat map and the analysis reports.
 *
 * Both formats are built from the same frozen figures the pages show
 * (hm_build_cells for the heat map, the stored snapshot for a report), so a
 * download always matches what the reader saw. Word documents are kept under
 * EXPORT_MAX_PAGES: the least important tables are shortened first, and the
 * full lists stay in the Excel version.
 *
 * Requires functions.php, reports.php, docx.php and xlsx.php.
 */

require_once __DIR__ . '/docx.php';
require_once __DIR__ . '/xlsx.php';

const EXPORT_MAX_PAGES = 9.5;   // the brief asks for reports under ten pages

/** One decimal, or a dash. */
function export_num(?float $v): string {
    return $v === null ? '—' : rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.');
}

/** Change between two scores as "+0.5", "−0.3", "0" or "new". */
function export_delta(?float $now, ?float $prev): string {
    if ($now === null) return $prev === null ? '' : 'was ' . export_num($prev);
    if ($prev === null) return 'new';
    $d = round($now - $prev, 1);
    if (abs($d) < 0.05) return '0';
    return ($d > 0 ? '+' : '−') . export_num(abs($d));
}

/** A score cell for Word, shaded in its tier. */
function export_score_cell(?float $v): array|string {
    return $v === null ? ['text' => '—', 'align' => 'center'] : ['text' => $v, 'tier' => true];
}

/** A score cell for Excel, shaded in its tier. */
function export_score_xcell(?float $v): array|string {
    return $v === null ? '' : ['v' => round($v, 1), 'tier' => true];
}

// ── Heat map ─────────────────────────────────────────────────────

/**
 * Everything the heat-map exports show, for one monitoring cycle or all time.
 *
 * @param ?string $cycleStart a cycle start date, or null for all time
 */
function export_heatmap_data(?string $cycleStart): array {
    $all = report_load_scored('2000-01-01');
    $cycle = $cycleStart !== null ? hm_cycle_bounds($cycleStart) : null;
    $now   = hm_build_cells($all, $cycle['start'] ?? null);
    $prevC = $cycle ? hm_cycle_shift($cycle['start'], -1) : null;
    $prev  = $prevC ? hm_build_cells($all, $prevC['start']) : ['country' => [], 'region' => [], 'total' => []];
    $sel   = $cycle ? array_values(array_filter($all, fn($r) => $r['cycle'] === $cycle['start'])) : $all;

    $tax  = heatmap_taxonomy();
    $cats = array_map(fn($c) => $c['name'], $tax);
    $overall = function (array $cells) use ($cats): ?float {
        $v = [];
        foreach ($cats as $c) if (isset($cells[$c])) $v[] = $cells[$c]['score'];
        return $v ? round(array_sum($v) / count($v), 1) : null;
    };
    $rows = function (array $nowLevel, array $prevLevel, array $order) use ($overall, $sel): array {
        $out = [];
        foreach ($order as $name) {
            $cells = $nowLevel[$name] ?? [];
            if (!$cells) continue;
            $pc = $prevLevel[$name] ?? [];
            $out[] = ['name' => $name, 'cells' => $cells, 'prev' => $pc, 'overall' => $overall($cells), 'overallPrev' => $overall($pc),
                      'reports' => count(array_filter($sel, fn($r) => in_array($name, $r['countries'], true)))];
        }
        return $out;
    };
    $countryNames = array_keys($now['country']);
    sort($countryNames);
    $regionNames = array_column(hm_country_groups(), 'name');

    $countries = $rows($now['country'], $prev['country'], $countryNames);
    usort($countries, fn($a, $b) => [$b['overall'] ?? -1, $a['name']] <=> [$a['overall'] ?? -1, $b['name']]);
    $label = $cycle ? $cycle['label'] : 'All time';

    return [
        'label'      => $label,
        'cycle'      => $cycle,
        'prevLabel'  => $prevC['label'] ?? null,
        'categories' => $cats,
        'total'      => $now['total'],
        'totalPrev'  => $prev['total'],
        'countries'  => $countries,
        'regions'    => $rows($now['region'], $prev['region'], $regionNames),
        'elements'   => report_element_cells($sel, $cycle ? 'wr' : 'wc'),
        'reports'    => array_reverse($sel),
        'method'     => hm_agg_settings(),
        'generatedAt'=> date('Y-m-d H:i'),
    ];
}

/** Rows of a ranking table: rank, country, overall, tier, previous, change. */
function export_ranking_rows(array $countries, bool $word): array {
    $rows = [];
    $rank = 0;
    foreach ($countries as $c) {
        if ($c['overall'] === null) continue;
        $rank++;
        $rows[] = $word
            ? [(string) $rank, $c['name'], export_score_cell($c['overall']), hm_tier_label($c['overall']), export_num($c['overallPrev']), export_delta($c['overall'], $c['overallPrev'])]
            : [$rank, $c['name'], export_score_xcell($c['overall']), hm_tier_label($c['overall']), $c['overallPrev'] !== null ? ['v' => $c['overallPrev'], 's' => 'num'] : '', export_delta($c['overall'], $c['overallPrev'])];
    }
    return $rows;
}

/** The country x category matrix, for Word ($word) or Excel. */
function export_matrix_rows(array $rows, array $cats, bool $word, bool $withReports = true): array {
    $out = [];
    foreach ($rows as $r) {
        $line = [$r['name']];
        foreach ($cats as $c) {
            $v = isset($r['cells'][$c]) ? (float) $r['cells'][$c]['score'] : null;
            $line[] = $word ? export_score_cell($v) : export_score_xcell($v);
        }
        $line[] = $word ? export_score_cell($r['overall']) : export_score_xcell($r['overall']);
        $line[] = export_delta($r['overall'], $r['overallPrev']);
        if ($withReports) $line[] = $word ? (string) $r['reports'] : (int) $r['reports'];
        $out[] = $line;
    }
    return $out;
}

function export_matrix_head(array $cats, bool $withReports = true): array {
    $h = array_merge(['Country / region'], $cats, ['Overall', 'Change']);
    if ($withReports) $h[] = 'Reports';
    return $h;
}

function export_matrix_widths(int $nCats, bool $withReports = true): array {
    $w = array_merge([0.2], array_fill(0, $nCats, 0.6 / $nCats), [0.1, 0.1]);
    if ($withReports) $w[] = 0.08;
    return $w;
}

/** Short column labels, so a five-category matrix fits a portrait page. */
function export_short_cats(array $cats): array {
    return array_map(function (string $c) {
        $c = str_replace(' & ', ' and ', $c);
        return mb_strlen($c) > 22 ? trim(mb_substr($c, 0, 21)) . '…' : $c;
    }, $cats);
}

function export_elements_rows(array $elements, array $elementsPrev, array $cats, bool $word): array {
    $rows = [];
    foreach ($cats as $cat) {
        foreach ($elements[$cat] ?? [] as $el => $cell) {
            $v = (float) $cell['score'];
            $p = isset($elementsPrev[$cat][$el]) ? (float) $elementsPrev[$cat][$el]['score'] : null;
            $rows[] = $word
                ? [$cat, $el, export_score_cell($v), export_delta($v, $p), (string) $cell['n']]
                : [$cat, $el, export_score_xcell($v), $p !== null ? ['v' => $p, 's' => 'num'] : '', export_delta($v, $p), (int) $cell['n']];
        }
    }
    return $rows;
}

function export_reports_rows(array $reports, bool $word, int $limit = 0): array {
    $rows = [];
    foreach ($limit ? array_slice($reports, 0, $limit) : $reports as $r) {
        $scores = [];
        foreach ($r['cats'] as $cat => $s) $scores[] = $cat . ' ' . export_num((float) $s);
        $base = defined('APP_URL') ? rtrim((string) constant('APP_URL'), '/') : '';
        $rows[] = $word
            ? [$r['date'], $r['title'], implode(', ', $r['countries']), implode('; ', $scores)]
            : [$r['date'], $r['title'], implode(', ', $r['countries']), $r['region'], implode('; ', $scores), (int) ($r['sourceCount'] ?? 1) ?: '', $r['scoreSource'] ?? '', $base . $r['url']];
    }
    return $rows;
}

function export_method_note(array $m, string $weighting): string {
    return 'Scores are CRTP\'s structured assessments of reported severity on a 1 to 10 scale (9 to 10 critical, 7 to 8.9 high, 5 to 6.9 moderate, '
         . '2 to 4.9 low, below 2 stable), combined across reports by ' . $weighting . ' and blended with the most severe report (peak weight '
         . $m['peak_weight'] . '). See the published methodology at /methodology.';
}

/** The heat map as an Excel workbook. */
function export_heatmap_xlsx(array $d): string {
    $cats = $d['categories'];
    $catCols = array_map(fn($c) => ['label' => $c, 'width' => 16], $cats);
    $summary = [['Tafakari conflict heat map', ''], ['Period', $d['label']], ['Compared with', $d['prevLabel'] ?? 'n/a'],
                ['Countries scored', count($d['countries'])], ['Scored reports', count($d['reports'])], ['Exported', $d['generatedAt']], ['', ''],
                ['Category', 'Combined score']];
    foreach ($cats as $c) $summary[] = [$c, isset($d['total'][$c]) ? export_score_xcell((float) $d['total'][$c]['score']) : ''];
    $summary[] = ['', ''];
    $summary[] = ['Method', export_method_note($d['method'], $d['cycle'] ? 'corroboration and recency' : 'corroboration')];
    return xlsx_build([
        ['name' => 'Summary', 'cols' => [['label' => 'Item', 'width' => 26], ['label' => 'Value', 'width' => 60]], 'rows' => $summary],
        ['name' => 'Ranking', 'cols' => [['label' => 'Rank', 'width' => 7], ['label' => 'Country', 'width' => 24], ['label' => 'Overall', 'width' => 10], ['label' => 'Tier', 'width' => 11], ['label' => 'Previous', 'width' => 10], ['label' => 'Change', 'width' => 9]],
         'rows' => export_ranking_rows($d['countries'], false)],
        ['name' => 'Countries', 'cols' => array_merge([['label' => 'Country', 'width' => 24]], $catCols, [['label' => 'Overall', 'width' => 10], ['label' => 'Change', 'width' => 9], ['label' => 'Reports', 'width' => 9]]),
         'rows' => export_matrix_rows($d['countries'], $cats, false)],
        ['name' => 'Regions', 'cols' => array_merge([['label' => 'Region', 'width' => 24]], $catCols, [['label' => 'Overall', 'width' => 10], ['label' => 'Change', 'width' => 9], ['label' => 'Reports', 'width' => 9]]),
         'rows' => export_matrix_rows($d['regions'], $cats, false)],
        ['name' => 'Elements', 'cols' => [['label' => 'Category', 'width' => 30], ['label' => 'Element', 'width' => 36], ['label' => 'Score', 'width' => 9], ['label' => 'Previous', 'width' => 10], ['label' => 'Change', 'width' => 9], ['label' => 'Reports', 'width' => 9]],
         'rows' => export_elements_rows($d['elements'], [], $cats, false)],
        ['name' => 'Reports', 'cols' => [['label' => 'Date', 'width' => 12], ['label' => 'Title', 'width' => 50], ['label' => 'Countries', 'width' => 26], ['label' => 'Region', 'width' => 16], ['label' => 'Category scores', 'width' => 50], ['label' => 'Sources', 'width' => 9], ['label' => 'Scored by', 'width' => 12], ['label' => 'Link', 'width' => 40]],
         'rows' => export_reports_rows($d['reports'], false)],
    ], ['title' => 'Tafakari heat map, ' . $d['label']]);
}

/** The heat map as a Word document, under the page limit. */
function export_heatmap_docx(array $d): string {
    $cats  = $d['categories'];
    $short = export_short_cats($cats);
    $n = count($cats);
    $blocks = [
        ['kv', [['Period', $d['label']], ['Compared with', $d['prevLabel'] ?? 'not applicable'], ['Countries scored', (string) count($d['countries'])],
                ['Scored reports', (string) count($d['reports'])], ['Exported', $d['generatedAt']]]],
        ['h1', 'Combined intensity by category'],
        ['table', ['head' => ['Category', 'Score', 'Tier', 'Previous', 'Change'], 'widths' => [0.44, 0.12, 0.16, 0.14, 0.14],
                   'rows' => array_map(function ($c) use ($d) {
                       $v = isset($d['total'][$c]) ? (float) $d['total'][$c]['score'] : null;
                       $p = isset($d['totalPrev'][$c]) ? (float) $d['totalPrev'][$c]['score'] : null;
                       return [$c, export_score_cell($v), $v === null ? '—' : hm_tier_label($v), export_num($p), export_delta($v, $p)];
                   }, $cats)]],
        ['h1', 'Comparative ratings'],
        ['p', 'Countries ranked by their overall score (the mean of their category scores), with the change from the previous period.'],
        ['table', ['head' => ['Rank', 'Country', 'Overall', 'Tier', 'Previous', 'Change'], 'widths' => [0.1, 0.34, 0.14, 0.16, 0.13, 0.13], 'rows' => export_ranking_rows($d['countries'], true)], 'optional' => 1, 'minRows' => 15, 'label' => 'the ranking'],
        ['h1', 'Scores by country and category'],
        ['table', ['head' => array_merge(['Country'], $short, ['Overall', 'Change', 'Reports']), 'widths' => export_matrix_widths($n), 'small' => true,
                   'rows' => export_matrix_rows($d['countries'], $cats, true)], 'optional' => 2, 'minRows' => 12, 'label' => 'the country table'],
    ];
    if ($d['regions']) {
        $blocks[] = ['h1', 'Monitoring regions'];
        $blocks[] = ['p', 'Each report counts once per region, even when it concerns several member countries. Regions overlap.'];
        $blocks[] = ['table', ['head' => array_merge(['Region'], $short, ['Overall', 'Change', 'Reports']), 'widths' => export_matrix_widths($n), 'small' => true,
                                'rows' => export_matrix_rows($d['regions'], $cats, true)], 'optional' => 3, 'label' => 'the regions table'];
    }
    if ($d['elements']) {
        $blocks[] = ['h1', 'Scores by element'];
        $blocks[] = ['table', ['head' => ['Category', 'Element', 'Score', 'Change', 'Reports'], 'widths' => [0.3, 0.38, 0.1, 0.11, 0.11], 'small' => true,
                                'rows' => export_elements_rows($d['elements'], [], $cats, true)], 'optional' => 4, 'minRows' => 10, 'label' => 'the elements table'];
    }
    $blocks[] = ['h1', 'Scored reports'];
    $blocks[] = ['table', ['head' => ['Date', 'Report', 'Countries', 'Category scores'], 'widths' => [0.12, 0.4, 0.2, 0.28], 'small' => true,
                            'rows' => export_reports_rows($d['reports'], true)], 'optional' => 5, 'minRows' => 8, 'label' => 'the list of reports'];
    $blocks[] = ['note', export_method_note($d['method'], $d['cycle'] ? 'corroboration and recency' : 'corroboration')];

    $fit = docx_fit_pages($blocks, EXPORT_MAX_PAGES);
    return docx_build([
        'meta'       => 'CRTP, Hekima University College · Reflexive Peace Practice',
        'title'      => 'Conflict Heat Map: ' . $d['label'],
        'standfirst' => 'Intensity scores by country, monitoring region, category and element' . ($d['prevLabel'] ? ', compared with ' . $d['prevLabel'] : '') . '.',
        'blocks'     => $fit['blocks'],
        'closing'    => ($fit['trimmed'] ? 'To keep this document under ten pages, ' . implode(', ', $fit['trimmed']) . '. The complete figures are in the Excel version. ' : '')
                      . 'Generated by the Tafakari Digital Hub on ' . $d['generatedAt'] . '.',
    ]);
}

// ── Analysis reports ─────────────────────────────────────────────

/** Narrative Markdown for Word, with [n] markers and links left readable. */
function export_report_narrative(array $report): string {
    return trim(str_replace("\r\n", "\n", (string) ($report['narrative'] ?? '')));
}

/** An Outlook's projections as table rows. */
function export_outlook_rows(array $s, bool $word): array {
    $rows = [];
    foreach ($s['countries'] as $r) {
        foreach ($r['cells'] as $cat => $fc) {
            if (!$fc) continue;
            $rows[] = $word
                ? [$r['name'], $cat, export_num((float) $fc['last']), export_score_cell((float) $fc['next']), export_num((float) $fc['low']) . '–' . export_num((float) $fc['high']), $fc['direction'], $fc['confidence']]
                : [$r['name'], $cat, ['v' => (float) $fc['last'], 's' => 'num'], export_score_xcell((float) $fc['next']), ['v' => (float) $fc['low'], 's' => 'num'], ['v' => (float) $fc['high'], 's' => 'num'], $fc['direction'], $fc['confidence'], (int) $fc['n']];
        }
    }
    return $rows;
}

/** An analysis report as an Excel workbook. */
function report_xlsx(array $report): string {
    $s = $report['snapshotData'];
    $isOutlook = ($s['kind'] ?? '') === 'OUTLOOK';
    $cats = array_column($s['categories'] ?? [], 'name');
    $meta = [['Report', $report['title']], ['Type', report_kind_label($report['kind'])], ['Scope', $s['scope']['label']], ['Period', $s['period']['label']],
             [$isOutlook ? 'Based on cycles up to' : 'Previous period', $s['prev']['label']], ['Status', $report['status']], ['Data frozen', (string) $report['snapshotAt']]];
    $narr = export_report_narrative($report);
    if ($narr !== '') { $meta[] = ['', '']; $meta[] = ['Narrative', ['v' => $narr, 's' => 'wrap']]; }
    $sheets = [['name' => 'Summary', 'cols' => [['label' => 'Item', 'width' => 24], ['label' => 'Value', 'width' => 90]], 'rows' => $meta]];

    if ($isOutlook) {
        $sheets[] = ['name' => 'Projections', 'cols' => [['label' => 'Country', 'width' => 22], ['label' => 'Category', 'width' => 30], ['label' => 'Last observed', 'width' => 13], ['label' => 'Projected', 'width' => 11], ['label' => 'Range low', 'width' => 11], ['label' => 'Range high', 'width' => 11], ['label' => 'Direction', 'width' => 11], ['label' => 'Confidence', 'width' => 11], ['label' => 'Scored cycles', 'width' => 13]],
                     'rows' => export_outlook_rows($s, false)];
        return xlsx_build($sheets, ['title' => $report['title']]);
    }

    $catCols = array_map(fn($c) => ['label' => $c, 'width' => 16], $cats);
    $k = $s['kpis'];
    $sheets[0]['rows'] = array_merge($sheets[0]['rows'], [['', ''], ['Reports analysed', $k['reports']], ['Countries scored', $k['countries']], ['Critical flags', $k['critical']],
                                                          ['Escalating', $k['escalating']], ['De-escalating', $k['deescalating']], ['Cross-border', $k['crossBorder']]]);
    $sheets[] = ['name' => 'Ranking', 'cols' => [['label' => 'Rank', 'width' => 7], ['label' => 'Country', 'width' => 24], ['label' => 'Overall', 'width' => 10], ['label' => 'Tier', 'width' => 11], ['label' => 'Previous', 'width' => 10], ['label' => 'Change', 'width' => 9], ['label' => 'Previous rank', 'width' => 13], ['label' => 'Rank change', 'width' => 12], ['label' => 'Highest category', 'width' => 32]],
                 'rows' => array_map(fn($r) => [$r['rank'], $r['name'], export_score_xcell($r['overall']), $r['tier'], $r['prev'] !== null ? ['v' => $r['prev'], 's' => 'num'] : '', $r['change'], $r['rankPrev'] ?? '', $r['rankChange'] ?? '', $r['top']], $s['ranking'] ?? [])];
    $sheets[] = ['name' => 'By category', 'cols' => [['label' => 'Category', 'width' => 34], ['label' => 'Score', 'width' => 9], ['label' => 'Previous', 'width' => 10], ['label' => 'Change', 'width' => 9], ['label' => 'Reports', 'width' => 9]],
                 'rows' => array_map(function ($c) use ($s) {
                     $v = isset($s['total'][$c]) ? (float) $s['total'][$c]['score'] : null; $p = isset($s['totalPrev'][$c]) ? (float) $s['totalPrev'][$c]['score'] : null;
                     return [$c, export_score_xcell($v), $p !== null ? ['v' => $p, 's' => 'num'] : '', export_delta($v, $p), (int) ($s['total'][$c]['n'] ?? 0)];
                 }, $cats)];
    $sheets[] = ['name' => 'Countries', 'cols' => array_merge([['label' => 'Country', 'width' => 24]], $catCols, [['label' => 'Overall', 'width' => 10], ['label' => 'Change', 'width' => 9], ['label' => 'Reports', 'width' => 9]]),
                 'rows' => export_matrix_rows($s['countries'], $cats, false)];
    if (!empty($s['regions'])) {
        $sheets[] = ['name' => 'Regions', 'cols' => array_merge([['label' => 'Region', 'width' => 24]], $catCols, [['label' => 'Overall', 'width' => 10], ['label' => 'Change', 'width' => 9]]),
                     'rows' => export_matrix_rows($s['regions'], $cats, false, false)];
    }
    $sheets[] = ['name' => 'Elements', 'cols' => [['label' => 'Category', 'width' => 30], ['label' => 'Element', 'width' => 36], ['label' => 'Score', 'width' => 9], ['label' => 'Previous', 'width' => 10], ['label' => 'Change', 'width' => 9], ['label' => 'Reports', 'width' => 9]],
                 'rows' => export_elements_rows($s['elements'] ?? [], $s['elementsPrev'] ?? [], $cats, false)];
    $moves = [];
    foreach (['up' => 'Escalating', 'down' => 'De-escalating'] as $k2 => $lbl) foreach ($s['movers'][$k2] as $m) $moves[] = [$lbl, $m['country'], $m['category'], ['v' => $m['prev'], 's' => 'num'], ['v' => $m['now'], 's' => 'num'], sprintf('%+.1f', $m['delta'])];
    $sheets[] = ['name' => 'Movers', 'cols' => [['label' => 'Direction', 'width' => 14], ['label' => 'Country', 'width' => 22], ['label' => 'Category', 'width' => 32], ['label' => 'Previous', 'width' => 10], ['label' => 'Now', 'width' => 9], ['label' => 'Change', 'width' => 9]], 'rows' => $moves];
    $trendCols = [['label' => 'Category', 'width' => 32]];
    foreach ($s['trend']['cycles'] as $cy) $trendCols[] = ['label' => $cy['label'], 'width' => 12];
    $trend = [];
    foreach ($s['trend']['total'] as $cat => $vals) $trend[] = array_merge([$cat], array_map(fn($v) => $v === null ? '' : ['v' => (float) $v, 's' => 'num'], array_values($vals)));
    foreach ($s['trend']['byCountry'] as $country => $byCat) foreach ($byCat as $cat => $vals) $trend[] = array_merge([$country . ' · ' . $cat], array_map(fn($v) => $v === null ? '' : ['v' => (float) $v, 's' => 'num'], array_values($vals)));
    $sheets[] = ['name' => 'Trend', 'cols' => $trendCols, 'rows' => $trend];
    $base = defined('APP_URL') ? rtrim((string) constant('APP_URL'), '/') : '';
    $sheets[] = ['name' => 'Sources', 'cols' => [['label' => 'Date', 'width' => 12], ['label' => 'Report', 'width' => 50], ['label' => 'Countries', 'width' => 26], ['label' => 'Categories', 'width' => 40], ['label' => 'Scored by', 'width' => 12], ['label' => 'Link', 'width' => 40]],
                 'rows' => array_map(fn($r) => [$r['date'], $r['title'], implode(', ', $r['countries']), implode('; ', $r['categories']), $r['scoreSource'] ?? '', $base . $r['url']], $s['reports'])];
    return xlsx_build($sheets, ['title' => $report['title']]);
}

/** An analysis report as a Word document, under the page limit. */
function report_docx(array $report): string {
    $s = $report['snapshotData'];
    $isOutlook = ($s['kind'] ?? '') === 'OUTLOOK';
    $narr = export_report_narrative($report);
    $blocks = [['kv', [['Report', report_kind_label($report['kind'])], ['Scope', $s['scope']['label']], ['Period', $s['period']['label']],
                       [$isOutlook ? 'Based on cycles up to' : 'Compared with', $s['prev']['label']], ['Data frozen', format_date((string) $report['snapshotAt'], 'j M Y, H:i')]]]];
    if ($narr !== '') {
        $blocks[] = ['md', $narr];
        $blocks[] = ['small', report_narrative_label($report['narrativeSource'] ?? null)];
    }

    if ($isOutlook) {
        $blocks[] = ['h1', 'Projected intensity'];
        $blocks[] = ['p', 'Indicative projections for the next monitoring cycle, with a likely range. They extend past reporting and cannot foresee events.'];
        $blocks[] = ['table', ['head' => ['Country', 'Category', 'Last', 'Projected', 'Range', 'Direction', 'Confidence'], 'widths' => [0.18, 0.3, 0.09, 0.12, 0.12, 0.1, 0.09], 'small' => true,
                                'rows' => export_outlook_rows($s, true)], 'optional' => 1, 'minRows' => 20, 'label' => 'the projections table'];
    } else {
        $cats  = array_column($s['categories'], 'name');
        $short = export_short_cats($cats);
        $n = count($cats);
        $k = $s['kpis'];
        $blocks[] = ['h1', 'Headline figures'];
        $blocks[] = ['table', ['head' => ['Reports analysed', 'Countries scored', 'Critical flags', 'Escalating', 'De-escalating', 'Cross-border'],
                                'rows' => [array_map(fn($v) => ['text' => (string) $v, 'bold' => true, 'align' => 'center'], [$k['reports'], $k['countries'], $k['critical'], $k['escalating'], $k['deescalating'], $k['crossBorder']])]]];
        $blocks[] = ['h1', 'Intensity by category'];
        $blocks[] = ['table', ['head' => ['Category', 'Score', 'Tier', 'Previous', 'Change'], 'widths' => [0.44, 0.12, 0.16, 0.14, 0.14],
                                'rows' => array_map(function ($c) use ($s) {
                                    $v = isset($s['total'][$c]) ? (float) $s['total'][$c]['score'] : null; $p = isset($s['totalPrev'][$c]) ? (float) $s['totalPrev'][$c]['score'] : null;
                                    return [$c, export_score_cell($v), $v === null ? '—' : hm_tier_label($v), export_num($p), export_delta($v, $p)];
                                }, $cats)]];
        if (!empty($s['ranking']) && count($s['ranking']) > 1) {
            $blocks[] = ['h1', 'Comparative ratings'];
            $blocks[] = ['p', 'Countries ranked by overall score, with the change from ' . $s['prev']['label'] . ' and the category that scores highest.'];
            $blocks[] = ['table', ['head' => ['Rank', 'Country', 'Overall', 'Tier', 'Change', 'Rank change', 'Highest category'], 'widths' => [0.08, 0.22, 0.1, 0.12, 0.1, 0.12, 0.26], 'small' => true,
                                    'rows' => array_map(fn($r) => [(string) $r['rank'], $r['name'], export_score_cell($r['overall']), $r['tier'], $r['change'], $r['rankChange'] === null ? 'new' : ($r['rankChange'] === 0 ? '0' : ($r['rankChange'] > 0 ? '▲ ' . $r['rankChange'] : '▼ ' . abs($r['rankChange']))), $r['top']], $s['ranking'])],
                         'optional' => 1, 'minRows' => 15, 'label' => 'the comparative ratings'];
        }
        if (!empty($s['regions'])) {
            $blocks[] = ['h1', 'Regional overview'];
            $blocks[] = ['table', ['head' => array_merge(['Region'], $short, ['Overall', 'Change']), 'widths' => export_matrix_widths($n, false), 'small' => true,
                                    'rows' => export_matrix_rows($s['regions'], $cats, true, false)], 'optional' => 4, 'label' => 'the regional overview'];
        }
        if ($s['countries'] && $s['scope']['type'] !== 'COUNTRY') {
            $blocks[] = ['h1', 'By country'];
            $blocks[] = ['table', ['head' => array_merge(['Country'], $short, ['Overall', 'Change', 'Reports']), 'widths' => export_matrix_widths($n), 'small' => true,
                                    'rows' => export_matrix_rows($s['countries'], $cats, true)], 'optional' => 2, 'minRows' => 12, 'label' => 'the country table'];
        }
        $moves = [];
        foreach ($s['movers']['up'] as $m)   $moves[] = ['Escalating', $m['country'], $m['category'], export_num($m['prev']) . ' → ' . export_num($m['now']), sprintf('%+.1f', $m['delta'])];
        foreach ($s['movers']['down'] as $m) $moves[] = ['De-escalating', $m['country'], $m['category'], export_num($m['prev']) . ' → ' . export_num($m['now']), sprintf('%+.1f', $m['delta'])];
        if ($moves) {
            $blocks[] = ['h1', 'Escalation and de-escalation'];
            $blocks[] = ['table', ['head' => ['Direction', 'Country', 'Category', 'Score', 'Change'], 'widths' => [0.16, 0.22, 0.38, 0.14, 0.1], 'small' => true, 'rows' => $moves], 'optional' => 3, 'minRows' => 8, 'label' => 'the movers'];
        }
        if ($s['critical']) {
            $blocks[] = ['h1', 'Critical flags'];
            $blocks[] = ['table', ['head' => ['Date', 'Report', 'Countries', 'Category', 'Score'], 'widths' => [0.12, 0.4, 0.2, 0.2, 0.08], 'small' => true,
                                    'rows' => array_map(fn($c) => [$c['date'], $c['title'], implode(', ', $c['countries']), $c['category'], export_score_cell((float) $c['score'])], $s['critical'])], 'optional' => 3, 'minRows' => 8, 'label' => 'the critical flags'];
        }
        if (!empty($s['elements'])) {
            $blocks[] = ['h1', 'Scores by element'];
            $blocks[] = ['table', ['head' => ['Category', 'Element', 'Score', 'Change', 'Reports'], 'widths' => [0.3, 0.38, 0.1, 0.11, 0.11], 'small' => true,
                                    'rows' => export_elements_rows($s['elements'], $s['elementsPrev'] ?? [], $cats, true)], 'optional' => 5, 'minRows' => 10, 'label' => 'the elements table'];
        }
        $blocks[] = ['h1', 'Sources'];
        $blocks[] = ['p', 'Every published, scored report in this period and scope, newest first.'];
        $blocks[] = ['table', ['head' => ['Date', 'Report', 'Countries', 'Categories'], 'widths' => [0.12, 0.42, 0.2, 0.26], 'small' => true,
                                'rows' => array_map(fn($r) => [$r['date'], $r['title'], implode(', ', $r['countries']), implode('; ', $r['categories'])], $s['reports'])], 'optional' => 6, 'minRows' => 8, 'label' => 'the list of sources'];
        $blocks[] = ['note', export_method_note(['peak_weight' => $s['method']['peakWeight']], $s['method']['weighting']) . ($s['kind'] === 'QUARTER'
            ? ' Quarterly figures weight reports by corroboration only.' : ' Bi-weekly figures match the heat map for that monitoring cycle.')];
    }

    $fit = docx_fit_pages($blocks, EXPORT_MAX_PAGES);
    return docx_build([
        'banner'     => $report['status'] !== 'PUBLISHED' ? strtoupper($report['status'] === 'PENDING' ? 'Awaiting approval' : 'Draft') . ' · not yet approved for publication' : null,
        'meta'       => 'CRTP, Hekima University College · Reflexive Peace Practice',
        'title'      => $report['title'],
        'standfirst' => report_kind_label($report['kind']) . ' · ' . $s['scope']['label'] . ' · ' . $s['period']['label'],
        'blocks'     => $fit['blocks'],
        'closing'    => ($fit['trimmed'] ? 'To keep this document under ten pages, ' . implode(', ', $fit['trimmed']) . '. The complete figures are in the Excel version. ' : '')
                      . ($report['status'] === 'PUBLISHED' && $report['publishedAt'] ? 'Published ' . format_date((string) $report['publishedAt']) . '. ' : '')
                      . 'Generated by the Tafakari Digital Hub on ' . date('j M Y, H:i') . '.',
    ]);
}

/** Handle ?format=csv|xlsx|docx for a report page and stop; return when no export was asked for. */
function report_export_if_requested(array $report): void {
    $format = (string) ($_GET['format'] ?? '');
    if (!in_array($format, ['csv', 'xlsx', 'docx'], true)) return;
    $name = 'tafakari-report-' . $report['periodStart'] . '-' . strtolower(str_replace(':', '-', (string) $report['scope']));
    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $name . '.csv"');
        echo report_csv($report);
        exit;
    }
    try {
        export_send($format === 'xlsx' ? report_xlsx($report) : report_docx($report), $name, $format);
    } catch (Throwable $e) {
        error_log('[report export] ' . $e->getMessage());
        http_response_code(500);
        exit('The file could not be created. Check that the PHP zip extension is enabled.');
    }
}
