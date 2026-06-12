<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

$user    = require_auth();
$isSuper = is_super_admin();
$uid     = $user['id'];

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

    if (!$title)         $error = 'Title is required.';
    elseif (!$country)   $error = 'Country is required.';
    elseif (!$region)    $error = 'Region is required.';
    elseif (!$issueCategory) $error = 'Issue category is required.';
    else {
        $validTypes = ['ARTICLE','GALLERY_IMAGE','PODCAST','VIDEO','DOCUMENT'];
        if (!in_array($type, $validTypes)) $type = 'ARTICLE';
        $id = generate_id();
        db()->prepare(
            'INSERT INTO Post (id,title,type,description,content,thumbnailUrl,mediaUrl,country,region,issueCategory,status,authorId,viewCount,downloadCount,createdAt,updatedAt)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,0,0,NOW(3),NOW(3))'
        )->execute([$id,$title,$type,$description,$content,$thumbnailUrl,$mediaUrl,$country,$region,$issueCategory,'DRAFT',$uid]);
        header('Location: /admin/content');
        exit;
    }
}

$pageTitle      = 'Create Content | Tafakari Admin';
$adminPageTitle = 'Create New Content';
$adminPageSub   = 'Draft a new article, media item, or document.';
?>
<?php include dirname(__DIR__, 2) . '/includes/head.php'; ?>
<body class="antialiased font-inter" style="background:#F4F6F8">
<div class="flex h-screen overflow-hidden">
<?php include dirname(__DIR__, 2) . '/includes/admin-sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden min-w-0">
<?php include dirname(__DIR__, 2) . '/includes/admin-topbar.php'; ?>

<main class="flex-1 overflow-y-auto p-6 md:p-8">
  <div class="mb-6">
    <a href="/admin/content" class="inline-flex items-center gap-1.5 text-[11px] font-bold text-slate-400 hover:text-primary transition-colors">
      <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
      Back to Content
    </a>
  </div>

  <?php if ($error): ?>
    <div class="mb-6 p-4 bg-rose-50 border border-rose-200 rounded-2xl text-rose-700 text-sm"><?= h($error) ?></div>
  <?php endif; ?>

  <form method="POST" class="max-w-3xl space-y-8">

    <!-- Step 1: Basic Info -->
    <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
      <h2 class="font-outfit font-bold text-lg mb-6 text-slate-900">Basic Information</h2>
      <div class="space-y-5">
        <div>
          <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Title *</label>
          <input type="text" name="title" required value="<?= h($_POST['title'] ?? '') ?>"
                 class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none"
                 placeholder="Enter a descriptive title">
        </div>
        <div>
          <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Content Type *</label>
          <select name="type" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none">
            <?php foreach (['ARTICLE'=>'Article','GALLERY_IMAGE'=>'Gallery Image','PODCAST'=>'Podcast','VIDEO'=>'Video','DOCUMENT'=>'Document'] as $v => $l): ?>
              <option value="<?= h($v) ?>" <?= (($_POST['type'] ?? 'ARTICLE') === $v) ? 'selected' : '' ?>><?= h($l) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Short Description</label>
          <textarea name="description" rows="3"
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none resize-none"
                    placeholder="Brief summary (shown in listings)"><?= h($_POST['description'] ?? '') ?></textarea>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Thumbnail URL</label>
            <input type="url" name="thumbnailUrl" value="<?= h($_POST['thumbnailUrl'] ?? '') ?>"
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none"
                   placeholder="https://...">
          </div>
          <div>
            <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Media URL</label>
            <input type="url" name="mediaUrl" value="<?= h($_POST['mediaUrl'] ?? '') ?>"
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none"
                   placeholder="https://...">
          </div>
        </div>
      </div>
    </div>

    <!-- Step 2: Content Body -->
    <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
      <h2 class="font-outfit font-bold text-lg mb-6 text-slate-900">Content Body</h2>
      <div id="editor-tabs" class="flex gap-1 p-1 bg-slate-100 rounded-xl w-fit mb-4">
        <button type="button" onclick="switchTab('write')" id="tab-write"
                class="px-4 py-2 rounded-lg text-xs font-bold transition-colors bg-white text-slate-900 shadow-sm">Write</button>
        <button type="button" onclick="switchTab('preview')" id="tab-preview"
                class="px-4 py-2 rounded-lg text-xs font-bold transition-colors text-slate-500 hover:text-slate-900">Preview</button>
      </div>
      <div id="write-panel">
        <textarea name="content" id="md-editor" rows="16"
                  class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none font-mono resize-y"
                  placeholder="Write your content in Markdown..."><?= h($_POST['content'] ?? '') ?></textarea>
      </div>
      <div id="preview-panel" class="hidden px-4 py-3 rounded-xl border border-slate-200 bg-white min-h-40 prose max-w-none text-sm"></div>
    </div>

    <!-- Step 3: Regional Tagging -->
    <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
      <h2 class="font-outfit font-bold text-lg mb-6 text-slate-900">Regional Tagging</h2>
      <div class="space-y-5">
        <div>
          <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Country *</label>
          <select name="country" required class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none">
            <option value="">Select country</option>
            <?php foreach (african_countries() as $c): ?>
              <option value="<?= h($c) ?>" <?= (($_POST['country'] ?? '') === $c) ? 'selected' : '' ?>><?= h($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Region / County / State *</label>
          <input type="text" name="region" required value="<?= h($_POST['region'] ?? '') ?>"
                 class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none"
                 placeholder="e.g. Nairobi, Tigray, Kinshasa">
        </div>
        <div>
          <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Issue Category *</label>
          <select name="issueCategory" required class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none">
            <option value="">Select category</option>
            <?php foreach (issue_categories() as $c): ?>
              <option value="<?= h($c) ?>" <?= (($_POST['issueCategory'] ?? '') === $c) ? 'selected' : '' ?>><?= h($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <div class="flex gap-4">
      <button type="submit" class="btn-primary" style="padding:.7rem 1.75rem">Save as Draft</button>
      <a href="/admin/content" class="inline-flex items-center px-6 py-3 rounded-full border border-slate-200 text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors">Cancel</a>
    </div>
  </form>

</main>
</div>
</div>

<script>
function switchTab(tab) {
  var write   = document.getElementById('write-panel');
  var preview = document.getElementById('preview-panel');
  var tw = document.getElementById('tab-write');
  var tp = document.getElementById('tab-preview');
  if (tab === 'write') {
    write.classList.remove('hidden'); preview.classList.add('hidden');
    tw.className = 'px-4 py-2 rounded-lg text-xs font-bold transition-colors bg-white text-slate-900 shadow-sm';
    tp.className = 'px-4 py-2 rounded-lg text-xs font-bold transition-colors text-slate-500 hover:text-slate-900';
  } else {
    write.classList.add('hidden'); preview.classList.remove('hidden');
    tp.className = 'px-4 py-2 rounded-lg text-xs font-bold transition-colors bg-white text-slate-900 shadow-sm';
    tw.className = 'px-4 py-2 rounded-lg text-xs font-bold transition-colors text-slate-500 hover:text-slate-900';
    // Simple live preview via API
    fetch('/api/markdown-preview.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({text: document.getElementById('md-editor').value})
    }).then(r=>r.json()).then(d=>{ document.getElementById('preview-panel').innerHTML = d.html||''; })
      .catch(function(){
        // Fallback: just show raw text
        document.getElementById('preview-panel').textContent = document.getElementById('md-editor').value;
      });
  }
}
</script>
</body>
</html>
