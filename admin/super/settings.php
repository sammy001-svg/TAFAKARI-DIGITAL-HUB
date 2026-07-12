<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_once dirname(__DIR__, 2) . '/includes/upload-widget.php';

$me = require_super_admin();
$uid = $me['id'];

ensure_settings_table();

$saved   = '';
$saveErr = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'branding') {
        set_setting('site_logo_url',     trim($_POST['site_logo_url']    ?? ''), $uid);
        set_setting('site_og_image_url', trim($_POST['site_og_image_url'] ?? ''), $uid);
        $saved = 'branding';
    } elseif ($action === 'banner') {
        set_setting('announcement_active', isset($_POST['announcement_active']) ? '1' : '0', $uid);
        set_setting('announcement_text',   substr(trim($_POST['announcement_text']   ?? ''), 0, 200), $uid);
        set_setting('announcement_url',    trim($_POST['announcement_url']   ?? ''), $uid);
        set_setting('announcement_color',  trim($_POST['announcement_color'] ?? 'crimson'), $uid);
        $saved = 'banner';
    } elseif ($action === 'featured') {
        set_setting('featured_article_id', trim($_POST['featured_article_id'] ?? ''), $uid);
        $saved = 'featured';
    } elseif ($action === 'carousel') {
        $raw = trim($_POST['carousel_slides'] ?? '');
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $clean = [];
            foreach (array_slice($decoded, 0, 5) as $s) {
                $clean[] = [
                    'img'    => trim($s['img']    ?? ''),
                    'region' => trim($s['region'] ?? ''),
                    'title'  => trim($s['title']  ?? ''),
                    'sub'    => trim($s['sub']     ?? ''),
                    'badge'  => trim($s['badge']   ?? ''),
                    'url'    => trim($s['url']     ?? ''),
                ];
            }
            set_setting('carousel_slides', json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $uid);
            $saved = 'carousel';
        } else {
            $saveErr = 'Invalid carousel data.';
        }
    } elseif ($action === 'visibility') {
        set_setting('show_podcasts',  isset($_POST['show_podcasts'])  ? '1' : '0', $uid);
        set_setting('show_videos',    isset($_POST['show_videos'])    ? '1' : '0', $uid);
        set_setting('show_documents', isset($_POST['show_documents']) ? '1' : '0', $uid);
        $saved = 'visibility';
    }
}

// ── Load current settings ──────────────────────────────────────────────────
$siteLogoUrl        = get_setting('site_logo_url',     '/public/crtp-logo.jpg');
$siteOgImageUrl      = get_setting('site_og_image_url', '/public/crtp-og-image.png');
$announcementActive = get_setting('announcement_active', '0');
$announcementText   = get_setting('announcement_text',   '');
$announcementUrl    = get_setting('announcement_url',    '');
$announcementColor  = get_setting('announcement_color',  'crimson');
$featuredId         = get_setting('featured_article_id', '');
$carouselJson       = get_setting('carousel_slides',     '[]');
$showPodcasts       = get_setting('show_podcasts',  '1');
$showVideos         = get_setting('show_videos',    '1');
$showDocuments      = get_setting('show_documents', '1');

$carouselSlides = json_decode($carouselJson, true);
if (!is_array($carouselSlides)) $carouselSlides = [];

// ── Featured article lookup ────────────────────────────────────────────────
$featuredPost = null;
if ($featuredId) {
    try {
        $s = db()->prepare("SELECT id, title, thumbnailUrl, country, issueCategory FROM Post WHERE id=? AND type='ARTICLE' AND status='PUBLISHED' LIMIT 1");
        $s->execute([$featuredId]);
        $featuredPost = $s->fetch() ?: null;
    } catch (Exception $e) {}
}

// ── Search results for featured article picker ─────────────────────────────
$searchResults = [];
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    try {
        $s = db()->prepare("SELECT id, title, country, issueCategory, thumbnailUrl FROM Post WHERE type='ARTICLE' AND status='PUBLISHED' AND title LIKE ? ORDER BY createdAt DESC LIMIT 8");
        $s->execute(['%' . $q . '%']);
        $searchResults = $s->fetchAll();
    } catch (Exception $e) {}
}

