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
             WHERE p.id = ? AND p.type = 'DOCUMENT' AND p.status = 'PUBLISHED' LIMIT 1"
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
    echo '<main class="flex-grow flex flex-col items-center justify-center py-24 text-center px-6">
            <div class="text-6xl mb-6">📄</div>
            <h1 class="font-outfit text-4xl font-black text-slate-900 mb-4">Document Not Found</h1>
            <p class="text-slate-500 mb-8">This document may have been removed or is not yet published.</p>
            <a href="/documents" class="inline-block px-6 py-3 rounded-xl font-bold text-sm" style="background:#E7952A;color:#0D0102">&larr; Back to Documents</a>
          </main>';
    include __DIR__ . '/includes/footer.php';
    echo '</body></html>';
    exit;
}

// Record download when ?download=1 is hit
if (isset($_GET['download']) && !empty($post['mediaUrl'])) {
    try {
        db()->prepare('UPDATE Post SET downloadCount = downloadCount + 1 WHERE id = ?')->execute([$id]);
    } catch (Exception $e) {}
    header('Location: ' . $post['mediaUrl']);
    exit;
}

// Related documents
$related = [];
try {
    $stmt = db()->prepare(
        "SELECT id, title, thumbnailUrl, country, issueCategory, mediaUrl, createdAt
         FROM Post WHERE type='DOCUMENT' AND status='PUBLISHED' AND id != ?
           AND (country = ? OR issueCategory = ?)
         ORDER BY createdAt DESC LIMIT 3"
    );
    $stmt->execute([$id, $post['country'], $post['issueCategory']]);
    $related = $stmt->fetchAll();
} catch (Exception $e) {}

// Approved comments
$comments = [];
try {
    $stmt = db()->prepare(
        'SELECT id, content, name, email, createdAt FROM Comment
         WHERE postId = ? AND isModerated = 1 AND isFlagged = 0 ORDER BY createdAt DESC'
    );
    $stmt->execute([$id]);
    $comments = $stmt->fetchAll();
} catch (Exception $e) {}

// Comment submission
$commentError   = '';
$commentSuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_content'])) {
    $content = trim(substr($_POST['comment_content'] ?? '', 0, 2000));
    $name    = trim(substr($_POST['comment_name']    ?? '', 0, 100));
    $email   = trim(substr($_POST['comment_email']   ?? '', 0, 200));
    if (!$content) {
        $commentError = 'Comment text is required.';
    } else {
        try {
            db()->prepare(
                'INSERT INTO Comment (id, content, name, email, postId, isModerated, isFlagged, createdAt)
                 VALUES (?, ?, ?, ?, ?, 0, 0, NOW(3))'
            )->execute([generate_id(), $content, $name ?: null, $email ?: null, $id]);
            $commentSuccess = true;
        } catch (Exception $e) {
            $commentError = 'Could not submit your comment. Please try again.';
        }
    }
}

$mediaUrl      = (string)($post['mediaUrl'] ?? '');
$fileType      = $mediaUrl !== '' ? doc_file_type($mediaUrl) : 'Document';
$isPdf         = str_ends_with(strtolower(parse_url($mediaUrl, PHP_URL_PATH) ?? ''), '.pdf');
$authorDisplay = $post['authorName'] ?? $post['authorUsername'] ?? 'Staff';

$protocol    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$shareUrl    = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? '') . '/documents/' . rawurlencode($id);
$downloadUrl = '/documents/' . rawurlencode($id) . '?download=1';
$twitterUrl  = 'https://twitter.com/intent/tweet?url=' . rawurlencode($shareUrl) . '&text=' . rawurlencode($post['title']);
$facebookUrl = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($shareUrl);
$linkedinUrl = 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode($shareUrl);

