<?php
declare(strict_types=1);
/*
 * LOGIN DIAGNOSTIC — Tafakari Digital Hub
 * DELETE this file immediately after resolving the login issue.
 */
require_once __DIR__ . '/config.php';

// ── DB test ────────────────────────────────────────────────────────────────
$dbOk  = false;
$dbErr = '';
$pdo   = null;
try {
    $pdo  = new PDO(DB_DSN, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $dbOk = true;
} catch (PDOException $e) {
    $dbErr = $e->getMessage();
}

// ── Actions ────────────────────────────────────────────────────────────────
$actionMsg = '';
$actionErr = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $dbOk) {
    $action = $_POST['action'] ?? '';

    // Force-delete and recreate both demo accounts
    if ($action === 'force_recreate') {
        try {
            $pdo->beginTransaction();
            foreach (['superadmin','editor'] as $uname) {
                $pdo->prepare('DELETE FROM User WHERE username=?')->execute([$uname]);
            }
            $accounts = [
                ['c'.bin2hex(random_bytes(12)), 'Super Administrator', 'superadmin', 'superadmin@tafakari.hub', 'Admin@Tafakari2024',  'SUPER_ADMIN'],
                ['c'.bin2hex(random_bytes(12)), 'Demo Editor',         'editor',     'editor@tafakari.hub',     'Editor@Tafakari2024', 'ADMIN'],
            ];
            foreach ($accounts as [$id,$name,$uname,$email,$pass,$role]) {
                $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
                $pdo->prepare('INSERT INTO User (id,name,username,email,password,role,createdAt,updatedAt) VALUES (?,?,?,?,?,?,NOW(3),NOW(3))')
                    ->execute([$id,$name,$uname,$email,$hash,$role]);
            }
            $pdo->commit();
            $actionMsg = 'Both accounts deleted and recreated with fresh password hashes.';
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $actionErr = 'Force recreate failed: ' . $e->getMessage();
        }
    }

    // Force-write session directly (bypasses auth_login entirely)
    if ($action === 'force_session') {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if ($dbOk) {
            $row = $pdo->prepare('SELECT id,name,email,username,role FROM User WHERE username=? LIMIT 1');
            $row->execute(['superadmin']);
            $u = $row->fetch();
            if ($u) {
                $_SESSION['user'] = $u;
                session_regenerate_id(true);
                $actionMsg = 'Session manually set for superadmin. <a href="/admin/dashboard" style="color:#9A1415;font-weight:bold">Click here to open Dashboard &rarr;</a>';
            } else {
                $actionErr = 'User "superadmin" not found. Run Force Recreate first.';
            }
        }
    }
}

// ── Read all users ─────────────────────────────────────────────────────────
$users = [];
$tableExists = false;
if ($dbOk) {
    try {
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $tableExists = in_array('User', $tables);
        if ($tableExists) {
            $users = $pdo->query('SELECT id, name, username, email, role, createdAt FROM User ORDER BY createdAt ASC')->fetchAll();
        }
    } catch (PDOException $e) { /* ignore */ }
}

// ── Password verification test ─────────────────────────────────────────────
$passTests = [];
if ($dbOk && $tableExists) {
    foreach (['superadmin' => 'Admin@Tafakari2024', 'editor' => 'Editor@Tafakari2024'] as $uname => $testPass) {
        $row = $pdo->prepare('SELECT password FROM User WHERE username=? LIMIT 1');
        $row->execute([$uname]);
        $stored = $row->fetchColumn();
        if ($stored === false) {
            $passTests[$uname] = ['found' => false, 'hash_preview' => null, 'verify' => null];
        } else {
            $passTests[$uname] = [
                'found'        => true,
                'hash_preview' => substr($stored, 0, 29) . '…',  // shows algo+cost+salt prefix only
                'verify'       => password_verify($testPass, $stored),
            ];
        }
    }
}

