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

$validTypes = ['ARTICLE','GALLERY_IMAGE','PODCAST','VIDEO','DOCUMENT','POLICY_BRIEF'];
$requestedType = strtoupper(trim($_GET['type'] ?? ''));
$defaultType   = in_array($requestedType, $validTypes) ? $requestedType : 'ARTICLE';

if ($defaultType === 'POLICY_BRIEF' || ($_SERVER['REQUEST_METHOD'] === 'POST' && strtoupper(trim($_POST['type'] ?? '')) === 'POLICY_BRIEF')) {
    ensure_policy_brief_post_type();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type          = trim($_POST['type']          ?? 'ARTICLE');
    $title         = trim($_POST['title']         ?? '');
    $description   = trim($_POST['description']   ?? '');
    $content       = trim($_POST['content']       ?? '');
    $thumbnailUrl  = trim($_POST['thumbnailUrl']  ?? '');
    $mediaUrl      = trim($_POST['mediaUrl']      ?? '');
    $country       = trim($_POST['country']       ?? '');
    $region        = trim($_POST['region']        ?? '');
    $rawCats = array_filter(array_map('trim', (array)($_POST['issueCategories'] ?? [])));
    $issueCategory = implode(',', array_intersect($rawCats, issue_categories()));

    if (!in_array($type, $validTypes)) $type = 'ARTICLE';

    if (!$title)             $error = 'Title is required.';
    elseif (!$country)       $error = 'Country is required.';
    elseif (!$region)        $error = 'Region is required.';
    elseif (!$issueCategory) $error = 'Issue category is required.';
    elseif ($type === 'GALLERY_IMAGE' && !$mediaUrl) $error = 'Image URL is required for gallery images.';
    elseif ($type === 'PODCAST'       && !$mediaUrl) $error = 'Audio file URL is required for podcasts.';
    elseif ($type === 'VIDEO'         && !$mediaUrl) $error = 'Video URL is required for videos.';
    elseif ($type === 'DOCUMENT'      && !$mediaUrl) $error = 'Document URL is required for documents.';
    elseif ($type === 'POLICY_BRIEF'  && !$mediaUrl) $error = 'Brief file URL is required for policy briefs.';
    // Column limits per migrations/001-widen-post-columns.sql — overflow throws
    // a PDOException and would lose the post silently
    elseif (mb_strlen($title) > 255)
        $error = 'Title is too long — ' . mb_strlen($title) . ' characters, limit is 255.';
    elseif (mb_strlen($issueCategory) > 512)
        $error = 'Too many issue categories selected. The combined list is ' . mb_strlen($issueCategory)
               . ' characters but the limit is 512. Please select fewer categories.';
    elseif (mb_strlen(trim($_POST['byline'] ?? '')) > 255)
        $error = 'Author / source is too long - limit is 255 characters.';
    elseif (mb_strlen($region) > 191)
        $error = 'Region is too long — ' . mb_strlen($region) . ' characters, limit is 191.';
    elseif (mb_strlen($thumbnailUrl) > 1024)
        $error = 'Thumbnail URL is too long — ' . mb_strlen($thumbnailUrl) . ' characters, limit is 1024.';
    elseif (mb_strlen($mediaUrl) > 1024)
        $error = 'Media URL is too long — ' . mb_strlen($mediaUrl) . ' characters, limit is 1024.';
    else {
    
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
    $id     = generate_id();
        $status = ($isSuper && isset($_POST['_publish'])) ? 'PUBLISHED' : 'DRAFT';
        try {
            db()->prepare(
                'INSERT INTO Post (id,title,byline,type,description,content,thumbnailUrl,mediaUrl,country,region,issueCategory,intensityScores,status,authorId,viewCount,downloadCount,createdAt,updatedAt)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,0,0,NOW(3),NOW(3))'
            )->execute([$id,$title,$byline,$type,$description,$content,$thumbnailUrl,$mediaUrl,$country,$region,$issueCategory,$intensityJson,$status,$uid]);

            $redirect = match($type) {
                'ARTICLE'      => '/admin/content/articles',
                'GALLERY_IMAGE'=> '/admin/content/gallery',
                'PODCAST'      => '/admin/content/podcasts',
                'VIDEO'        => '/admin/content/videos',
                'DOCUMENT'     => '/admin/content/documents',
                'POLICY_BRIEF' => '/admin/content/policy-briefs',
                default        => '/admin/content',
            };
            header('Location: ' . $redirect . '?created=' . ($status === 'PUBLISHED' ? 'pub' : '1'));
            exit;
        } catch (\Throwable $e) {
            error_log('[admin/content/new] create failed: ' . $e->getMessage());
            $error = 'Could not save this item: ' . $e->getMessage();
        }
    }
    $defaultType = $_POST['type'] ?? $defaultType;
}

