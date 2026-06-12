<nav class="sticky top-0 z-50 text-white shadow-xl px-6 md:px-12 py-4 border-b border-white/10" style="background:#750B25">
  <div class="flex items-center justify-between max-w-7xl mx-auto w-full">

    <!-- Logo -->
    <a href="/" class="flex items-center no-underline group" aria-label="CRTP — Centre For Research Training and Publications">
      <div class="bg-white rounded-xl px-3 py-1.5 shadow-md group-hover:shadow-lg transition-shadow duration-200">
        <img src="/public/crtp-logo.png"
             alt="Centre For Research Training and Publications — CRTP"
             class="h-10 md:h-11 w-auto object-contain block"
             onerror="this.parentElement.innerHTML='<span class=\'font-outfit font-black text-lg text-[#750B25] tracking-tight px-1\'>CRTP</span>'">
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
        <a href="<?= h($href) ?>"
           class="transition-colors tracking-wide <?= $active ? 'font-bold pb-1' : 'hover:text-secondary' ?>"
           <?= $active ? 'style="color:#ED1C24;border-bottom:2px solid #ED1C24"' : '' ?>>
          <?= h($label) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Desktop CTA -->
    <div class="hidden md:flex items-center gap-3">
      <!-- Search icon button -->
      <a href="/search" aria-label="Search" class="w-9 h-9 flex items-center justify-center rounded-full text-white/70 hover:text-white hover:bg-white/10 transition-all" title="Search">
        <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      </a>
      <a href="/admin/login" class="text-sm font-bold text-slate-900 px-4 py-2.5 rounded-full transition-all hover:brightness-110" style="background:#E7952A">
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
    <a href="/search" class="text-base font-semibold hover:text-secondary transition-colors py-1.5 flex items-center gap-2">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      Search
    </a>
    <div class="flex gap-3 mt-3 pt-3 border-t border-white/20">
      <a href="/admin/login" class="flex-1 text-center text-sm font-bold text-slate-900 px-4 py-2.5 rounded-full" style="background:#E7952A">Login</a>
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
