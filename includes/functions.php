<?php
declare(strict_types=1);

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function generate_id(): string {
    return 'c' . bin2hex(random_bytes(12));
}

function format_date(string|null $date, string $fmt = 'M j, Y'): string {
    if (!$date) return '';
    return date($fmt, strtotime($date));
}

function format_relative_time(string|null $date): string {
    if (!$date) return '';
    $diff = time() - strtotime($date);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', strtotime($date));
}

function format_number(int $n): string {
    if ($n >= 1_000_000) return round($n / 1_000_000, 1) . 'M';
    if ($n >= 1_000)     return round($n / 1_000, 1) . 'k';
    return (string) $n;
}

function markdown_to_html(string $raw): string {
    $t = htmlspecialchars($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Fenced code blocks
    $t = preg_replace_callback('/```(\w*)\n([\s\S]*?)```/m', fn($m) =>
        '<pre class="bg-slate-900 text-slate-100 p-4 rounded-xl overflow-x-auto my-4 text-sm font-mono"><code>' . $m[2] . '</code></pre>', $t);

    // Headings
    $t = preg_replace('/^###### (.+)$/m', '<h6 class="font-bold text-sm mt-4 mb-1 text-slate-800">$1</h6>', $t);
    $t = preg_replace('/^##### (.+)$/m',  '<h5 class="font-bold text-base mt-4 mb-1 text-slate-800">$1</h5>', $t);
    $t = preg_replace('/^#### (.+)$/m',   '<h4 class="font-outfit font-bold text-lg mt-5 mb-2 text-slate-800">$1</h4>', $t);
    $t = preg_replace('/^### (.+)$/m',    '<h3 class="font-outfit font-bold text-xl mt-6 mb-2 text-slate-900">$1</h3>', $t);
    $t = preg_replace('/^## (.+)$/m',     '<h2 class="font-outfit font-bold text-2xl mt-8 mb-3 text-slate-900">$1</h2>', $t);
    $t = preg_replace('/^# (.+)$/m',      '<h1 class="font-outfit font-bold text-3xl mt-8 mb-4 text-slate-900">$1</h1>', $t);

    // Blockquote
    $t = preg_replace('/^&gt; (.+)$/m',
        '<blockquote class="border-l-4 border-secondary pl-4 italic text-slate-600 my-4">$1</blockquote>', $t);

    // HR
    $t = preg_replace('/^---$/m', '<hr class="my-8 border-slate-200">', $t);

    // Inline: bold, italic, strikethrough, code
    $t = preg_replace('/\*\*(.+?)\*\*/', '<strong class="font-bold text-slate-900">$1</strong>', $t);
    $t = preg_replace('/\*(.+?)\*/',     '<em class="italic">$1</em>', $t);
    $t = preg_replace('/~~(.+?)~~/',     '<del class="line-through text-slate-500">$1</del>', $t);
    $t = preg_replace('/`([^`]+)`/',     '<code class="bg-slate-100 text-rose-600 px-1.5 py-0.5 rounded text-sm font-mono">$1</code>', $t);

    // Links
    $t = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/',
        '<a href="$2" class="text-primary underline underline-offset-2 hover:text-secondary" target="_blank" rel="noopener noreferrer">$1</a>', $t);

    // Unordered lists
    $t = preg_replace_callback('/((?:^[-*] .+\n?)+)/m', function ($m) {
        $items = preg_replace('/^[-*] (.+)$/m', '<li class="ml-1">$1</li>', trim($m[0]));
        return '<ul class="list-disc pl-6 my-3 space-y-1 text-slate-700">' . $items . '</ul>';
    }, $t);

    // Ordered lists
    $t = preg_replace_callback('/((?:^\d+\. .+\n?)+)/m', function ($m) {
        $items = preg_replace('/^\d+\. (.+)$/m', '<li class="ml-1">$1</li>', trim($m[0]));
        return '<ol class="list-decimal pl-6 my-3 space-y-1 text-slate-700">' . $items . '</ol>';
    }, $t);

    // Paragraphs (wrap lines not already wrapped in block tags)
    $lines = explode("\n", $t);
    $out   = '';
    foreach ($lines as $line) {
        $l = trim($line);
        if ($l === '') { $out .= "\n"; continue; }
        if (preg_match('/^<(h[1-6]|ul|ol|li|pre|blockquote|hr|div)/', $l)) {
            $out .= $l . "\n";
        } else {
            $out .= '<p class="text-slate-700 leading-relaxed my-3">' . $l . '</p>' . "\n";
        }
    }
    return $out;
}

// ── Media helpers ────────────────────────────────────────────────