// ── Session state ──────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();
$sessionUser = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login Diagnostic | Tafakari</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>body{font-family:ui-monospace,monospace}code{font-family:inherit}</style>
</head>
<body class="bg-slate-100 min-h-screen p-6">
<div class="max-w-3xl mx-auto space-y-5">

  <div class="bg-rose-800 text-white rounded-2xl p-5">
    <p class="font-bold text-[13px]">&#9888; SECURITY WARNING — DELETE <code>diag.php</code> IMMEDIATELY after resolving login issues.</p>
  </div>

  <h1 class="text-2xl font-bold text-slate-900">Login Diagnostic Tool</h1>

  <?php if ($actionMsg): ?>
    <div class="bg-emerald-50 border border-emerald-300 rounded-xl p-4 text-emerald-800 text-sm font-bold"><?= $actionMsg ?></div>
  <?php endif; ?>
  <?php if ($actionErr): ?>
    <div class="bg-rose-50 border border-rose-300 rounded-xl p-4 text-rose-800 text-sm font-bold"><?= htmlspecialchars($actionErr) ?></div>
  <?php endif; ?>

  <!-- ① DB Connection -->
  <div class="bg-white rounded-2xl border p-6 <?= $dbOk ? 'border-emerald-200' : 'border-rose-300' ?>">
    <h2 class="font-bold text-slate-900 mb-3">① Database Connection</h2>
    <?php if ($dbOk): ?>
      <p class="text-emerald-700 text-sm font-bold">✓ Connected to <code><?= htmlspecialchars(DB_NAME) ?></code> on <code><?= htmlspecialchars(DB_HOST) ?></code></p>
    <?php else: ?>
      <p class="text-rose-700 text-sm font-bold">✗ Connection failed</p>
      <code class="block mt-2 text-xs bg-rose-50 p-3 rounded-lg text-rose-800"><?= htmlspecialchars($dbErr) ?></code>
      <p class="mt-3 text-xs text-slate-500">Edit <code>config.php</code> on the server with correct DB_USER, DB_PASS, DB_NAME.</p>
    <?php endif; ?>
  </div>

  <!-- ② User Table -->
  <div class="bg-white rounded-2xl border p-6 <?= $tableExists ? 'border-emerald-200' : 'border-amber-300' ?>">
    <h2 class="font-bold text-slate-900 mb-3">② User Table — All Accounts (<?= count($users) ?>)</h2>
    <?php if (!$tableExists): ?>
      <p class="text-amber-700 text-sm font-bold">✗ User table not found — run setup.php first.</p>
    <?php elseif (empty($users)): ?>
      <p class="text-amber-700 text-sm font-bold">Table exists but is empty. Run the Force Recreate action below.</p>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-xs border-collapse">
          <thead><tr class="bg-slate-50">
            <th class="text-left p-2 border border-slate-200">username</th>
            <th class="text-left p-2 border border-slate-200">email</th>
            <th class="text-left p-2 border border-slate-200">role</th>
            <th class="text-left p-2 border border-slate-200">created</th>
          </tr></thead>
          <tbody>
          <?php foreach ($users as $u): ?>
            <tr class="hover:bg-slate-50">
              <td class="p-2 border border-slate-200 font-bold"><?= htmlspecialchars($u['username'] ?? '(null)') ?></td>
              <td class="p-2 border border-slate-200"><?= htmlspecialchars($u['email'] ?? '(null)') ?></td>
              <td class="p-2 border border-slate-200"><?= htmlspecialchars($u['role'] ?? '(null)') ?></td>
              <td class="p-2 border border-slate-200"><?= htmlspecialchars($u['createdAt'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- ③ Password Verification -->
  <div class="bg-white rounded-2xl border border-slate-200 p-6">
    <h2 class="font-bold text-slate-900 mb-3">③ Password Verification Test</h2>
    <?php if (!$tableExists): ?>
      <p class="text-slate-400 text-sm">Waiting for User table.</p>
    <?php else: ?>
      <div class="space-y-4">
        <?php foreach ($passTests as $uname => $t): ?>
          <div class="p-4 rounded-xl border <?= !$t['found'] ? 'border-amber-200 bg-amber-50' : ($t['verify'] ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50') ?>">
            <p class="font-bold text-[13px] text-slate-900 mb-1">
              <?= htmlspecialchars($uname) ?>
              <?php if (!$t['found']): ?>
                <span class="text-amber-700"> — account not found in DB</span>
              <?php elseif ($t['verify']): ?>
                <span class="text-emerald-700"> — ✓ password_verify() PASSED</span>
              <?php else: ?>
                <span class="text-rose-700"> — ✗ password_verify() FAILED (hash mismatch)</span>
              <?php endif; ?>
            </p>
            <?php if ($t['found']): ?>
              <p class="text-xs text-slate-500">Stored hash prefix: <code><?= htmlspecialchars($t['hash_preview']) ?></code></p>
              <p class="text-xs text-slate-500 mt-0.5">
                Expected hash starts with: <code>$2y$12$</code>
                <?php if ($t['hash_preview'] && !str_starts_with($t['hash_preview'], '$2y$')): ?>
                  <span class="text-rose-600 font-bold"> ← WRONG FORMAT — hash may be plain text or MD5, not bcrypt!</span>
                <?php endif; ?>
              </p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- ④ Session State -->
  <div class="bg-white rounded-2xl border border-slate-200 p-6">
    <h2 class="font-bold text-slate-900 mb-3">④ Current Session State</h2>
    <p class="text-xs text-slate-500 mb-2">Session ID: <code><?= htmlspecialchars(session_id()) ?></code></p>
    <?php if ($sessionUser): ?>
      <div class="p-3 bg-emerald-50 rounded-lg border border-emerald-200">
        <p class="text-emerald-700 text-sm font-bold">✓ You are logged in as: <code><?= htmlspecialchars($sessionUser['username'] ?? '?') ?></code> (<?= htmlspecialchars($sessionUser['role'] ?? '?') ?>)</p>
        <a href="/admin/dashboard" class="inline-block mt-2 text-xs font-bold text-emerald-700 underline">Open Admin Dashboard →</a>
      </div>
    <?php else: ?>
      <p class="text-amber-700 text-sm font-bold">No active session — not logged in.</p>
      <p class="text-xs text-slate-400 mt-1">If password_verify PASSED above but you cannot log in, this confirms a session-writing problem (e.g., session directory not writable).</p>
    <?php endif; ?>
  </div>

  <!-- ⑤ Config Values -->
  <div class="bg-white rounded-2xl border border-slate-200 p-6">
    <h2 class="font-bold text-slate-900 mb-3">⑤ Config &amp; Environment</h2>
    <table class="w-full text-xs border-collapse">
      <tbody>
        <?php foreach ([
          'DB_HOST'        => DB_HOST,
          'DB_NAME'        => DB_NAME,
          'DB_USER'        => DB_USER,
          'DB_PASS'        => str_repeat('•', min(strlen(DB_PASS), 12)) . ' (' . strlen(DB_PASS) . ' chars)',
          'APP_ENV'        => APP_ENV,
          'PHP version'    => PHP_VERSION,
          'Session save path' => session_save_path() ?: '(default)',
          'session.use_cookies' => ini_get('session.use_cookies') ?: '1',
          'session.cookie_secure' => ini_get('session.cookie_secure') ?: '0',
        ] as $k => $v): ?>
          <tr class="border-b border-slate-100">
            <td class="p-2 font-bold text-slate-700 w-40"><?= htmlspecialchars($k) ?></td>
            <td class="p-2 text-slate-600"><?= htmlspecialchars((string)$v) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- ⑥ Actions -->
  <?php if ($dbOk && $tableExists): ?>
  <div class="bg-white rounded-2xl border border-slate-200 p-6">
    <h2 class="font-bold text-slate-900 mb-1">⑥ Fix Actions</h2>
    <p class="text-xs text-slate-500 mb-5">Use these only to resolve the login issue. Start with the one that matches your diagnosis above.</p>
    <div class="flex flex-col sm:flex-row gap-3">

      <!-- Force recreate -->
      <form method="POST" class="flex-1">
        <input type="hidden" name="action" value="force_recreate">
        <button type="submit" class="w-full py-3 px-4 rounded-xl text-sm font-bold text-white bg-rose-700 hover:bg-rose-800 transition-colors">
          Force Delete &amp; Recreate Accounts
        </button>
        <p class="text-[10px] text-slate-400 mt-1.5 text-center">Deletes superadmin + editor, inserts fresh with correct passwords.</p>
      </form>

      <!-- Force session -->
      <form method="POST" class="flex-1">
        <input type="hidden" name="action" value="force_session">
        <button type="submit" class="w-full py-3 px-4 rounded-xl text-sm font-bold text-white bg-slate-700 hover:bg-slate-800 transition-colors">
          Force Session Login (superadmin)
        </button>
        <p class="text-[10px] text-slate-400 mt-1.5 text-center">Bypasses auth_login() — directly writes session. Use to test if login logic itself is broken.</p>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <p class="text-center text-[11px] text-slate-400 pb-8">DELETE diag.php from your server after resolving the issue.</p>
</div>
</body>
</html>
