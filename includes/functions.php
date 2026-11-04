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

// ── Site settings helpers ─────────────────────────────────────────
function get_setting(string $key, string $default = ''): string {
    static $cache = [];
    if (array_key_exists($key, $cache)) return $cache[$key];
    try {
        $stmt = db()->prepare('SELECT `value` FROM SiteSetting WHERE `key` = ? LIMIT 1');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $cache[$key] = ($row !== false ? (string)($row['value'] ?? '') : $default);
    } catch (Exception $e) { return $cache[$key] = $default; }
}

function set_setting(string $key, string $value, string $updatedBy = ''): void {
    try {
        db()->prepare(
            'INSERT INTO SiteSetting (`key`,`value`,`updatedAt`,`updatedBy`) VALUES (?,?,NOW(3),?)
             ON DUPLICATE KEY UPDATE `value`=VALUES(`value`),`updatedAt`=NOW(3),`updatedBy`=VALUES(`updatedBy`)'
        )->execute([$key, $value, $updatedBy ?: null]);
    } catch (Exception $e) { /* table may not exist yet */ }
}

function ensure_settings_table(): void {
    try {
        db()->exec("CREATE TABLE IF NOT EXISTS `SiteSetting` (
          `key` VARCHAR(100) NOT NULL, `value` TEXT NULL,
          `updatedAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
          `updatedBy` VARCHAR(191) NULL, PRIMARY KEY (`key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Exception $e) {}
}

function ensure_partners_table(): void {
    try {
        db()->exec("CREATE TABLE IF NOT EXISTS `Partner` (
          `id` VARCHAR(191) NOT NULL,
          `name` VARCHAR(200) NOT NULL,
          `subtitle` VARCHAR(200) NULL,
          `description` TEXT NULL,
          `logoUrl` VARCHAR(500) NULL,
          `websiteUrl` VARCHAR(500) NULL,
          `sortOrder` INT NOT NULL DEFAULT 0,
          `isActive` TINYINT(1) NOT NULL DEFAULT 1,
          `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
          `updatedAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Exception $e) {}
}

function ensure_team_table(): void {
    try {
        db()->exec("CREATE TABLE IF NOT EXISTS `TeamMember` (
          `id` VARCHAR(191) NOT NULL,
          `name` VARCHAR(200) NOT NULL,
          `role` VARCHAR(200) NULL,
          `bio` TEXT NULL,
          `photoUrl` VARCHAR(500) NULL,
          `sortOrder` INT NOT NULL DEFAULT 0,
          `isActive` TINYINT(1) NOT NULL DEFAULT 1,
          `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
          `updatedAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Exception $e) {}
}

// Adds the `rating` column to an existing Comment table (installs that predate the
// star-rating feature). Checks first so the ALTER only ever runs once per install.
function ensure_comment_rating_column(): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;
    try {
        $pdo = db();
        $col = $pdo->query("SHOW COLUMNS FROM `Comment` LIKE 'rating'")->fetch();
        if (!$col) {
            $pdo->exec("ALTER TABLE `Comment` ADD COLUMN `rating` TINYINT NULL AFTER `content`");
        }
    } catch (Exception $e) {}
}

// Widens the Post.type ENUM to include POLICY_BRIEF on existing databases.
// Must run before any INSERT/UPDATE sets type='POLICY_BRIEF' — MySQL rejects
// (or silently blanks, outside strict mode) enum values not yet in the column
// definition, so this is called from every write path that can set that type.
function ensure_policy_brief_post_type(): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;
    try {
        $pdo = db();
        $col = $pdo->query("SHOW COLUMNS FROM `Post` LIKE 'type'")->fetch();
        if ($col && stripos($col['Type'], 'POLICY_BRIEF') === false) {
            $pdo->exec("ALTER TABLE `Post` MODIFY `type` ENUM('ARTICLE','GALLERY_IMAGE','PODCAST','VIDEO','DOCUMENT','POLICY_BRIEF') NOT NULL DEFAULT 'ARTICLE'");
        }
    } catch (Exception $e) {}
}

/**
 * Content types that feed the conflict heat map.
 *
 * Only documents carry the monitoring reports the heat map is built from.
 * Gallery images, podcasts, videos and news articles are published content,
 * not assessed reporting, so they are not plotted or scored.
 */
function post_type_on_heatmap(string $type): bool {
    return $type === 'DOCUMENT';
}

/**
 * Content types that must be tagged with an issue category.
 *
 * Gallery images are exempt: they are illustrative, carry no heat-map meaning,
 * and forcing a category on every upload only slows the batch uploader down.
 */
function post_type_needs_category(string $type): bool {
    return $type !== 'GALLERY_IMAGE';
}

/**
 * Content types that carry a stated author / issuing body.
 * Reporting formats are attributable; media formats are not.
 */
function post_type_has_byline(string $type): bool {
    return in_array($type, ['ARTICLE', 'DOCUMENT', 'POLICY_BRIEF'], true);
}

/** Label for the byline field / display, per content type. */
function byline_label(string $type): string {
    return $type === 'POLICY_BRIEF' ? 'Author / Issuing Body' : 'Author / Source';
}

/**
 * Ensure the optional Post columns this app relies on exist.
 *
 *  byline           - the stated author or issuing body of the source material.
 *                     Distinct from authorId, which is the platform user who
 *                     entered the record.
 *  intensityScores  - per-element 1-10 scores (see migrations/004).
 *
 * ATTEMPTED AT MOST ONCE, EVER - not once per request.
 * ------------------------------------------------------
 * ALTER TABLE takes a metadata lock on Post and rebuilds it. Every admin page
 * queries Post (the sidebar counts pending posts), so a slow or repeatedly
 * retried ALTER blocks the whole admin area until requests time out.
 *
 * An earlier version guarded only with a `static` flag, which resets each
 * request, and swallowed failures - so if the ALTER could not succeed (no ALTER
 * privilege is common on shared hosting) every single request tried to rebuild
 * the table again. The outcome is now recorded in SiteSetting so the DDL runs
 * once and never again.
 *
 * The guard key is versioned: v2 added the multi-country and score-provenance
 * columns (migrations/005), so installs that recorded v1 get exactly one more
 * attempt for the new columns.
 *
 * To retry after fixing the underlying cause, delete the
 * `schema_post_columns_v2` row from SiteSetting, or just apply
 * migrations/003, 004 and 005 by hand.
 */
function ensure_post_columns(): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    // Already attempted in a previous request (successfully or not) - do not
    // touch DDL again.
    if (get_setting('schema_post_columns_v2', '') !== '') return;

    try {
        $pdo  = db();
        $rows = $pdo->query('SHOW COLUMNS FROM `Post`')->fetchAll();
        $have = [];
        foreach ($rows as $r) $have[$r['Field']] = true;

        $missing = [];
        foreach (post_optional_columns() as $col => $ddl) {
            if (!isset($have[$col])) $missing[] = 'ADD COLUMN ' . $ddl;
        }

        if (!$missing) {
            set_setting('schema_post_columns_v2', 'ok');   // nothing to do
            return;
        }

        // Record the attempt BEFORE running it: if this request dies mid-ALTER,
        // the next one must not start another table rebuild on top of it.
        set_setting('schema_post_columns_v2', 'attempted');

        // One statement = one table rebuild, rather than one per column
        $pdo->exec('ALTER TABLE `Post` ' . implode(', ', $missing));

        set_setting('schema_post_columns_v2', 'ok');
    } catch (Throwable $e) {
        error_log('[ensure_post_columns] schema update failed: ' . $e->getMessage()
                . ' - apply migrations/003, 004 and 005 manually.');
        set_setting('schema_post_columns_v2', 'failed');
    }
}

