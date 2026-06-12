<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

require_super_admin();
$pdo = db();

$messages    = $pdo->query("SELECT * FROM ContactMessage ORDER BY isRead ASC, createdAt DESC")->fetchAll();
$unreadCount = (int)$pdo->query("SELECT COUNT(*) FROM ContactMessage WHERE isRead=0")->fetchColumn();

$pageTitle      = 'Contact Inbox | Tafakari Admin';
$adminPageTitle = 'Contact Inbox';
$adminPageSub   = count($messages) . ' total · ' . $unreadCount . ' unread';
?>
<?php include dirname(__DIR__, 2) . '/includes/head.php'; ?>
<body class="antialiased font-inter" style="background:#F4F6F8">
<div class="flex h-screen overflow-hidden">
<?php include dirname(__DIR__, 2) . '/includes/admin-sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden min-w-0">
<?php include dirname(__DIR__, 2) . '/includes/admin-topbar.php'; ?>

<main class="flex-1 overflow-y-auto p-6 md:p-8">
  <div class="flex items-center gap-3 mb-6">
    <?php if ($unreadCount > 0): ?>
      <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-amber-50 text-amber-700 border border-amber-200"><?= $unreadCount ?> unread</span>
    <?php endif; ?>
  </div>

  <?php if (empty($messages)): ?>
    <div class="bg-white rounded-4xl p-16 shadow-sm border border-slate-100 text-center">
      <div class="text-5xl mb-4">📬</div>
      <h3 class="font-outfit font-bold text-xl text-slate-900">No messages yet</h3>
      <p class="text-slate-400 mt-2 text-sm">Contact form submissions will appear here.</p>
    </div>
  <?php else: ?>
    <div class="space-y-3 max-w-3xl">
      <?php foreach ($messages as $m): ?>
        <div class="bg-white rounded-3xl border shadow-sm overflow-hidden <?= $m['isRead'] ? 'border-slate-100' : 'border-amber-200' ?>"
             id="msg-<?= h($m['id']) ?>">
          <!-- Collapsed header -->
          <div class="flex items-center gap-4 p-6 cursor-pointer msg-header" onclick="toggleMsg('<?= h($m['id']) ?>')">
            <?php if (!$m['isRead']): ?>
              <div class="w-2 h-2 rounded-full shrink-0" style="background:#D99F51"></div>
            <?php else: ?>
              <div class="w-2 h-2 rounded-full bg-slate-200 shrink-0"></div>
            <?php endif; ?>
            <div class="flex-grow min-w-0">
              <div class="flex items-center gap-3 flex-wrap">
                <span class="font-bold text-slate-900 text-sm"><?= h($m['fullName']) ?></span>
                <span class="text-xs text-slate-400"><?= h($m['email']) ?></span>
              </div>
              <div class="flex items-center gap-2 mt-1 flex-wrap">
                <?php if ($m['country']): ?><span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-slate-100 text-slate-600"><?= h($m['country']) ?></span><?php endif; ?>
                <?php if ($m['interest']): ?><span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-amber-50 text-amber-700"><?= h($m['interest']) ?></span><?php endif; ?>
                <span class="text-xs text-slate-400"><?= format_relative_time($m['createdAt']) ?></span>
              </div>
              <p class="text-xs text-slate-500 mt-1 line-clamp-1"><?= h($m['message']) ?></p>
            </div>
          </div>
          <!-- Expanded body -->
          <div class="msg-body hidden border-t border-slate-100 p-6">
            <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line mb-6"><?= h($m['message']) ?></p>
            <div class="flex gap-3 flex-wrap">
              <a href="mailto:<?= h($m['email']) ?>?subject=Re: Your inquiry to Tafakari Hub"
                 class="btn-primary text-xs px-5 py-2.5">
                ✉ Reply by Email
              </a>
              <?php if (!$m['isRead']): ?>
                <button onclick="markRead('<?= h($m['id']) ?>')"
                        class="px-5 py-2.5 text-xs font-black uppercase tracking-widest bg-emerald-100 text-emerald-700 rounded-xl hover:bg-emerald-200 transition-colors">
                  ✓ Mark as Read
                </button>
              <?php endif; ?>
              <button onclick="deleteMsg('<?= h($m['id']) ?>')"
                      class="px-5 py-2.5 text-xs font-black uppercase tracking-widest bg-rose-100 text-rose-700 rounded-xl hover:bg-rose-200 transition-colors">
                🗑 Delete
              </button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</main>
</div>
</div>

<script>
function toggleMsg(id) {
  var body = document.querySelector('#msg-'+id+' .msg-body');
  body.classList.toggle('hidden');
}

function markRead(id) {
  fetch('/api/contact/'+id, { method:'PATCH', headers:{'Content-Type':'application/json'} })
    .then(r=>r.json()).then(function(d){
      if (d.error) { alert(d.error); return; }
      // Remove amber border and dot
      var el = document.getElementById('msg-'+id);
      el.classList.remove('border-amber-200'); el.classList.add('border-slate-100');
      var dot = el.querySelector('.msg-header div:first-child');
      if (dot) { dot.style.background=''; dot.classList.add('bg-slate-200'); }
      // Hide button
      var btn = el.querySelector('[onclick*="markRead"]');
      if (btn) btn.remove();
    }).catch(function(){ alert('Request failed'); });
}

function deleteMsg(id) {
  if (!confirm('Delete this message permanently?')) return;
  fetch('/api/contact/'+id, { method:'DELETE' })
    .then(r=>r.json()).then(function(d){
      if (d.error) { alert(d.error); return; }
      document.getElementById('msg-'+id).remove();
    }).catch(function(){ alert('Request failed'); });
}
</script>
</body>
</html>
