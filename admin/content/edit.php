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
        $redirect = match($type) {
            'ARTICLE'       => '/admin/content/articles',
            'GALLERY_IMAGE' => '/admin/content/gallery',
            'PODCAST'       => '/admin/content/podcasts',
            'VIDEO'         => '/admin/content/videos',
            'DOCUMENT'      => '/admin/content/documents',
            default         => '/admin/content',
        };
        header('Location: ' . $redirect);
        exit;
    }
}

// Pre-populate form from DB or POST
$f = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $post;

$moduleBack = match($post['type'] ?? 'ARTICLE') {
    'ARTICLE'       => ['/admin/content/articles',  'Articles'],
    'GALLERY_IMAGE' => ['/admin/content/gallery',   'Gallery'],
    'PODCAST'       => ['/admin/content/podcasts',  'Podcasts'],
    'VIDEO'         => ['/admin/content/videos',    'Videos'],
    'DOCUMENT'      => ['/admin/content/documents', 'Documents'],
    default         => ['/admin/content',           'Content'],
};

$pageTitle      = 'Edit Content | Tafakari Admin';
$adminPageTitle = 'Edit Content';
$adminPageSub   = h($post['title'] ?? '');
?>
<?php include dirname(__DIR__, 2) . '/includes/head.php'; ?>
<body class="antialiased font-inter" style="background:#F4F6F8">
<div class="flex h-screen overflow-hidden">
<?php include dirname(__DIR__, 2) . '/includes/admin-sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden min-w-0">
<?php include dirname(__DIR__, 2) . '/includes/admin-topbar.php'; ?>

<main class="flex-1 overflow-y-auto p-6 md:p-8">
  <div class="flex items-center gap-3 mb-6">
    <div class="flex items-center gap-2 text-[11px] font-bold">
      <a href="/admin/content" class="text-slate-400 hover:text-primary transition-colors">All Content</a>
      <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
      <a href="<?= h($moduleBack[0]) ?>" class="text-slate-400 hover:text-primary transition-colors"><?= h($moduleBack[1]) ?></a>
      <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
      <span class="text-slate-600">Edit</span>
    </div>
    <?= status_badge($post['status']) ?>
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

    <div class="flex gap-3 flex-wrap">
      <button type="submit" name="save_draft" class="btn-primary" style="padding:.7rem 1.75rem">Save Changes</button>
      <?php if ($canSubmit && !$isSuper): ?>
        <button type="submit" name="save_submit" class="btn-secondary" style="padding:.7rem 1.75rem">Save & Submit for Approval</button>
      <?php endif; ?>
      <a href="<?= h($moduleBack[0]) ?>" class="inline-flex items-center px-6 py-3 rounded-full border border-slate-200 text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors">Cancel</a>
    </div>
  </form>

</main>
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
