<?php
declare(strict_types=1);

/**
 * Image upload widget — renders a drag-and-drop image picker with URL fallback.
 * The form field submitted is a plain URL string (upload is done via AJAX first).
 *
 * @param string $id        Unique widget ID (e.g. 'art-thumb')
 * @param string $fieldName Form field name (e.g. 'thumbnailUrl')
 * @param string $label     Visible label text
 * @param string $hint      Small hint below the drop zone
 * @param string $current   Pre-filled URL (for edit forms)
 * @param bool   $req       Whether the field is required
 * @param string $inputId   Override the hidden input's id (leave blank to use uw-val-{id})
 */
function uw_img(string $id, string $fieldName, string $label, string $hint = '', string $current = '', bool $req = false, string $inputId = ''): void {
    $eid     = htmlspecialchars($id, ENT_QUOTES);
    $efn     = htmlspecialchars($fieldName, ENT_QUOTES);
    $ecur    = htmlspecialchars($current, ENT_QUOTES);
    $elbl    = htmlspecialchars($label, ENT_QUOTES);
    $ehnt    = htmlspecialchars($hint, ENT_QUOTES);
    $valId   = $inputId !== '' ? htmlspecialchars($inputId, ENT_QUOTES) : 'uw-val-' . $eid;
    $hasVal  = $current !== '';
    $reqAttr = $req ? ' data-uw-req="' . $eid . '"' : '';
    ?>
<?php if ($label !== ''): ?><div>
  <label style="display:block;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:#94a3b8;margin-bottom:7px"><?= $elbl ?></label>
<?php endif; ?>
  <div style="position:relative">
    <input type="text" name="<?= $efn ?>" id="<?= $valId ?>" value="<?= $ecur ?>"<?= $reqAttr ?>
           style="position:absolute;width:1px;height:1px;opacity:0;pointer-events:none;border:none;padding:0" tabindex="-1" aria-hidden="true">
    <div id="uw-zone-<?= $eid ?>"
         onclick="document.getElementById('uw-file-<?= $eid ?>').click()"
         ondragover="uwDragOver(event,'<?= $eid ?>')" ondragleave="uwDragOut('<?= $eid ?>')"
         ondrop="uwDrop(event,'<?= $eid ?>','image')"
         style="border:2px dashed #e2e8f0;border-radius:12px;padding:18px 14px;cursor:pointer;text-align:center;transition:border-color .2s,background .2s;background:#f8fafc;min-height:108px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px">
      <div id="uw-empty-<?= $eid ?>"<?= $hasVal ? ' style="display:none"' : '' ?>>
        <svg width="26" height="26" fill="none" stroke="#cbd5e1" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 5px"><path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
        <p style="font-size:12px;font-weight:600;color:#64748b;margin:0">Click to upload or drag &amp; drop</p>
        <p style="font-size:11px;color:#94a3b8;margin:2px 0 0">JPG, PNG, WebP, GIF · max 8 MB</p>
      </div>
      <div id="uw-prev-<?= $eid ?>" style="width:100%<?= $hasVal ? '' : ';display:none' ?>">
        <img id="uw-img-<?= $eid ?>" src="<?= $ecur ?>" alt="Preview"
             style="max-height:130px;max-width:100%;object-fit:contain;border-radius:8px;margin:0 auto 6px;display:<?= $hasVal ? 'block' : 'none' ?>">
        <div style="display:flex;align-items:center;justify-content:center;gap:8px">
          <p id="uw-name-<?= $eid ?>" style="font-size:11px;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:180px;margin:0"><?= $hasVal ? htmlspecialchars(basename($current), ENT_QUOTES) : '' ?></p>
          <button type="button" onclick="uwClear('<?= $eid ?>',event)"
                  style="font-size:11px;color:#ef4444;background:none;border:none;cursor:pointer;font-weight:700;padding:2px 6px;flex-shrink:0">× Remove</button>
        </div>
      </div>
      <div id="uw-prog-<?= $eid ?>" style="display:none;width:100%;padding:0 8px">
        <div style="height:4px;background:#e2e8f0;border-radius:4px;overflow:hidden;margin-bottom:6px">
          <div id="uw-bar-<?= $eid ?>" style="height:100%;background:#750B25;width:0%;transition:width .1s;border-radius:4px"></div>
        </div>
        <p style="font-size:11px;color:#64748b;text-align:center;margin:0">Uploading…</p>
      </div>
      <p id="uw-err-<?= $eid ?>" style="display:none;font-size:11px;color:#dc2626;font-weight:600;margin:0;max-width:280px"></p>
    </div>
    <input type="file" id="uw-file-<?= $eid ?>" accept="image/*" style="display:none"
           onchange="uwFileChange(this,'<?= $eid ?>')">
    <div style="margin-top:5px;text-align:right">
      <button type="button" onclick="uwToggleUrl('<?= $eid ?>')"
              style="font-size:11px;color:#94a3b8;background:none;border:none;cursor:pointer;text-decoration:underline;padding:0">
        Or paste a URL instead
      </button>
    </div>
    <div id="uw-url-wrap-<?= $eid ?>" style="display:none;margin-top:4px">
      <input type="url" id="uw-url-<?= $eid ?>" placeholder="https://…"
             style="width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#f8fafc;font-size:13px;box-sizing:border-box;outline:none;font-family:inherit"
             oninput="uwSetUrl('<?= $eid ?>','<?= $valId ?>',this.value)">
    </div>
    <?php if ($req): ?>
      <p id="uw-req-err-<?= $eid ?>" style="display:none;font-size:11px;color:#dc2626;margin-top:4px;font-weight:600">This image is required.</p>
    <?php endif; ?>
  </div>
  <?php if ($label !== '' && $hint !== ''): ?>
    <p style="font-size:11px;color:#94a3b8;margin-top:5px"><?= $ehnt ?></p>
  <?php endif; ?>
<?php if ($label !== ''): ?></div><?php endif; ?>
    <?php
}

