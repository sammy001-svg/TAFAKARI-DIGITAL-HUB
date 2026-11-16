<?php
declare(strict_types=1);
/**
 * Minimal Word (.docx) writer for Tafakari articles, reports and updates.
 *
 * Builds the file directly with ZipArchive, so it needs only PHP's zip
 * extension. Headings use Word's built-in Heading styles, so the navigation
 * pane and tables of contents work when an editor opens the file.
 *
 * Body text is the site's Markdown subset: "## " and "### " headings, "- "
 * bullets, "1. " numbered items, "> " quotes, pipe tables, **bold** and
 * *italic*. Richer documents pass a list of blocks (see docx_blocks()),
 * which can be sized and trimmed to a page limit with docx_fit_pages().
 */

const DOCX_NS = 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
              . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"';
const DOCX_TEXT_WIDTH = 9298;   // twips between the margins of an A4 page

function docx_available(): bool {
    return class_exists('ZipArchive');
}

/** XML-safe text: drops characters XML 1.0 forbids, which would corrupt the file. */
function docx_esc(string $s): string {
    $s = mb_scrub($s, 'UTF-8');
    $s = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $s) ?? '';
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function docx_run(string $text, string $props = ''): string {
    if ($text === '') return '';
    return '<w:r>' . ($props !== '' ? "<w:rPr>{$props}</w:rPr>" : '')
         . '<w:t xml:space="preserve">' . docx_esc($text) . '</w:t></w:r>';
}

