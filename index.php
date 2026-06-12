<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$latestArticles = [];
try {
    $stmt = db()->query("SELECT id, title, description, thumbnailUrl, country, issueCategory, createdAt
                         FROM Post WHERE type='ARTICLE' AND status='PUBLISHED'
                         ORDER BY createdAt DESC LIMIT 3");
    $latestArticles = $stmt->fetchAll();
} catch (Exception $e) { /* DB not yet set up */ }
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex flex-col bg-slate-50 font-inter">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="flex-grow flex flex-col gap-20 pb-20">

  <!-- Hero Carousel -->
  <section class="relative w-full overflow-hidden" style="height:85vh;min-height:500px">
    <?php
    $slides = [
      ['img' => '/public/nairobi_sunset_hero.png',      'tag' => 'Kenya Focus',    'title' => 'Tracking What Matters Across Kenya',         'sub' => 'Real-time issue mapping and data-driven insights for all 47 counties.'],
      ['img' => '/public/ethiopia_community_hero.png',  'tag' => 'Ethiopia Focus', 'title' => 'Community Knowledge from Ethiopia\'s Regions','sub' => 'Field reports, research briefs and oral histories from 11 regional states.'],
      ['img' => '/public/drc_nature_hero.png',          'tag' => 'DRC Focus',      'title' => 'Voices from the Congo Basin',                 'sub' => 'Documenting resilience and change across 26 provinces.'],
    ];
    foreach ($slides as $i => $s): ?>
      <div class="carousel-slide absolute inset-0 transition-opacity duration-700 <?= $i === 0 ? 'opacity-100' : 'opacity-0' ?>" data-index="<?= $i ?>">
        <img src="<?= h($s['img']) ?>" alt="<?= h($s['title']) ?>" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/40 to-transparent"></div>
        <div class="absolute inset-0 flex flex-col justify-end pb-24 px-10 md:px-24">
          <span class="inline-flex mb-4 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest w-fit" style="background:rgba(217,159,81,.9);color:#020617"><?= h($s['tag']) ?></span>
          <h1 class="font-outfit font-black text-3xl md:text-5xl text-white leading-tight max-w-2xl mb-4"><?= h($s['title']) ?></h1>
          <p class="text-white/80 text-base md:text-lg max-w-xl mb-8"><?= h($s['sub']) ?></p>
          <div class="flex gap-4">
            <a href="/heatmap" class="btn-primary">Explore Heatmap</a>
            <a href="/about"   class="btn-secondary">Our Mission</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <!-- Dots -->
    <div class="absolute bottom-8 left-10 md:left-24 flex gap-3 items-center z-10">
      <?php foreach ($slides as $i => $_): ?>
        <button class="carousel-dot w-2 h-2 rounded-full transition-all duration-300 <?= $i === 0 ? 'w-8 bg-secondary' : 'bg-white/50' ?>"
                data-index="<?= $i ?>"></button>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Feature Cards -->
  <section class="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-3 gap-8 pt-4">
    <?php
    $cards = [
      ['icon' => 'M', 'title' => 'Media Broadcasting',    'desc' => 'Galleries, podcasts, and video libraries documenting field activities and regional oral reports.',  'cls' => 'bg-white/10 text-secondary'],
      ['icon' => 'K', 'title' => 'Knowledge Repository',  'desc' => 'A structured library of research reports, policy briefs, and actionable datasets for public use.',   'cls' => 'bg-white/20 text-white'],
      ['icon' => 'C', 'title' => 'Community Engagement',  'desc' => 'Moderated comments and contact submissions ensuring safe and constructive dialogue.',                 'cls' => 'bg-primary text-white'],
    ];
    foreach ($cards as $c): ?>
    <div class="glass-dark p-8 rounded-3xl hover:border-white/40 transition-all hover:-translate-y-1 text-white" style="background:var(--glass-bg);border:1px solid var(--glass-border)">
      <div class="w-12 h-12 <?= $c['cls'] ?> rounded-2xl flex items-center justify-center text-2xl mb-6 font-bold shadow-sm border border-white/10">
        <?= h($c['icon']) ?>
      </div>
      <h3 class="font-outfit font-bold text-xl mb-3 text-white"><?= h($c['title']) ?></h3>
      <p class="text-white/70 text-sm leading-relaxed"><?= h($c['desc']) ?></p>
    </div>
    <?php endforeach; ?>
  </section>

  <!-- Latest Articles -->
  <?php if (!empty($latestArticles)): ?>
  <section class="max-w-7xl mx-auto px-6">
    <div class="flex items-center justify-between mb-10">
      <div>
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-black uppercase tracking-widest mb-3">
          Latest from the Region
        </div>
        <h2 class="font-outfit text-3xl font-bold">Recent Articles</h2>
      </div>
      <a href="/news" class="text-sm font-black uppercase tracking-widest hover:underline hidden md:block" style="color:#9A1415">
        View All &rarr;
      </a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      <?php foreach ($latestArticles as $art): ?>
        <a href="/news/<?= h($art['id']) ?>" class="group flex flex-col gap-4">
          <div class="h-48 bg-slate-100 rounded-3xl overflow-hidden relative border border-slate-200">
            <?php if (!empty($art['thumbnailUrl'])): ?>
              <img src="<?= h($art['thumbnailUrl']) ?>" alt="<?= h($art['title']) ?>"
                   class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
            <?php else: ?>
              <div class="absolute inset-0 opacity-10 group-hover:opacity-20 transition-opacity" style="background:#9A1415"></div>
            <?php endif; ?>
            <div class="absolute top-4 left-4">
              <span class="glass-dark px-2 py-1 rounded-full text-[10px] font-bold text-white uppercase backdrop-blur-md" style="background:var(--glass-bg)">
                <?= h($art['country']) ?>
              </span>
            </div>
          </div>
          <div>
            <span class="text-[10px] font-black uppercase tracking-widest block mb-1" style="color:#D99F51">
              <?= h($art['issueCategory']) ?>
            </span>
            <h3 class="font-outfit font-bold text-lg leading-snug group-hover:text-primary transition-colors line-clamp-2">
              <?= h($art['title']) ?>
            </h3>
            <p class="text-xs text-slate-400 mt-1"><?= format_date($art['createdAt']) ?></p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
    <div class="mt-6 md:hidden text-center">
      <a href="/news" class="text-sm font-black uppercase tracking-widest" style="color:#9A1415">View All Articles &rarr;</a>
    </div>
  </section>
  <?php endif; ?>

  <!-- Target Geographies -->
  <section class="bg-slate-50 py-24 px-6 border-y" style="border-color:rgba(217,159,81,.1)">
    <div class="max-w-7xl mx-auto text-center mb-16">
      <h2 class="font-outfit text-4xl font-bold mb-4" style="color:#9A1415">Target Geographies</h2>
      <p class="text-slate-500 max-w-2xl mx-auto">
        Providing structured information sharing and visualization across three strategic nations.
      </p>
    </div>
    <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-12">
      <?php
      $geos = [
        ['img' => '/public/nairobi_city.png',    'flag' => '/public/kenya_flag.png',    'code' => 'KE', 'name' => 'Kenya',    'sub' => '47 Counties • Nairobi, Mombasa, Kisumu'],
        ['img' => '/public/addis_ababa_city.png','flag' => '/public/ethiopia_flag.png', 'code' => 'ET', 'name' => 'Ethiopia', 'sub' => '11 Regional States • Addis Ababa, Dire Dawa'],
        ['img' => '/public/kinshasa_city.png',   'flag' => '/public/drc_flag.png',      'code' => 'CD', 'name' => 'DR Congo', 'sub' => '26 Provinces • Kinshasa, Lubumbashi'],
      ];
      foreach ($geos as $g): ?>
        <div class="flex flex-col gap-6 group">
          <div class="h-56 rounded-3xl overflow-hidden relative shadow-xl group-hover:scale-[1.02] transition-all" style="background:#9A1415">
            <img src="<?= h($g['img']) ?>" alt="<?= h($g['name']) ?>" class="w-full h-full object-cover opacity-60 group-hover:opacity-80 transition-opacity">
            <div class="absolute inset-0 bg-black/50 z-10"></div>
            <div class="absolute top-4 right-4 w-12 h-12 z-20 shadow-xl rounded-full overflow-hidden border-2 border-white/20">
              <img src="<?= h($g['flag']) ?>" alt="<?= h($g['name']) ?> Flag" class="w-full h-full object-cover">
            </div>
            <div class="absolute bottom-6 left-6 z-20">
              <div class="font-outfit font-black text-4xl text-white uppercase tracking-tighter"><?= h($g['code']) ?></div>
            </div>
          </div>
          <div>
            <h4 class="font-outfit font-bold text-2xl mb-2" style="color:#9A1415"><?= h($g['name']) ?></h4>
            <p class="text-slate-500 text-sm italic"><?= h($g['sub']) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
(function(){
  var slides = document.querySelectorAll('.carousel-slide');
  var dots   = document.querySelectorAll('.carousel-dot');
  var cur    = 0;
  function go(n) {
    slides[cur].classList.remove('opacity-100'); slides[cur].classList.add('opacity-0');
    dots[cur].classList.remove('w-8','bg-secondary'); dots[cur].classList.add('bg-white/50');
    cur = n % slides.length;
    slides[cur].classList.remove('opacity-0'); slides[cur].classList.add('opacity-100');
    dots[cur].classList.remove('bg-white/50'); dots[cur].classList.add('w-8','bg-secondary');
  }
  dots.forEach(function(d){ d.addEventListener('click', function(){ go(+this.dataset.index); }); });
  setInterval(function(){ go(cur+1); }, 6500);
})();
</script>
</body>
</html>
