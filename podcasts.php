<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$country = trim($_GET['country'] ?? '');
$cat     = trim($_GET['cat']     ?? '');

$podcasts       = [];
$countryOptions = [];
$total          = 0;
$pages          = 1;

$where  = ["type = 'PODCAST'", "status = 'PUBLISHED'"];
$params = [];
if ($country !== '') {
    $where[]  = 'country = ?';
    $params[] = $country;
}
if ($cat !== '') {
    $where[]  = 'issueCategory = ?';
    $params[] = $cat;
}
$whereStr = implode(' AND ', $where);

try {
    $cs = db()->query(
        "SELECT DISTINCT country FROM Post WHERE type='PODCAST' AND status='PUBLISHED' AND country IS NOT NULL AND country != '' ORDER BY country"
    );
    $countryOptions = $cs->fetchAll(PDO::FETCH_COLUMN);

    $countStmt = db()->prepare("SELECT COUNT(*) FROM Post WHERE $whereStr");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();
    $pages = max(1, (int) ceil($total / $perPage));
    $page  = min($page, $pages);
    $off   = ($page - 1) * $perPage;

    $stmt = db()->prepare(
        "SELECT id, title, description, thumbnailUrl, mediaUrl, country, issueCategory, createdAt
         FROM Post WHERE $whereStr ORDER BY createdAt DESC
         LIMIT $perPage OFFSET $off"
    );
    $stmt->execute($params);
    $podcasts = $stmt->fetchAll();
} catch (Exception $e) { /* DB not ready */ }

function is_audio_file(string $url): bool {
    $path = parse_url($url, PHP_URL_PATH) ?? '';
    $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return in_array($ext, ['mp3', 'ogg', 'wav', 'm4a', 'aac', 'opus', 'flac']);
}

function podcast_url(int $n, string $country, string $cat): string {
    $p = array_filter(['country' => $country, 'cat' => $cat, 'page' => $n > 1 ? $n : ''],
        fn($v) => $v !== '' && $v !== null);
    return '/podcasts' . ($p ? '?' . http_build_query($p) : '');
}

$hasFilter = $country !== '' || $cat !== '';
$pageTitle = 'Podcast Library | Tafakari Digital Hub';
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex flex-col font-inter" style="background:#F8F8F0">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- ── Page header ──────────────────────────────────────────────────────────── -->
<div style="background:#0D0102" class="py-14 px-6">
  <div class="max-w-7xl mx-auto">
    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-4 text-amber-900" style="background:#E7952A">Audio Stories</span>
    <h1 class="font-outfit text-4xl md:text-5xl font-black text-white leading-tight mb-3">Podcast Library</h1>
    <p class="text-white/60 max-w-xl">Interviews, field discussions, and community voices from researchers, policymakers, and local experts.</p>
  </div>
</div>

