<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_once dirname(__DIR__, 2) . '/includes/upload-widget.php';

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
    $rawCats = array_filter(array_map('trim', (array)($_POST['issueCategories'] ?? [])));
    $issueCategory = implode(',', array_intersect($rawCats, issue_categories()));
    $andSubmit     = isset($_POST['save_submit']);
    $andPublish    = $isSuper && isset($_POST['save_publish']);

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

        if ($andPublish) {
            $pdo->prepare("UPDATE Post SET status='PUBLISHED',approverId=?,updatedAt=NOW(3) WHERE id=?")->execute([$uid, $id]);
        } elseif ($andSubmit && $canSubmit) {
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
$selectedCats = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? array_filter(array_map('trim', (array)($_POST['issueCategories'] ?? [])))
    : array_filter(array_map('trim', explode(',', $post['issueCategory'] ?? '')));

// Upload widget config based on current post type
$editType = $f['type'] ?? $post['type'] ?? 'ARTICLE';
$mFt  = match($editType) { 'PODCAST'=>'audio', 'VIDEO'=>'video', 'DOCUMENT'=>'document', default=>'image' };
$mAcc = match($mFt) { 'audio'=>'audio/*', 'video'=>'video/mp4,video/webm,video/ogg', 'document'=>'.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx', default=>'image/*' };
$mDH  = match($mFt) { 'audio'=>'MP3, OGG, WAV, M4A · max 150 MB', 'video'=>'MP4, WebM · max 500 MB', 'document'=>'PDF, DOCX, XLS, PPT · max 50 MB', default=>'JPG, PNG, WebP · max 8 MB' };
$mUH  = match($mFt) { 'audio'=>'MP3 link or podcast episode URL', 'video'=>'YouTube / Vimeo link, or direct video URL', 'document'=>'Link to PDF, Word doc, or other file', default=>'Direct image URL' };
$mPh  = match($mFt) { 'audio'=>'https://…/episode.mp3', 'video'=>'https://www.youtube.com/watch?v=…', 'document'=>'https://…/report.pdf', default=>'https://…/image.jpg' };
$mLabel = match($editType) { 'PODCAST'=>'Audio File', 'VIDEO'=>'Video URL', 'DOCUMENT'=>'Document File', 'GALLERY_IMAGE'=>'Gallery Image', default=>'' };

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
        <?php uw_img('edit-thumb','thumbnailUrl','Thumbnail / Cover Image','Optional preview image or cover',($f['thumbnailUrl'] ?? '')); ?>
        <?php if ($mLabel !== ''): ?>
          <?php uw_media('edit-media','mediaUrl',$mLabel,$mFt,$mAcc,$mDH,$mUH,$mPh,($f['mediaUrl'] ?? '')); ?>
        <?php endif; ?>
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
          <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Issue Categories *</label>
          <div class="relative" id="cat-ms-wrap">
            <button type="button" id="cat-ms-btn" onclick="catMsToggle()"
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm text-left flex items-center justify-between focus:outline-none">
              <span id="cat-ms-label" class="text-slate-400">Select categories</span>
              <svg width="14" height="14" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div id="cat-ms-dropdown" class="hidden absolute z-50 mt-1 w-full bg-white rounded-xl border border-slate-200 shadow-lg">
              <div class="p-2 border-b border-slate-100">
                <input type="text" placeholder="Search categories…" oninput="catMsSearch(this.value)"
                       class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 bg-slate-50 focus:outline-none">
              </div>
              <div class="overflow-y-auto max-h-52 p-1.5" id="cat-ms-list">
                <?php foreach (issue_categories() as $c): ?>
                  <label class="cat-ms-opt flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-slate-50 cursor-pointer">
                    <input type="checkbox" name="issueCategories[]" value="<?= h($c) ?>"
                           <?= in_array($c, $selectedCats, true) ? 'checked' : '' ?>
                           onchange="catMsUpdate()"
                           class="w-4 h-4 rounded" style="accent-color:#750B25">
                    <span class="text-sm text-slate-700"><?= h($c) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="flex gap-3 flex-wrap">
      <button type="submit" name="save_draft" class="btn-primary" style="padding:.7rem 1.75rem">Save Changes</button>
      <?php if ($isSuper && in_array($post['status'], ['DRAFT','PENDING','REJECTED'])): ?>
        <button type="submit" name="save_publish"
                class="inline-flex items-center px-6 py-3 rounded-full text-sm font-bold text-white transition-opacity hover:opacity-90"
                style="background:#16a34a">Save & Publish</button>
      <?php endif; ?>
      <?php if ($canSubmit && !$isSuper): ?>
        <button type="submit" name="save_submit" class="btn-secondary" style="padding:.7rem 1.75rem">Save & Submit for Approval</button>
      <?php endif; ?>
      <a href="<?= h($moduleBack[0]) ?>" class="inline-flex items-center px-6 py-3 rounded-full border border-slate-200 text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors">Cancel</a>
    </div>
  </form>

</main>
</div>
</div>

<?php uw_scripts(); ?>
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
function catMsToggle() {
  var dd = document.getElementById('cat-ms-dropdown');
  if (dd.classList.contains('hidden')) {
    dd.classList.remove('hidden');
    dd.querySelector('input[type=text]').focus();
  } else {
    dd.classList.add('hidden');
  }
}
function catMsSearch(q) {
  q = q.toLowerCase();
  document.querySelectorAll('.cat-ms-opt').forEach(function(el) {
    el.style.display = el.querySelector('span').textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
  });
}
function catMsUpdate() {
  var checked = document.querySelectorAll('input[name="issueCategories[]"]:checked');
  var label = document.getElementById('cat-ms-label');
  if (checked.length === 0) {
    label.textContent = 'Select categories';
    label.style.color = '';
  } else if (checked.length === 1) {
    label.textContent = checked[0].value;
    label.style.color = '#0f172a';
  } else {
    label.textContent = checked.length + ' categories selected';
    label.style.color = '#0f172a';
  }
}
document.addEventListener('click', function(e) {
  var wrap = document.getElementById('cat-ms-wrap');
  if (wrap && !wrap.contains(e.target)) {
    document.getElementById('cat-ms-dropdown').classList.add('hidden');
  }
});
catMsUpdate();
</script>
</body>
</html>
