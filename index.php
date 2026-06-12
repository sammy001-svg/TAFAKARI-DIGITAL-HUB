<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Tafakari Digital Hub | Knowledge, Media & Community Platform for East & Central Africa';
$pageDesc  = 'A centralized knowledge repository, media broadcasting center, and community engagement hub serving Kenya, Ethiopia, and the Democratic Republic of Congo.';

$latestArticles = [];
try {
    $stmt = db()->query("
        SELECT id, title, description, thumbnailUrl, country, issueCategory, viewCount, createdAt
        FROM Post WHERE type='ARTICLE' AND status='PUBLISHED'
        ORDER BY createdAt DESC LIMIT 3
    ");
    $latestArticles = $stmt->fetchAll();
} catch (Exception $e) {}

$publishedCount = 0;
$totalViews     = 0;
try {
    $row = db()->query("SELECT COUNT(*) AS c, COALESCE(SUM(viewCount),0) AS v FROM Post WHERE status='PUBLISHED'")->fetch();
    $publishedCount = (int)$row['c'];
    $totalViews     = (int)$row['v'];
} catch (Exception $e) {}
?>
<?php include __DIR__ . '/includes/head.php'; ?>

<style>
  body { overflow-x: hidden; }

  /* Hero */
  .hero-slide {
    position: absolute; inset: 0;
    transition: opacity .85s cubic-bezier(.4,0,.2,1);
  }

  /* Stats bar */
  .stat-item:not(:last-child) { border-right: 1px solid rgba(255,255,255,.09); }

  /* Pillar cards */
  .pillar-card {
    border-left: 4px solid transparent;
    transition: border-color .2s, transform .22s, box-shadow .22s;
  }
  .pillar-card:hover {
    border-left-color: #D99F51;
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
    background: linear-gradient(15deg, rgba(13,2,3,.9) 0%, rgba(13,2,3,.55) 55%, rgba(0,0,0,.15) 100%);
    transition: opacity .3s;
  }
  .geo-card:hover .geo-overlay { opacity: .95; }

  /* Eyebrow labels */
  .eyebrow-left {
    display: inline-flex; align-items: center; gap: 10px;
    font-size: .65rem; font-weight: 800; letter-spacing: .14em;
    text-transform: uppercase; color: #D99F51; margin-bottom: 1rem;
  }
  .eyebrow-left::before {
    content: ''; display: block;
    width: 30px; height: 2px; background: #D99F51; flex-shrink: 0;
  }
  .eyebrow-center {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    font-size: .65rem; font-weight: 800; letter-spacing: .14em;
    text-transform: uppercase; color: #D99F51; margin-bottom: 1rem;
  }
  .eyebrow-center::before,
  .eyebrow-center::after {
    content: ''; display: block;
    width: 28px; height: 2px; background: #D99F51;
  }

  /* Mission drop-cap quote mark */
  .mission-quote { position: relative; padding-top: 1rem; }
  .mission-quote::before {
    content: '\201C';
    position: absolute; top: -2.5rem; left: -1rem;
    font-size: 9rem; line-height: 1; font-family: 'Outfit', sans-serif;
    font-weight: 900; color: #D99F51; opacity: .15;
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
<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="flex-grow flex flex-col">

  <!-- ══════════════════════════════════════════
       1. HERO CAROUSEL
  ══════════════════════════════════════════ -->
  <section class="relative w-full overflow-hidden" style="height:93vh;min-height:580px">

    <?php
    $slides = [
      [
        'img'    => '/public/nairobi_sunset_hero.png',
        'region' => 'Kenya',
        'title'  => 'Tracking What Matters Across All 47 Counties',
        'sub'    => 'Real-time issue mapping and data-driven insights powering better decisions across Kenya.',
        'badge'  => '47 Counties Covered',
      ],
      [
        'img'    => '/public/ethiopia_community_hero.png',
        'region' => 'Ethiopia',
        'title'  => 'Community Knowledge from the Horn of Africa',
        'sub'    => 'Field reports, research briefs and oral histories from 11 regional states documented for public access.',
        'badge'  => '11 Regional States',
      ],
      [
        'img'    => '/public/drc_nature_hero.png',
        'region' => 'DR Congo',
        'title'  => 'Amplifying Voices from the Congo Basin',
        'sub'    => 'Documenting resilience, community narratives, and structural change across 26 provinces.',
        'badge'  => '26 Provinces',
      ],
    ];
    foreach ($slides as $i => $s): ?>
      <div class="hero-slide <?= $i === 0 ? 'opacity-100' : 'opacity-0' ?>" data-index="<?= $i ?>">
        <img src="<?= h($s['img']) ?>" alt="<?= h($s['title']) ?>"
             class="w-full h-full object-cover" style="filter:brightness(.7) saturate(1.1)">
        <div class="absolute inset-0"
             style="background:linear-gradient(110deg,rgba(5,0,1,.85) 0%,rgba(5,0,1,.5) 55%,rgba(0,0,0,.12) 100%)"></div>

        <div class="absolute inset-0 flex flex-col justify-center px-8 md:px-24 pb-20">

          <!-- Region eyebrow -->
          <div class="flex items-center gap-3 mb-6">
            <span class="block w-10 h-px" style="background:#D99F51"></span>
            <span class="text-[11px] font-black uppercase tracking-[.22em]" style="color:#D99F51"><?= h($s['region']) ?></span>
          </div>

          <!-- Headline -->
          <h1 class="font-outfit font-black text-4xl sm:text-5xl md:text-6xl text-white leading-[1.04] max-w-3xl mb-6">
            <?= h($s['title']) ?>
          </h1>

          <!-- Sub-headline -->
          <p class="text-white/70 text-base md:text-lg max-w-xl mb-10 leading-relaxed">
            <?= h($s['sub']) ?>
          </p>

          <!-- CTAs -->
          <div class="flex flex-wrap items-center gap-5">
            <a href="/heatmap" class="btn-gold" style="padding:.75rem 1.75rem">Explore Heatmap</a>
            <a href="/about" class="inline-flex items-center gap-2 text-sm font-bold text-white/75 hover:text-white transition-colors">
              Our Mission
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
          </div>

          <!-- Coverage badge -->
          <div class="mt-10">
            <span class="inline-block text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full border"
                  style="color:#D99F51;border-color:rgba(217,159,81,.45);background:rgba(217,159,81,.07)">
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
                style="width:<?= $i === 0 ? '2rem' : '1rem' ?>;background:<?= $i === 0 ? '#D99F51' : 'rgba(255,255,255,.35)' ?>"></span>
        </button>
      <?php endforeach; ?>
    </div>

    <!-- Slide counter (bottom-left) -->
    <div class="absolute bottom-10 left-8 md:left-24 z-20 flex items-center gap-4">
      <span id="hero-cur" class="font-outfit font-black text-3xl" style="color:#D99F51">01</span>
      <div class="h-px w-10 bg-white/25"></div>
      <span class="text-white/40 text-sm font-semibold">0<?= count($slides) ?></span>
    </div>

    <!-- Scroll indicator (bottom-center) -->
    <div class="scroll-bounce absolute bottom-8 z-20 flex flex-col items-center gap-1.5" style="left:50%;transform:translateX(-50%)">
      <span class="text-[9px] font-black uppercase tracking-[.2em] text-white/35">Scroll</span>
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.35)" stroke-width="2.5">
        <path d="M12 5v14M5 12l7 7 7-7"/>
      </svg>
    </div>
  </section>


  <!-- ══════════════════════════════════════════
       2. IMPACT STATISTICS
  ══════════════════════════════════════════ -->
  <section style="background:#0C0203" class="py-14 px-6">
    <div class="max-w-7xl mx-auto grid grid-cols-2 md:grid-cols-4">
      <?php
      $impactStats = [
        ['num' => '3',                                                        'suf' => '',  'label' => 'Countries'],
        ['num' => '84',                                                       'suf' => '+', 'label' => 'Administrative Units'],
        ['num' => $publishedCount > 0 ? (string)$publishedCount : '50',      'suf' => '+', 'label' => 'Published Resources'],
        ['num' => $totalViews > 0     ? format_number($totalViews) : '10K',  'suf' => '+', 'label' => 'Community Engagements'],
      ];
      foreach ($impactStats as $j => $st): ?>
        <div class="stat-item text-center py-10 px-4">
          <div class="font-outfit font-black text-4xl md:text-5xl leading-none mb-2.5">
            <span style="color:#D99F51"><?= h($st['num']) ?></span><span style="color:#EAD2AC"><?= h($st['suf']) ?></span>
          </div>
          <div class="text-white/45 text-[11px] font-semibold uppercase tracking-widest"><?= h($st['label']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>


  <!-- ══════════════════════════════════════════
       3. MISSION STATEMENT
  ══════════════════════════════════════════ -->
  <section class="max-w-7xl mx-auto px-6 md:px-12 py-28 grid grid-cols-1 md:grid-cols-2 gap-16 md:gap-28 items-center">

    <div>
      <div class="mission-quote">
        <p class="font-outfit font-black text-3xl md:text-[2.6rem] text-slate-900 leading-snug">
          Connecting communities through knowledge, media, and informed dialogue across East and Central Africa.
        </p>
      </div>
    </div>

    <div>
      <span class="eyebrow-left">Our Purpose</span>
      <p class="text-slate-500 text-[15px] leading-[1.85] mb-5">
        Tafakari Digital Hub is a multi-user digital platform that bridges the information gap across Kenya, Ethiopia, and the Democratic Republic of Congo. We aggregate field research, amplify grassroots narratives, and make critical data accessible to decision-makers, journalists, and communities.
      </p>
      <p class="text-slate-500 text-[15px] leading-[1.85] mb-10">
        From interactive regional heatmaps to curated document libraries and community dialogue boards &mdash; every tool on this platform is designed to inform, connect, and empower.
      </p>
      <a href="/about" class="btn-gold" style="padding:.75rem 1.75rem">Discover Our Mission</a>
    </div>
  </section>


  <!-- ══════════════════════════════════════════
       4. WHAT WE DO — FOUR PILLARS
  ══════════════════════════════════════════ -->
  <section class="bg-slate-50 border-y border-slate-100 py-24 px-6">
    <div class="max-w-7xl mx-auto">

      <div class="text-center mb-16">
        <span class="eyebrow-center">What We Do</span>
        <h2 class="font-outfit font-black text-3xl md:text-4xl text-slate-900">Four Pillars of Impact</h2>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php
        $pillars = [
          [
            'svg'  => '<svg width="26" height="26" fill="none" stroke="#D99F51" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M15 10l4.553-2.069A1 1 0 0121 8.87v6.26a1 1 0 01-1.447.9L15 14M3 8a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/></svg>',
            'title' => 'Media Broadcasting',
            'desc'  => 'Photo galleries, podcasts, and video libraries documenting field activities and regional oral histories across three nations.',
            'href'  => '/gallery',
            'cta'   => 'View Gallery',
          ],
          [
            'svg'  => '<svg width="26" height="26" fill="none" stroke="#D99F51" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>',
            'title' => 'Knowledge Repository',
            'desc'  => 'A structured library of research reports, policy briefs, and actionable datasets made freely accessible for public and academic use.',
            'href'  => '/documents',
            'cta'   => 'Browse Documents',
          ],
          [
            'svg'  => '<svg width="26" height="26" fill="none" stroke="#D99F51" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>',
            'title' => 'Community Engagement',
            'desc'  => 'Moderated public comment systems and contact infrastructure ensuring safe, constructive, and inclusive community dialogue.',
            'href'  => '/contact',
            'cta'   => 'Get Involved',
          ],
          [
            'svg'  => '<svg width="26" height="26" fill="none" stroke="#D99F51" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z"/></svg>',
            'title' => 'Interactive Data Maps',
            'desc'  => 'Visual heatmaps displaying issue intensity by geography and category, enabling pattern recognition across regions and time periods.',
            'href'  => '/heatmap',
            'cta'   => 'Open Heatmap',
          ],
        ];
        foreach ($pillars as $p): ?>
          <div class="pillar-card bg-white rounded-2xl p-8 shadow-sm border border-slate-100">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-6" style="background:rgba(217,159,81,.10)">
              <?= $p['svg'] ?>
            </div>
            <h3 class="font-outfit font-bold text-[1.1rem] text-slate-900 mb-3 leading-snug"><?= h($p['title']) ?></h3>
            <p class="text-slate-500 text-sm leading-relaxed mb-6"><?= h($p['desc']) ?></p>
            <a href="<?= h($p['href']) ?>"
               class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest transition-all hover:gap-2.5" style="color:#B07D2A">
              <?= h($p['cta']) ?>
              <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>


  <!-- ══════════════════════════════════════════
       5. LATEST ARTICLES (conditional)
  ══════════════════════════════════════════ -->
  <?php if (!empty($latestArticles)): ?>
  <section class="max-w-7xl mx-auto px-6 py-24">
    <div class="flex items-end justify-between mb-14">
      <div>
        <span class="eyebrow-left">Latest from the Region</span>
        <h2 class="font-outfit font-black text-3xl md:text-4xl text-slate-900">Recent Articles</h2>
      </div>
      <a href="/news" class="hidden md:inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest hover:gap-2.5 transition-all" style="color:#B07D2A">
        View All Articles
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
      </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      <?php foreach ($latestArticles as $art): ?>
        <a href="/news/<?= h($art['id']) ?>"
           class="article-card flex flex-col bg-white rounded-2xl overflow-hidden shadow-sm border border-slate-100 group">

          <div class="h-52 relative overflow-hidden flex-shrink-0">
            <?php if (!empty($art['thumbnailUrl'])): ?>
              <img src="<?= h($art['thumbnailUrl']) ?>" alt="<?= h($art['title']) ?>"
                   class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
            <?php else: ?>
              <div class="w-full h-full flex items-center justify-center"
                   style="background:linear-gradient(135deg,#1a1200 0%,#7a5718 100%)">
                <svg width="42" height="42" fill="none" stroke="rgba(255,255,255,.25)" stroke-width="1.4" viewBox="0 0 24 24">
                  <path d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                </svg>
              </div>
            <?php endif; ?>
            <div class="absolute top-4 left-4">
              <span class="text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full text-slate-900" style="background:#D99F51">
                <?= h($art['country']) ?>
              </span>
            </div>
          </div>

          <div class="flex flex-col flex-grow p-6">
            <span class="text-[9px] font-black uppercase tracking-widest mb-2 block" style="color:#D99F51">
              <?= h($art['issueCategory']) ?>
            </span>
            <h3 class="font-outfit font-bold text-[1.05rem] text-slate-900 leading-snug mb-3 line-clamp-2 group-hover:text-primary transition-colors">
              <?= h($art['title']) ?>
            </h3>
            <?php if (!empty($art['description'])): ?>
              <p class="text-slate-500 text-sm leading-relaxed line-clamp-3 mb-4 flex-grow">
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
       6. TARGET GEOGRAPHIES
  ══════════════════════════════════════════ -->
  <section class="py-24 px-6 border-t border-slate-100">
    <div class="max-w-7xl mx-auto">

      <div class="text-center mb-16">
        <span class="eyebrow-center">Coverage Area</span>
        <h2 class="font-outfit font-black text-3xl md:text-4xl text-slate-900">Our Target Geographies</h2>
        <p class="text-slate-500 mt-4 max-w-lg mx-auto text-[15px] leading-relaxed">
          Three strategically selected nations spanning East and Central Africa, covering some of the continent&rsquo;s most dynamic political and social landscapes.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <?php
        $geos = [
          [
            'img'    => '/public/nairobi_city.png',
            'flag'   => '/public/kenya_flag.png',
            'name'   => 'Kenya',
            'region' => 'East Africa',
            'units'  => '47 Counties',
            'cities' => 'Nairobi &middot; Mombasa &middot; Kisumu',
            'desc'   => 'County-level tracking across governance, health, environment, and security sectors throughout Kenya.',
          ],
          [
            'img'    => '/public/addis_ababa_city.png',
            'flag'   => '/public/ethiopia_flag.png',
            'name'   => 'Ethiopia',
            'region' => 'Horn of Africa',
            'units'  => '11 Regional States',
            'cities' => 'Addis Ababa &middot; Dire Dawa',
            'desc'   => 'Field reports and research briefs documenting Ethiopia\'s complex regional dynamics and development landscape.',
          ],
          [
            'img'    => '/public/kinshasa_city.png',
            'flag'   => '/public/drc_flag.png',
            'name'   => 'DR Congo',
            'region' => 'Central Africa',
            'units'  => '26 Provinces',
            'cities' => 'Kinshasa &middot; Lubumbashi &middot; Goma',
            'desc'   => 'Documenting resilience, community narratives, and structural change across the Congo Basin.',
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
                    style="color:#D99F51;border-color:rgba(217,159,81,.4)"><?= h($g['region']) ?></span>
              <h3 class="font-outfit font-black text-[2.5rem] text-white leading-none mb-1"><?= h($g['name']) ?></h3>
              <p class="text-white/50 text-xs mb-1"><?= h($g['units']) ?></p>
              <p class="text-white/40 text-[11px] italic mb-5"><?= $g['cities'] ?></p>
              <p class="text-white/75 text-sm leading-relaxed mb-6 max-w-[260px]"><?= h($g['desc']) ?></p>
              <a href="/heatmap"
                 class="inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-widest transition-all hover:gap-3" style="color:#D99F51">
                Explore Region
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
           style="background:linear-gradient(118deg,#100102 0%,#2d0405 40%,#9A1415 100%)">
    <div class="max-w-3xl mx-auto text-center">
      <span class="inline-block text-[9px] font-black uppercase tracking-[.2em] px-3.5 py-1.5 rounded-full mb-8"
            style="background:rgba(217,159,81,.12);color:#D99F51;border:1px solid rgba(217,159,81,.3)">
        Join the Platform
      </span>
      <h2 class="font-outfit font-black text-4xl md:text-[3.25rem] text-white leading-[1.06] mb-6">
        Stay Informed.<br>Make an Impact.
      </h2>
      <p class="text-white/60 text-base md:text-lg leading-relaxed mb-12 max-w-xl mx-auto">
        Join researchers, journalists, policy analysts, and community leaders who rely on Tafakari Digital Hub to stay connected with what matters across East and Central Africa.
      </p>
      <div class="flex flex-wrap gap-4 justify-center">
        <a href="/news"    class="btn-secondary" style="padding:.8rem 2rem">Browse Content</a>
        <a href="/contact" class="btn-ghost-white">
          Get in Touch
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
      </div>
    </div>
  </section>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
(function () {
  var slides  = document.querySelectorAll('.hero-slide');
  var dotBars = document.querySelectorAll('.hero-dot-bar');
  var numEl   = document.getElementById('hero-cur');
  var cur     = 0;
  var total   = slides.length;

  function pad(n) { return n < 10 ? '0' + n : '' + n; }

  function goTo(n) {
    slides[cur].classList.remove('opacity-100');
    slides[cur].classList.add('opacity-0');
    if (dotBars[cur]) {
      dotBars[cur].style.width      = '1rem';
      dotBars[cur].style.background = 'rgba(255,255,255,.35)';
    }

    cur = ((n % total) + total) % total;

    slides[cur].classList.remove('opacity-0');
    slides[cur].classList.add('opacity-100');
    if (dotBars[cur]) {
      dotBars[cur].style.width      = '2rem';
      dotBars[cur].style.background = '#D99F51';
    }
    if (numEl) numEl.textContent = pad(cur + 1);
  }

  document.querySelectorAll('.carousel-dot').forEach(function (btn) {
    btn.addEventListener('click', function () { goTo(+this.dataset.index); });
  });

  setInterval(function () { goTo(cur + 1); }, 7000);
})();
</script>
</body>
</html>
