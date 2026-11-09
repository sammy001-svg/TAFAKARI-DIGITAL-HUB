<?php
declare(strict_types=1);
/**
 * Minimal Word (.docx) writer for Tafakari articles.
 *
 * Builds the file directly with ZipArchive, so it needs only PHP's zip
 * extension. Headings use Word's built-in Heading styles, so the navigation
 * pane and tables of contents work when an editor opens the file.
 *
 * Body text is the site's Markdown subset: "## " and "### " headings, "- "
 * bullets, "> " quotes, **bold** and *italic*.
 */

const DOCX_NS = 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
              . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"';

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

/** Runs for one line of text, honouring **bold** and *italic*. */
function docx_inline(string $text): string {
    $parts = preg_split('/(\*\*[^*\n]+\*\*|\*[^*\n]+\*)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
    $xml = '';
    foreach ($parts as $p) {
        if (preg_match('/^\*\*([^*\n]+)\*\*$/u', $p, $m))  $xml .= docx_run($m[1], '<w:b/><w:bCs/>');
        elseif (preg_match('/^\*([^*\n]+)\*$/u', $p, $m))  $xml .= docx_run($m[1], '<w:i/><w:iCs/>');
        else                                               $xml .= docx_run($p);
    }
    return $xml;
}

function docx_para(string $style, string $runs): string {
    return '<w:p><w:pPr><w:pStyle w:val="' . $style . '"/></w:pPr>' . $runs . '</w:p>';
}

/** Word paragraphs for a Markdown body. */
function docx_body(string $md): string {
    $xml  = '';
    $para = [];
    $flush = function () use (&$para, &$xml): void {
        if ($para) $xml .= docx_para('Normal', docx_inline(implode(' ', $para)));
        $para = [];
    };
    foreach (preg_split('/\R/u', $md) ?: [] as $line) {
        $t = trim($line);
        if ($t === '') { $flush(); continue; }
        if (preg_match('/^(#{1,6})\s+(.+)$/u', $t, $m)) {
            $flush();
            $xml .= docx_para(strlen($m[1]) <= 2 ? 'Heading1' : 'Heading2', docx_inline($m[2]));
        } elseif (preg_match('/^[-*]\s+(.+)$/u', $t, $m)) {
            $flush();
            $xml .= docx_para('ListBullet', docx_inline($m[1]));
        } elseif (preg_match('/^>\s*(.+)$/u', $t, $m)) {
            $flush();
            $xml .= docx_para('Quote', docx_inline($m[1]));
        } else {
            $para[] = $t;
        }
    }
    $flush();
    return $xml;
}

/**
 * @param array{title:string,standfirst?:string,meta?:string,banner?:?string,body:string,
 *              sources?:array<int,array{label:string,url:string,suffix?:string}>,closing?:string} $doc
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
    $body .= docx_body($doc['body']);

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
            . '<w:num w:numId="1"><w:abstractNumId w:val="0"/></w:num></w:numbering>',
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
        . $p('Quote', 'Quote', '<w:pBdr><w:left w:val="single" w:sz="18" w:space="10" w:color="E7952A"/></w:pBdr><w:ind w:left="284"/>', '<w:i/><w:iCs/><w:color w:val="475569"/>')
        . $p('SourceItem', 'Source Item', '<w:spacing w:after="80" w:line="264" w:lineRule="auto"/><w:ind w:left="454" w:hanging="454"/>', '<w:sz w:val="19"/><w:szCs w:val="19"/>')
        . $p('Closing', 'Closing Note', '<w:pBdr><w:top w:val="single" w:sz="4" w:space="8" w:color="CBD5E1"/></w:pBdr><w:spacing w:before="280"/>', '<w:i/><w:iCs/><w:color w:val="64748B"/><w:sz w:val="19"/><w:szCs w:val="19"/>')
        . $p('Footer', 'footer', '<w:jc w:val="center"/>', '<w:color w:val="64748B"/><w:sz w:val="16"/><w:szCs w:val="16"/>')
        . '<w:style w:type="character" w:styleId="Hyperlink"><w:name w:val="Hyperlink"/><w:rPr><w:color w:val="750B25"/><w:u w:val="single"/></w:rPr></w:style>'
        . '</w:styles>';
}