/**
 * Post columns added after the original schema, with their DDL.
 * Single source of truth for ensure_post_columns() and migrations/005.
 */
function post_optional_columns(): array {
    return [
        'byline'              => '`byline` VARCHAR(255) NULL',
        'intensityScores'     => '`intensityScores` TEXT NULL',
        'additionalCountries' => '`additionalCountries` TEXT NULL',
        'sourceCount'         => '`sourceCount` SMALLINT NULL',
        'scoreSource'         => '`scoreSource` VARCHAR(16) NULL',
        'scoredBy'            => '`scoredBy` VARCHAR(191) NULL',
        'scoredAt'            => '`scoredAt` DATETIME(3) NULL',
    ];
}

/**
 * True when the Post table has $col. Cached per request, so pages can read
 * newer columns without breaking on a database that has not been migrated.
 */
function post_has_column(string $col): bool {
    static $cols = null;
    if ($cols === null) {
        $cols = [];
        try {
            foreach (db()->query('SHOW COLUMNS FROM `Post`')->fetchAll() as $r) {
                $cols[$r['Field']] = true;
            }
        } catch (Throwable $e) {}
    }
    return isset($cols[$col]);
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

/**
 * Categories offered when tagging content.
 *
 * These are exactly the categories configured in Admin > Heatmap Config, so the
 * content form and the heat map always agree. The built-in list below is only a
 * fallback for a fresh install with nothing configured yet.
 *
 * (Previously this returned the built-in list PLUS the configured ones, which
 * meant five configured categories showed up as seventeen on the form.)
 */
function issue_categories(): array {
    static $cached = null;
    if ($cached !== null) return $cached;

    $configured = [];
    try {
        $raw = get_setting('heatmap_categories', '');
        if ($raw !== '') {
            $dbCats = json_decode($raw, true);
            if (is_array($dbCats)) {
                foreach ($dbCats as $cat) {
                    $name = is_array($cat) ? trim((string)($cat['name'] ?? '')) : trim((string)$cat);
                    if ($name !== '' && !in_array($name, $configured, true)) {
                        $configured[] = $name;
                    }
                }
            }
        }
    } catch (Throwable $e) {}

    if ($configured) {
        $cached = $configured;
        return $cached;
    }

    // Fallback: nothing configured yet
    $cached = [
        'Health','Education','Security & Conflict','Climate & Environment',
        'Economic Development','Policy & Governance','Human Rights',
        'Migration & Refugees','Agriculture & Food Security',
        'Infrastructure & Energy','Technology & Innovation',
        'Gender & Social Affairs',
    ];
    return $cached;
}

/**
 * The configured categories, plus any values a post already carries that are no
 * longer configured.
 *
 * Without this, editing an older post tagged with a since-retired category would
 * drop that value on save (it would have no checkbox to re-submit), and could
 * even block the save entirely by emptying a required field.
 *
 * @param  string[] $existing categories currently stored on the post
 * @return string[]
 */
function issue_categories_with(array $existing): array {
    $list = issue_categories();
    foreach ($existing as $e) {
        $e = trim((string)$e);
        if ($e !== '' && !in_array($e, $list, true)) $list[] = $e;
    }
    return $list;
}

/** True when a category is no longer in the configured set (kept for legacy data). */
function is_retired_category(string $name): bool {
    return !in_array($name, issue_categories(), true);
}

// ── Heat-map taxonomy: categories -> component elements ──────────

/**
 * Default component elements for each of the five heat-map categories.
 * Used when a category has no explicit element list configured in
 * Admin > Heatmap Config. Keyed by category name.
 */
function heatmap_default_elements(): array {
    return [
        'Conflict & Security Watch' => [
            'Armed conflict & security incidents',
            'Armed group statements & activity',
            'Civilian harm & protection',
        ],
        'Peace & Political Track' => [
            'Key peace & conflict developments',
            'Peace initiatives & mediation',
            'Government positions & actions',
        ],
        'Humanitarian & Emergency Situation' => [
            'Humanitarian situation & access',
            'Displacement & returns',
            'Natural disasters & health emergencies',
        ],
        'Actors & Institutional Engagement' => [
            'International & regional actors',
            'Religious institutions & civil society',
            'Local governance & institutions',
        ],
        'Early Warning Signals' => [
            'Escalation indicators',
            'Social tension & hate speech',
            'Resource & economic stress',
        ],
    ];
}

/**
 * The configured heat-map taxonomy.
 *
 * Returns a list of ['name' => string, 'color' => string, 'elements' => string[]].
 * Element lists come from the stored config when present, otherwise from
 * heatmap_default_elements(), otherwise the category stands alone as its own
 * single element so it always remains scoreable.
 */
function heatmap_taxonomy(): array {
    static $cached = null;
    if ($cached !== null) return $cached;

    $defaults = heatmap_default_elements();
    $out      = [];

    try {
        $raw = get_setting('heatmap_categories', '');
        $rows = $raw !== '' ? json_decode($raw, true) : null;
        if (is_array($rows)) {
            foreach ($rows as $r) {
                if (!is_array($r)) continue;
                $name = trim((string)($r['name'] ?? ''));
                if ($name === '') continue;

                $els = [];
                if (isset($r['elements']) && is_array($r['elements'])) {
                    foreach ($r['elements'] as $e) {
                        $e = trim((string)$e);
                        if ($e !== '' && !in_array($e, $els, true)) $els[] = $e;
                    }
                }
                if (!$els) $els = $defaults[$name] ?? [$name];

                $out[] = [
                    'name'     => $name,
                    'color'    => trim((string)($r['color'] ?? '')) ?: '#94a3b8',
                    'elements' => $els,
                ];
            }
        }
    } catch (Throwable $e) {}

    if (!$out) {
        foreach ($defaults as $name => $els) {
            $out[] = ['name' => $name, 'color' => '#94a3b8', 'elements' => $els];
        }
    }

    $cached = $out;
    return $cached;
}

/** Flat map of element name => owning category name. */
function heatmap_element_index(): array {
    static $cached = null;
    if ($cached !== null) return $cached;
    $idx = [];
    foreach (heatmap_taxonomy() as $c) {
        foreach ($c['elements'] as $e) $idx[$e] = $c['name'];
    }
    $cached = $idx;
    return $cached;
}

/**
 * Average the 1-10 element scores of one post into per-category averages.
 *
 * @param  array $scores  element name => score
 * @return array          category name => ['avg'=>float,'n'=>int,'elements'=>[el=>score]]
 */
function heatmap_category_scores(array $scores): array {
    $idx = heatmap_element_index();
    $acc = [];
    foreach ($scores as $el => $val) {
        $v = (int)$val;
        if ($v < 1 || $v > 10) continue;          // 0 / blank means "not assessed"
        $cat = $idx[$el] ?? null;
        if ($cat === null) continue;              // element no longer configured
        if (!isset($acc[$cat])) $acc[$cat] = ['sum' => 0, 'n' => 0, 'elements' => []];
        $acc[$cat]['sum'] += $v;
        $acc[$cat]['n']   += 1;
        $acc[$cat]['elements'][$el] = $v;
    }
    $out = [];
    foreach ($acc as $cat => $a) {
        $out[$cat] = [
            'avg'      => round($a['sum'] / $a['n'], 1),
            'n'        => $a['n'],
            'elements' => $a['elements'],
        ];
    }
    return $out;
}

/**
 * Severity tier colour for a 1-10 intensity score.
 * Mirrors intensityColor() in heatmap.php so server- and client-rendered
 * views agree.
 */
function hm_tier_color(float $v): string {
    if ($v >= 9) return '#ED1C24';   // Critical
    if ($v >= 7) return '#E7952A';   // High
    if ($v >= 5) return '#F59E0B';   // Moderate
    if ($v >= 2) return '#65A30D';   // Low
    return '#059669';                // Stable
}

/** Severity tier label for a 1-10 intensity score. */
function hm_tier_label(float $v): string {
    if ($v >= 9) return 'Critical';
    if ($v >= 7) return 'High';
    if ($v >= 5) return 'Moderate';
    if ($v >= 2) return 'Low';
    return 'Stable';
}

// ── Two-week monitoring cycles ───────────────────────────────────
//
// Intensity scores live on Post rows and are never overwritten, so the full
// history is inherently preserved. A "cycle" is therefore just a date window
// over those rows: closing one and opening the next destroys nothing, and any
// past cycle can be replayed by re-running the aggregation over its dates.

/** Cycle length in days. Capped at 14 - the cycle must never exceed two weeks. */
function hm_cycle_days(): int {
    $n = (int) get_setting('heatmap_cycle_days', '14');
    if ($n < 1)  $n = 14;
    if ($n > 14) $n = 14;
    return $n;
}

/** Date all cycles are counted from (a Monday by default). */
function hm_cycle_anchor(): string {
    $a = trim(get_setting('heatmap_cycle_anchor', ''));
    if ($a !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $a)) return $a;
    return '2026-01-05';
}

