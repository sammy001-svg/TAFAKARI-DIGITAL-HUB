<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Tafakari Digital Hub | Knowledge, Media & Community Platform for East & Central Africa';
$pageDesc  = 'A centralized knowledge repository, media broadcasting center, and community engagement hub serving Kenya, Ethiopia, and the Democratic Republic of Congo.';

// ── Site settings ──────────────────────────────────────────────────────────
$announcementActive   = get_setting('announcement_active',    '0');
$announcementText     = get_setting('announcement_text',      '');
$announcementUrl      = get_setting('announcement_url',       '');
$announcementColor    = get_setting('announcement_color',     'crimson');
$announcementImageUrl = get_setting('announcement_image_url', '');
$featuredId         = get_setting('featured_article_id', '');
$carouselJson       = get_setting('carousel_slides',     '[]');
$showPodcasts       = get_setting('show_podcasts',  '1') !== '0';
$showVideos         = get_setting('show_videos',    '1') !== '0';
$showDocuments      = get_setting('show_documents', '1') !== '0';

$dbSlides = json_decode($carouselJson, true);
$useDbCarousel = is_array($dbSlides) && count($dbSlides) > 0;

// Featured article
$featuredPost = null;
if ($featuredId) {
    try {
        $s = db()->prepare("SELECT id,title,description,thumbnailUrl,country,issueCategory,createdAt FROM Post WHERE id=? AND type='ARTICLE' AND status='PUBLISHED' LIMIT 1");
        $s->execute([$featuredId]);
        $featuredPost = $s->fetch() ?: null;
    } catch (Exception $e) {}
}

// Banner color map
$bannerBg = match($announcementColor) {
    'amber'  => '#E7952A',
    'green'  => '#059669',
    'slate'  => '#334155',
    default  => '#750B25',
};

$latestArticles  = [];
$latestPodcasts  = [];
$latestVideos    = [];
$latestDocuments = [];
$publishedCount  = 0;
$totalViews      = 0;
$typeCounts      = [];

