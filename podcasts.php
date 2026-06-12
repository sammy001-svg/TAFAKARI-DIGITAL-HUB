<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// ── Filter params ──────────────────────────────────────────────────────────────
$country = trim($_GET['country'] ?? '');
$cat     = trim($_GET['cat']     ?? '');

$podcasts       = [];
$countryOptions = [];

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

    $stmt = db()->prepare(
        "SELECT id, title, description, thumbnailUrl, mediaUrl, country, issueCategory, createdAt
         FROM Post WHERE $whereStr ORDER BY createdAt DESC LIMIT 40"
    );
    $stmt->execute($params);
    $podcasts = $stmt->fetchAll();
} catch (Exception $e) { /* DB not ready */ }

// ── Detect if a URL points to a streamable audio file ─────────────────────────
function is_audio_file(string $url): bool {
    $path = parse_url($url, PHP_URL_PATH) ?? '';
    $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return in_array($ext, ['mp3', 'ogg', 'wav', 'm4a', 'aac', 'opus', 'flac']);
}

$hasFilter = $country !== '' || $cat !== '';
$pageTitle = 'Podcast Library | Tafakari Digital Hub';
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex flex-col font-inter" style="background:#F7F3EC">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- ── Page header ──────────────────────────────────────────────────────────── -->
<div style="background:#0D0102" class="py-14 px-6">
  <div class="max-w-4xl mx-auto">
    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-4 text-amber-900" style="background:#D99F51">Audio Stories</span>
    <h1 class="font-outfit text-4xl md:text-5xl font-black text-white leading-tight mb-3">Podcast Library</h1>
    <p class="text-white/60 max-w-xl">Interviews, field discussions, and community voices from researchers, policymakers, and local experts.</p>
  </div>
</div>