/**
 * The cycle containing $date.
 *
 * @return array{index:int,start:string,end:string,label:string,short:string}
 *         end is inclusive.
 */
function hm_cycle_bounds(string $date): array {
    $len    = hm_cycle_days();
    $anchor = new DateTimeImmutable(hm_cycle_anchor());
    $d      = new DateTimeImmutable(substr($date, 0, 10));

    $diff = (int) floor(($d->getTimestamp() - $anchor->getTimestamp()) / 86400);
    $idx  = (int) floor($diff / $len);

    $start = $anchor->modify('+' . ($idx * $len) . ' days');
    $end   = $start->modify('+' . ($len - 1) . ' days');

    return [
        'index' => $idx,
        'start' => $start->format('Y-m-d'),
        'end'   => $end->format('Y-m-d'),
        'label' => hm_cycle_label($start->format('Y-m-d'), $end->format('Y-m-d')),
        'short' => $start->format('j M') . ' – ' . $end->format('j M'),
    ];
}

/** Human label for a cycle, e.g. "6 – 19 Jul 2026". */
function hm_cycle_label(string $start, string $end): string {
    $s = new DateTimeImmutable($start);
    $e = new DateTimeImmutable($end);
    if ($s->format('Y') !== $e->format('Y')) {
        return $s->format('j M Y') . ' – ' . $e->format('j M Y');
    }
    if ($s->format('m') !== $e->format('m')) {
        return $s->format('j M') . ' – ' . $e->format('j M Y');
    }
    return $s->format('j') . ' – ' . $e->format('j M Y');
}

