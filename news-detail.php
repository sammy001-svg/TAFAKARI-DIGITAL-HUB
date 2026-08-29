<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$id   = $_GET['id'] ?? '';
$post = null;

if ($id) {
    try {
        $stmt = db()->prepare(
            "SELECT p.*, u.name AS authorName, u.username AS authorUsername
             FROM Post p LEFT JOIN User u ON p.authorId = u.id
             WHERE p.id = ? AND p.type = 'ARTICLE' AND p.status = 'PUBLISHED' LIMIT 1"
        );
        $stmt->execute([$id]);
        $post = $stmt->fetch();

        if ($post) {
            db()->prepare('UPDATE Post SET viewCount = viewCount + 1 WHERE id = ?')->execute([$id]);
        }
    } catch (Exception $e) { /* ignore */ }
}

if (!$post) {
    http_response_code(404);
    $pageTitle = '404 Not Found | Tafakari Digital Hub';
    include __DIR__ . '/includes/head.php';
    echo '<body class="flex flex-col min-h-screen font-inter" style="background:#F8F8F0">';
    include __DIR__ . '/includes/navbar.php';
    echo '<main class="grow flex flex-col items-center justify-center py-24 text-center px-6">
            <div class="text-6xl mb-6">📭</div>
            <h1 class="font-outfit text-4xl font-black text-slate-900 mb-4">Article Not Found</h1>
            <p class="text-slate-500 mb-8">This article may have been removed or is not yet published.</p>
            <a href="/news" class="inline-block px-6 py-3 rounded-xl font-bold text-sm" style="background:#E7952A;color:#0D0102">&larr; Back to News</a>
          </main>';
    include __DIR__ . '/includes/footer.php';
    echo '</body></html>';
    exit;
}

// ── Load approved comments ─────────────────────────────────────────────────────
ensure_comment_rating_column();
$comments = [];
try {
    $stmt = db()->prepare(
        'SELECT id, content, rating, name, email, createdAt FROM Comment
         WHERE postId = ? AND isModerated = 1 AND isFlagged = 0
         ORDER BY createdAt DESC'
    );
    $stmt->execute([$id]);
    $comments = $stmt->fetchAll();
} catch (Exception $e) { /* ignore */ }
$ratedComments = array_filter($comments, fn($c) => !empty($c['rating']));
$avgRating = count($ratedComments) > 0 ? array_sum(array_column($ratedComments, 'rating')) / count($ratedComments) : null;

// ── Load related articles (same country or same category, excluding self) ─────
$related = [];
try {
    $stmt = db()->prepare(
        "SELECT id, title, thumbnailUrl, country, issueCategory, createdAt
         FROM Post
         WHERE type='ARTICLE' AND status='PUBLISHED' AND id != ?
           AND (country = ? OR FIND_IN_SET(?, issueCategory) > 0)
         ORDER BY createdAt DESC LIMIT 3"
    );
    $relCat = explode(',', $post['issueCategory'] ?? '')[0];
    $stmt->execute([$id, $post['country'], $relCat]);
    $related = $stmt->fetchAll();
} catch (Exception $e) { /* ignore */ }

// ── Handle comment submission ──────────────────────────────────────────────────
$commentError   = '';
$commentSuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_content'])) {
    $content = trim(substr($_POST['comment_content'] ?? '', 0, 2000));
    $name    = trim(substr($_POST['comment_name']    ?? '', 0, 100));
    $email   = trim(substr($_POST['comment_email']   ?? '', 0, 200));
    $rating  = (int)($_POST['comment_rating'] ?? 0);
    $rating  = ($rating >= 1 && $rating <= 5) ? $rating : null;
    if (!$content) {
        $commentError = 'Comment text is required.';
    } else {
        try {
            $stmt = db()->prepare(
                'INSERT INTO Comment (id, content, rating, name, email, postId, isModerated, isFlagged, createdAt)
                 VALUES (?, ?, ?, ?, ?, ?, 0, 0, NOW(3))'
            );
            $stmt->execute([generate_id(), $content, $rating, $name ?: null, $email ?: null, $id]);
            $commentSuccess = true;
        } catch (Exception $e) {
            $commentError = 'Could not submit your comment. Please try again.';
        }
    }
}

// ── Share URLs ─────────────────────────────────────────────────────────────────
$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$shareUrl  = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? '') . '/news/' . rawurlencode($id);
$twitterUrl  = 'https://twitter.com/intent/tweet?url=' . rawurlencode($shareUrl) . '&text=' . rawurlencode($post['title']);
$facebookUrl = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($shareUrl);
$linkedinUrl = 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode($shareUrl);

