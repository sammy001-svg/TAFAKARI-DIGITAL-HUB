<?php
/*
 * Admin Topbar — include AFTER setting these in the calling page:
 *   $adminPageTitle  (string) — page heading shown in topbar
 *   $adminPageSub    (string) — optional subtitle / meta line
 */
$_tb_title = $adminPageTitle ?? ($pageTitle ?? 'Admin');
$_tb_sub   = $adminPageSub   ?? '';

// Pending count for notification bell
$_tb_pending = 0;
if (is_super_admin()) {
    try { $_tb_pending = (int)db()->query("SELECT COUNT(*) FROM Post WHERE status='PENDING'")->fetchColumn(); }
    catch (Exception $e) {}
}
?>
<header class="flex items-center gap-4 px-6 h-[60px] bg-white border-b shrink-0"
        style="border-color:rgba(0,0,0,.07)">

  <!-- Mobile: sidebar toggle -->
  <button class="md:hidden w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 transition-colors shrink-0"
          onclick="toggleSidebar()">
    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"
         stroke-linecap="round" viewBox="0 0 24 24">
      <path d="M4 6h16M4 12h16M4 18h16"/>
    </svg>
  </button>

  <!-- Separator dot + breadcrumb -->
  <div class="hidden md:flex items-center gap-2 text-[11px] text-slate-400 font-medium">
    <a href="/admin/dashboard" class="hover:text-slate-700 transition-colors">Dashboard</a>
    <?php if ($_tb_title !== 'Dashboard Overview'): ?>
      <span>&rsaquo;</span>
      <span class="text-slate-700 font-semibold"><?= h($_tb_title) ?></span>
    <?php endif; ?>
  </div>

  <div class="flex-grow"></div>

  <!-- Right actions -->
  <div class="flex items-center gap-2">

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
            style="background:#750B25"><?= min($_tb_pending, 9) ?><?= $_tb_pending > 9 ? '+' : '' ?></span>
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

    <!-- Divider -->
    <span class="h-5 w-px bg-slate-200 mx-1"></span>

    <!-- Create content CTA -->
    <a href="/admin/content/new"
       class="hidden sm:inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-[12px] font-bold text-white transition-all hover:opacity-90"
       style="background:#750B25">
      <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5"
           stroke-linecap="round" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
      New Content
    </a>

  </div>
</header>
