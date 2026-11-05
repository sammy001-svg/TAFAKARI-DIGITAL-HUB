<?php
declare(strict_types=1);
/**
 * Partners & Network Members - data layer shared by the admin module, the
 * public /partners directory, the About page and the footer.
 *
 * Requires includes/functions.php.
 */

/** Partner types, in display order. Keys are stored; labels are shown. */
function partner_types(): array {
    return [
        'ACADEMIC'   => 'Academic & research',
        'FAITH'      => 'Faith-based',
        'CIVIL'      => 'Civil society & NGO',
        'REGIONAL'   => 'Regional & intergovernmental',
        'UN'         => 'UN & international agency',
        'GOVERNMENT' => 'Government',
        'FUNDING'    => 'Funding partner',
        'MEDIA'      => 'Media',
        'NETWORK'    => 'Network member',
    ];
}

function partner_type_label(?string $key): string {
    return partner_types()[$key ?? ''] ?? partner_types()['NETWORK'];
}

/**
 * The Partner table plus the columns the premium module added: type,
 * country, featured and partner-since year. Column changes are attempted at
 * most once (recorded in SiteSetting), as elsewhere in the site.
 */
function ensure_partner_schema(): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;
    ensure_partners_table();
    if (get_setting('schema_partner_v2', '') !== '') return;
    try {
        $have = [];
        foreach (db()->query('SHOW COLUMNS FROM `Partner`')->fetchAll() as $r) $have[$r['Field']] = true;
        $add = [];
        if (!isset($have['category']))     $add[] = "ADD COLUMN `category` VARCHAR(20) NOT NULL DEFAULT 'NETWORK'";
        if (!isset($have['country']))      $add[] = 'ADD COLUMN `country` VARCHAR(100) NULL';
        if (!isset($have['featured']))     $add[] = 'ADD COLUMN `featured` TINYINT(1) NOT NULL DEFAULT 0';
        if (!isset($have['partnerSince'])) $add[] = 'ADD COLUMN `partnerSince` SMALLINT NULL';
        set_setting('schema_partner_v2', 'attempted');
        if ($add) db()->exec('ALTER TABLE `Partner` ' . implode(', ', $add));
        set_setting('schema_partner_v2', 'ok');
    } catch (Throwable $e) {
        error_log('[ensure_partner_schema] ' . $e->getMessage() . ' - apply migrations/008 manually.');
        set_setting('schema_partner_v2', 'failed');
    }
}

/** True when the premium columns exist (an un-migrated table still works). */
function partner_has_v2(): bool {
    static $ok = null;
    if ($ok !== null) return $ok;
    try {
        $cols = array_column(db()->query('SHOW COLUMNS FROM `Partner`')->fetchAll(), 'Field');
        return $ok = in_array('category', $cols, true) && in_array('featured', $cols, true);
    } catch (Throwable $e) {
        return $ok = false;
    }
}

/** Normalise a row so every page can rely on the same keys. */
function partner_row(array $p): array {
    $p['category']     = isset(partner_types()[$p['category'] ?? '']) ? $p['category'] : 'NETWORK';
    $p['country']      = trim((string) ($p['country'] ?? ''));
    $p['featured']     = (int) ($p['featured'] ?? 0) === 1;
    $p['isActive']     = (int) ($p['isActive'] ?? 0) === 1;
    $p['partnerSince'] = !empty($p['partnerSince']) ? (int) $p['partnerSince'] : null;
    $p['typeLabel']    = partner_type_label($p['category']);
    return $p;
}

/** @return array[] every partner (admin), in display order */
function partners_all(): array {
    ensure_partner_schema();
    try {
        return array_map('partner_row', db()->query('SELECT * FROM Partner ORDER BY sortOrder ASC, createdAt ASC')->fetchAll());
    } catch (Throwable $e) {
        return [];
    }
}

/** @return array[] visible partners (public), featured first when asked */
function partners_public(bool $featuredFirst = false, int $limit = 0): array {
    $rows = array_values(array_filter(partners_all(), fn($p) => $p['isActive']));
    if ($featuredFirst) {
        usort($rows, fn($a, $b) => [$b['featured'] ? 1 : 0, (int) $a['sortOrder']] <=> [$a['featured'] ? 1 : 0, (int) $b['sortOrder']]);
    }
    return $limit > 0 ? array_slice($rows, 0, $limit) : $rows;
}

/** Up to two initials for the monogram fallback ("Hekima University College" -> "HU"). */
function partner_initials(string $name): string {
    $skip  = ['of', 'the', 'and', 'for', 'in', '&', 'de', 'la', 'des'];
    $words = [];
    foreach (preg_split('/[\s\-]+/u', trim(strip_tags($name))) as $w) {
        $w = preg_replace('/[^\p{L}\p{N}]/u', '', $w);   // letters and digits only
        if ($w !== '' && !in_array(mb_strtolower($w), $skip, true)) $words[] = $w;
    }
    if (!$words) return '?';
    $i = mb_strtoupper(mb_substr($words[0], 0, 1));
    if (isset($words[1])) $i .= mb_strtoupper(mb_substr($words[1], 0, 1));
    return $i;
}

