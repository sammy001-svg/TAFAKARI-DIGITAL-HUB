<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

$user    = require_auth();
$isSuper = is_super_admin();
$uid     = $user['id'];
$id      = $_GET['id'] ?? '';

if (!$id) { header('Location: /admin/content'); exit; }

$pdo  = db();
$stmt = $pdo->prepare('SELECT * FROM Post WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) { header('Location: /admin/content'); exit; }

// Permission: ADMIN can only edit own DRAFT/REJECTED
if (!$isSuper) {
    if ($post['authorId'] !== $uid) { header('Location: /admin/content'); exit; }
    if (in_array($post['status'], ['PUBLISHED','ARCHIVED'])) { header('Location: /admin/content'); exit; }
}

$canSubmit = $isSuper || in_array($post['status'], ['DRAFT','REJECTED']);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title         = trim($_POST['title']         ?? '');
    $type          = trim($_POST['type']          ?? 'ARTICLE');
    $description   = trim($_POST['description']   ?? '');
    $content       = trim($_POST['content']       ?? '');
    $thumbnailUrl  = trim($_POST['thumbnailUrl']  ?? '');
    $mediaUrl      = trim($_POST['mediaUrl']      ?? '');
    $country       = trim($_POST['country']       ?? '');
    $region        = trim($_POST['region']        ?? '');
    $issueCategory = trim($_POST['issueCategory'] ?? '');
    $andSubmit     = isset($_POST['save_submit']);

    if (!$title)             $error = 'Title is required.';
    elseif (!$country)       $error = 'Country is required.';
    elseif (!$region)        $error = 'Region is required.';
    elseif (!$issueCategory) $error = 'Issue category is required.';
    else {
        $validTypes = ['ARTICLE','GALLERY_IMAGE','PODCAST','VIDEO','DOCUMENT'];
        if (!in_array($type, $validTypes)) $type = 'ARTICLE';
        $pdo->prepare(
            'UPDATE Post SET title=?,type=?,description=?,content=?,thumbnailUrl=?,mediaUrl=?,country=?,region=?,issueCategory=?,updatedAt=NOW(3) WHERE id=?'
        )->execute([$title,$type,$description,$content,$thumbnailUrl,$mediaUrl,$country,$region,$issueCategory,$id]);

        if ($andSubmit && $canSubmit) {
            $pdo->prepare("UPDATE Post SET status='PENDING',rejectionNotes=NULL,updatedAt=NOW(3) WHERE id=?")->execute([$id]);
        }
        header('Location: /admin/content');
        exit;
    }
}

// Pre-populate form from DB or POST
$f = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $post;
$pageTitle = 'Edit Content | Tafakari Admin';
?>
<?php include dirname(__DIR__, 2) . '/includes/head.php'; ?>
<body class="antialiased bg-slate-100 font-inter">
<div class="flex h-screen overflow-hidden">
<?php include dirname(__DIR__, 2) . '/includes/admin-sidebar.php'; ?>

