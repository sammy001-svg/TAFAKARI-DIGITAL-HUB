<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

require_super_admin();

$filterType    = trim($_GET['type']    ?? '');
$filterCountry = trim($_GET['country'] ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$perPage       = 20;

$validTypes = ['ARTICLE','VIDEO','PODCAST','DOCUMENT','GALLERY_IMAGE'];
if (!in_array($filterType, $validTypes)) $filterType = '';

$whereParts = ["p.status = 'PENDING'"];
$params     = [];
if ($filterType    !== '') { $whereParts[] = 'p.type = ?';    $params[] = $filterType;    }
if ($filterCountry !== '') { $whereParts[] = 'p.country = ?'; $params[] = $filterCountry; }
$where = 'WHERE ' . implode(' AND ', $whereParts);

$pdo = db();

// Count matching for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM Post p $where");
$countStmt->execute($params);
$totalMatching = (int) $countStmt->fetchColumn();
$pages = max(1, (int) ceil($totalMatching / $perPage));
$page  = min($page, $pages);
$off   = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    "SELECT p.id, p.title, p.type, p.country, p.region, p.issueCategory,
            p.description, p.thumbnailUrl, p.mediaUrl,
            SUBSTRING(p.content, 1, 800) AS contentPreview,
            p.createdAt, u.name AS authorName, u.username AS authorUsername
     FROM Post p LEFT JOIN User u ON p.authorId = u.id
     $where ORDER BY p.createdAt ASC
     LIMIT $perPage OFFSET $off"
);
$stmt->execute($params);
$pending = $stmt->fetchAll();

// Countries and types present in the full pending queue
$allPending = $pdo->query(
    "SELECT DISTINCT p.type, p.country FROM Post p WHERE p.status='PENDING' ORDER BY p.type, p.country"
)->fetchAll();
$availableTypes     = array_unique(array_column($allPending, 'type'));
$availableCountries = array_unique(array_column($allPending, 'country'));

// Per-type counts for stats bar
$typeCountRows = $pdo->query(
    "SELECT type, COUNT(*) as cnt FROM Post WHERE status='PENDING' GROUP BY type ORDER BY cnt DESC"
)->fetchAll(PDO::FETCH_KEY_PAIR);

$totalPending = (int)$pdo->query("SELECT COUNT(*) FROM Post WHERE status='PENDING'")->fetchColumn();

$typeLabels = ['ARTICLE'=>'Articles','VIDEO'=>'Videos','PODCAST'=>'Podcasts','DOCUMENT'=>'Documents','GALLERY_IMAGE'=>'Gallery'];
$typeIcons  = [
    'ARTICLE'       => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    'GALLERY_IMAGE' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
    'PODCAST'       => 'M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z',
    'VIDEO'         => 'M15 10l4.553-2.069A1 1 0 0121 8.87v6.26a1 1 0 01-1.447.9L15 14M3 8a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z',
    'DOCUMENT'      => 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z',
];

function appr_url(int $n, string $type, string $country): string {
    $p = array_filter(['type' => $type, 'country' => $country, 'page' => $n > 1 ? $n : ''],
        fn($v) => $v !== '' && $v !== null);
    return '/admin/super/approvals' . ($p ? '?' . http_build_query($p) : '');
}