$pageTitle      = 'Site Settings | Tafakari Admin';
$adminPageTitle = 'Site Settings';
$adminPageSub   = 'Control the homepage, carousel, announcement banner and section visibility';
?>
<?php include dirname(__DIR__, 2) . '/includes/head.php'; ?>
<body class="antialiased font-inter" style="background:#F4F6F8">
<div class="flex h-screen overflow-hidden">
<?php include dirname(__DIR__, 2) . '/includes/admin-sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden min-w-0">
<?php include dirname(__DIR__, 2) . '/includes/admin-topbar.php'; ?>

<main class="flex-1 overflow-y-auto p-6 md:p-8">

<?php if ($saved): ?>
  <div id="save-toast"
       style="position:fixed;bottom:24px;right:24px;z-index:9999;
              background:#0D0102;color:#fff;font-size:13px;font-weight:600;
              padding:12px 20px;border-radius:50px;border:1px solid rgba(255,255,255,.1);
              box-shadow:0 8px 32px rgba(0,0,0,.25);display:flex;align-items:center;gap:8px">
    <svg width="14" height="14" fill="none" stroke="#10B981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
    <?= ['branding'=>'Site branding saved','banner'=>'Banner saved','featured'=>'Featured article saved','carousel'=>'Carousel saved','visibility'=>'Visibility saved'][$saved] ?? 'Saved' ?>
  </div>
  <script>setTimeout(function(){ var t=document.getElementById('save-toast'); if(t) t.style.opacity='0'; }, 2800);</script>
