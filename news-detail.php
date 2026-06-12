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
            // Increment view count (fire-and-forget style)
            db()->prepare('UPDATE Post SET viewCount = viewCount + 1 WHERE id = ?')->execute([$id]);
        }
    } catch (Exception $e) { /* ignore */ }
}

if (!$post) {
    http_response_code(404);
    $pageTitle = '404 Not Found | Tafakari Digital Hub';
    include __DIR__ . '/includes/head.php';
    echo '<body class="bg-slate-50 flex flex-col min-h-screen font-inter">';
    include __DIR__ . '/includes/navbar.php';
    echo '<main class="flex-grow flex flex-col items-center justify-center py-24 text-center px-6">
            <div class="text-6xl mb-6">📭</div>
            <h1 class="font-outfit text-4xl font-black text-slate-900 mb-4">Article Not Found</h1>
            <p class="text-slate-500 mb-8">This article may have been removed or is not yet published.</p>
            <a href="/news" class="btn-primary">Back to News</a>
          </main>';
    include __DIR__ . '/includes/footer.php';
    echo '</body></html>';
    exit;
}

// Load comments
$comments = [];
try {
    $stmt = db()->prepare(
        'SELECT id, content, name, email, createdAt FROM Comment
         WHERE postId = ? AND isModerated = 1 AND isFlagged = 0
         ORDER BY createdAt DESC'
    );
    $stmt->execute([$id]);
    $comments = $stmt->fetchAll();
} catch (Exception $e) { /* ignore */ }

// Handle comment submission
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
            $stmt = db()->prepare(
                'INSERT INTO Comment (id, content, name, email, postId, isModerated, isFlagged, createdAt)
                 VALUES (?, ?, ?, ?, ?, 0, 0, NOW(3))'
            );
            $stmt->execute([generate_id(), $content, $name ?: null, $email ?: null, $id]);
            $commentSuccess = true;
        } catch (Exception $e) {
            $commentError = 'Could not submit your comment. Please try again.';
        }
    }
}