/** Move $n cycles from the cycle containing $date (negative = earlier). */
function hm_cycle_shift(string $date, int $n): array {
    $len = hm_cycle_days();
    $c   = hm_cycle_bounds($date);
    $d   = (new DateTimeImmutable($c['start']))->modify('+' . ($n * $len) . ' days');
    return hm_cycle_bounds($d->format('Y-m-d'));
}

/** The cycle we are in right now. */
function hm_cycle_current(): array {
    return hm_cycle_bounds(date('Y-m-d'));
}

/** True when $date (Y-m-d or datetime) falls inside the given cycle. */
function hm_in_cycle(string $date, array $cycle): bool {
    $d = substr($date, 0, 10);
    return $d >= $cycle['start'] && $d <= $cycle['end'];
}

/** Decode a post's stored intensityScores JSON into element => score. */
function post_intensity_scores(?string $json): array {
    if ($json === null || trim($json) === '') return [];
    $d = json_decode($json, true);
    if (!is_array($d)) return [];
    $out = [];
    foreach ($d as $el => $v) {
        $el = trim((string)$el);
        $v  = (int)$v;
        if ($el !== '' && $v >= 1 && $v <= 10) $out[$el] = $v;
    }
    return $out;
}

// ── Multi-country attribution ────────────────────────────────────
//
// Post.country stays the PRIMARY country (required, and what every listing,
// search and detail page already reads). Cross-border items also carry
// Post.additionalCountries, a JSON array of further countries. The heat map
// attributes such an item to every country it names, so transnational
// developments are not understated; regional aggregates count it once.

