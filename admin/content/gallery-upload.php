<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

$user    = require_auth();
$isSuper = is_super_admin();
$uid     = $user['id'];

$pageTitle      = 'Batch Photo Upload | Tafakari Admin';
$adminPageTitle = 'Batch Photo Upload';
$adminPageSub   = 'Upload multiple gallery images at once';

$countries  = african_countries();
$categories = issue_categories();
?>
<?php include dirname(__DIR__, 2) . '/includes/head.php'; ?>
<body class="antialiased font-inter" style="background:#F4F6F8">
<div class="flex h-screen overflow-hidden">
<?php include dirname(__DIR__, 2) . '/includes/admin-sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden min-w-0">
<?php include dirname(__DIR__, 2) . '/includes/admin-topbar.php'; ?>

<main class="flex-1 overflow-y-auto p-6 md:p-8">

  <!-- Breadcrumb + Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
      <div class="flex items-center gap-2 mb-1 text-[11px] font-bold flex-wrap">
        <a href="/admin/content" class="text-slate-400 hover:text-primary transition-colors">All Content</a>
        <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
        <a href="/admin/content/gallery" class="text-slate-400 hover:text-primary transition-colors">Gallery</a>
        <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
        <span class="text-slate-600">Batch Upload</span>
      </div>
      <h1 class="font-outfit font-black text-2xl text-slate-900">Batch Photo Upload</h1>
    </div>
    <div class="flex items-center gap-3">
      <a href="/admin/content/gallery"
         class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-[12px] font-bold border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Gallery
      </a>
      <a href="/admin/content/new?type=GALLERY_IMAGE"
         class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-[12px] font-bold border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
        Single Upload
      </a>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

    <!-- ── Left: Shared Metadata ────────────────────────────────────── -->
    <div class="lg:col-span-1">
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 sticky top-6">
        <div class="mb-5">
          <h2 class="font-outfit font-bold text-[15px] text-slate-900">Shared Metadata</h2>
          <p class="text-[11px] text-slate-400 mt-0.5">Applied to every image in this batch. Titles can be edited per image.</p>
        </div>

        <div class="space-y-4">

          <!-- Country -->
          <div>
            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Country *</label>
            <select id="meta-country"
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none">
              <option value="">Select country</option>
              <?php foreach ($countries as $c): ?>
                <option value="<?= h($c) ?>"><?= h($c) ?></option>
              <?php endforeach; ?>
            </select>
            <p class="meta-err hidden text-[11px] text-rose-500 mt-1 font-semibold" id="err-country">Country is required.</p>
          </div>

          <!-- Region -->
          <div>
            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Region / County / State *</label>
            <input type="text" id="meta-region" placeholder="e.g. Nairobi, Tigray, Kinshasa"
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none">
            <p class="meta-err hidden text-[11px] text-rose-500 mt-1 font-semibold" id="err-region">Region is required.</p>
          </div>

          <!-- Issue categories intentionally omitted: gallery images are
               not tagged to the heat map, so no category is collected. -->

          <!-- Description -->
          <div>
            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Caption / Description <span class="normal-case tracking-normal font-medium">(optional)</span></label>
            <textarea id="meta-description" rows="3" placeholder="Shared caption applied to all images…"
                      class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none resize-none"></textarea>
          </div>

        </div>

        <!-- Summary -->
        <div id="meta-summary" class="mt-5 pt-5 border-t border-slate-100 hidden">
          <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Batch Summary</p>
          <p class="text-[12px] text-slate-600"><span id="summary-count">0</span> image(s) queued</p>
          <p class="text-[12px] text-slate-600"><span id="summary-done">0</span> uploaded successfully</p>
          <p class="text-[12px] text-rose-500 hidden" id="summary-err-line"><span id="summary-err">0</span> failed</p>
        </div>

      </div>
    </div>

    <!-- ── Right: Drop Zone + Queue ─────────────────────────────────── -->
    <div class="lg:col-span-2 space-y-4">

      <!-- Drop zone -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
        <div id="drop-zone"
             onclick="document.getElementById('file-input').click()"
             ondragover="dzOver(event)" ondragleave="dzOut()" ondrop="dzDrop(event)"
             class="rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50 flex flex-col items-center justify-center gap-3 cursor-pointer transition-all duration-200 select-none"
             style="min-height:160px">
          <div id="dz-inner" class="text-center px-6 py-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mx-auto mb-3" style="background:rgba(117,11,37,.07)">
              <svg width="22" height="22" fill="none" stroke="#750B25" stroke-width="1.6" stroke-linecap="round" viewBox="0 0 24 24">
                <path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
            </div>
            <p class="text-sm font-semibold text-slate-700">Drop images here or <span style="color:#750B25">click to browse</span></p>
            <p class="text-[11px] text-slate-400 mt-1">JPG, PNG, WebP, GIF &mdash; up to 8 MB each &mdash; multiple files supported</p>
          </div>
        </div>
        <input type="file" id="file-input" accept="image/*" multiple style="display:none" onchange="filesChosen(this.files)">
      </div>

      <!-- Queue -->
      <div id="queue-wrap" class="hidden bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
          <div>
            <h3 class="font-outfit font-bold text-[15px] text-slate-900">Upload Queue</h3>
            <p class="text-[11px] text-slate-400 mt-0.5" id="queue-sub">0 images ready</p>
          </div>
          <div class="flex items-center gap-2">
            <button onclick="addMoreFiles()" id="btn-add-more"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-[11px] font-bold border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
              <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
              Add More
            </button>
            <button onclick="clearQueued()" id="btn-clear"
                    class="px-3 py-2 rounded-xl text-[11px] font-bold border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-rose-500 transition-colors">
              Clear
            </button>
          </div>
        </div>

        <!-- Queue items -->
        <div id="queue-list" class="divide-y divide-slate-50 max-h-[520px] overflow-y-auto"></div>

        <!-- Upload controls -->
        <div id="queue-footer" class="px-6 py-4 bg-slate-50/60 border-t border-slate-100 flex items-center justify-between gap-4">
          <p class="text-[11px] text-slate-500" id="footer-status">Ready to upload</p>
          <div class="flex items-center gap-3">
            <button onclick="retryFailed()" id="btn-retry"
                    class="hidden px-5 py-2.5 rounded-xl text-[12px] font-bold border border-amber-200 text-amber-700 hover:bg-amber-50 transition-colors">
              Retry Failed
            </button>
            <button onclick="startBatchUpload()" id="btn-upload"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-[13px] font-bold text-white transition-all hover:opacity-90 disabled:opacity-50"
                    style="background:#750B25">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24"><path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
              <span id="btn-upload-label">Upload All</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Done banner -->
      <div id="done-banner" class="hidden bg-white rounded-2xl shadow-sm border border-slate-100 p-6 text-center">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4" style="background:rgba(16,185,129,.08)">
          <svg width="24" height="24" fill="none" stroke="#10B981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h3 class="font-outfit font-bold text-xl text-slate-900 mb-1" id="done-title">Batch complete!</h3>
        <p class="text-sm text-slate-500 mb-5" id="done-sub">All images have been saved as drafts.</p>
        <div class="flex items-center justify-center gap-3">
          <a href="/admin/content/gallery"
             class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-[13px] font-bold text-white"
             style="background:#750B25">View Gallery</a>
          <button onclick="resetBatch()"
                  class="px-5 py-2.5 rounded-xl text-[13px] font-bold border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
            Upload More
          </button>
        </div>
      </div>

    </div>
  </div>