// Helpers for media preview
function appr_youtube_id(string $url): string {
    if (preg_match('/(?:youtube\.com\/(?:watch\?.*v=|shorts\/|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m)) return $m[1];
    return '';
}
function appr_vimeo_id(string $url): string {
    if (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) return $m[1];
    return '';
}
function appr_is_audio(string $url): bool {
    $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
    return in_array($ext, ['mp3', 'ogg', 'wav', 'm4a', 'aac', 'opus', 'flac']);
}

$pageTitle      = 'Approval Queue | Tafakari Admin';
$adminPageTitle = 'Approval Queue';
$adminPageSub   = $totalPending > 0
    ? $totalPending . ' submission' . ($totalPending !== 1 ? 's' : '') . ' awaiting review'
    : 'Queue is clear';
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

    <!-- Per-type stats bar -->
    <?php if (!empty($typeCountRows)): ?>
      <div class="flex flex-wrap gap-3 mb-6">
        <?php foreach ($typeCountRows as $t => $cnt):
          $isActive = $filterType === $t;
          $icon = $typeIcons[$t] ?? $typeIcons['ARTICLE'];
          $label = $typeLabels[$t] ?? $t;
        ?>
          <a href="<?= h(appr_url(1, $isActive ? '' : $t, $filterCountry)) ?>"
             class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl border text-[12px] font-bold transition-all"
             style="<?= $isActive
               ? 'background:#750B25;color:#fff;border-color:#750B25'
               : 'background:#fff;color:#374151;border-color:#e2e8f0' ?>">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
              <path d="<?= $icon ?>"/>
            </svg>
            <?= h($label) ?>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black" style="background:<?= $isActive ? 'rgba(255,255,255,.2)' : 'rgba(117,11,37,.08)' ?>;color:<?= $isActive ? '#fff' : '#750B25' ?>"><?= $cnt ?></span>
          </a>
        <?php endforeach; ?>
        <?php if ($filterType !== ''): ?>
          <a href="<?= h(appr_url(1, '', $filterCountry)) ?>"
             class="flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-dashed border-slate-300 text-[12px] font-bold text-slate-400 hover:border-slate-400 transition-colors">
            All Types
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Filter bar -->
    <form method="GET" action="/admin/super/approvals" class="flex flex-wrap items-center gap-3 mb-6">
      <?php if (!empty($availableCountries) && count($availableCountries) > 1): ?>
        <select name="country" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none shadow-sm min-w-[140px]">
          <option value="">All Countries</option>
          <?php foreach ($availableCountries as $c): ?>
            <option value="<?= h($c) ?>" <?= $filterCountry === $c ? 'selected' : '' ?>><?= h($c) ?></option>
          <?php endforeach; ?>
        </select>
      <?php endif; ?>
      <?php if ($filterType !== ''): ?>
        <input type="hidden" name="type" value="<?= h($filterType) ?>">
      <?php endif; ?>
      <?php if ($filterCountry !== '' || $filterType !== ''): ?>
        <button type="submit" class="px-4 py-2.5 rounded-xl text-[12px] font-bold text-white shadow-sm" style="background:#750B25">Filter</button>
        <a href="/admin/super/approvals" class="px-4 py-2.5 rounded-xl text-[12px] font-bold border border-slate-200 text-slate-600 hover:bg-slate-50 bg-white shadow-sm">Clear All</a>
      <?php else: ?>
        <?php if (!empty($availableCountries) && count($availableCountries) > 1): ?>
          <button type="submit" class="px-4 py-2.5 rounded-xl text-[12px] font-bold text-white shadow-sm" style="background:#750B25">Filter</button>
        <?php endif; ?>
      <?php endif; ?>
      <div class="ml-auto text-[11px] text-slate-400 font-semibold">
        <?= $totalMatching ?> <?= $filterType || $filterCountry ? 'matching' : 'pending' ?>
        <?= $pages > 1 ? '· page ' . $page . ' of ' . $pages : '' ?>
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
        <?php foreach ($pending as $p):
          $author  = $p['authorName'] ?? $p['authorUsername'] ?? '—';
          $svg     = $typeIcons[$p['type']] ?? $typeIcons['ARTICLE'];
          $preview = trim($p['contentPreview'] ?? '');
          $desc    = trim($p['description']    ?? '');
          $media   = trim($p['mediaUrl']       ?? '');
          $thumb   = trim($p['thumbnailUrl']   ?? '');

          // How old is this submission?
          $ageSeconds = time() - strtotime($p['createdAt']);
          $isOld      = $ageSeconds > 3 * 86400;
          $isVeryOld  = $ageSeconds > 7 * 86400;
        ?>
          <div class="bg-white rounded-2xl border shadow-sm overflow-hidden <?= $isVeryOld ? 'border-rose-200' : ($isOld ? 'border-amber-200' : 'border-slate-100') ?>"
               id="item-<?= h($p['id']) ?>">
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
                    <?php if ($isVeryOld): ?>
                      <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest bg-rose-50 text-rose-600">Overdue</span>
                    <?php elseif ($isOld): ?>
                      <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest bg-amber-50 text-amber-700">Waiting</span>
                    <?php endif; ?>
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

                <?php
                  $ytId = $media ? appr_youtube_id($media) : '';
                  $vmId = $media ? appr_vimeo_id($media)   : '';
                ?>

                <!-- Media preview -->
                <?php if ($p['type'] === 'PODCAST' && $media !== ''): ?>
                  <div class="mb-4 rounded-xl overflow-hidden border border-amber-100" style="background:#FFFBF2">
                    <?php if (appr_is_audio($media)): ?>
                      <div class="px-4 py-3">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Audio Player</p>
                        <audio controls preload="none" class="w-full rounded-lg" style="height:44px;accent-color:#E7952A">
                          <source src="<?= h($media) ?>">
                        </audio>
                      </div>
                    <?php else: ?>
                      <div class="px-4 py-3 flex items-center gap-3">
                        <svg width="16" height="16" fill="none" stroke="#E7952A" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                        <a href="<?= h($media) ?>" target="_blank" rel="noopener noreferrer" class="text-xs font-bold underline" style="color:#C47C1A">
                          Listen to Episode &rarr;
                        </a>
                      </div>
                    <?php endif; ?>
                  </div>
                <?php elseif ($p['type'] === 'VIDEO' && $media !== ''): ?>
                  <div class="mb-4 rounded-xl overflow-hidden border border-slate-200" style="background:#0D0102">
                    <?php if ($ytId !== ''): ?>
                      <div style="aspect-ratio:16/9">
                        <iframe src="https://www.youtube.com/embed/<?= h($ytId) ?>?rel=0&modestbranding=1"
                                title="<?= h($p['title']) ?>"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen class="w-full h-full border-0 rounded-xl"></iframe>
                      </div>
                    <?php elseif ($vmId !== ''): ?>
                      <div style="aspect-ratio:16/9">
                        <iframe src="https://player.vimeo.com/video/<?= h($vmId) ?>?title=0&byline=0&portrait=0"
                                title="<?= h($p['title']) ?>"
                                allow="autoplay; fullscreen; picture-in-picture"
                                allowfullscreen class="w-full h-full border-0 rounded-xl"></iframe>
                      </div>
                    <?php else: ?>
                      <div class="px-4 py-3 flex items-center gap-3">
                        <svg width="16" height="16" fill="none" stroke="#ED1C24" stroke-width="2" viewBox="0 0 24 24"><polygon points="5,3 19,12 5,21 5,3"/></svg>
                        <a href="<?= h($media) ?>" target="_blank" rel="noopener noreferrer" class="text-xs font-bold underline" style="color:#ED1C24">
                          Watch Video &rarr;
                        </a>
                      </div>
                    <?php endif; ?>
                  </div>
                <?php elseif ($p['type'] === 'GALLERY_IMAGE' && $thumb !== ''): ?>
                  <div class="mb-4">
                    <img src="<?= h($thumb) ?>" alt="<?= h($p['title']) ?>"
                         class="max-h-52 rounded-xl object-cover border border-slate-200"
                         onerror="this.style.display='none'">
                  </div>
                <?php elseif ($p['type'] === 'DOCUMENT' && $media !== ''): ?>
                  <div class="mb-4 flex items-center gap-3 px-4 py-3 rounded-xl border border-slate-200 bg-slate-50">
                    <svg width="16" height="16" fill="none" stroke="#4A5568" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/><path d="M14 2v6h6"/></svg>
                    <a href="<?= h($media) ?>" target="_blank" rel="noopener noreferrer" class="text-xs font-bold underline text-slate-600">
                      View / Download Document &rarr;
                    </a>
                  </div>
                <?php endif; ?>

                <!-- Text preview -->
                <div class="flex gap-4">
                  <?php if ($thumb !== '' && $p['type'] !== 'GALLERY_IMAGE'): ?>
                    <img src="<?= h($thumb) ?>" alt=""
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

      <!-- ── Pagination ──────────────────────────────────────────────────── -->
      <?php if ($pages > 1): ?>
        <nav class="flex items-center gap-2 mt-8 max-w-3xl" aria-label="Pagination">
          <?php if ($page > 1): ?>
            <a href="<?= h(appr_url($page - 1, $filterType, $filterCountry)) ?>"
               class="w-9 h-9 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-600 hover:bg-slate-50 transition-colors text-base font-light">&#8249;</a>
          <?php else: ?>
            <span class="w-9 h-9 rounded-xl border border-slate-100 flex items-center justify-center text-slate-300 text-base cursor-not-allowed">&#8249;</span>
          <?php endif; ?>

          <?php
          $start = max(1, $page - 2);
          $end   = min($pages, $page + 2);
          if ($start > 1): ?>
            <a href="<?= h(appr_url(1, $filterType, $filterCountry)) ?>" class="w-9 h-9 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-xs font-bold text-slate-600 hover:bg-slate-50 transition-colors">1</a>
            <?php if ($start > 2): ?><span class="text-slate-400 px-1 text-sm">…</span><?php endif; ?>
          <?php endif; ?>

          <?php for ($i = $start; $i <= $end; $i++): ?>
            <a href="<?= h(appr_url($i, $filterType, $filterCountry)) ?>"
               class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold transition-colors <?= $i === $page ? 'text-slate-900 border-2' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' ?>"
               style="<?= $i === $page ? 'border-color:#750B25;background:#fff5f5' : '' ?>">
              <?= $i ?>
            </a>
          <?php endfor; ?>

          <?php if ($end < $pages): ?>
            <?php if ($end < $pages - 1): ?><span class="text-slate-400 px-1 text-sm">…</span><?php endif; ?>
            <a href="<?= h(appr_url($pages, $filterType, $filterCountry)) ?>" class="w-9 h-9 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-xs font-bold text-slate-600 hover:bg-slate-50 transition-colors"><?= $pages ?></a>
          <?php endif; ?>

          <?php if ($page < $pages): ?>
            <a href="<?= h(appr_url($page + 1, $filterType, $filterCountry)) ?>"
               class="w-9 h-9 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-600 hover:bg-slate-50 transition-colors text-base font-light">&#8250;</a>
          <?php else: ?>
            <span class="w-9 h-9 rounded-xl border border-slate-100 flex items-center justify-center text-slate-300 text-base cursor-not-allowed">&#8250;</span>
          <?php endif; ?>
        </nav>
      <?php endif; ?>
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
