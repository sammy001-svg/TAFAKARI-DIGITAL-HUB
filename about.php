<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/about-content.php';

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

// ── Live team list (falls back to default content below if empty) ──────────────
$dbTeam = [];
try {
    ensure_team_table();
    $dbTeam = db()->query(
        "SELECT * FROM TeamMember WHERE isActive = 1 ORDER BY sortOrder ASC, createdAt ASC"
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
      <?php $t = about_text('hero_badge'); ?><span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-6 text-white" style="background:#750B25"<?= $t['i18n'] ?>><?= h($t['text']) ?></span>
      <h1 class="font-outfit text-5xl md:text-6xl font-black text-white leading-tight mb-6">
        <?php $t = about_text('hero_title1'); ?><span<?= $t['i18n'] ?>><?= h($t['text']) ?></span><br>
        <?php $t = about_text('hero_title2'); ?><span style="color:#E7952A"<?= $t['i18n'] ?>><?= h($t['text']) ?></span>
      </h1>
      <?php $t = about_text('hero_desc'); ?>
      <p class="text-white/70 text-lg leading-relaxed max-w-2xl mb-8"<?= $t['i18n'] ?>><?= h($t['text']) ?></p>
      <div class="flex flex-wrap gap-4">
        <a href="/contact" class="px-7 py-3.5 rounded-2xl font-bold text-white transition-all hover:brightness-110" style="background:#750B25"<?= about_text('hero_cta1')['i18n'] ?>><?= h(about_str('hero_cta1')) ?></a>
        <a href="/heatmap" class="px-7 py-3.5 rounded-2xl font-bold text-white transition-all border hover:bg-white/10" style="border-color:rgba(255,255,255,.2)"<?= about_text('hero_cta2')['i18n'] ?>><?= h(about_str('hero_cta2')) ?></a>
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
        ['value' => max((int)$stats['countries'], 15), 'label' => 'Countries Monitored', 'key' => 'about.stat.countries', 'suffix' => '+', 'href' => '/heatmap', 'icon' => 'M3.055 11H5a2 2 0 0 1 2 2v1a2 2 0 0 0 2 2 2 2 0 0 1 2 2v2.945M8 3.935V5.5A2.5 2.5 0 0 0 10.5 8h.5a2 2 0 0 1 2 2 2 2 0 1 0 4 0 2 2 0 0 1 2-2h1.064M15 20.488V18a2 2 0 0 1 2-2h3.064'],
    ];
    ?>
    <?php foreach ($impactStats as $s): ?>
      <a href="<?= h($s['href']) ?>" class="block bg-white rounded-2xl border border-amber-100 shadow-sm p-5 text-center hover:shadow-md hover:border-amber-200 hover:-translate-y-0.5 transition-all">
        <div class="w-10 h-10 rounded-xl mx-auto mb-3 flex items-center justify-center" style="background:#F8F8F0">
          <svg width="20" height="20" fill="none" stroke="#750B25" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
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
  <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-3 text-amber-900" style="background:#E7952A"<?= about_text('purpose_badge')['i18n'] ?>><?= h(about_str('purpose_badge')) ?></span>
  <?php $t = about_text('purpose_title'); ?><h2 class="font-outfit font-black text-4xl text-slate-900 mb-4"<?= $t['i18n'] ?>><?= h($t['text']) ?></h2>
  <?php $t = about_text('purpose_desc'); ?>
  <p class="text-slate-600 leading-relaxed text-lg max-w-3xl"<?= $t['i18n'] ?>><?= h($t['text']) ?></p>
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
      <?php $t = about_text('mission_title'); ?><h2 class="font-outfit font-black text-3xl text-slate-900 mb-4"<?= $t['i18n'] ?>><?= h($t['text']) ?></h2>
      <?php $t = about_text('mission_desc'); ?>
      <p class="text-slate-600 leading-relaxed text-lg"<?= $t['i18n'] ?>><?= h($t['text']) ?></p>
    </div>
    <!-- Vision -->
    <div class="rounded-3xl border p-10 relative overflow-hidden" style="background:#0D0102;border-color:rgba(231,149,42,.2)">
      <div class="absolute top-0 right-0 w-40 h-40 rounded-full -translate-y-1/2 translate-x-1/2 pointer-events-none" style="background:rgba(117,11,37,.15)"></div>
      <span class="inline-block w-10 h-1 rounded-full mb-5" style="background:#E7952A"></span>
      <?php $t = about_text('vision_title'); ?><h2 class="font-outfit font-black text-3xl text-white mb-4"<?= $t['i18n'] ?>><?= h($t['text']) ?></h2>
      <?php $t = about_text('vision_desc'); ?>
      <p class="text-white/70 leading-relaxed text-lg"<?= $t['i18n'] ?>><?= h($t['text']) ?></p>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- CORE PROGRAMME AREAS                                                      -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div id="what-we-do" class="max-w-7xl mx-auto px-6 mb-20 scroll-mt-24">
  <div class="mb-10">
    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-3 text-amber-900" style="background:#E7952A"<?= about_text('programmes_badge')['i18n'] ?>><?= h(about_str('programmes_badge')) ?></span>
    <?php $t = about_text('programmes_title'); ?><h2 class="font-outfit font-black text-4xl text-slate-900"<?= $t['i18n'] ?>><?= h($t['text']) ?></h2>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <?php
    $programmes = about_list('programmes');
    ?>
    <?php foreach ($programmes as $p): ?>
      <div class="bg-white rounded-3xl border border-amber-100 p-8 shadow-sm hover:shadow-md hover:border-amber-200 transition-all group">
        <div class="flex items-start justify-between mb-5">
          <span class="font-outfit font-black text-4xl" style="color:rgba(231,149,42,.25)"><?= h($p['num'] ?? '') ?></span>
          <span class="w-8 h-0.5 mt-5" style="background:#E7952A;display:block"></span>
        </div>
        <h3 class="font-outfit font-bold text-xl text-slate-900 mb-3 group-hover:text-amber-800 transition-colors"><?= h($p['title'] ?? '') ?></h3>
        <p class="text-slate-500 leading-relaxed text-sm mb-5"><?= h($p['desc'] ?? '') ?></p>
        <div class="flex flex-wrap gap-2">
          <?php foreach ((array)($p['tags'] ?? []) as $tag): ?>
            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-amber-50 text-amber-800"><?= h((string)$tag) ?></span>
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
        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-5 text-amber-900" style="background:#E7952A"<?= about_text('geo_badge')['i18n'] ?>><?= h(about_str('geo_badge')) ?></span>
        <?php $t = about_text('geo_title'); ?><h2 class="font-outfit font-black text-4xl text-white mb-4"<?= $t['i18n'] ?>><?= h($t['text']) ?></h2>
        <?php $t = about_text('geo_desc'); ?>
        <p class="text-white/60 leading-relaxed mb-8"<?= $t['i18n'] ?>><?= h($t['text']) ?></p>
        <a href="/heatmap" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-bold text-slate-900 transition-all hover:brightness-110" style="background:#E7952A">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
          <span<?= about_text('geo_cta')['i18n'] ?>><?= h(about_str('geo_cta')) ?></span>
        </a>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <?php
        $geoFocus = about_list('geo');
        foreach ($geoFocus as $g): ?>
          <div class="rounded-2xl p-5 border" style="background:rgba(255,255,255,.04);border-color:rgba(255,255,255,.08)">
            <div class="flex items-center justify-between mb-2">
              <h4 class="font-outfit font-bold text-white text-sm"><?= h($g['region'] ?? '') ?></h4>
              <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest
                <?= $g['tag'] === 'Primary' ? 'text-amber-900' : ($g['tag'] === 'Active' ? 'text-emerald-800' : 'text-slate-500') ?>"
                style="background:<?= $g['tag'] === 'Primary' ? '#E7952A' : ($g['tag'] === 'Active' ? 'rgba(52,211,153,.15)' : 'rgba(255,255,255,.08)') ?>"
                ><?= h($g['tag'] ?? '') ?></span>
            </div>
            <p class="text-white/40 text-xs leading-relaxed"><?= h($g['countries'] ?? '') ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- OUR CORE VALUES                                                                -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div class="max-w-7xl mx-auto px-6 py-20">
  <div class="text-center max-w-2xl mx-auto mb-12">
    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-3 text-amber-900" style="background:#E7952A"<?= about_text('values_badge')['i18n'] ?>><?= h(about_str('values_badge')) ?></span>
    <?php $t = about_text('values_title'); ?><h2 class="font-outfit font-black text-4xl text-slate-900"<?= $t['i18n'] ?>><?= h($t['text']) ?></h2>
  </div>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    <?php
    $values = about_list('values');
    foreach ($values as $v): ?>
      <div class="bg-white rounded-2xl border border-amber-100 p-7 shadow-sm text-center hover:shadow-md hover:-translate-y-1 transition-all">
        <div class="w-12 h-12 rounded-2xl mx-auto mb-4 flex items-center justify-center font-outfit font-black text-lg text-white" style="background:#750B25">
          <?= h($v['letter'] ?? mb_strtoupper(mb_substr((string)($v['title'] ?? '?'), 0, 1))) ?>
        </div>
        <h3 class="font-outfit font-bold text-lg text-slate-900 mb-2"><?= h($v['title'] ?? '') ?></h3>
        <p class="text-slate-500 text-sm leading-relaxed"><?= h($v['desc'] ?? '') ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- OUR TEAM MEMBERS (Placeholder ready for real names/photos)                       -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div class="max-w-7xl mx-auto px-6 mb-20">
  <div class="mb-10">
    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-3 text-amber-900" style="background:#E7952A"<?= about_text('team_badge')['i18n'] ?>><?= h(about_str('team_badge')) ?></span>
    <?php $t = about_text('team_title'); ?><h2 class="font-outfit font-black text-4xl text-slate-900"<?= $t['i18n'] ?>><?= h($t['text']) ?></h2>
    <p class="text-slate-500 mt-2" data-i18n="about.teamDesc">Researchers, journalists, and policy specialists committed to the long game.</p>
  </div>
  <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
    <?php if (!empty($dbTeam)): ?>
      <!-- Live team members from admin/super/team -->
      <?php foreach ($dbTeam as $i => $member):
        $fbId = 'team-fb-' . $i; ?>
        <div class="bg-white rounded-2xl border border-amber-100 p-5 text-center shadow-sm hover:shadow-md hover:-translate-y-1 transition-all">
          <div class="relative w-16 h-16 mx-auto mb-3">
            <?php if (!empty($member['photoUrl'])): ?>
              <img src="<?= h($member['photoUrl']) ?>" alt="<?= h($member['name']) ?>"
                   class="w-16 h-16 rounded-2xl object-cover shadow-sm"
                   onerror="this.style.display='none';document.getElementById('<?= $fbId ?>').style.display='flex'">
            <?php endif; ?>
            <div id="<?= $fbId ?>" class="absolute inset-0 rounded-2xl items-center justify-center font-outfit font-black text-xl text-white shadow-sm"
                 style="background:#750B25;<?= empty($member['photoUrl']) ? '' : 'display:none' ?>">
              <?= h(strtoupper(substr($member['name'], 0, 1))) ?>
            </div>
          </div>
          <p class="font-bold text-sm text-slate-800 leading-snug mb-1"><?= h($member['name']) ?></p>
          <?php if (!empty($member['role'])): ?>
            <p class="text-[10px] font-black uppercase tracking-widest text-amber-700"><?= h($member['role']) ?></p>
          <?php endif; ?>
          <?php if (!empty($member['bio'])): ?>
            <p class="text-xs text-slate-500 mt-2 leading-relaxed"><?= h($member['bio']) ?></p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <!-- Default placeholder content until team members are added via admin/super/team -->
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
    <?php endif; ?>
  </div>
  <?php if (empty($dbTeam)): ?>
    <p class="text-center text-xs text-slate-400 mt-6" data-i18n="about.teamComingSoon">Team profiles with photos and biographies coming soon.</p>
  <?php endif; ?>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- OUR PARTNERS                                                                  -->
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
    <?php $t = about_text('cta_title'); ?><h2 class="font-outfit font-black text-4xl text-white mb-4"<?= $t['i18n'] ?>><?= h($t['text']) ?></h2>
    <?php $t = about_text('cta_desc'); ?>
    <p class="text-white/60 text-lg mb-8"<?= $t['i18n'] ?>><?= h($t['text']) ?></p>
    <div class="flex flex-wrap justify-center gap-4">
      <a href="/contact" class="px-8 py-4 rounded-2xl font-bold text-slate-900 transition-all hover:brightness-110 text-base" style="background:#E7952A"<?= about_text('cta_btn1')['i18n'] ?>><?= h(about_str('cta_btn1')) ?></a>
      <a href="/news" class="px-8 py-4 rounded-2xl font-bold text-white transition-all border hover:bg-white/10 text-base" style="border-color:rgba(255,255,255,.2)"<?= about_text('cta_btn2')['i18n'] ?>><?= h(about_str('cta_btn2')) ?></a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