try {
    $stmt = db()->query("
        SELECT id, title, description, thumbnailUrl, country, issueCategory, viewCount, createdAt
        FROM Post WHERE type='ARTICLE' AND status='PUBLISHED'
        ORDER BY createdAt DESC LIMIT 3
    ");
    $latestArticles = $stmt->fetchAll();
} catch (Exception $e) {}

try {
    $stmt = db()->query("
        SELECT id, title, description, thumbnailUrl, country, issueCategory, createdAt
        FROM Post WHERE type='PODCAST' AND status='PUBLISHED'
        ORDER BY createdAt DESC LIMIT 3
    ");
    $latestPodcasts = $stmt->fetchAll();
} catch (Exception $e) {}

try {
    $stmt = db()->query("
        SELECT id, title, description, thumbnailUrl, country, issueCategory, viewCount, createdAt
        FROM Post WHERE type='VIDEO' AND status='PUBLISHED'
        ORDER BY createdAt DESC LIMIT 3
    ");
    $latestVideos = $stmt->fetchAll();
} catch (Exception $e) {}

try {
    $stmt = db()->query("
        SELECT id, title, description, mediaUrl, country, issueCategory, downloadCount, createdAt
        FROM Post WHERE type='DOCUMENT' AND status='PUBLISHED'
        ORDER BY createdAt DESC LIMIT 3
    ");
    $latestDocuments = $stmt->fetchAll();
} catch (Exception $e) {}

try {
    $row = db()->query("
        SELECT
            COUNT(*) AS total,
            COALESCE(SUM(viewCount),0) AS views,
            COUNT(CASE WHEN type='ARTICLE'       THEN 1 END) AS articles,
            COUNT(CASE WHEN type='PODCAST'       THEN 1 END) AS podcasts,
            COUNT(CASE WHEN type='VIDEO'         THEN 1 END) AS videos,
            COUNT(CASE WHEN type='DOCUMENT'      THEN 1 END) AS documents,
            COUNT(CASE WHEN type='GALLERY_IMAGE' THEN 1 END) AS images
        FROM Post WHERE status='PUBLISHED'
    ")->fetch();
    if ($row) {
        $publishedCount = (int)$row['total'];
        $totalViews     = (int)$row['views'];
        $typeCounts     = $row;
    }
} catch (Exception $e) {}
?>
<?php include __DIR__ . '/includes/head.php'; ?>

<style>
  body { overflow-x: hidden; }

  /* ── Hero carousel ─────────────────────────────────────────── */
  .hero-slide {
    position: absolute; inset: 0;
    opacity: 0; visibility: hidden;
    transition: opacity 1s cubic-bezier(.4,0,.2,1), visibility 1s;
  }
  .hero-slide.is-active { opacity: 1; visibility: visible; }

  /* Slow zoom-out on the active slide (Ken Burns) */
  .hero-img {
    width: 100%; height: 100%; object-fit: cover;
    transform: scale(1.08);
    transition: transform 9s linear;
  }
  .hero-slide.is-active .hero-img { transform: scale(1); }

  /* Flat maroon veil — brand colour, no gradient */
  .hero-veil { position: absolute; inset: 0; background: rgba(117,11,37,.62); }

  /* Staggered entrance for the active slide's copy */
  .hero-anim {
    opacity: 0; transform: translateY(20px);
    transition: opacity .8s cubic-bezier(.4,0,.2,1), transform .8s cubic-bezier(.4,0,.2,1);
  }
  .hero-slide.is-active .hero-anim { opacity: 1; transform: none; }
  .hero-slide.is-active .hero-anim:nth-child(1) { transition-delay: .18s; }
  .hero-slide.is-active .hero-anim:nth-child(2) { transition-delay: .28s; }
  .hero-slide.is-active .hero-anim:nth-child(3) { transition-delay: .38s; }
  .hero-slide.is-active .hero-anim:nth-child(4) { transition-delay: .48s; }
  .hero-slide.is-active .hero-anim:nth-child(5) { transition-delay: .58s; }

  /* Legibility without darkening the photo further */
  .hero-title { text-shadow: 0 2px 24px rgba(45,4,14,.45); }
  .hero-sub   { text-shadow: 0 1px 12px rgba(45,4,14,.4); }

  /* Prev / next arrows */
  .hero-arrow {
    position: absolute; top: 50%; transform: translateY(-50%);
    width: 46px; height: 46px; border-radius: 9999px;
    display: flex; align-items: center; justify-content: center;
    background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.3);
    color: #fff; cursor: pointer; z-index: 20;
    backdrop-filter: blur(6px);
    transition: background .2s, border-color .2s;
  }
  .hero-arrow:hover { background: rgba(255,255,255,.25); border-color: rgba(255,255,255,.6); }
  .hero-arrow-prev { left: 18px; }
  .hero-arrow-next { right: 18px; }
  @media (min-width: 768px) { .hero-arrow-next { right: 78px; } }

  /* Mobile horizontal dots */
  .hero-dot-m {
    width: 22px; height: 3px; border-radius: 9999px;
    background: rgba(255,255,255,.35); border: none; padding: 0; cursor: pointer;
    transition: width .4s, background .4s;
  }
  .hero-dot-m.is-on { width: 40px; background: #E7952A; }

  @media (max-width: 767px) {
    .hero-arrow { width: 38px; height: 38px; }
  }

  /* ── Impact stats bar ──────────────────────────────────────── */
  .stat-item { position: relative; }
  .stat-item:not(:last-child)::after {
    content: ''; position: absolute; right: 0; top: 50%;
    transform: translateY(-50%);
    width: 1px; height: 52px; background: rgba(248,248,240,.18);
  }
  @media (max-width: 767px) {
    .stat-item:nth-child(2)::after { display: none; }
  }

  /* Pillar cards */
  .pillar-card {
    border-left: 4px solid transparent;
    transition: border-color .2s, transform .22s, box-shadow .22s;
  }
  .pillar-card:hover {
    border-left-color: #E7952A;
    transform: translateY(-5px);
    box-shadow: 0 24px 60px rgba(0,0,0,.09);
  }

  /* Article cards */
  .article-card { transition: transform .25s, box-shadow .25s; }
  .article-card:hover { transform: translateY(-6px); box-shadow: 0 28px 56px rgba(0,0,0,.11); }

  /* Geography cards */
  .geo-card {
    position: relative; overflow: hidden; border-radius: 1.5rem;
    height: 420px; cursor: pointer;
    transition: transform .28s;
  }
  .geo-card:hover { transform: translateY(-5px); }
  .geo-card img.geo-bg {
    position: absolute; inset: 0; width: 100%; height: 100%;
    object-fit: cover;
    transition: transform .7s cubic-bezier(.4,0,.2,1);
  }
  .geo-card:hover img.geo-bg { transform: scale(1.07); }
  .geo-overlay {
    position: absolute; inset: 0;
    background: rgba(13,2,3,.72);
    transition: opacity .3s;
  }
  .geo-card:hover .geo-overlay { opacity: .95; }

  /* Eyebrow labels */
  .eyebrow-left {
    display: inline-flex; align-items: center; gap: 10px;
    font-size: .65rem; font-weight: 800; letter-spacing: .14em;
    text-transform: uppercase; color: #E7952A; margin-bottom: 1rem;
  }
  .eyebrow-left::before {
    content: ''; display: block;
    width: 30px; height: 2px; background: #E7952A; flex-shrink: 0;
  }
  .eyebrow-center {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    font-size: .65rem; font-weight: 800; letter-spacing: .14em;
    text-transform: uppercase; color: #E7952A; margin-bottom: 1rem;
  }
  .eyebrow-center::before,
  .eyebrow-center::after {
    content: ''; display: block;
    width: 28px; height: 2px; background: #E7952A;
  }

  /* Mission drop-cap quote mark */
  .mission-quote { position: relative; padding-top: 1rem; }
  .mission-quote::before {
    content: '\201C';
    position: absolute; top: -2.5rem; left: -1rem;
    font-size: 9rem; line-height: 1; font-family: 'Outfit', sans-serif;
    font-weight: 900; color: #E7952A; opacity: .15;
    pointer-events: none; user-select: none;
  }

  /* Scroll bounce animation */
  .scroll-bounce { animation: scrollBounce 2.2s ease-in-out infinite; }
  @keyframes scrollBounce {
    0%,100% { transform: translateX(-50%) translateY(0); }
    50%      { transform: translateX(-50%) translateY(9px); }
  }

  /* Ghost CTA button */
  .btn-ghost-white {
    display: inline-flex; align-items: center; gap: 8px;
    padding: .8rem 2rem; border-radius: 9999px;
    font-weight: 700; font-size: .875rem; text-decoration: none;
    color: #fff; border: 1.5px solid rgba(255,255,255,.35);
    transition: border-color .2s, background .2s;
  }
  .btn-ghost-white:hover { border-color: rgba(255,255,255,.7); background: rgba(255,255,255,.1); }
</style>

<body class="antialiased min-h-screen flex flex-col bg-white font-inter">

<?php if ($announcementActive === '1' && $announcementText !== ''): ?>
<div id="site-banner" style="background:<?= h($bannerBg) ?>;color:#fff;font-size:13px;font-weight:600;padding:9px 48px;position:relative;line-height:1.4;display:flex;align-items:center;justify-content:center;gap:8px">
  <?php if (!empty($announcementImageUrl)): ?>
    <img src="<?= h($announcementImageUrl) ?>" alt="" style="height:22px;width:auto;border-radius:3px;object-fit:contain;flex-shrink:0" onerror="this.style.display='none'">
  <?php endif; ?>
  <span>
    <?php if ($announcementUrl): ?>
      <a href="<?= h($announcementUrl) ?>" style="color:inherit;text-decoration:underline;text-underline-offset:2px"><?= h($announcementText) ?></a>
    <?php else: ?>
      <?= h($announcementText) ?>
    <?php endif; ?>
  </span>
  <button onclick="this.closest('#site-banner').remove()" title="Dismiss"
          style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:rgba(255,255,255,.7);cursor:pointer;font-size:18px;line-height:1;padding:4px">&times;</button>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="grow flex flex-col">

  <!-- ══════════════════════════════════════════
       1. HERO CAROUSEL
  ══════════════════════════════════════════ -->
  <section id="hero" class="relative w-full overflow-hidden" style="height:84vh;max-height:820px;min-height:540px">

    <?php
    $defaultSlides = [
      ['img'=>'/public/nairobi_sunset_hero.png',     'region'=>'Kenya',    'title'=>'Tracking What Matters Across All 47 Counties',      'sub'=>'Real-time issue mapping and data-driven insights powering better decisions across Kenya.',                                      'badge'=>'47 Counties Covered', 'url'=>'', 'key'=>'kenya'],
      ['img'=>'/public/ethiopia_community_hero.png', 'region'=>'Ethiopia', 'title'=>'Community Knowledge from the Horn of Africa',        'sub'=>'Field reports, research briefs and oral histories from 11 regional states documented for public access.',                    'badge'=>'11 Regional States',  'url'=>'', 'key'=>'ethiopia'],
      ['img'=>'/public/drc_nature_hero.png',         'region'=>'DR Congo', 'title'=>'Amplifying Voices from the Congo Basin',            'sub'=>'Documenting resilience, community narratives, and structural change across 26 provinces.',                                   'badge'=>'26 Provinces',        'url'=>'', 'key'=>'drc'],
    ];
    $slides = $useDbCarousel ? $dbSlides : $defaultSlides;
    // filter out slides with no image or title
    $slides = array_values(array_filter($slides, fn($s) => !empty($s['img']) && !empty($s['title'])));
    if (empty($slides)) $slides = $defaultSlides;
    foreach ($slides as $i => $s): ?>
      <div class="hero-slide <?= $i === 0 ? 'is-active' : '' ?>" data-index="<?= $i ?>">
        <img src="<?= h($s['img']) ?>" alt="<?= h($s['title']) ?>" class="hero-img"
             <?= $i === 0 ? 'fetchpriority="high"' : 'fetchpriority="low"' ?>>
        <div class="hero-veil"></div>

        <div class="absolute inset-0 flex flex-col justify-center px-8 md:px-24 pb-24 md:pb-20">

          <!-- Region eyebrow -->
          <div class="hero-anim flex items-center gap-3 mb-6">
            <span class="block w-10 h-px" style="background:#E7952A"></span>
            <span class="text-[11px] font-black uppercase tracking-[.22em]" style="color:#E7952A" <?= isset($s['key']) ? 'data-i18n="home.hero.'.h($s['key']).'.region"' : '' ?>><?= h($s['region']) ?></span>
          </div>

          <!-- Headline -->
          <h1 class="hero-anim hero-title font-outfit font-black text-4xl sm:text-5xl md:text-6xl text-white leading-[1.04] max-w-3xl mb-6" <?= isset($s['key']) ? 'data-i18n="home.hero.'.h($s['key']).'.title"' : '' ?>>
            <?= h($s['title']) ?>
          </h1>

          <!-- Sub-headline -->
          <p class="hero-anim hero-sub text-white/85 text-base md:text-lg max-w-xl mb-10 leading-relaxed" <?= isset($s['key']) ? 'data-i18n="home.hero.'.h($s['key']).'.sub"' : '' ?>>
            <?= h($s['sub']) ?>
          </p>

          <!-- CTAs -->
          <div class="hero-anim flex flex-wrap items-center gap-5">
            <a href="<?= !empty($s['url']) ? h($s['url']) : '/heatmap' ?>" class="btn-gold" style="padding:.8rem 1.9rem" <?= empty($s['cta']) ? 'data-i18n="home.exploreHeatmap"' : '' ?>><?= !empty($s['cta']) ? h($s['cta']) : 'Explore Heatmap' ?></a>
            <a href="/about" class="inline-flex items-center gap-2 text-sm font-bold text-white/85 hover:text-white transition-colors">
              <span data-i18n="ourMission">Our Mission</span>
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
          </div>

          <!-- Coverage badge -->
          <div class="hero-anim mt-10">
            <span class="inline-block text-[10px] font-black uppercase tracking-widest px-3 py-1.5 rounded-full border"
                  style="color:#E7952A;border-color:rgba(231,149,42,.55);background:rgba(231,149,42,.12)" <?= isset($s['key']) ? 'data-i18n="home.hero.'.h($s['key']).'.badge"' : '' ?>>
              <?= h($s['badge']) ?>
            </span>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <!-- Vertical dot nav (desktop right edge) -->
    <div class="absolute right-8 top-1/2 -translate-y-1/2 flex-col gap-3 z-20 hidden md:flex">
      <?php foreach ($slides as $i => $_): ?>
        <button class="carousel-dot py-1 flex items-center justify-end" data-index="<?= $i ?>">
          <span class="hero-dot-bar block h-0.5 rounded-full transition-all duration-500"
                style="width:<?= $i === 0 ? '2rem' : '1rem' ?>;background:<?= $i === 0 ? '#E7952A' : 'rgba(255,255,255,.35)' ?>"></span>
        </button>
      <?php endforeach; ?>
    </div>

    <?php if (count($slides) > 1): ?>
      <!-- Prev / next arrows -->
      <button class="hero-arrow hero-arrow-prev" id="hero-prev" aria-label="Previous slide">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
      </button>
      <button class="hero-arrow hero-arrow-next" id="hero-next" aria-label="Next slide">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
      </button>

      <!-- Horizontal dots (mobile) -->
      <div class="absolute bottom-8 left-8 z-20 flex items-center gap-2 md:hidden">
        <?php foreach ($slides as $i => $_): ?>
          <button class="carousel-dot hero-dot-m <?= $i === 0 ? 'is-on' : '' ?>" data-index="<?= $i ?>" aria-label="Go to slide <?= $i + 1 ?>"></button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- Slide counter (bottom-left, desktop) -->
    <div class="absolute bottom-10 left-8 md:left-24 z-20 hidden md:flex items-center gap-4">
      <span id="hero-cur" class="font-outfit font-black text-3xl" style="color:#E7952A">01</span>
      <div class="h-px w-10" style="background:rgba(255,255,255,.35)"></div>
      <span class="text-white/60 text-sm font-semibold"><?= str_pad((string)count($slides), 2, '0', STR_PAD_LEFT) ?></span>
    </div>

    <!-- Scroll indicator (bottom-center) -->
    <div class="scroll-bounce absolute bottom-8 z-20 hidden md:flex flex-col items-center gap-1.5" style="left:50%;transform:translateX(-50%)">
      <span class="text-[9px] font-black uppercase tracking-[.2em] text-white/55" data-i18n="home.scroll">Scroll</span>
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.55)" stroke-width="2.5">
        <path d="M12 5v14M5 12l7 7 7-7"/>
      </svg>
    </div>
  </section>


  <!-- ══════════════════════════════════════════
       2. IMPACT STATISTICS
  ══════════════════════════════════════════ -->
  <section style="background:#750B25" class="py-16 md:py-20 px-6">
    <div class="max-w-7xl mx-auto">

      <!-- Section label -->
      <div class="flex items-center justify-center gap-3 mb-12">
        <span class="block w-8 h-px" style="background:rgba(231,149,42,.7)"></span>
        <span class="text-[10px] font-black uppercase tracking-[.2em]" style="color:#E7952A" data-i18n="home.ourReach">Our Reach</span>
        <span class="block w-8 h-px" style="background:rgba(231,149,42,.7)"></span>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-y-12">
        <?php
        $impactStats = [
          ['num' => '3',                            'suf' => '',  'label' => 'Countries',             'key' => 'countries'],
          ['num' => '84',                           'suf' => '+', 'label' => 'Administrative Units',  'key' => 'adminUnits'],
          ['num' => format_number($publishedCount), 'suf' => '+', 'label' => 'Published Resources',   'key' => 'publishedResources'],
          ['num' => format_number($totalViews),     'suf' => '+', 'label' => 'Community Engagements', 'key' => 'communityEngagements'],
        ];
        foreach ($impactStats as $j => $st): ?>
          <div class="stat-item text-center px-4">
            <div class="font-outfit font-black text-4xl md:text-[3.25rem] leading-none mb-3" style="color:#E7952A">
              <?= h($st['num']) ?><span style="color:rgba(231,149,42,.6)"><?= h($st['suf']) ?></span>
            </div>
            <div class="text-[11px] font-bold uppercase tracking-[.14em] leading-snug"
                 style="color:rgba(248,248,240,.75)" data-i18n="home.stat.<?= h($st['key']) ?>"><?= h($st['label']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>


  <!-- ══════════════════════════════════════════
       3. MISSION STATEMENT
  ══════════════════════════════════════════ -->
  <section class="max-w-7xl mx-auto px-6 md:px-12 py-24 grid grid-cols-1 md:grid-cols-2 gap-16 md:gap-28 items-center">

    <div>
      <div class="mission-quote">
        <p class="font-outfit font-black text-3xl md:text-[2.6rem] text-slate-900 leading-snug" data-i18n="home.missionQuote">
          Connecting communities through knowledge, media, and informed dialogue across East and Central Africa.
        </p>
      </div>
    </div>

    <div>
      <span class="eyebrow-left" data-i18n="ourPurpose">Our Purpose</span>
      <p class="text-slate-500 text-[15px] leading-[1.85] mb-5" data-i18n="home.missionP1">
        Tafakari Digital Hub is a multi-user digital platform that bridges the information gap across Kenya, Ethiopia, and the Democratic Republic of Congo. We aggregate field research, amplify grassroots narratives, and make critical data accessible to decision-makers, journalists, and communities.
      </p>
      <p class="text-slate-500 text-[15px] leading-[1.85] mb-10" data-i18n="home.missionP2">
        From interactive regional heatmaps to curated document libraries and community dialogue boards &mdash; every tool on this platform is designed to inform, connect, and empower.
      </p>
      <a href="/about" class="btn-gold" style="padding:.75rem 1.75rem" data-i18n="home.discoverMission">Discover Our Mission</a>
    </div>
  </section>


  <!-- ══════════════════════════════════════════
       4. WHAT WE DO — FOUR PILLARS
  ══════════════════════════════════════════ -->
  <section class="bg-slate-50 border-y border-slate-100 py-24 px-6">
    <div class="max-w-7xl mx-auto">

      <div class="text-center mb-16">
        <span class="eyebrow-center" data-i18n="whatWeDo">What We Do</span>
        <h2 class="font-outfit font-black text-3xl md:text-4xl text-slate-900" data-i18n="home.fourPillars">Four Pillars of Impact</h2>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php
        $pillars = [
          [
            'svg'  => '<svg width="26" height="26" fill="none" stroke="#E7952A" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M15 10l4.553-2.069A1 1 0 0121 8.87v6.26a1 1 0 01-1.447.9L15 14M3 8a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/></svg>',
            'title' => 'Media Broadcasting', 'key' => 'mediaBroadcasting',
            'desc'  => 'Photo galleries, podcasts, and video libraries documenting field activities and regional oral histories across three nations.',
            'href'  => '/gallery',
            'cta'   => 'View Gallery', 'ctaKey' => 'viewGallery',
          ],
          [
            'svg'  => '<svg width="26" height="26" fill="none" stroke="#E7952A" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>',
            'title' => 'Knowledge Repository', 'key' => 'knowledgeRepository',
            'desc'  => 'A structured library of research reports, policy briefs, and actionable datasets made freely accessible for public and academic use.',
            'href'  => '/documents',
            'cta'   => 'Browse Documents', 'ctaKey' => 'browseDocuments',
          ],
          [
            'svg'  => '<svg width="26" height="26" fill="none" stroke="#E7952A" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>',
            'title' => 'Community Engagement', 'key' => 'communityEngagement',
            'desc'  => 'Moderated public comment systems and contact infrastructure ensuring safe, constructive, and inclusive community dialogue.',
            'href'  => '/contact',
            'cta'   => 'Contact Us', 'ctaKey' => 'getInvolved',
          ],
          [
            'svg'  => '<svg width="26" height="26" fill="none" stroke="#E7952A" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z"/></svg>',
            'title' => 'Interactive Data Maps', 'key' => 'interactiveDataMaps',
            'desc'  => 'Visual heatmaps displaying issue intensity by geography and category, enabling pattern recognition across regions and time periods.',
            'href'  => '/heatmap',
            'cta'   => 'Open Heatmap', 'ctaKey' => 'openHeatmap',
          ],
        ];
        foreach ($pillars as $p): ?>
          <div class="pillar-card bg-white rounded-2xl p-8 shadow-sm border border-slate-100">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-6" style="background:rgba(231,149,42,.10)">
              <?= $p['svg'] ?>
            </div>
            <h3 class="font-outfit font-bold text-[1.1rem] text-slate-900 mb-3 leading-snug" data-i18n="home.pillar.<?= h($p['key']) ?>.title"><?= h($p['title']) ?></h3>
            <p class="text-slate-500 text-sm leading-relaxed mb-6" data-i18n="home.pillar.<?= h($p['key']) ?>.desc"><?= h($p['desc']) ?></p>
            <a href="<?= h($p['href']) ?>"
               class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[.11em] transition-all hover:gap-2.5" style="color:#750B25">
              <span data-i18n="home.pillarCta.<?= h($p['ctaKey']) ?>"><?= h($p['cta']) ?></span>
              <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>


  <!-- ══════════════════════════════════════════
       5a. EDITOR'S PICK (featured article, if pinned)
  ══════════════════════════════════════════ -->
  <?php if ($featuredPost): ?>
  <section class="max-w-7xl mx-auto px-6 pt-16 pb-0">
    <div class="eyebrow-left" data-i18n="home.editorsPick">Editor's Pick</div>
    <a href="/news/<?= h($featuredPost['id']) ?>"
       class="flex flex-col md:flex-row gap-6 bg-white rounded-3xl overflow-hidden border border-slate-200 shadow-md hover:shadow-xl transition-shadow group"
       style="border-left:5px solid #750B25">
      <?php if ($featuredPost['thumbnailUrl']): ?>
        <div class="md:w-80 shrink-0 overflow-hidden" style="background:#0D0102">
          <img src="<?= h($featuredPost['thumbnailUrl']) ?>" alt="<?= h($featuredPost['title']) ?>"
               class="w-full h-56 md:h-full object-cover group-hover:scale-105 transition-transform duration-500">
        </div>
      <?php endif; ?>
      <div class="flex flex-col justify-center p-7">
        <div class="flex flex-wrap gap-2 mb-3">
          <span class="text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full" style="background:rgba(117,11,37,.09);color:#750B25" data-i18n="home.featuredReport">Featured Report</span>
          <span class="text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full bg-slate-100 text-slate-500"><?= h($featuredPost['country']) ?></span>
        </div>
        <h2 class="font-outfit font-black text-2xl md:text-3xl text-slate-900 leading-tight mb-3 group-hover:text-[#750B25] transition-colors">
          <?= h($featuredPost['title']) ?>
        </h2>
        <?php if ($featuredPost['description']): ?>
          <p class="text-slate-500 text-sm leading-relaxed mb-4 line-clamp-3"><?= h($featuredPost['description']) ?></p>
        <?php endif; ?>
        <div class="flex items-center gap-2 text-xs font-bold" style="color:#750B25">
          <span data-i18n="home.readFullReport">Read Full Report</span>
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </div>
      </div>
    </a>
  </section>
  <?php endif; ?>

  <!-- ══════════════════════════════════════════
       5. LATEST ARTICLES (conditional)
  ══════════════════════════════════════════ -->
  <?php if (!empty($latestArticles)): ?>
  <section class="max-w-7xl mx-auto px-6 py-24">
    <div class="flex items-end justify-between mb-14">
      <div>
        <span class="eyebrow-left" data-i18n="home.latestFromRegion">Latest from the Region</span>
        <h2 class="font-outfit font-black text-3xl md:text-4xl text-slate-900" data-i18n="home.recentArticles">Recent Articles</h2>
      </div>
      <a href="/news" class="hidden md:inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[.11em] hover:gap-2.5 transition-all" style="color:#750B25">
        <span data-i18n="home.viewAllArticles">View All Articles</span>
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
      </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      <?php foreach ($latestArticles as $art): ?>
        <a href="/news/<?= h($art['id']) ?>"
           class="article-card flex flex-col bg-white rounded-2xl overflow-hidden shadow-sm border border-slate-100 group">

          <div class="h-52 relative overflow-hidden shrink-0">
            <?php if (!empty($art['thumbnailUrl'])): ?>
              <img src="<?= h($art['thumbnailUrl']) ?>" alt="<?= h($art['title']) ?>"
                   class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
            <?php else: ?>
              <div class="w-full h-full flex items-center justify-center"
                   style="background:#0D0102">
                <svg width="42" height="42" fill="none" stroke="rgba(255,255,255,.25)" stroke-width="1.4" viewBox="0 0 24 24">
                  <path d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                </svg>
              </div>
            <?php endif; ?>
            <div class="absolute top-4 left-4">
              <span class="text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full text-slate-900" style="background:#E7952A">
                <?= h($art['country']) ?>
              </span>
            </div>
          </div>

          <div class="flex flex-col grow p-6">
            <span class="text-[9px] font-black uppercase tracking-widest mb-2 block" style="color:#64748b">
              <?= h($art['issueCategory']) ?>
            </span>
            <h3 class="font-outfit font-bold text-[1.05rem] text-slate-900 leading-snug mb-3 line-clamp-2 group-hover:text-primary transition-colors">
              <?= h($art['title']) ?>
            </h3>
            <?php if (!empty($art['description'])): ?>
              <p class="text-slate-500 text-sm leading-relaxed line-clamp-3 mb-4 grow">
                <?= h($art['description']) ?>
              </p>
            <?php endif; ?>
            <div class="flex items-center justify-between pt-4 mt-auto border-t border-slate-100">
              <span class="text-[11px] text-slate-400"><?= format_date($art['createdAt']) ?></span>
              <?php if (!empty($art['viewCount'])): ?>
                <span class="text-[11px] text-slate-400 flex items-center gap-1">
                  <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                    <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  </svg>
                  <?= format_number($art['viewCount']) ?>
                </span>
              <?php endif; ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="mt-10 md:hidden text-center">
      <a href="/news" class="btn-gold">View All Articles</a>
    </div>
  </section>
  <?php endif; ?>

  <!-- ══════════════════════════════════════════
       5b. LATEST PODCASTS (conditional)
  ══════════════════════════════════════════ -->
  <?php if ($showPodcasts && !empty($latestPodcasts)): ?>
  <section class="border-t border-slate-100 py-24 px-6" style="background:#F8F8F0">
    <div class="max-w-7xl mx-auto">
      <div class="flex items-end justify-between mb-14">
        <div>
          <span class="eyebrow-left" data-i18n="home.listenLearn">Listen &amp; Learn</span>
          <h2 class="font-outfit font-black text-3xl md:text-4xl text-slate-900" data-i18n="home.latestPodcasts">Latest Podcasts</h2>
        </div>
        <a href="/podcasts" class="hidden md:inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[.11em] hover:gap-2.5 transition-all" style="color:#750B25">
          <span data-i18n="home.allEpisodes">All Episodes</span>
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <?php foreach ($latestPodcasts as $i => $pod): ?>
          <div class="flex flex-col bg-white rounded-2xl overflow-hidden shadow-sm border border-slate-100 hover:shadow-md hover:-translate-y-1 transition-all duration-300">
            <!-- Artwork -->
            <div class="relative h-48 shrink-0 overflow-hidden" style="background:#0D0102">
              <?php if (!empty($pod['thumbnailUrl'])): ?>
                <img src="<?= h($pod['thumbnailUrl']) ?>" alt="<?= h($pod['title']) ?>"
                     class="w-full h-full object-cover opacity-70"
                     onerror="this.style.display='none'">
              <?php endif; ?>
              <div class="absolute inset-0 flex flex-col items-center justify-center" style="background:rgba(13,1,2,.5)">
                <div class="w-14 h-14 rounded-full flex items-center justify-center border-2 border-white/30 mb-2" style="background:rgba(231,149,42,.9)">
                  <svg width="22" height="22" fill="white" viewBox="0 0 24 24" style="margin-left:3px"><path d="M8 5v14l11-7z"/></svg>
                </div>
                <span class="text-[9px] font-black uppercase tracking-widest" style="color:rgba(255,255,255,.5)"><span data-i18n="home.episode">Episode</span> <?= $i + 1 ?></span>
              </div>
              <?php if (!empty($pod['country'])): ?>
                <div class="absolute top-4 left-4">
                  <span class="text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full text-slate-900" style="background:#E7952A">
                    <?= h($pod['country']) ?>
                  </span>
                </div>
              <?php endif; ?>
            </div>
            <!-- Info -->
            <div class="flex flex-col grow p-6">
              <?php if (!empty($pod['issueCategory'])): ?>
                <span class="text-[9px] font-black uppercase tracking-widest mb-2 block" style="color:#64748b"><?= h($pod['issueCategory']) ?></span>
              <?php endif; ?>
              <h3 class="font-outfit font-bold text-[1.05rem] text-slate-900 leading-snug mb-3 line-clamp-2">
                <?= h($pod['title']) ?>
              </h3>
              <?php if (!empty($pod['description'])): ?>
                <p class="text-slate-500 text-sm leading-relaxed line-clamp-2 mb-4 grow"><?= h($pod['description']) ?></p>
              <?php endif; ?>
              <div class="flex items-center justify-between pt-4 mt-auto border-t border-slate-100">
                <span class="text-[11px] text-slate-400"><?= format_date($pod['createdAt']) ?></span>
                <a href="/podcasts/<?= h($pod['id']) ?>" class="text-[11px] font-bold uppercase tracking-[.11em] flex items-center gap-1 hover:gap-2 transition-all" style="color:#750B25">
                  <span data-i18n="home.listen">Listen</span>
                  <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="mt-10 md:hidden text-center">
        <a href="/podcasts" class="btn-gold" data-i18n="home.allEpisodes">All Episodes</a>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ══════════════════════════════════════════
       5c. LATEST VIDEOS (conditional)
  ══════════════════════════════════════════ -->
  <?php if ($showVideos && !empty($latestVideos)): ?>
  <section class="border-t border-slate-100 py-24 px-6 bg-white">
    <div class="max-w-7xl mx-auto">
      <div class="flex items-end justify-between mb-14">
        <div>
          <span class="eyebrow-left" data-i18n="home.watch">Watch</span>
          <h2 class="font-outfit font-black text-3xl md:text-4xl text-slate-900" data-i18n="home.latestVideos">Latest Videos</h2>
        </div>
        <a href="/videos" class="hidden md:inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[.11em] hover:gap-2.5 transition-all" style="color:#750B25">
          <span data-i18n="home.allVideos">All Videos</span>
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <?php foreach ($latestVideos as $vid): ?>
          <a href="/videos/<?= h($vid['id']) ?>" class="group flex flex-col bg-white rounded-2xl overflow-hidden shadow-sm border border-slate-100 hover:shadow-md hover:-translate-y-1 transition-all duration-300">
            <!-- Thumbnail -->
            <div class="relative h-48 shrink-0 overflow-hidden" style="background:#0D0102">
              <?php if (!empty($vid['thumbnailUrl'])): ?>
                <img src="<?= h($vid['thumbnailUrl']) ?>" alt="<?= h($vid['title']) ?>"
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                     onerror="this.style.display='none'">
              <?php endif; ?>
              <div class="absolute inset-0 flex items-center justify-center" style="background:rgba(0,0,0,.38)">
                <div class="w-14 h-14 rounded-full flex items-center justify-center border-2 border-white/50 group-hover:scale-110 transition-transform duration-300" style="background:rgba(117,11,37,.85)">
                  <svg width="20" height="20" fill="white" viewBox="0 0 24 24" style="margin-left:3px"><path d="M8 5v14l11-7z"/></svg>
                </div>
              </div>
              <?php if (!empty($vid['country'])): ?>
                <div class="absolute top-4 left-4">
                  <span class="text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full text-slate-900" style="background:#E7952A">
                    <?= h($vid['country']) ?>
                  </span>
                </div>
              <?php endif; ?>
              <?php if (($vid['viewCount'] ?? 0) > 0): ?>
                <div class="absolute bottom-3 right-3 flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-bold text-white" style="background:rgba(0,0,0,.6)">
                  <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                  <?= format_number((int)$vid['viewCount']) ?>
                </div>
              <?php endif; ?>
            </div>
            <!-- Info -->
            <div class="flex flex-col grow p-6">
              <?php if (!empty($vid['issueCategory'])): ?>
                <span class="text-[9px] font-black uppercase tracking-widest mb-2 block" style="color:#64748b"><?= h($vid['issueCategory']) ?></span>
              <?php endif; ?>
              <h3 class="font-outfit font-bold text-[1.05rem] text-slate-900 leading-snug mb-3 line-clamp-2 group-hover:text-primary transition-colors">
                <?= h($vid['title']) ?>
              </h3>
              <?php if (!empty($vid['description'])): ?>
                <p class="text-slate-500 text-sm leading-relaxed line-clamp-2 mb-4 grow"><?= h($vid['description']) ?></p>
              <?php endif; ?>
              <div class="flex items-center pt-4 mt-auto border-t border-slate-100">
                <span class="text-[11px] text-slate-400"><?= format_date($vid['createdAt']) ?></span>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
      <div class="mt-10 md:hidden text-center">
        <a href="/videos" class="btn-gold" data-i18n="home.allVideos">All Videos</a>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ══════════════════════════════════════════
       5d. LATEST DOCUMENTS (conditional)
  ══════════════════════════════════════════ -->
  <?php if ($showDocuments && !empty($latestDocuments)): ?>
  <section class="border-t border-slate-100 py-24 px-6" style="background:#F8F8F0">
    <div class="max-w-7xl mx-auto">
      <div class="flex items-end justify-between mb-14">
        <div>
          <span class="eyebrow-left" data-i18n="home.researchLibrary">Research Library</span>
          <h2 class="font-outfit font-black text-3xl md:text-4xl text-slate-900" data-i18n="home.recentDocuments">Recent Documents</h2>
        </div>
        <a href="/documents" class="hidden md:inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[.11em] hover:gap-2.5 transition-all" style="color:#750B25">
          <span data-i18n="home.fullLibrary">Full Library</span>
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($latestDocuments as $doc):
          $ext   = strtolower(pathinfo(parse_url((string)($doc['mediaUrl'] ?? ''), PHP_URL_PATH), PATHINFO_EXTENSION));
          $ftype = match(true) { $ext==='pdf'=>'PDF', in_array($ext,['doc','docx'])=>'DOC', in_array($ext,['xls','xlsx'])=>'XLS', in_array($ext,['ppt','pptx'])=>'PPT', default=>'DOC' };
          $fcol  = match($ftype) { 'PDF'=>'#dc2626', 'XLS'=>'#16a34a', 'PPT'=>'#ea580c', default=>'#2563eb' };
        ?>
          <a href="/documents/<?= h($doc['id']) ?>" class="group flex items-start gap-5 bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-md hover:-translate-y-1 transition-all duration-300">
            <!-- File icon -->
            <div class="w-14 h-16 rounded-xl flex flex-col items-center justify-center shrink-0 shadow-sm"
                 style="background:<?= $fcol ?>15;border:1px solid <?= $fcol ?>30">
              <svg width="18" height="18" fill="none" stroke="<?= $fcol ?>" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
              </svg>
              <span class="text-[9px] font-black mt-1" style="color:<?= $fcol ?>"><?= $ftype ?></span>
            </div>
            <!-- Info -->
            <div class="flex-1 min-w-0">
              <?php if (!empty($doc['issueCategory'])): ?>
                <span class="text-[9px] font-black uppercase tracking-widest mb-1.5 block" style="color:#64748b"><?= h($doc['issueCategory']) ?></span>
              <?php endif; ?>
              <h3 class="font-outfit font-bold text-[0.95rem] text-slate-900 leading-snug mb-2 line-clamp-2 group-hover:text-primary transition-colors">
                <?= h($doc['title']) ?>
              </h3>
              <?php if (!empty($doc['description'])): ?>
                <p class="text-slate-500 text-[12px] leading-relaxed line-clamp-2 mb-3"><?= h($doc['description']) ?></p>
              <?php endif; ?>
              <div class="flex items-center gap-3 flex-wrap">
                <span class="text-[10px] text-slate-400"><?= format_date($doc['createdAt']) ?></span>
                <?php if (!empty($doc['country'])): ?>
                  <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full text-slate-900" style="background:#E7952A"><?= h($doc['country']) ?></span>
                <?php endif; ?>
                <?php if (($doc['downloadCount'] ?? 0) > 0): ?>
                  <span class="flex items-center gap-1 text-[10px] text-slate-400">
                    <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <?= number_format((int)$doc['downloadCount']) ?>
                  </span>
                <?php endif; ?>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
      <div class="mt-10 md:hidden text-center">
        <a href="/documents" class="btn-gold" data-i18n="home.fullDocumentLibrary">Full Document Library</a>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ══════════════════════════════════════════
       6. TARGET GEOGRAPHIES
  ══════════════════════════════════════════ -->
  <section class="py-24 px-6 border-t border-slate-100">
    <div class="max-w-7xl mx-auto">

      <div class="text-center mb-16">
        <span class="eyebrow-center" data-i18n="coverageArea">Coverage Area</span>
        <h2 class="font-outfit font-black text-3xl md:text-4xl text-slate-900" data-i18n="home.targetGeographies">Our Target Geographies</h2>
        <p class="text-slate-500 mt-4 max-w-lg mx-auto text-[15px] leading-relaxed" data-i18n="home.targetGeographiesDesc">
          Three strategically selected nations spanning East and Central Africa, covering some of the continent&rsquo;s most dynamic political and social landscapes.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <?php
        $geos = [
          [
            'img'    => '/public/nairobi_city.png',
            'flag'   => '/public/kenya_flag.png',
            'name'   => 'Kenya', 'nameKey' => 'countryKenya',
            'region' => 'East Africa', 'regionKey' => 'eastAfricaGeo',
            'units'  => '47 Counties', 'unitsKey' => 'kenyaUnits',
            'cities' => 'Nairobi &middot; Mombasa &middot; Kisumu',
            'desc'   => 'County-level tracking across governance, health, environment, and security sectors throughout Kenya.', 'descKey' => 'kenyaGeoDesc',
          ],
          [
            'img'    => '/public/addis_ababa_city.png',
            'flag'   => '/public/ethiopia_flag.png',
            'name'   => 'Ethiopia', 'nameKey' => 'countryEthiopia',
            'region' => 'Horn of Africa', 'regionKey' => 'hornOfAfricaGeo',
            'units'  => '11 Regional States', 'unitsKey' => 'ethiopiaUnits',
            'cities' => 'Addis Ababa &middot; Dire Dawa',
            'desc'   => 'Field reports and research briefs documenting Ethiopia\'s complex regional dynamics and development landscape.', 'descKey' => 'ethiopiaGeoDesc',
          ],
          [
            'img'    => '/public/kinshasa_city.png',
            'flag'   => '/public/drc_flag.png',
            'name'   => 'DR Congo', 'nameKey' => 'countryDrc',
            'region' => 'Central Africa', 'regionKey' => 'centralAfricaGeo',
            'units'  => '26 Provinces', 'unitsKey' => 'drcUnits',
            'cities' => 'Kinshasa &middot; Lubumbashi &middot; Goma',
            'desc'   => 'Documenting resilience, community narratives, and structural change across the Congo Basin.', 'descKey' => 'drcGeoDesc',
          ],
        ];
        foreach ($geos as $g): ?>
          <div class="geo-card">
            <img src="<?= h($g['img']) ?>" alt="<?= h($g['name']) ?>" class="geo-bg">
            <div class="geo-overlay"></div>

            <!-- Flag -->
            <div class="absolute top-6 right-6 z-10 w-14 h-9 rounded-lg overflow-hidden shadow-xl border-2 border-white/20">
              <img src="<?= h($g['flag']) ?>" alt="<?= h($g['name']) ?> flag" class="w-full h-full object-cover">
            </div>

            <!-- Content -->
            <div class="absolute inset-0 z-10 flex flex-col justify-end p-8">
              <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded border inline-block w-fit mb-3"
                    style="color:#E7952A;border-color:rgba(231,149,42,.4)" data-i18n="home.geo.<?= h($g['regionKey']) ?>"><?= h($g['region']) ?></span>
              <h3 class="font-outfit font-black text-[2.5rem] text-white leading-none mb-1" data-i18n="<?= h($g['nameKey']) ?>"><?= h($g['name']) ?></h3>
              <p class="text-white/50 text-xs mb-1" data-i18n="home.geo.<?= h($g['unitsKey']) ?>"><?= h($g['units']) ?></p>
              <p class="text-white/40 text-[11px] italic mb-5"><?= $g['cities'] ?></p>
              <p class="text-white/75 text-sm leading-relaxed mb-6 max-w-65" data-i18n="home.geo.<?= h($g['descKey']) ?>"><?= h($g['desc']) ?></p>
              <a href="/heatmap"
                 class="inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-widest transition-all hover:gap-3" style="color:#E7952A">
                <span data-i18n="home.exploreRegion">Explore Region</span>
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>


  <!-- ══════════════════════════════════════════
       7. CALL TO ACTION BAND
  ══════════════════════════════════════════ -->
  <section class="py-28 px-6"
           style="background:#750B25">
    <div class="max-w-3xl mx-auto text-center">
      <span class="inline-block text-[9px] font-black uppercase tracking-[.2em] px-3.5 py-1.5 rounded-full mb-8"
            style="background:rgba(231,149,42,.12);color:#E7952A;border:1px solid rgba(231,149,42,.3)" data-i18n="home.joinPlatform">
        Join the Platform
      </span>
      <h2 class="font-outfit font-black text-4xl md:text-[3.25rem] text-white leading-[1.06] mb-6" data-i18n="home.stayInformed">
        Stay Informed.<br>Make an Impact.
      </h2>
      <p class="text-white/60 text-base md:text-lg leading-relaxed mb-12 max-w-xl mx-auto" data-i18n="home.ctaDesc">
        Join researchers, journalists, policy analysts, and community leaders who rely on Tafakari Digital Hub to stay connected with what matters across East and Central Africa.
      </p>
      <div class="flex flex-wrap gap-4 justify-center">
        <a href="/news"    class="btn-secondary" style="padding:.8rem 2rem" data-i18n="home.browseContent">Browse Content</a>
        <a href="/contact" class="btn-ghost-white">
          <span data-i18n="home.getInTouch">Get in Touch</span>
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
      </div>
    </div>
  </section>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
(function () {
  var hero    = document.getElementById('hero');
  var slides  = document.querySelectorAll('.hero-slide');
  var dotBars = document.querySelectorAll('.hero-dot-bar');
  var dotsM   = document.querySelectorAll('.hero-dot-m');
  var numEl   = document.getElementById('hero-cur');
  var cur     = 0;
  var total   = slides.length;
  var timer   = null;

  if (!total) return;

  function pad(n) { return n < 10 ? '0' + n : '' + n; }

  function goTo(n) {
    var next = ((n % total) + total) % total;
    if (next === cur) return;

    slides[cur].classList.remove('is-active');
    if (dotBars[cur]) {
      dotBars[cur].style.width      = '1rem';
      dotBars[cur].style.background = 'rgba(255,255,255,.35)';
    }
    if (dotsM[cur]) dotsM[cur].classList.remove('is-on');

    cur = next;

    slides[cur].classList.add('is-active');
    if (dotBars[cur]) {
      dotBars[cur].style.width      = '2rem';
      dotBars[cur].style.background = '#E7952A';
    }
    if (dotsM[cur]) dotsM[cur].classList.add('is-on');
    if (numEl) numEl.textContent = pad(cur + 1);
  }

  function start() { stop(); if (total > 1) timer = setInterval(function () { goTo(cur + 1); }, 7000); }
  function stop()  { if (timer) { clearInterval(timer); timer = null; } }

  document.querySelectorAll('.carousel-dot').forEach(function (btn) {
    btn.addEventListener('click', function () { goTo(+this.dataset.index); start(); });
  });

  var prev = document.getElementById('hero-prev');
  var next = document.getElementById('hero-next');
  if (prev) prev.addEventListener('click', function () { goTo(cur - 1); start(); });
  if (next) next.addEventListener('click', function () { goTo(cur + 1); start(); });

  // Keyboard arrows
  document.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowLeft')  { goTo(cur - 1); start(); }
    if (e.key === 'ArrowRight') { goTo(cur + 1); start(); }
  });

  // Touch swipe
  var tx = null;
  if (hero) {
    hero.addEventListener('touchstart', function (e) { tx = e.changedTouches[0].clientX; }, { passive: true });
    hero.addEventListener('touchend', function (e) {
      if (tx === null) return;
      var dx = e.changedTouches[0].clientX - tx;
      if (Math.abs(dx) > 45) { goTo(dx < 0 ? cur + 1 : cur - 1); start(); }
      tx = null;
    }, { passive: true });
    // Pause while hovering, and when the tab is hidden
    hero.addEventListener('mouseenter', stop);
    hero.addEventListener('mouseleave', start);
  }
  document.addEventListener('visibilitychange', function () {
    document.hidden ? stop() : start();
  });

  start();
})();
</script>
</body>
</html>
