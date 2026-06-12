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
            // Check uniqueness
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
        $newPass  = $_POST['new_password']      ?? '';
        $confirm  = $_POST['confirm_password']  ?? '';
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

// Refresh user from DB
$dbUser = $pdo->prepare('SELECT id,name,email,username,role,createdAt FROM User WHERE id=? LIMIT 1');
$dbUser->execute([$uid]);
$dbUser = $dbUser->fetch();

$pageTitle = 'My Profile | Tafakari Admin';
?>
<?php include dirname(__DIR__) . '/includes/head.php'; ?>
<body class="antialiased bg-slate-100 font-inter">
<div class="flex h-screen overflow-hidden">
<?php include dirname(__DIR__) . '/includes/admin-sidebar.php'; ?>

<div class="flex-1 overflow-y-auto p-10">
  <div class="mb-8">
    <h1 class="font-outfit text-3xl font-bold text-slate-900">My Profile</h1>
    <p class="text-slate-500 mt-1">Manage your account details and password.</p>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 max-w-4xl">
    <!-- User Card -->
    <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100 text-center">
      <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-white font-black text-2xl mx-auto mb-4 shadow-lg" style="background:#9A1415">
        <?= strtoupper(substr($dbUser['name'] ?? $dbUser['username'] ?? 'U', 0, 1)) ?>
      </div>
      <h3 class="font-outfit font-bold text-lg text-slate-900"><?= h($dbUser['name'] ?? $dbUser['username']) ?></h3>
      <p class="text-sm text-slate-400">@<?= h($dbUser['username'] ?? '') ?></p>
      <span class="inline-block mt-3 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest <?= $dbUser['role'] === 'SUPER_ADMIN' ? 'bg-rose-50 text-rose-600' : 'bg-slate-100 text-slate-600' ?>">
        <?= h(str_replace('_', ' ', $dbUser['role'])) ?>
      </span>
      <p class="text-xs text-slate-400 mt-4">Member since <?= format_date($dbUser['createdAt'], 'M Y') ?></p>
    </div>

    <!-- Forms -->
    <div class="lg:col-span-2 space-y-6">
      <!-- Profile Form -->
      <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
        <h3 class="font-outfit font-bold text-lg mb-6 text-slate-900">Profile Information</h3>
        <?php if ($profileMsg): ?><div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 text-sm"><?= h($profileMsg) ?></div><?php endif; ?>
        <?php if ($profileErr): ?><div class="mb-4 p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-sm"><?= h($profileErr) ?></div><?php endif; ?>
        <form method="POST" class="space-y-4">
          <div>
            <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Full Name</label>
            <input type="text" name="name" required value="<?= h($dbUser['name'] ?? '') ?>"
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none">
          </div>
          <div>
            <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Email</label>
            <input type="email" name="email" required value="<?= h($dbUser['email'] ?? '') ?>"
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none">
          </div>
          <div>
            <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Username</label>
            <input type="text" name="username" required value="<?= h($dbUser['username'] ?? '') ?>"
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none">
          </div>
          <button type="submit" name="save_profile" class="btn-primary px-6 py-3">Save Profile</button>
        </form>
      </div>

      <!-- Password Form -->
      <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
        <h3 class="font-outfit font-bold text-lg mb-6 text-slate-900">Change Password</h3>
        <?php if ($passMsg): ?><div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 text-sm"><?= h($passMsg) ?></div><?php endif; ?>
        <?php if ($passErr): ?><div class="mb-4 p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-sm"><?= h($passErr) ?></div><?php endif; ?>
        <form method="POST" class="space-y-4">
          <div>
            <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">New Password</label>
            <input type="password" name="new_password" required minlength="8"
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none"
                   placeholder="Min 8 characters">
          </div>
          <div>
            <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Confirm Password</label>
            <input type="password" name="confirm_password" required
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none">
          </div>
          <button type="submit" name="save_password" class="btn-primary px-6 py-3">Change Password</button>
        </form>
      </div>
    </div>
  </div>
</div>
</div>
</body>
</html>
