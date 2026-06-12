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
      <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-6 text-white" style="background:#750B25">About CRTP</span>
      <h1 class="font-outfit text-5xl md:text-6xl font-black text-white leading-tight mb-6">
        Reflecting on Our<br>
        <span style="color:#E7952A">Shared Future</span>
      </h1>
      <p class="text-white/70 text-lg leading-relaxed max-w-2xl mb-8">
        The Centre for Research, Training and Policy (CRTP) is an independent research and capacity-building
        organization committed to advancing peace, security, and good governance across Africa.
        <em class="text-white/50">Tafakari</em> — meaning "to reflect" in Swahili — is our digital platform
        connecting research, media, and community.
      </p>
      <div class="flex flex-wrap gap-4">
        <a href="/contact" class="px-7 py-3.5 rounded-2xl font-bold text-white transition-all hover:brightness-110" style="background:#750B25">
          Partner With Us
        </a>
        <a href="/heatmap" class="px-7 py-3.5 rounded-2xl font-bold text-white transition-all border hover:bg-white/10" style="border-color:rgba(255,255,255,.2)">
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
        ['value' => max($stats['articles'], 0),  'label' => 'Articles',           'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z'],
        ['value' => max($stats['documents'], 0), 'label' => 'Research Docs',       'icon' => 'M7 21h10a2 2 0 0 0 2-2V9.414a1 1 0 0 0-.293-.707l-5.414-5.414A1 1 0 0 0 12.586 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2z'],
        ['value' => max($stats['podcasts'], 0),  'label' => 'Podcast Episodes',    'icon' => 'M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3'],
        ['value' => max($stats['videos'], 0),    'label' => 'Video Reports',        'icon' => 'M15 10l4.553-2.069A1 1 0 0 1 21 8.82v6.36a1 1 0 0 1-1.447.894L15 14M5 18h8a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2z'],
        ['value' => max($stats['images'], 0),    'label' => 'Gallery Photos',       'icon' => 'M4 16l4.586-4.586a2 2 0 0 1 2.828 0L16 16m-2-2 1.586-1.586a2 2 0 0 1 2.828 0L20 14m-6-6h.01M6 20h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z'],
        ['value' => max((int)$stats['countries'], 15), 'label' => 'Countries Monitored', 'suffix' => '+', 'icon' => 'M3.055 11H5a2 2 0 0 1 2 2v1a2 2 0 0 0 2 2 2 2 0 0 1 2 2v2.945M8 3.935V5.5A2.5 2.5 0 0 0 10.5 8h.5a2 2 0 0 1 2 2 2 2 0 1 0 4 0 2 2 0 0 1 2-2h1.064M15 20.488V18a2 2 0 0 1 2-2h3.064'],
    ];
    ?>
    <?php foreach ($impactStats as $s): ?>
      <div class="bg-white rounded-2xl border border-amber-100 shadow-sm p-5 text-center hover:shadow-md hover:border-amber-200 transition-all">
        <div class="w-10 h-10 rounded-xl mx-auto mb-3 flex items-center justify-center" style="background:#F8F8F0">
          <svg width="20" height="20" fill="none" stroke="#C47C1A" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <path d="<?= $s['icon'] ?>"/>
          </svg>
        </div>
        <div class="font-outfit font-black text-2xl text-slate-900">
          <?= $s['value'] > 0 ? format_number((int)$s['value']) : '—' ?><?= $s['suffix'] ?? '' ?>
        </div>
        <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-1"><?= h($s['label']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- MISSION & VISION                                                          -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div class="max-w-7xl mx-auto px-6 mb-20">
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Mission -->
    <div class="bg-white rounded-3xl border border-amber-100 p-10 shadow-sm relative overflow-hidden">
      <div class="absolute top-0 right-0 w-40 h-40 rounded-full -translate-y-1/2 translate-x-1/2 pointer-events-none" style="background:rgba(231,149,42,.06)"></div>
      <span class="inline-block w-10 h-1 rounded-full mb-5" style="background:#E7952A"></span>
      <h2 class="font-outfit font-black text-3xl text-slate-900 mb-4">Our Mission</h2>
      <p class="text-slate-600 leading-relaxed text-lg">
        To generate rigorous, policy-relevant research and provide capacity-building support
        that empowers institutions, communities, and leaders to build sustainable peace
        and advance human security across Africa.
      </p>
    </div>
    <!-- Vision -->
    <div class="rounded-3xl border p-10 relative overflow-hidden" style="background:#0D0102;border-color:rgba(231,149,42,.2)">
      <div class="absolute top-0 right-0 w-40 h-40 rounded-full -translate-y-1/2 translate-x-1/2 pointer-events-none" style="background:rgba(117,11,37,.15)"></div>
      <span class="inline-block w-10 h-1 rounded-full mb-5" style="background:#E7952A"></span>
      <h2 class="font-outfit font-black text-3xl text-white mb-4">Our Vision</h2>
      <p class="text-white/70 leading-relaxed text-lg">
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
<div class="max-w-7xl mx-auto px-6 mb-20">
  <div class="mb-10">
    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-3 text-amber-900" style="background:#E7952A">What We Do</span>
    <h2 class="font-outfit font-black text-4xl text-slate-900">Core Programme Areas</h2>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <?php
    $programmes = [
      [
        'num' => '01', 'title' => 'Peace & Security Research',
        'desc' => 'Conflict analysis, early-warning systems, and field-based research across active and post-conflict zones. Our studies inform UN peacekeeping operations, NGO interventions, and government policy.',
        'tags' => ['Conflict Analysis', 'Early Warning', 'Field Research'],
      ],
      [
        'num' => '02', 'title' => 'Governance & Policy',
        'desc' => 'Monitoring state fragility, electoral integrity, institutional reform, and anti-corruption measures. We translate complex policy environments into actionable recommendations.',
        'tags' => ['State Fragility', 'Electoral Integrity', 'Policy Briefs'],
      ],
      [
        'num' => '03', 'title' => 'Capacity Building & Training',
        'desc' => 'Structured training programmes for journalists, civil society organizations, government officials, and community leaders on conflict-sensitive reporting, peacebuilding, and advocacy.',
        'tags' => ['Journalism Training', 'CSO Support', 'Advocacy Skills'],
      ],
      [
        'num' => '04', 'title' => 'Knowledge Management & Media',
        'desc' => 'The Tafakari platform serves as our digital knowledge hub — aggregating research, broadcasting field stories through podcasts and video, and providing open-access document archives.',
        'tags' => ['Open Access', 'Podcast', 'Digital Media'],
      ],
    ];
    ?>
    <?php foreach ($programmes as $p): ?>
      <div class="bg-white rounded-3xl border border-amber-100 p-8 shadow-sm hover:shadow-md hover:border-amber-200 transition-all group">
        <div class="flex items-start justify-between mb-5">
          <span class="font-outfit font-black text-4xl" style="color:rgba(231,149,42,.25)"><?= h($p['num']) ?></span>
          <span class="w-8 h-0.5 mt-5" style="background:#E7952A;display:block"></span>
        </div>
        <h3 class="font-outfit font-bold text-xl text-slate-900 mb-3 group-hover:text-amber-800 transition-colors"><?= h($p['title']) ?></h3>
        <p class="text-slate-500 leading-relaxed text-sm mb-5"><?= h($p['desc']) ?></p>
        <div class="flex flex-wrap gap-2">
          <?php foreach ($p['tags'] as $tag): ?>
            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-amber-50 text-amber-800"><?= h($tag) ?></span>
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
        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-5 text-amber-900" style="background:#E7952A">Where We Work</span>
        <h2 class="font-outfit font-black text-4xl text-white mb-4">Geographic Focus</h2>
        <p class="text-white/60 leading-relaxed mb-8">
          Our primary focus spans East Africa and the Great Lakes region, with expanding coverage
          across the Sahel and Horn of Africa — regions experiencing the most complex and
          intersecting conflict dynamics on the continent.
        </p>
        <a href="/heatmap" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-bold text-slate-900 transition-all hover:brightness-110" style="background:#E7952A">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
          Explore Interactive Heatmap
        </a>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <?php
        $geoFocus = [
          ['region' => 'East Africa', 'countries' => 'Kenya · Uganda · Tanzania · Rwanda · Burundi', 'tag' => 'Primary'],
          ['region' => 'Great Lakes', 'countries' => 'DR Congo · Rwanda · Burundi · South Sudan', 'tag' => 'Primary'],
          ['region' => 'Horn of Africa', 'countries' => 'Ethiopia · Somalia · Eritrea · Djibouti · Sudan', 'tag' => 'Active'],
          ['region' => 'Sahel Region', 'countries' => 'Mali · Niger · Burkina Faso · Chad · Nigeria NE', 'tag' => 'Expanding'],
          ['region' => 'Southern Africa', 'countries' => 'Mozambique · Zimbabwe · Zambia · Madagascar', 'tag' => 'Expanding'],
          ['region' => 'Central Africa', 'countries' => 'Central African Republic · Cameroon · Congo', 'tag' => 'Monitoring'],
        ];
        foreach ($geoFocus as $g): ?>
          <div class="rounded-2xl p-5 border" style="background:rgba(255,255,255,.04);border-color:rgba(255,255,255,.08)">
            <div class="flex items-center justify-between mb-2">
              <h4 class="font-outfit font-bold text-white text-sm"><?= h($g['region']) ?></h4>
              <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest
                <?= $g['tag'] === 'Primary' ? 'text-amber-900' : ($g['tag'] === 'Active' ? 'text-emerald-800' : 'text-slate-500') ?>"
                style="background:<?= $g['tag'] === 'Primary' ? '#E7952A' : ($g['tag'] === 'Active' ? 'rgba(52,211,153,.15)' : 'rgba(255,255,255,.08)') ?>">
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
<!-- OUR VALUES                                                                -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div class="max-w-7xl mx-auto px-6 py-20">
  <div class="text-center max-w-2xl mx-auto mb-12">
    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-3 text-amber-900" style="background:#E7952A">Our Foundation</span>
    <h2 class="font-outfit font-black text-4xl text-slate-900">Guiding Values</h2>
  </div>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    <?php
    $values = [
      ['title' => 'Rigour',         'desc' => 'Evidence-based methodology underpins all our research. We hold ourselves to the highest academic and professional standards.'],
      ['title' => 'Independence',   'desc' => 'We operate free from political affiliation or donor bias. Our findings reflect the evidence, not agendas.'],
      ['title' => 'Inclusion',      'desc' => 'Community voices are as vital as academic expertise. We amplify perspectives that are often overlooked in formal policy spaces.'],
      ['title' => 'Impact',         'desc' => 'Research without action is incomplete. We design every project with measurable policy, community, or behavioural outcomes in mind.'],
    ];
    foreach ($values as $i => $v): ?>
      <div class="bg-white rounded-2xl border border-amber-100 p-7 shadow-sm text-center hover:shadow-md hover:-translate-y-1 transition-all">
        <div class="w-12 h-12 rounded-2xl mx-auto mb-4 flex items-center justify-center font-outfit font-black text-lg text-white" style="background:#750B25">
          <?= ['R', 'I', 'I', 'I'][$i] ?>
        </div>
        <h3 class="font-outfit font-bold text-lg text-slate-900 mb-2"><?= h($v['title']) ?></h3>
        <p class="text-slate-500 text-sm leading-relaxed"><?= h($v['desc']) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- OUR TEAM (Placeholder ready for real names/photos)                       -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div class="max-w-7xl mx-auto px-6 mb-20">
  <div class="mb-10">
    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-3 text-amber-900" style="background:#E7952A">The People</span>
    <h2 class="font-outfit font-black text-4xl text-slate-900">Our Team</h2>
    <p class="text-slate-500 mt-2">Researchers, journalists, and policy specialists committed to the long game.</p>
  </div>
  <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
    <?php
    $team = [
      ['name' => 'Executive Director',          'role' => 'Leadership',       'initials' => 'ED'],
      ['name' => 'Head of Research',            'role' => 'Research',         'initials' => 'HR'],
      ['name' => 'Policy Analyst — East Africa','role' => 'Policy',           'initials' => 'PA'],
      ['name' => 'Field Coordinator — DRC',     'role' => 'Field Operations', 'initials' => 'FC'],
      ['name' => 'Communications Manager',      'role' => 'Media & Comms',    'initials' => 'CM'],
      ['name' => 'Data & GIS Specialist',       'role' => 'Technology',       'initials' => 'GS'],
      ['name' => 'Training Coordinator',        'role' => 'Capacity Building','initials' => 'TC'],
      ['name' => 'Programme Officer — Sahel',   'role' => 'Field Operations', 'initials' => 'PO'],
    ];
    foreach ($team as $member): ?>
      <div class="bg-white rounded-2xl border border-amber-100 p-5 text-center shadow-sm hover:shadow-md hover:-translate-y-1 transition-all">
        <div class="w-16 h-16 rounded-2xl mx-auto mb-3 flex items-center justify-center font-outfit font-black text-xl text-white shadow-sm"
             style="background:#750B25">
          <?= h($member['initials']) ?>
        </div>
        <p class="font-bold text-sm text-slate-800 leading-snug mb-1"><?= h($member['name']) ?></p>
        <p class="text-[10px] font-black uppercase tracking-widest text-amber-700"><?= h($member['role']) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
  <p class="text-center text-xs text-slate-400 mt-6">Team profiles with photos and biographies coming soon.</p>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- PARTNERS                                                                  -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div class="max-w-7xl mx-auto px-6 mb-20">
  <div class="bg-white rounded-3xl border border-amber-100 p-10 shadow-sm">
    <div class="text-center mb-10">
      <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-3 text-amber-900" style="background:#E7952A">Network</span>
      <h2 class="font-outfit font-black text-3xl text-slate-900">Partners &amp; Network Members</h2>
      <p class="text-slate-500 mt-2 max-w-xl mx-auto text-sm">
        We work alongside academic institutions, civil society, and international organizations
        to amplify our impact across the continent.
      </p>
    </div>
    <!-- Featured partner -->
    <div class="max-w-sm mx-auto rounded-2xl border border-amber-100 p-8 text-center mb-8" style="background:#F8F8F0">
      <img src="/public/hekima-logo.png" alt="Hekima University College"
           class="h-16 object-contain mx-auto mb-4"
           onerror="this.parentElement.querySelector('.hekima-fb').style.display='flex';this.style.display='none'">
      <div class="hekima-fb w-16 h-16 rounded-2xl mx-auto mb-4 items-center justify-center font-outfit font-black text-2xl text-white hidden" style="background:#750B25">H</div>
      <h3 class="font-outfit font-bold text-lg text-slate-900 mb-1">Hekima University College</h3>
      <p class="text-xs text-amber-800 font-bold uppercase tracking-widest mb-3">Jesuit Institute of Peace Studies &amp; International Relations</p>
      <p class="text-slate-500 text-sm">A leading Jesuit institution in Nairobi, Kenya, specializing in peace studies, conflict transformation, and social justice in the African context.</p>
    </div>
    <!-- Partner placeholder grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <?php foreach (['UN Agencies', 'African Union', 'Civil Society Partners', 'Academic Institutions'] as $p): ?>
        <div class="rounded-xl border border-dashed border-amber-200 p-4 text-center">
          <p class="text-xs font-bold text-slate-400"><?= h($p) ?></p>
          <p class="text-[10px] text-slate-300 mt-1">Logos coming soon</p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- CALL TO ACTION                                                            -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div style="background:#0D0102" class="py-20 px-6">
  <div class="max-w-3xl mx-auto text-center">
    <h2 class="font-outfit font-black text-4xl text-white mb-4">Ready to collaborate?</h2>
    <p class="text-white/60 text-lg mb-8">
      Whether you are a researcher, journalist, policymaker, or community leader —
      there is a place for your expertise in our network.
    </p>
    <div class="flex flex-wrap justify-center gap-4">
      <a href="/contact" class="px-8 py-4 rounded-2xl font-bold text-slate-900 transition-all hover:brightness-110 text-base" style="background:#E7952A">
        Get In Touch
      </a>
      <a href="/news" class="px-8 py-4 rounded-2xl font-bold text-white transition-all border hover:bg-white/10 text-base" style="border-color:rgba(255,255,255,.2)">
        Read Our Research
      </a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