</main>
</div>
</div>

<script>
/* ── State ───────────────────────────────────────────────────────── */
var queue       = [];   // {id, file, objUrl, title, status, errorMsg, postId}
var processing  = false;
var nextId      = 0;

/* ── Drop zone ──────────────────────────────────────────────────── */
function dzOver(e) {
  e.preventDefault();
  var z = document.getElementById('drop-zone');
  z.style.borderColor = '#750B25'; z.style.background = '#fef2f2';
}
function dzOut() {
  var z = document.getElementById('drop-zone');
  z.style.borderColor = '#e2e8f0'; z.style.background = '#f8fafc';
}
function dzDrop(e) {
  e.preventDefault(); dzOut();
  filesChosen(e.dataTransfer.files);
}
function addMoreFiles() {
  var inp = document.createElement('input');
  inp.type = 'file'; inp.accept = 'image/*'; inp.multiple = true;
  inp.onchange = function() { filesChosen(this.files); };
  inp.click();
}
function filesChosen(files) {
  Array.from(files).forEach(function(f) {
    if (!f.type.startsWith('image/')) return;
    var title = f.name.replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' ').trim();
    queue.push({ id: ++nextId, file: f, objUrl: URL.createObjectURL(f),
                 title: title, status: 'queued', errorMsg: '', postId: '' });
  });
  renderQueue();
}