/** Decode Post.additionalCountries into a clean list (primary excluded). */
function post_additional_countries(?string $json, string $primary = ''): array {
    if ($json === null || trim($json) === '') return [];
    $d = json_decode($json, true);
    if (!is_array($d)) return [];
    $out = [];
    foreach ($d as $c) {
        $c = trim((string)$c);
        if ($c !== '' && $c !== $primary && !in_array($c, $out, true)) $out[] = $c;
    }
    return $out;
}

/** Every country a post is attributed to, primary first. */
function post_countries(array $post): array {
    $primary = trim((string)($post['country'] ?? ''));
    $all     = $primary !== '' ? [$primary] : [];
    foreach (post_additional_countries($post['additionalCountries'] ?? null, $primary) as $c) {
        $all[] = $c;
    }
    return $all;
}

/**
 * Read the additional-countries multi-select from a form submission.
 * Only known country names are kept, and the primary is never repeated.
 *
 * @return string|null JSON for Post.additionalCountries, or null when none
 */
function additional_countries_from_post(array $src, string $primary): ?string {
    $valid = african_countries();
    $out   = [];
    foreach ((array)($src['additionalCountries'] ?? []) as $c) {
        $c = trim((string)$c);
        if ($c !== '' && $c !== $primary && in_array($c, $valid, true) && !in_array($c, $out, true)) {
            $out[] = $c;
        }
    }
    return $out ? json_encode($out, JSON_UNESCAPED_UNICODE) : null;
}