$pageTitle     = strip_tags($post['title']) . ' | Tafakari Digital Hub';
$pageDesc      = $post['description'] ?? '';
$pageImage     = $post['thumbnailUrl'] ?? '';
$pageType      = 'article';
$pagePublished = $post['createdAt'] ?? '';
$pageModified  = $post['updatedAt'] ?? $post['createdAt'] ?? '';
$pageKeywords  = implode(', ', array_filter([$post['country'] ?? '', $post['issueCategory'] ?? '', $post['region'] ?? '', 'Africa', 'research']));
$authorDisplay = $post['authorName'] ?? $post['authorUsername'] ?? 'Staff';
$pageAuthor    = $authorDisplay;
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex flex-col font-inter" style="background:#F8F8F0">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="grow">

  <!-- ── Article hero strip ──────────────────────────────────────────────── -->
  <div style="background:#0D0102" class="py-12 px-6">
    <div class="max-w-4xl mx-auto">
      <!-- Breadcrumb -->
      <nav class="flex items-center gap-2 text-xs text-white/40 mb-6">
        <a href="/news" class="hover:text-amber-400 transition-colors font-bold text-white/60">News</a>
        <span>&rsaquo;</span>
        <span class="text-white/40 truncate max-w-xs"><?= h($post['issueCategory']) ?></span>
      </nav>

      <!-- Badges -->
      <div class="flex flex-wrap gap-2 mb-5">
        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest text-slate-900" style="background:#E7952A"><?= h($post['country']) ?></span>
        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest" style="background:rgba(231,149,42,.15);color:#E7952A"><?= h($post['issueCategory']) ?></span>
      </div>

      <!-- Title -->
      <h1 class="font-outfit text-3xl md:text-4xl font-black text-white leading-tight mb-4"><?= h($post['title']) ?></h1>
      <?php if (!empty($post['byline'])): ?>
        <!-- Stated author / issuing body: sits between title and summary -->
        <p class="flex items-center gap-2 mb-4">
          <svg width="15" height="15" fill="none" stroke="#E7952A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="flex-shrink:0">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
          </svg>
          <a href="/search?q=<?= urlencode($post['byline']) ?>"
             class="text-sm font-bold hover:underline" style="color:#E7952A"
             title="See other reporting from this author or body"><?= h($post['byline']) ?></a>
        </p>
      <?php endif; ?>

      <?php if (!empty($post['description'])): ?>
        <p class="text-lg text-white/60 leading-relaxed mb-6 max-w-2xl"><?= h($post['description']) ?></p>
      <?php endif; ?>

      <!-- Meta row -->
      <div class="flex flex-wrap items-center gap-4 text-xs text-white/40">
        <span>By <strong class="text-white/70"><?= h($authorDisplay) ?></strong></span>
        <span>&bull; <?= format_date($post['createdAt'], 'F j, Y') ?></span>
        <?php if (!empty($post['region'])): ?>
          <span>&bull; <?= h($post['region']) ?></span>
        <?php endif; ?>
        <?php if ($post['viewCount'] > 0): ?>
          <span>&bull; <?= format_number((int)$post['viewCount']) ?> views</span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ── Main content area ───────────────────────────────────────────────── -->
  <div class="max-w-4xl mx-auto px-6 py-12">

    <!-- Hero image -->
    <?php if (!empty($post['thumbnailUrl'])): ?>
      <div class="w-full h-72 md:h-96 rounded-3xl overflow-hidden mb-10 border border-amber-100 shadow-md">
        <img src="<?= h($post['thumbnailUrl']) ?>" alt="<?= h($post['title']) ?>" class="w-full h-full object-cover">
      </div>
    <?php endif; ?>

    <!-- Article body -->
    <?php if (!empty($post['content'])): ?>
      <article class="prose max-w-none mb-12 bg-white rounded-3xl border border-amber-100 p-8 md:p-12 shadow-sm">
        <?= markdown_to_html($post['content']) ?>
      </article>
    <?php endif; ?>

    <!-- Attached media link -->
    <?php if (!empty($post['mediaUrl'])): ?>
      <div class="my-8 p-6 rounded-2xl border border-amber-200" style="background:#F8F8F0">
        <p class="text-sm font-bold text-amber-900 mb-3">📎 Attached Media / Source Document</p>
        <a href="<?= h($post['mediaUrl']) ?>" target="_blank" rel="noopener noreferrer"
           class="inline-block px-5 py-2.5 rounded-xl font-bold text-sm transition-all hover:brightness-110" style="background:#E7952A;color:#0D0102">
          Open Media &rarr;
        </a>
      </div>
    <?php endif; ?>

    <!-- ── Share section ──────────────────────────────────────────────────── -->
    <div class="my-10 flex flex-col sm:flex-row sm:items-center gap-4 p-6 bg-white rounded-2xl border border-amber-100 shadow-sm">
      <span class="text-sm font-black uppercase tracking-widest text-slate-400 shrink-0">Share</span>
      <div class="flex flex-wrap gap-3">
        <!-- Twitter/X -->
        <a href="<?= h($twitterUrl) ?>" target="_blank" rel="noopener noreferrer"
           class="flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.746l7.73-8.835L1.254 2.25H8.08l4.253 5.622 5.91-5.622Zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
          Twitter / X
        </a>
        <!-- Facebook -->
        <a href="<?= h($facebookUrl) ?>" target="_blank" rel="noopener noreferrer"
           class="flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.791-4.697 4.533-4.697 1.312 0 2.686.235 2.686.235v2.97h-1.513c-1.491 0-1.956.93-1.956 1.887v2.267h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>
          Facebook
        </a>
        <!-- LinkedIn -->
        <a href="<?= h($linkedinUrl) ?>" target="_blank" rel="noopener noreferrer"
           class="flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="#0077B5"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
          LinkedIn
        </a>
        <!-- Copy link -->
        <button id="copy-btn" onclick="copyLink()"
                class="flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
          <span id="copy-label">Copy Link</span>
        </button>
      </div>
    </div>

    <!-- ── Comments ───────────────────────────────────────────────────────── -->
    <section class="mt-12">
      <h2 class="font-outfit font-bold text-2xl mb-8 text-slate-900 flex flex-wrap items-center gap-3">
        Comments
        <?php if (!empty($comments)): ?>
          <span class="text-base font-normal text-slate-400">(<?= count($comments) ?>)</span>
        <?php endif; ?>
        <?php if ($avgRating !== null): ?>
          <span class="inline-flex items-center gap-1 text-sm font-bold" style="color:#E7952A">
            <?php for ($i = 1; $i <= 5; $i++): ?><span style="color:<?= $i <= round($avgRating) ? '#E7952A' : '#e2e8f0' ?>">★</span><?php endfor; ?>
            <span class="text-slate-500 font-normal text-xs ml-1"><?= number_format($avgRating, 1) ?> average (<?= count($ratedComments) ?> rating<?= count($ratedComments) !== 1 ? 's' : '' ?>)</span>
          </span>
        <?php endif; ?>
      </h2>

      <?php if ($commentSuccess): ?>
        <div class="p-6 bg-emerald-50 border border-emerald-200 rounded-2xl mb-8 text-center">
          <p class="text-emerald-700 font-bold">Comment submitted — it will appear after moderation. Thank you!</p>
        </div>
      <?php else: ?>
        <div class="bg-white rounded-3xl border border-amber-100 p-8 mb-10 shadow-sm">
          <h3 class="font-outfit font-bold text-lg mb-6 text-slate-900">Leave a Comment</h3>
          <?php if ($commentError): ?>
            <div class="mb-4 p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-sm"><?= h($commentError) ?></div>
          <?php endif; ?>
          <form method="POST" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Name (optional)</label>
                <input type="text" name="comment_name" value="<?= h($_POST['comment_name'] ?? '') ?>"
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:outline-none text-sm" placeholder="Your name">
              </div>
              <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Email (optional)</label>
                <input type="email" name="comment_email" value="<?= h($_POST['comment_email'] ?? '') ?>"
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:outline-none text-sm" placeholder="you@example.com">
              </div>
            </div>
            <div>
              <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Your Rating (optional)</label>
              <div class="flex items-center gap-1" id="star-rating">
                <?php $_r = (int)($_POST['comment_rating'] ?? 0); ?>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <button type="button" class="star-btn text-3xl leading-none transition-colors" style="color:<?= $i <= $_r ? '#E7952A' : '#cbd5e1' ?>"
                          data-value="<?= $i ?>" onclick="setCommentRating(<?= $i ?>)" aria-label="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>">★</button>
                <?php endfor; ?>
              </div>
              <input type="hidden" name="comment_rating" id="comment_rating_input" value="<?= h($_POST['comment_rating'] ?? '') ?>">
            </div>
            <div>
              <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Comment *</label>
              <textarea name="comment_content" required rows="4"
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:outline-none text-sm resize-none"
                        placeholder="Share your thoughts..."><?= h($_POST['comment_content'] ?? '') ?></textarea>
            </div>
            <div class="flex items-center justify-between">
              <p class="text-xs text-slate-400">Comments are reviewed before publishing.</p>
              <button type="submit" class="px-6 py-3 rounded-xl font-bold text-sm transition-all hover:brightness-110" style="background:#E7952A;color:#0D0102">
                Post Comment
              </button>
            </div>
          </form>
        </div>
      <?php endif; ?>

      <?php if (empty($comments)): ?>
        <p class="text-slate-400 text-sm text-center py-8 bg-white rounded-2xl border border-amber-100">No approved comments yet. Be the first to comment.</p>
      <?php else: ?>
        <div class="space-y-4">
          <?php foreach ($comments as $c): ?>
            <div class="bg-white rounded-2xl border border-amber-100 p-6 shadow-sm">
              <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold text-white" style="background:#750B25">
                  <?= strtoupper(substr($c['name'] ?: 'A', 0, 1)) ?>
                </div>
                <div>
                  <span class="font-bold text-sm text-slate-800"><?= h($c['name'] ?: 'Anonymous') ?></span>
                  <span class="text-xs text-slate-400 ml-2">&bull; <?= format_relative_time($c['createdAt']) ?></span>
                </div>
              </div>
              <?php if (!empty($c['rating'])): ?>
                <div class="flex items-center gap-0.5 mb-2" aria-label="<?= (int)$c['rating'] ?> out of 5 stars">
                  <?php for ($i = 1; $i <= 5; $i++): ?><span class="text-sm" style="color:<?= $i <= (int)$c['rating'] ? '#E7952A' : '#e2e8f0' ?>">★</span><?php endfor; ?>
                </div>
              <?php endif; ?>
              <p class="text-slate-700 text-sm leading-relaxed"><?= h($c['content']) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <script>
    function setCommentRating(val) {
      document.getElementById('comment_rating_input').value = val;
      document.querySelectorAll('#star-rating .star-btn').forEach(function(btn){
        btn.style.color = parseInt(btn.dataset.value, 10) <= val ? '#E7952A' : '#cbd5e1';
      });
    }
    </script>

    <!-- Back link -->
    <div class="mt-12 pt-8 border-t border-amber-100">
      <a href="/news" class="inline-flex items-center gap-2 text-sm font-bold transition-colors hover:text-amber-700" style="color:#C47C1A">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>
        Back to News
      </a>
    </div>
  </div>

  <!-- ── Related Articles ────────────────────────────────────────────────── -->
  <?php if (!empty($related)): ?>
    <div style="background:#0D0102" class="py-16 px-6 mt-8">
      <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-8">
          <div>
            <span class="block text-[10px] font-black uppercase tracking-widest mb-1" style="color:#E7952A">You May Also Like</span>
            <h2 class="font-outfit font-bold text-2xl text-white">Related Articles</h2>
          </div>
          <a href="/news?country=<?= urlencode($post['country']) ?>" class="text-xs font-bold hidden sm:block" style="color:#E7952A">
            More from <?= h($post['country']) ?> &rarr;
          </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <?php foreach ($related as $r): ?>
            <a href="/news/<?= h($r['id']) ?>"
               class="group rounded-2xl overflow-hidden border border-white/10 hover:border-amber-400/40 transition-all hover:-translate-y-1 duration-300 flex flex-col" style="background:#0D0102">
              <div class="h-40 overflow-hidden" style="background:#0D0102">
                <?php if (!empty($r['thumbnailUrl'])): ?>
                  <img src="<?= h($r['thumbnailUrl']) ?>" alt="<?= h($r['title']) ?>"
                       class="w-full h-full object-cover opacity-70 group-hover:opacity-90 group-hover:scale-105 transition-all duration-500">
                <?php else: ?>
                  <div class="w-full h-full flex items-center justify-center">
                    <svg width="32" height="32" fill="none" stroke="#E7952A" stroke-width="1.5" opacity=".3" viewBox="0 0 24 24"><path d="M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z"/><path d="M9 3v18M9 9h10"/></svg>
                  </div>
                <?php endif; ?>
              </div>
              <div class="p-5 flex flex-col grow">
                <span class="text-[9px] font-black uppercase tracking-widest mb-2 block" style="color:#E7952A"><?= h($r['issueCategory']) ?></span>
                <h3 class="font-outfit font-bold text-sm text-white leading-snug line-clamp-2 group-hover:text-amber-300 transition-colors grow"><?= h($r['title']) ?></h3>
                <div class="flex items-center justify-between mt-4 pt-3 border-t border-white/10">
                  <span class="text-[10px] text-white/40"><?= format_date($r['createdAt']) ?></span>
                  <span class="text-[10px] font-bold" style="color:#E7952A">Read &rarr;</span>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
function copyLink() {
  var url = '<?= addslashes($shareUrl) ?>';
  navigator.clipboard.writeText(url).then(function() {
    var label = document.getElementById('copy-label');
    label.textContent = 'Copied!';
    setTimeout(function(){ label.textContent = 'Copy Link'; }, 2000);
  }).catch(function() {
    // Fallback for older browsers
    var ta = document.createElement('textarea');
    ta.value = url;
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
    document.getElementById('copy-label').textContent = 'Copied!';
    setTimeout(function(){ document.getElementById('copy-label').textContent = 'Copy Link'; }, 2000);
  });
}
</script>
</body>
</html>
