<?php
declare(strict_types=1);
/**
 * Minimal Excel (.xlsx) writer for Tafakari exports.
 *
 * Builds the workbook directly with ZipArchive, so it needs only PHP's zip
 * extension. Strings are written inline, so there is no shared-string table
 * to maintain. A small fixed set of styles covers headings, numbers and the
 * heat map's severity tiers.
 *
 * A sheet is ['name' => string, 'cols' => [['label' => string, 'width' => int], ...],
 *             'rows' => [[cell, ...], ...]]
 * A cell is a scalar (string, int, float, null), or ['v' => scalar, 's' => style],
 * or ['v' => score, 'tier' => true] to shade a 1-10 score in its severity tier.
 * Styles: head, bold, num, wrap, tier0..tier4.
 */

function xlsx_available(): bool {
    return class_exists('ZipArchive');
}

/** XML-safe text: drops characters XML 1.0 forbids. */
function xlsx_esc(string $s): string {
    $s = mb_scrub($s, 'UTF-8');
    $s = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $s) ?? '';
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

/** Column letters for a 0-based index: 0 => A, 25 => Z, 26 => AA. */
function xlsx_col(int $i): string {
    $s = '';
    $i++;
    while ($i > 0) {
        $m = ($i - 1) % 26;
        $s = chr(65 + $m) . $s;
        $i = intdiv($i - 1, 26);
    }
    return $s;
}

/** Sheet names: at most 31 characters, none of the characters Excel forbids. */
function xlsx_sheet_name(string $name, int $n): string {
    $name = trim((string) preg_replace('/[\[\]:*?\/\\\\]+/', ' ', $name));
    $name = mb_substr($name, 0, 31);
    return $name !== '' ? $name : 'Sheet' . $n;
}

/** The style index for a cell description. */
function xlsx_style(array $cell): int {
    static $map = ['head' => 1, 'bold' => 2, 'num' => 3, 'tier0' => 4, 'tier1' => 5, 'tier2' => 6, 'tier3' => 7, 'tier4' => 8, 'wrap' => 9, 'pct' => 10];
    if (!empty($cell['tier'])) {
        $v = $cell['v'];
        if (!is_numeric($v)) return 0;
        $v = (float) $v;
        return $v >= 9 ? 4 : ($v >= 7 ? 5 : ($v >= 5 ? 6 : ($v >= 2 ? 7 : 8)));
    }
    return $map[$cell['s'] ?? ''] ?? 0;
}

function xlsx_cell_xml(string $ref, mixed $cell): string {
    $desc = is_array($cell) ? $cell : ['v' => $cell];
    $v = $desc['v'] ?? null;
    if ($v === null || $v === '') return '';
    $s = xlsx_style($desc);
    $sAttr = $s ? ' s="' . $s . '"' : '';
    if (is_int($v) || is_float($v) || (is_string($v) && preg_match('/^-?\d+(\.\d+)?$/', $v) && !empty($desc['numeric']))) {
        return '<c r="' . $ref . '"' . $sAttr . '><v>' . (is_string($v) ? $v : (is_float($v) ? rtrim(rtrim(number_format($v, 6, '.', ''), '0'), '.') : (string) $v)) . '</v></c>';
    }
    if (is_bool($v)) $v = $v ? 'Yes' : 'No';
    return '<c r="' . $ref . '" t="inlineStr"' . $sAttr . '><is><t xml:space="preserve">' . xlsx_esc((string) $v) . '</t></is></c>';
}

function xlsx_sheet_xml(array $sheet): string {
    $cols = $sheet['cols'] ?? [];
    $rows = $sheet['rows'] ?? [];
    $nCols = max(count($cols), ...array_map('count', $rows ?: [[]]));
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
         . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
         . '<sheetViews><sheetView workbookViewId="0"' . (empty($sheet['first']) ? '' : ' tabSelected="1"') . '>'
         . ($cols ? '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>' : '')
         . '</sheetView></sheetViews><sheetFormatPr defaultRowHeight="15"/>';
    if ($cols) {
        $xml .= '<cols>';
        foreach (array_values($cols) as $i => $c) {
            $w = max(6, min(80, (int) ($c['width'] ?? 16)));
            $xml .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
        }
        $xml .= '</cols>';
    }
    $xml .= '<sheetData>';
    $r = 0;
    if ($cols) {
        $r++;
        $xml .= '<row r="1">';
        foreach (array_values($cols) as $i => $c) $xml .= xlsx_cell_xml(xlsx_col($i) . '1', ['v' => (string) ($c['label'] ?? ''), 's' => 'head']);
        $xml .= '</row>';
    }
    foreach ($rows as $row) {
        $r++;
        $xml .= '<row r="' . $r . '">';
        foreach (array_values($row) as $i => $cell) $xml .= xlsx_cell_xml(xlsx_col($i) . $r, $cell);
        $xml .= '</row>';
    }
    $xml .= '</sheetData>';
    if (!empty($sheet['merge'])) {
        $xml .= '<mergeCells count="' . count($sheet['merge']) . '">';
        foreach ($sheet['merge'] as $m) $xml .= '<mergeCell ref="' . xlsx_esc($m) . '"/>';
        $xml .= '</mergeCells>';
    }
    $xml .= '<pageMargins left="0.5" right="0.5" top="0.6" bottom="0.6" header="0.3" footer="0.3"/>'
          . '<pageSetup orientation="' . ($nCols > 7 ? 'landscape' : 'portrait') . '" paperSize="9" fitToWidth="1" fitToHeight="0"/>'
          . '</worksheet>';
    return $xml;
}