// ── Monitoring regions (groups of countries) ─────────────────────
//
// Distinct from the `heatmap_regions` setting, which holds sub-national
// places used to position map pins. A monitoring region is a named group of
// countries ("East Africa") that gets its own aggregate intensity row.
// Groups may overlap: Kenya can sit in both East Africa and the Great Lakes.

/** Built-in groups, used until Admin > Heatmap Config > Monitoring Regions is saved. */
function hm_default_country_groups(): array {
    return [
        ['name' => 'East Africa',    'countries' => ['Burundi','DR Congo','Kenya','Rwanda','Somalia','South Sudan','Tanzania','Uganda']],
        ['name' => 'Horn of Africa', 'countries' => ['Djibouti','Eritrea','Ethiopia','Somalia','South Sudan','Sudan']],
        ['name' => 'Great Lakes',    'countries' => ['Burundi','DR Congo','Kenya','Rwanda','Tanzania','Uganda']],
        ['name' => 'Sahel',          'countries' => ['Burkina Faso','Chad','Mali','Mauritania','Niger']],
    ];
}

/** Configured monitoring regions: list of ['name' => string, 'countries' => string[]]. */
function hm_country_groups(): array {
    static $cached = null;
    if ($cached !== null) return $cached;

    $out = [];
    try {
        $rows = json_decode(get_setting('heatmap_country_groups', ''), true);
        if (is_array($rows)) {
            foreach ($rows as $r) {
                if (!is_array($r)) continue;
                $name = trim((string)($r['name'] ?? ''));
                $cs   = array_values(array_unique(array_filter(array_map(
                    fn($c) => trim((string)$c), (array)($r['countries'] ?? [])
                ))));
                if ($name !== '' && $cs) $out[] = ['name' => $name, 'countries' => $cs];
            }
        }
    } catch (Throwable $e) {}

    // An explicitly saved empty list means "no regional rows": only fall back
    // to the defaults when nothing has ever been saved.
    if (!$out && get_setting('heatmap_country_groups', '') === '') $out = hm_default_country_groups();

    $cached = $out;
    return $cached;
}

// ── Score provenance ─────────────────────────────────────────────
//
// Who produced a post's intensity scores. Kept alongside the scores so the
// published methodology can state, for every number, how it was arrived at.
//   ANALYST      scored by a CRTP analyst in the admin forms
//   AI           proposed by the Tafakari Agent, not yet changed by an analyst
//   AI_REVIEWED  proposed by the agent, then adjusted by an analyst

function score_source_label(?string $src): string {
    return match ($src) {
        'ANALYST'     => 'Analyst-scored',
        'AI'          => 'AI-proposed',
        'AI_REVIEWED' => 'AI-proposed, analyst-reviewed',
        default       => 'Scored (source not recorded)',
    };
}

