<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$user = require_auth();
$uid  = $user['id'];
$pdo  = db();

$profileMsg = '';
$profileErr = '';
$passMsg    = '';
$passErr    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_profile'])) {
        $name     = trim($_POST['name']     ?? '');
        $email    = trim($_POST['email']    ?? '');
        $username = trim($_POST['username'] ?? '');
        if (!$name || !$email || !$username) {
            $profileErr = 'All fields are required.';
        } else {
            $dup = $pdo->prepare('SELECT id FROM User WHERE (email=? OR username=?) AND id!=? LIMIT 1');
            $dup->execute([$email, $username, $uid]);
            if ($dup->fetch()) {
                $profileErr = 'Email or username is already in use.';
            } else {
                $pdo->prepare('UPDATE User SET name=?, email=?, username=?, updatedAt=NOW(3) WHERE id=?')
                    ->execute([$name, $email, $username, $uid]);
                $_SESSION['user']['name']     = $name;
                $_SESSION['user']['email']    = $email;
                $_SESSION['user']['username'] = $username;
                $user = $_SESSION['user'];
                $profileMsg = 'Profile updated successfully.';
            }
        }
    }
    if (isset($_POST['save_password'])) {
        $newPass  = $_POST['new_password']     ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        if (strlen($newPass) < 8) {
            $passErr = 'Password must be at least 8 characters.';
        } elseif ($newPass !== $confirm) {
            $passErr = 'Passwords do not match.';
        } else {
            $hashed = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare('UPDATE User SET password=?, updatedAt=NOW(3) WHERE id=?')->execute([$hashed, $uid]);
            $passMsg = 'Password changed successfully.';
        }
    }
}

$dbUser = $pdo->prepare('SELECT id,name,email,username,role,createdAt FROM User WHERE id=? LIMIT 1');
$dbUser->execute([$uid]);
$dbUser = $dbUser->fetch();

$initial      = strtoupper(substr($dbUser['name'] ?? $dbUser['username'] ?? 'U', 0, 1));
$isSuperAdmin = ($dbUser['role'] === 'SUPER_ADMIN');

$pageTitle    = 'My Profile | Tafakari Admin';
$adminPageTitle = 'My Profile';
$adminPageSub   = 'Manage your account information and security settings.';
?>
<?php include dirname(__DIR__) . '/includes/head.php'; ?>

<style>
  .form-input {
    width: 100%;
    padding: .7rem 1rem;
    border-radius: .75rem;
    border: 1px solid #E2E8F0;
    background: #F8FAFC;
    font-size: .875rem;
    color: #1E293B;
    outline: none;
    transition: border-color .15s, box-shadow .15s;
  }
  .form-input:focus {
    border-color: #9A1415;
    box-shadow: 0 0 0 3px rgba(154,20,21,.1);
    background: #fff;
  }
  .form-label {
    display: block;
    font-size: .65rem;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: #64748B;
    margin-bottom: .5rem;
  }
</style>

<body class="antialiased font-inter" style="background:#F4F6F8">
<div class="flex h-screen overflow-hidden">
<?php include dirname(__DIR__) . '/includes/admin-sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden min-w-0">
<?php include dirname(__DIR__) . '/includes/admin-topbar.php'; ?>