<main class="flex-grow max-w-7xl mx-auto px-6 py-12 w-full">

  <!-- ── Filter bar ───────────────────────────────────────────────────────── -->
  <div class="bg-white rounded-2xl border border-amber-100 shadow-sm p-5 mb-8">
    <form method="GET" action="/podcasts" class="flex flex-wrap gap-3 items-end">
      <div>
        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Country</label>
        <select name="country" class="px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none min-w-[150px]">
          <option value="">All Countries</option>
          <?php foreach ($countryOptions as $c): ?>
            <option value="<?= h($c) ?>" <?= $country === $c ? 'selected' : '' ?>><?= h($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Topic</label>
        <select name="cat" class="px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none min-w-[180px]">
          <option value="">All Topics</option>
          <?php foreach (issue_categories() as $c): ?>
            <option value="<?= h($c) ?>" <?= $cat === $c ? 'selected' : '' ?>><?= h($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="px-6 py-3 rounded-xl font-bold text-sm transition-all hover:brightness-110"
              style="background:#E7952A;color:#0D0102">
        Filter
      </button>
      <?php if ($hasFilter): ?>
        <a href="/podcasts" class="px-6 py-3 rounded-xl font-bold text-sm border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">Clear</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- ── Results meta ─────────────────────────────────────────────────────── -->
  <?php if ($total > 0): ?>
    <div class="flex items-center justify-between mb-6">
      <p class="text-sm text-slate-500">
        Showing <strong class="text-slate-800"><?= ($page - 1) * $perPage + 1 ?>–<?= min($page * $perPage, $total) ?></strong> of <strong class="text-slate-800"><?= $total ?></strong> episode<?= $total !== 1 ? 's' : '' ?>
        <?= $country ? ' from <strong class="text-slate-700">' . h($country) . '</strong>' : '' ?>
        <?= $cat ? ($country ? ' · ' : ' ') . '<strong class="text-slate-700">' . h($cat) . '</strong>' : '' ?>
      </p>
      <?php if ($pages > 1): ?>
        <span class="text-xs text-slate-400">Page <?= $page ?> of <?= $pages ?></span>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- ── Episode list ──────────────────────────────────────────────────────── -->
  <?php if (empty($podcasts)): ?>
    <div class="text-center py-24 bg-white rounded-3xl border border-amber-100">
      <div class="text-6xl mb-4">🎧</div>
      <h3 class="font-outfit font-bold text-xl text-slate-900">No episodes found</h3>
      <p class="text-slate-400 mt-2 text-sm mb-6">
        <?= $hasFilter ? 'Try different filter options.' : 'Podcast episodes will appear here once published.' ?>
      </p>
      <?php if ($hasFilter): ?>
        <a href="/podcasts" class="inline-block px-6 py-3 rounded-xl font-bold text-sm" style="background:#E7952A;color:#0D0102">View All Episodes</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="space-y-5" id="episode-list">
      <?php foreach ($podcasts as $idx => $ep):
        $hasAudio = !empty($ep['mediaUrl']) && is_audio_file($ep['mediaUrl']);
        $hasLink  = !empty($ep['mediaUrl']) && !$hasAudio;
        $playerId = 'player-' . $idx;
        $audioId  = 'audio-' . $idx;
      ?>
        <div class="bg-white rounded-2xl border border-amber-100 shadow-sm overflow-hidden transition-all duration-200 hover:shadow-md hover:border-amber-200">

          <!-- Top section: cover + info + controls -->
          <div class="flex items-start gap-5 p-6">

            <!-- Cover -->
            <div class="shrink-0">
              <?php if (!empty($ep['thumbnailUrl'])): ?>
                <div class="w-16 h-16 rounded-2xl overflow-hidden shadow-sm">
                  <img src="<?= h($ep['thumbnailUrl']) ?>" alt="" class="w-full h-full object-cover">
                </div>
              <?php else: ?>
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center" style="background:#0D0102">
                  <svg width="28" height="28" fill="none" stroke="#E7952A" stroke-width="1.5" viewBox="0 0 24 24">
                    <path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>
                  </svg>
                </div>
              <?php endif; ?>
            </div>

            <!-- Episode details -->
            <div class="flex-grow min-w-0">
              <div class="flex flex-wrap gap-2 mb-2">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest text-amber-900" style="background:#E7952A"><?= h($ep['country']) ?></span>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-slate-100 text-slate-600"><?= h($ep['issueCategory']) ?></span>
              </div>
              <h3 class="font-outfit font-bold text-lg text-slate-900 leading-snug mb-1">
                <a href="/podcasts/<?= h($ep['id']) ?>" class="hover:text-primary transition-colors"><?= h($ep['title']) ?></a>
              </h3>
              <?php if (!empty($ep['description'])): ?>
                <p class="text-sm text-slate-500 line-clamp-2 mb-3"><?= h($ep['description']) ?></p>
              <?php endif; ?>
              <div class="flex items-center gap-4">
                <span class="text-xs text-slate-400"><?= format_date($ep['createdAt']) ?></span>
                <?php if ($hasAudio): ?>
                  <button onclick="togglePlayer('<?= $playerId ?>', '<?= $audioId ?>')"
                          id="<?= $playerId ?>-btn"
                          class="flex items-center gap-1.5 text-xs font-bold transition-colors"
                          style="color:#ED1C24">
                    <svg id="<?= $playerId ?>-icon" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                    <span id="<?= $playerId ?>-label">Play Episode</span>
                  </button>
                <?php elseif ($hasLink): ?>
                  <a href="<?= h($ep['mediaUrl']) ?>" target="_blank" rel="noopener noreferrer"
                     class="flex items-center gap-1.5 text-xs font-bold transition-colors" style="color:#C47C1A">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                    Listen &rarr;
                  </a>
                <?php endif; ?>
              </div>
            </div>

            <!-- Large play/listen button (right) -->
            <?php if (!empty($ep['mediaUrl'])): ?>
              <div class="shrink-0 hidden sm:block">
                <?php if ($hasAudio): ?>
                  <button onclick="togglePlayer('<?= $playerId ?>', '<?= $audioId ?>')"
                          class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-sm transition-all hover:scale-105"
                          style="background:#ED1C24;color:#fff">
                    <svg id="<?= $playerId ?>-icon2" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                  </button>
                <?php else: ?>
                  <a href="<?= h($ep['mediaUrl']) ?>" target="_blank" rel="noopener noreferrer"
                     class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-sm transition-all hover:scale-105"
                     style="background:#ED1C24;color:#fff">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15,3 21,3 21,9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                  </a>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Expandable HTML5 audio player -->
          <?php if ($hasAudio): ?>
            <div id="<?= $audioId ?>" class="hidden border-t border-amber-50 px-6 py-4" style="background:#FFFBF2">
              <audio id="<?= $audioId ?>-el" controls preload="none"
                     class="w-full rounded-xl" style="height:44px;accent-color:#E7952A">
                <source src="<?= h($ep['mediaUrl']) ?>">
                Your browser does not support audio playback.
              </audio>
              <p class="text-xs text-slate-400 mt-2">
                Direct file — plays in your browser. Having trouble?
                <a href="<?= h($ep['mediaUrl']) ?>" target="_blank" rel="noopener noreferrer" class="font-bold underline" style="color:#C47C1A">Open file directly</a>
              </p>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- ── Pagination ────────────────────────────────────────────────────── -->
    <?php if ($pages > 1): ?>
      <nav class="flex items-center justify-center gap-2 mt-10 mb-10" aria-label="Pagination">
        <?php if ($page > 1): ?>
          <a href="<?= h(podcast_url($page - 1, $country, $cat)) ?>"
             class="w-10 h-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-600 hover:bg-amber-50 transition-colors text-lg font-light">&#8249;</a>
        <?php else: ?>
          <span class="w-10 h-10 rounded-xl border border-slate-100 flex items-center justify-center text-slate-300 text-lg cursor-not-allowed">&#8249;</span>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 2);
        $end   = min($pages, $page + 2);
        if ($start > 1): ?>
          <a href="<?= h(podcast_url(1, $country, $cat)) ?>" class="w-10 h-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-sm font-bold text-slate-600 hover:bg-amber-50 transition-colors">1</a>
          <?php if ($start > 2): ?><span class="text-slate-400 px-1">…</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
          <a href="<?= h(podcast_url($i, $country, $cat)) ?>"
             class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold transition-colors <?= $i === $page ? 'text-slate-900 border-2' : 'border border-slate-200 bg-white text-slate-600 hover:bg-amber-50' ?>"
             style="<?= $i === $page ? 'border-color:#E7952A;background:#F8F8F0' : '' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>

        <?php if ($end < $pages): ?>
          <?php if ($end < $pages - 1): ?><span class="text-slate-400 px-1">…</span><?php endif; ?>
          <a href="<?= h(podcast_url($pages, $country, $cat)) ?>" class="w-10 h-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-sm font-bold text-slate-600 hover:bg-amber-50 transition-colors"><?= $pages ?></a>
        <?php endif; ?>

        <?php if ($page < $pages): ?>
          <a href="<?= h(podcast_url($page + 1, $country, $cat)) ?>"
             class="w-10 h-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-600 hover:bg-amber-50 transition-colors text-lg font-light">&#8250;</a>
        <?php else: ?>
          <span class="w-10 h-10 rounded-xl border border-slate-100 flex items-center justify-center text-slate-300 text-lg cursor-not-allowed">&#8250;</span>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>

  <!-- ── Subscribe section ────────────────────────────────────────────────── -->
  <div class="bg-white rounded-3xl border border-amber-100 p-8 text-center shadow-sm mt-8">
    <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4" style="background:#0D0102">
      <svg width="28" height="28" fill="none" stroke="#E7952A" stroke-width="1.5" viewBox="0 0 24 24">
        <path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>
      </svg>
    </div>
    <h3 class="font-outfit font-bold text-xl mb-2 text-slate-900">Subscribe &amp; Never Miss an Episode</h3>
    <p class="text-slate-500 text-sm mb-6 max-w-sm mx-auto">Follow our podcast on your favourite platform for the latest conversations on peace, conflict, and community.</p>
    <div class="flex flex-wrap justify-center gap-3">
      <?php foreach ([
          ['name' => 'Spotify'],
          ['name' => 'Apple Podcasts'],
          ['name' => 'Google Podcasts'],
          ['name' => 'RSS Feed'],
      ] as $platform): ?>
        <span class="px-5 py-2.5 rounded-xl border border-amber-100 text-sm font-bold text-slate-500 cursor-not-allowed hover:bg-amber-50 transition-colors">
          <?= h($platform['name']) ?>
        </span>
      <?php endforeach; ?>
    </div>
    <p class="text-xs text-slate-400 mt-4">Platform links will be added when the podcast launches.</p>
  </div>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
function togglePlayer(playerId, audioId) {
    var panel   = document.getElementById(audioId);
    var audioEl = document.getElementById(audioId + '-el');
    var label   = document.getElementById(playerId + '-label');
    var icon    = document.getElementById(playerId + '-icon');
    var icon2   = document.getElementById(playerId + '-icon2');

    var isOpen = !panel.classList.contains('hidden');

    if (isOpen) {
        panel.classList.add('hidden');
        if (audioEl) audioEl.pause();
        if (label) label.textContent = 'Play Episode';
        if (icon)  icon.innerHTML  = '<path d="M8 5v14l11-7z"/>';
        if (icon2) icon2.innerHTML = '<path d="M8 5v14l11-7z"/>';
    } else {
        panel.classList.remove('hidden');
        if (audioEl) audioEl.play().catch(function(){});
        if (label) label.textContent = 'Pause';
        if (icon)  icon.innerHTML  = '<path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>';
        if (icon2) icon2.innerHTML = '<path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>';
        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}
</script>
</body>
</html>
