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

$pageTitle = 'Approval Queue | Tafakari Admin';
?>
<?php include dirname(__DIR__, 2) . '/includes/head.php'; ?>
<body class="antialiased bg-slate-100 font-inter">
<div class="flex h-screen overflow-hidden">
<?php include dirname(__DIR__, 2) . '/includes/admin-sidebar.php'; ?>

<div class="flex-1 overflow-y-auto p-10">
  <div class="flex items-center gap-4 mb-10">
    <h1 class="font-outfit text-3xl font-bold text-slate-900">Approval Queue</h1>
    <?php if (!empty($pending)): ?>
      <span class="px-3 py-1 rounded-full text-sm font-black bg-amber-100 text-amber-700"><?= count($pending) ?> pending</span>
    <?php endif; ?>
  </div>

  <?php if (empty($pending)): ?>
    <div class="bg-white rounded-4xl p-16 shadow-sm border border-slate-100 text-center">
      <div class="text-5xl mb-4">✅</div>
      <h3 class="font-outfit font-bold text-xl text-slate-900">Queue is clear</h3>
      <p class="text-slate-400 mt-2 text-sm">No submissions awaiting review.</p>
    </div>
  <?php else: ?>
    <div class="space-y-4 max-w-3xl">
      <?php foreach ($pending as $p):
        $author = $p['authorName'] ?? $p['authorUsername'] ?? '—';
        $typeIcon = ['ARTICLE'=>'📄','GALLERY_IMAGE'=>'🖼️','PODCAST'=>'🎧','VIDEO'=>'🎬','DOCUMENT'=>'📁'];
      ?>
        <div class="bg-white rounded-3xl border border-slate-100 p-7 shadow-sm" id="item-<?= h($p['id']) ?>">
          <div class="flex items-start gap-4">
            <div class="text-2xl"><?= $typeIcon[$p['type']] ?? '📄' ?></div>
            <div class="flex-grow min-w-0">
              <h3 class="font-outfit font-bold text-lg text-slate-900 truncate"><?= h($p['title']) ?></h3>
              <p class="text-xs text-slate-400 mt-1">
                By <strong><?= h($author) ?></strong> &bull;
                <?= h($p['country']) ?> &bull; <?= h($p['issueCategory']) ?> &bull; <?= h($p['region']) ?> &bull;
                submitted <?= format_relative_time($p['createdAt']) ?>
              </p>
            </div>
            <a href="/admin/content/<?= h($p['id']) ?>/edit" target="_blank"
               class="text-xs font-bold px-3 py-2 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 shrink-0">
              Review &rarr;
            </a>
          </div>

          <!-- Rejection notes input (hidden by default) -->
          <div id="reject-form-<?= h($p['id']) ?>" class="hidden mt-4 pt-4 border-t border-slate-100">
            <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Rejection Notes *</label>
            <textarea id="notes-<?= h($p['id']) ?>" rows="3"
                      class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none resize-none"
                      placeholder="Explain why this is being rejected..."></textarea>
          </div>
          <div id="err-<?= h($p['id']) ?>" class="hidden mt-2 text-rose-600 text-xs font-bold"></div>

          <div class="flex gap-3 mt-5">
            <button onclick="approve('<?= h($p['id']) ?>')"
                    class="px-6 py-2.5 text-xs font-black uppercase tracking-widest bg-emerald-100 text-emerald-700 rounded-xl hover:bg-emerald-200 transition-colors">
              ✓ Approve
            </button>
            <button id="reject-btn-<?= h($p['id']) ?>" onclick="showReject('<?= h($p['id']) ?>')"
                    class="px-6 py-2.5 text-xs font-black uppercase tracking-widest bg-rose-100 text-rose-700 rounded-xl hover:bg-rose-200 transition-colors">
              ✗ Reject
            </button>
            <button id="reject-submit-<?= h($p['id']) ?>" onclick="reject('<?= h($p['id']) ?>')"
                    class="hidden px-6 py-2.5 text-xs font-black uppercase tracking-widest bg-rose-600 text-white rounded-xl hover:bg-rose-700 transition-colors">
              Confirm Rejection
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
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
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({action:'APPROVE'})
  }).then(r=>r.json()).then(function(d){
    if (d.error) { showErr(id, d.error); return; }
    document.getElementById('item-'+id).remove();
    if (!document.querySelector('[id^="item-"]')) location.reload();
  }).catch(function(){ showErr(id, 'Request failed'); });
}

function reject(id) {
  var notes = document.getElementById('notes-'+id).value.trim();
  if (!notes) { showErr(id, 'Rejection notes are required.'); return; }
  fetch('/api/posts/'+id+'/approve', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({action:'REJECT', rejectionNotes: notes})
  }).then(r=>r.json()).then(function(d){
    if (d.error) { showErr(id, d.error); return; }
    document.getElementById('item-'+id).remove();
    if (!document.querySelector('[id^="item-"]')) location.reload();
  }).catch(function(){ showErr(id, 'Request failed'); });
}

function showErr(id, msg) {
  var el = document.getElementById('err-'+id);
  el.textContent = msg;
  el.classList.remove('hidden');
}
</script>
</body>
</html>
