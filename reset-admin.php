<?php
declare(strict_types=1);
/*
 * Admin Account Setup — Tafakari Digital Hub
 * Use this page to create or reset your Super Admin account.
 * Restrict access or delete after setup is complete.
 */

// ── Bootstrap: only need config constants, not the full app ──────────────────
require_once __DIR__ . '/config.php';

$step    = 'check';   // check | form | done | error
$dbOk    = false;
$dbErr   = '';
$pdo     = null;
$users   = [];
$message = '';
$formErr = '';

// ── DB connection ─────────────────────────────────────────────────────────────
try {
    $pdo  = new PDO(DB_DSN, DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $dbOk = true;
} catch (\PDOException $e) {
    $dbErr = $e->getMessage();
}

// ── Read existing admins ──────────────────────────────────────────────────────
if ($dbOk) {
    try {
        $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
        if (in_array('User', $tables)) {
            $users = $pdo->query('SELECT id, name, username, email, role, createdAt FROM User ORDER BY createdAt ASC')->fetchAll();
        }
    } catch (\PDOException $e) { /* table may not exist yet */ }
}

// ── Handle POST: create / reset account ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $dbOk) {
    $name     = trim($_POST['name']     ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['confirm']       ?? '';
    $replace  = ($_POST['replace'] ?? '0') === '1';

    // Validate
    if (!$name)                      $formErr = 'Full name is required.';
    elseif (!$username)              $formErr = 'Username is required.';
    elseif (!preg_match('/^\w{3,32}$/', $username)) $formErr = 'Username must be 3–32 characters, letters/numbers/underscore only.';
    elseif (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $formErr = 'A valid email is required.';
    elseif (strlen($password) < 8)   $formErr = 'Password must be at least 8 characters.';
    elseif ($password !== $confirm)  $formErr = 'Passwords do not match.';
    else {
        try {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            // Verify the hash was created correctly before saving
            if (!password_verify($password, $hash)) {
                throw new \RuntimeException('Hash verification failed before saving. Contact your hosting provider about PHP bcrypt support.');
            }

            if ($replace) {
                // Remove any existing user with this username or email
                $pdo->prepare('DELETE FROM User WHERE username = ? OR email = ?')->execute([$username, $email]);
            }

            // Check if username/email already taken (without replace)
            if (!$replace) {
                $chk = $pdo->prepare('SELECT id FROM User WHERE username = ? OR email = ? LIMIT 1');
                $chk->execute([$username, $email]);
                if ($chk->fetch()) {
                    $formErr = 'Username or email already exists. Tick "Replace existing" to overwrite.';
                }
            }

            if (!$formErr) {
                $id = 'c' . bin2hex(random_bytes(12));
                $pdo->prepare(
                    'INSERT INTO User (id, name, username, email, password, role, createdAt, updatedAt)
                     VALUES (?, ?, ?, ?, ?, \'SUPER_ADMIN\', NOW(3), NOW(3))'
                )->execute([$id, $name, $username, $email, $hash]);

                // ── Auto-login ────────────────────────────────────────────────
                if (session_status() === PHP_SESSION_NONE) session_start();
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id'       => $id,
                    'name'     => $name,
                    'email'    => $email,
                    'username' => $username,
                    'role'     => 'SUPER_ADMIN',
                ];
                $message = $username;
                $step    = 'done';
            }
        } catch (\RuntimeException $e) {
            $formErr = $e->getMessage();
        } catch (\PDOException $e) {
            $formErr = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Account Setup | Tafakari Digital Hub</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@700;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<style>
  body { font-family: 'Inter', sans-serif; }
  .font-outfit { font-family: 'Outfit', sans-serif; }
  input:focus { outline: 2px solid #E7952A; outline-offset: 2px; }
</style>
</head>
<body class="antialiased min-h-screen" style="background:#F4F6F8">

<div class="max-w-xl mx-auto px-6 py-16">

  <!-- Header -->
  <div class="text-center mb-10">
    <div class="w-14 h-14 rounded-2xl mx-auto mb-5 flex items-center justify-center text-white font-black text-2xl shadow-lg"
         style="background:#750B25">T</div>
    <h1 class="font-outfit font-black text-3xl text-slate-900">Admin Account Setup</h1>
    <p class="text-slate-500 mt-2 text-sm">Tafakari Digital Hub &mdash; One-time account creation</p>
  </div>

  <!-- ────────────────────────────────────────────────────── SUCCESS ── -->
  <?php if ($step === 'done'): ?>
    <div class="bg-white rounded-3xl border border-emerald-200 shadow-sm p-10 text-center">
      <div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center mx-auto mb-6">
        <svg width="32" height="32" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24">
          <path d="M9 12l2 2 4-4M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
      <h2 class="font-outfit font-black text-2xl text-slate-900 mb-2">Account Created &amp; Logged In</h2>
      <p class="text-slate-500 text-sm mb-2">Super Admin account <strong><?= htmlspecialchars($message) ?></strong> created successfully.</p>
      <p class="text-slate-400 text-xs mb-8">You are now logged in. Head to your dashboard to get started.</p>
      <a href="/admin/dashboard"
         class="inline-block py-3.5 px-8 rounded-2xl font-bold text-slate-900 text-base transition-all hover:brightness-110"
         style="background:#E7952A">
        Open Admin Dashboard &rarr;
      </a>
      <p class="mt-8 text-xs text-rose-600 font-bold">
        Security: Delete or rename <code>reset-admin.php</code> on your server now that setup is complete.
      </p>
    </div>

  <!-- ────────────────────────────────────────────────────── DB ERROR ── -->
  <?php elseif (!$dbOk): ?>
    <div class="bg-white rounded-3xl border border-rose-200 shadow-sm p-8">
      <h2 class="font-outfit font-bold text-lg text-rose-700 mb-3">Database Connection Failed</h2>
      <div class="bg-rose-50 rounded-xl p-4 mb-5">
        <code class="text-xs text-rose-800 break-all"><?= htmlspecialchars($dbErr) ?></code>
      </div>
      <p class="text-sm text-slate-600">
        Open <code class="bg-slate-100 px-1.5 py-0.5 rounded text-xs">config.php</code> on your server
        (via cPanel File Manager) and update <strong>DB_NAME</strong>, <strong>DB_USER</strong>, and <strong>DB_PASS</strong>
        to your actual cPanel MySQL credentials, then reload this page.
      </p>
    </div>

  <!-- ────────────────────────────────────────────────────── FORM ── -->
  <?php else: ?>

    <!-- Existing accounts panel -->
    <?php if (!empty($users)): ?>
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 mb-6">
        <p class="text-xs font-black uppercase tracking-widest text-slate-400 mb-3">Existing Accounts (<?= count($users) ?>)</p>
        <div class="space-y-2">
          <?php foreach ($users as $u): ?>
            <div class="flex items-center justify-between py-2 border-b border-slate-50 last:border-0">
              <div>
                <span class="font-bold text-[13px] text-slate-800"><?= htmlspecialchars($u['username'] ?? '') ?></span>
                <span class="text-[11px] text-slate-400 ml-2"><?= htmlspecialchars($u['email'] ?? '') ?></span>
              </div>
              <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full <?= ($u['role'] ?? '') === 'SUPER_ADMIN' ? 'bg-rose-50 text-rose-600' : 'bg-slate-100 text-slate-600' ?>">
                <?= htmlspecialchars(str_replace('_', ' ', $u['role'] ?? '')) ?>
              </span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php else: ?>
      <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 mb-6 text-sm text-amber-800">
        <strong>No accounts found.</strong> Fill in the form below to create your Super Admin account.
        <?php if (!in_array('User', $pdo ? $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN) : [])): ?>
          <br><strong>Note:</strong> The User table does not exist yet — run
          <a href="/setup" class="underline font-bold">setup.php</a> first to create the database tables.
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Error banner -->
    <?php if ($formErr): ?>
      <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4 mb-5 text-sm text-rose-700 font-bold">
        <?= htmlspecialchars($formErr) ?>
      </div>
    <?php endif; ?>

    <!-- Account creation form -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-8">
      <h2 class="font-outfit font-bold text-lg text-slate-900 mb-1">Create Super Admin Account</h2>
      <p class="text-xs text-slate-400 mb-7">Fill in your own credentials. These will be saved to the database and you will be logged in immediately.</p>

      <form method="POST" class="space-y-5" autocomplete="off">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <div>
            <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Full Name *</label>
            <input type="text" name="name" required
                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                   placeholder="e.g. Jane Odhiambo"
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm">
          </div>
          <div>
            <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Username *</label>
            <input type="text" name="username" required
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                   placeholder="e.g. admin"
                   pattern="\w{3,32}" title="3–32 characters, letters/numbers/underscore"
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm">
          </div>
        </div>

        <div>
          <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Email Address *</label>
          <input type="email" name="email" required
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 placeholder="you@yourdomain.com"
                 class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <div>
            <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Password * <span class="normal-case font-normal text-slate-400">(min 8 chars)</span></label>
            <input type="password" name="password" required minlength="8"
                   placeholder="Choose a strong password"
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm">
          </div>
          <div>
            <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Confirm Password *</label>
            <input type="password" name="confirm" required minlength="8"
                   placeholder="Repeat password"
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm">
          </div>
        </div>

        <?php if (!empty($users)): ?>
          <label class="flex items-center gap-3 cursor-pointer py-3 px-4 rounded-xl bg-amber-50 border border-amber-200">
            <input type="checkbox" name="replace" value="1" class="accent-amber-600">
            <span class="text-sm text-amber-800 font-medium">
              Replace existing account with this username/email
              <span class="text-amber-600 text-xs block mt-0.5">Tick this if the username already exists and you want to reset it</span>
            </span>
          </label>
        <?php endif; ?>

        <button type="submit"
                class="w-full py-4 rounded-2xl font-bold text-base transition-all hover:brightness-110"
                style="background:#E7952A;color:#0D0102">
          Create Account &amp; Login
        </button>
      </form>
    </div>

    <p class="text-center text-xs text-slate-400 mt-6">
      Already have an account? <a href="/login" class="font-bold" style="color:#750B25">Go to Login</a>
    </p>

  <?php endif; ?>

</div>
</body>
</html>