/** Styles in CRTP's palette: header row, bold, one-decimal numbers, the five severity tiers, wrapped text. */
function xlsx_styles_xml(): string {
    $tierFills = ['ED1C24', 'E7952A', 'F59E0B', '65A30D', '059669'];
    $fills = '<fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
           . '<fill><patternFill patternType="solid"><fgColor rgb="FFF1F5F9"/><bgColor indexed="64"/></patternFill></fill>';
    foreach ($tierFills as $f) $fills .= '<fill><patternFill patternType="solid"><fgColor rgb="FF' . $f . '"/><bgColor indexed="64"/></patternFill></fill>';
    $xfs = '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'                                                                     // 0 default
         . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment vertical="center" wrapText="1"/></xf>'   // 1 head
         . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'                                                         // 2 bold
         . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>';                                             // 3 num
    for ($i = 0; $i < 5; $i++) {
        $xfs .= '<xf numFmtId="164" fontId="2" fillId="' . (3 + $i) . '" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center"/></xf>'; // 4-8 tiers
    }
    $xfs .= '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'       // 9 wrap
          . '<xf numFmtId="9" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>';                                             // 10 pct
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<numFmts count="1"><numFmt numFmtId="164" formatCode="0.0"/></numFmts>'
        . '<fonts count="3"><font><sz val="11"/><color rgb="FF1F2937"/><name val="Calibri"/><family val="2"/></font>'
        . '<font><b/><sz val="11"/><color rgb="FF0F172A"/><name val="Calibri"/><family val="2"/></font>'
        . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font></fonts>'
        . '<fills count="' . (3 + count($tierFills)) . '">' . $fills . '</fills>'
        . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>'
        . '<border><left style="thin"><color rgb="FFCBD5E1"/></left><right style="thin"><color rgb="FFCBD5E1"/></right><top style="thin"><color rgb="FFCBD5E1"/></top><bottom style="thin"><color rgb="FFCBD5E1"/></bottom><diagonal/></border></borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="11">' . $xfs . '</cellXfs>'
        . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
        . '</styleSheet>';
}

/**
 * @param list<array> $sheets at least one sheet
 * @param array{title?:string} $meta
 * @return string the .xlsx file's bytes
 */
function xlsx_build(array $sheets, array $meta = []): string {
    if (!xlsx_available()) throw new RuntimeException('The PHP zip extension is required to create Excel files.');
    $sheets = array_values($sheets);
    if (!$sheets) throw new InvalidArgumentException('A workbook needs at least one sheet.');

    $head = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
    $types = ''; $wbSheets = ''; $wbRels = ''; $files = [];
    $names = [];
    foreach ($sheets as $i => $sheet) {
        $n = $i + 1;
        $name = xlsx_sheet_name((string) ($sheet['name'] ?? ''), $n);
        while (in_array(strtolower($name), $names, true)) $name = mb_substr($name, 0, 28) . ' ' . $n;
        $names[] = strtolower($name);
        $sheet['first'] = $i === 0;
        $types    .= '<Override PartName="/xl/worksheets/sheet' . $n . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        $wbSheets .= '<sheet name="' . xlsx_esc($name) . '" sheetId="' . $n . '" r:id="rId' . ($n + 2) . '"/>';
        $wbRels   .= '<Relationship Id="rId' . ($n + 2) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $n . '.xml"/>';
        $files['xl/worksheets/sheet' . $n . '.xml'] = xlsx_sheet_xml($sheet);
    }
    $title = (string) ($meta['title'] ?? 'Tafakari export');

    $files = [
        '[Content_Types].xml' => $head . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . $types . '</Types>',
        '_rels/.rels' => $head . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>',
        'docProps/core.xml' => $head . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
            . 'xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>' . xlsx_esc($title) . '</dc:title><dc:creator>CRTP, Hekima University College</dc:creator>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . gmdate('Y-m-d\TH:i:s\Z') . '</dcterms:created></cp:coreProperties>',
        'docProps/app.xml' => $head . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>Tafakari Digital Hub</Application></Properties>',
        'xl/workbook.xml' => $head . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<bookViews><workbookView xWindow="0" yWindow="0" windowWidth="20000" windowHeight="12000"/></bookViews>'
            . '<sheets>' . $wbSheets . '</sheets></workbook>',
        'xl/_rels/workbook.xml.rels' => $head . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . $wbRels . '</Relationships>',
        'xl/styles.xml' => xlsx_styles_xml(),
    ] + $files;

    $tmp = tempnam(sys_get_temp_dir(), 'tkxlsx');
    if ($tmp === false) throw new RuntimeException('Could not create a temporary file for the Excel workbook.');
    try {
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Could not create the Excel workbook.');
        foreach ($files as $name => $content) $zip->addFromString($name, $content);
        $zip->close();
        $bytes = file_get_contents($tmp);
    } finally {
        @unlink($tmp);
    }
    if ($bytes === false || $bytes === '') throw new RuntimeException('Could not read the Excel workbook back.');
    return $bytes;
}

/** Send a built file to the browser and stop. */
function export_send(string $bytes, string $filename, string $format): never {
    $type = $format === 'xlsx'
        ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($filename)), '-');
    header('Content-Type: ' . $type);
    header('Content-Disposition: attachment; filename="' . (substr($slug, 0, 90) ?: 'tafakari') . '.' . $format . '"');
    header('Content-Length: ' . strlen($bytes));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    echo $bytes;
    exit;
}