$pageTitle = h($post['title']) . ' | Tafakari Digital Hub';
$pageDesc  = $post['description'] ?? '';
$authorDisplay = $post['authorName'] ?? $post['authorUsername'] ?? 'Staff';
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex flex-col bg-slate-50 font-inter">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="flex-grow max-w-4xl mx-auto px-6 py-16">

  <!-- Breadcrumb -->
  <nav class="flex items-center gap-2 text-xs text-slate-400 mb-8">
    <a href="/news" class="hover:text-primary transition-colors font-bold">News</a>
    <span>&rsaquo;</span>
    <span class="text-slate-600 truncate max-w-xs"><?= h($post['issueCategory']) ?></span>
  </nav>

  <!-- Badges -->
  <div class="flex flex-wrap gap-2 mb-6">
    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-slate-100 text-slate-600"><?= h($post['country']) ?></span>
    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest text-white" style="background:#D99F51;color:#020617"><?= h($post['issueCategory']) ?></span>
  </div>

  <!-- Title & Meta -->
  <h1 class="font-outfit text-3xl md:text-4xl font-black text-slate-900 leading-tight mb-4"><?= h($post['title']) ?></h1>
  <?php if (!empty($post['description'])): ?>
    <p class="text-lg text-slate-600 leading-relaxed mb-6"><?= h($post['description']) ?></p>
  <?php endif; ?>
  <div class="flex flex-wrap items-center gap-4 text-xs text-slate-400 mb-10 pb-6 border-b border-slate-200">
    <span>By <strong class="text-slate-600"><?= h($authorDisplay) ?></strong></span>
    <span>&bull; <?= format_date($post['createdAt'], 'F j, Y') ?></span>
    <span>&bull; <?= h($post['region']) ?></span>
    <?php if ($post['viewCount'] > 0): ?>
      <span>&bull; <?= format_number((int)$post['viewCount']) ?> views</span>
    <?php endif; ?>
  </div>

  <!-- Hero Image -->
  <?php if (!empty($post['thumbnailUrl'])): ?>
    <div class="w-full h-80 rounded-3xl overflow-hidden mb-10 border border-slate-100">
      <img src="<?= h($post['thumbnailUrl']) ?>" alt="<?= h($post['title']) ?>" class="w-full h-full object-cover">
    </div>
  <?php endif; ?>

  <!-- Content -->
  <?php if (!empty($post['content'])): ?>
    <article class="prose max-w-none mb-12">
      <?= markdown_to_html($post['content']) ?>
    </article>
  <?php endif; ?>

  <!-- Media Link -->
  <?php if (!empty($post['mediaUrl'])): ?>
    <div class="my-8 p-6 bg-slate-100 rounded-2xl border border-slate-200">
      <p class="text-sm font-bold text-slate-600 mb-3">📎 Attached Media</p>
      <a href="<?= h($post['mediaUrl']) ?>" target="_blank" rel="noopener noreferrer"
         class="btn-primary text-sm">Open Media &rarr;</a>
    </div>
  <?php endif; ?>

  <!-- Comments -->
  <section class="mt-16">
    <h2 class="font-outfit font-bold text-2xl mb-8 text-slate-900">
      Comments
      <?php if (!empty($comments)): ?>
        <span class="text-base font-normal text-slate-400 ml-2">(<?= count($comments) ?>)</span>
      <?php endif; ?>
    </h2>

    <!-- Submit comment -->
    <?php if ($commentSuccess): ?>
      <div class="p-6 bg-emerald-50 border border-emerald-200 rounded-2xl mb-8 text-center">
        <p class="text-emerald-700 font-bold">✅ Comment submitted for moderation — thank you!</p>
      </div>
    <?php else: ?>
      <div class="bg-white rounded-3xl border border-slate-100 p-8 mb-10 shadow-sm">
        <h3 class="font-outfit font-bold text-lg mb-6 text-slate-900">Leave a Comment</h3>
        <?php if ($commentError): ?>
          <div class="mb-4 p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-sm"><?= h($commentError) ?></div>
        <?php endif; ?>
        <form method="POST" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Name (optional)</label>
              <input type="text" name="comment_name" value="<?= h($_POST['comment_name'] ?? '') ?>"
                     class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:outline-none text-sm" placeholder="Your name">
            </div>
            <div>
              <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Email (optional)</label>
              <input type="email" name="comment_email" value="<?= h($_POST['comment_email'] ?? '') ?>"
                     class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:outline-none text-sm" placeholder="you@example.com">
            </div>
          </div>
          <div>
            <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Comment *</label>
            <textarea name="comment_content" required rows="4"
                      class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:outline-none text-sm resize-none"
                      placeholder="Share your thoughts..."><?= h($_POST['comment_content'] ?? '') ?></textarea>
          </div>
          <div class="flex items-center justify-between">
            <p class="text-xs text-slate-400">Comments are reviewed before publishing.</p>
            <button type="submit" class="btn-primary px-6 py-3">Post Comment</button>
          </div>
        </form>
      </div>
    <?php endif; ?>

    <!-- Comment List -->
    <?php if (empty($comments)): ?>
      <p class="text-slate-400 text-sm text-center py-8">No approved comments yet. Be the first to comment.</p>
    <?php else: ?>
      <div class="space-y-4">
        <?php foreach ($comments as $c): ?>
          <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-sm">
            <div class="flex items-center gap-3 mb-3">
              <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-xs font-bold text-slate-600">
                <?= strtoupper(substr($c['name'] ?: 'A', 0, 1)) ?>
              </div>
              <div>
                <span class="font-bold text-sm text-slate-800"><?= h($c['name'] ?: 'Anonymous') ?></span>
                <span class="text-xs text-slate-400 ml-2">&bull; <?= format_relative_time($c['createdAt']) ?></span>
              </div>
            </div>
            <p class="text-slate-700 text-sm leading-relaxed"><?= h($c['content']) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <div class="mt-12">
    <a href="/news" class="text-sm font-bold hover:underline" style="color:#9A1415">&larr; Back to News</a>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