<main class="flex-grow max-w-4xl mx-auto px-6 py-12 w-full">

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
              style="background:#D99F51;color:#0D0102">
        Filter
      </button>
      <?php if ($hasFilter): ?>
        <a href="/podcasts" class="px-6 py-3 rounded-xl font-bold text-sm border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">Clear</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- ── Results count ────────────────────────────────────────────────────── -->
  <?php if (!empty($podcasts)): ?>
    <p class="text-sm text-slate-500 mb-6">
      <strong class="text-slate-800"><?= count($podcasts) ?></strong> episode<?= count($podcasts) !== 1 ? 's' : '' ?>
      <?= $country ? ' from <strong class="text-slate-700">' . h($country) . '</strong>' : '' ?>
      <?= $cat ? ($country ? ' · ' : ' ') . '<strong class="text-slate-700">' . h($cat) . '</strong>' : '' ?>
    </p>
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
        <a href="/podcasts" class="inline-block px-6 py-3 rounded-xl font-bold text-sm" style="background:#D99F51;color:#0D0102">View All Episodes</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="space-y-5 mb-16" id="episode-list">
      <?php foreach ($podcasts as $idx => $ep):
        $hasAudio  = !empty($ep['mediaUrl']) && is_audio_file($ep['mediaUrl']);
        $hasLink   = !empty($ep['mediaUrl']) && !$hasAudio;
        $playerId  = 'player-' . $idx;
        $audioId   = 'audio-' . $idx;
      ?>
        <div class="bg-white rounded-2xl border border-amber-100 shadow-sm overflow-hidden transition-all duration-200 hover:shadow-md hover:border-amber-200">

          <!-- Top section: cover + info + controls -->
          <div class="flex items-start gap-5 p-6">

            <!-- Cover / play button -->
            <div class="shrink-0">
              <?php if (!empty($ep['thumbnailUrl'])): ?>
                <div class="w-16 h-16 rounded-2xl overflow-hidden shadow-sm">
                  <img src="<?= h($ep['thumbnailUrl']) ?>" alt="" class="w-full h-full object-cover">
                </div>
              <?php else: ?>
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center" style="background:linear-gradient(135deg,#1a0203,#3B0708)">
                  <svg width="28" height="28" fill="none" stroke="#D99F51" stroke-width="1.5" viewBox="0 0 24 24">
                    <path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>
                  </svg>
                </div>
              <?php endif; ?>
            </div>

            <!-- Episode details -->
            <div class="flex-grow min-w-0">
              <div class="flex flex-wrap gap-2 mb-2">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest text-amber-900" style="background:#D99F51"><?= h($ep['country']) ?></span>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-slate-100 text-slate-600"><?= h($ep['issueCategory']) ?></span>
              </div>
              <h3 class="font-outfit font-bold text-lg text-slate-900 leading-snug mb-1"><?= h($ep['title']) ?></h3>
              <?php if (!empty($ep['description'])): ?>
                <p class="text-sm text-slate-500 line-clamp-2 mb-3"><?= h($ep['description']) ?></p>
              <?php endif; ?>
              <div class="flex items-center gap-4">
                <span class="text-xs text-slate-400"><?= format_date($ep['createdAt']) ?></span>

                <?php if ($hasAudio): ?>
                  <button onclick="togglePlayer('<?= $playerId ?>', '<?= $audioId ?>')"
                          id="<?= $playerId ?>-btn"
                          class="flex items-center gap-1.5 text-xs font-bold transition-colors"
                          style="color:#B07D2A">
                    <svg id="<?= $playerId ?>-icon" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                    <span id="<?= $playerId ?>-label">Play Episode</span>
                  </button>
                <?php elseif ($hasLink): ?>
                  <a href="<?= h($ep['mediaUrl']) ?>" target="_blank" rel="noopener noreferrer"
                     class="flex items-center gap-1.5 text-xs font-bold transition-colors" style="color:#B07D2A">
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
                          style="background:#D99F51;color:#0D0102">
                    <svg id="<?= $playerId ?>-icon2" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                  </button>
                <?php else: ?>
                  <a href="<?= h($ep['mediaUrl']) ?>" target="_blank" rel="noopener noreferrer"
                     class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-sm transition-all hover:scale-105"
                     style="background:#D99F51;color:#0D0102">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15,3 21,3 21,9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                  </a>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Expandable HTML5 audio player (only for direct audio files) -->
          <?php if ($hasAudio): ?>
            <div id="<?= $audioId ?>" class="hidden border-t border-amber-50 px-6 py-4" style="background:#FFFBF2">
              <audio id="<?= $audioId ?>-el" controls preload="none"
                     class="w-full rounded-xl" style="height:44px;accent-color:#D99F51">
                <source src="<?= h($ep['mediaUrl']) ?>">
                Your browser does not support audio playback.
              </audio>
              <p class="text-xs text-slate-400 mt-2">
                Direct file — plays in your browser. Having trouble?
                <a href="<?= h($ep['mediaUrl']) ?>" target="_blank" rel="noopener noreferrer" class="font-bold underline" style="color:#B07D2A">Open file directly</a>
              </p>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- ── Subscribe section ────────────────────────────────────────────────── -->
  <div class="bg-white rounded-3xl border border-amber-100 p-8 text-center shadow-sm">
    <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4" style="background:linear-gradient(135deg,#1a0203,#3B0708)">
      <svg width="28" height="28" fill="none" stroke="#D99F51" stroke-width="1.5" viewBox="0 0 24 24">
        <path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>
      </svg>
    </div>
    <h3 class="font-outfit font-bold text-xl mb-2 text-slate-900">Subscribe &amp; Never Miss an Episode</h3>
    <p class="text-slate-500 text-sm mb-6 max-w-sm mx-auto">Follow our podcast on your favourite platform for the latest conversations on peace, conflict, and community.</p>
    <div class="flex flex-wrap justify-center gap-3">
      <?php foreach ([
          ['name' => 'Spotify', 'icon' => '#1DB954'],
          ['name' => 'Apple Podcasts', 'icon' => '#FF2D55'],
          ['name' => 'Google Podcasts', 'icon' => '#4285F4'],
          ['name' => 'RSS Feed', 'icon' => '#F26522'],
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
var openPlayers = {};

function togglePlayer(playerId, audioId) {
    var panel = document.getElementById(audioId);
    var audioEl = document.getElementById(audioId + '-el');
    var label = document.getElementById(playerId + '-label');
    var icon  = document.getElementById(playerId + '-icon');
    var icon2 = document.getElementById(playerId + '-icon2');

    var isOpen = !panel.classList.contains('hidden');

    if (isOpen) {
        panel.classList.add('hidden');
        if (audioEl) audioEl.pause();
        if (label) label.textContent = 'Play Episode';
        if (icon) icon.innerHTML = '<path d="M8 5v14l11-7z"/>';
        if (icon2) icon2.innerHTML = '<path d="M8 5v14l11-7z"/>';
    } else {
        panel.classList.remove('hidden');
        if (audioEl) audioEl.play().catch(function(){});
        if (label) label.textContent = 'Pause';
        if (icon) icon.innerHTML = '<path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>';
        if (icon2) icon2.innerHTML = '<path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>';
        // scroll to player
        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}
</script>
</body>
</html>
