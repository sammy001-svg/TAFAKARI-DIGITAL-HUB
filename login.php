<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

// ── Session bootstrap (config.php starts the session, but ensure it's active) ──
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Already logged in
if (isset($_SESSION['user']['id'])) {
    header('Location: /admin/dashboard');
    exit;
}

// ── Process login inline — no dependency on auth.php or db.php ──
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';      // do NOT trim passwords

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $loginOk  = false;
        $dbFailed = false;
        try {
            $pdo = new PDO(
                DB_DSN, DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
            $stmt = $pdo->prepare(
                'SELECT id, name, email, username, password, role FROM User WHERE username = ? LIMIT 1'
            );
            $stmt->execute([$username]);
            $row = $stmt->fetch();

            if ($row && password_verify($password, $row['password'])) {
                unset($row['password']);
                session_regenerate_id(true);
                $_SESSION['user'] = $row;
                header('Location: /admin/dashboard');
                exit;
            }
        } catch (\PDOException $e) {
            $dbFailed = true;
            error_log('[Tafakari login] DB error: ' . $e->getMessage());
        }

        if ($dbFailed) {
            $error = 'Database connection failed. Please check config.php on the server.';
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

$pageTitle = 'Login | Tafakari Digital Hub';
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex font-inter" style="background:#0D0102">

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
          <span class="inline-flex mb-3 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest w-fit" style="background:#E7952A;color:#020617"><?= h($s['tag']) ?></span>
          <h2 class="font-outfit font-black text-3xl text-white mb-3"><?= h($s['title']) ?></h2>
          <p class="text-white/70 text-sm"><?= h($s['sub']) ?></p>
        </div>
      </div>
    <?php endforeach; ?>
    <div class="absolute bottom-8 left-12 flex gap-2 z-10">
      <?php foreach ($slides as $i => $_): ?>
        <button class="login-dot w-2 h-2 rounded-full transition-all <?= $i === 0 ? 'w-6 bg-secondary' : 'bg-white/40' ?>" data-index="<?= $i ?>"></button>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Right: Login form -->
  <div class="flex-1 flex flex-col items-center justify-center px-8 py-12 min-h-screen" style="background:#0D0102">
    <div class="w-full max-w-md">

      <!-- Logo -->
      <div class="mb-10 flex flex-col items-center gap-4">
        <div class="bg-white rounded-2xl px-5 py-3 shadow-xl">
          <img src="/public/crtp-logo.jpg"
               alt="Centre For Research Training and Publications — CRTP"
               class="h-12 w-auto object-contain block"
               onerror="this.parentElement.innerHTML='<span class=\'font-outfit font-black text-xl text-[#750B25] tracking-tight px-2\'>CRTP</span>'">
        </div>
        <div class="text-center">
          <div class="font-outfit font-bold text-base text-white/80 uppercase tracking-[.18em] text-[11px]">Admin Portal</div>
        </div>
      </div>

      <h1 class="font-outfit text-2xl font-bold text-white mb-2">Welcome back</h1>
      <p class="text-slate-400 text-sm mb-8">Sign in to your editorial account.</p>

      <?php if ($error): ?>
        <div class="mb-6 p-4 rounded-xl border text-sm" style="background:rgba(239,68,68,.1);border-color:rgba(239,68,68,.3);color:#fca5a5">
          <?= h($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="/login" class="space-y-5">
        <div>
          <label class="block text-xs font-black uppercase tracking-widest mb-2 text-slate-400">Username</label>
          <input type="text" name="username" required autofocus
                 value="<?= h($_POST['username'] ?? '') ?>"
                 class="w-full px-4 py-4 rounded-2xl text-white text-sm focus:outline-none focus:ring-2"
                 style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);outline-color:#E7952A"
                 placeholder="Enter your username">
        </div>
        <div>
          <label class="block text-xs font-black uppercase tracking-widest mb-2 text-slate-400">Password</label>
          <input type="password" name="password" required
                 class="w-full px-4 py-4 rounded-2xl text-white text-sm focus:outline-none focus:ring-2"
                 style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);outline-color:#E7952A"
                 placeholder="••••••••">
        </div>
        <button type="submit"
                class="w-full py-4 text-base font-bold rounded-2xl mt-2 transition-all hover:brightness-110"
                style="background:#E7952A;color:#0D0102">
          Sign In
        </button>
      </form>

      <p class="text-center text-xs text-slate-600 mt-8">
        <a href="/" class="hover:text-white transition-colors">&larr; Back to public site</a>
        &nbsp;&nbsp;·&nbsp;&nbsp;
        <a href="/reset-admin" class="hover:text-white transition-colors" style="color:rgba(231,149,42,.6)">Account Setup</a>
      </p>
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
