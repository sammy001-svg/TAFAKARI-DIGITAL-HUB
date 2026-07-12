<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// ── Pull live impact stats from DB ─────────────────────────────────────────────
$stats = ['articles' => 0, 'documents' => 0, 'podcasts' => 0, 'videos' => 0, 'images' => 0, 'countries' => 0];
try {
    $row = db()->query("
        SELECT
            COUNT(CASE WHEN type='ARTICLE'       AND status='PUBLISHED' THEN 1 END) AS articles,
            COUNT(CASE WHEN type='DOCUMENT'      AND status='PUBLISHED' THEN 1 END) AS documents,
            COUNT(CASE WHEN type='PODCAST'       AND status='PUBLISHED' THEN 1 END) AS podcasts,
            COUNT(CASE WHEN type='VIDEO'         AND status='PUBLISHED' THEN 1 END) AS videos,
            COUNT(CASE WHEN type='GALLERY_IMAGE' AND status='PUBLISHED' THEN 1 END) AS images,
            COUNT(DISTINCT CASE WHEN status='PUBLISHED' AND country IS NOT NULL AND country != '' THEN country END) AS countries
        FROM Post
    ")->fetch();
    if ($row) $stats = array_merge($stats, $row);
} catch (Exception $e) { /* DB not ready */ }

// ── Live partners list (falls back to default content below if empty) ──────────
$dbPartners = [];
try {
    ensure_partners_table();
    $dbPartners = db()->query(
        "SELECT * FROM Partner WHERE isActive = 1 ORDER BY sortOrder ASC, createdAt ASC"
    )->fetchAll();
} catch (Exception $e) { /* DB not ready */ }

$pageTitle    = 'About CRTP | Tafakari Digital Hub';
$pageDesc     = 'The Centre for Research, Training and Policy (CRTP) is a research and capacity-building organization committed to peace, security, and governance across Africa.';
$pageKeywords = 'CRTP, Centre for Research Training Policy, Africa peace research, conflict analysis, Hekima, Kenya, Ethiopia, DR Congo';
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex flex-col font-inter" style="background:#F8F8F0">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- HERO                                                                      -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div style="background:#0D0102" class="py-20 px-6">
  <div class="max-w-7xl mx-auto">
    <div class="max-w-3xl">
      <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-6 text-white" style="background:#750B25" data-i18n="about.badge">About CRTP</span>
      <h1 class="font-outfit text-5xl md:text-6xl font-black text-white leading-tight mb-6">
        <span data-i18n="about.heroTitle1">Reflecting on Our</span><br>
        <span style="color:#E7952A" data-i18n="about.heroTitle2">Shared Future</span>
      </h1>
      <p class="text-white/70 text-lg leading-relaxed max-w-2xl mb-8" data-i18n="about.heroDesc">
        The Centre for Research, Training and Policy (CRTP) is an independent research and capacity-building
        organization committed to advancing peace, security, and good governance across Africa.
        <em class="text-white/50">Tafakari</em> — meaning "to reflect" in Swahili — is our digital platform
        connecting research, media, and community.
      </p>
      <div class="flex flex-wrap gap-4">
        <a href="/contact" class="px-7 py-3.5 rounded-2xl font-bold text-white transition-all hover:brightness-110" style="background:#750B25" data-i18n="about.partnerWithUs">
          Partner With Us
        </a>
        <a href="/heatmap" class="px-7 py-3.5 rounded-2xl font-bold text-white transition-all border hover:bg-white/10" style="border-color:rgba(255,255,255,.2)" data-i18n="about.viewHeatmap">
          View Conflict Heatmap
        </a>
      </div>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- LIVE IMPACT STATISTICS                                                    -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div class="max-w-7xl mx-auto px-6 pt-12 mb-16">
  <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
    <?php
    $impactStats = [
        ['value' => max($stats['articles'], 0),  'label' => 'Articles',           'key' => 'about.stat.articles',   'href' => '/news',      'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z'],
        ['value' => max($stats['documents'], 0), 'label' => 'Research Docs',       'key' => 'about.stat.documents',  'href' => '/documents', 'icon' => 'M7 21h10a2 2 0 0 0 2-2V9.414a1 1 0 0 0-.293-.707l-5.414-5.414A1 1 0 0 0 12.586 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2z'],
        ['value' => max($stats['podcasts'], 0),  'label' => 'Podcast Episodes',    'key' => 'about.stat.podcasts',   'href' => '/podcasts',  'icon' => 'M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3'],
        ['value' => max($stats['videos'], 0),    'label' => 'Video Reports',        'key' => 'about.stat.videos',    'href' => '/videos',    'icon' => 'M15 10l4.553-2.069A1 1 0 0 1 21 8.82v6.36a1 1 0 0 1-1.447.894L15 14M5 18h8a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2z'],
        ['value' => max($stats['images'], 0),    'label' => 'Gallery Photos',       'key' => 'about.stat.images',    'href' => '/gallery',   'icon' => 'M4 16l4.586-4.586a2 2 0 0 1 2.828 0L16 16m-2-2 1.586-1.586a2 2 0 0 1 2.828 0L20 14m-6-6h.01M6 20h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z'],
        ['value' => max((int)$stats['countries'], 15), 'label' => 'Countries Monitored', 'key' => 'about.stat.countries', 'suffix' => '+', 'href' => '#coverage', 'icon' => 'M3.055 11H5a2 2 0 0 1 2 2v1a2 2 0 0 0 2 2 2 2 0 0 1 2 2v2.945M8 3.935V5.5A2.5 2.5 0 0 0 10.5 8h.5a2 2 0 0 1 2 2 2 2 0 1 0 4 0 2 2 0 0 1 2-2h1.064M15 20.488V18a2 2 0 0 1 2-2h3.064'],
    ];
    ?>
    <?php foreach ($impactStats as $s): ?>
      <a href="<?= h($s['href']) ?>" class="block bg-white rounded-2xl border border-amber-100 shadow-sm p-5 text-center hover:shadow-md hover:border-amber-200 hover:-translate-y-0.5 transition-all">
        <div class="w-10 h-10 rounded-xl mx-auto mb-3 flex items-center justify-center" style="background:#F8F8F0">
          <svg width="20" height="20" fill="none" stroke="#C47C1A" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <path d="<?= $s['icon'] ?>"/>
          </svg>
        </div>
        <div class="font-outfit font-black text-2xl text-slate-900">
          <?= $s['value'] > 0 ? format_number((int)$s['value']) : '—' ?><?= $s['suffix'] ?? '' ?>
        </div>
        <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-1" data-i18n="<?= h($s['key']) ?>"><?= h($s['label']) ?></div>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- PURPOSE                                                                   -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div id="purpose" class="max-w-7xl mx-auto px-6 mb-20 scroll-mt-24">
  <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-3 text-amber-900" style="background:#E7952A" data-i18n="about.purposeBadge">Our Purpose</span>
  <h2 class="font-outfit font-black text-4xl text-slate-900 mb-4" data-i18n="about.purposeTitle">Why CRTP Exists</h2>
  <p class="text-slate-600 leading-relaxed text-lg max-w-3xl" data-i18n="about.purposeDesc">
    CRTP exists to give African institutions, communities, and leaders a trustworthy source of
    rigorous research and practical capacity-building support — so that decisions on peace,
    security, and governance are grounded in evidence, not guesswork.
  </p>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- MISSION & VISION                                                          -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div id="mission" class="max-w-7xl mx-auto px-6 mb-20 scroll-mt-24">
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Mission -->
    <div class="bg-white rounded-3xl border border-amber-100 p-10 shadow-sm relative overflow-hidden">
      <div class="absolute top-0 right-0 w-40 h-40 rounded-full -translate-y-1/2 translate-x-1/2 pointer-events-none" style="background:rgba(231,149,42,.06)"></div>
      <span class="inline-block w-10 h-1 rounded-full mb-5" style="background:#E7952A"></span>
      <h2 class="font-outfit font-black text-3xl text-slate-900 mb-4" data-i18n="about.missionTitle">Our Mission</h2>
      <p class="text-slate-600 leading-relaxed text-lg" data-i18n="about.missionDesc">
        To generate rigorous, policy-relevant research and provide capacity-building support
        that empowers institutions, communities, and leaders to build sustainable peace
        and advance human security across Africa.
      </p>
    </div>
    <!-- Vision -->
    <div class="rounded-3xl border p-10 relative overflow-hidden" style="background:#0D0102;border-color:rgba(231,149,42,.2)">
      <div class="absolute top-0 right-0 w-40 h-40 rounded-full -translate-y-1/2 translate-x-1/2 pointer-events-none" style="background:rgba(117,11,37,.15)"></div>
      <span class="inline-block w-10 h-1 rounded-full mb-5" style="background:#E7952A"></span>
      <h2 class="font-outfit font-black text-3xl text-white mb-4" data-i18n="about.visionTitle">Our Vision</h2>
      <p class="text-white/70 leading-relaxed text-lg" data-i18n="about.visionDesc">
        An Africa where knowledge-driven decision-making, inclusive dialogue, and accountable
        governance create the conditions for lasting peace and equitable development —
        leaving no community behind.
      </p>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- CORE PROGRAMME AREAS                                                      -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div id="what-we-do" class="max-w-7xl mx-auto px-6 mb-20 scroll-mt-24">
  <div class="mb-10">
    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-3 text-amber-900" style="background:#E7952A" data-i18n="about.whatWeDoBadge">What We Do</span>
    <h2 class="font-outfit font-black text-4xl text-slate-900" data-i18n="about.programmeAreasTitle">Core Programme Areas</h2>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <?php
    $programmes = [
      [
        'num' => '01', 'title' => 'Peace & Security Research', 'key' => 'peaceSecurity',
        'desc' => 'Conflict analysis, early-warning systems, and field-based research across active and post-conflict zones. Our studies inform UN peacekeeping operations, NGO interventions, and government policy.',
        'tags' => [['Conflict Analysis', 'conflictAnalysis'], ['Early Warning', 'earlyWarning'], ['Field Research', 'fieldResearch']],
      ],
      [
        'num' => '02', 'title' => 'Governance & Policy', 'key' => 'governancePolicy',
        'desc' => 'Monitoring state fragility, electoral integrity, institutional reform, and anti-corruption measures. We translate complex policy environments into actionable recommendations.',
        'tags' => [['State Fragility', 'stateFragility'], ['Electoral Integrity', 'electoralIntegrity'], ['Policy Briefs', 'policyBriefs']],
      ],
      [
        'num' => '03', 'title' => 'Capacity Building & Training', 'key' => 'capacityBuilding',
        'desc' => 'Structured training programmes for journalists, civil society organizations, government officials, and community leaders on conflict-sensitive reporting, peacebuilding, and advocacy.',
        'tags' => [['Journalism Training', 'journalismTraining'], ['CSO Support', 'csoSupport'], ['Advocacy Skills', 'advocacySkills']],
      ],
      [
        'num' => '04', 'title' => 'Knowledge Management & Media', 'key' => 'knowledgeMedia',
        'desc' => 'The Tafakari platform serves as our digital knowledge hub — aggregating research, broadcasting field stories through podcasts and video, and providing open-access document archives.',
        'tags' => [['Open Access', 'openAccess'], ['Podcast', 'podcastTag'], ['Digital Media', 'digitalMedia']],
      ],
    ];
    ?>
    <?php foreach ($programmes as $p): ?>
      <div class="bg-white rounded-3xl border border-amber-100 p-8 shadow-sm hover:shadow-md hover:border-amber-200 transition-all group">
        <div class="flex items-start justify-between mb-5">
          <span class="font-outfit font-black text-4xl" style="color:rgba(231,149,42,.25)"><?= h($p['num']) ?></span>
          <span class="w-8 h-0.5 mt-5" style="background:#E7952A;display:block"></span>
        </div>
        <h3 class="font-outfit font-bold text-xl text-slate-900 mb-3 group-hover:text-amber-800 transition-colors" data-i18n="about.programme.<?= h($p['key']) ?>.title"><?= h($p['title']) ?></h3>
        <p class="text-slate-500 leading-relaxed text-sm mb-5" data-i18n="about.programme.<?= h($p['key']) ?>.desc"><?= h($p['desc']) ?></p>
        <div class="flex flex-wrap gap-2">
          <?php foreach ($p['tags'] as [$tag, $tagKey]): ?>
            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-amber-50 text-amber-800" data-i18n="about.tag.<?= h($tagKey) ?>"><?= h($tag) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- GEOGRAPHIC FOCUS                                                          -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div style="background:#0D0102" class="py-16 px-6 mb-0">
  <div class="max-w-7xl mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
      <div>
        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-5 text-amber-900" style="background:#E7952A" data-i18n="about.whereWeWorkBadge">Where We Work</span>
        <h2 class="font-outfit font-black text-4xl text-white mb-4" data-i18n="about.geoFocusTitle">Geographic Focus</h2>
        <p class="text-white/60 leading-relaxed mb-8" data-i18n="about.geoFocusDesc">
          Our primary focus spans East Africa and the Great Lakes region, with expanding coverage
          across the Sahel and Horn of Africa — regions experiencing the most complex and
          intersecting conflict dynamics on the continent.
        </p>
        <a href="/heatmap" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-bold text-slate-900 transition-all hover:brightness-110" style="background:#E7952A">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
          <span data-i18n="about.exploreHeatmap">Explore Interactive Heatmap</span>
        </a>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <?php
        $geoFocus = [
          ['region' => 'East Africa', 'regionKey' => 'eastAfrica', 'countries' => 'Kenya · Uganda · Tanzania · Rwanda · Burundi', 'tag' => 'Primary', 'tagKey' => 'primary'],
          ['region' => 'Great Lakes', 'regionKey' => 'greatLakes', 'countries' => 'DR Congo · Rwanda · Burundi · South Sudan', 'tag' => 'Primary', 'tagKey' => 'primary'],
          ['region' => 'Horn of Africa', 'regionKey' => 'hornOfAfrica', 'countries' => 'Ethiopia · Somalia · Eritrea · Djibouti · Sudan', 'tag' => 'Active', 'tagKey' => 'active'],
          ['region' => 'Sahel Region', 'regionKey' => 'sahelRegion', 'countries' => 'Mali · Niger · Burkina Faso · Chad · Nigeria NE', 'tag' => 'Expanding', 'tagKey' => 'expanding'],
          ['region' => 'Southern Africa', 'regionKey' => 'southernAfrica', 'countries' => 'Mozambique · Zimbabwe · Zambia · Madagascar', 'tag' => 'Expanding', 'tagKey' => 'expanding'],
          ['region' => 'Central Africa', 'regionKey' => 'centralAfrica', 'countries' => 'Central African Republic · Cameroon · Congo', 'tag' => 'Monitoring', 'tagKey' => 'monitoring'],
        ];
        foreach ($geoFocus as $g): ?>
          <div class="rounded-2xl p-5 border" style="background:rgba(255,255,255,.04);border-color:rgba(255,255,255,.08)">
            <div class="flex items-center justify-between mb-2">
              <h4 class="font-outfit font-bold text-white text-sm" data-i18n="about.region.<?= h($g['regionKey']) ?>"><?= h($g['region']) ?></h4>
              <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest
                <?= $g['tag'] === 'Primary' ? 'text-amber-900' : ($g['tag'] === 'Active' ? 'text-emerald-800' : 'text-slate-500') ?>"
                style="background:<?= $g['tag'] === 'Primary' ? '#E7952A' : ($g['tag'] === 'Active' ? 'rgba(52,211,153,.15)' : 'rgba(255,255,255,.08)') ?>"
                data-i18n="about.geoTag.<?= h($g['tagKey']) ?>">
                <?= h($g['tag']) ?>
              </span>
            </div>
            <p class="text-white/40 text-xs leading-relaxed"><?= h($g['countries']) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- COVERAGE AREA (matches the interactive heatmap's live data countries)     -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div id="coverage" class="max-w-7xl mx-auto px-6 py-20 scroll-mt-24">
  <div class="text-center max-w-2xl mx-auto mb-12">
    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-3 text-amber-900" style="background:#E7952A" data-i18n="coverageArea">Coverage Area</span>
    <h2 class="font-outfit font-black text-4xl text-slate-900" data-i18n="about.coverageTitle">Countries on the Heatmap</h2>
    <p class="text-slate-500 mt-3" data-i18n="about.coverageDesc">The three countries currently tracked in detail on our interactive conflict heatmap.</p>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <?php
    $coverageCountries = [
      ['id' => 'coverage-kenya',    'flag' => '🇰🇪', 'name' => 'Kenya',    'nameKey' => 'countryKenya',    'desc' => 'Nairobi, Mombasa, Kisumu, and 13 other monitored regions.', 'descKey' => 'about.coverage.kenyaDesc'],
      ['id' => 'coverage-ethiopia', 'flag' => '🇪🇹', 'name' => 'Ethiopia', 'nameKey' => 'countryEthiopia', 'desc' => 'Addis Ababa, Gonder, Bahir Dar, and 13 other monitored regions.', 'descKey' => 'about.coverage.ethiopiaDesc'],
      ['id' => 'coverage-drc',     'flag' => '🇨🇩', 'name' => 'DR Congo', 'nameKey' => 'countryDrc',      'desc' => 'Kinshasa, Goma, Lubumbashi, and 14 other monitored regions.', 'descKey' => 'about.coverage.drcDesc'],
    ];
    foreach ($coverageCountries as $c): ?>
      <div id="<?= h($c['id']) ?>" class="bg-white rounded-2xl border border-amber-100 p-8 shadow-sm text-center scroll-mt-24">
        <div class="text-4xl mb-3"><?= $c['flag'] ?></div>
        <h3 class="font-outfit font-bold text-xl text-slate-900 mb-2" data-i18n="<?= h($c['nameKey']) ?>"><?= h($c['name']) ?></h3>
        <p class="text-slate-500 text-sm leading-relaxed" data-i18n="<?= h($c['descKey']) ?>"><?= h($c['desc']) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="text-center mt-10">
    <a href="/heatmap" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-bold text-white transition-all hover:brightness-110" style="background:#750B25" data-i18n="about.exploreHeatmapArrow">
      Explore Interactive Heatmap →
    </a>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- OUR VALUES                                                                -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div class="max-w-7xl mx-auto px-6 py-20">
  <div class="text-center max-w-2xl mx-auto mb-12">
    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-3 text-amber-900" style="background:#E7952A" data-i18n="about.foundationBadge">Our Foundation</span>
    <h2 class="font-outfit font-black text-4xl text-slate-900" data-i18n="about.valuesTitle">Guiding Values</h2>
  </div>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    <?php
    $values = [
      ['title' => 'Rigour',       'key' => 'rigour',       'desc' => 'Evidence-based methodology underpins all our research. We hold ourselves to the highest academic and professional standards.'],
      ['title' => 'Independence', 'key' => 'independence', 'desc' => 'We operate free from political affiliation or donor bias. Our findings reflect the evidence, not agendas.'],
      ['title' => 'Inclusion',    'key' => 'inclusion',    'desc' => 'Community voices are as vital as academic expertise. We amplify perspectives that are often overlooked in formal policy spaces.'],
      ['title' => 'Impact',       'key' => 'impact',       'desc' => 'Research without action is incomplete. We design every project with measurable policy, community, or behavioural outcomes in mind.'],
    ];
    foreach ($values as $i => $v): ?>
      <div class="bg-white rounded-2xl border border-amber-100 p-7 shadow-sm text-center hover:shadow-md hover:-translate-y-1 transition-all">
        <div class="w-12 h-12 rounded-2xl mx-auto mb-4 flex items-center justify-center font-outfit font-black text-lg text-white" style="background:#750B25">
          <?= ['R', 'I', 'I', 'I'][$i] ?>
        </div>
        <h3 class="font-outfit font-bold text-lg text-slate-900 mb-2" data-i18n="about.value.<?= h($v['key']) ?>.title"><?= h($v['title']) ?></h3>
        <p class="text-slate-500 text-sm leading-relaxed" data-i18n="about.value.<?= h($v['key']) ?>.desc"><?= h($v['desc']) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- OUR TEAM (Placeholder ready for real names/photos)                       -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div class="max-w-7xl mx-auto px-6 mb-20">
  <div class="mb-10">
    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-3 text-amber-900" style="background:#E7952A" data-i18n="about.peopleBadge">The People</span>
    <h2 class="font-outfit font-black text-4xl text-slate-900" data-i18n="about.teamTitle">Our Team</h2>
    <p class="text-slate-500 mt-2" data-i18n="about.teamDesc">Researchers, journalists, and policy specialists committed to the long game.</p>
  </div>
  <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
    <?php
    $team = [
      ['name' => 'Executive Director',          'nameKey' => 'execDirector',  'role' => 'Leadership',       'roleKey' => 'leadership',      'initials' => 'ED', 'photo' => '/public/team/exec-director.jpg'],
      ['name' => 'Head of Research',            'nameKey' => 'headResearch',  'role' => 'Research',         'roleKey' => 'research',        'initials' => 'HR', 'photo' => '/public/team/head-research.jpg'],
      ['name' => 'Policy Analyst — East Africa','nameKey' => 'policyAnalyst', 'role' => 'Policy',           'roleKey' => 'policy',          'initials' => 'PA', 'photo' => '/public/team/policy-analyst.jpg'],
      ['name' => 'Field Coordinator — DRC',     'nameKey' => 'fieldCoord',    'role' => 'Field Operations', 'roleKey' => 'fieldOperations',  'initials' => 'FC', 'photo' => '/public/team/field-coordinator.jpg'],
      ['name' => 'Communications Manager',      'nameKey' => 'commsManager',  'role' => 'Media & Comms',    'roleKey' => 'mediaComms',       'initials' => 'CM', 'photo' => '/public/team/comms-manager.jpg'],
      ['name' => 'Data & GIS Specialist',       'nameKey' => 'dataSpecialist','role' => 'Technology',       'roleKey' => 'technology',       'initials' => 'GS', 'photo' => '/public/team/data-specialist.jpg'],
      ['name' => 'Training Coordinator',        'nameKey' => 'trainingCoord', 'role' => 'Capacity Building','roleKey' => 'capacityBuildingRole', 'initials' => 'TC', 'photo' => '/public/team/training-coordinator.jpg'],
      ['name' => 'Programme Officer — Sahel',   'nameKey' => 'programmeOfficer', 'role' => 'Field Operations', 'roleKey' => 'fieldOperations', 'initials' => 'PO', 'photo' => '/public/team/programme-officer.jpg'],
    ];
    foreach ($team as $i => $member):
      $fbId = 'team-fb-' . $i; ?>
      <div class="bg-white rounded-2xl border border-amber-100 p-5 text-center shadow-sm hover:shadow-md hover:-translate-y-1 transition-all">
        <div class="relative w-16 h-16 mx-auto mb-3">
          <img src="<?= h($member['photo']) ?>" alt="<?= h($member['name']) ?>"
               class="w-16 h-16 rounded-2xl object-cover shadow-sm"
               onerror="this.style.display='none';document.getElementById('<?= $fbId ?>').style.display='flex'">
          <div id="<?= $fbId ?>" class="absolute inset-0 rounded-2xl items-center justify-center font-outfit font-black text-xl text-white shadow-sm"
               style="background:#750B25;display:none">
            <?= h($member['initials']) ?>
          </div>
        </div>
        <p class="font-bold text-sm text-slate-800 leading-snug mb-1" data-i18n="about.teamMember.<?= h($member['nameKey']) ?>"><?= h($member['name']) ?></p>
        <p class="text-[10px] font-black uppercase tracking-widest text-amber-700" data-i18n="about.teamRole.<?= h($member['roleKey']) ?>"><?= h($member['role']) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
  <p class="text-center text-xs text-slate-400 mt-6" data-i18n="about.teamComingSoon">Team profiles with photos and biographies coming soon.</p>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- PARTNERS                                                                  -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div class="max-w-7xl mx-auto px-6 mb-20">
  <div class="bg-white rounded-3xl border border-amber-100 p-10 shadow-sm">
    <div class="text-center mb-10">
      <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-3 text-amber-900" style="background:#E7952A" data-i18n="about.networkBadge">Network</span>
      <h2 class="font-outfit font-black text-3xl text-slate-900" data-i18n="about.partnersTitle">Partners &amp; Network Members</h2>
      <p class="text-slate-500 mt-2 max-w-xl mx-auto text-sm" data-i18n="about.partnersDesc">
        We work alongside academic institutions, civil society, and international organizations
        to amplify our impact across the continent.
      </p>
    </div>
    <?php if (!empty($dbPartners)): ?>
      <!-- Live partners from admin/super/partners -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($dbPartners as $partner): ?>
          <div class="rounded-2xl border border-amber-100 p-6 text-center" style="background:#F8F8F0">
            <div class="w-16 h-16 rounded-2xl mx-auto mb-4 flex items-center justify-center overflow-hidden bg-white shadow-sm">
              <?php if (!empty($partner['logoUrl'])): ?>
                <img src="<?= h($partner['logoUrl']) ?>" alt="<?= h($partner['name']) ?>"
                     class="w-full h-full object-contain p-1.5"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                <div class="hidden w-full h-full items-center justify-center font-outfit font-black text-xl text-white" style="background:#750B25"><?= h(strtoupper(substr($partner['name'], 0, 1))) ?></div>
              <?php else: ?>
                <div class="w-full h-full flex items-center justify-center font-outfit font-black text-xl text-white" style="background:#750B25"><?= h(strtoupper(substr($partner['name'], 0, 1))) ?></div>
              <?php endif; ?>
            </div>
            <h3 class="font-outfit font-bold text-lg text-slate-900 mb-1"><?= h($partner['name']) ?></h3>
            <?php if (!empty($partner['subtitle'])): ?>
              <p class="text-xs text-amber-800 font-bold uppercase tracking-widest mb-3"><?= h($partner['subtitle']) ?></p>
            <?php endif; ?>
            <?php if (!empty($partner['description'])): ?>
              <p class="text-slate-500 text-sm"><?= h($partner['description']) ?></p>
            <?php endif; ?>
            <?php if (!empty($partner['websiteUrl'])): ?>
              <a href="<?= h($partner['websiteUrl']) ?>" target="_blank" rel="noopener noreferrer"
                 class="inline-block mt-3 text-xs font-bold hover:underline" style="color:#750B25">Visit Website →</a>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <!-- Default content until partners are added via admin/super/partners -->
      <div class="max-w-sm mx-auto rounded-2xl border border-amber-100 p-8 text-center mb-8" style="background:#F8F8F0">
        <img src="/public/hekima-logo.jpg" alt="Hekima University College"
             class="h-16 object-contain mx-auto mb-4"
             onerror="this.parentElement.querySelector('.hekima-fb').style.display='flex';this.style.display='none'">
        <div class="hekima-fb w-16 h-16 rounded-2xl mx-auto mb-4 items-center justify-center font-outfit font-black text-2xl text-white hidden" style="background:#750B25">H</div>
        <h3 class="font-outfit font-bold text-lg text-slate-900 mb-1">Hekima University College</h3>
        <p class="text-xs text-amber-800 font-bold uppercase tracking-widest mb-3" data-i18n="about.hekimaSubtitle">Jesuit Institute of Peace Studies &amp; International Relations</p>
        <p class="text-slate-500 text-sm" data-i18n="about.hekimaDesc">A leading Jesuit institution in Nairobi, Kenya, specializing in peace studies, conflict transformation, and social justice in the African context.</p>
      </div>
      <!-- Partner placeholder grid -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <?php foreach ([['UN Agencies', 'unAgencies'], ['African Union', 'africanUnion'], ['Civil Society Partners', 'civilSociety'], ['Academic Institutions', 'academicInstitutions']] as [$p, $pKey]): ?>
          <div class="rounded-xl border border-dashed border-amber-200 p-4 text-center">
            <p class="text-xs font-bold text-slate-400" data-i18n="about.partnerType.<?= h($pKey) ?>"><?= h($p) ?></p>
            <p class="text-[10px] text-slate-300 mt-1" data-i18n="about.logosComingSoon">Logos coming soon</p>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- CALL TO ACTION                                                            -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div style="background:#0D0102" class="py-20 px-6">
  <div class="max-w-3xl mx-auto text-center">
    <h2 class="font-outfit font-black text-4xl text-white mb-4" data-i18n="about.ctaTitle">Ready to collaborate?</h2>
    <p class="text-white/60 text-lg mb-8" data-i18n="about.ctaDesc">
      Whether you are a researcher, journalist, policymaker, or community leader —
      there is a place for your expertise in our network.
    </p>
    <div class="flex flex-wrap justify-center gap-4">
      <a href="/contact" class="px-8 py-4 rounded-2xl font-bold text-slate-900 transition-all hover:brightness-110 text-base" style="background:#E7952A" data-i18n="about.getInTouch">
        Get In Touch
      </a>
      <a href="/news" class="px-8 py-4 rounded-2xl font-bold text-white transition-all border hover:bg-white/10 text-base" style="border-color:rgba(255,255,255,.2)" data-i18n="about.readResearch">
        Read Our Research
      </a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
