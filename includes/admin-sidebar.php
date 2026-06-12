<?php
$_u       = current_user();
$_role    = $_u['role'] ?? '';
$_name    = $_u['name'] ?? $_u['username'] ?? 'User';
$_initial = strtoupper(substr($_name, 0, 1));
$_path    = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');

$_navCore = [
  ['href' => '/admin/dashboard',   'label' => 'Overview',   'svg' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
  ['href' => '/admin/content',     'label' => 'My Content', 'svg' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
  ['href' => '/admin/content/new', 'label' => 'Create New', 'svg' => 'M12 4v16m8-8H4'],
  ['href' => '/admin/profile',     'label' => 'My Profile', 'svg' => 'M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
];

$_navSuper = [
  ['href' => '/admin/super/approvals',  'label' => 'Approval Queue',      'svg' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
  ['href' => '/admin/super/moderation', 'label' => 'Comment Moderation',  'svg' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
  ['href' => '/admin/super/messages',   'label' => 'Contact Inbox',       'svg' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
  ['href' => '/admin/super/users',      'label' => 'User Management',     'svg' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
];

$_pendingCount = 0;
if ($_role === 'SUPER_ADMIN') {
    try {
        $_pendingCount = (int)db()->query("SELECT COUNT(*) FROM Post WHERE status='PENDING'")->fetchColumn();
    } catch (Exception $e) {}
}

function _sidebarItem(array $item, string $currentPath, int $badge = 0): void {
    $isActive = ($currentPath === $item['href']) ||
                ($item['href'] === '/admin/content'
                    && str_starts_with($currentPath, '/admin/content')
                    && $currentPath !== '/admin/content/new');
    ?>
    <a href="<?= h($item['href']) ?>"
       class="sidebar-link group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-medium transition-all"
       style="<?= $isActive
           ? 'background:rgba(117,11,37,.85);color:#fff'
           : 'color:rgba(255,255,255,.45)' ?>">
      <span class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 transition-colors"
            style="<?= $isActive ? 'background:rgba(255,255,255,.15)' : 'background:rgba(255,255,255,.05)' ?>">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
          <path d="<?= h($item['svg']) ?>"/>
        </svg>
      </span>
      <span class="flex-grow leading-none"><?= h($item['label']) ?></span>
      <?php if ($badge > 0): ?>
        <span class="text-[9px] font-black w-5 h-5 rounded-full flex items-center justify-center shrink-0"
              style="background:#E7952A;color:#0D0203"><?= min($badge, 9) ?><?= $badge > 9 ? '+' : '' ?></span>
      <?php endif; ?>
    </a>
    <?php
}
?>

<style>
  .sidebar-link:not([style*="background:rgba(154"]):hover {
    background: rgba(255,255,255,.07) !important;
    color: rgba(255,255,255,.85) !important;
  }
  #admin-sidebar { z-index: 40; }
  @media (max-width: 768px) {
    #admin-sidebar {
      position: fixed; top: 0; left: 0; height: 100vh;
      transform: translateX(-100%);
      transition: transform .25s ease;
    }
    #admin-sidebar.open { transform: translateX(0); }
  }
</style>

<aside id="admin-sidebar"
       class="w-60 h-screen flex flex-col overflow-y-auto shrink-0"
       style="background:#0D0102;border-right:1px solid rgba(255,255,255,.055)">

  <!-- Branding -->
  <div class="flex items-center justify-between px-4 h-[64px] border-b shrink-0" style="border-color:rgba(255,255,255,.055)">
    <a href="/" aria-label="CRTP — Home" class="flex items-center">
      <div class="bg-white rounded-lg px-2 py-1.5 shadow-sm">
        <img src="/public/crtp-logo.png"
             alt="CRTP"
             class="h-7 w-auto object-contain block"
             onerror="this.parentElement.innerHTML='<span class=\'font-outfit font-black text-sm text-[#750B25] px-0.5\'>CRTP</span>'">
      </div>
    </a>
    <span class="text-[9px] font-black uppercase tracking-[.16em] ml-2 shrink-0" style="color:rgba(231,149,42,.55)">Admin Portal</span>
  </div>

  <!-- Navigation -->
  <nav class="flex-grow px-2 pt-6 pb-4 overflow-y-auto space-y-5">

    <div>
      <p class="px-3 mb-2 text-[9px] font-black uppercase tracking-[.15em]" style="color:rgba(255,255,255,.22)">
        Workspace
      </p>
      <div class="space-y-0.5">
        <?php foreach ($_navCore as $item): _sidebarItem($item, $_path); endforeach; ?>
      </div>
    </div>

    <?php if ($_role === 'SUPER_ADMIN'): ?>
    <div>
      <div class="flex items-center gap-2 px-3 mb-2">
        <p class="text-[9px] font-black uppercase tracking-[.15em]" style="color:rgba(239,68,68,.5)">Super Authority</p>
        <span class="h-px flex-grow" style="background:rgba(239,68,68,.12)"></span>
      </div>
      <div class="space-y-0.5">
        <?php foreach ($_navSuper as $item):
            $badge = ($item['href'] === '/admin/super/approvals') ? $_pendingCount : 0;
            _sidebarItem($item, $_path, $badge);
        endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </nav>

  <!-- User Footer -->
  <div class="px-2 pb-3 border-t shrink-0" style="border-color:rgba(255,255,255,.055)">
    <div class="flex items-center gap-2.5 px-3 py-3 mt-3 rounded-xl" style="background:rgba(255,255,255,.04)">
      <div class="w-8 h-8 rounded-xl flex items-center justify-center font-black text-xs text-white shrink-0"
           style="background:#750B25"><?= h($_initial) ?></div>
      <div class="flex-grow min-w-0">
        <p class="text-[12px] font-bold text-white truncate leading-none"><?= h($_name) ?></p>
        <p class="text-[9px] mt-0.5 truncate" style="color:rgba(255,255,255,.3)">
          <?= $_role === 'SUPER_ADMIN' ? 'Super Admin' : 'Editor' ?>
        </p>
      </div>
      <a href="/admin/logout" title="Sign Out"
         class="w-7 h-7 flex items-center justify-center rounded-lg shrink-0 transition-all"
         style="color:rgba(255,255,255,.25)"
         onmouseover="this.style.color='#fca5a5';this.style.background='rgba(239,68,68,.12)'"
         onmouseout="this.style.color='rgba(255,255,255,.25)';this.style.background='transparent'">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
          <path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
        </svg>
      </a>
    </div>
  </div>
</aside>

<!-- Mobile overlay -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-30 hidden md:hidden" onclick="toggleSidebar()"></div>

<script>
function toggleSidebar() {
  var s = document.getElementById('admin-sidebar');
  var o = document.getElementById('sidebar-overlay');
  var open = s.classList.toggle('open');
  o.classList.toggle('hidden', !open);
}
</script>