/**
 * Provenance label for a PUBLISHED report. Everything public has passed CRTP
 * approval, so the AI labels say what the analyst did with the proposal,
 * rather than suggesting an unreviewed score.
 */
function score_source_public_label(?string $src): string {
    return match ($src) {
        'ANALYST'     => 'Scored by a CRTP analyst',
        'AI'          => 'AI-proposed scores, accepted by a CRTP analyst',
        'AI_REVIEWED' => 'AI-proposed scores, adjusted by a CRTP analyst',
        default       => 'Scored by CRTP',
    };
}

/**
 * Provenance to store after an analyst saves a post's scoring.
 *
 * @param  array $prev   the stored Post row ([] when creating)
 * @param  array $scores the newly submitted element => score map
 * @param  int   $sources the newly submitted corroborating-source count
 * @return array{changed:bool,source:?string} changed=false means leave the
 *         stored provenance as it is
 */
function score_provenance_after_edit(array $prev, array $scores, int $sources): array {
    $old = post_intensity_scores($prev['intensityScores'] ?? null);
    ksort($old);
    ksort($scores);
    $oldSources = max(1, (int)($prev['sourceCount'] ?? 1));

    if ($old === $scores && $oldSources === $sources) {
        return ['changed' => false, 'source' => $prev['scoreSource'] ?? null];
    }
    if (!$scores) return ['changed' => true, 'source' => null];   // scoring cleared

    $was = $prev['scoreSource'] ?? null;
    $src = ($was === 'AI' || $was === 'AI_REVIEWED') ? 'AI_REVIEWED' : 'ANALYST';
    return ['changed' => true, 'source' => $src];
}

// ── Intensity aggregation ────────────────────────────────────────
//
// How individual report scores become one heat-map cell (a country, or a
// monitoring region, x category x cycle). Documented for end users on
// /methodology, which reads the same settings, so the two cannot drift.
//
//  1. Report score: the average of the report's scored elements in that
//     category (1-10), as before.
//  2. Report weight = corroboration x recency
//       corroboration = 1 + 0.25 x (independent sources - 1), capped at 2.0
//                       (1 source = 1.0, 5 or more = 2.0)
//       recency       = 0.5 ^ (days before the cycle's end / half-life),
//                       never below 0.25. Off for the all-time view, which
//                       is an overall record rather than a current reading.
//  3. Weighted mean M = sum(weight x score) / sum(weight)
//  4. Cell score = (1 - p) x M + p x Peak, where Peak is the highest single
//     report score and p is the peak weight. Without this, one critical event
//     reported once is diluted by several routine reports.
//  5. Critical flag: raised whenever any report in the cell scores 9 or
//     more, whatever the blended score, so critical events stay visible.

/** Aggregation settings, clamped to safe ranges. */
function hm_agg_settings(): array {
    $p = (float) get_setting('heatmap_peak_weight', '0.3');
    if ($p < 0) $p = 0.0;
    if ($p > 1) $p = 1.0;

    $hl = (int) get_setting('heatmap_recency_halflife', '14');
    if ($hl < 0)  $hl = 0;      // 0 = recency weighting off
    if ($hl > 90) $hl = 90;

    return ['peak_weight' => round($p, 2), 'half_life' => $hl];
}

/** Corroboration factor for a report backed by $sources independent sources. */
function hm_corroboration_factor(int $sources): float {
    $s = max(1, $sources);
    return 1.0 + 0.25 * min($s - 1, 4);
}

/**
 * Recency factor for a report dated $date, read at $refDate (Y-m-d).
 * Returns 1.0 when recency weighting is off or $refDate is null.
 */
function hm_recency_factor(string $date, ?string $refDate): float {
    $hl = hm_agg_settings()['half_life'];
    if ($hl <= 0 || $refDate === null) return 1.0;
    $age = (strtotime($refDate) - strtotime(substr($date, 0, 10))) / 86400;
    if ($age <= 0) return 1.0;
    return max(0.25, pow(0.5, $age / $hl));
}