<?php endif; ?>
<?php if ($saveErr): ?>
  <div class="mb-5 px-4 py-3 rounded-2xl text-sm font-medium text-red-800 bg-red-50 border border-red-200"><?= h($saveErr) ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 max-w-6xl">

  <!-- ── LEFT COLUMN (2/3) ─────────────────────────────────────── -->
  <div class="xl:col-span-2 space-y-6">

    <!-- Site Branding -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
      <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background:#0EA5E918">
          <svg width="16" height="16" fill="none" stroke="#0EA5E9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <div>
          <h2 class="font-outfit font-black text-base text-slate-900">Site Branding</h2>
          <p class="text-xs text-slate-400">Logo and default social-share image used across the site</p>
        </div>
      </div>
      <form method="POST" class="px-6 py-5 space-y-5" onsubmit="return uwCheckRequired(this)">
        <input type="hidden" name="action" value="branding">
        <?php uw_img('site-logo', 'site_logo_url', 'Site Logo', 'Shown in the navbar, footer, and structured data. A transparent PNG works best.', $siteLogoUrl); ?>
        <?php uw_img('site-og', 'site_og_image_url', 'Default Social Share Image', 'Shown when a page is shared on Facebook, X, or LinkedIn without its own image. 1200×630 recommended.', $siteOgImageUrl); ?>
        <div class="flex justify-end pt-1">
          <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white shadow-sm transition-opacity hover:opacity-90" style="background:#0EA5E9">
            Save Branding
          </button>
        </div>
      </form>
    </div>

    <!-- Announcement Banner -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
      <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background:#750B2518">
          <svg width="16" height="16" fill="none" stroke="#750B25" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        </div>
        <div>
          <h2 class="font-outfit font-black text-base text-slate-900">Announcement Banner</h2>
          <p class="text-xs text-slate-400">Displays a dismissible bar at the top of every public page</p>
        </div>
      </div>
      <form method="POST" class="px-6 py-5 space-y-4">
        <input type="hidden" name="action" value="banner">
        <div class="flex items-center justify-between">
          <label class="text-sm font-semibold text-slate-700">Enable Banner</label>
          <label style="position:relative;display:inline-block;width:44px;height:24px;cursor:pointer">
            <input type="checkbox" name="announcement_active" value="1" <?= $announcementActive === '1' ? 'checked' : '' ?>
                   style="opacity:0;width:0;height:0;position:absolute" onchange="this.closest('label').nextElementSibling.textContent=this.checked?'On':'Off'">
            <span id="toggle-track" style="position:absolute;inset:0;border-radius:24px;transition:background .2s;background:<?= $announcementActive==='1'?'#750B25':'#cbd5e1' ?>"></span>
            <span style="position:absolute;left:3px;top:3px;width:18px;height:18px;border-radius:50%;background:#fff;transition:transform .2s;transform:<?= $announcementActive==='1'?'translateX(20px)':'translateX(0)' ?>"></span>
          </label>
          <span class="text-sm font-medium ml-2 text-slate-500"><?= $announcementActive === '1' ? 'On' : 'Off' ?></span>
        </div>
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1.5">Banner Text <span class="text-slate-400 font-normal">(max 200 chars)</span></label>
          <input type="text" name="announcement_text" value="<?= h($announcementText) ?>" maxlength="200"
                 placeholder="e.g. New reports available from DRC field mission →"
                 class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2"
                 style="--tw-ring-color:#750B2540">
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Link URL <span class="text-slate-400 font-normal">(optional)</span></label>
            <input type="url" name="announcement_url" value="<?= h($announcementUrl) ?>"
                   placeholder="/news or https://..."
                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none">
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Color</label>
            <select name="announcement_color" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none bg-white">
              <option value="crimson" <?= $announcementColor==='crimson' ?'selected':'' ?>>Crimson (brand)</option>
              <option value="amber"   <?= $announcementColor==='amber'   ?'selected':'' ?>>Amber / Gold</option>
              <option value="green"   <?= $announcementColor==='green'   ?'selected':'' ?>>Green</option>
              <option value="slate"   <?= $announcementColor==='slate'   ?'selected':'' ?>>Dark Slate</option>
            </select>
          </div>
        </div>
        <div class="flex justify-end pt-1">
          <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white shadow-sm transition-opacity hover:opacity-90" style="background:#750B25">
            Save Banner
          </button>
        </div>
      </form>
    </div>

    <!-- Featured Article -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
      <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background:#E7952A18">
          <svg width="16" height="16" fill="none" stroke="#E7952A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        </div>
        <div>
          <h2 class="font-outfit font-black text-base text-slate-900">Featured Article</h2>
          <p class="text-xs text-slate-400">Pinned to the homepage above the articles grid as "Editor's Pick"</p>
        </div>
      </div>
      <div class="px-6 py-5">

        <!-- Current pinned article -->
        <?php if ($featuredPost): ?>
          <div class="flex items-center gap-4 p-4 rounded-2xl mb-5 border border-amber-200" style="background:#FFFBF0">
            <?php if ($featuredPost['thumbnailUrl']): ?>
              <img src="<?= h($featuredPost['thumbnailUrl']) ?>" alt=""
                   class="w-16 h-12 object-cover rounded-xl shrink-0" onerror="this.style.display='none'">
            <?php endif; ?>
            <div class="flex-grow min-w-0">
              <p class="text-[10px] font-black uppercase tracking-widest text-amber-600 mb-0.5">Currently Featured</p>
              <p class="font-semibold text-sm text-slate-900 leading-snug line-clamp-2"><?= h($featuredPost['title']) ?></p>
              <p class="text-xs text-slate-400 mt-0.5"><?= h($featuredPost['country']) ?> · <?= h($featuredPost['issueCategory']) ?></p>
            </div>
            <form method="POST" class="shrink-0">
              <input type="hidden" name="action" value="featured">
              <input type="hidden" name="featured_article_id" value="">
              <button type="submit" class="text-xs font-bold text-red-500 hover:text-red-700 px-3 py-1.5 rounded-lg hover:bg-red-50 transition-colors">Clear</button>
            </form>
          </div>
        <?php else: ?>
          <p class="text-sm text-slate-400 mb-4 italic">No article pinned — the section won't appear until one is selected.</p>
        <?php endif; ?>

        <!-- Search -->
        <form method="GET" class="flex gap-2 mb-4">
          <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search published articles by title…"
                 class="flex-grow px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none">
          <button type="submit" class="px-4 py-2.5 rounded-xl text-sm font-bold text-white shrink-0" style="background:#750B25">Search</button>
        </form>

        <?php if (!empty($searchResults)): ?>
          <div class="space-y-2">
            <?php foreach ($searchResults as $r): ?>
              <form method="POST" class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 hover:border-amber-200 hover:bg-amber-50/30 transition-all">
                <input type="hidden" name="action" value="featured">
                <input type="hidden" name="featured_article_id" value="<?= h($r['id']) ?>">
                <?php if ($r['thumbnailUrl']): ?>
                  <img src="<?= h($r['thumbnailUrl']) ?>" alt="" class="w-12 h-9 object-cover rounded-lg shrink-0" onerror="this.style.display='none'">
                <?php endif; ?>
                <div class="flex-grow min-w-0">
                  <p class="text-sm font-semibold text-slate-800 leading-snug line-clamp-1"><?= h($r['title']) ?></p>
                  <p class="text-xs text-slate-400"><?= h($r['country']) ?></p>
                </div>
                <button type="submit" class="shrink-0 text-[11px] font-black uppercase tracking-wider px-3 py-1.5 rounded-lg transition-colors"
                        style="background:#750B25;color:#fff"><?= $r['id'] === $featuredId ? '✓ Pinned' : 'Pin' ?></button>
              </form>
            <?php endforeach; ?>
          </div>
        <?php elseif ($q !== ''): ?>
          <p class="text-sm text-slate-400 py-4 text-center">No published articles matching "<?= h($q) ?>"</p>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /left col -->

  <!-- ── RIGHT COLUMN (1/3) ────────────────────────────────────── -->
  <div class="xl:col-span-1 space-y-6">

    <!-- Section Visibility -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
      <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background:#6366F118">
          <svg width="16" height="16" fill="none" stroke="#6366F1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        </div>
        <div>
          <h2 class="font-outfit font-black text-base text-slate-900">Section Visibility</h2>
          <p class="text-xs text-slate-400">Toggle homepage content sections</p>
        </div>
      </div>
      <form method="POST" class="px-5 py-4 space-y-3">
        <input type="hidden" name="action" value="visibility">
        <?php
        $toggles = [
          ['show_podcasts',  $showPodcasts,  'Latest Podcasts'],
          ['show_videos',    $showVideos,    'Latest Videos'],
          ['show_documents', $showDocuments, 'Recent Documents'],
        ];
        foreach ($toggles as [$key, $val, $label]): ?>
          <label class="flex items-center justify-between py-2 cursor-pointer">
            <span class="text-sm font-medium text-slate-700"><?= h($label) ?></span>
            <input type="checkbox" name="<?= $key ?>" value="1" <?= $val === '1' ? 'checked' : '' ?>
                   style="width:16px;height:16px;accent-color:#750B25;cursor:pointer">
          </label>
        <?php endforeach; ?>
        <div class="flex justify-end pt-2">
          <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold text-white" style="background:#6366F1">
            Save Visibility
          </button>
        </div>
      </form>
    </div>

    <!-- Homepage Carousel -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
      <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background:#10B98118">
          <svg width="16" height="16" fill="none" stroke="#10B981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
        </div>
        <div>
          <h2 class="font-outfit font-black text-base text-slate-900">Hero Carousel</h2>
          <p class="text-xs text-slate-400">Up to 5 slides · leave empty to use defaults</p>
        </div>
      </div>
      <div class="px-5 py-4">
        <div id="carousel-editor" class="space-y-3 mb-4"></div>
        <button type="button" onclick="addSlide()"
                class="w-full py-2.5 rounded-xl border-2 border-dashed border-slate-200 text-sm font-semibold text-slate-400 hover:border-green-400 hover:text-green-600 transition-all flex items-center justify-center gap-2">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
          Add Slide
        </button>
        <form method="POST" id="carousel-form" class="mt-4">
          <input type="hidden" name="action" value="carousel">
          <input type="hidden" name="carousel_slides" id="carousel-json" value="">
          <button type="submit" onclick="serializeCarousel()"
                  class="w-full py-2.5 rounded-xl text-sm font-bold text-white transition-opacity hover:opacity-90" style="background:#10B981">
            Save Carousel
          </button>
        </form>
        <?php if (count($carouselSlides) === 0): ?>
          <p class="text-[11px] text-slate-400 text-center mt-2">Using default hardcoded slides</p>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /right col -->

