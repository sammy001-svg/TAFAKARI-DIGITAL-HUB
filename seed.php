<?php
declare(strict_types=1);
/*
 * DEMO SEED FILE — Tafakari Digital Hub
 * ──────────────────────────────────────
 * Creates two demo admin accounts with default credentials.
 * DELETE this file or restrict access after first use.
 *
 * Access via browser: https://yourdomain.com/seed.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

$status  = '';
$seeded  = false;
$errors  = [];
$skipped = [];
$created = [];

// Demo accounts to seed
$demoAccounts = [
    [
        'role'     => 'SUPER_ADMIN',
        'name'     => 'Super Administrator',
        'username' => 'superadmin',
        'email'    => 'superadmin@tafakari.hub',
        'password' => 'Admin@Tafakari2024',
    ],
    [
        'role'     => 'ADMIN',
        'name'     => 'Demo Editor',
        'username' => 'editor',
        'email'    => 'editor@tafakari.hub',
        'password' => 'Editor@Tafakari2024',
    ],
];

$dbOk = false;
$tableExists = false;
$accountStatus = []; // username => ['exists'=>bool, 'passOk'=>bool|null, 'role'=>string|null]
try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $dbOk = true;
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $tableExists = in_array('User', $tables);
    if ($tableExists) {
        foreach ($demoAccounts as $acc) {
            $chk = $pdo->prepare('SELECT id, password, role FROM User WHERE username=? LIMIT 1');
            $chk->execute([$acc['username']]);
            $row = $chk->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $accountStatus[$acc['username']] = [
                    'exists' => true,
                    'passOk' => password_verify($acc['password'], $row['password']),
                    'role'   => $row['role'],
                ];
            } else {
                $accountStatus[$acc['username']] = ['exists' => false, 'passOk' => null, 'role' => null];
            }
        }
    }
} catch (PDOException $e) {
    $errors[] = 'Database connection failed: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seed']) && $dbOk && $tableExists) {
    foreach ($demoAccounts as $acc) {
        try {
            $exists = $pdo->prepare('SELECT id FROM User WHERE username=? OR email=? LIMIT 1');
            $exists->execute([$acc['username'], $acc['email']]);
            if ($exists->fetch()) {
                $skipped[] = $acc['username'];
                continue;
            }
            $id     = 'c' . bin2hex(random_bytes(12));
            $hashed = password_hash($acc['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare(
                'INSERT INTO User (id,name,username,email,password,role,createdAt,updatedAt)
                 VALUES (?,?,?,?,?,?,NOW(3),NOW(3))'
            )->execute([$id, $acc['name'], $acc['username'], $acc['email'], $hashed, $acc['role']]);
            $created[] = $acc['username'];
        } catch (PDOException $e) {
            $errors[] = 'Failed to create ' . $acc['username'] . ': ' . $e->getMessage();
        }
    }
    $seeded = true;
    // Refresh full diagnostic after seeding
    if ($tableExists) {
        foreach ($demoAccounts as $acc) {
            $chk = $pdo->prepare('SELECT id, password, role FROM User WHERE username=? LIMIT 1');
            $chk->execute([$acc['username']]);
            $row = $chk->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $accountStatus[$acc['username']] = [
                    'exists' => true,
                    'passOk' => password_verify($acc['password'], $row['password']),
                    'role'   => $row['role'],
                ];
            } else {
                $accountStatus[$acc['username']] = ['exists' => false, 'passOk' => null, 'role' => null];
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset']) && $dbOk && $tableExists) {
    // Reset passwords only
    foreach ($demoAccounts as $acc) {
        try {
            $hashed = password_hash($acc['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare('UPDATE User SET password=?, updatedAt=NOW(3) WHERE username=?')
                ->execute([$hashed, $acc['username']]);
        } catch (PDOException $e) {
            $errors[] = 'Failed to reset ' . $acc['username'];
        }
    }
    $status = 'reset';
    // Refresh diagnostic after reset
    if ($dbOk && $tableExists) {
        foreach ($demoAccounts as $acc) {
            $chk = $pdo->prepare('SELECT id, password, role FROM User WHERE username=? LIMIT 1');
            $chk->execute([$acc['username']]);
            $row = $chk->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $accountStatus[$acc['username']] = [
                    'exists' => true,
                    'passOk' => password_verify($acc['password'], $row['password']),
                    'role'   => $row['role'],
                ];
            } else {
                $accountStatus[$acc['username']] = ['exists' => false, 'passOk' => null, 'role' => null];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Demo Seed | Tafakari Digital Hub</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body { font-family:'Inter',sans-serif; }
    .font-outfit { font-family:'Outfit',sans-serif; }
    code { font-family: ui-monospace, monospace; }
  </style>
</head>
<body class="min-h-screen flex items-start justify-center py-12 px-4" style="background:#F4F6F8">

<div class="w-full max-w-2xl space-y-5">

  <!-- Header -->
  <div class="text-center mb-8">
    <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-white font-black text-xl mx-auto mb-4"
         style="background:#750B25">T</div>
    <h1 class="font-outfit font-black text-3xl text-slate-900">Demo Seed Tool</h1>
    <p class="text-slate-500 mt-2">Tafakari Digital Hub &mdash; Admin Account Setup</p>
  </div>

  <!-- Warning Banner -->
  <div class="rounded-2xl p-5 border" style="background:#FEF9EC;border-color:#E7952A">
    <div class="flex gap-3">
      <svg width="20" height="20" fill="none" stroke="#E7952A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" class="shrink-0 mt-0.5">
        <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
      </svg>
      <div>
        <p class="font-bold text-[13px]" style="color:#92400E">Security Warning</p>
        <p class="text-[12px] mt-1" style="color:#78350F">
          This file creates accounts with known default credentials.
          <strong>Delete <code>seed.php</code> from your server immediately after first use</strong>,
          or change all passwords via <a href="/admin/profile" style="color:#750B25" class="underline">Admin &rsaquo; Profile</a>.
        </p>
      </div>
    </div>
  </div>

  <!-- Status checks -->
  <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
    <h2 class="font-outfit font-bold text-[15px] text-slate-900 mb-4">System Status</h2>
    <div class="space-y-3">
      <div class="flex items-center justify-between py-2 border-b border-slate-50">
        <span class="text-[13px] text-slate-600">Database Connection</span>
        <?php if ($dbOk): ?>
          <span class="flex items-center gap-1.5 text-[12px] font-semibold text-emerald-700">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Connected
          </span>
        <?php else: ?>
          <span class="flex items-center gap-1.5 text-[12px] font-semibold text-rose-700">
            <span class="w-2 h-2 rounded-full bg-rose-500"></span> Failed
          </span>
        <?php endif; ?>
      </div>
      <div class="flex items-center justify-between py-2">
        <span class="text-[13px] text-slate-600">User Table</span>
        <?php if ($tableExists): ?>
          <span class="flex items-center gap-1.5 text-[12px] font-semibold text-emerald-700">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Exists
          </span>
        <?php else: ?>
          <span class="flex items-center gap-1.5 text-[12px] font-semibold text-amber-700">
            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
            Not found &mdash; run <a href="/setup.php" style="color:#750B25" class="underline">setup.php</a> first
          </span>
        <?php endif; ?>
      </div>
    </div>
    <?php if (!empty($errors)): ?>
      <div class="mt-4 p-4 bg-rose-50 border border-rose-200 rounded-xl">
        <?php foreach ($errors as $e): ?>
          <p class="text-rose-700 text-[12px]"><?= h($e) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Action prompt: clearly tell user what to do next -->
  <?php if ($dbOk && $tableExists && !$seeded):
      $allReady     = !empty($accountStatus) && !in_array(false, array_column($accountStatus,'exists'), true) && !in_array(false, array_column($accountStatus,'passOk'), true);
      $existsNotOk  = !empty($accountStatus) && !in_array(false, array_column($accountStatus,'exists'), true) && in_array(false, array_column($accountStatus,'passOk'), true);
      $someMissing  = empty($accountStatus) || in_array(false, array_column($accountStatus,'exists'), true);
  ?>
    <?php if ($someMissing): ?>
      <div class="rounded-2xl p-5 border" style="background:#EFF6FF;border-color:#BFDBFE">
        <p class="font-bold text-[13px] text-blue-800">
          Action required: Click <strong>"Seed Demo Accounts"</strong> below to create the login accounts.
        </p>
        <p class="text-[11px] text-blue-700 mt-1">
          The credentials shown on the login page won't work until you seed them here.
        </p>
      </div>
    <?php elseif ($existsNotOk): ?>
      <div class="rounded-2xl p-5 border" style="background:#FEF2F2;border-color:#FECACA">
        <p class="font-bold text-[13px] text-rose-800">
          Accounts exist but the passwords don't match the demo credentials.
        </p>
        <p class="text-[11px] text-rose-700 mt-1">
          Click <strong>"Reset Passwords to Defaults"</strong> below to fix this.
        </p>
      </div>
    <?php elseif ($allReady): ?>
      <div class="rounded-2xl p-5 border" style="background:#F0FDF4;border-color:#BBF7D0">
        <p class="font-bold text-[13px] text-emerald-800">
          Both accounts exist and passwords are verified. You should be able to log in now.
        </p>
        <p class="text-[11px] text-emerald-700 mt-1">
          If login still fails, check that your browser is submitting to <code>/admin/login</code> or <code>/login</code> and that cookies are not blocked.
        </p>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <!-- Credentials Preview -->
  <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100">
      <h2 class="font-outfit font-bold text-[15px] text-slate-900">Demo Credentials</h2>
      <p class="text-[11px] text-slate-400 mt-0.5">These will be created (or already exist if previously seeded).</p>
    </div>
    <div class="divide-y divide-slate-50">
      <?php foreach ($demoAccounts as $acc):
          $diag   = $accountStatus[$acc['username']] ?? ['exists'=>false,'passOk'=>null,'role'=>null];
          $exists = $diag['exists'];
          $passOk = $diag['passOk']; ?>
        <div class="px-6 py-5">
          <div class="flex items-start justify-between gap-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-black shrink-0"
                   style="background:<?= $acc['role'] === 'SUPER_ADMIN' ? '#750B25' : '#475569' ?>">
                <?= strtoupper(substr($acc['name'], 0, 1)) ?>
              </div>
              <div>
                <p class="font-semibold text-[13px] text-slate-900"><?= h($acc['name']) ?></p>
                <span class="inline-block text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full mt-0.5 <?= $acc['role'] === 'SUPER_ADMIN' ? 'bg-rose-50 text-rose-600' : 'bg-slate-100 text-slate-600' ?>">
                  <?= h(str_replace('_', ' ', $acc['role'])) ?>
                </span>
              </div>
            </div>
            <?php if ($tableExists): ?>
              <div class="flex flex-col items-end gap-1.5 shrink-0 text-right">
                <?php if ($exists): ?>
                  <span class="flex items-center gap-1 text-[11px] font-bold text-emerald-700">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Account exists
                  </span>
                  <?php if ($passOk): ?>
                    <span class="flex items-center gap-1 text-[11px] font-bold text-emerald-700">
                      <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Password OK &mdash; login ready
                    </span>
                  <?php else: ?>
                    <span class="flex items-center gap-1 text-[11px] font-bold text-rose-700">
                      <span class="w-2 h-2 rounded-full bg-rose-500"></span> Password mismatch &mdash; click Reset
                    </span>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="flex items-center gap-1 text-[11px] font-bold text-amber-700">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span> Not created yet
                  </span>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
          <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="bg-slate-50 rounded-xl px-4 py-3">
              <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Username</p>
              <code class="text-[13px] font-bold text-slate-900"><?= h($acc['username']) ?></code>
            </div>
            <div class="bg-slate-50 rounded-xl px-4 py-3">
              <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Password</p>
              <code class="text-[13px] font-bold text-slate-900"><?= h($acc['password']) ?></code>
            </div>
            <div class="bg-slate-50 rounded-xl px-4 py-3">
              <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Email</p>
              <code class="text-[11px] font-semibold text-slate-700"><?= h($acc['email']) ?></code>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Result after seeding -->
  <?php if ($seeded): ?>
    <div class="rounded-2xl p-5 <?= empty($errors) ? 'bg-emerald-50 border border-emerald-200' : 'bg-amber-50 border border-amber-200' ?>">
      <?php if (!empty($created)): ?>
        <p class="font-bold text-emerald-800 text-[13px]">
          ✓ Created: <?= implode(', ', array_map('h', $created)) ?>
        </p>
      <?php endif; ?>
      <?php if (!empty($skipped)): ?>
        <p class="font-bold text-amber-800 text-[13px] mt-1">
          Skipped (already exist): <?= implode(', ', array_map('h', $skipped)) ?>
        </p>
      <?php endif; ?>
      <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $e): ?>
          <p class="text-rose-700 text-[12px] mt-1"><?= h($e) ?></p>
        <?php endforeach; ?>
      <?php endif; ?>
      <?php if (!empty($created)): ?>
        <a href="/login" class="inline-flex items-center gap-2 mt-4 px-5 py-2.5 rounded-xl text-[12px] font-bold text-white"
           style="background:#750B25">
          Go to Admin Login &rarr;
        </a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($status === 'reset'): ?>
    <div class="rounded-2xl p-5 bg-emerald-50 border border-emerald-200">
      <p class="font-bold text-emerald-800 text-[13px]">✓ Passwords reset to defaults.</p>
    </div>
  <?php endif; ?>

  <!-- Actions -->
  <?php if ($dbOk && $tableExists): ?>
  <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 flex flex-col sm:flex-row gap-3">
    <form method="POST" class="flex-1">
      <button type="submit" name="seed" value="1"
              class="w-full py-3 rounded-xl font-bold text-[13px] text-white transition-all hover:opacity-90"
              style="background:#750B25">
        Seed Demo Accounts
      </button>
    </form>
    <form method="POST" class="flex-1">
      <button type="submit" name="reset" value="1"
              class="w-full py-3 rounded-xl font-bold text-[13px] text-slate-700 border border-slate-200 hover:bg-slate-50 transition-colors">
        Reset Passwords to Defaults
      </button>
    </form>
    <a href="/login"
       class="flex-1 flex items-center justify-center py-3 rounded-xl text-[13px] font-bold text-slate-600 bg-slate-50 hover:bg-slate-100 transition-colors">
      Go to Login
    </a>
  </div>
  <?php endif; ?>

  <!-- Footer note -->
  <p class="text-center text-[11px] text-slate-400 pb-8">
    After logging in, change credentials at
    <a href="/admin/profile" style="color:#750B25" class="font-semibold hover:underline">Admin &rsaquo; My Profile</a>.
    Then delete this file from your server.
  </p>

</div>
</body>
</html>
