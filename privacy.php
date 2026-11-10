<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Privacy Policy | Tafakari Digital Hub';
$pageDesc  = 'How the Tafakari Digital Hub, run by CRTP at Hekima University College, handles personal information.';
$updated   = '10 November 2026';
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex flex-col font-inter" style="background:#F8F8F0">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="grow">
  <div style="background:#750B25" class="text-white px-4 md:px-6 py-10 md:py-12">
    <div class="max-w-3xl mx-auto">
      <p class="text-[11px] font-black uppercase tracking-[.14em] mb-3" style="color:#E7952A">Tafakari Digital Hub</p>
      <h1 class="font-outfit font-black text-3xl md:text-5xl leading-tight">Privacy Policy</h1>
      <p class="mt-4 text-sm" style="color:rgba(255,255,255,.8)">Last updated <?= h($updated) ?></p>
    </div>
  </div>

  <article class="max-w-3xl mx-auto px-4 md:px-6 py-10 text-slate-700 leading-relaxed space-y-8">
    <section>
      <h2 class="font-outfit font-black text-xl text-slate-900 mb-2">Who we are</h2>
      <p>The Tafakari Digital Hub is run by the Centre for Research, Training and Publications (CRTP) at Hekima University College,
        Riara Road, off Ngong Road, Nairobi, Kenya. This policy explains what personal information the website handles and why.
        For any question about it, write to <a class="underline font-semibold" style="color:#750B25" href="mailto:crtp.communications@hekima.ac.ke">crtp.communications@hekima.ac.ke</a>.</p>
    </section>

    <section>
      <h2 class="font-outfit font-black text-xl text-slate-900 mb-2">What we collect, and why</h2>
      <ul class="list-disc pl-5 space-y-2">
        <li><strong>Contact form.</strong> Your name, email address, country, area of interest and message, so that we can reply to you.</li>
        <li><strong>Comments.</strong> Your name, an optional email address, an optional rating and your comment. Comments are reviewed before they appear, and your name and comment are then shown publicly. Your email address is never shown.</li>
        <li><strong>Staff accounts.</strong> CRTP staff who manage the site have accounts with a name, username, email address and an encrypted password.</li>
        <li><strong>Chat assistant.</strong> Your messages are sent to Anthropic, the provider of the Claude AI model, to produce a reply. We do not store the conversation on our server. To prevent misuse, we keep only counts of messages and a coded value derived from your internet address that changes every day and cannot be turned back into the address. The current conversation is kept in your own browser until you close the tab or choose &ldquo;Start over&rdquo;.</li>
        <li><strong>Technical logs.</strong> Like most websites, our hosting server keeps standard records of requests, such as internet addresses and pages visited, to keep the site running and secure.</li>
      </ul>
      <p class="mt-3">We do not sell personal information, and we do not use advertising or tracking services.</p>
    </section>

    <section>
      <h2 class="font-outfit font-black text-xl text-slate-900 mb-2">Cookies and browser storage</h2>
      <p>The site sets one session cookie, which keeps forms secure and keeps staff signed in. Your browser also stores your language choice
        and, while the tab is open, the chat conversation. None of these is used to track you across other websites.</p>
    </section>

    <section>
      <h2 class="font-outfit font-black text-xl text-slate-900 mb-2">Services from other providers</h2>
      <p>To show its pages, the site loads some content from other providers, which receive your internet address when it does:</p>
      <ul class="list-disc pl-5 space-y-2 mt-2">
        <li>Google Fonts, for the typefaces.</li>
        <li>OpenStreetMap, for the map tiles on the heat map.</li>
        <li>jsDelivr, cdnjs and Tailwind, for code libraries the pages use.</li>
        <li>YouTube and Vimeo, when you play an embedded video. These services may set their own cookies.</li>
        <li>Anthropic, for the chat assistant, as described above.</li>
      </ul>
      <p class="mt-3">Links to our social media pages take you to those services, whose own privacy policies apply.</p>
    </section>

    <section>
      <h2 class="font-outfit font-black text-xl text-slate-900 mb-2">How published content is prepared</h2>
      <p>Some articles, monitoring findings and video selections are prepared with the help of AI from public sources. A member of CRTP's team
        reviews and approves each one before it is published, and AI-assisted items say so.</p>
    </section>

    <section>
      <h2 class="font-outfit font-black text-xl text-slate-900 mb-2">How long we keep information</h2>
      <p>We keep messages, comments and account details only for as long as we need them for the purpose they were given for, and for our records.
        You can ask us to delete them sooner.</p>
    </section>

    <section>
      <h2 class="font-outfit font-black text-xl text-slate-900 mb-2">Your rights</h2>
      <p>You may ask to see the personal information we hold about you, to correct it, or to have it deleted, and you have the other rights
        given by applicable law, including Kenya&rsquo;s Data Protection Act, 2019. Write to
        <a class="underline font-semibold" style="color:#750B25" href="mailto:crtp.communications@hekima.ac.ke">crtp.communications@hekima.ac.ke</a> and we will respond.</p>
    </section>

    <section>
      <h2 class="font-outfit font-black text-xl text-slate-900 mb-2">Changes to this policy</h2>
      <p>If we change this policy, we will update this page and the date at the top.</p>
    </section>
  </article>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
