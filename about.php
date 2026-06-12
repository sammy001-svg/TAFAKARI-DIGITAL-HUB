<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'About Us | Tafakari Digital Hub';
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex flex-col bg-slate-50 font-inter">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="flex-grow max-w-7xl mx-auto px-6 py-20 flex flex-col gap-20">

  <!-- Hero -->
  <section class="text-center max-w-3xl mx-auto">
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-black uppercase tracking-widest mb-6">
      About Tafakari Hub
    </div>
    <h1 class="font-outfit text-5xl font-black mb-6" style="color:#9A1415">Reflecting on Our Shared Future</h1>
    <p class="text-slate-600 text-lg leading-relaxed">
      <em>Tafakari</em> — meaning "to reflect" in Swahili — is a multi-user digital platform serving as a
      centralized knowledge repository, media broadcasting center, and community engagement tool
      across Kenya, Ethiopia, and the Democratic Republic of Congo.
    </p>
  </section>

  <!-- Mission Grid -->
  <section class="grid grid-cols-1 md:grid-cols-3 gap-8">
    <?php
    $missions = [
      ['icon' => '📡', 'title' => 'Media Broadcasting',
       'desc' => 'We capture and distribute field activities through galleries, podcasts, and video libraries. Every story from every region deserves to be heard and preserved.'],
      ['icon' => '📚', 'title' => 'Knowledge Repository',
       'desc' => 'A structured library of research reports, policy briefs, and actionable datasets. Our archive empowers policymakers, researchers, and communities with reliable data.'],
      ['icon' => '🤝', 'title' => 'Community Engagement',
       'desc' => 'Moderated comments and secure contact channels ensure safe, constructive dialogue. We believe every voice matters in shaping a better future for the region.'],
    ];
    foreach ($missions as $m): ?>
    <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm hover:-translate-y-1 transition-all">
      <div class="text-4xl mb-6"><?= $m['icon'] ?></div>
      <h3 class="font-outfit font-bold text-xl mb-3 text-slate-900"><?= h($m['title']) ?></h3>
      <p class="text-slate-500 text-sm leading-relaxed"><?= h($m['desc']) ?></p>
    </div>
    <?php endforeach; ?>
  </section>

  <!-- Geographic Focus -->
  <section class="bg-white rounded-4xl border border-slate-100 shadow-sm p-12">
    <h2 class="font-outfit font-bold text-3xl mb-2 text-slate-900">Geographic Focus</h2>
    <p class="text-slate-500 mb-10">Three strategic nations. Endless stories worth telling.</p>
    <div class="flex flex-wrap gap-4">
      <?php
      $countries = [
        ['KE', 'Kenya',    '47 Counties'],
        ['ET', 'Ethiopia', '11 Regional States'],
        ['CD', 'DR Congo', '26 Provinces'],
      ];
      foreach ($countries as [$code, $name, $sub]): ?>
        <div class="flex items-center gap-3 px-6 py-4 rounded-2xl border border-slate-200 bg-slate-50 hover:border-primary transition-colors">
          <span class="font-outfit font-black text-2xl" style="color:#9A1415"><?= h($code) ?></span>
          <div>
            <div class="font-bold text-slate-900"><?= h($name) ?></div>
            <div class="text-xs text-slate-400"><?= h($sub) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- CTA -->
  <section class="text-center">
    <p class="text-slate-500 mb-6">Want to contribute, partner, or simply learn more?</p>
    <a href="/contact" class="btn-primary text-base px-8 py-4">Get In Touch</a>
  </section>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