/**
 * The date recency is measured from for a cycle: its last day, or today while
 * the cycle is still running.
 */
function hm_cycle_ref_date(array $cycle): string {
    $today = date('Y-m-d');
    return $cycle['end'] < $today ? $cycle['end'] : $today;
}

/**
 * Combine report-level scores into one cell.
 *
 * @param  array $items list of ['score' => float, 'w' => float]
 * @return array{score:float,mean:float,peak:float,n:int,critical:bool}|null
 */
function hm_aggregate(array $items): ?array {
    $sw = 0.0; $sws = 0.0; $peak = 0.0; $n = 0;
    foreach ($items as $it) {
        $s = (float)$it['score'];
        $w = max(0.0, (float)$it['w']);
        if ($s < 1 || $s > 10 || $w <= 0) continue;
        $sw  += $w;
        $sws += $w * $s;
        if ($s > $peak) $peak = $s;
        $n++;
    }
    if ($n === 0) return null;

    $mean  = $sws / $sw;
    $p     = hm_agg_settings()['peak_weight'];
    $score = (1 - $p) * $mean + $p * $peak;

    return [
        'score'    => round($score, 1),
        'mean'     => round($mean, 1),
        'peak'     => round($peak, 1),
        'n'        => $n,
        'critical' => $peak >= 9,
    ];
}

/**
 * Aggregate a set of scored reports into cells. Shared by the heat map and
 * the analysis reports, so both always compute identical numbers.
 *
 * Each report is ['countries' => string[], 'cats' => [category => score],
 * 'wc' => corroboration weight, 'wr' => corroboration x recency weight, ...].
 * A report feeds every country it names, but each monitoring region, and the
 * overall total, at most once however many of their countries it names.
 *
 * @param  string $wkey 'wr' (within a cycle) or 'wc' (all time, or a quarter)
 * @return array{country:array,region:array,total:array} cells keyed by category
 */
function hm_cells_from(array $reports, string $wkey): array {
    $groups    = hm_country_groups();
    $byCountry = [];
    $byRegion  = [];
    $total     = [];
    foreach ($reports as $r) {
        $w = $r[$wkey];
        $inRegions = [];
        foreach ($groups as $g) {
            if (array_intersect($r['countries'], $g['countries'])) $inRegions[] = $g['name'];
        }
        foreach ($r['cats'] as $cat => $s) {
            $item = ['score' => $s, 'w' => $w];
            foreach ($r['countries'] as $c)  $byCountry[$c][$cat][] = $item;
            foreach ($inRegions as $rn)      $byRegion[$rn][$cat][]  = $item;
            $total[$cat][] = $item;
        }
    }
    $finish = function (array $acc): array {
        $out = [];
        foreach ($acc as $k => $byCat) {
            foreach ($byCat as $cat => $items) {
                $cell = hm_aggregate($items);
                if ($cell !== null) $out[$k][$cat] = $cell;
            }
        }
        ksort($out);
        return $out;
    };
    $tot = [];
    foreach ($total as $cat => $items) {
        $cell = hm_aggregate($items);
        if ($cell !== null) $tot[$cat] = $cell;
    }
    return ['country' => $finish($byCountry), 'region' => $finish($byRegion), 'total' => $tot];
}

/**
 * Heat-map cells for one monitoring cycle (null = all time): reports in that
 * cycle weighted by corroboration x recency, or every report weighted by
 * corroboration alone.
 */
function hm_build_cells(array $reports, ?string $cycleStart): array {
    if ($cycleStart === null) return hm_cells_from($reports, 'wc');
    return hm_cells_from(array_values(array_filter($reports, fn($r) => $r['cycle'] === $cycleStart)), 'wr');
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
        'POLICY_BRIEF'  => '📜',
    ];
    $icon = $icons[$type] ?? '📄';
    $label = str_replace('_', ' ', $type);
    return '<span class="inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-widest text-slate-400">' . $icon . ' ' . h($label) . '</span>';
}
