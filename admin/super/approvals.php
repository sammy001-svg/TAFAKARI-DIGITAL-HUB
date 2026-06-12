<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

require_super_admin();

$filterType    = trim($_GET['type']    ?? '');
$filterCountry = trim($_GET['country'] ?? '');

$validTypes = ['ARTICLE','VIDEO','PODCAST','DOCUMENT','GALLERY_IMAGE'];
if (!in_array($filterType, $validTypes)) $filterType = '';

$whereParts = ["p.status = 'PENDING'"];
$params     = [];
if ($filterType    !== '') { $whereParts[] = 'p.type = ?';    $params[] = $filterType;    }
if ($filterCountry !== '') { $whereParts[] = 'p.country = ?'; $params[] = $filterCountry; }
$where = 'WHERE ' . implode(' AND ', $whereParts);

$pdo  = db();
$stmt = $pdo->prepare(
    "SELECT p.id, p.title, p.type, p.country, p.region, p.issueCategory,
            p.description, p.thumbnailUrl, SUBSTRING(p.content, 1, 800) AS contentPreview,
            p.createdAt, u.name AS authorName, u.username AS authorUsername
     FROM Post p LEFT JOIN User u ON p.authorId = u.id
     $where ORDER BY p.createdAt ASC"
);
$stmt->execute($params);
$pending = $stmt->fetchAll();

// Countries and types present in the full pending queue (for filter dropdowns)
$allPending = $pdo->query(
    "SELECT DISTINCT p.type, p.country FROM Post p WHERE p.status='PENDING' ORDER BY p.type, p.country"
)->fetchAll();
$availableTypes     = array_unique(array_column($allPending, 'type'));
$availableCountries = array_unique(array_column($allPending, 'country'));

$pendingCount   = count($pending);
$totalPending   = (int)$pdo->query("SELECT COUNT(*) FROM Post WHERE status='PENDING'")->fetchColumn();

$typeLabels = ['ARTICLE'=>'Article','VIDEO'=>'Video','PODCAST'=>'Podcast','DOCUMENT'=>'Document','GALLERY_IMAGE'=>'Gallery'];

$pageTitle      = 'Approval Queue | Tafakari Admin';
$adminPageTitle = 'Approval Queue';
$adminPageSub   = $totalPending > 0 ? $totalPending . ' submission' . ($totalPending !== 1 ? 's' : '') . ' awaiting review' : 'Queue is clear';
?>
<?php include dirname(__DIR__, 2) . '/includes/head.php'; ?>
<body class="antialiased font-inter" style="background:#F4F6F8">
<div class="flex h-screen overflow-hidden">
<?php include dirname(__DIR__, 2) . '/includes/admin-sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden min-w-0">
<?php include dirname(__DIR__, 2) . '/includes/admin-topbar.php'; ?>

