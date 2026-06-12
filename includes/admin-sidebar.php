<?php
$_user    = current_user();
$_role    = $_user['role'] ?? '';
$_name    = $_user['name'] ?? $_user['username'] ?? 'User';
$_initial = strtoupper(substr($_name, 0, 1));
$_path    = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');

$_coreItems = [
  ['href' => '/admin/dashboard',     'icon' => '📊', 'label' => 'Overview'],
  ['href' => '/admin/content',       'icon' => '📝', 'label' => 'My Content'],
  ['href' => '/admin/content/new',   'icon' => '➕', 'label' => 'Create New'],
  ['href' => '/admin/profile',       'icon' => '👤', 'label' => 'My Profile'],
];

$_superItems = [
  ['href' => '/admin/super/approvals',  'icon' => '⚖️',  'label' => 'Approval Queue'],
  ['href' => '/admin/super/moderation', 'icon' => '🛡️',  'label' => 'Comment Moderation'],
  ['href' => '/admin/super/messages',   'icon' => '📬',  'label' => 'Contact Inbox'],
  ['href' => '/admin/super/users',      'icon' => '👥',  'label' => 'User Management'],
];
?>
<aside class="w-72 h-screen sticky top-0 flex flex-col p-6 overflow-y-auto shrink-0" style="background:#1F0404;border-right:1px solid rgba(255,255,255,.08)">
  <!-- Logo -->
  <div class="flex items-center gap-3 mb-12 px-2">
    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-lg border border-yellow-800/20" style="background:#9A1415">T</div>
    <div class="flex flex-col">
      <span class="font-outfit font-bold text-lg text-white leading-tight">Admin Portal</span>
      <span class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">Tafakari Hub</span>
    </div>
  </div>

  <nav class="grow space-y-8">
    <!-- Core Actions -->
    <div class="space-y-1">
      <h4 class="px-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-4">Core Actions</h4>
      <?php foreach ($_coreItems as $_item):
        $isActive = str_starts_with($_path, $_item['href']) && ($_item['href'] !== '/admin/content' || $_path === '/admin/content' || str_starts_with($_path, '/admin/content/'));
        $isActive = ($_path === $_item['href']) || ($_item['href'] === '/admin/content' && str_starts_with($_path, '/admin/content'));
      ?>
        <a href="<?= h($_item['href']) ?>"
           class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all <?= $isActive ? 'text-white shadow-lg shadow-primary/20' : 'text-slate-400 hover:text-white hover:bg-white/5' ?>"
           <?= $isActive ? 'style="background:#9A1415"' : '' ?>>
          <span class="text-lg opacity-80"><?= $_item['icon'] ?></span>
          <?= h($_item['label']) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if ($_role === 'SUPER_ADMIN'): ?>
    <!-- Super Authority -->
    <div class="space-y-1">
      <h4 class="px-4 text-[10px] font-bold text-rose-500 uppercase tracking-widest mb-4">Super Authority</h4>
      <?php foreach ($_superItems as $_item):
        $isActive = ($_path === $_item['href']);
      ?>
        <a href="<?= h($_item['href']) ?>"
           class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all <?= $isActive ? 'bg-white text-slate-900 shadow-lg shadow-white/10' : 'text-slate-400 hover:text-white hover:bg-white/5' ?>">
          <span class="text-lg opacity-80"><?= $_item['icon'] ?></span>
          <?= h($_item['label']) ?>
        </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </nav>

  <!-- User card -->
  <div class="mt-auto border-t pt-6 space-y-4" style="border-color:rgba(255,255,255,.05)">
    <div class="flex items-center gap-3 px-2">
      <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-xs font-bold text-slate-300">
        <?= h($_initial) ?>
      </div>
      <div class="flex flex-col min-w-0">
        <span class="text-sm font-bold text-white truncate"><?= h($_name) ?></span>
        <span class="text-[10px] text-slate-500 capitalize"><?= h(strtolower(str_replace('_', ' ', $_role))) ?></span>
      </div>
    </div>
    <a href="/admin/logout"
       class="w-full text-left px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:text-rose-400 hover:bg-rose-500/5 transition-all flex items-center gap-3">
      <span class="text-lg">🚪</span>
      Sign Out
    </a>
  </div>
</aside>
