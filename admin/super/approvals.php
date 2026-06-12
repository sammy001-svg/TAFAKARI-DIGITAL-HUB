<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

require_super_admin();

$pending = db()->query(
    "SELECT p.id, p.title, p.type, p.country, p.region, p.issueCategory, p.createdAt,
            u.name AS authorName, u.username AS authorUsername
     FROM Post p LEFT JOIN User u ON p.authorId = u.id
     WHERE p.status = 'PENDING'
     ORDER BY p.createdAt ASC"
)->fetchAll();

$pendingCount = count($pending);

$pageTitle      = 'Approval Queue | Tafakari Admin';
$adminPageTitle = 'Approval Queue';
$adminPageSub   = $pendingCount > 0 ? $pendingCount . ' submission' . ($pendingCount !== 1 ? 's' : '') . ' awaiting review' : 'Queue is clear';
?>
<?php include dirname(__DIR__, 2) . '/includes/head.php'; ?>
<body class="antialiased font-inter" style="background:#F4F6F8">
<div class="flex h-screen overflow-hidden">
<?php include dirname(__DIR__, 2) . '/includes/admin-sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden min-w-0">
<?php include dirname(__DIR__, 2) . '/includes/admin-topbar.php'; ?>

<main class="flex-1 overflow-y-auto p-6 md:p-8">

  <?php if (empty($pending)): ?>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-16 text-center max-w-lg mx-auto">
      <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-5" style="background:rgba(16,185,129,.08)">
        <svg width="28" height="28" fill="none" stroke="#10B981" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
          <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
      <h3 class="font-outfit font-bold text-xl text-slate-900">Queue is clear</h3>
      <p class="text-slate-400 mt-2 text-sm">No submissions are awaiting review right now.</p>
    </div>

  <?php else: ?>
    <div class="space-y-4 max-w-3xl">
      <?php
      $typeIcons = [
        'ARTICLE'       => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'GALLERY_IMAGE' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
        'PODCAST'       => 'M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z',
        'VIDEO'         => 'M15 10l4.553-2.069A1 1 0 0121 8.87v6.26a1 1 0 01-1.447.9L15 14M3 8a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z',
        'DOCUMENT'      => 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z',
      ];
      foreach ($pending as $p):
        $author = $p['authorName'] ?? $p['authorUsername'] ?? '—';
        $svg    = $typeIcons[$p['type']] ?? $typeIcons['ARTICLE'];
      ?>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden" id="item-<?= h($p['id']) ?>">
          <div class="p-6">
            <div class="flex items-start gap-4">
              <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                   style="background:rgba(117,11,37,.06)">
                <svg width="18" height="18" fill="none" stroke="#750B25" stroke-width="1.8"
                     stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <path d="<?= $svg ?>"/>
                </svg>
              </div>
              <div class="flex-grow min-w-0">
                <h3 class="font-outfit font-semibold text-[15px] text-slate-900 truncate"><?= h($p['title']) ?></h3>
                <p class="text-[11px] text-slate-400 mt-1 flex flex-wrap gap-1.5">
                  <span class="font-semibold text-slate-600"><?= h($author) ?></span>
                  <span>&bull;</span>
                  <span><?= h($p['country']) ?></span>
                  <span>&bull;</span>
                  <span><?= h($p['issueCategory']) ?></span>
                  <span>&bull;</span>
                  <span><?= h($p['region']) ?></span>
                  <span>&bull;</span>
                  <span><?= format_relative_time($p['createdAt']) ?></span>
                </p>
              </div>
              <a href="/admin/content/<?= h($p['id']) ?>/edit" target="_blank"
                 class="inline-flex items-center gap-1.5 px-3 py-2 text-[10px] font-bold rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors shrink-0">
                Review
                <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
              </a>
            </div>

            <!-- Rejection notes (hidden) -->
            <div id="reject-form-<?= h($p['id']) ?>" class="hidden mt-5">
              <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">
                Rejection Notes <span class="text-rose-500">*</span>
              </label>
              <textarea id="notes-<?= h($p['id']) ?>" rows="3"
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 resize-none"
                        style="focus-ring-color:#750B25"
                        placeholder="Explain why this submission is being rejected…"></textarea>
            </div>
            <div id="err-<?= h($p['id']) ?>" class="hidden mt-2 text-rose-600 text-xs font-semibold"></div>

            <div class="flex flex-wrap gap-2.5 mt-5">
              <button onclick="approve('<?= h($p['id']) ?>')"
                      class="inline-flex items-center gap-1.5 px-5 py-2.5 text-[11px] font-bold rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Approve & Publish
              </button>
              <button id="reject-btn-<?= h($p['id']) ?>" onclick="showReject('<?= h($p['id']) ?>')"
                      class="inline-flex items-center gap-1.5 px-5 py-2.5 text-[11px] font-bold rounded-xl bg-rose-50 text-rose-700 hover:bg-rose-100 transition-colors border border-rose-200">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Reject
              </button>
              <button id="reject-submit-<?= h($p['id']) ?>" onclick="reject('<?= h($p['id']) ?>')"
                      class="hidden inline-flex items-center gap-1.5 px-5 py-2.5 text-[11px] font-bold rounded-xl text-white hover:opacity-90 transition-opacity"
                      style="background:#EF4444">
                Confirm Rejection
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
function showReject(id) {
  document.getElementById('reject-form-'+id).classList.remove('hidden');
  document.getElementById('reject-btn-'+id).classList.add('hidden');
  document.getElementById('reject-submit-'+id).classList.remove('hidden');
}

function approve(id) {
  if (!confirm('Approve and publish this post?')) return;
  fetch('/api/posts/'+id+'/approve', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({action:'APPROVE'})
  }).then(r=>r.json()).then(function(d) {
    if (d.error) { showErr(id, d.error); return; }
    document.getElementById('item-'+id).remove();
    if (!document.querySelector('[id^="item-"]')) location.reload();
  }).catch(function(){ showErr(id, 'Request failed.'); });
}

function reject(id) {
  var notes = document.getElementById('notes-'+id).value.trim();
  if (!notes) { showErr(id, 'Rejection notes are required.'); return; }
  fetch('/api/posts/'+id+'/approve', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({action:'REJECT', rejectionNotes: notes})
  }).then(r=>r.json()).then(function(d) {
    if (d.error) { showErr(id, d.error); return; }
    document.getElementById('item-'+id).remove();
    if (!document.querySelector('[id^="item-"]')) location.reload();
  }).catch(function(){ showErr(id, 'Request failed.'); });
}

function showErr(id, msg) {
  var el = document.getElementById('err-'+id);
  el.textContent = msg;
  el.classList.remove('hidden');
}
</script>
</body>
</html>
