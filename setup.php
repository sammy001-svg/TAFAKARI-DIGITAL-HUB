<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check DB status
$dbConnected  = false;
$tablesReady  = false;
$userExists   = false;
$setupDone    = false;
$dbError      = '';
$requiredTables = ['User', 'Post', 'Comment', 'ContactMessage'];
$missingTables  = [];

try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $dbConnected = true;
    $existing = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $missingTables = array_diff($requiredTables, $existing);
    $tablesReady   = empty($missingTables);
    if ($tablesReady) {
        $userExists = (int)$pdo->query("SELECT COUNT(*) FROM User")->fetchColumn() > 0;
        if ($userExists) $setupDone = true;
    }
} catch (PDOException $e) {
    $dbError = $e->getMessage();
}

// Handle POST: create tables
$initMsg   = '';
$initError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'init_db' && $dbConnected && !$tablesReady) {
        try {
            $sqls = [
                "CREATE TABLE IF NOT EXISTS `User` (
                  `id` VARCHAR(191) NOT NULL, `name` VARCHAR(191) NULL,
                  `email` VARCHAR(191) NULL, `username` VARCHAR(191) NULL,
                  `password` VARCHAR(191) NOT NULL,
                  `role` ENUM('SUPER_ADMIN','ADMIN') NOT NULL DEFAULT 'ADMIN',
                  `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
                  `updatedAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
                  PRIMARY KEY (`id`), UNIQUE KEY `User_email_key`(`email`), UNIQUE KEY `User_username_key`(`username`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                "CREATE TABLE IF NOT EXISTS `Post` (
                  `id` VARCHAR(191) NOT NULL, `title` VARCHAR(191) NOT NULL,
                  `content` TEXT NULL, `description` TEXT NULL,
                  `thumbnailUrl` VARCHAR(191) NULL, `mediaUrl` VARCHAR(191) NULL,
                  `type` ENUM('ARTICLE','GALLERY_IMAGE','PODCAST','VIDEO','DOCUMENT') NOT NULL DEFAULT 'ARTICLE',
                  `status` ENUM('DRAFT','PENDING','PUBLISHED','REJECTED','ARCHIVED') NOT NULL DEFAULT 'DRAFT',
                  `authorId` VARCHAR(191) NOT NULL, `approverId` VARCHAR(191) NULL,
                  `rejectionNotes` TEXT NULL, `country` VARCHAR(191) NOT NULL,
                  `region` VARCHAR(191) NOT NULL, `issueCategory` VARCHAR(191) NOT NULL,
                  `viewCount` INT NOT NULL DEFAULT 0, `downloadCount` INT NOT NULL DEFAULT 0,
                  `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
                  `updatedAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
                  PRIMARY KEY (`id`),
                  CONSTRAINT `Post_authorId_fkey` FOREIGN KEY (`authorId`) REFERENCES `User`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                "CREATE TABLE IF NOT EXISTS `Comment` (
                  `id` VARCHAR(191) NOT NULL, `content` TEXT NOT NULL,
                  `name` VARCHAR(191) NULL, `email` VARCHAR(191) NULL,
                  `isModerated` TINYINT(1) NOT NULL DEFAULT 0, `isFlagged` TINYINT(1) NOT NULL DEFAULT 0,
                  `postId` VARCHAR(191) NOT NULL, `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
                  PRIMARY KEY (`id`),
                  CONSTRAINT `Comment_postId_fkey` FOREIGN KEY (`postId`) REFERENCES `Post`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                "CREATE TABLE IF NOT EXISTS `ContactMessage` (
                  `id` VARCHAR(191) NOT NULL, `fullName` VARCHAR(191) NOT NULL,
                  `email` VARCHAR(191) NOT NULL, `country` VARCHAR(191) NOT NULL,
                  `interest` VARCHAR(191) NOT NULL, `message` TEXT NOT NULL,
                  `isRead` TINYINT(1) NOT NULL DEFAULT 0,
                  `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            ];
            foreach ($sqls as $sql) $pdo->exec($sql);
            $initMsg    = 'Database tables created successfully.';
            $tablesReady = true;
            $missingTables = [];
        } catch (PDOException $e) {
            $initError = 'Could not create tables: ' . $e->getMessage();
        }
    }

    if ($_POST['action'] === 'create_admin' && $tablesReady && !$userExists) {
        $name     = trim($_POST['name']     ?? '');
        $email    = trim($_POST['email']    ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirm  = trim($_POST['confirm']  ?? '');
        if (!$name || !$email || !$username || !$password) {
            $initError = 'All fields are required.';
        } elseif (strlen($password) < 8) {
            $initError = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirm) {
            $initError = 'Passwords do not match.';
        } else {
            try {
                $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $pdo->prepare(
                    'INSERT INTO User (id, name, email, username, password, role, createdAt, updatedAt) VALUES (?,?,?,?,?,?,NOW(3),NOW(3))'
                );
                $stmt->execute([generate_id(), $name, $email, $username, $hashed, 'SUPER_ADMIN']);
                $initMsg  = 'Super Admin account created! You can now log in.';
                $userExists = true;
                $setupDone  = true;
            } catch (PDOException $e) {
                $initError = 'Could not create account: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Setup | Tafakari Digital Hub';
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen bg-slate-50 flex flex-col font-inter">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="flex-grow max-w-2xl mx-auto px-6 py-16">
  <div class="mb-10 text-center">
    <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-white font-black text-3xl mx-auto mb-4 shadow-lg" style="background:#9A1415">T</div>
    <h1 class="font-outfit text-3xl font-black text-slate-900">Platform Setup</h1>
    <p class="text-slate-500 mt-2">One-time configuration to get your Tafakari Hub running.</p>
  </div>

  <?php if ($initMsg): ?>
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-700 text-sm font-bold"><?= h($initMsg) ?></div>
  <?php endif; ?>
  <?php if ($initError): ?>
    <div class="mb-6 p-4 bg-rose-50 border border-rose-200 rounded-2xl text-rose-700 text-sm"><?= h($initError) ?></div>
  <?php endif; ?>

  <!-- Steps -->
  <div class="space-y-4">

    <!-- Step 1: DB Connection -->
    <div class="bg-white rounded-3xl border p-8 shadow-sm <?= $dbConnected ? 'border-emerald-200' : 'border-rose-200' ?>">
      <div class="flex items-center gap-4 mb-4">
        <div class="w-8 h-8 rounded-full flex items-center justify-center font-black text-sm <?= $dbConnected ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' ?>">
          <?= $dbConnected ? '✓' : '1' ?>
        </div>
        <h2 class="font-outfit font-bold text-lg text-slate-900">Database Connection</h2>
        <span class="ml-auto px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest <?= $dbConnected ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' ?>">
          <?= $dbConnected ? 'Connected' : 'Failed' ?>
        </span>
      </div>
      <?php if (!$dbConnected): ?>
        <div class="p-4 bg-rose-50 rounded-xl border border-rose-200 text-sm text-rose-700 mb-4">
          <p class="font-bold mb-2">Connection error:</p>
          <code class="text-xs"><?= h($dbError) ?></code>
        </div>
        <p class="text-sm text-slate-600">Open <code class="bg-slate-100 px-1 rounded">config.php</code> and fill in your real cPanel database credentials (DB_NAME, DB_USER, DB_PASS), then ensure the database exists in cPanel &rarr; MySQL Databases.</p>
      <?php else: ?>
        <p class="text-sm text-slate-500">Connected to <strong><?= h(DB_NAME) ?></strong> on <strong><?= h(DB_HOST) ?></strong>.</p>
      <?php endif; ?>
    </div>

    <!-- Step 2: Schema -->
    <div class="bg-white rounded-3xl border p-8 shadow-sm <?= $tablesReady ? 'border-emerald-200' : ($dbConnected ? 'border-amber-200' : 'border-slate-200') ?>">
      <div class="flex items-center gap-4 mb-4">
        <div class="w-8 h-8 rounded-full flex items-center justify-center font-black text-sm <?= $tablesReady ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' ?>">
          <?= $tablesReady ? '✓' : '2' ?>
        </div>
        <h2 class="font-outfit font-bold text-lg text-slate-900">Database Schema</h2>
        <span class="ml-auto px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest <?= $tablesReady ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' ?>">
          <?= $tablesReady ? 'Ready' : 'Pending' ?>
        </span>
      </div>
      <?php if (!$tablesReady && $dbConnected): ?>
        <p class="text-sm text-slate-600 mb-4">Missing tables: <strong><?= h(implode(', ', $missingTables)) ?></strong></p>
        <form method="POST">
          <input type="hidden" name="action" value="init_db">
          <button type="submit" class="btn-primary px-6 py-3 text-sm">Create Tables</button>
        </form>
      <?php elseif ($tablesReady): ?>
        <p class="text-sm text-slate-500">All 4 tables are present and ready.</p>
      <?php else: ?>
        <p class="text-sm text-slate-400">Waiting for database connection.</p>
      <?php endif; ?>
    </div>

    <!-- Step 3: Admin Account -->
    <div class="bg-white rounded-3xl border p-8 shadow-sm <?= $userExists ? 'border-emerald-200' : ($tablesReady ? 'border-slate-200' : 'border-slate-100') ?>">
      <div class="flex items-center gap-4 mb-6">
        <div class="w-8 h-8 rounded-full flex items-center justify-center font-black text-sm <?= $userExists ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>">
          <?= $userExists ? '✓' : '3' ?>
        </div>
        <h2 class="font-outfit font-bold text-lg text-slate-900">Super Admin Account</h2>
        <?php if ($userExists): ?>
          <span class="ml-auto px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-emerald-50 text-emerald-700">Done</span>
        <?php endif; ?>
      </div>

      <?php if ($setupDone): ?>
        <div class="text-center py-4">
          <p class="text-slate-600 text-sm mb-6">Setup is complete. Your platform is ready to use.</p>
          <a href="/admin/login" class="btn-primary px-8 py-3 text-base">Go to Admin Login</a>
        </div>
      <?php elseif ($tablesReady && !$userExists): ?>
        <form method="POST" class="space-y-4">
          <input type="hidden" name="action" value="create_admin">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Full Name</label>
              <input type="text" name="name" required value="<?= h($_POST['name'] ?? '') ?>"
                     class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none">
            </div>
            <div>
              <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Username</label>
              <input type="text" name="username" required value="<?= h($_POST['username'] ?? '') ?>"
                     class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none">
            </div>
          </div>
          <div>
            <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Email</label>
            <input type="email" name="email" required value="<?= h($_POST['email'] ?? '') ?>"
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none">
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Password</label>
              <input type="password" name="password" required minlength="8"
                     class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none"
                     placeholder="Min 8 chars">
            </div>
            <div>
              <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Confirm</label>
              <input type="password" name="confirm" required
                     class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none"
                     placeholder="Repeat password">
            </div>
          </div>
          <button type="submit" class="btn-primary w-full py-4">Create Super Admin</button>
        </form>
      <?php else: ?>
        <p class="text-sm text-slate-400">Complete the steps above first.</p>
      <?php endif; ?>
    </div>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