/** Runs for one line of text, honouring **bold** and *italic*; links keep their text. */
function docx_inline(string $text, string $base = ''): string {
    $text = preg_replace('/\[([^\]]+)\]\([^)]*\)/u', '$1', $text) ?? $text;
    $parts = preg_split('/(\*\*[^*\n]+\*\*|\*[^*\n]+\*)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
    $xml = '';
    foreach ($parts as $p) {
        if (preg_match('/^\*\*([^*\n]+)\*\*$/u', $p, $m))  $xml .= docx_run($m[1], $base . '<w:b/><w:bCs/>');
        elseif (preg_match('/^\*([^*\n]+)\*$/u', $p, $m))  $xml .= docx_run($m[1], $base . '<w:i/><w:iCs/>');
        else                                               $xml .= docx_run($p, $base);
    }
    return $xml;
}

function docx_para(string $style, string $runs, string $extraPpr = ''): string {
    return '<w:p><w:pPr><w:pStyle w:val="' . $style . '"/>' . $extraPpr . '</w:pPr>' . $runs . '</w:p>';
}

/** One table row of a Markdown pipe table, split into cells. */
function docx_pipe_cells(string $line): array {
    $line = trim($line);
    $line = preg_replace('/^\||\|$/u', '', $line) ?? $line;
    return array_map('trim', explode('|', $line));
}

/** Word paragraphs for a Markdown body. */
function docx_body(string $md): string {
    $xml  = '';
    $para = [];
    $flush = function () use (&$para, &$xml): void {
        if ($para) $xml .= docx_para('Normal', docx_inline(implode(' ', $para)));
        $para = [];
    };
    $lines = preg_split('/\R/u', $md) ?: [];
    $n = count($lines);
    for ($i = 0; $i < $n; $i++) {
        $t = trim($lines[$i]);
        if ($t === '') { $flush(); continue; }
        // A pipe table: a header row, a separator row, then data rows
        if (str_starts_with($t, '|') && isset($lines[$i + 1]) && preg_match('/^\|?\s*:?-{2,}/', trim($lines[$i + 1]))) {
            $flush();
            $head = docx_pipe_cells($t);
            $rows = [];
            for ($j = $i + 2; $j < $n && str_starts_with(trim($lines[$j]), '|'); $j++) $rows[] = docx_pipe_cells($lines[$j]);
            $xml .= docx_table(['head' => $head, 'rows' => $rows]);
            $i = $j - 1;
            continue;
        }
        if (preg_match('/^(#{1,6})\s+(.+)$/u', $t, $m)) {
            $flush();
            $xml .= docx_para(strlen($m[1]) <= 2 ? 'Heading1' : 'Heading2', docx_inline($m[2]));
        } elseif (preg_match('/^[-*]\s+(.+)$/u', $t, $m)) {
            $flush();
            $xml .= docx_para('ListBullet', docx_inline($m[1]));
        } elseif (preg_match('/^\d+[.)]\s+(.+)$/u', $t, $m)) {
            $flush();
            $xml .= docx_para('ListNumber', docx_inline($m[1]));
        } elseif (preg_match('/^>\s*(.+)$/u', $t, $m)) {
            $flush();
            $xml .= docx_para('Quote', docx_inline($m[1]));
        } elseif ($t === '---') {
            $flush();
        } else {
            $para[] = $t;
        }
    }
    $flush();
    return $xml;
}

/** Fill and text colours for a 1-10 score, matching the heat map's tiers. */
function docx_tier_fill(float $v): string {
    if ($v >= 9) return 'ED1C24';
    if ($v >= 7) return 'E7952A';
    if ($v >= 5) return 'F59E0B';
    if ($v >= 2) return '65A30D';
    return '059669';
}

/**
 * A table. $t = ['head' => string[], 'rows' => array<array<cell>>, 'widths' => float[] (fractions),
 *                'small' => bool]. A cell is a string or number, or
 *                ['text' => string, 'fill' => hex, 'color' => hex, 'bold' => bool, 'align' => 'center'|'right',
 *                 'tier' => true (shade by score), 'span' => int].
 */
function docx_table(array $t): string {
    $head  = array_values($t['head'] ?? []);
    $rows  = array_values($t['rows'] ?? []);
    $nCols = max(count($head), ...array_map(fn($r) => count((array) $r), $rows ?: [[]]));
    if ($nCols === 0) return '';
    $widths = $t['widths'] ?? [];
    if (count($widths) !== $nCols) $widths = array_fill(0, $nCols, 1 / $nCols);
    $sum = array_sum($widths) ?: 1;
    $tw = array_map(fn($w) => (int) round(DOCX_TEXT_WIDTH * $w / $sum), $widths);
    $style = !empty($t['small']) ? 'TableSmall' : 'TableText';

    $cell = function (mixed $c, int $i, bool $isHead) use ($tw, $style, $nCols): string {
        $d = is_array($c) ? $c : ['text' => $c];
        $text = $d['text'] ?? '';
        $text = is_float($text) ? rtrim(rtrim(number_format($text, 1, '.', ''), '0'), '.') : (string) $text;
        $fill = $d['fill'] ?? ($isHead ? 'F1F5F9' : '');
        $color = $d['color'] ?? '';
        $bold = $isHead || !empty($d['bold']);
        $align = $d['align'] ?? '';
        if (!empty($d['tier']) && is_numeric($d['text'] ?? null)) {
            $fill = docx_tier_fill((float) $d['text']); $color = 'FFFFFF'; $bold = true; $align = 'center';
        }
        $span = max(1, (int) ($d['span'] ?? 1));
        $w = 0;
        for ($k = $i; $k < min($nCols, $i + $span); $k++) $w += $tw[$k] ?? 0;
        $rpr = ($bold ? '<w:b/><w:bCs/>' : '') . ($color !== '' ? '<w:color w:val="' . $color . '"/>' : '');
        $ppr = $align !== '' ? '<w:jc w:val="' . $align . '"/>' : '';
        $tcPr = '<w:tcW w:w="' . $w . '" w:type="dxa"/>' . ($span > 1 ? '<w:gridSpan w:val="' . $span . '"/>' : '')
              . ($fill !== '' ? '<w:shd w:val="clear" w:color="auto" w:fill="' . $fill . '"/>' : '') . '<w:vAlign w:val="center"/>';
        $paras = '';
        foreach (preg_split('/\R/u', $text) ?: [''] as $line) $paras .= docx_para($style, docx_inline($line, $rpr), $ppr);
        return '<w:tc><w:tcPr>' . $tcPr . '</w:tcPr>' . $paras . '</w:tc>';
    };

    $grid = '';
    foreach ($tw as $w) $grid .= '<w:gridCol w:w="' . $w . '"/>';
    $border = fn(string $side) => '<w:' . $side . ' w:val="single" w:sz="4" w:space="0" w:color="CBD5E1"/>';
    $xml = '<w:tbl><w:tblPr><w:tblW w:w="' . DOCX_TEXT_WIDTH . '" w:type="dxa"/><w:tblBorders>'
         . $border('top') . $border('left') . $border('bottom') . $border('right') . $border('insideH') . $border('insideV')
         . '</w:tblBorders><w:tblLayout w:type="fixed"/><w:tblCellMar><w:top w:w="40" w:type="dxa"/><w:left w:w="80" w:type="dxa"/>'
         . '<w:bottom w:w="40" w:type="dxa"/><w:right w:w="80" w:type="dxa"/></w:tblCellMar><w:tblLook w:val="04A0"/></w:tblPr>'
         . '<w:tblGrid>' . $grid . '</w:tblGrid>';
    if ($head) {
        $xml .= '<w:tr><w:trPr><w:tblHeader/><w:cantSplit/></w:trPr>';
        foreach ($head as $i => $c) $xml .= $cell($c, $i, true);
        $xml .= '</w:tr>';
    }
    foreach ($rows as $r) {
        $r = array_values((array) $r);
        $xml .= '<w:tr><w:trPr><w:cantSplit/></w:trPr>';
        $i = 0;
        foreach ($r as $c) {
            $xml .= $cell($c, $i, false);
            $i += max(1, (int) (is_array($c) ? ($c['span'] ?? 1) : 1));
        }
        for (; $i < $nCols; $i++) $xml .= $cell('', $i, false);
        $xml .= '</w:tr>';
    }
    return $xml . '</w:tbl><w:p><w:pPr><w:pStyle w:val="TableAfter"/></w:pPr></w:p>';
}

/**
 * Blocks: ['h1', text] ['h2', text] ['p', text] ['md', markdown] ['bullets', [items]]
 *         ['table', table-array] ['kv', [[label, value], ...]] ['note', text] ['break']
 * Each block may carry extra keys: 'optional' => int (trim priority; higher goes first)
 * and, for tables, 'minRows' => int. These are only read by docx_fit_pages().
 */
function docx_blocks(array $blocks): string {
    $xml = '';
    foreach ($blocks as $b) {
        switch ($b[0] ?? '') {
            case 'h1':      $xml .= docx_para('Heading1', docx_inline((string) $b[1])); break;
            case 'h2':      $xml .= docx_para('Heading2', docx_inline((string) $b[1])); break;
            case 'p':       if (trim((string) $b[1]) !== '') $xml .= docx_para('Normal', docx_inline((string) $b[1])); break;
            case 'md':      $xml .= docx_body((string) $b[1]); break;
            case 'note':    $xml .= docx_para('Closing', docx_inline((string) $b[1])); break;
            case 'small':   $xml .= docx_para('Small', docx_inline((string) $b[1])); break;
            case 'bullets': foreach ((array) $b[1] as $it) $xml .= docx_para('ListBullet', docx_inline((string) $it)); break;
            case 'table':   $xml .= docx_table((array) $b[1]); break;
            case 'kv':
                $rows = [];
                foreach ((array) $b[1] as [$k, $v]) $rows[] = [['text' => (string) $k, 'bold' => true, 'fill' => 'F8F8F0'], (string) $v];
                $xml .= docx_table(['rows' => $rows, 'widths' => [0.32, 0.68], 'small' => true]);
                break;
            case 'break':   $xml .= '<w:p><w:r><w:br w:type="page"/></w:r></w:p>'; break;
        }
    }
    return $xml;
}

// ── Page budget ───────────────────────────────────────────────────

/** Lines of text a piece of prose takes at this width (A4, 11 pt): a rough, deliberately generous count. */
function docx_lines(string $text, float $widthFraction = 1.0): float {
    $words = (int) preg_match_all('/\S+/u', $text);
    $perLine = max(2.0, 14.0 * $widthFraction);
    $lines = 0.0;
    foreach (preg_split('/\R/u', $text) ?: [''] as $l) $lines += max(1.0, ceil(((int) preg_match_all('/\S+/u', $l)) / $perLine));
    return $words === 0 ? 1.0 : $lines;
}

/** Approximate length of a block list in pages (about 44 lines per page). */
function docx_estimate_pages(array $blocks): float {
    $lines = 0.0;
    $page  = 44.0;
    foreach ($blocks as $b) {
        switch ($b[0] ?? '') {
            case 'h1':   $lines += 2.6; break;
            case 'h2':   $lines += 2.0; break;
            case 'p': case 'note': case 'small': $lines += docx_lines((string) $b[1]) + 0.6; break;
            case 'md':
                foreach (preg_split('/\n{2,}/u', (string) $b[1]) ?: [] as $para) {
                    $t = trim($para);
                    if ($t === '') continue;
                    $lines += preg_match('/^#{1,6}\s/', $t) ? 2.0 : docx_lines(preg_replace('/^[-*>\d.]+\s+/mu', '', $t) ?? $t) + 0.6;
                }
                break;
            case 'bullets': foreach ((array) $b[1] as $it) $lines += docx_lines((string) $it, 0.92); break;
            case 'kv':      foreach ((array) $b[1] as $kv) $lines += max(1.0, docx_lines((string) ($kv[1] ?? ''), 0.68)) * 1.15 + 0.15; $lines += 1; break;
            case 'table':
                $t = (array) $b[1];
                $nCols = max(1, count($t['head'] ?? []) ?: count((array) (($t['rows'] ?? [[]])[0] ?? [])));
                $widths = $t['widths'] ?? array_fill(0, $nCols, 1 / $nCols);
                $rowLines = function (array $r) use ($widths, $nCols): float {
                    $m = 1.0;
                    foreach (array_values($r) as $i => $c) {
                        $txt = is_array($c) ? (string) ($c['text'] ?? '') : (string) $c;
                        $m = max($m, docx_lines($txt, (float) ($widths[$i] ?? 1 / $nCols)));
                    }
                    return $m * (!empty($t['small']) ? 0.9 : 1.1) + 0.25;
                };
                if (!empty($t['head'])) $lines += $rowLines($t['head']);
                foreach ((array) ($t['rows'] ?? []) as $r) $lines += $rowLines((array) $r);
                $lines += 1.2;
                break;
            case 'break': $lines = ceil($lines / $page) * $page; break;
        }
    }
    return round($lines / $page, 2);
}

/**
 * Trim a block list until it fits the page limit: optional blocks go first
 * (highest 'optional' value first); tables shrink to their 'minRows' before
 * they are dropped. Returns the blocks and what was trimmed, for a note.
 *
 * @return array{blocks:array,pages:float,trimmed:string[]}
 */
function docx_fit_pages(array $blocks, float $maxPages): array {
    $trimmed = [];
    $blocks  = array_values($blocks);
    for ($guard = 0; $guard < 60; $guard++) {
        $pages = docx_estimate_pages($blocks);
        if ($pages <= $maxPages) break;
        // The most dispensable block still present
        $best = null; $bestPri = -1;
        foreach ($blocks as $i => $b) {
            $pri = (int) ($b['optional'] ?? 0);
            if ($pri > $bestPri) { $bestPri = $pri; $best = $i; }
        }
        if ($best === null || $bestPri <= 0) break;
        $b = $blocks[$best];
        $label = (string) ($b['label'] ?? ($b[0] === 'table' ? 'a table' : 'a section'));
        if ($b[0] === 'table') {
            $rows = array_values((array) ($b[1]['rows'] ?? []));
            $min  = max(0, (int) ($b['minRows'] ?? 0));
            if (count($rows) > $min) {
                $keep = max($min, (int) floor(count($rows) * 0.6));
                $cut  = count($rows) - $keep;
                $b[1]['rows'] = array_slice($rows, 0, $keep);
                $b[1]['more'] = ($b[1]['more'] ?? 0) + $cut;
                $blocks[$best] = $b;
                $trimmed[$label] = $label . ' shortened';
                continue;
            }
        }
        array_splice($blocks, $best, 1);
        $trimmed[$label] = $label . ' left out';
    }
    // Tell the reader what was shortened
    foreach ($blocks as $i => $b) {
        if ($b[0] === 'table' && !empty($b[1]['more'])) {
            $n = count($b[1]['head'] ?? []) ?: count((array) (($b[1]['rows'] ?? [[]])[0] ?? [])) ?: 1;
            $blocks[$i][1]['rows'][] = [['text' => '… and ' . $b[1]['more'] . ' more, available in the Excel version.', 'span' => $n]];
        }
    }
    return ['blocks' => array_values($blocks), 'pages' => docx_estimate_pages($blocks), 'trimmed' => array_values($trimmed)];
}

/**
 * @param array{title:string,standfirst?:string,meta?:string,banner?:?string,body?:string,blocks?:array,
 *              sources?:array<int,array{label:string,url:string,suffix?:string}>,closing?:string,landscape?:bool} $doc
 * @return string the .docx file's bytes
 */
function docx_build(array $doc): string {
    if (!docx_available()) throw new RuntimeException('The PHP zip extension is required to create Word files.');

    $rels = [
        '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>',
        '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/numbering" Target="numbering.xml"/>',
        '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer" Target="footer1.xml"/>',
    ];
    $body = '';
    if (!empty($doc['banner']))     $body .= docx_para('DraftBanner', docx_run($doc['banner']));
    if (!empty($doc['meta']))       $body .= docx_para('Eyebrow', docx_run($doc['meta']));
    $body .= docx_para('Title', docx_run($doc['title']));
    if (!empty($doc['standfirst'])) $body .= docx_para('Subtitle', docx_run($doc['standfirst']));
    if (!empty($doc['body']))       $body .= docx_body($doc['body']);
    if (!empty($doc['blocks']))     $body .= docx_blocks($doc['blocks']);

    if (!empty($doc['sources'])) {
        $body .= docx_para('Heading1', docx_run('Sources'));
        foreach (array_values($doc['sources']) as $i => $s) {
            $rid    = 'rId' . (10 + $i);
            $rels[] = '<Relationship Id="' . $rid . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink" Target="'
                    . docx_esc($s['url']) . '" TargetMode="External"/>';
            $body  .= docx_para('SourceItem',
                docx_run('[' . ($i + 1) . '] ')
                . '<w:hyperlink r:id="' . $rid . '" w:history="1">' . docx_run($s['label'], '<w:rStyle w:val="Hyperlink"/>') . '</w:hyperlink>'
                . docx_run((string) ($s['suffix'] ?? '')));
        }
    }
    if (!empty($doc['closing'])) $body .= docx_para('Closing', docx_run($doc['closing']));

    $sect = '<w:sectPr><w:footerReference w:type="default" r:id="rId3"/><w:pgSz w:w="11906" w:h="16838"/>'
          . '<w:pgMar w:top="1304" w:right="1304" w:bottom="1304" w:left="1304" w:header="567" w:footer="567" w:gutter="0"/></w:sectPr>';

    $head = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
    $files = [
        '[Content_Types].xml' => $head . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            . '<Override PartName="/word/numbering.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.numbering+xml"/>'
            . '<Override PartName="/word/footer1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '</Types>',
        '_rels/.rels' => $head . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '</Relationships>',
        'docProps/core.xml' => $head . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
            . 'xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>' . docx_esc($doc['title']) . '</dc:title><dc:creator>CRTP, Hekima University College</dc:creator>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . gmdate('Y-m-d\TH:i:s\Z') . '</dcterms:created>'
            . '</cp:coreProperties>',
        'word/_rels/document.xml.rels' => $head . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . implode('', $rels) . '</Relationships>',
        'word/document.xml'  => $head . '<w:document ' . DOCX_NS . '><w:body>' . $body . $sect . '</w:body></w:document>',
        'word/styles.xml'    => $head . docx_styles(),
        'word/numbering.xml' => $head . '<w:numbering ' . DOCX_NS . '><w:abstractNum w:abstractNumId="0"><w:multiLevelType w:val="hybridMultilevel"/>'
            . '<w:lvl w:ilvl="0"><w:start w:val="1"/><w:numFmt w:val="bullet"/><w:lvlText w:val="&#8226;"/><w:lvlJc w:val="left"/>'
            . '<w:pPr><w:ind w:left="454" w:hanging="284"/></w:pPr></w:lvl></w:abstractNum>'
            . '<w:abstractNum w:abstractNumId="1"><w:multiLevelType w:val="hybridMultilevel"/>'
            . '<w:lvl w:ilvl="0"><w:start w:val="1"/><w:numFmt w:val="decimal"/><w:lvlText w:val="%1."/><w:lvlJc w:val="left"/>'
            . '<w:pPr><w:ind w:left="454" w:hanging="284"/></w:pPr></w:lvl></w:abstractNum>'
            . '<w:num w:numId="1"><w:abstractNumId w:val="0"/></w:num><w:num w:numId="2"><w:abstractNumId w:val="1"/></w:num></w:numbering>',
        'word/footer1.xml'   => $head . '<w:ftr ' . DOCX_NS . '><w:p><w:pPr><w:pStyle w:val="Footer"/></w:pPr>'
            . docx_run('CRTP, Hekima University College · Tafakari Digital Hub · Page ')
            . '<w:fldSimple w:instr=" PAGE "><w:r><w:t>1</w:t></w:r></w:fldSimple></w:p></w:ftr>',
    ];

    $tmp = tempnam(sys_get_temp_dir(), 'tkdocx');
    if ($tmp === false) throw new RuntimeException('Could not create a temporary file for the Word document.');
    try {
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Could not create the Word document.');
        foreach ($files as $name => $content) $zip->addFromString($name, $content);
        $zip->close();
        $bytes = file_get_contents($tmp);
    } finally {
        @unlink($tmp);
    }
    if ($bytes === false || $bytes === '') throw new RuntimeException('Could not read the Word document back.');
    return $bytes;
}

/** Word styles in CRTP's palette. */
function docx_styles(): string {
    $font = '<w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:eastAsia="Calibri" w:cs="Calibri"/>';
    $p = function (string $id, string $name, string $ppr, string $rpr, string $extra = '') {
        return '<w:style w:type="paragraph" w:styleId="' . $id . '"><w:name w:val="' . $name . '"/>'
             . ($id !== 'Normal' ? '<w:basedOn w:val="Normal"/>' : '') . $extra . '<w:qFormat/>'
             . ($ppr !== '' ? "<w:pPr>{$ppr}</w:pPr>" : '') . ($rpr !== '' ? "<w:rPr>{$rpr}</w:rPr>" : '') . '</w:style>';
    };
    return '<w:styles ' . DOCX_NS . '><w:docDefaults><w:rPrDefault><w:rPr>' . $font
        . '<w:color w:val="1F2937"/><w:sz w:val="22"/><w:szCs w:val="22"/><w:lang w:val="en-GB"/></w:rPr></w:rPrDefault></w:docDefaults>'
        . str_replace('w:type="paragraph" w:styleId="Normal"', 'w:type="paragraph" w:default="1" w:styleId="Normal"',
              $p('Normal', 'Normal', '<w:spacing w:after="160" w:line="288" w:lineRule="auto"/>', ''))
        . $p('Title', 'Title', '<w:spacing w:before="60" w:after="160" w:line="240" w:lineRule="auto"/>', '<w:b/><w:bCs/><w:color w:val="750B25"/><w:sz w:val="48"/><w:szCs w:val="48"/>', '<w:next w:val="Normal"/>')
        . $p('Subtitle', 'Subtitle', '<w:pBdr><w:bottom w:val="single" w:sz="12" w:space="10" w:color="E7952A"/></w:pBdr><w:spacing w:after="280" w:line="300" w:lineRule="auto"/>', '<w:color w:val="475569"/><w:sz w:val="26"/><w:szCs w:val="26"/>', '<w:next w:val="Normal"/>')
        . $p('Eyebrow', 'Eyebrow', '<w:spacing w:after="60"/>', '<w:b/><w:bCs/><w:caps/><w:color w:val="9A5B0F"/><w:spacing w:val="20"/><w:sz w:val="17"/><w:szCs w:val="17"/>')
        . $p('DraftBanner', 'Draft Banner', '<w:shd w:val="clear" w:color="auto" w:fill="FBEFD9"/><w:spacing w:after="240"/>', '<w:b/><w:bCs/><w:color w:val="750B25"/><w:sz w:val="19"/><w:szCs w:val="19"/>')
        . $p('Heading1', 'heading 1', '<w:keepNext/><w:keepLines/><w:spacing w:before="280" w:after="100"/><w:outlineLvl w:val="0"/>', '<w:b/><w:bCs/><w:color w:val="750B25"/><w:sz w:val="30"/><w:szCs w:val="30"/>', '<w:next w:val="Normal"/>')
        . $p('Heading2', 'heading 2', '<w:keepNext/><w:keepLines/><w:spacing w:before="200" w:after="80"/><w:outlineLvl w:val="1"/>', '<w:b/><w:bCs/><w:color w:val="1F2937"/><w:sz w:val="25"/><w:szCs w:val="25"/>', '<w:next w:val="Normal"/>')
        . $p('ListBullet', 'List Bullet', '<w:numPr><w:ilvl w:val="0"/><w:numId w:val="1"/></w:numPr><w:spacing w:after="80"/>', '')
        . $p('ListNumber', 'List Number', '<w:numPr><w:ilvl w:val="0"/><w:numId w:val="2"/></w:numPr><w:spacing w:after="80"/>', '')
        . $p('Quote', 'Quote', '<w:pBdr><w:left w:val="single" w:sz="18" w:space="10" w:color="E7952A"/></w:pBdr><w:ind w:left="284"/>', '<w:i/><w:iCs/><w:color w:val="475569"/>')
        . $p('SourceItem', 'Source Item', '<w:spacing w:after="80" w:line="264" w:lineRule="auto"/><w:ind w:left="454" w:hanging="454"/>', '<w:sz w:val="19"/><w:szCs w:val="19"/>')
        . $p('Closing', 'Closing Note', '<w:pBdr><w:top w:val="single" w:sz="4" w:space="8" w:color="CBD5E1"/></w:pBdr><w:spacing w:before="280"/>', '<w:i/><w:iCs/><w:color w:val="64748B"/><w:sz w:val="19"/><w:szCs w:val="19"/>')
        . $p('Small', 'Small Note', '<w:spacing w:after="120"/>', '<w:color w:val="64748B"/><w:sz w:val="18"/><w:szCs w:val="18"/>')
        . $p('TableText', 'Table Text', '<w:spacing w:before="0" w:after="0" w:line="240" w:lineRule="auto"/>', '<w:sz w:val="19"/><w:szCs w:val="19"/>')
        . $p('TableSmall', 'Table Small', '<w:spacing w:before="0" w:after="0" w:line="240" w:lineRule="auto"/>', '<w:sz w:val="17"/><w:szCs w:val="17"/>')
        . $p('TableAfter', 'Table After', '<w:spacing w:before="0" w:after="120" w:line="200" w:lineRule="auto"/>', '<w:sz w:val="8"/><w:szCs w:val="8"/>')
        . $p('Footer', 'footer', '<w:jc w:val="center"/>', '<w:color w:val="64748B"/><w:sz w:val="16"/><w:szCs w:val="16"/>')
        . '<w:style w:type="character" w:styleId="Hyperlink"><w:name w:val="Hyperlink"/><w:rPr><w:color w:val="750B25"/><w:u w:val="single"/></w:rPr></w:style>'
        . '</w:styles>';
}