/**
 * Media upload widget — renders tabbed "Upload File" | "Enter URL" picker.
 * Suits audio, video, document, or image media fields.
 *
 * @param string $id        Unique widget ID (e.g. 'pod-media')
 * @param string $fieldName Form field name (e.g. 'mediaUrl')
 * @param string $label     Visible label text
 * @param string $fileType  'audio' | 'video' | 'document' | 'image'
 * @param string $accept    HTML file-input accept string
 * @param string $dropHint  Small hint inside the drop zone (e.g. 'MP3, OGG · max 150 MB')
 * @param string $urlHint   Hint below the URL input
 * @param string $urlPh     Placeholder for the URL input
 * @param string $current   Pre-filled URL
 * @param bool   $req       Whether the field is required
 * @param string $inputId   Override the hidden input's id
 */
function uw_media(string $id, string $fieldName, string $label, string $fileType, string $accept, string $dropHint, string $urlHint, string $urlPh = 'https://…', string $current = '', bool $req = false, string $inputId = ''): void {
    $eid    = htmlspecialchars($id, ENT_QUOTES);
    $efn    = htmlspecialchars($fieldName, ENT_QUOTES);
    $ecur   = htmlspecialchars($current, ENT_QUOTES);
    $elbl   = htmlspecialchars($label, ENT_QUOTES);
    $edh    = htmlspecialchars($dropHint, ENT_QUOTES);
    $euh    = htmlspecialchars($urlHint, ENT_QUOTES);
    $euph   = htmlspecialchars($urlPh, ENT_QUOTES);
    $eft    = htmlspecialchars($fileType, ENT_QUOTES);
    $eacc   = htmlspecialchars($accept, ENT_QUOTES);
    $valId  = $inputId !== '' ? htmlspecialchars($inputId, ENT_QUOTES) : 'uwm-val-' . $eid;
    $hasVal = $current !== '';
    $reqAttr = $req ? ' data-uw-req="' . $eid . '" data-uw-media="1"' : '';

    $iconPath = match ($fileType) {
        'audio'    => 'M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z',
        'video'    => 'M15 10l4.553-2.069A1 1 0 0121 8.87v6.26a1 1 0 01-1.447.9L15 14M3 8a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z',
        'document' => 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z',
        default    => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
    };
    $upLabel = match ($fileType) {
        'audio'    => 'Upload Audio',
        'video'    => 'Upload Video',
        'document' => 'Upload File',
        default    => 'Upload Image',
    };
    // If there's a pre-filled value start on URL tab (it came from a URL)
    $startUpload = !$hasVal;
    ?>
<?php if ($label !== ''): ?><div>
  <label style="display:block;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:#94a3b8;margin-bottom:7px"><?= $elbl ?></label>
<?php endif; ?>
  <div style="position:relative">
    <input type="text" name="<?= $efn ?>" id="<?= $valId ?>" value="<?= $ecur ?>"<?= $reqAttr ?>
           style="position:absolute;width:1px;height:1px;opacity:0;pointer-events:none;border:none;padding:0" tabindex="-1" aria-hidden="true">
    <!-- Tabs -->
    <div style="display:flex;border-bottom:1.5px solid #e2e8f0;margin-bottom:11px">
      <button type="button" id="uwm-tab-up-<?= $eid ?>" onclick="uwmTab('<?= $eid ?>','upload')"
              style="padding:6px 14px;font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;border:none;background:none;cursor:pointer;margin-bottom:-1.5px;transition:color .15s;<?= $startUpload ? 'color:#750B25;border-bottom:2.5px solid #750B25' : 'color:#94a3b8;border-bottom:2.5px solid transparent' ?>">
        <?= htmlspecialchars($upLabel, ENT_QUOTES) ?>
      </button>
      <button type="button" id="uwm-tab-url-<?= $eid ?>" onclick="uwmTab('<?= $eid ?>','url')"
              style="padding:6px 14px;font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;border:none;background:none;cursor:pointer;margin-bottom:-1.5px;transition:color .15s;<?= !$startUpload ? 'color:#750B25;border-bottom:2.5px solid #750B25' : 'color:#94a3b8;border-bottom:2.5px solid transparent' ?>">
        Enter URL
      </button>
    </div>
    <!-- Upload panel -->
    <div id="uwm-up-<?= $eid ?>"<?= !$startUpload ? ' style="display:none"' : '' ?>>
      <div id="uwm-zone-<?= $eid ?>"
           onclick="document.getElementById('uwm-file-<?= $eid ?>').click()"
           ondragover="uwmDragOver(event,'<?= $eid ?>')" ondragleave="uwmDragOut('<?= $eid ?>')"
           ondrop="uwmDrop(event,'<?= $eid ?>')"
           style="border:2px dashed #e2e8f0;border-radius:12px;padding:18px 14px;cursor:pointer;text-align:center;transition:border-color .2s,background .2s;background:#f8fafc;min-height:100px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px">
        <div id="uwm-empty-<?= $eid ?>">
          <svg width="26" height="26" fill="none" stroke="#cbd5e1" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 5px"><path d="<?= $iconPath ?>"/></svg>
          <p style="font-size:12px;font-weight:600;color:#64748b;margin:0">Click to upload or drag &amp; drop</p>
          <p id="uwm-drop-hint-<?= $eid ?>" style="font-size:11px;color:#94a3b8;margin:2px 0 0"><?= $edh ?></p>
        </div>
        <div id="uwm-prev-<?= $eid ?>" style="display:none;width:100%">
          <div style="display:flex;align-items:center;gap:8px;background:#f1f5f9;padding:8px 12px;border-radius:8px">
            <svg width="16" height="16" fill="none" stroke="#64748b" stroke-width="1.5" viewBox="0 0 24 24" style="flex-shrink:0"><path d="<?= $iconPath ?>"/></svg>
            <p id="uwm-name-<?= $eid ?>" style="font-size:11px;color:#475569;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;margin:0"></p>
            <button type="button" onclick="uwmClear('<?= $eid ?>','<?= $valId ?>',event)"
                    style="font-size:13px;color:#ef4444;background:none;border:none;cursor:pointer;font-weight:700;padding:0 2px;flex-shrink:0">×</button>
          </div>
        </div>
        <div id="uwm-prog-<?= $eid ?>" style="display:none;width:100%;padding:0 8px">
          <div style="height:4px;background:#e2e8f0;border-radius:4px;overflow:hidden;margin-bottom:6px">
            <div id="uwm-bar-<?= $eid ?>" style="height:100%;background:#750B25;width:0%;transition:width .1s;border-radius:4px"></div>
          </div>
          <p style="font-size:11px;color:#64748b;text-align:center;margin:0">Uploading…</p>
        </div>
        <p id="uwm-err-<?= $eid ?>" style="display:none;font-size:11px;color:#dc2626;font-weight:600;margin:0;max-width:280px"></p>
      </div>
      <input type="file" id="uwm-file-<?= $eid ?>" accept="<?= $eacc ?>" style="display:none"
             data-filetype="<?= $eft ?>" data-valid="<?= $valId ?>" onchange="uwmFileChange(this,'<?= $eid ?>')">
    </div>
    <!-- URL panel -->
    <div id="uwm-url-<?= $eid ?>"<?= $startUpload ? ' style="display:none"' : '' ?>>
      <input type="url" id="uwm-url-inp-<?= $eid ?>" placeholder="<?= $euph ?>"
             value="<?= $ecur ?>"
             style="width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#f8fafc;font-size:13px;box-sizing:border-box;outline:none;font-family:inherit"
             oninput="uwmUrlInput('<?= $eid ?>','<?= $valId ?>',this.value)">
      <p id="uwm-url-hint-<?= $eid ?>" style="font-size:11px;color:#94a3b8;margin:5px 0 0"><?= $euh ?></p>
    </div>
    <?php if ($req): ?>
      <p id="uwm-req-err-<?= $eid ?>" style="display:none;font-size:11px;color:#dc2626;margin-top:4px;font-weight:600">This field is required.</p>
    <?php endif; ?>
  </div>
<?php if ($label !== ''): ?></div><?php endif; ?>
    <?php
}

