<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/partners.php';

$partners = [];
try { $partners = partners_public(true); } catch (Throwable $e) {}
$featured  = array_values(array_filter($partners, fn($p) => $p['featured']));
$typeCount = [];
foreach ($partners as $p) $typeCount[$p['category']] = ($typeCount[$p['category']] ?? 0) + 1;
$countries = array_unique(array_filter(array_column($partners, 'country'), fn($c) => $c !== '' && $c !== 'International'));

$pageTitle = 'Partners & Network Members | Tafakari Digital Hub';
$pageDesc  = 'The academic, faith-based, civil-society, regional and international organisations CRTP works with to advance peace, research and dialogue across Africa.';
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex flex-col font-inter" style="background:var(--tk-cream)">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<style>
.pp-feat { display:grid; grid-template-columns:repeat(auto-fit,minmax(340px,1fr)); gap:18px; }
.pp-spot { padding:26px; display:flex; flex-direction:column; gap:16px; }
.pp-spot__top { display:flex; gap:18px; align-items:center; }
.pp-spot__type { font:800 10.5px/1 var(--tk-font); letter-spacing:.14em; text-transform:uppercase; color:var(--tk-amber-700); }
.pp-spot h3 { font:800 21px/1.2 var(--tk-font-display); color:var(--tk-text); margin:8px 0 0; }
.pp-spot p.sub { font:500 13px/1.5 var(--tk-font); color:var(--tk-text-3); margin:4px 0 0; }
.pp-spot p.desc { font:400 14.5px/1.7 var(--tk-font); color:var(--tk-text-2); margin:0; }
.pp-spot__foot { display:flex; align-items:center; justify-content:space-between; gap:12px; padding-top:16px; border-top:1px solid var(--tk-line); margin-top:auto; }
.pp-filters { display:flex; flex-wrap:wrap; align-items:center; gap:8px; }
.pp-filter { height:36px; padding:0 14px; border-radius:999px; border:1px solid var(--tk-line-2); background:var(--tk-surface); cursor:pointer;
             font:700 13px/1 var(--tk-font); color:var(--tk-text-2); display:inline-flex; align-items:center; gap:7px; transition:all .15s; }
