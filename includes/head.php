<?php
// ── Compute canonical URL + OG values from page variables ─────────────────────
$_proto    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_host     = $_SERVER['HTTP_HOST'] ?? 'tafakaridigitalhub.com';
$_path     = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$_siteRoot = $_proto . '://' . $_host;

$_canonical = $pageUrl ?? ($_siteRoot . $_path);

// Fallback values — override per-page by setting $pageTitle, $pageDesc, etc. before include
$_title     = strip_tags($pageTitle ?? 'Tafakari Digital Hub | Knowledge & Community Platform');
$_desc      = $pageDesc    ?? 'A centralized knowledge repository, media broadcasting center, and community engagement tool for Kenya, Ethiopia, DR Congo, and conflict zones across Africa.';
$_image     = $pageImage   ?? '/public/crtp-og-image.png';
$_type      = $pageType    ?? 'website';   // 'article' on news-detail.php
$_author    = $pageAuthor  ?? 'Tafakari Digital Hub';
$_published = $pagePublished ?? '';
$_modified  = $pageModified  ?? $pagePublished ?? '';
$_keywords  = $pageKeywords  ?? 'Africa, conflict, peace, research, Kenya, Ethiopia, DR Congo, policy, governance';
$_siteName  = 'Tafakari Digital Hub — CRTP';

// Absolute image URL
if ($_image && !str_starts_with($_image, 'http')) {
    $_image = $_siteRoot . $_image;
}