/**
 * Outputs the shared JavaScript for all upload widgets on this page.
 * Call once per page, anywhere before </body>.
 */
function uw_scripts(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    ?>
<script>
/* ── Upload Widget shared JS ─────────────────────────────────────── */

/* ── Image widget ── */
function uwDragOver(e, id) {
  e.preventDefault();
  var z = document.getElementById('uw-zone-' + id);
  if (z) { z.style.borderColor = '#750B25'; z.style.background = '#fef2f2'; }
}
function uwDragOut(id) {
  var z = document.getElementById('uw-zone-' + id);
  if (z) { z.style.borderColor = '#e2e8f0'; z.style.background = '#f8fafc'; }
}
function uwDrop(e, id, fileType) {
  e.preventDefault();
  uwDragOut(id);
  var f = e.dataTransfer.files[0];
  if (f) uwDoUpload(f, id, fileType);
}
function uwFileChange(input, id) {
  var f = input.files[0];
  if (f) uwDoUpload(f, id, 'image');
  input.value = '';
}
function uwDoUpload(file, id, fileType) {
  var valEl  = document.getElementById('uw-val-' + id);
  var zoneEl = document.getElementById('uw-zone-' + id);
  var emEl   = document.getElementById('uw-empty-' + id);
  var prEl   = document.getElementById('uw-prev-' + id);
  var pgEl   = document.getElementById('uw-prog-' + id);
  var barEl  = document.getElementById('uw-bar-' + id);
  var errEl  = document.getElementById('uw-err-' + id);
  var reEl   = document.getElementById('uw-req-err-' + id);

  emEl.style.display = 'none';
  prEl.style.display = 'none';
  if (errEl) errEl.style.display = 'none';
  pgEl.style.display = '';

  var fd = new FormData();
  fd.append('file', file);

  var xhr = new XMLHttpRequest();
  xhr.open('POST', '/api/upload?type=' + encodeURIComponent(fileType));
  xhr.upload.onprogress = function(ev) {
    if (ev.lengthComputable) barEl.style.width = Math.round(ev.loaded / ev.total * 100) + '%';
  };
  xhr.onload = function() {
    pgEl.style.display = 'none';
    var data;
    try { data = JSON.parse(xhr.responseText); } catch(ex) { data = {error:'Invalid response'}; }
    if (data.error) {
      emEl.style.display = '';
      if (errEl) { errEl.style.display = ''; errEl.textContent = data.error; }
    } else {
      valEl.value = data.url;
      if (reEl) reEl.style.display = 'none';
      var imgEl  = document.getElementById('uw-img-' + id);
      var nmEl   = document.getElementById('uw-name-' + id);
      if (imgEl) { imgEl.src = data.url; imgEl.style.display = 'block'; }
      if (nmEl)  nmEl.textContent = data.filename + ' (' + uwFmt(data.size) + ')';
      prEl.style.display = '';
    }
  };
  xhr.onerror = function() {
    pgEl.style.display = 'none';
    emEl.style.display = '';
    if (errEl) { errEl.style.display = ''; errEl.textContent = 'Upload failed — network error.'; }
  };
  xhr.send(fd);
}
function uwClear(id, e) {
  if (e) e.stopPropagation();
  var valEl = document.getElementById('uw-val-' + id);
  var imgEl = document.getElementById('uw-img-' + id);
  var nmEl  = document.getElementById('uw-name-' + id);
  if (valEl) valEl.value = '';
  if (imgEl) { imgEl.src = ''; imgEl.style.display = 'none'; }
  if (nmEl)  nmEl.textContent = '';
  document.getElementById('uw-prev-' + id).style.display = 'none';
  document.getElementById('uw-empty-' + id).style.display = '';
  var barEl = document.getElementById('uw-bar-' + id);
  if (barEl) barEl.style.width = '0%';
  // Reset URL input too
  var urlEl = document.getElementById('uw-url-' + id);
  if (urlEl) urlEl.value = '';
}
function uwToggleUrl(id) {
  var wrap = document.getElementById('uw-url-wrap-' + id);
  var vis  = wrap.style.display === 'none' || wrap.style.display === '';
  wrap.style.display = vis ? '' : 'none';
  if (vis) setTimeout(function() { var u = document.getElementById('uw-url-' + id); if (u) u.focus(); }, 50);
}
function uwSetUrl(id, valId, value) {
  var valEl = document.getElementById(valId || ('uw-val-' + id));
  if (valEl) valEl.value = value;
  var reEl = document.getElementById('uw-req-err-' + id);
  if (reEl && value) reEl.style.display = 'none';
  // Clear the drop-zone preview if URL is being typed
  if (!value) {
    var imgEl = document.getElementById('uw-img-' + id);
    if (imgEl) { imgEl.src = ''; imgEl.style.display = 'none'; }
    document.getElementById('uw-prev-' + id).style.display = 'none';
    document.getElementById('uw-empty-' + id).style.display = '';
  }
}

/* ── Media widget ── */
function uwmTab(id, tab) {
  var upPanel  = document.getElementById('uwm-up-' + id);
  var urlPanel = document.getElementById('uwm-url-' + id);
  var tabUp    = document.getElementById('uwm-tab-up-' + id);
  var tabUrl   = document.getElementById('uwm-tab-url-' + id);
  var isUp = (tab === 'upload');
  upPanel.style.display  = isUp ? '' : 'none';
  urlPanel.style.display = isUp ? 'none' : '';
  tabUp.style.color        = isUp ? '#750B25' : '#94a3b8';
  tabUp.style.borderBottom = isUp ? '2.5px solid #750B25' : '2.5px solid transparent';
  tabUrl.style.color        = isUp ? '#94a3b8' : '#750B25';
  tabUrl.style.borderBottom = isUp ? '2.5px solid transparent' : '2.5px solid #750B25';
  // If switching to URL tab, focus the URL input
  if (!isUp) setTimeout(function() { var u = document.getElementById('uwm-url-inp-' + id); if (u) u.focus(); }, 50);
}
function uwmDragOver(e, id) {
  e.preventDefault();
  var z = document.getElementById('uwm-zone-' + id);
  if (z) { z.style.borderColor = '#750B25'; z.style.background = '#fef2f2'; }
}
function uwmDragOut(id) {
  var z = document.getElementById('uwm-zone-' + id);
  if (z) { z.style.borderColor = '#e2e8f0'; z.style.background = '#f8fafc'; }
}
function uwmDrop(e, id) {
  e.preventDefault();
  uwmDragOut(id);
  var fileInput = document.getElementById('uwm-file-' + id);
  var fileType  = fileInput ? fileInput.dataset.filetype : 'document';
  var valId     = fileInput ? fileInput.dataset.valid    : ('uwm-val-' + id);
  var f = e.dataTransfer.files[0];
  if (f) uwmDoUpload(f, id, fileType, valId);
}
function uwmFileChange(input, id) {
  var f = input.files[0];
  if (f) uwmDoUpload(f, id, input.dataset.filetype, input.dataset.valid || ('uwm-val-' + id));
  input.value = '';
}
function uwmDoUpload(file, id, fileType, valId) {
  valId = valId || ('uwm-val-' + id);
  var valEl  = document.getElementById(valId);
  var emEl   = document.getElementById('uwm-empty-' + id);
  var prEl   = document.getElementById('uwm-prev-' + id);
  var pgEl   = document.getElementById('uwm-prog-' + id);
  var barEl  = document.getElementById('uwm-bar-' + id);
  var errEl  = document.getElementById('uwm-err-' + id);
  var reEl   = document.getElementById('uwm-req-err-' + id);

  emEl.style.display = 'none';
  prEl.style.display = 'none';
  if (errEl) errEl.style.display = 'none';
  pgEl.style.display = '';

  var fd = new FormData();
  fd.append('file', file);

  var xhr = new XMLHttpRequest();
  xhr.open('POST', '/api/upload?type=' + encodeURIComponent(fileType));
  xhr.upload.onprogress = function(ev) {
    if (ev.lengthComputable) barEl.style.width = Math.round(ev.loaded / ev.total * 100) + '%';
  };
  xhr.onload = function() {
    pgEl.style.display = 'none';
    var data;
    try { data = JSON.parse(xhr.responseText); } catch(ex) { data = {error:'Invalid response'}; }
    if (data.error) {
      emEl.style.display = '';
      if (errEl) { errEl.style.display = ''; errEl.textContent = data.error; }
    } else {
      if (valEl) valEl.value = data.url;
      if (reEl) reEl.style.display = 'none';
      var nmEl = document.getElementById('uwm-name-' + id);
      if (nmEl) nmEl.textContent = data.filename + ' (' + uwFmt(data.size) + ')';
      prEl.style.display = '';
    }
  };
  xhr.onerror = function() {
    pgEl.style.display = 'none';
    emEl.style.display = '';
    if (errEl) { errEl.style.display = ''; errEl.textContent = 'Upload failed — network error.'; }
  };
  xhr.send(fd);
}
function uwmClear(id, valId, e) {
  if (e) e.stopPropagation();
  valId = valId || ('uwm-val-' + id);
  var valEl  = document.getElementById(valId);
  var urlInp = document.getElementById('uwm-url-inp-' + id);
  var errEl  = document.getElementById('uwm-err-' + id);
  if (valEl)  valEl.value = '';
  if (urlInp) urlInp.value = '';
  if (errEl)  errEl.style.display = 'none';
  document.getElementById('uwm-prev-' + id).style.display  = 'none';
  document.getElementById('uwm-empty-' + id).style.display = '';
  var barEl = document.getElementById('uwm-bar-' + id);
  if (barEl) barEl.style.width = '0%';
}
function uwmUrlInput(id, valId, value) {
  valId = valId || ('uwm-val-' + id);
  var valEl = document.getElementById(valId);
  if (valEl) valEl.value = value;
  var reEl = document.getElementById('uwm-req-err-' + id);
  if (reEl && value) reEl.style.display = 'none';
}

/* ── Shared helper ── */
function uwFmt(bytes) {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1048576) return Math.round(bytes / 1024) + ' KB';
  return (bytes / 1048576).toFixed(1) + ' MB';
}

