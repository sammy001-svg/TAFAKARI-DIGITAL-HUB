<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_once dirname(__DIR__, 2) . '/includes/upload-widget.php';
require_once dirname(__DIR__, 2) . '/includes/intensity-scoring.php';

ensure_post_columns();   // self-healing: byline + intensityScores

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

// Permission: ADMIN may edit their own content — including PUBLISHED, which
// re-enters the approval queue on save. ARCHIVED stays locked.
$lockedMsg = '';
if (!$isSuper) {
    if ($post['authorId'] !== $uid) { header('Location: /admin/content'); exit; }
    if ($post['status'] === 'ARCHIVED') {
        $lockedMsg = 'This content is archived and cannot be edited. Ask a super admin to restore it first.';
    }
}

// A non-super admin editing live content sends it back for re-approval
$willRequeue = (!$isSuper && $post['status'] === 'PUBLISHED');

$canSubmit = $isSuper || in_array($post['status'], ['DRAFT','REJECTED']);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $lockedMsg !== '') {
    $error = $lockedMsg;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    // Stated author / issuing body; only meaningful for reporting formats
    $byline = trim($_POST['byline'] ?? '');
    if (!post_type_has_byline($type)) $byline = '';

    // Per-element 1-10 intensity scores (0 = not assessed, dropped)
    $intensityScores = [];
    foreach ((array)($_POST['intensity'] ?? []) as $_el => $_v) {
        $_el = trim((string)$_el);
        $_v  = (int)$_v;
        if ($_el !== '' && $_v >= 1 && $_v <= 10) $intensityScores[$_el] = $_v;
    }
    $intensityJson = $intensityScores ? json_encode($intensityScores, JSON_UNESCAPED_UNICODE) : null;
    $andSubmit     = isset($_POST['save_submit']);
    $andPublish    = $isSuper && isset($_POST['save_publish']);

    // Column limits per migrations/001-widen-post-columns.sql. Overflowing one
    // throws a PDOException that would otherwise surface as a blank page and a
    // silently discarded edit.
    if (!$title)             $error = 'Title is required.';
    elseif (!$country)       $error = 'Country is required.';
    elseif (!$region)        $error = 'Region is required.';
    elseif (!$issueCategory) $error = 'Issue category is required.';
    elseif (mb_strlen($title) > 255)
        $error = 'Title is too long — ' . mb_strlen($title) . ' characters, limit is 255.';
    elseif (mb_strlen($issueCategory) > 512)
        $error = 'Too many issue categories selected. The combined list is ' . mb_strlen($issueCategory)
               . ' characters but the limit is 512. Please select fewer categories.';
    elseif (mb_strlen($byline) > 255)
        $error = 'Author / source is too long - ' . mb_strlen($byline) . ' characters, limit is 255.';
    elseif (mb_strlen($region) > 191)
        $error = 'Region is too long — ' . mb_strlen($region) . ' characters, limit is 191.';
    elseif (mb_strlen($thumbnailUrl) > 1024)
        $error = 'Thumbnail URL is too long — ' . mb_strlen($thumbnailUrl) . ' characters, limit is 1024.';
    elseif (mb_strlen($mediaUrl) > 1024)
        $error = 'Media URL is too long — ' . mb_strlen($mediaUrl) . ' characters, limit is 1024.';
    else {
        $validTypes = ['ARTICLE','GALLERY_IMAGE','PODCAST','VIDEO','DOCUMENT','POLICY_BRIEF'];
        if (!in_array($type, $validTypes)) $type = 'ARTICLE';
        if ($type === 'POLICY_BRIEF') ensure_policy_brief_post_type();

        try {
            $pdo->prepare(
                'UPDATE Post SET title=?,byline=?,type=?,description=?,content=?,thumbnailUrl=?,mediaUrl=?,country=?,region=?,issueCategory=?,intensityScores=?,updatedAt=NOW(3) WHERE id=?'
            )->execute([$title,$byline,$type,$description,$content,$thumbnailUrl,$mediaUrl,$country,$region,$issueCategory,$intensityJson,$id]);

            $flag = 'saved';
            if ($andPublish) {
                $pdo->prepare("UPDATE Post SET status='PUBLISHED',approverId=?,updatedAt=NOW(3) WHERE id=?")->execute([$uid, $id]);
                $flag = 'published';
            } elseif ($andSubmit && $canSubmit) {
                $pdo->prepare("UPDATE Post SET status='PENDING',rejectionNotes=NULL,updatedAt=NOW(3) WHERE id=?")->execute([$id]);
                $flag = 'submitted';
            } elseif ($willRequeue) {
                // Admin edited live content — pull it from the public site pending re-approval
                $pdo->prepare("UPDATE Post SET status='PENDING',rejectionNotes=NULL,updatedAt=NOW(3) WHERE id=?")->execute([$id]);
                $flag = 'requeued';
            }

            $redirect = match($type) {
                'ARTICLE'       => '/admin/content/articles',
                'GALLERY_IMAGE' => '/admin/content/gallery',
                'PODCAST'       => '/admin/content/podcasts',
                'VIDEO'         => '/admin/content/videos',
                'DOCUMENT'      => '/admin/content/documents',
                'POLICY_BRIEF'  => '/admin/content/policy-briefs',
                default         => '/admin/content',
            };
            header('Location: ' . $redirect . '?updated=' . $flag);
            exit;
        } catch (\Throwable $e) {
            error_log('[admin/content/edit] save failed for post ' . $id . ': ' . $e->getMessage());
            $error = 'Could not save your changes: ' . $e->getMessage();
        }
    }
}