</div><!-- /grid -->

</main>
</div>
</div>

<?php uw_scripts(); ?>
<script>
/* ── Carousel editor ───────────────────────────────────────────── */
var slides = <?= json_encode($carouselSlides, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

function esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function renderSlides() {
  var el = document.getElementById('carousel-editor');
  if (slides.length === 0) {
    el.innerHTML = '<p class="text-xs text-slate-400 text-center py-2">No slides added yet</p>';
    return;
  }
  el.innerHTML = slides.map(function(s, i) {
    return '<div class="border border-slate-200 rounded-2xl overflow-hidden" id="slide-'+i+'">'
      + '<div style="background:#f8fafc;padding:8px 12px;display:flex;align-items:center;justify-content:space-between">'
      + '<span style="font-size:11px;font-weight:700;color:#64748b">Slide '+(i+1)+'</span>'
      + '<div style="display:flex;gap:6px">'
      + (i > 0 ? '<button type="button" onclick="moveSlide('+i+',-1)" style="padding:2px 6px;border:1px solid #e2e8f0;border-radius:6px;font-size:11px;background:#fff;cursor:pointer">↑</button>' : '')
      + (i < slides.length-1 ? '<button type="button" onclick="moveSlide('+i+',1)" style="padding:2px 6px;border:1px solid #e2e8f0;border-radius:6px;font-size:11px;background:#fff;cursor:pointer">↓</button>' : '')
      + '<button type="button" onclick="removeSlide('+i+')" style="padding:2px 6px;border:1px solid #fecaca;border-radius:6px;font-size:11px;background:#fff;color:#dc2626;cursor:pointer">✕</button>'
      + '</div>'
      + '</div>'
      + '<div style="padding:10px 12px;display:grid;gap:6px">'
      + '<div style="display:flex;gap:6px">'
      + '<input type="text" id="slide-img-'+i+'" placeholder="Image URL *" value="'+esc(s.img)+'" onchange="slides['+i+'].img=this.value" style="flex:1;min-width:0;padding:6px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:12px">'
      + '<button type="button" onclick="uploadSlideImage('+i+')" title="Upload image" style="padding:6px 10px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;cursor:pointer;font-size:11px;font-weight:700;color:#750B25;flex-shrink:0">Upload</button>'
      + '</div>'
      + '<input type="text" placeholder="Region / Country label" value="'+esc(s.region)+'" onchange="slides['+i+'].region=this.value" style="width:100%;padding:6px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:12px">'
      + '<input type="text" placeholder="Headline *" value="'+esc(s.title)+'" onchange="slides['+i+'].title=this.value" style="width:100%;padding:6px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:12px">'
      + '<input type="text" placeholder="Sub-headline" value="'+esc(s.sub)+'" onchange="slides['+i+'].sub=this.value" style="width:100%;padding:6px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:12px">'
      + '<input type="text" placeholder="Badge text (e.g. 47 Counties)" value="'+esc(s.badge)+'" onchange="slides['+i+'].badge=this.value" style="width:100%;padding:6px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:12px">'
      + '<input type="text" placeholder="CTA URL (optional, e.g. /news)" value="'+esc(s.url)+'" onchange="slides['+i+'].url=this.value" style="width:100%;padding:6px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:12px">'
      + '</div>'
      + '</div>';
  }).join('');
}

function addSlide() {
  if (slides.length >= 5) { alert('Maximum 5 slides.'); return; }
  slides.push({ img:'', region:'', title:'', sub:'', badge:'', url:'' });
  renderSlides();
}

function removeSlide(i) {
  slides.splice(i, 1);
  renderSlides();
}

function moveSlide(i, dir) {
  var j = i + dir;
  if (j < 0 || j >= slides.length) return;
  var tmp = slides[i]; slides[i] = slides[j]; slides[j] = tmp;
  renderSlides();
}

var _slideFileInput = null;
function uploadSlideImage(i) {
  if (!_slideFileInput) {
    _slideFileInput = document.createElement('input');
    _slideFileInput.type = 'file';
    _slideFileInput.accept = 'image/*';
    _slideFileInput.style.display = 'none';
    document.body.appendChild(_slideFileInput);
  }
  _slideFileInput.onchange = function() {
    var f = _slideFileInput.files[0];
    _slideFileInput.value = '';
    if (!f) return;
    var fd = new FormData();
    fd.append('file', f);
    fetch('/api/upload?type=image', { method: 'POST', body: fd })
      .then(function(r){ return r.json(); })
      .then(function(data){
        if (data.error) { alert(data.error); return; }
        slides[i].img = data.url;
        renderSlides();
      })
      .catch(function(){ alert('Upload failed — network error.'); });
  };
  _slideFileInput.click();
}

function serializeCarousel() {
  // Collect latest input values before serializing
  document.querySelectorAll('#carousel-editor input').forEach(function(inp) {
    var match = inp.placeholder;
    // already bound via onchange — values are live
  });
  document.getElementById('carousel-json').value = JSON.stringify(slides);
}

/* Toggle track animation */
document.addEventListener('DOMContentLoaded', function() {
  var cb = document.querySelector('input[name="announcement_active"]');
  var track = document.getElementById('toggle-track');
  var knob  = track ? track.nextElementSibling : null;
  if (cb && track && knob) {
    cb.addEventListener('change', function() {
      track.style.background = cb.checked ? '#750B25' : '#cbd5e1';
      knob.style.transform   = cb.checked ? 'translateX(20px)' : 'translateX(0)';
    });
  }
  renderSlides();
});
</script>

</body>
</html>