/* ── Form validation ── */
function uwCheckRequired(form) {
  var ok = true;
  form.querySelectorAll('[data-uw-req]').forEach(function(inp) {
    // Skip if inside a hidden ancestor section
    var section = inp.closest('[data-uw-section]');
    if (section && (section.style.display === 'none' || section.hidden)) return;
    var wid    = inp.dataset.uwReq;
    var isMedia = !!inp.dataset.uwMedia;
    var reEl   = document.getElementById((isMedia ? 'uwm' : 'uw') + '-req-err-' + wid);
    if (!inp.value.trim()) {
      if (reEl) { reEl.style.display = ''; reEl.scrollIntoView({block:'nearest', behavior:'smooth'}); }
      ok = false;
    } else {
      if (reEl) reEl.style.display = 'none';
    }
  });
  return ok;
}

/* ── Type switching for new.php media widget ── */
function uwmSetType(id, fileType, accept, dropHint, urlHint, uploadLabel) {
  var fileInput = document.getElementById('uwm-file-' + id);
  var tabUpBtn  = document.getElementById('uwm-tab-up-' + id);
  var hintEl    = document.getElementById('uwm-drop-hint-' + id);
  var urlHintEl = document.getElementById('uwm-url-hint-' + id);

  if (fileInput) { fileInput.accept = accept; fileInput.dataset.filetype = fileType; }
  if (tabUpBtn && uploadLabel) tabUpBtn.textContent = uploadLabel;
  if (hintEl && dropHint) hintEl.textContent = dropHint;
  if (urlHintEl && urlHint) urlHintEl.textContent = urlHint;

  // Reset to upload tab / empty state
  uwmClear(id, fileInput ? fileInput.dataset.valid : null, null);
  uwmTab(id, 'upload');
}
</script>
    <?php
}
