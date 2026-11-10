<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Terms of Use | Tafakari Digital Hub';
$pageDesc  = 'The terms for using the Tafakari Digital Hub, run by CRTP at Hekima University College.';
$updated   = '10 November 2026';
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex flex-col font-inter" style="background:#F8F8F0">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="grow">
  <div style="background:#750B25" class="text-white px-4 md:px-6 py-10 md:py-12">
    <div class="max-w-3xl mx-auto">
      <p class="text-[11px] font-black uppercase tracking-[.14em] mb-3" style="color:#E7952A">Tafakari Digital Hub</p>
      <h1 class="font-outfit font-black text-3xl md:text-5xl leading-tight">Terms of Use</h1>
      <p class="mt-4 text-sm" style="color:rgba(255,255,255,.8)">Last updated <?= h($updated) ?></p>
    </div>
  </div>

  <article class="max-w-3xl mx-auto px-4 md:px-6 py-10 text-slate-700 leading-relaxed space-y-8">
    <section>
      <h2 class="font-outfit font-black text-xl text-slate-900 mb-2">About these terms</h2>
      <p>The Tafakari Digital Hub is run by the Centre for Research, Training and Publications (CRTP) at Hekima University College in Nairobi.
        By using the site you agree to these terms. Please also read our <a class="underline font-semibold" style="color:#750B25" href="/privacy">Privacy Policy</a>.</p>
    </section>

    <section>
      <h2 class="font-outfit font-black text-xl text-slate-900 mb-2">Using our research</h2>
      <p>The reports, articles, data and other material on this site are published by CRTP unless stated otherwise. You are welcome to quote
        and share them for research, teaching, peacebuilding and public discussion, provided you credit CRTP and the Tafakari Digital Hub
        and link to the original. To republish material in full or use it commercially, please ask us first.</p>
    </section>

    <section>
      <h2 class="font-outfit font-black text-xl text-slate-900 mb-2">Read the scores and forecasts with care</h2>
      <ul class="list-disc pl-5 space-y-2">
        <li>Heat-map scores are CRTP&rsquo;s assessments of reported severity, made with the method set out on the
          <a class="underline font-semibold" style="color:#750B25" href="/methodology">Methodology</a> page. They are not measurements, and they depend on what was reported.</li>
        <li>Outlooks project recent trends forward. They cannot foresee events and are not predictions that violence will occur.</li>
        <li>The chat assistant uses AI and can make mistakes. Check its answers against the sources it links to.</li>
        <li>Nothing on this site is a basis for decisions about personal safety, travel or security operations. In an emergency, contact local emergency services.</li>
      </ul>
    </section>

    <section>
      <h2 class="font-outfit font-black text-xl text-slate-900 mb-2">Comments and messages</h2>
      <p>Comments are reviewed before they appear, and we may decline or remove any. Please do not post hate speech, threats, personal attacks,
        or anyone&rsquo;s personal information, including your own. Do not use the site or the chat assistant to harass anyone, to seek harmful
        information, or to try to break or misuse the service.</p>
    </section>

    <section>
      <h2 class="font-outfit font-black text-xl text-slate-900 mb-2">Links to other websites</h2>
      <p>We link to sources and services run by others so that you can check our work. We are not responsible for their content or their practices.</p>
    </section>

    <section>
      <h2 class="font-outfit font-black text-xl text-slate-900 mb-2">Accuracy and changes</h2>
      <p>We work to keep the site accurate and available, but we cannot promise that it is free of errors or always online. We may update
        material, these terms or the site itself at any time. If you see an error, please tell us.</p>
    </section>

    <section>
      <h2 class="font-outfit font-black text-xl text-slate-900 mb-2">Contact</h2>
      <p>Write to <a class="underline font-semibold" style="color:#750B25" href="mailto:crtp.communications@hekima.ac.ke">crtp.communications@hekima.ac.ke</a>.</p>
    </section>
  </article>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