/** Logo tile with a monogram fallback when there is no logo or it fails to load. */
function partner_logo_html(array $p, string $size = ''): string {
    $cls  = 'tk-logo' . ($size !== '' ? ' tk-logo--' . $size : '');
    $mono = '<span class="tk-mono" aria-hidden="true">' . h(partner_initials($p['name'])) . '</span>';
    if (!empty($p['logoUrl'])) {
        return '<span class="' . $cls . '"><img src="' . h($p['logoUrl']) . '" alt="' . h($p['name']) . ' logo" loading="lazy"'
             . ' onerror="this.replaceWith(Object.assign(document.createElement(\'span\'),{className:\'tk-mono\',textContent:' . h(json_encode(partner_initials($p['name']))) . '}))"></span>';
    }
    return '<span class="' . $cls . '">' . $mono . '</span>';
}

/** A website URL safe to link to (http/https only), or ''. */
function partner_safe_url(?string $url): string {
    $url = trim((string) $url);
    if ($url === '') return '';
    // Any other scheme (javascript:, data:, ftp:, mailto:) is refused outright
    if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $url) && !preg_match('#^https?://#i', $url)) return '';
    if (!preg_match('#^https?://#i', $url)) $url = 'https://' . $url;
    return filter_var($url, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $url) ? $url : '';
}

/** Host for display ("https://www.au.int/en" -> "au.int"). */
function partner_host(string $url): string {
    return preg_replace('/^www\./', '', (string) parse_url($url, PHP_URL_HOST));
}

/**
 * Validate and save a partner from a form submission.
 *
 * @return array{ok:bool,error?:string,id?:string}
 */
function partner_save(array $in): array {
    ensure_partner_schema();
    $name = trim((string) ($in['name'] ?? ''));
    if ($name === '')            return ['ok' => false, 'error' => 'Partner name is required.'];
    if (mb_strlen($name) > 200)  return ['ok' => false, 'error' => 'Partner name is too long (200 characters at most).'];
    $subtitle = mb_substr(trim((string) ($in['subtitle'] ?? '')), 0, 200);
    $desc     = trim((string) ($in['description'] ?? ''));
    if (mb_strlen($desc) > 600)  return ['ok' => false, 'error' => 'The description is too long (600 characters at most).'];
    $web = trim((string) ($in['websiteUrl'] ?? ''));
    if ($web !== '' && partner_safe_url($web) === '') return ['ok' => false, 'error' => 'The website must be a valid web address.'];
    $web  = $web !== '' ? partner_safe_url($web) : '';
    $logo = mb_substr(trim((string) ($in['logoUrl'] ?? '')), 0, 500);
    $cat  = isset(partner_types()[$in['category'] ?? '']) ? $in['category'] : 'NETWORK';
    $country = trim((string) ($in['country'] ?? ''));
    if ($country !== '' && !in_array($country, african_countries(), true) && $country !== 'International') $country = '';
    $since = (int) ($in['partnerSince'] ?? 0);
    $since = ($since >= 1950 && $since <= (int) date('Y') + 1) ? $since : null;
    $active   = !empty($in['isActive']) ? 1 : 0;
    $featured = !empty($in['featured']) ? 1 : 0;

    $pdo = db();
    $id  = trim((string) ($in['id'] ?? ''));
    $v2  = partner_has_v2();
    $now = date('Y-m-d H:i:s');
    if ($id !== '') {
        $sql  = 'UPDATE Partner SET name=?, subtitle=?, description=?, logoUrl=?, websiteUrl=?, isActive=?, updatedAt=?'
              . ($v2 ? ', category=?, country=?, featured=?, partnerSince=?' : '') . ' WHERE id=?';
        $args = [$name, $subtitle, $desc, $logo, $web, $active, $now];
        if ($v2) array_push($args, $cat, $country ?: null, $featured, $since);
        $args[] = $id;
        $pdo->prepare($sql)->execute($args);
        return ['ok' => true, 'id' => $id];
    }
    // New partners go to the end of the order
    $next = (int) $pdo->query('SELECT COALESCE(MAX(sortOrder), 0) + 1 FROM Partner')->fetchColumn();
    $id   = generate_id();
    $cols = ['id', 'name', 'subtitle', 'description', 'logoUrl', 'websiteUrl', 'sortOrder', 'isActive', 'createdAt', 'updatedAt'];
    $args = [$id, $name, $subtitle, $desc, $logo, $web, $next, $active, $now, $now];
    if ($v2) { array_push($cols, 'category', 'country', 'featured', 'partnerSince'); array_push($args, $cat, $country ?: null, $featured, $since); }
    $pdo->prepare('INSERT INTO Partner (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')')->execute($args);
    return ['ok' => true, 'id' => $id];
}

/**
 * Persist a new display order for some or all partners.
 *
 * The list may be a subset (dragging while a filter hides some cards): the
 * listed partners are rearranged among the positions they already occupy,
 * and every other partner keeps its place. Unknown ids are ignored.
 */
function partner_reorder(array $ids): int {
    $current = array_column(partners_all(), 'id');
    $ids     = array_values(array_filter(array_unique(array_map('strval', $ids)), fn($i) => in_array($i, $current, true)));
    if (!$ids) return 0;
    $queue = $ids;
    $final = [];
    foreach ($current as $id) {
        $final[] = in_array($id, $ids, true) ? array_shift($queue) : $id;
    }
    $st = db()->prepare('UPDATE Partner SET sortOrder=? WHERE id=?');
    foreach ($final as $i => $id) $st->execute([$i + 1, $id]);
    return count($ids);
}
