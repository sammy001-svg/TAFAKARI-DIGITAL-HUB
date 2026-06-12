<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Already logged in → go to dashboard
if (is_logged_in()) {
    header('Location: /admin/dashboard');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if (auth_login($username, $password)) {
        header('Location: /admin/dashboard');
        exit;
    }
    $error = 'Invalid username or password.';
}

$pageTitle = 'Login | Tafakari Digital Hub';
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex font-inter" style="background:#1F0404">

<div class="flex w-full min-h-screen">
  <!-- Left: Marketing carousel -->
  <div class="hidden lg:flex flex-col w-1/2 relative overflow-hidden">
    <?php
    $slides = [
      ['img' => '/public/nairobi_sunset_hero.png',     'tag' => 'Kenya',    'title' => 'Tracking What Matters',           'sub' => 'Real-time issue mapping across all 47 counties.'],
      ['img' => '/public/ethiopia_community_hero.png', 'tag' => 'Ethiopia', 'title' => 'Community Knowledge Platform',    'sub' => 'Field reports from 11 regional states.'],
      ['img' => '/public/drc_nature_hero.png',         'tag' => 'DRC',      'title' => 'Voices from the Congo',           'sub' => 'Documenting resilience across 26 provinces.'],
      ['img' => '/public/gallery_nairobi.png',         'tag' => 'Platform', 'title' => 'Your Editorial Voice Matters',   'sub' => 'Create, submit, and publish impactful content.'],
    ];
    foreach ($slides as $i => $s): ?>
      <div class="login-slide absolute inset-0 transition-opacity duration-700 <?= $i === 0 ? 'opacity-100' : 'opacity-0' ?>">
        <img src="<?= h($s['img']) ?>" alt="" class="w-full h-full object-cover">
        <div class="absolute inset-0" style="background:rgba(0,0,0,.6)"></div>
        <div class="absolute inset-0 flex flex-col justify-end pb-16 px-12">
          <span class="inline-flex mb-3 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest w-fit" style="background:#D99F51;color:#020617"><?= h($s['tag']) ?></span>
          <h2 class="font-outfit font-black text-3xl text-white mb-3"><?= h($s['title']) ?></h2>
          <p class="text-white/70 text-sm"><?= h($s['sub']) ?></p>
        </div>
      </div>
    <?php endforeach; ?>
    <!-- Dots -->
    <div class="absolute bottom-8 left-12 flex gap-2 z-10">
      <?php foreach ($slides as $i => $_): ?>
        <button class="login-dot w-2 h-2 rounded-full transition-all <?= $i === 0 ? 'w-6 bg-secondary' : 'bg-white/40' ?>" data-index="<?= $i ?>"></button>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Right: Login form -->
  <div class="flex-1 flex flex-col items-center justify-center px-8 py-12 min-h-screen" style="background:#1F0404">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="flex items-center gap-3 mb-12">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-white font-black text-2xl shadow-lg" style="background:#9A1415">T</div>
        <div>
          <div class="font-outfit font-bold text-xl text-white leading-tight">Admin Portal</div>
          <div class="text-[10px] text-slate-500 uppercase tracking-widest">Tafakari Digital Hub</div>
        </div>
      </div>

      <h1 class="font-outfit text-2xl font-bold text-white mb-2">Welcome back</h1>
      <p class="text-slate-400 text-sm mb-8">Sign in to your editorial account.</p>

      <?php if ($error): ?>
        <div class="mb-6 p-4 rounded-xl border text-sm" style="background:rgba(239,68,68,.1);border-color:rgba(239,68,68,.3);color:#fca5a5">
          <?= h($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="space-y-5">
        <div>
          <label class="block text-xs font-black uppercase tracking-widest mb-2 text-slate-400">Username</label>
          <input type="text" name="username" required autofocus value="<?= h($_POST['username'] ?? '') ?>"
                 class="w-full px-4 py-4 rounded-2xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                 style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1)"
                 placeholder="Enter your username">
        </div>
        <div>
          <label class="block text-xs font-black uppercase tracking-widest mb-2 text-slate-400">Password</label>
          <input type="password" name="password" required
                 class="w-full px-4 py-4 rounded-2xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                 style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1)"
                 placeholder="••••••••">
        </div>
        <button type="submit" class="btn-primary w-full py-4 text-base mt-2">Sign In</button>
      </form>

      <div class="mt-8 space-y-3">
        <p class="text-center text-xs text-slate-600">
          <a href="/" class="hover:text-white transition-colors">&larr; Back to public site</a>
        </p>
        <!-- Demo credentials hint (remove this block in production) -->
        <div class="rounded-xl p-4 border" style="background:rgba(217,159,81,.06);border-color:rgba(217,159,81,.25)">
          <p class="text-[9px] font-black uppercase tracking-[.14em] mb-2.5" style="color:rgba(217,159,81,.7)">Demo Login Credentials</p>
          <div class="space-y-1.5">
            <div class="flex justify-between items-center">
              <span class="text-[10px] text-slate-400 font-medium">Super Admin</span>
              <code class="text-[10px] font-bold text-white/70">superadmin / Admin@Tafakari2024</code>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-[10px] text-slate-400 font-medium">Editor</span>
              <code class="text-[10px] font-bold text-white/70">editor / Editor@Tafakari2024</code>
            </div>
          </div>
          <p class="text-[9px] text-slate-600 mt-2.5">
            Run <a href="/seed.php" class="underline" style="color:rgba(217,159,81,.8)">seed.php</a> first to create these accounts.
            Change passwords in <a href="/admin/profile" class="underline" style="color:rgba(217,159,81,.8)">Admin &rsaquo; Profile</a>.
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  var slides = document.querySelectorAll('.login-slide');
  var dots   = document.querySelectorAll('.login-dot');
  if (!slides.length) return;
  var cur = 0;
  function go(n) {
    slides[cur].classList.remove('opacity-100'); slides[cur].classList.add('opacity-0');
    dots[cur].classList.remove('w-6','bg-secondary'); dots[cur].classList.add('bg-white/40');
    cur = n % slides.length;
    slides[cur].classList.remove('opacity-0'); slides[cur].classList.add('opacity-100');
    dots[cur].classList.remove('bg-white/40'); dots[cur].classList.add('w-6','bg-secondary');
  }
  dots.forEach(function(d){ d.addEventListener('click', function(){ go(+this.dataset.index); }); });
  setInterval(function(){ go(cur+1); }, 4000);
})();
</script>
</body>
</html>