$typeConfig = [
    'ARTICLE' => [
        'label'       => 'Article',
        'heading'     => 'Write Article',
        'sub'         => 'Publish research, analysis, or field reports.',
        'thumbLabel'  => 'Featured Image URL',
        'thumbHint'   => 'Hero image displayed in listings',
        'mediaLabel'  => '',
        'mediaHint'   => '',
        'mediaReq'    => false,
        'hasBody'     => true,
        'bodyLabel'   => 'Article Body',
        'bodyHint'    => 'Supports Markdown formatting',
        'bodyRows'    => 18,
        'backHref'    => '/admin/content/articles',
        'backLabel'   => 'Articles',
    ],
    'GALLERY_IMAGE' => [
        'label'       => 'Gallery Image',
        'heading'     => 'Upload Gallery Image',
        'sub'         => 'Add photographs or illustrations to the gallery.',
        'thumbLabel'  => 'Thumbnail / Preview URL',
        'thumbHint'   => 'Smaller version used in grid listings (optional)',
        'mediaLabel'  => 'Image URL *',
        'mediaHint'   => 'Full-size image URL (required)',
        'mediaReq'    => true,
        'hasBody'     => false,
        'bodyLabel'   => '',
        'bodyHint'    => '',
        'bodyRows'    => 0,
        'backHref'    => '/admin/content/gallery',
        'backLabel'   => 'Gallery',
    ],
    'PODCAST' => [
        'label'       => 'Podcast Episode',
        'heading'     => 'New Podcast Episode',
        'sub'         => 'Publish an audio episode with show notes.',
        'thumbLabel'  => 'Artwork / Cover Image URL',
        'thumbHint'   => 'Square artwork shown in players and listings',
        'mediaLabel'  => 'Audio File URL *',
        'mediaHint'   => 'Direct link to MP3, OGG, or WAV file (required)',
        'mediaReq'    => true,
        'hasBody'     => true,
        'bodyLabel'   => 'Episode Notes',
        'bodyHint'    => 'Show notes, transcript, or episode summary',
        'bodyRows'    => 10,
        'backHref'    => '/admin/content/podcasts',
        'backLabel'   => 'Podcasts',
    ],
    'VIDEO' => [
        'label'       => 'Video',
        'heading'     => 'Add Video',
        'sub'         => 'Publish a video to the media library.',
        'thumbLabel'  => 'Thumbnail URL',
        'thumbHint'   => 'Preview image shown before playback',
        'mediaLabel'  => 'Video URL *',
        'mediaHint'   => 'YouTube link, Vimeo link, or direct video file URL (required)',
        'mediaReq'    => true,
        'hasBody'     => true,
        'bodyLabel'   => 'Video Description',
        'bodyHint'    => 'Full description or transcript',
        'bodyRows'    => 8,
        'backHref'    => '/admin/content/videos',
        'backLabel'   => 'Videos',
    ],
    'DOCUMENT' => [
        'label'       => 'Document',
        'heading'     => 'Upload Document',
        'sub'         => 'Publish reports, policy briefs, or reference materials.',
        'thumbLabel'  => 'Cover Image URL',
        'thumbHint'   => 'Document cover shown in listings (optional)',
        'mediaLabel'  => 'Document URL *',
        'mediaHint'   => 'Direct link to PDF, DOCX, or other file (required)',
        'mediaReq'    => true,
        'hasBody'     => true,
        'bodyLabel'   => 'Abstract / Summary',
        'bodyHint'    => 'Brief description of the document contents',
        'bodyRows'    => 8,
        'backHref'    => '/admin/content/documents',
        'backLabel'   => 'Documents',
    ],
    'POLICY_BRIEF' => [
        'label'       => 'Policy Brief',
        'heading'     => 'New Policy Brief',
        'sub'         => 'Publish concise, actionable analysis for policymakers.',
        'thumbLabel'  => 'Cover Image URL',
        'thumbHint'   => 'Shown on the public policy brief card (recommended)',
        'mediaLabel'  => 'Brief File URL *',
        'mediaHint'   => 'Direct link to PDF or Word file (required)',
        'mediaReq'    => true,
        'hasBody'     => true,
        'bodyLabel'   => 'Full Brief Content',
        'bodyHint'    => 'Key findings and policy recommendations',
        'bodyRows'    => 8,
        'backHref'    => '/admin/content/policy-briefs',
        'backLabel'   => 'Policy Briefs',
    ],
];

