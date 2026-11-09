<?php
declare(strict_types=1);
/**
 * Server-rendered SVG charts for the analysis reports.
 *
 * No JavaScript library and no CDN: the charts are plain inline SVG, so they
 * render anywhere, print to PDF, and work with scripts off. Every chart is
 * followed by a data-table view, the accessible equivalent, and every mark
 * carries a <title> for hover.
 *
 * Colour: the heat-map category colours are configurable in Admin and are not
 * a validated categorical palette, so charts never rely on them for identity.
 * Trends use small multiples (one category per panel, one brand hue), and
 * bars use one hue with the value and its severity tier written at the tip.
 */

const CH_ACCENT  = '#750B25';   // the one data hue (brand burgundy)
const CH_PREV    = '#94a3b8';   // de-emphasis: previous period
const CH_GRID    = '#e5e7eb';   // hairline grid / axes
const CH_TEXT    = '#0f172a';   // primary text
const CH_TEXT_2  = '#475569';   // secondary text
const CH_MUTED   = '#94a3b8';   // muted text
const CH_SURFACE = '#ffffff';   // chart surface (ring + gap colour)
const CH_CRIT    = '#ED1C24';   // status: critical (threshold rule only)

function ch_e(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8'); }
function ch_num(?float $v): string { return $v === null ? '—' : rtrim(rtrim(number_format($v, 1), '0'), '.'); }

/** Horizontal bar path: square at the baseline, 4px rounded data end. */
function ch_bar_path(float $x0, float $y, float $w, float $h): string {
    if ($w <= 0) return '';
    $r  = min(4.0, $w, $h / 2);
    $x1 = $x0 + $w;
    return sprintf('M%.1f %.1fH%.1fQ%.1f %.1f %.1f %.1fV%.1fQ%.1f %.1f %.1f %.1fH%.1fZ',
        $x0, $y, $x1 - $r, $x1, $y, $x1, $y + $r, $y + $h - $r, $x1, $y + $h, $x1 - $r, $y + $h, $x0);
}

/** A <details> data table, the accessible twin of a chart. */
function ch_table(array $head, array $rows, string $caption): string {
    $h = '<details class="ch-table"><summary>Show the data table</summary><div class="ch-table-wrap"><table>'
       . '<caption>' . ch_e($caption) . '</caption><thead><tr>';
    foreach ($head as $i => $c) $h .= '<th scope="col"' . ($i ? ' class="num"' : '') . '>' . ch_e($c) . '</th>';
    $h .= '</tr></thead><tbody>';
    foreach ($rows as $row) {
        $h .= '<tr>';
        foreach (array_values($row) as $i => $c) {
            $h .= $i === 0 ? '<th scope="row">' . ch_e((string) $c) . '</th>' : '<td class="num">' . ch_e((string) $c) . '</td>';
        }
        $h .= '</tr>';
    }
    return $h . '</tbody></table></div></details>';
}

/** Shared CSS for charts and their tables. Print-friendly. */
function ch_styles(): string {
    return '<style>
.ch-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(230px,1fr)); gap:14px; }
.ch-card { background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:12px 14px 8px; break-inside:avoid; }
.ch-card h4 { display:flex; align-items:flex-start; gap:7px; font:700 12px/1.3 Inter,sans-serif; color:' . CH_TEXT . '; margin:0 0 4px; min-height:2.6em; }
.ch-swatch { margin-top:3px; }
.ch-swatch { width:9px; height:9px; border-radius:3px; flex-shrink:0; }
.ch-svg { display:block; width:100%; height:auto; overflow:visible; }
.ch-bars { max-width:720px; }
.ch-svg text { font-family:Inter,sans-serif; font-variant-numeric:tabular-nums; }
.ch-hit { fill:transparent; cursor:default; }
.ch-hit:hover, .ch-hit:focus { fill:rgba(117,11,37,.06); outline:none; }
.ch-bar:hover { opacity:.82; }
.ch-legend { display:flex; flex-wrap:wrap; gap:14px; font:600 11px Inter,sans-serif; color:' . CH_TEXT_2 . '; margin:4px 0 12px; }
.ch-legend span { display:inline-flex; align-items:center; gap:6px; }
.ch-key-bar { width:14px; height:8px; border-radius:2px; background:' . CH_ACCENT . '; }
.ch-key-tick { width:2px; height:12px; background:' . CH_PREV . '; }
.ch-key-line { width:14px; height:2px; background:' . CH_ACCENT . '; border-radius:1px; }
.ch-empty { font:500 12px Inter,sans-serif; color:' . CH_MUTED . '; padding:18px 0; }
.ch-table { margin-top:8px; font:12px Inter,sans-serif; color:' . CH_TEXT_2 . '; }
.ch-table summary { cursor:pointer; font-weight:700; color:' . CH_ACCENT . '; width:fit-content; }
.ch-table-wrap { overflow-x:auto; margin-top:8px; }
.ch-table table { border-collapse:collapse; min-width:100%; }
.ch-table caption { text-align:left; font-weight:700; color:' . CH_TEXT . '; padding:4px 0 6px; }
.ch-table th, .ch-table td { padding:5px 10px; border-top:1px solid #f1f5f9; text-align:left; white-space:nowrap; }
.ch-table thead th { font-size:10px; text-transform:uppercase; letter-spacing:.06em; color:' . CH_MUTED . '; }
.ch-table .num { text-align:right; font-variant-numeric:tabular-nums; }
@media print {
  .ch-table { display:none; }
  .ch-card { border-color:#cbd5e1; }
}
</style>';
}

/**
 * Small multiples of 1-10 intensity over monitoring cycles: one panel per
 * category, a shared 0-10 scale, a hairline at the critical threshold (9).
 *
 * @param array<int,string> $labels        x labels (cycles)
 * @param array             $series        category => list of ?float, aligned to $labels
 * @param array<string,string> $swatches   category => colour (identity swatch beside the title)
 */
function chart_small_multiples(array $labels, array $series, array $swatches, string $caption): string {
    $W = 240; $H = 140; $L = 22; $R = 30; $T = 10; $B = 24;
    // Axis shows each cycle's start date; tooltips and the table keep the full range
    $axis = fn(string $l) => explode(' – ', $l)[0];
    $pw = $W - $L - $R; $ph = $H - $T - $B;
    $n  = count($labels);
    $x  = fn(int $i) => $L + ($n <= 1 ? $pw / 2 : $pw * $i / ($n - 1));
    $y  = fn(float $v) => $T + $ph * (1 - $v / 10);

    $html = '<div class="ch-grid">';
    foreach ($series as $cat => $vals) {
        $has = array_filter($vals, fn($v) => $v !== null);
        $html .= '<figure class="ch-card" style="margin:0"><h4><span class="ch-swatch" style="background:' . ch_e($swatches[$cat] ?? CH_MUTED) . '"></span>'
               . ch_e($cat) . '</h4>';
        if (!$has) {
            $html .= '<p class="ch-empty">No scores in this window.</p></figure>';
            continue;
        }
        $last = null; foreach ($vals as $i => $v) if ($v !== null) $last = $i;
        $aria = $cat . ': ' . implode(', ', array_map(fn($i) => $labels[$i] . ' ' . ch_num($vals[$i]), array_keys($has)));
        $s = '<svg class="ch-svg" viewBox="0 0 ' . $W . ' ' . $H . '" role="img" aria-label="' . ch_e($aria) . '">';
        // Grid and y ticks
        foreach ([0, 5, 10] as $t) {
            $s .= sprintf('<line x1="%d" x2="%d" y1="%.1f" y2="%.1f" stroke="%s" stroke-width="1"/>', $L, $W - $R, $y($t), $y($t), CH_GRID);
            $s .= sprintf('<text x="%d" y="%.1f" font-size="10" fill="%s" text-anchor="end" dominant-baseline="middle">%d</text>', $L - 6, $y($t), CH_MUTED, $t);
        }
        // Critical threshold
        $s .= sprintf('<line x1="%d" x2="%d" y1="%.1f" y2="%.1f" stroke="%s" stroke-opacity=".45" stroke-width="1"/>', $L, $W - $R, $y(9), $y(9), CH_CRIT);
        // X labels: first and last only, so they never collide
        $s .= sprintf('<text x="%.1f" y="%d" font-size="10" fill="%s" text-anchor="start">%s</text>', $x(0) - 4, $H - 6, CH_MUTED, ch_e($axis($labels[0])));
        if ($n > 1) $s .= sprintf('<text x="%.1f" y="%d" font-size="10" fill="%s" text-anchor="end">%s</text>', $x($n - 1) + 4, $H - 6, CH_MUTED, ch_e($axis($labels[$n - 1])));
        // Line, broken across cycles with no score
        $seg = [];
        foreach ($vals as $i => $v) {
            if ($v === null) { if (count($seg) > 1) $s .= ch_polyline($seg); $seg = []; continue; }
            $seg[] = [$x($i), $y($v)];
        }
        if (count($seg) > 1) $s .= ch_polyline($seg);
        // Points with a surface ring; value labelled at the end only
        foreach ($vals as $i => $v) {
            if ($v === null) continue;
            $s .= sprintf('<circle cx="%.1f" cy="%.1f" r="4" fill="%s" stroke="%s" stroke-width="2"/>', $x($i), $y($v), CH_ACCENT, CH_SURFACE);
        }
        if ($last !== null) {
            $s .= sprintf('<text x="%.1f" y="%.1f" font-size="12" font-weight="700" fill="%s" dominant-baseline="middle">%s</text>',
                $x($last) + 8, $y($vals[$last]), CH_TEXT, ch_e(ch_num($vals[$last])));
        }
        // Hover bands, one per cycle, wider than the marks
        $band = $n > 1 ? $pw / ($n - 1) : $pw;
        foreach ($labels as $i => $lab) {
            $v = $vals[$i] ?? null;
            $tip = $lab . ': ' . ($v === null ? 'no score' : ch_num($v) . ' / 10 (' . hm_tier_label($v) . ')');
            $s .= sprintf('<rect class="ch-hit" x="%.1f" y="%d" width="%.1f" height="%d" tabindex="0" aria-label="%s"><title>%s</title></rect>',
                max(0, $x($i) - $band / 2), $T, $band, $ph, ch_e($tip), ch_e($tip));
        }
        $html .= $s . '</svg></figure>';
    }
    $html .= '</div>';

    $rows = [];
    foreach ($labels as $i => $lab) {
        $row = [$lab];
        foreach ($series as $vals) $row[] = ch_num($vals[$i] ?? null);
        $rows[] = $row;
    }
    return $html . ch_table(array_merge(['Cycle'], array_keys($series)), $rows, $caption);
}

function ch_polyline(array $pts): string {
    return '<polyline fill="none" stroke="' . CH_ACCENT . '" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" points="'
         . implode(' ', array_map(fn($p) => sprintf('%.1f,%.1f', $p[0], $p[1]), $pts)) . '"/>';
}

/**
 * Horizontal bars of 1-10 scores, one hue, value and tier at the tip, with an
 * optional previous-period tick.
 *
 * @param array $rows list of ['label' => string, 'value' => ?float, 'prev' => ?float]
 */
function chart_score_bars(array $rows, string $caption, string $prevLabel = 'Previous period'): string {
    $rows = array_values(array_filter($rows, fn($r) => $r['value'] !== null || ($r['prev'] ?? null) !== null));
    if (!$rows) return '<p class="ch-empty">No scores in this period.</p>';

    $labW = 230; $valW = 110; $barH = 16; $gap = 14; $W = 640;
    $pw = $W - $labW - $valW;
    $H  = count($rows) * ($barH + $gap) + 22;
    $x  = fn(float $v) => $labW + $pw * $v / 10;
    $hasPrev = (bool) array_filter($rows, fn($r) => ($r['prev'] ?? null) !== null);

    $s = '<svg class="ch-svg ch-bars" viewBox="0 0 ' . $W . ' ' . $H . '" role="img" aria-label="' . ch_e($caption) . '">';
    foreach ([0, 5, 10] as $t) {
        $s .= sprintf('<line x1="%.1f" x2="%.1f" y1="0" y2="%d" stroke="%s" stroke-width="1"/>', $x($t), $x($t), $H - 18, CH_GRID);
        $s .= sprintf('<text x="%.1f" y="%d" font-size="10" fill="%s" text-anchor="middle">%d</text>', $x($t), $H - 4, CH_MUTED, $t);
    }
    foreach ($rows as $i => $r) {
        $y0 = $i * ($barH + $gap) + 4;
        $v  = $r['value']; $p = $r['prev'] ?? null;
        $tip = $r['label'] . ': ' . ($v === null ? 'no score' : ch_num($v) . ' / 10 (' . hm_tier_label($v) . ')')
             . ($p !== null ? '; previous ' . ch_num($p) : '');
        $s .= sprintf('<text x="%d" y="%.1f" font-size="12" fill="%s" dominant-baseline="middle">%s</text>',
            0, $y0 + $barH / 2, CH_TEXT_2, ch_e(mb_strlen($r['label']) > 34 ? mb_substr($r['label'], 0, 33) . '…' : $r['label']));
        if ($v !== null) {
            $s .= '<path class="ch-bar" d="' . ch_bar_path($labW, $y0, $pw * $v / 10, $barH) . '" fill="' . CH_ACCENT . '"><title>' . ch_e($tip) . '</title></path>';
            $s .= sprintf('<text x="%.1f" y="%.1f" font-size="11" font-weight="700" fill="%s" dominant-baseline="middle">%s <tspan font-weight="500" fill="%s">%s</tspan></text>',
                $x(max($v, $p ?? 0)) + 8, $y0 + $barH / 2, CH_TEXT, ch_e(ch_num($v)), CH_TEXT_2, ch_e(hm_tier_label($v)));
        }
        if ($p !== null) {
            $s .= sprintf('<rect x="%.1f" y="%.1f" width="2" height="%d" fill="%s"><title>%s</title></rect>', $x($p) - 1, $y0 - 3, $barH + 6, CH_PREV, ch_e($tip));
        }
        $s .= sprintf('<rect class="ch-hit" x="0" y="%.1f" width="%d" height="%d" tabindex="0" aria-label="%s"><title>%s</title></rect>',
            $y0 - $gap / 2, $W, $barH + $gap, ch_e($tip), ch_e($tip));
    }
    $s .= '</svg>';

    $legend = $hasPrev
        ? '<div class="ch-legend"><span><i class="ch-key-bar"></i>This period</span><span><i class="ch-key-tick"></i>' . ch_e($prevLabel) . '</span></div>'
        : '';
    $table = ch_table(['Category', 'Score', 'Tier', 'Previous', 'Change'], array_map(fn($r) => [
        $r['label'], ch_num($r['value']), $r['value'] !== null ? hm_tier_label($r['value']) : '—', ch_num($r['prev'] ?? null),
        ($r['value'] !== null && ($r['prev'] ?? null) !== null) ? sprintf('%+.1f', $r['value'] - $r['prev']) : '—',
    ], $rows), $caption);
    return $legend . $s . $table;
}

/**
 * Share of reports by category as ranked bars (largest first, one hue). The
 * honest form of a pie: bars on one baseline compare parts exactly.
 *
 * @param array $rows list of ['label' => string, 'count' => int, 'pct' => int], pre-sorted
 */
function chart_share_bars(array $rows, int $total, string $caption, int $W = 640, string $unit = 'report', string $units = 'reports'): string {
    if (!$rows || $total === 0) return '<p class="ch-empty">No scored reports in this period.</p>';
    // $W is the design width: pass the card's real width (e.g. 360) so text renders at its intended size
    $labW = (int) round($W * 0.34); $valW = (int) round($W * 0.24); $barH = 16; $gap = 14;
    $pw = $W - $labW - $valW;
    $H  = count($rows) * ($barH + $gap) + 4;
    $s  = '<svg class="ch-svg ch-bars" viewBox="0 0 ' . $W . ' ' . $H . '" role="img" aria-label="' . ch_e($caption) . '">';
    $s .= sprintf('<line x1="%d" x2="%d" y1="0" y2="%d" stroke="%s" stroke-width="1"/>', $labW, $labW, $H, CH_GRID);
    foreach ($rows as $i => $r) {
        $y0 = $i * ($barH + $gap) + 4;
        $w  = $pw * $r['pct'] / 100;
        $tip = sprintf('%s: %d of %d %s (%d%%)', $r['label'], $r['count'], $total, $units, $r['pct']);
        $s .= sprintf('<text x="0" y="%.1f" font-size="12" fill="%s" dominant-baseline="middle">%s</text>', $y0 + $barH / 2, CH_TEXT_2, ch_e($r['label']));
        $s .= '<path class="ch-bar" d="' . ch_bar_path($labW, $y0, max($w, 2), $barH) . '" fill="' . CH_ACCENT . '"><title>' . ch_e($tip) . '</title></path>';
        $s .= sprintf('<text x="%.1f" y="%.1f" font-size="11" font-weight="700" fill="%s" dominant-baseline="middle">%d%% <tspan font-weight="500" fill="%s">%d %s</tspan></text>',
            $labW + max($w, 2) + 8, $y0 + $barH / 2, CH_TEXT, $r['pct'], CH_TEXT_2, $r['count'], ch_e($r['count'] === 1 ? $unit : $units));
        $s .= sprintf('<rect class="ch-hit" x="0" y="%.1f" width="%d" height="%d" tabindex="0" aria-label="%s"><title>%s</title></rect>',
            $y0 - $gap / 2, $W, $barH + $gap, ch_e($tip), ch_e($tip));
    }
    $s .= '</svg>';
    return $s . ch_table(['Category', ucfirst($units), 'Share'], array_map(fn($r) => [$r['label'], $r['count'], $r['pct'] . '%'], $rows), $caption);
}

/**
 * Small multiples of observed intensity with a one-cycle projection: observed
 * in a solid line; the projection dashed, with a hollow marker and its likely
 * range drawn as a bar, so it can never be mistaken for data.
 *
 * @param array<int,string> $labels observed cycles
 * @param array $series    category => list of ?float
 * @param array $forecasts category => forecast_series() result or null
 */
function chart_forecast_multiples(array $labels, string $nextLabel, array $series, array $forecasts, array $swatches, string $caption): string {
    $W = 240; $H = 140; $L = 22; $R = 34; $T = 10; $B = 24;
    $pw = $W - $L - $R; $ph = $H - $T - $B;
    $n  = count($labels) + 1;
    $x  = fn(int $i) => $L + $pw * $i / max(1, $n - 1);
    $y  = fn(float $v) => $T + $ph * (1 - $v / 10);
    $axis = fn(string $l) => explode(' – ', $l)[0];

    $html = '<div class="ch-legend"><span><i class="ch-key-line"></i>Observed</span>'
          . '<span><svg width="24" height="4" viewBox="0 0 24 4" aria-hidden="true"><line x1="1" y1="2" x2="23" y2="2" stroke="' . CH_ACCENT . '" stroke-width="2" stroke-dasharray="4 3" stroke-linecap="round"/></svg>Projected, with likely range</span></div><div class="ch-grid">';
    foreach ($series as $cat => $vals) {
        $vals = array_values($vals);
        $fc   = $forecasts[$cat] ?? null;
        $has  = array_filter($vals, fn($v) => $v !== null);
        $html .= '<figure class="ch-card" style="margin:0"><h4><span class="ch-swatch" style="background:' . ch_e($swatches[$cat] ?? CH_MUTED) . '"></span>' . ch_e($cat) . '</h4>';
        if (!$has) { $html .= '<p class="ch-empty">No scores in this window.</p></figure>'; continue; }
        $aria = $cat . ($fc ? ': projected ' . ch_num($fc['next']) . ', ' . strtolower($fc['direction']) . ', ' . strtolower($fc['confidence']) . ' confidence' : ': not enough data to project');
        $s = '<svg class="ch-svg" viewBox="0 0 ' . $W . ' ' . $H . '" role="img" aria-label="' . ch_e($aria) . '">';
        foreach ([0, 5, 10] as $t) {
            $s .= sprintf('<line x1="%d" x2="%d" y1="%.1f" y2="%.1f" stroke="%s" stroke-width="1"/>', $L, $W - $R, $y($t), $y($t), CH_GRID);
            $s .= sprintf('<text x="%d" y="%.1f" font-size="10" fill="%s" text-anchor="end" dominant-baseline="middle">%d</text>', $L - 6, $y($t), CH_MUTED, $t);
        }
        $s .= sprintf('<line x1="%d" x2="%d" y1="%.1f" y2="%.1f" stroke="%s" stroke-opacity=".45" stroke-width="1"/>', $L, $W - $R, $y(9), $y(9), CH_CRIT);
        $s .= sprintf('<text x="%.1f" y="%d" font-size="10" fill="%s">%s</text>', $x(0) - 4, $H - 6, CH_MUTED, ch_e($axis($labels[0])));
        $s .= sprintf('<text x="%.1f" y="%d" font-size="10" fill="%s" text-anchor="end">%s</text>', $x($n - 1) + 4, $H - 6, CH_MUTED, ch_e($nextLabel));
        $seg = []; $last = null;
        foreach ($vals as $i => $v) {
            if ($v === null) { if (count($seg) > 1) $s .= ch_polyline($seg); $seg = []; continue; }
            $seg[] = [$x($i), $y($v)]; $last = $i;
        }
        if (count($seg) > 1) $s .= ch_polyline($seg);
        foreach ($vals as $i => $v) {
            if ($v !== null) $s .= sprintf('<circle cx="%.1f" cy="%.1f" r="4" fill="%s" stroke="%s" stroke-width="2"><title>%s: %s</title></circle>', $x($i), $y($v), CH_ACCENT, CH_SURFACE, ch_e($labels[$i]), ch_e(ch_num($v)));
        }
        if ($fc && $last !== null) {
            $tx = $x($n - 1);
            $tip = sprintf('%s: projected %s (likely %s to %s), %s, %s confidence', $nextLabel, ch_num($fc['next']), ch_num($fc['low']), ch_num($fc['high']), strtolower($fc['direction']), strtolower($fc['confidence']));
            $s .= sprintf('<rect x="%.1f" y="%.1f" width="10" height="%.1f" rx="4" fill="%s" fill-opacity=".14"/>', $tx - 5, $y($fc['high']), max(2, $y($fc['low']) - $y($fc['high'])), CH_ACCENT);
            $s .= sprintf('<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="%s" stroke-width="2" stroke-dasharray="4 3" stroke-linecap="round"/>', $x($last), $y($vals[$last]), $tx, $y($fc['next']), CH_ACCENT);
            $s .= sprintf('<circle cx="%.1f" cy="%.1f" r="4" fill="%s" stroke="%s" stroke-width="2"><title>%s</title></circle>', $tx, $y($fc['next']), CH_SURFACE, CH_ACCENT, ch_e($tip));
            $s .= sprintf('<text x="%.1f" y="%.1f" font-size="12" font-weight="700" fill="%s" dominant-baseline="middle">%s</text>', $tx + 8, $y($fc['next']), CH_TEXT, ch_e(ch_num($fc['next'])));
        }
        $html .= $s . '</svg>' . ($fc ? '' : '<p class="ch-empty" style="padding:4px 0 6px">Not enough recent data to project.</p>') . '</figure>';
    }
    $html .= '</div>';
    $rows = [];
    foreach ($series as $cat => $vals) {
        $fc = $forecasts[$cat] ?? null;
        $rows[] = [$cat, $fc ? ch_num($fc['last']) : '—', $fc ? ch_num($fc['next']) : '—', $fc ? ch_num($fc['low']) . ' to ' . ch_num($fc['high']) : '—', $fc['direction'] ?? '—', $fc['confidence'] ?? '—'];
    }
    return $html . ch_table(['Category', 'Last observed', 'Projected', 'Likely range', 'Direction', 'Confidence'], $rows, $caption);
}