<div class="flex-1 overflow-y-auto p-10">
  <div class="mb-8">
    <a href="/admin/content" class="text-xs font-bold text-slate-400 hover:text-primary mb-4 inline-block">&larr; Back to Content</a>
    <div class="flex items-center gap-4">
      <h1 class="font-outfit text-3xl font-bold text-slate-900">Edit Content</h1>
      <?= status_badge($post['status']) ?>
    </div>
  </div>

  <?php if (!empty($post['rejectionNotes']) && $post['status'] === 'REJECTED'): ?>
    <div class="mb-6 p-5 bg-rose-50 border border-rose-200 rounded-2xl">
      <p class="text-xs font-black uppercase tracking-widest text-rose-500 mb-1">Rejection Notes</p>
      <p class="text-rose-700 text-sm"><?= h($post['rejectionNotes']) ?></p>
    </div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="mb-6 p-4 bg-rose-50 border border-rose-200 rounded-2xl text-rose-700 text-sm"><?= h($error) ?></div>
  <?php endif; ?>

  <form method="POST" class="max-w-3xl space-y-8">
    <!-- Basic Info -->
    <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
      <h2 class="font-outfit font-bold text-lg mb-6 text-slate-900">Basic Information</h2>
      <div class="space-y-5">
        <div>
          <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Title *</label>
          <input type="text" name="title" required value="<?= h($f['title'] ?? '') ?>"
                 class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none">
        </div>
        <div>
          <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Content Type</label>
          <select name="type" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none">
            <?php foreach (['ARTICLE'=>'Article','GALLERY_IMAGE'=>'Gallery Image','PODCAST'=>'Podcast','VIDEO'=>'Video','DOCUMENT'=>'Document'] as $v => $l): ?>
              <option value="<?= h($v) ?>" <?= (($f['type'] ?? 'ARTICLE') === $v) ? 'selected' : '' ?>><?= h($l) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Short Description</label>
          <textarea name="description" rows="3"
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none resize-none"><?= h($f['description'] ?? '') ?></textarea>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Thumbnail URL</label>
            <input type="url" name="thumbnailUrl" value="<?= h($f['thumbnailUrl'] ?? '') ?>"
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none">
          </div>
          <div>
            <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Media URL</label>
            <input type="url" name="mediaUrl" value="<?= h($f['mediaUrl'] ?? '') ?>"
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none">
          </div>
        </div>
      </div>
    </div>

    <!-- Content Body -->
    <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
      <h2 class="font-outfit font-bold text-lg mb-4 text-slate-900">Content Body</h2>
      <div id="editor-tabs" class="flex gap-1 p-1 bg-slate-100 rounded-xl w-fit mb-4">
        <button type="button" onclick="switchTab('write')" id="tab-write"
                class="px-4 py-2 rounded-lg text-xs font-bold transition-colors bg-white text-slate-900 shadow-sm">Write</button>
        <button type="button" onclick="switchTab('preview')" id="tab-preview"
                class="px-4 py-2 rounded-lg text-xs font-bold transition-colors text-slate-500 hover:text-slate-900">Preview</button>
      </div>
      <div id="write-panel">
        <textarea name="content" id="md-editor" rows="16"
                  class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none font-mono resize-y"><?= h($f['content'] ?? '') ?></textarea>
      </div>
      <div id="preview-panel" class="hidden px-4 py-3 rounded-xl border border-slate-200 bg-white min-h-40 prose max-w-none text-sm"></div>
    </div>

    <!-- Regional Tagging -->
    <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
      <h2 class="font-outfit font-bold text-lg mb-6 text-slate-900">Regional Tagging</h2>
      <div class="space-y-5">
        <div>
          <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Country *</label>
          <select name="country" required class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none">
            <option value="">Select country</option>
            <?php foreach (african_countries() as $c): ?>
              <option value="<?= h($c) ?>" <?= (($f['country'] ?? '') === $c) ? 'selected' : '' ?>><?= h($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Region *</label>
          <input type="text" name="region" required value="<?= h($f['region'] ?? '') ?>"
                 class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none">
        </div>
        <div>
          <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Issue Category *</label>
          <select name="issueCategory" required class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none">
            <option value="">Select category</option>
            <?php foreach (issue_categories() as $c): ?>
              <option value="<?= h($c) ?>" <?= (($f['issueCategory'] ?? '') === $c) ? 'selected' : '' ?>><?= h($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <div class="flex gap-4 flex-wrap">
      <button type="submit" name="save_draft" class="btn-primary px-8 py-4">Save as Draft</button>
      <?php if ($canSubmit && !$isSuper): ?>
        <button type="submit" name="save_submit" class="btn-secondary px-8 py-4">Save & Submit for Approval</button>
      <?php endif; ?>
      <a href="/admin/content" class="px-8 py-4 rounded-full border border-slate-200 text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors">Cancel</a>
    </div>
  </form>
</div>
</div>

<script>
function switchTab(tab) {
  var write   = document.getElementById('write-panel');
  var preview = document.getElementById('preview-panel');
  var tw = document.getElementById('tab-write'), tp = document.getElementById('tab-preview');
  if (tab === 'write') {
    write.classList.remove('hidden'); preview.classList.add('hidden');
    tw.className = 'px-4 py-2 rounded-lg text-xs font-bold transition-colors bg-white text-slate-900 shadow-sm';
    tp.className = 'px-4 py-2 rounded-lg text-xs font-bold transition-colors text-slate-500 hover:text-slate-900';
  } else {
    write.classList.add('hidden'); preview.classList.remove('hidden');
    tp.className = 'px-4 py-2 rounded-lg text-xs font-bold transition-colors bg-white text-slate-900 shadow-sm';
    tw.className = 'px-4 py-2 rounded-lg text-xs font-bold transition-colors text-slate-500 hover:text-slate-900';
    fetch('/api/markdown-preview.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({text: document.getElementById('md-editor').value}) })
      .then(r=>r.json()).then(d=>{ document.getElementById('preview-panel').innerHTML = d.html||''; })
      .catch(function(){ document.getElementById('preview-panel').textContent = document.getElementById('md-editor').value; });
  }
}
</script>
</body>
</html>