$selectedNewCats = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? array_filter(array_map('trim', (array)($_POST['issueCategories'] ?? [])))
    : [];

$cfg = $typeConfig[$defaultType];

$pageTitle      = $cfg['heading'] . ' | Tafakari Admin';
$adminPageTitle = $cfg['heading'];
$adminPageSub   = $cfg['sub'];
?>
<?php include dirname(__DIR__, 2) . '/includes/head.php'; ?>
<body class="antialiased font-inter" style="background:#F4F6F8">
<div class="flex h-screen overflow-hidden">
<?php include dirname(__DIR__, 2) . '/includes/admin-sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden min-w-0">
<?php include dirname(__DIR__, 2) . '/includes/admin-topbar.php'; ?>

<main class="flex-1 overflow-y-auto p-6 md:p-8">

  <!-- Breadcrumb -->
  <div class="flex items-center gap-2 mb-6 text-[11px] font-bold">
    <a href="/admin/content" class="text-slate-400 hover:text-primary transition-colors">All Content</a>
    <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
    <a href="<?= h($cfg['backHref']) ?>" class="text-slate-400 hover:text-primary transition-colors" id="breadcrumb-module"><?= h($cfg['backLabel']) ?></a>
    <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
    <span class="text-slate-600" id="breadcrumb-action">New</span>
  </div>

  <?php if ($error): ?>
    <div class="mb-6 p-4 bg-rose-50 border border-rose-200 rounded-2xl text-rose-700 text-sm font-medium"><?= h($error) ?></div>
  <?php endif; ?>

  <form method="POST" id="content-form" class="max-w-3xl space-y-6" onsubmit="return uwCheckRequired(this)">

    <!-- Step 1: Type + Basic Info -->
    <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
      <h2 class="font-outfit font-bold text-lg mb-6 text-slate-900">Content Details</h2>
      <div class="space-y-5">

        <!-- Type Selector -->
        <div>
          <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-3">Content Type</label>
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2" id="type-selector">
            <?php
            $typeIcons = [
              'ARTICLE'       => 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 12h6M7 8h2',
              'GALLERY_IMAGE' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
              'PODCAST'       => 'M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z',
              'VIDEO'         => 'M15 10l4.553-2.069A1 1 0 0121 8.87v6.26a1 1 0 01-1.447.9L15 14M3 8a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z',
              'DOCUMENT'      => 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z',
              'POLICY_BRIEF'  => 'M9 17h6M9 13h6M9 9h1m3-6H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2',
            ];
            $typeShortLabels = ['ARTICLE'=>'Article','GALLERY_IMAGE'=>'Gallery','PODCAST'=>'Podcast','VIDEO'=>'Video','DOCUMENT'=>'Document','POLICY_BRIEF'=>'Policy Brief'];
            foreach ($validTypes as $t): ?>
            <button type="button" data-type="<?= $t ?>"
                    onclick="setType('<?= $t ?>')"
                    class="type-btn flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 transition-all text-center"
                    style="<?= $defaultType === $t ? 'border-color:#750B25;background:rgba(117,11,37,.06)' : 'border-color:#e2e8f0;background:transparent' ?>">
              <svg width="18" height="18" fill="none" stroke="<?= $defaultType === $t ? '#750B25' : '#94a3b8' ?>"
                   stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="<?= $typeIcons[$t] ?>"/>
              </svg>
              <span class="text-[10px] font-black uppercase tracking-wider" style="color:<?= $defaultType === $t ? '#750B25' : '#94a3b8' ?>"><?= $typeShortLabels[$t] ?></span>
            </button>
            <?php endforeach; ?>
          </div>
          <input type="hidden" name="type" id="type-input" value="<?= h($defaultType) ?>">
        </div>

        <div class="border-t border-slate-100 pt-5">
          <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2" id="label-title">Title *</label>
          <input type="text" name="title" required value="<?= h($_POST['title'] ?? '') ?>"
                 class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none"
                 id="input-title" placeholder="Enter a descriptive title">
        </div>

        <?php if (post_type_has_byline($defaultType)): ?>
        <!-- Author / issuing body — sits between the title and the summary -->
        <div id="byline-field">
          <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">
            <?= h(byline_label($defaultType)) ?>
            <span class="text-slate-300 font-bold normal-case tracking-normal">(optional)</span>
          </label>
          <input type="text" name="byline" value="<?= h($_POST['byline'] ?? '') ?>" maxlength="255"
                 class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none"
                 placeholder="e.g. Rodgers Mwansa, or Centre for Research, Training and Publications">
          <p class="text-[11px] text-slate-400 mt-1.5">
            Name the individual author, or the issuing body where no individual is named.
          </p>
        </div>
        <?php endif; ?>

        <div>
          <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Short Description</label>
          <textarea name="description" rows="3"
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none resize-none"
                    placeholder="Brief summary shown in listings (optional)"><?= h($_POST['description'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <!-- Step 2: Media / Files -->
    <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
      <h2 class="font-outfit font-bold text-lg mb-6 text-slate-900" id="media-section-title">Media</h2>
      <div class="space-y-5">

        <!-- Thumbnail (all types) -->
        <div>
          <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2" id="label-thumbnail"><?= h($cfg['thumbLabel']) ?></label>
          <?php uw_img('new-thumb', 'thumbnailUrl', '', '', $_POST['thumbnailUrl'] ?? ''); ?>
          <p class="text-[11px] text-slate-400 mt-1" id="hint-thumbnail"><?= h($cfg['thumbHint']) ?></p>
        </div>

        <!-- Media (GALLERY, PODCAST, VIDEO, DOCUMENT) -->
        <?php
          $mFt  = match($defaultType) { 'PODCAST'=>'audio', 'VIDEO'=>'video', 'DOCUMENT','POLICY_BRIEF'=>'document', default=>'image' };
          $mAcc = match($defaultType) { 'PODCAST'=>'audio/*', 'VIDEO'=>'video/mp4,video/webm,video/ogg', 'DOCUMENT'=>'.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx', 'POLICY_BRIEF'=>'.pdf,.doc,.docx', default=>'image/*' };
          $mDH  = match($defaultType) { 'PODCAST'=>'MP3, OGG, WAV, M4A · max 150 MB', 'VIDEO'=>'MP4, WebM · max 500 MB', 'DOCUMENT'=>'PDF, DOCX, XLS · max 50 MB', 'POLICY_BRIEF'=>'PDF or Word · max 50 MB', default=>'JPG, PNG, WebP · max 8 MB' };
          $mUH  = match($defaultType) { 'PODCAST'=>'MP3 link or podcast episode URL', 'VIDEO'=>'YouTube / Vimeo link, or direct video URL', 'DOCUMENT','POLICY_BRIEF'=>'Link to PDF or other document file', default=>'Direct image URL' };
          $mPh  = match($defaultType) { 'PODCAST'=>'https://…/episode.mp3', 'VIDEO'=>'https://www.youtube.com/watch?v=…', 'DOCUMENT'=>'https://…/report.pdf', 'POLICY_BRIEF'=>'https://…/brief.pdf', default=>'https://…' };
          $mReq = $cfg['mediaReq'] && $defaultType !== 'ARTICLE';
        ?>
        <div id="media-url-section" data-uw-section style="<?= $defaultType === 'ARTICLE' ? 'display:none' : '' ?>">
          <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2" id="label-media"><?= h($cfg['mediaLabel']) ?></label>
          <?php uw_media('new-media', 'mediaUrl', '', $mFt, $mAcc, $mDH, $mUH, $mPh, $_POST['mediaUrl'] ?? '', $mReq, 'input-media'); ?>
          <p class="text-[11px] text-slate-400 mt-1" id="hint-media"><?= h($cfg['mediaHint']) ?></p>
        </div>
      </div>
    </div>

    <!-- Step 3: Content Body -->
    <div id="body-section" style="<?= !$cfg['hasBody'] ? 'display:none' : '' ?>">
      <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-outfit font-bold text-lg text-slate-900" id="label-body"><?= h($cfg['bodyLabel']) ?></h2>
          <span class="text-[10px] text-slate-400" id="hint-body"><?= h($cfg['bodyHint']) ?></span>
        </div>

        <!-- Article gets full markdown editor with preview; others get plain textarea -->
        <div id="article-editor" style="<?= $defaultType !== 'ARTICLE' ? 'display:none' : '' ?>">
          <div id="editor-tabs" class="flex gap-1 p-1 bg-slate-100 rounded-xl w-fit mb-4">
            <button type="button" onclick="switchTab('write')" id="tab-write"
                    class="px-4 py-2 rounded-lg text-xs font-bold transition-colors bg-white text-slate-900 shadow-sm">Write</button>
            <button type="button" onclick="switchTab('preview')" id="tab-preview"
                    class="px-4 py-2 rounded-lg text-xs font-bold transition-colors text-slate-500 hover:text-slate-900">Preview</button>
          </div>
          <div id="write-panel">
            <textarea name="content" id="md-editor" rows="18"
                      class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none font-mono resize-y"
                      placeholder="Write your article in Markdown…"><?= h($_POST['content'] ?? '') ?></textarea>
          </div>
          <div id="preview-panel" class="hidden px-4 py-3 rounded-xl border border-slate-200 bg-white min-h-40 prose max-w-none text-sm"></div>
        </div>

        <div id="simple-editor" style="<?= $defaultType === 'ARTICLE' ? 'display:none' : '' ?>">
          <textarea name="content" id="plain-editor" rows="10"
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none resize-y"
                    placeholder=""><?= h($_POST['content'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <!-- Step 4: Regional Tagging -->
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
                           <?= in_array($c, $selectedNewCats, true) ? 'checked' : '' ?>
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
    <?php intensity_scoring_fields(array_map('intval', (array)($_POST['intensity'] ?? []))); ?>

    <div class="flex gap-4 pb-8">
      <?php if ($isSuper): ?>
        <button type="submit" name="_publish" value="1"
                class="inline-flex items-center px-6 py-3 rounded-full text-sm font-bold text-white transition-opacity hover:opacity-90"
                style="background:#16a34a">Publish Now</button>
      <?php endif; ?>
      <button type="submit" class="btn-primary" id="submit-btn" style="padding:.7rem 1.75rem">Save as Draft</button>
      <a href="<?= h($cfg['backHref']) ?>" id="cancel-link"
         class="inline-flex items-center px-6 py-3 rounded-full border border-slate-200 text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors">Cancel</a>
    </div>
  </form>

</main>
</div>
</div>

<?php uw_scripts(); ?>
<?php intensity_scoring_scripts(); ?>
<script>
var _typeConfig = {
  ARTICLE: {
    heading:'Write Article', backHref:'/admin/content/articles', backLabel:'Articles',
    thumbLabel:'Featured Image', thumbHint:'Hero image displayed in listings',
    mediaLabel:'', mediaHint:'', mediaReq:false, hasBody:true,
    bodyLabel:'Article Body', bodyHint:'Supports Markdown formatting',
    titlePlaceholder:'Article headline', mediaSectionTitle:'Media', isArticle:true,
    mediaFileType:null, mediaAccept:'', mediaDropHint:'', mediaUrlHint:'', mediaUpLabel:''
  },
  GALLERY_IMAGE: {
    heading:'Upload Gallery Image', backHref:'/admin/content/gallery', backLabel:'Gallery',
    thumbLabel:'Thumbnail / Preview', thumbHint:'Smaller version for grid listings (optional)',
    mediaLabel:'Full-Size Image *', mediaHint:'Full-size image (required)', mediaReq:true, hasBody:false,
    bodyLabel:'', bodyHint:'', titlePlaceholder:'Image caption', mediaSectionTitle:'Image File', isArticle:false,
    mediaFileType:'image', mediaAccept:'image/*', mediaDropHint:'JPG, PNG, WebP, GIF · max 8 MB',
    mediaUrlHint:'Direct image URL', mediaUpLabel:'Upload Image'
  },
  PODCAST: {
    heading:'New Podcast Episode', backHref:'/admin/content/podcasts', backLabel:'Podcasts',
    thumbLabel:'Artwork / Cover Image', thumbHint:'Square artwork shown in players and listings',
    mediaLabel:'Audio File *', mediaHint:'MP3, OGG, or WAV file (required)', mediaReq:true, hasBody:true,
    bodyLabel:'Episode Notes', bodyHint:'Show notes, transcript, or episode summary',
    titlePlaceholder:'Episode title', mediaSectionTitle:'Audio & Artwork', isArticle:false,
    mediaFileType:'audio', mediaAccept:'audio/*', mediaDropHint:'MP3, OGG, WAV, M4A · max 150 MB',
    mediaUrlHint:'MP3 link or podcast episode URL', mediaUpLabel:'Upload Audio'
  },
  VIDEO: {
    heading:'Add Video', backHref:'/admin/content/videos', backLabel:'Videos',
    thumbLabel:'Thumbnail', thumbHint:'Preview image shown before playback',
    mediaLabel:'Video URL *', mediaHint:'YouTube / Vimeo link, or direct video file (required)', mediaReq:true, hasBody:true,
    bodyLabel:'Video Description', bodyHint:'Full description or transcript',
    titlePlaceholder:'Video title', mediaSectionTitle:'Video & Thumbnail', isArticle:false,
    mediaFileType:'video', mediaAccept:'video/mp4,video/webm,video/ogg',
    mediaDropHint:'MP4, WebM · max 500 MB', mediaUrlHint:'YouTube / Vimeo link, or direct video URL', mediaUpLabel:'Upload Video'
  },
  DOCUMENT: {
    heading:'Upload Document', backHref:'/admin/content/documents', backLabel:'Documents',
    thumbLabel:'Cover Image', thumbHint:'Document cover shown in listings (optional)',
    mediaLabel:'Document File *', mediaHint:'PDF, DOCX, or other file (required)', mediaReq:true, hasBody:true,
    bodyLabel:'Abstract / Summary', bodyHint:'Brief description of the document contents',
    titlePlaceholder:'Document title', mediaSectionTitle:'File & Cover', isArticle:false,
    mediaFileType:'document', mediaAccept:'.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx',
    mediaDropHint:'PDF, DOCX, XLS, PPT · max 50 MB', mediaUrlHint:'Link to PDF or other document file', mediaUpLabel:'Upload File'
  },
  POLICY_BRIEF: {
    heading:'New Policy Brief', backHref:'/admin/content/policy-briefs', backLabel:'Policy Briefs',
    thumbLabel:'Cover Image', thumbHint:'Shown on the public policy brief card (recommended)',
    mediaLabel:'Brief File *', mediaHint:'PDF or Word file (required)', mediaReq:true, hasBody:true,
    bodyLabel:'Full Brief Content', bodyHint:'Key findings and policy recommendations',
    titlePlaceholder:'Policy brief title', mediaSectionTitle:'File & Cover', isArticle:false,
    mediaFileType:'document', mediaAccept:'.pdf,.doc,.docx',
    mediaDropHint:'PDF or Word · max 50 MB', mediaUrlHint:'Link to the downloadable brief', mediaUpLabel:'Upload File'
  }
};

var _typeIconPaths = {
  ARTICLE:       'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 12h6M7 8h2',
  GALLERY_IMAGE: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
  PODCAST:       'M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z',
  VIDEO:         'M15 10l4.553-2.069A1 1 0 0121 8.87v6.26a1 1 0 01-1.447.9L15 14M3 8a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z',
  DOCUMENT:      'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z',
  POLICY_BRIEF:  'M9 17h6M9 13h6M9 9h1m3-6H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2'
};

var _typeShortLabels = {
  ARTICLE:'Article', GALLERY_IMAGE:'Gallery', PODCAST:'Podcast', VIDEO:'Video', DOCUMENT:'Document', POLICY_BRIEF:'Policy Brief'
};

function setType(type) {
  var cfg = _typeConfig[type];
  if (!cfg) return;

  // Update hidden input
  document.getElementById('type-input').value = type;

  // Update type selector buttons
  document.querySelectorAll('.type-btn').forEach(function(btn) {
    var t = btn.dataset.type;
    var active = (t === type);
    btn.style.borderColor  = active ? '#750B25' : '#e2e8f0';
    btn.style.background   = active ? 'rgba(117,11,37,.06)' : 'transparent';
    var svg   = btn.querySelector('svg');
    var label = btn.querySelector('span');
    if (svg)   svg.setAttribute('stroke', active ? '#750B25' : '#94a3b8');
    if (label) label.style.color = active ? '#750B25' : '#94a3b8';
  });

  // Update breadcrumb
  var bm = document.getElementById('breadcrumb-module');
  if (bm) { bm.textContent = cfg.backLabel; bm.href = cfg.backHref; }

  // Update labels
  document.getElementById('label-thumbnail').textContent = cfg.thumbLabel;
  document.getElementById('hint-thumbnail').textContent  = cfg.thumbHint;
  document.getElementById('input-title').placeholder     = cfg.titlePlaceholder;

  // Media section
  var mediaSection = document.getElementById('media-url-section');
  var mediaInput   = document.getElementById('input-media');
  if (cfg.mediaLabel) {
    mediaSection.style.display = '';
    document.getElementById('label-media').textContent = cfg.mediaLabel;
    document.getElementById('hint-media').textContent  = cfg.mediaHint;
    if (cfg.mediaReq) {
      mediaInput.dataset.uwReq   = 'new-media';
      mediaInput.dataset.uwMedia = '1';
    } else {
      delete mediaInput.dataset.uwReq;
      delete mediaInput.dataset.uwMedia;
    }
    // Update the upload widget for the new type
    if (cfg.mediaFileType) {
      uwmSetType('new-media', cfg.mediaFileType, cfg.mediaAccept, cfg.mediaDropHint, cfg.mediaUrlHint, cfg.mediaUpLabel);
    }
  } else {
    mediaSection.style.display = 'none';
    delete mediaInput.dataset.uwReq;
    delete mediaInput.dataset.uwMedia;
  }

  // Media section title
  document.getElementById('media-section-title').textContent = cfg.mediaSectionTitle;

  // Body section
  var bodySection = document.getElementById('body-section');
  if (cfg.hasBody) {
    bodySection.style.display = '';
    document.getElementById('label-body').textContent = cfg.bodyLabel;
    document.getElementById('hint-body').textContent  = cfg.bodyHint;
    // Show markdown editor for articles, plain textarea for others
    var articleEditor = document.getElementById('article-editor');
    var simpleEditor  = document.getElementById('simple-editor');
    if (cfg.isArticle) {
      articleEditor.style.display = '';
      simpleEditor.style.display  = 'none';
      // Move content between textareas
      var plain = document.getElementById('plain-editor');
      var md    = document.getElementById('md-editor');
      if (plain.value && !md.value) md.value = plain.value;
      plain.removeAttribute('name');
      md.setAttribute('name', 'content');
    } else {
      articleEditor.style.display = 'none';
      simpleEditor.style.display  = '';
      var plain = document.getElementById('plain-editor');
      var md    = document.getElementById('md-editor');
      if (md.value && !plain.value) plain.value = md.value;
      md.removeAttribute('name');
      plain.setAttribute('name', 'content');
      plain.placeholder = cfg.bodyLabel + '…';
    }
  } else {
    bodySection.style.display = 'none';
  }

  // Cancel link
  document.getElementById('cancel-link').href = cfg.backHref;
}

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
    fetch('/api/markdown-preview.php', {
      method: 'POST', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({text: document.getElementById('md-editor').value})
    }).then(r=>r.json()).then(d=>{ document.getElementById('preview-panel').innerHTML = d.html||''; })
      .catch(function(){ document.getElementById('preview-panel').textContent = document.getElementById('md-editor').value; });
  }
}

// Init: ensure the correct textarea has the name attribute on page load
(function(){
  var initialType = document.getElementById('type-input').value || 'ARTICLE';
  var cfg = _typeConfig[initialType];
  if (cfg && !cfg.isArticle) {
    var md    = document.getElementById('md-editor');
    var plain = document.getElementById('plain-editor');
    if (md)    md.removeAttribute('name');
    if (plain) plain.setAttribute('name', 'content');
  }
})();

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