.pp-filter:hover { border-color:var(--tk-brand); color:var(--tk-brand); }
.pp-filter.is-active { background:var(--tk-brand); border-color:var(--tk-brand); color:#fff; }
.pp-filter span { font-weight:600; opacity:.7; }
.pp-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(270px,1fr)); gap:16px; }
.pp-card { padding:22px; display:flex; flex-direction:column; gap:14px; text-decoration:none; }
.pp-card h3 { font:700 16.5px/1.3 var(--tk-font-display); color:var(--tk-text); margin:0; }
.pp-card p.sub { font:500 12.5px/1.45 var(--tk-font); color:var(--tk-text-3); margin:4px 0 0; }
.pp-card p.desc { font:400 13.5px/1.65 var(--tk-font); color:var(--tk-text-2); margin:0; }
.pp-card__meta { display:flex; flex-wrap:wrap; gap:6px; margin-top:auto; }
.pp-card__foot { padding-top:12px; border-top:1px solid var(--tk-line); }
.pp-visit { display:inline-flex; align-items:center; gap:6px; font:700 13px/1 var(--tk-font); color:var(--tk-brand); text-decoration:none; }
.pp-visit:hover { text-decoration:underline; }
.pp-visit svg { width:14px; height:14px; }
.pp-cta { background:var(--tk-ink); color:#fff; border-radius:var(--tk-r-xl); padding:40px; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:24px; }
.pp-cta h2 { font:900 clamp(24px,3vw,32px)/1.15 var(--tk-font-display); margin:12px 0 0; }
.pp-cta p { color:rgba(255,255,255,.7); font:400 15px/1.6 var(--tk-font); margin:10px 0 0; max-width:56ch; }
[hidden] { display:none !important; }
</style>

<main class="grow">
  <section class="tk-hero">
    <div class="tk-hero__rings" aria-hidden="true"></div>
    <div class="tk-hero__inner">
      <span class="tk-eyebrow tk-eyebrow--light">Our network</span>
      <h1 class="tk-hero__title">Partners &amp; Network Members</h1>
      <p class="tk-hero__lead">CRTP works alongside academic institutions, faith communities, civil society and regional and international
        organisations to turn research and reflection into peace, justice and better decisions across Africa.</p>
      <?php if ($partners): ?>
        <div class="tk-hero__meta">
          <div><strong><?= count($partners) ?></strong><span>Partner organisations</span></div>
          <div><strong><?= count($typeCount) ?></strong><span>Kinds of partner</span></div>
          <?php if ($countries): ?><div><strong><?= count($countries) ?></strong><span>Countries</span></div><?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <?php if (!$partners): ?>
    <section class="tk-section"><div class="tk-container">
      <div class="tk-card tk-empty">
        <h2 class="tk-h2">Our partner directory is being prepared</h2>
        <p class="tk-lead" style="margin:10px auto 22px;font-size:14px">In the meantime, read about CRTP and our work, or get in touch about partnering with us.</p>
        <a href="/about" class="tk-btn tk-btn--outline">About CRTP</a>
      </div>
    </div></section>
  <?php else: ?>

    <?php if ($featured): ?>
      <section class="tk-section" style="padding-bottom:12px">
        <div class="tk-container">
          <div class="tk-section__head">
            <div><span class="tk-eyebrow">Featured</span><h2 class="tk-h1">Strategic partners</h2></div>
          </div>
          <div class="pp-feat">
            <?php foreach ($featured as $p): $web = partner_safe_url($p['websiteUrl'] ?? ''); ?>
              <article class="tk-card tk-card--hover pp-spot">
                <div class="pp-spot__top">
                  <?= partner_logo_html($p, 'lg') ?>
                  <div style="min-width:0">
                    <span class="pp-spot__type"><?= h($p['typeLabel']) ?></span>
                    <h3><?= h($p['name']) ?></h3>
                    <?php if (!empty($p['subtitle'])): ?><p class="sub"><?= h($p['subtitle']) ?></p><?php endif; ?>
                  </div>
                </div>
                <?php if (!empty($p['description'])): ?><p class="desc"><?= h($p['description']) ?></p><?php endif; ?>
                <div class="pp-spot__foot">
                  <span class="tk-muted" style="font:600 12.5px var(--tk-font)"><?= h(implode(' · ', array_filter([$p['country'], $p['partnerSince'] ? 'Partner since ' . $p['partnerSince'] : '']))) ?></span>
                  <?php if ($web): ?><a class="pp-visit" href="<?= h($web) ?>" target="_blank" rel="noopener noreferrer">Visit <?= h(partner_host($web)) ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17L17 7M8 7h9v9"/></svg></a><?php endif; ?>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <section class="tk-section">
      <div class="tk-container">
        <div class="tk-section__head">
          <div><span class="tk-eyebrow">Directory</span><h2 class="tk-h1">All partners</h2></div>
          <label class="tk-input-icon" style="flex:0 1 320px">
            <span style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)">Search partners</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
            <input type="search" id="pp-q" class="tk-input" placeholder="Search partners" oninput="ppFilter()">
          </label>
        </div>

        <?php if (count($typeCount) > 1): ?>
          <div class="pp-filters" role="group" aria-label="Filter by type" style="margin-bottom:20px">
            <button type="button" class="pp-filter is-active" data-type="" onclick="ppType(this)" aria-pressed="true">All <span><?= count($partners) ?></span></button>
            <?php foreach (partner_types() as $k => $l): if (empty($typeCount[$k])) continue; ?>
              <button type="button" class="pp-filter" data-type="<?= h($k) ?>" onclick="ppType(this)" aria-pressed="false"><?= h($l) ?> <span><?= (int) $typeCount[$k] ?></span></button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="pp-grid" id="pp-grid">
          <?php foreach ($partners as $p): $web = partner_safe_url($p['websiteUrl'] ?? ''); ?>
            <article class="tk-card tk-card--hover pp-card" data-type="<?= h($p['category']) ?>"
                     data-search="<?= h(mb_strtolower($p['name'] . ' ' . ($p['subtitle'] ?? '') . ' ' . $p['country'] . ' ' . $p['typeLabel'])) ?>">
              <div style="display:flex;gap:14px;align-items:center">
                <?= partner_logo_html($p) ?>
                <div style="min-width:0">
                  <h3><?= h($p['name']) ?></h3>
                  <?php if (!empty($p['subtitle'])): ?><p class="sub tk-clamp-2"><?= h($p['subtitle']) ?></p><?php endif; ?>
                </div>
              </div>
              <?php if (!empty($p['description'])): ?><p class="desc tk-clamp-3"><?= h($p['description']) ?></p><?php endif; ?>
              <div class="pp-card__meta">
                <span class="tk-chip"><?= h($p['typeLabel']) ?></span>
                <?php if ($p['country'] !== ''): ?><span class="tk-chip"><?= h($p['country']) ?></span><?php endif; ?>
              </div>
              <?php if ($web): ?>
                <div class="pp-card__foot">
                  <a class="pp-visit" href="<?= h($web) ?>" target="_blank" rel="noopener noreferrer" aria-label="Visit the <?= h($p['name']) ?> website"><?= h(partner_host($web)) ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17L17 7M8 7h9v9"/></svg></a>
                </div>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
        <p id="pp-none" class="tk-lead" hidden style="text-align:center;margin:28px auto 0">No partners match your search.</p>
      </div>
    </section>
  <?php endif; ?>

  <section class="tk-section" style="padding-top:8px;padding-bottom:72px">
    <div class="tk-container">
      <div class="pp-cta">
        <div>
          <span class="tk-eyebrow tk-eyebrow--light">Work with us</span>
          <h2>Become a partner</h2>
          <p>Research institutions, faith communities, civil-society organisations and agencies working for peace in Africa are welcome to join the network.</p>
        </div>
        <a href="/contact" class="tk-btn tk-btn--accent" style="height:48px;padding:0 24px">Get in touch</a>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script>
(function () {
  var type = '';
  window.ppFilter = function () {
    var q = (document.getElementById('pp-q') || {}).value || '';
    q = q.trim().toLowerCase();
    var n = 0;
    document.querySelectorAll('#pp-grid [data-type]').forEach(function (el) {
      var ok = (!type || el.dataset.type === type) && (!q || el.dataset.search.indexOf(q) !== -1);
      el.hidden = !ok; if (ok) n++;
    });
    var none = document.getElementById('pp-none'); if (none) none.hidden = n !== 0;
  };
  window.ppType = function (btn) {
    document.querySelectorAll('.pp-filter').forEach(function (b) { var on = b === btn; b.classList.toggle('is-active', on); b.setAttribute('aria-pressed', on ? 'true' : 'false'); });
    type = btn.dataset.type;
    ppFilter();
  };
})();
</script>
</body>
</html>
