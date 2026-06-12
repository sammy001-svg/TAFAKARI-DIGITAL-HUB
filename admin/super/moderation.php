<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

require_super_admin();
$pdo = db();

$flagged   = $pdo->query(
    "SELECT c.id, c.content, c.name, c.email, c.createdAt, p.id AS postId, p.title AS postTitle
     FROM Comment c LEFT JOIN Post p ON c.postId = p.id
     WHERE c.isFlagged = 1 AND c.isModerated = 0
     ORDER BY c.createdAt ASC"
)->fetchAll();

$totalComments     = (int)$pdo->query("SELECT COUNT(*) FROM Comment")->fetchColumn();
$flaggedCount      = (int)$pdo->query("SELECT COUNT(*) FROM Comment WHERE isFlagged=1")->fetchColumn();
$moderatedCount    = (int)$pdo->query("SELECT COUNT(*) FROM Comment WHERE isModerated=1")->fetchColumn();

$pageTitle = 'Comment Moderation | Tafakari Admin';
?>
<?php include dirname(__DIR__, 2) . '/includes/head.php'; ?>
<body class="antialiased bg-slate-100 font-inter">
<div class="flex h-screen overflow-hidden">
<?php include dirname(__DIR__, 2) . '/includes/admin-sidebar.php'; ?>

<div class="flex-1 overflow-y-auto p-10">
  <div class="flex items-center gap-4 mb-10">
    <h1 class="font-outfit text-3xl font-bold text-slate-900">Comment Moderation</h1>
    <span class="px-3 py-1 rounded-full text-xs font-black bg-emerald-50 text-emerald-700">Auto-Moderation Active</span>
  </div>

  <?php if (empty($flagged)): ?>
    <div class="bg-white rounded-4xl p-16 shadow-sm border border-slate-100 text-center">
      <div class="text-5xl mb-4">🛡️</div>
      <h3 class="font-outfit font-bold text-xl text-slate-900">No flagged comments</h3>
      <p class="text-slate-400 mt-2 text-sm">All comments are clean — nothing to review.</p>
    </div>
  <?php else: ?>
    <div class="space-y-4 max-w-3xl mb-10">
      <?php foreach ($flagged as $c): ?>
        <div class="bg-white rounded-3xl border border-slate-100 p-7 shadow-sm" id="comment-<?= h($c['id']) ?>">
          <div class="flex items-start gap-4 mb-4">
            <div class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center text-sm font-bold text-slate-600 shrink-0">
              <?= strtoupper(substr($c['name'] ?: 'A', 0, 1)) ?>
            </div>
            <div class="min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="font-bold text-sm text-slate-800"><?= h($c['name'] ?: 'Anonymous') ?></span>
                <?php if ($c['email']): ?><span class="text-xs text-slate-400"><?= h($c['email']) ?></span><?php endif; ?>
                <span class="text-xs text-slate-400">&bull; <?= format_relative_time($c['createdAt']) ?></span>
              </div>
              <?php if ($c['postTitle']): ?>
                <p class="text-xs text-slate-400 mt-0.5">On: <a href="/news/<?= h($c['postId']) ?>" target="_blank" class="text-primary hover:underline"><?= h($c['postTitle']) ?></a></p>
              <?php endif; ?>
            </div>
          </div>
          <blockquote class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 italic mb-5">
            "<?= h(substr($c['content'], 0, 400)) ?><?= strlen($c['content']) > 400 ? '…' : '' ?>"
          </blockquote>
          <div id="err-<?= h($c['id']) ?>" class="hidden mb-3 text-rose-600 text-xs font-bold"></div>
          <div class="flex gap-3">
            <button onclick="acceptComment('<?= h($c['id']) ?>')"
                    class="px-5 py-2 text-[10px] font-black uppercase tracking-widest bg-emerald-100 text-emerald-700 rounded-xl hover:bg-emerald-200 transition-colors">
              ✓ Accept
            </button>
            <button onclick="deleteComment('<?= h($c['id']) ?>')"
                    class="px-5 py-2 text-[10px] font-black uppercase tracking-widest bg-rose-100 text-rose-700 rounded-xl hover:bg-rose-200 transition-colors">
              🗑 Delete
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="grid grid-cols-3 gap-4 max-w-3xl">
    <?php
    $stats = [
      ['label'=>'Total Comments',   'value'=>$totalComments,  'cls'=>'bg-slate-50 border-slate-200 text-slate-700'],
      ['label'=>'Manually Reviewed','value'=>$moderatedCount, 'cls'=>'bg-emerald-50 border-emerald-200 text-emerald-700'],
      ['label'=>'Currently Flagged','value'=>$flaggedCount,   'cls'=>'bg-amber-50 border-amber-200 text-amber-700'],
    ];
    foreach ($stats as $s): ?>
      <div class="p-5 rounded-2xl border text-center <?= $s['cls'] ?>">
        <div class="text-2xl font-black"><?= $s['value'] ?></div>
        <div class="text-[10px] font-black uppercase tracking-widest mt-1"><?= h($s['label']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
</div>

<script>
function acceptComment(id) {
  fetch('/api/comments/'+id, {
    method: 'PUT',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({isModerated:true, isFlagged:false})
  }).then(r=>r.json()).then(function(d){
    if (d.error) { showErr(id,d.error); return; }
    document.getElementById('comment-'+id).remove();
  }).catch(function(){ showErr(id,'Request failed'); });
}

function deleteComment(id) {
  if (!confirm('Permanently delete this comment?')) return;
  fetch('/api/comments/'+id, { method:'DELETE' })
    .then(r=>r.json()).then(function(d){
      if (d.error) { showErr(id,d.error); return; }
      document.getElementById('comment-'+id).remove();
    }).catch(function(){ showErr(id,'Request failed'); });
}

function showErr(id, msg) {
  var el = document.getElementById('err-'+id);
  el.textContent = msg; el.classList.remove('hidden');
}
</script>
</body>
</html>