/* ── Queue render ───────────────────────────────────────────────── */
function renderQueue() {
  var wrap  = document.getElementById('queue-wrap');
  var list  = document.getElementById('queue-list');
  var sub   = document.getElementById('queue-sub');
  var foot  = document.getElementById('footer-status');
  var btnUp = document.getElementById('btn-upload');
  var btnR  = document.getElementById('btn-retry');
  var sumWrap = document.getElementById('meta-summary');

  if (queue.length === 0) { wrap.classList.add('hidden'); return; }
  wrap.classList.remove('hidden');

  var queued   = queue.filter(function(i) { return i.status === 'queued'; }).length;
  var uploading = queue.filter(function(i) { return i.status === 'uploading' || i.status === 'saving'; }).length;
  var done     = queue.filter(function(i) { return i.status === 'done'; }).length;
  var errors   = queue.filter(function(i) { return i.status === 'error'; }).length;

  sub.textContent = queue.length + ' image' + (queue.length !== 1 ? 's' : '') +
    (done > 0 ? ' · ' + done + ' done' : '') +
    (errors > 0 ? ' · ' + errors + ' failed' : '');

  // Update summary panel
  sumWrap.classList.remove('hidden');
  document.getElementById('summary-count').textContent = queue.length;
  document.getElementById('summary-done').textContent  = done;
  document.getElementById('summary-err').textContent   = errors;
  document.getElementById('summary-err-line').classList.toggle('hidden', errors === 0);

  // Button states
  if (processing) {
    btnUp.disabled = true;
    document.getElementById('btn-upload-label').textContent = 'Uploading…';
  } else {
    btnUp.disabled = queued === 0;
    document.getElementById('btn-upload-label').textContent = 'Upload All (' + queued + ')';
  }
  btnR.classList.toggle('hidden', errors === 0);

  // Footer status
  if (processing) {
    foot.textContent = 'Processing — please wait…';
  } else if (done + errors === queue.length && queue.length > 0) {
    foot.textContent = done + ' uploaded, ' + errors + ' failed.';
  } else {
    foot.textContent = queued + ' image' + (queued !== 1 ? 's' : '') + ' ready to upload.';
  }

  // Render items
  list.innerHTML = queue.map(function(item, idx) {
    var isActive  = item.status === 'uploading' || item.status === 'saving';
    var isDone    = item.status === 'done';
    var isError   = item.status === 'error';
    var isQueued  = item.status === 'queued';

    var badge = '';
    if (isQueued)  badge = '<span class="text-[9px] font-black uppercase tracking-wide px-2 py-1 rounded-full bg-slate-100 text-slate-500">Queued</span>';
    if (isActive)  badge = '<span class="flex items-center gap-1.5 text-[10px] font-bold text-amber-600"><span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>' + (item.status === 'uploading' ? 'Uploading…' : 'Saving…') + '</span>';
    if (isDone)    badge = '<span class="flex items-center gap-1.5 text-[10px] font-bold text-emerald-600"><svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>Done</span>';
    if (isError)   badge = '<span class="text-[9px] font-black uppercase px-2 py-1 rounded-full bg-rose-100 text-rose-600">Failed</span>';

    var removeBtn = isQueued
      ? '<button onclick="removeItem(' + idx + ')" class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-300 hover:text-rose-500 hover:bg-rose-50 transition-colors shrink-0" title="Remove"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg></button>'
      : '<div class="w-7 h-7 shrink-0"></div>';

    var errMsg = isError
      ? '<p class="text-[10px] text-rose-500 mt-1 truncate">' + escHtml(item.errorMsg) + '</p>'
      : '';

    var titleInput = isDone || isActive
      ? '<p class="text-[13px] font-semibold text-slate-800 truncate">' + escHtml(item.title) + '</p>'
      : '<input type="text" value="' + escAttr(item.title) + '" oninput="updateTitle(' + idx + ',this.value)"'
        + ' class="w-full text-[13px] font-semibold text-slate-800 bg-transparent border-b border-transparent hover:border-slate-200 focus:border-slate-300 focus:outline-none px-0 py-0 truncate"'
        + ' placeholder="Image title…">';

    return '<div class="flex items-start gap-3 px-5 py-3.5 hover:bg-slate-50/60 transition-colors">'
      + '<div class="w-14 h-14 rounded-xl overflow-hidden bg-slate-100 shrink-0 border border-slate-100">'
      + '<img src="' + escAttr(item.objUrl) + '" alt="" class="w-full h-full object-cover" loading="lazy">'
      + '</div>'
      + '<div class="flex-1 min-w-0">'
      + titleInput
      + errMsg
      + '<div class="flex items-center gap-2 mt-1.5">'
      + badge
      + '<span class="text-[10px] text-slate-300">' + escHtml(fmtFileSize(item.file.size)) + '</span>'
      + '</div>'
      + '</div>'
      + removeBtn
      + '</div>';
  }).join('');
}