<main class="flex-1 overflow-y-auto p-6 md:p-8">
<div class="max-w-5xl">

  <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

    <!-- Profile Card -->
    <div class="lg:col-span-1">
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 text-center sticky top-6">
        <div class="w-20 h-20 rounded-2xl flex items-center justify-center text-white font-black text-2xl mx-auto mb-4 shadow-lg"
             style="background:#9A1415"><?= h($initial) ?></div>
        <h3 class="font-outfit font-bold text-[15px] text-slate-900 leading-tight">
          <?= h($dbUser['name'] ?? $dbUser['username'] ?? '—') ?>
        </h3>
        <p class="text-sm text-slate-400 mt-0.5">@<?= h($dbUser['username'] ?? '') ?></p>
        <span class="inline-block mt-3 px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest <?= $isSuperAdmin ? 'bg-rose-50 text-rose-600' : 'bg-slate-100 text-slate-600' ?>">
          <?= h(str_replace('_', ' ', $dbUser['role'])) ?>
        </span>
        <div class="mt-5 pt-5 border-t border-slate-100">
          <p class="text-[10px] text-slate-400 uppercase tracking-widest font-semibold">Member since</p>
          <p class="text-[13px] font-semibold text-slate-700 mt-0.5"><?= format_date($dbUser['createdAt'], 'M j, Y') ?></p>
        </div>
        <div class="mt-4 pt-4 border-t border-slate-100 text-left space-y-2">
          <a href="/admin/dashboard"
             class="flex items-center gap-2 text-[11px] text-slate-500 hover:text-primary transition-colors font-medium">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Back to Dashboard
          </a>
          <a href="/admin/logout"
             class="flex items-center gap-2 text-[11px] text-rose-400 hover:text-rose-600 transition-colors font-medium">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            Sign Out
          </a>
        </div>
      </div>
    </div>

    <!-- Forms -->
    <div class="lg:col-span-3 space-y-5">

      <!-- Profile Information -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:rgba(154,20,21,.06)">
            <svg width="15" height="15" fill="none" stroke="#9A1415" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
              <path d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <h3 class="font-outfit font-bold text-[15px] text-slate-900">Profile Information</h3>
        </div>

        <div class="p-6">
          <?php if ($profileMsg): ?>
            <div class="flex items-center gap-2 mb-5 p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 text-[13px]">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <?= h($profileMsg) ?>
            </div>
          <?php endif; ?>
          <?php if ($profileErr): ?>
            <div class="flex items-center gap-2 mb-5 p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-[13px]">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <?= h($profileErr) ?>
            </div>
          <?php endif; ?>

          <form method="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
              <div>
                <label class="form-label">Full Name</label>
                <input type="text" name="name" required class="form-input"
                       value="<?= h($dbUser['name'] ?? '') ?>" placeholder="Your full name">
              </div>
              <div>
                <label class="form-label">Username</label>
                <input type="text" name="username" required class="form-input"
                       value="<?= h($dbUser['username'] ?? '') ?>" placeholder="@username">
              </div>
              <div class="md:col-span-2">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" required class="form-input"
                       value="<?= h($dbUser['email'] ?? '') ?>" placeholder="you@example.com">
              </div>
            </div>
            <button type="submit" name="save_profile" class="btn-primary" style="padding:.65rem 1.5rem">
              Save Changes
            </button>
          </form>
        </div>
      </div>

      <!-- Change Password -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:rgba(154,20,21,.06)">
            <svg width="15" height="15" fill="none" stroke="#9A1415" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
              <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
          </div>
          <h3 class="font-outfit font-bold text-[15px] text-slate-900">Change Password</h3>
        </div>

        <div class="p-6">
          <?php if ($passMsg): ?>
            <div class="flex items-center gap-2 mb-5 p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 text-[13px]">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <?= h($passMsg) ?>
            </div>
          <?php endif; ?>
          <?php if ($passErr): ?>
            <div class="flex items-center gap-2 mb-5 p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-[13px]">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <?= h($passErr) ?>
            </div>
          <?php endif; ?>

          <form method="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
              <div>
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" required minlength="8" class="form-input"
                       placeholder="Min 8 characters">
              </div>
              <div>
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" required class="form-input"
                       placeholder="Repeat new password">
              </div>
            </div>
            <div class="flex items-center gap-3">
              <button type="submit" name="save_password" class="btn-primary" style="padding:.65rem 1.5rem">
                Update Password
              </button>
              <p class="text-[11px] text-slate-400">Changes take effect immediately.</p>
            </div>
          </form>
        </div>
      </div>

      <!-- Danger Zone (visible to self only) -->
      <div class="bg-white rounded-2xl border border-rose-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-rose-100 flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-rose-50">
            <svg width="15" height="15" fill="none" stroke="#EF4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
              <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
          </div>
          <h3 class="font-outfit font-bold text-[15px] text-rose-600">Account</h3>
        </div>
        <div class="p-6 flex items-center justify-between">
          <div>
            <p class="text-[13px] font-semibold text-slate-700">Sign out of all sessions</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Clears your current session and redirects to login.</p>
          </div>
          <a href="/admin/logout"
             class="px-4 py-2 rounded-xl text-[11px] font-bold border border-rose-200 text-rose-600 hover:bg-rose-50 transition-colors">
            Sign Out
          </a>
        </div>
      </div>

    </div>
  </div>
</div>
</main>
</div>
</div>
</body>
</html>
