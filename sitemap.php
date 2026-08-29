<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex');

$root = defined('APP_URL') && APP_URL !== 'https://your-domain.com'
    ? rtrim(APP_URL, '/')
    : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http')
      . '://' . ($_SERVER['HTTP_HOST'] ?? 'tafakaridigitalhub.com');

$now   = date('Y-m-d');

function sitemapUrl(string $loc, string $lastmod, string $changefreq, string $priority): string {
    return '  <url>' . "\n"
         . '    <loc>' . htmlspecialchars($loc, ENT_XML1, 'UTF-8') . '</loc>' . "\n"
         . '    <lastmod>' . htmlspecialchars($lastmod, ENT_XML1, 'UTF-8') . '</lastmod>' . "\n"
         . '    <changefreq>' . $changefreq . '</changefreq>' . "\n"
         . '    <priority>' . $priority . '</priority>' . "\n"
         . '  </url>' . "\n";
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
echo '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

// ── Static pages ───────────────────────────────────────────────────────────────
$static = [
    ['/',          '1.0', 'daily'],
    ['/news',      '0.9', 'daily'],
    ['/podcasts',  '0.8', 'daily'],
    ['/videos',    '0.8', 'daily'],
    ['/documents', '0.8', 'weekly'],
    ['/policy-briefs', '0.8', 'weekly'],
    ['/gallery',   '0.7', 'weekly'],
    ['/heatmap',   '0.7', 'daily'],
    ['/about',     '0.5', 'monthly'],
];
foreach ($static as [$path, $pri, $freq]) {
    echo sitemapUrl($root . $path, $now, $freq, $pri);
}

// ── Dynamic post pages ─────────────────────────────────────────────────────────
$typeToPath = [
    'ARTICLE'      => '/news/',
    'PODCAST'      => '/podcasts/',
    'VIDEO'        => '/videos/',
    'DOCUMENT'     => '/documents/',
    'POLICY_BRIEF' => '/policy-briefs/',
];

try {
    $posts = db()->query("
        SELECT id, type, thumbnailUrl, updatedAt, createdAt
        FROM Post WHERE status='PUBLISHED'
          AND type IN ('ARTICLE','PODCAST','VIDEO','DOCUMENT','POLICY_BRIEF')
        ORDER BY updatedAt DESC LIMIT 2000
    ")->fetchAll();

    foreach ($posts as $p) {
        $path = ($typeToPath[$p['type']] ?? '/news/') . rawurlencode($p['id']);
        $lastmod = substr($p['updatedAt'] ?? $p['createdAt'] ?? $now, 0, 10) ?: $now;

        $priority   = $p['type'] === 'ARTICLE' ? '0.7' : '0.6';
        $changefreq = 'weekly';

        $urlXml  = '  <url>' . "\n";
        $urlXml .= '    <loc>' . htmlspecialchars($root . $path, ENT_XML1, 'UTF-8') . '</loc>' . "\n";
        $urlXml .= '    <lastmod>' . htmlspecialchars($lastmod, ENT_XML1, 'UTF-8') . '</lastmod>' . "\n";
        $urlXml .= '    <changefreq>' . $changefreq . '</changefreq>' . "\n";
        $urlXml .= '    <priority>' . $priority . '</priority>' . "\n";

        // Image extension for Google Image Sitemap
        if (!empty($p['thumbnailUrl'])) {
            $imgUrl = str_starts_with($p['thumbnailUrl'], 'http')
                ? $p['thumbnailUrl']
                : $root . $p['thumbnailUrl'];
            $urlXml .= '    <image:image><image:loc>'
                     . htmlspecialchars($imgUrl, ENT_XML1, 'UTF-8')
                     . '</image:loc></image:image>' . "\n";
        }

        $urlXml .= '  </url>' . "\n";
        echo $urlXml;
    }
} catch (Exception $e) { /* DB error — skip dynamic entries */ }

echo '</urlset>' . "\n";