function updateTitle(idx, val) { queue[idx].title = val; }
function removeItem(idx) {
  if (queue[idx].objUrl) URL.revokeObjectURL(queue[idx].objUrl);
  queue.splice(idx, 1);
  if (queue.length === 0) document.getElementById('queue-wrap').classList.add('hidden');
  else renderQueue();
}
function clearQueued() {
  queue = queue.filter(function(i) {
    if (i.status !== 'queued') return true;
    if (i.objUrl) URL.revokeObjectURL(i.objUrl);
    return false;
  });
  if (queue.length === 0) document.getElementById('queue-wrap').classList.add('hidden');
  else renderQueue();
}
function retryFailed() {
  queue.forEach(function(i) { if (i.status === 'error') { i.status = 'queued'; i.errorMsg = ''; } });
  renderQueue();
}

/* ── Metadata validation ─────────────────────────────────────────── */
function getMeta() {
  return {
    country:       document.getElementById('meta-country').value.trim(),
    region:        document.getElementById('meta-region').value.trim(),
    issueCategory: '',   // gallery images are not heat-map tagged
    description:   document.getElementById('meta-description').value.trim(),
  };
}
function validateMeta() {
  var m    = getMeta();
  var ok   = true;
  var show = function(id, show) { document.getElementById(id).classList.toggle('hidden', !show); };
  show('err-country',  !m.country);       if (!m.country)       ok = false;
  show('err-region',   !m.region);        if (!m.region)        ok = false;
  // No category requirement: gallery images are not heat-map tagged.
  if (!ok) {
    var first = document.querySelector('.meta-err:not(.hidden)');
    if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
  return ok;
}

/* ── Upload flow ─────────────────────────────────────────────────── */
async function startBatchUpload() {
  if (processing) return;
  if (!validateMeta()) return;
  var toUpload = queue.filter(function(i) { return i.status === 'queued'; });
  if (toUpload.length === 0) return;

  processing = true;
  renderQueue();

  var meta = getMeta();
  for (var i = 0; i < queue.length; i++) {
    var item = queue[i];
    if (item.status !== 'queued') continue;

    // 1. Upload file
    item.status = 'uploading'; renderQueue();
    var upRes = await doUpload(item.file);
    if (upRes.error) {
      item.status = 'error'; item.errorMsg = upRes.error; renderQueue(); continue;
    }

    // 2. Create post
    item.status = 'saving'; renderQueue();
    var postRes = await createPost({
      type:          'GALLERY_IMAGE',
      title:         item.title || item.file.name,
      mediaUrl:      upRes.url,
      thumbnailUrl:  upRes.url,
      country:       meta.country,
      region:        meta.region,
      issueCategory: meta.issueCategory,
      description:   meta.description,
      content:       '',
    });

    if (postRes.error) {
      item.status = 'error'; item.errorMsg = postRes.error;
    } else {
      item.status = 'done'; item.postId = postRes.id || '';
      if (item.objUrl) { URL.revokeObjectURL(item.objUrl); item.objUrl = upRes.url; }
    }
    renderQueue();
  }

  processing = false;
  renderQueue();
  checkDone();
}

function checkDone() {
  var queued = queue.filter(function(i) { return i.status === 'queued'; }).length;
  var errors = queue.filter(function(i) { return i.status === 'error'; }).length;
  var done   = queue.filter(function(i) { return i.status === 'done'; }).length;
  if (queued === 0 && done + errors === queue.length) {
    var banner = document.getElementById('done-banner');
    banner.classList.remove('hidden');
    document.getElementById('done-title').textContent =
      errors === 0 ? 'Batch complete!' : done + ' uploaded, ' + errors + ' failed.';
    document.getElementById('done-sub').textContent =
      errors === 0
        ? done + ' image' + (done !== 1 ? 's' : '') + ' saved as drafts. Publish them from the Gallery.'
        : 'Check the failed items above and retry, or continue to the gallery.';
  }
}

function resetBatch() {
  queue.forEach(function(i) { if (i.objUrl && i.status !== 'done') URL.revokeObjectURL(i.objUrl); });
  queue = [];
  processing = false;
  document.getElementById('queue-wrap').classList.add('hidden');
  document.getElementById('done-banner').classList.add('hidden');
  document.getElementById('meta-summary').classList.add('hidden');
  renderQueue();
}

/* ── API calls ──────────────────────────────────────────────────── */
function doUpload(file) {
  return new Promise(function(resolve) {
    var fd = new FormData();
    fd.append('file', file);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/upload?type=image');
    xhr.onload = function() {
      try { resolve(JSON.parse(xhr.responseText)); }
      catch(e) { resolve({ error: 'Invalid server response' }); }
    };
    xhr.onerror = function() { resolve({ error: 'Network error during upload' }); };
    xhr.send(fd);
  });
}
function createPost(data) {
  return new Promise(function(resolve) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/posts');
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onload = function() {
      try { resolve(JSON.parse(xhr.responseText)); }
      catch(e) { resolve({ error: 'Invalid server response' }); }
    };
    xhr.onerror = function() { resolve({ error: 'Network error creating post' }); };
    xhr.send(JSON.stringify(data));
  });
}

/* ── Helpers ─────────────────────────────────────────────────────── */
function fmtFileSize(b) {
  if (b < 1024) return b + ' B';
  if (b < 1048576) return Math.round(b / 1024) + ' KB';
  return (b / 1048576).toFixed(1) + ' MB';
}
function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function escAttr(s) {
  return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;');
}
</script>
</body>
</html>