// JSON-LD schema
if ($_type === 'article') {
    $_schema = [
        '@context'        => 'https://schema.org',
        '@type'           => 'Article',
        'headline'        => $_title,
        'description'     => $_desc,
        'image'           => $_image ?: null,
        'datePublished'   => $_published ? date('c', strtotime($_published)) : null,
        'dateModified'    => $_modified  ? date('c', strtotime($_modified))  : null,
        'author'          => ['@type' => 'Person', 'name' => $_author],
        'publisher'       => [
            '@type' => 'Organization',
            'name'  => 'Tafakari Digital Hub',
            'logo'  => ['@type' => 'ImageObject', 'url' => $_siteRoot . '/public/crtp-logo.png'],
        ],
        'mainEntityOfPage'=> ['@type' => 'WebPage', '@id' => $_canonical],
    ];
} else {
    $_schema = [
        '@context'        => 'https://schema.org',
        '@type'           => 'WebSite',
        'name'            => $_siteName,
        'url'             => $_siteRoot,
        'description'     => $_desc,
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => ['@type' => 'EntryPoint', 'urlTemplate' => $_siteRoot . '/search?q={search_term_string}'],
            'query-input' => 'required name=search_term_string',
        ],
    ];
}
$_schemaJson = json_encode(array_filter($_schema), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
<!DOCTYPE html>
<html lang="en" prefix="og: https://ogp.me/ns#">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">

  <!-- ── Primary SEO ─────────────────────────────────────────────────────── -->
  <title><?= htmlspecialchars($_title, ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="description" content="<?= htmlspecialchars($_desc,     ENT_QUOTES, 'UTF-8') ?>">
  <meta name="keywords"    content="<?= htmlspecialchars($_keywords, ENT_QUOTES, 'UTF-8') ?>">
  <meta name="author"      content="<?= htmlspecialchars($_author,   ENT_QUOTES, 'UTF-8') ?>">
  <meta name="robots"      content="index, follow, max-snippet:-1, max-image-preview:large">
  <link rel="canonical"    href="<?= htmlspecialchars($_canonical, ENT_QUOTES, 'UTF-8') ?>">

  <!-- ── Open Graph (Facebook, WhatsApp, LinkedIn) ───────────────────────── -->
  <meta property="og:type"        content="<?= htmlspecialchars($_type,      ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:title"       content="<?= htmlspecialchars($_title,     ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:description" content="<?= htmlspecialchars($_desc,      ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:image"       content="<?= htmlspecialchars($_image,     ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:image:width"  content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:url"         content="<?= htmlspecialchars($_canonical, ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:site_name"   content="<?= htmlspecialchars($_siteName,  ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:locale"      content="en_GB">
  <?php if ($_type === 'article'): ?>
    <?php if ($_published): ?>
      <meta property="article:published_time" content="<?= htmlspecialchars(date('c', strtotime($_published)), ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    <?php if ($_modified): ?>
      <meta property="article:modified_time"  content="<?= htmlspecialchars(date('c', strtotime($_modified)), ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    <meta property="article:author" content="<?= htmlspecialchars($_author, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="article:section" content="<?= htmlspecialchars($post['issueCategory'] ?? 'Research', ENT_QUOTES, 'UTF-8') ?>">
  <?php endif; ?>

  <!-- ── Twitter / X Card ─────────────────────────────────────────────────── -->
  <meta name="twitter:card"        content="summary_large_image">
  <meta name="twitter:site"        content="@TafakariHub">
  <meta name="twitter:title"       content="<?= htmlspecialchars($_title, ENT_QUOTES, 'UTF-8') ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($_desc,  ENT_QUOTES, 'UTF-8') ?>">
  <meta name="twitter:image"       content="<?= htmlspecialchars($_image, ENT_QUOTES, 'UTF-8') ?>">

  <!-- ── Favicons ──────────────────────────────────────────────────────────── -->
  <link rel="icon"             href="/public/crtp-logo.png" type="image/png">
  <link rel="shortcut icon"    href="/public/crtp-logo.png" type="image/png">
  <link rel="apple-touch-icon" href="/public/crtp-logo.png">

  <!-- ── JSON-LD Structured Data ──────────────────────────────────────────── -->
  <script type="application/ld+json"><?= $_schemaJson ?></script>

  <!-- ── Fonts ─────────────────────────────────────────────────────────────── -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- ── Tailwind ──────────────────────────────────────────────────────────── -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            outfit: ['Outfit', 'sans-serif'],
            inter:  ['Inter', 'sans-serif'],
          },
          colors: {
            primary:   '#750B25',
            secondary: '#E7952A',
            accent:    '#ED1C24',
          },
          borderRadius: {
            '4xl': '2rem',
          }
        }
      }
    }
  </script>

  <!-- ── Global styles ─────────────────────────────────────────────────────── -->
  <style>
    :root {
      --primary:      #750B25;
      --secondary:    #E7952A;
      --accent:       #ED1C24;
      --offwhite:     #F8F8F0;
      --dark:         #0D0102;
      --glass-bg:     rgba(117, 11, 37, 0.97);
      --glass-border: rgba(255, 255, 255, 0.15);
    }
    body { font-family: 'Inter', sans-serif; }
    .font-outfit { font-family: 'Outfit', sans-serif !important; }
    .glass {
      background: rgba(255,255,255,0.85);
      border: 1px solid rgba(255,255,255,0.3);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
    }
    .glass-dark {
      background: var(--glass-bg);
      border: 1px solid var(--glass-border);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
    }
    /* ── Buttons ── */
    .btn-primary {
      display: inline-flex; align-items: center;
      background: #750B25; color: #fff;
      padding: 0.65rem 1.4rem;
      border-radius: 9999px; font-weight: 700; font-size: 0.875rem;
      text-decoration: none; border: none; cursor: pointer;
      transition: transform .15s, box-shadow .15s;
    }
    .btn-primary:hover  { transform: scale(1.04); box-shadow: 0 8px 24px rgba(117,11,37,.35); }
    .btn-primary:active { transform: scale(.97); }
    .btn-secondary {
      display: inline-flex; align-items: center;
      background: #E7952A; color: #020617;
      padding: 0.65rem 1.4rem;
      border-radius: 9999px; font-weight: 700; font-size: 0.875rem;
      text-decoration: none; border: none; cursor: pointer;
      transition: transform .15s;
    }
    .btn-secondary:hover { transform: scale(1.04); }
    .btn-gold {
      display: inline-flex; align-items: center;
      background: #E7952A; color: #0D0102;
      padding: 0.65rem 1.4rem;
      border-radius: 9999px; font-weight: 700; font-size: 0.875rem;
      text-decoration: none; border: none; cursor: pointer;
      transition: transform .15s, box-shadow .15s;
    }
    .btn-gold:hover  { transform: scale(1.04); box-shadow: 0 8px 24px rgba(231,149,42,.4); }
    .btn-gold:active { transform: scale(.97); }
    .btn-cream {
      display: inline-flex; align-items: center;
      background: #F8F8F0; color: #2A0515;
      padding: 0.65rem 1.4rem;
      border-radius: 9999px; font-weight: 700; font-size: 0.875rem;
      text-decoration: none; border: 1.5px solid #E7952A; cursor: pointer;
      transition: transform .15s, background .15s;
    }
    .btn-cream:hover { background: #F5E9C8; transform: scale(1.03); }
    /* ── Utilities ── */
    .line-clamp-2 { display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden; }
    .line-clamp-3 { display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden; }
    .premium-gradient { background: #750B25; }
    /* ── Focus ring for accessibility ── */
    :focus-visible { outline: 2px solid #E7952A; outline-offset: 2px; }
  </style>
</head>