$pageTitle     = strip_tags($post['title']) . ' | Tafakari Digital Hub';
$pageDesc      = $post['description'] ?? '';
$pageImage     = $post['thumbnailUrl'] ?? '';
$pageType      = 'document';
$pageAuthor    = $authorDisplay;
$pagePublished = $post['createdAt'] ?? '';
$pageModified  = $post['updatedAt'] ?? $post['createdAt'] ?? '';
$pageMediaUrl  = $mediaUrl;
$pageUrl       = $shareUrl;
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex flex-col font-inter" style="background:#F8F8F0">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="flex-grow">

  <!-- ── Document hero ──────────────────────────────────────────────────── -->
  <div style="background:#0D0102" class="py-12 px-6">
    <div class="max-w-4xl mx-auto">
      <nav class="flex items-center gap-2 text-xs text-white/40 mb-6">
        <a href="/documents" class="hover:text-amber-400 transition-colors font-bold text-white/60">Documents</a>
        <span>&rsaquo;</span>
        <span class="text-white/40 truncate max-w-xs"><?= h($post['issueCategory'] ?? '') ?></span>
      </nav>
      <div class="flex flex-wrap gap-2 mb-5">
        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest text-slate-900" style="background:#E7952A"><?= h($post['country'] ?? '') ?></span>
        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest" style="background:rgba(231,149,42,.15);color:#E7952A"><?= h($post['issueCategory'] ?? '') ?></span>
        <?php if ($fileType !== 'Document'): ?>
          <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-indigo-500/20 text-indigo-300"><?= h($fileType) ?></span>
        <?php endif; ?>
      </div>
      <h1 class="font-outfit text-3xl md:text-4xl font-black text-white leading-tight mb-4"><?= h($post['title']) ?></h1>
      <?php if (!empty($post['description'])): ?>
        <p class="text-lg text-white/60 leading-relaxed mb-6 max-w-2xl"><?= h($post['description']) ?></p>
      <?php endif; ?>
      <div class="flex flex-wrap items-center gap-4 text-xs text-white/40">
        <span>By <strong class="text-white/70"><?= h($authorDisplay) ?></strong></span>
        <span>&bull; <?= format_date($post['createdAt'] ?? '', 'F j, Y') ?></span>
        <?php if (!empty($post['region'])): ?><span>&bull; <?= h($post['region']) ?></span><?php endif; ?>
        <?php if (($post['downloadCount'] ?? 0) > 0): ?><span>&bull; <?= format_number((int)$post['downloadCount']) ?> downloads</span><?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ── Main ───────────────────────────────────────────────────────────── -->
  <div class="max-w-4xl mx-auto px-6 py-12">

    <!-- Download card -->
    <?php if (!empty($mediaUrl)): ?>
      <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6 bg-white rounded-3xl border border-indigo-100 shadow-md p-6 md:p-8 mb-10">
        <!-- File icon -->
        <div class="w-16 h-20 rounded-2xl flex flex-col items-center justify-center shrink-0 shadow-sm"
             style="background:#6366F115;border:1px solid #6366F130">
          <svg width="24" height="24" fill="none" stroke="#6366F1" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
          </svg>
          <span class="text-[9px] font-black mt-1" style="color:#6366F1"><?= h($fileType) ?></span>
        </div>
        <!-- Info + buttons -->
        <div class="flex-1 min-w-0">
          <p class="font-outfit font-bold text-lg text-slate-900 mb-1 leading-snug"><?= h($post['title']) ?></p>
          <p class="text-[11px] text-slate-400 mb-4">
            <?= h($fileType) ?> &bull; <?= h($post['country'] ?? '') ?>
            <?php if (($post['downloadCount'] ?? 0) > 0): ?> &bull; <?= format_number((int)$post['downloadCount']) ?> downloads<?php endif; ?>
          </p>
          <div class="flex flex-wrap gap-3">
            <a href="<?= h($downloadUrl) ?>"
               class="inline-flex items-center gap-2 px-5 py-3 rounded-xl font-bold text-sm transition-all hover:brightness-110"
               style="background:#6366F1;color:#fff">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
              Download <?= h($fileType) ?>
            </a>
            <?php if ($isPdf): ?>
              <a href="<?= h($mediaUrl) ?>" target="_blank" rel="noopener noreferrer"
                 class="inline-flex items-center gap-2 px-5 py-3 rounded-xl font-bold text-sm border border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/></svg>
                Preview in Browser
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Cover image -->
    <?php if (!empty($post['thumbnailUrl'])): ?>
      <div class="w-full h-64 md:h-80 rounded-3xl overflow-hidden mb-10 border border-amber-100 shadow-md">
        <img src="<?= h($post['thumbnailUrl']) ?>" alt="<?= h($post['title']) ?>" class="w-full h-full object-cover">
      </div>
    <?php endif; ?>

    <!-- Abstract / body -->
    <?php if (!empty($post['content'])): ?>
      <h2 class="font-outfit font-bold text-xl text-slate-900 mb-4">Abstract / Summary</h2>
      <article class="prose max-w-none mb-12 bg-white rounded-3xl border border-amber-100 p-8 md:p-10 shadow-sm">
        <?= markdown_to_html($post['content']) ?>
      </article>
    <?php endif; ?>

    <!-- Share -->
    <div class="my-10 flex flex-col sm:flex-row sm:items-center gap-4 p-6 bg-white rounded-2xl border border-amber-100 shadow-sm">
      <span class="text-sm font-black uppercase tracking-widest text-slate-400 shrink-0">Share</span>
      <div class="flex flex-wrap gap-3">
        <a href="<?= h($twitterUrl) ?>" target="_blank" rel="noopener noreferrer"
           class="flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.746l7.73-8.835L1.254 2.25H8.08l4.253 5.622 5.91-5.622Zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
          Twitter / X
        </a>
        <a href="<?= h($facebookUrl) ?>" target="_blank" rel="noopener noreferrer"
           class="flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.791-4.697 4.533-4.697 1.312 0 2.686.235 2.686.235v2.97h-1.513c-1.491 0-1.956.93-1.956 1.887v2.267h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>
          Facebook
        </a>
        <a href="<?= h($linkedinUrl) ?>" target="_blank" rel="noopener noreferrer"
           class="flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="#0077B5"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
          LinkedIn
        </a>
        <button id="copy-btn" onclick="copyLink()"
                class="flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
          <span id="copy-label">Copy Link</span>
        </button>
      </div>
    </div>

    <!-- Comments -->
    <section class="mt-12">
      <h2 class="font-outfit font-bold text-2xl mb-8 text-slate-900">
        Comments
        <?php if (!empty($comments)): ?><span class="text-base font-normal text-slate-400 ml-2">(<?= count($comments) ?>)</span><?php endif; ?>
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
          <form method="POST">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
              <div>
                <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase tracking-widest">Name (optional)</label>
                <input type="text" name="comment_name" maxlength="100"
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none">
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase tracking-widest">Email (optional)</label>
                <input type="email" name="comment_email" maxlength="200"
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none">
              </div>
            </div>
            <div class="mb-5">
              <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase tracking-widest">Comment *</label>
              <textarea name="comment_content" rows="4" required maxlength="2000"
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none resize-none"></textarea>
            </div>
            <button type="submit" class="px-6 py-3 rounded-xl font-bold text-sm transition-all hover:brightness-110"
                    style="background:#E7952A;color:#0D0102">Submit Comment</button>
          </form>
        </div>
      <?php endif; ?>

      <?php if (!empty($comments)): ?>
        <div class="space-y-5">
          <?php foreach ($comments as $c): ?>
            <div class="bg-white rounded-2xl border border-amber-100 p-6 shadow-sm">
              <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-full flex items-center justify-center font-outfit font-black text-sm text-white"
                     style="background:#750B25"><?= h(strtoupper(substr($c['name'] ?: 'A', 0, 1))) ?></div>
                <div>
                  <p class="text-sm font-bold text-slate-800"><?= h($c['name'] ?: 'Anonymous') ?></p>
                  <p class="text-[10px] text-slate-400"><?= format_date($c['createdAt'], 'F j, Y') ?></p>
                </div>
              </div>
              <p class="text-sm text-slate-700 leading-relaxed"><?= nl2br(h($c['content'])) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php elseif (!$commentSuccess): ?>
        <div class="text-center py-12 bg-white rounded-2xl border border-amber-100">
          <p class="text-slate-400 text-sm">No comments yet. Be the first to respond to this document!</p>
        </div>
      <?php endif; ?>
    </section>

    <!-- Related documents -->
    <?php if (!empty($related)): ?>
      <section class="mt-16 pt-12 border-t border-slate-100">
        <h2 class="font-outfit font-bold text-xl text-slate-900 mb-6">Related Documents</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
          <?php foreach ($related as $r):
            $rFt = !empty($r['mediaUrl']) ? doc_file_type($r['mediaUrl']) : 'DOC';
          ?>
            <a href="/documents/<?= h($r['id']) ?>" class="group flex items-start gap-4 bg-white rounded-2xl p-4 border border-amber-100 hover:shadow-md hover:-translate-y-1 transition-all">
              <div class="w-10 h-12 rounded-lg flex flex-col items-center justify-center shrink-0"
                   style="background:#6366F115;border:1px solid #6366F130">
                <svg width="14" height="14" fill="none" stroke="#6366F1" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                <span class="text-[7px] font-black mt-0.5" style="color:#6366F1"><?= h($rFt) ?></span>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-[9px] font-black uppercase tracking-widest mb-1" style="color:#E7952A"><?= h($r['country']) ?></p>
                <p class="text-[12px] font-bold text-slate-800 line-clamp-2 group-hover:text-primary transition-colors"><?= h($r['title']) ?></p>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

  </div>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
function copyLink() {
  navigator.clipboard.writeText('<?= h($shareUrl) ?>').then(function() {
    var l = document.getElementById('copy-label');
    l.textContent = 'Copied!';
    setTimeout(function(){ l.textContent = 'Copy Link'; }, 2000);
  });
}
</script>
</body>
</html>