<main class="flex-1 overflow-y-auto p-6 md:p-8">

  <?php if ($totalPending === 0): ?>
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

    <!-- Filter bar -->
    <form method="GET" action="/admin/super/approvals" class="flex flex-wrap items-center gap-3 mb-6">
      <?php if (!empty($availableTypes) && count($availableTypes) > 1): ?>
        <select name="type" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none shadow-sm min-w-[140px]">
          <option value="">All Types</option>
          <?php foreach ($availableTypes as $t): ?>
            <option value="<?= h($t) ?>" <?= $filterType === $t ? 'selected' : '' ?>><?= h($typeLabels[$t] ?? $t) ?></option>
          <?php endforeach; ?>
        </select>
      <?php endif; ?>
      <?php if (!empty($availableCountries) && count($availableCountries) > 1): ?>
        <select name="country" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none shadow-sm min-w-[140px]">
          <option value="">All Countries</option>
          <?php foreach ($availableCountries as $c): ?>
            <option value="<?= h($c) ?>" <?= $filterCountry === $c ? 'selected' : '' ?>><?= h($c) ?></option>
          <?php endforeach; ?>
        </select>
      <?php endif; ?>
      <?php if ($filterType !== '' || $filterCountry !== ''): ?>
        <button type="submit" class="px-4 py-2.5 rounded-xl text-[12px] font-bold text-white shadow-sm" style="background:#750B25">Filter</button>
        <a href="/admin/super/approvals" class="px-4 py-2.5 rounded-xl text-[12px] font-bold border border-slate-200 text-slate-600 hover:bg-slate-50 bg-white shadow-sm">Clear</a>
      <?php else: ?>
        <?php if (!empty($availableTypes) && count($availableTypes) > 1 || !empty($availableCountries) && count($availableCountries) > 1): ?>
          <button type="submit" class="px-4 py-2.5 rounded-xl text-[12px] font-bold text-white shadow-sm" style="background:#750B25">Filter</button>
        <?php endif; ?>
      <?php endif; ?>
      <div class="ml-auto text-[11px] text-slate-400 font-semibold">
        <?= $pendingCount ?> shown <?= $filterType || $filterCountry ? '(filtered)' : '' ?>
        &bull; <?= $totalPending ?> total
      </div>
    </form>

    <?php if (empty($pending)): ?>
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-10 text-center max-w-lg mx-auto">
        <p class="text-slate-500 text-sm">No submissions match the selected filters.</p>
        <a href="/admin/super/approvals" class="text-[11px] font-bold mt-3 inline-block" style="color:#750B25">Clear filters</a>
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
          $author  = $p['authorName'] ?? $p['authorUsername'] ?? '—';
          $svg     = $typeIcons[$p['type']] ?? $typeIcons['ARTICLE'];
          $preview = trim($p['contentPreview'] ?? '');
          $desc    = trim($p['description']    ?? '');
        ?>
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden" id="item-<?= h($p['id']) ?>">
            <div class="p-6">

              <!-- Header row -->
              <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                     style="background:rgba(117,11,37,.06)">
                  <svg width="18" height="18" fill="none" stroke="#750B25" stroke-width="1.8"
                       stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="<?= $svg ?>"/>
                  </svg>
                </div>
                <div class="flex-grow min-w-0">
                  <div class="flex items-center gap-2 flex-wrap mb-1">
                    <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest bg-slate-100 text-slate-600">
                      <?= h($typeLabels[$p['type']] ?? $p['type']) ?>
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest" style="background:rgba(231,149,42,.15);color:#C47C1A">
                      <?= h($p['country']) ?>
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest bg-slate-100 text-slate-500">
                      <?= h($p['issueCategory']) ?>
                    </span>
                  </div>
                  <h3 class="font-outfit font-semibold text-[15px] text-slate-900 leading-snug"><?= h($p['title']) ?></h3>
                  <p class="text-[11px] text-slate-400 mt-1">
                    by <span class="font-semibold text-slate-600"><?= h($author) ?></span>
                    &bull; <?= format_relative_time($p['createdAt']) ?>
                    <?php if (!empty($p['region'])): ?> &bull; <?= h($p['region']) ?><?php endif; ?>
                  </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                  <button onclick="togglePreview('<?= h($p['id']) ?>')"
                          id="preview-btn-<?= h($p['id']) ?>"
                          class="inline-flex items-center gap-1.5 px-3 py-2 text-[10px] font-bold rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors border border-indigo-200">
                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    Preview
                  </button>
                  <a href="/admin/content/<?= h($p['id']) ?>/edit" target="_blank"
                     class="inline-flex items-center gap-1.5 px-3 py-2 text-[10px] font-bold rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">
                    Edit
                    <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                  </a>
                </div>
              </div>

              <!-- Inline Preview (collapsible) -->
              <div id="preview-<?= h($p['id']) ?>" class="hidden mt-5 border-t border-slate-100 pt-5">
                <div class="flex gap-4">
                  <?php if (!empty($p['thumbnailUrl'])): ?>
                    <img src="<?= h($p['thumbnailUrl']) ?>" alt=""
                         class="w-24 h-16 rounded-xl object-cover shrink-0 border border-slate-100"
                         onerror="this.style.display='none'">
                  <?php endif; ?>
                  <div class="flex-grow min-w-0">
                    <?php if ($desc !== ''): ?>
                      <p class="text-sm text-slate-700 font-medium mb-2"><?= h($desc) ?></p>
                    <?php endif; ?>
                    <?php if ($preview !== ''): ?>
                      <p class="text-[12px] text-slate-500 leading-relaxed">
                        <?= h(mb_strimwidth($preview, 0, 600, '…')) ?>
                      </p>
                    <?php else: ?>
                      <p class="text-[12px] text-slate-400 italic">No content body to preview.</p>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <!-- Rejection notes field (hidden) -->
              <div id="reject-form-<?= h($p['id']) ?>" class="hidden mt-5">
                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">
                  Rejection Notes <span class="text-rose-500">*</span>
                </label>
                <textarea id="notes-<?= h($p['id']) ?>" rows="3"
                          class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 resize-none"
                          placeholder="Explain why this submission is being rejected…"></textarea>
              </div>
              <div id="err-<?= h($p['id']) ?>" class="hidden mt-2 text-rose-600 text-xs font-semibold"></div>

              <!-- Action buttons -->
              <div class="flex flex-wrap gap-2.5 mt-5">
                <button onclick="approve('<?= h($p['id']) ?>')"
                        class="inline-flex items-center gap-1.5 px-5 py-2.5 text-[11px] font-bold rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200">
                  <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  Approve &amp; Publish
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
  <?php endif; ?>

</main>
</div>
</div>

<script>
function togglePreview(id) {
  var el  = document.getElementById('preview-' + id);
  var btn = document.getElementById('preview-btn-' + id);
  var open = el.classList.toggle('hidden');
  btn.style.background = open ? '' : 'rgba(99,102,241,.15)';
}

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
    var el = document.getElementById('item-'+id);
    el.style.opacity = '0'; el.style.transition = 'opacity .3s';
    setTimeout(function(){ el.remove(); if (!document.querySelector('[id^="item-"]')) location.reload(); }, 300);
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
    var el = document.getElementById('item-'+id);
    el.style.opacity = '0'; el.style.transition = 'opacity .3s';
    setTimeout(function(){ el.remove(); if (!document.querySelector('[id^="item-"]')) location.reload(); }, 300);
  }).catch(function(){ showErr(id, 'Request failed.'); });
}

function showErr(id, msg) {
  var el = document.getElementById('err-'+id);
  el.textContent = msg; el.classList.remove('hidden');
}
</script>
</body>
</html>
