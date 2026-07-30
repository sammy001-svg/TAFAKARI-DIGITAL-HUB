<?php
/*
 * Admin Topbar — include AFTER setting these in the calling page:
 *   $adminPageTitle  (string) — page heading shown in topbar
 *   $adminPageSub    (string) — optional subtitle / meta line
 */
$_tb_title = $adminPageTitle ?? ($pageTitle ?? 'Admin');
$_tb_sub   = $adminPageSub   ?? '';
$_tb_user  = current_user();
$_tb_name  = $_tb_user['name'] ?? $_tb_user['username'] ?? 'User';
$_tb_init  = strtoupper(substr($_tb_name, 0, 1));
$_tb_role  = $_tb_user['role'] ?? '';

// Pending count for notification bell
$_tb_pending = 0;
if (is_super_admin()) {
    try { $_tb_pending = (int)db()->query("SELECT COUNT(*) FROM Post WHERE status='PENDING'")->fetchColumn(); }
    catch (Exception $e) {}
}
?>
<header class="flex items-center gap-3 px-5 h-[64px] bg-white border-b shrink-0"
        style="border-color:rgba(0,0,0,.07)">

  <!-- Mobile: sidebar toggle -->
  <button class="md:hidden w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 transition-colors shrink-0"
          onclick="toggleSidebar()">
    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"
         stroke-linecap="round" viewBox="0 0 24 24">
      <path d="M4 6h16M4 12h16M4 18h16"/>
    </svg>
  </button>

  <!-- Breadcrumb -->
  <div class="hidden md:flex items-center gap-2 text-[11px] text-slate-400 font-medium">
    <a href="/admin/dashboard" class="hover:text-slate-700 transition-colors">Dashboard</a>
    <?php if ($_tb_title !== 'Dashboard Overview'): ?>
      <span>&rsaquo;</span>
      <span class="text-slate-700 font-semibold"><?= h($_tb_title) ?></span>
    <?php endif; ?>
  </div>

  <!-- Centre: search -->
  <div class="hidden md:flex flex-1 max-w-xs mx-4">
    <div class="relative w-full">
      <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <input type="text" placeholder="Search content…"
             class="w-full pl-9 pr-4 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:border-transparent"
             style="--tw-ring-color:rgba(117,11,37,.25)"
             onfocus="this.style.borderColor='#750B25'" onblur="this.style.borderColor=''">
    </div>
  </div>

  <div class="flex-grow"></div>

  <!-- Date chip -->
  <div class="hidden lg:flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-semibold text-slate-500 bg-slate-50 border border-slate-100 shrink-0">
    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
    <?= date('D, M j') ?>
  </div>

  <!-- Right actions -->
  <div class="flex items-center gap-1.5">

    <!-- Pending bell (super admin only) -->
    <?php if (is_super_admin() && $_tb_pending > 0): ?>
    <a href="/admin/super/approvals"
       class="relative w-9 h-9 flex items-center justify-center rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
       title="<?= $_tb_pending ?> pending approval<?= $_tb_pending !== 1 ? 's' : '' ?>">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"
           stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
      </svg>
      <span class="absolute -top-0.5 -right-0.5 w-4 h-4 rounded-full flex items-center justify-center text-[9px] font-black text-white"
            style="background:#ED1C24"><?= min($_tb_pending, 9) ?><?= $_tb_pending > 9 ? '+' : '' ?></span>
    </a>
    <?php endif; ?>

    <!-- View site -->
    <a href="/" target="_blank"
       class="w-9 h-9 flex items-center justify-center rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
       title="View public site">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
      </svg>
    </a>

    <!-- New content (desktop) -->
    <a href="/admin/content/new"
       class="hidden sm:inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-[12px] font-bold text-white transition-all hover:opacity-90 ml-1"
       style="background:#750B25">
      <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5"
           stroke-linecap="round" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
      New Content
    </a>

    <!-- Divider -->
    <span class="h-5 w-px bg-slate-200 mx-1 hidden sm:block"></span>

    <!-- User profile dropdown -->
    <div class="relative" id="tb-user-menu">
      <button onclick="toggleUserMenu()" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-xl hover:bg-slate-100 transition-colors">
        <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white font-black text-xs shrink-0"
             style="background:<?= $_tb_role === 'SUPER_ADMIN' ? '#750B25' : '#64748b' ?>">
          <?= h($_tb_init) ?>
        </div>
        <div class="hidden sm:block text-left">
          <p class="text-[12px] font-bold text-slate-800 leading-none"><?= h($_tb_name) ?></p>
          <p class="text-[9px] text-slate-400 mt-0.5 uppercase tracking-widest">
            <?= $_tb_role === 'SUPER_ADMIN' ? 'Super Admin' : 'Admin' ?>
          </p>
        </div>
        <svg width="12" height="12" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24" class="hidden sm:block">
          <path d="M19 9l-7 7-7-7"/>
        </svg>
      </button>

      <!-- Dropdown -->
      <div id="tb-user-dropdown"
           class="hidden absolute right-0 top-full mt-2 w-52 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 z-50">
        <!-- User info header -->
        <div class="px-4 py-3 border-b border-slate-100">
          <p class="text-[12px] font-bold text-slate-900 truncate"><?= h($_tb_name) ?></p>
          <p class="text-[10px] text-slate-400 truncate mt-0.5"><?= h($_tb_user['email'] ?? '') ?></p>
        </div>
        <div class="py-1">
          <a href="/admin/dashboard"
             class="flex items-center gap-3 px-4 py-2.5 text-[12px] font-medium text-slate-700 hover:bg-slate-50 hover:text-primary transition-colors">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Dashboard
          </a>
          <a href="/admin/profile"
             class="flex items-center gap-3 px-4 py-2.5 text-[12px] font-medium text-slate-700 hover:bg-slate-50 hover:text-primary transition-colors">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            My Profile
          </a>
          <a href="/admin/content"
             class="flex items-center gap-3 px-4 py-2.5 text-[12px] font-medium text-slate-700 hover:bg-slate-50 hover:text-primary transition-colors">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            My Content
          </a>
          <a href="/" target="_blank"
             class="flex items-center gap-3 px-4 py-2.5 text-[12px] font-medium text-slate-700 hover:bg-slate-50 transition-colors">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            View Site
          </a>
        </div>
        <div class="border-t border-slate-100 pt-1">
          <a href="/admin/logout"
             class="flex items-center gap-3 px-4 py-2.5 text-[12px] font-medium text-rose-600 hover:bg-rose-50 transition-colors">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            Sign Out
          </a>
        </div>
      </div>
    </div>

  </div>
