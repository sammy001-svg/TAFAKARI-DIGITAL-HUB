<nav class="sticky top-0 z-50 bg-primary/95 backdrop-blur-md text-white shadow-xl px-6 md:px-12 py-5 border-b border-secondary/20" style="background:rgba(154,20,21,.96)">
  <div class="flex items-center justify-between max-w-7xl mx-auto w-full">

    <!-- Logo -->
    <a href="/" class="flex items-center gap-2 no-underline text-white">
      <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-lg border border-white/10" style="background:#9A1415">T</div>
      <span class="font-outfit font-bold text-xl tracking-tight hidden md:block">Tafakari Hub</span>
    </a>

    <!-- Desktop Links -->
    <div class="hidden md:flex items-center gap-8 font-medium text-sm">
      <?php
      $navLinks = [
        ['/', 'Home'], ['/heatmap', 'Heatmap'], ['/gallery', 'Gallery'],
        ['/news', 'News'], ['/podcasts', 'Podcasts'], ['/videos', 'Videos'], ['/documents', 'Documents'],
      ];
      $currentPath = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
      foreach ($navLinks as [$href, $label]):
        $active = ($currentPath === $href);
      ?>
        <a href="<?= h($href) ?>" class="transition-colors <?= $active ? 'text-secondary font-bold' : 'hover:text-secondary' ?>">
          <?= h($label) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Desktop CTA -->
    <div class="hidden md:flex items-center gap-4">
      <a href="/admin/login" class="text-xs font-bold text-white px-4 py-2 rounded-lg transition-colors" style="background:rgba(217,159,81,.8)">
        Login
      </a>
      <a href="/contact" class="btn-primary py-2 px-5 text-sm">Get Involved</a>
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
      <a href="<?= h($href) ?>" class="text-sm font-medium hover:text-secondary transition-colors py-1 block"><?= h($label) ?></a>
    <?php endforeach; ?>
    <div class="flex gap-3 mt-2 pt-3 border-t border-white/20">
      <a href="/admin/login" class="flex-1 text-center text-xs font-bold text-white px-4 py-2 rounded-lg" style="background:rgba(217,159,81,.8)">Login</a>
      <a href="/contact" class="flex-1 text-center btn-primary py-2 px-4 text-xs">Get Involved</a>
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