// Pre-populate form from DB or POST
$f = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $post;
$selectedCats = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? array_filter(array_map('trim', (array)($_POST['issueCategories'] ?? [])))
    : array_filter(array_map('trim', explode(',', $post['issueCategory'] ?? '')));

// Upload widget config based on current post type
$editType = $f['type'] ?? $post['type'] ?? 'ARTICLE';
$mFt  = match($editType) { 'PODCAST'=>'audio', 'VIDEO'=>'video', 'DOCUMENT','POLICY_BRIEF'=>'document', default=>'image' };
$mAcc = match($mFt) { 'audio'=>'audio/*', 'video'=>'video/mp4,video/webm,video/ogg', 'document'=>'.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx', default=>'image/*' };
$mDH  = match($mFt) { 'audio'=>'MP3, OGG, WAV, M4A · max 150 MB', 'video'=>'MP4, WebM · max 500 MB', 'document'=>'PDF, DOCX, XLS, PPT · max 50 MB', default=>'JPG, PNG, WebP · max 8 MB' };
$mUH  = match($mFt) { 'audio'=>'MP3 link or podcast episode URL', 'video'=>'YouTube / Vimeo link, or direct video URL', 'document'=>'Link to PDF, Word doc, or other file', default=>'Direct image URL' };
$mPh  = match($mFt) { 'audio'=>'https://…/episode.mp3', 'video'=>'https://www.youtube.com/watch?v=…', 'document'=>'https://…/report.pdf', default=>'https://…/image.jpg' };
$mLabel = match($editType) { 'PODCAST'=>'Audio File', 'VIDEO'=>'Video URL', 'DOCUMENT'=>'Document File', 'GALLERY_IMAGE'=>'Gallery Image', 'POLICY_BRIEF'=>'Brief File', default=>'' };

$moduleBack = match($post['type'] ?? 'ARTICLE') {
    'ARTICLE'       => ['/admin/content/articles',  'Articles'],
    'GALLERY_IMAGE' => ['/admin/content/gallery',   'Gallery'],
    'PODCAST'       => ['/admin/content/podcasts',  'Podcasts'],
    'VIDEO'         => ['/admin/content/videos',    'Videos'],
    'DOCUMENT'      => ['/admin/content/documents', 'Documents'],
    'POLICY_BRIEF'  => ['/admin/content/policy-briefs', 'Policy Briefs'],
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

  <?php if ($lockedMsg): ?>
    <div class="mb-6 p-4 bg-slate-100 border border-slate-200 rounded-2xl text-slate-600 text-sm flex items-start gap-2.5">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px">
        <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
      </svg>
      <span><?= h($lockedMsg) ?></span>
    </div>
  <?php elseif ($willRequeue): ?>
    <div class="mb-6 p-4 rounded-2xl text-sm flex items-start gap-2.5" style="background:#fffbeb;border:1px solid #fde68a;color:#92400e">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px">
        <path d="M12 9v4M12 17h.01"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
      </svg>
      <span>This item is currently <strong>live</strong>. Saving changes will unpublish it and resubmit it for super-admin approval.</span>
    </div>
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
          <select name="type" id="type-select" onchange="bylineToggle(this.value)"
                  class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none">
            <?php foreach (['ARTICLE'=>'Article','GALLERY_IMAGE'=>'Gallery Image','PODCAST'=>'Podcast','VIDEO'=>'Video','DOCUMENT'=>'Document','POLICY_BRIEF'=>'Policy Brief'] as $v => $l): ?>
              <option value="<?= h($v) ?>" <?= (($f['type'] ?? 'ARTICLE') === $v) ? 'selected' : '' ?>><?= h($l) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Author / issuing body — sits between the title and the summary.
             Shown only for reporting formats; toggled when the type changes. -->
        <div id="byline-field"<?= post_type_has_byline($editType) ? '' : ' style="display:none"' ?>>
          <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">
            <span id="byline-label"><?= h(byline_label($editType)) ?></span>
            <span class="text-slate-300 font-bold normal-case tracking-normal">(optional)</span>
          </label>
          <input type="text" name="byline" value="<?= h($f['byline'] ?? '') ?>" maxlength="255"
                 class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none"
                 placeholder="e.g. Rodgers Mwansa, or Centre for Research, Training and Publications">
          <p class="text-[11px] text-slate-400 mt-1.5">
            Name the individual author, or the issuing body where no individual is named.
          </p>
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

    <!-- Intensity Assessment -->
    <?php
      $currentScores = $_SERVER['REQUEST_METHOD'] === 'POST'
          ? array_map('intval', (array)($_POST['intensity'] ?? []))
          : post_intensity_scores($post['intensityScores'] ?? null);
      intensity_scoring_fields($currentScores);
    ?>

    <div class="flex gap-3 flex-wrap">
      <?php if (!$lockedMsg): ?>
        <button type="submit" name="save_draft" class="btn-primary" style="padding:.7rem 1.75rem">
          <?= $willRequeue ? 'Save &amp; Resubmit for Approval' : 'Save Changes' ?>
        </button>
      <?php endif; ?>
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
<?php intensity_scoring_scripts(); ?>
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
// Byline applies to reporting formats only
function bylineToggle(type) {
  var wrap = document.getElementById('byline-field');
  var lbl  = document.getElementById('byline-label');
  if (!wrap) return;
  var show = (type === 'ARTICLE' || type === 'DOCUMENT' || type === 'POLICY_BRIEF');
  wrap.style.display = show ? '' : 'none';
  if (lbl) lbl.textContent = (type === 'POLICY_BRIEF') ? 'Author / Issuing Body' : 'Author / Source';
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