</header>

<script>
function toggleUserMenu() {
  var dd = document.getElementById('tb-user-dropdown');
  dd.classList.toggle('hidden');
}
document.addEventListener('click', function(e) {
  var menu = document.getElementById('tb-user-menu');
  if (menu && !menu.contains(e.target)) {
    document.getElementById('tb-user-dropdown').classList.add('hidden');
  }
});
<?php if (isset($_GET['updated'])):
  $_msgs = [
    'saved'     => ['Changes saved successfully.',                              '#0f172a'],
    'published' => ['Changes saved and published.',                             '#16a34a'],
    'submitted' => ['Changes saved and submitted for approval.',                '#E7952A'],
    'requeued'  => ['Changes saved. Item unpublished and sent for re-approval.', '#E7952A'],
  ];
  $_m = $_msgs[$_GET['updated']] ?? $_msgs['saved']; ?>
document.addEventListener('DOMContentLoaded', function() {
  var b = document.createElement('div');
  b.textContent = <?= json_encode($_m[0]) ?>;
  b.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;padding:12px 20px;'
    + 'border-radius:12px;font-size:13px;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,.2);'
    + 'color:#fff;background:<?= $_m[1] ?>';
  document.body.appendChild(b);
  setTimeout(function(){ b.remove(); }, 4500);
});
<?php endif; ?>
</script>