function is_audio_url(string $url): bool {
    $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
    return in_array($ext, ['mp3', 'ogg', 'wav', 'm4a', 'aac', 'opus', 'flac']);
}

function is_video_url(string $url): bool {
    $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
    return in_array($ext, ['mp4', 'webm', 'ogg', 'mov']);
}

function youtube_id(string $url): string {
    if (preg_match('/(?:youtube\.com\/(?:watch\?.*v=|shorts\/|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m)) {
        return $m[1];
    }
    return '';
}

function vimeo_id(string $url): string {
    if (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) {
        return $m[1];
    }
    return '';
}

function video_embed_html(string $url, string $title, string $cssClass = 'w-full h-full border-0'): string {
    $ytId = youtube_id($url);
    if ($ytId) {
        $src = 'https://www.youtube.com/embed/' . $ytId . '?rel=0&modestbranding=1';
        return '<iframe src="' . h($src) . '" title="' . h($title) . '" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen class="' . $cssClass . '"></iframe>';
    }
    $vmId = vimeo_id($url);
    if ($vmId) {
        $src = 'https://player.vimeo.com/video/' . $vmId . '?title=0&byline=0&portrait=0&color=D99F51';
        return '<iframe src="' . h($src) . '" title="' . h($title) . '" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen class="' . $cssClass . '"></iframe>';
    }
    if (is_video_url($url)) {
        return '<video controls class="' . $cssClass . '"><source src="' . h($url) . '"><p class="p-4 text-slate-400">Your browser does not support HTML5 video.</p></video>';
    }
    return '';
}

function doc_file_type(string $url): string {
    $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
    return match($ext) { 'pdf'=>'PDF', 'doc','docx'=>'Word', 'xls','xlsx'=>'Excel', 'ppt','pptx'=>'PowerPoint', default=>strtoupper($ext) ?: 'File' };
}

// ── JSON API helpers ─────────────────────────────────────────────

function json_response(mixed $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $message, int $status = 400): never {
    json_response(['error' => $message], $status);
}

function request_body(): array {
    static $body = null;
    if ($body === null) {
        $raw  = file_get_contents('php://input');
        $body = json_decode($raw, true) ?? [];
    }
    return $body;
}

function request_method(): string {
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

// ── Dropdown data ────────────────────────────────────────────────

function african_countries(): array {
    return [
        'Algeria','Angola','Benin','Botswana','Burkina Faso','Burundi','Cabo Verde',
        'Cameroon','Central African Republic','Chad','Comoros','Congo','DR Congo',
        'Djibouti','Egypt','Equatorial Guinea','Eritrea','Eswatini','Ethiopia','Gabon',
        'Gambia','Ghana','Guinea','Guinea-Bissau','Ivory Coast','Kenya','Lesotho',
        'Liberia','Libya','Madagascar','Malawi','Mali','Mauritania','Mauritius',
        'Morocco','Mozambique','Namibia','Niger','Nigeria','Rwanda',
        'São Tomé and Príncipe','Senegal','Sierra Leone','Somalia','South Africa',
        'South Sudan','Sudan','Tanzania','Togo','Tunisia','Uganda','Zambia','Zimbabwe',
    ];
}

function issue_categories(): array {
    return [
        'Health','Education','Security & Conflict','Climate & Environment',
        'Economic Development','Policy & Governance','Human Rights',
        'Migration & Refugees','Agriculture & Food Security',
        'Infrastructure & Energy','Technology & Innovation',
        'Gender & Social Affairs',
    ];
}

// ── Status / type badge HTML ─────────────────────────────────────

function status_badge(string $status): string {
    $map = [
        'DRAFT'     => 'bg-slate-100 text-slate-600',
        'PENDING'   => 'bg-amber-100 text-amber-700',
        'PUBLISHED' => 'bg-emerald-100 text-emerald-700',
        'REJECTED'  => 'bg-rose-100 text-rose-700',
        'ARCHIVED'  => 'bg-gray-100 text-gray-500',
    ];
    $cls = $map[$status] ?? 'bg-slate-100 text-slate-600';
    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest ' . $cls . '">' . h($status) . '</span>';
}

function type_badge(string $type): string {
    $icons = [
        'ARTICLE'       => '📄',
        'GALLERY_IMAGE' => '🖼️',
        'PODCAST'       => '🎧',
        'VIDEO'         => '🎬',
        'DOCUMENT'      => '📁',
    ];
    $icon = $icons[$type] ?? '📄';
    $label = str_replace('_', ' ', $type);
    return '<span class="inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-widest text-slate-400">' . $icon . ' ' . h($label) . '</span>';
}
