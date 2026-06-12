<nav class="sticky top-0 z-50 text-white shadow-xl px-6 md:px-12 py-4 border-b border-secondary/20" style="background:rgba(154,20,21,.97)">
  <div class="flex items-center justify-between max-w-7xl mx-auto w-full">

    <!-- Logo: CRTP logo image, falls back to styled wordmark -->
    <a href="/" class="flex items-center gap-3 no-underline text-white">
      <img src="/public/crtp-logo.png" alt="CRTP Logo"
           class="h-11 w-auto object-contain"
           onerror="this.style.display='none';document.getElementById('nav-logo-fallback').style.display='flex'">
      <div id="nav-logo-fallback" class="items-center gap-2" style="display:none">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-lg border border-white/20" style="background:rgba(255,255,255,.1)">C</div>
        <span class="font-outfit font-bold text-xl tracking-tight hidden md:block">CRTP</span>
      </div>
      <div class="hidden md:flex flex-col leading-tight ml-1">
        <span class="font-outfit font-bold text-[1.1rem] tracking-tight leading-none">Tafakari Hub</span>
        <span class="text-[9px] uppercase tracking-[.15em] text-white/50 font-semibold mt-0.5">CRTP Knowledge Platform</span>
      </div>
    </a>

    <!-- Desktop Links -->
    <div class="hidden md:flex items-center gap-7 font-semibold text-base">
      <?php
      $navLinks = [
        ['/', 'Home'], ['/heatmap', 'Heatmap'], ['/gallery', 'Gallery'],
        ['/news', 'News'], ['/podcasts', 'Podcasts'], ['/videos', 'Videos'], ['/documents', 'Documents'],
      ];
      $currentPath = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
      foreach ($navLinks as [$href, $label]):
        $active = ($currentPath === $href);
      ?>
        <a href="<?= h($href) ?>" class="transition-colors tracking-wide <?= $active ? 'text-secondary font-bold border-b-2 border-secondary/70 pb-0.5' : 'hover:text-secondary' ?>">
          <?= h($label) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Desktop CTA -->
    <div class="hidden md:flex items-center gap-3">
      <a href="/admin/login" class="text-sm font-bold text-slate-900 px-4 py-2.5 rounded-full transition-all hover:brightness-110" style="background:#D99F51">
        Login
      </a>
      <a href="/contact" class="text-sm font-bold text-white px-5 py-2.5 rounded-full border-2 border-white/40 hover:border-white/80 hover:bg-white/10 transition-all">
        Get Involved
      </a>
    </div>

    <!-- Hamburger (mobile) -->
    <button id="nav-toggle" class="md:hidden flex flex-col gap-1.5 p-2 rounded-lg hover:bg-white/10 transition-colors" aria-label="Toggle menu">
      <span class="block w-6 h-0.5 bg-white transition-all duration-300" id="hb1"></span>
      <span class="block w-6 h-0.5 bg-white transition-all duration-300" id="hb2"></span>
      <span class="block w-6 h-0.5 bg-white transition-all duration-300" id="hb3"></span>
    </button>
  </div>

  <!-- Mobile menu -->
  <div id="nav-mobile" class="md:hidden hidden mt-4 border-t border-white/20 pt-4 flex-col gap-3 pb-4">
    <?php foreach ($navLinks as [$href, $label]): ?>
      <a href="<?= h($href) ?>" class="text-base font-semibold hover:text-secondary transition-colors py-1.5 block"><?= h($label) ?></a>
    <?php endforeach; ?>
    <div class="flex gap-3 mt-3 pt-3 border-t border-white/20">
      <a href="/admin/login" class="flex-1 text-center text-sm font-bold text-slate-900 px-4 py-2.5 rounded-full" style="background:#D99F51">Login</a>
      <a href="/contact" class="flex-1 text-center text-sm font-bold text-white px-4 py-2.5 rounded-full border-2 border-white/40">Get Involved</a>
    </div>
  </div>
</nav>
<script>
(function(){
  var btn=document.getElementById('nav-toggle');
  var menu=document.getElementById('nav-mobile');
  var h1=document.getElementById('hb1'),h2=document.getElementById('hb2'),h3=document.getElementById('hb3');
  var open=false;
  btn.addEventListener('click',function(){
    open=!open;
    menu.classList.toggle('hidden',!open);
    menu.classList.toggle('flex',open);
    h1.style.transform=open?'rotate(45deg) translateY(8px)':'';
    h2.style.opacity=open?'0':'1';
    h3.style.transform=open?'rotate(-45deg) translateY(-8px)':'';
  });
})();
</script>
