<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_once dirname(__DIR__, 2) . '/includes/about-content.php';

$me  = require_super_admin();
$uid = $me['id'];

ensure_settings_table();

$saved   = '';
$saveErr = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'text') {
            // Only keys we know about; blank means "revert to the built-in copy"
            foreach (array_keys(about_defaults()) as $k) {
                if (!array_key_exists($k, $_POST)) continue;
                set_setting('about_' . $k, trim((string)$_POST[$k]), $uid);
            }
            $saved = 'text';

        } elseif ($action === 'programmes') {
            $rows = json_decode(trim((string)($_POST['rows'] ?? '')), true);
            $clean = [];
            foreach (is_array($rows) ? $rows : [] as $r) {
                if (!is_array($r)) continue;
                $title = trim((string)($r['title'] ?? ''));
                if ($title === '') continue;
                $tags = [];
                foreach ((array)($r['tags'] ?? []) as $t) {
                    $t = trim((string)$t);
                    if ($t !== '' && !in_array($t, $tags, true)) $tags[] = $t;
                }
                $clean[] = [
                    'num'   => trim((string)($r['num'] ?? '')),
                    'title' => $title,
                    'desc'  => trim((string)($r['desc'] ?? '')),
                    'tags'  => array_slice($tags, 0, 6),
                ];
            }
            set_setting('about_list_programmes', $clean ? json_encode($clean, JSON_UNESCAPED_UNICODE) : '', $uid);
            $saved = 'programmes';

        } elseif ($action === 'values') {
            $rows = json_decode(trim((string)($_POST['rows'] ?? '')), true);
            $clean = [];
            foreach (is_array($rows) ? $rows : [] as $r) {
                if (!is_array($r)) continue;
                $title = trim((string)($r['title'] ?? ''));
                if ($title === '') continue;
                $letter = trim((string)($r['letter'] ?? ''));
                $clean[] = [
                    // Fall back to the first letter of the title
                    'letter' => mb_strtoupper($letter !== '' ? mb_substr($letter, 0, 1) : mb_substr($title, 0, 1)),
                    'title'  => $title,
                    'desc'   => trim((string)($r['desc'] ?? '')),
                ];
            }
            set_setting('about_list_values', $clean ? json_encode($clean, JSON_UNESCAPED_UNICODE) : '', $uid);
            $saved = 'values';

        } elseif ($action === 'geo') {
            $rows  = json_decode(trim((string)($_POST['rows'] ?? '')), true);
            $tags  = about_geo_tags();
            $clean = [];
            foreach (is_array($rows) ? $rows : [] as $r) {
                if (!is_array($r)) continue;
                $region = trim((string)($r['region'] ?? ''));
                if ($region === '') continue;
                $tag = trim((string)($r['tag'] ?? ''));
                $clean[] = [
                    'region'    => $region,
                    'countries' => trim((string)($r['countries'] ?? '')),
                    'tag'       => in_array($tag, $tags, true) ? $tag : 'Monitoring',
                ];
            }
            set_setting('about_list_geo', $clean ? json_encode($clean, JSON_UNESCAPED_UNICODE) : '', $uid);
            $saved = 'geo';

        } elseif ($action === 'reset_all') {
            foreach (array_keys(about_defaults()) as $k) set_setting('about_' . $k, '', $uid);
            foreach (['programmes','values','geo'] as $k) set_setting('about_list_' . $k, '', $uid);
            $saved = 'reset';
        }
    } catch (Throwable $e) {
        error_log('[admin/super/about] save failed: ' . $e->getMessage());
        $saveErr = 'Could not save: ' . $e->getMessage();
    }
}

$defs  = about_defaults();
$texts = [];
foreach (array_keys($defs) as $k) $texts[$k] = about_text($k);

$listProgrammes = about_list('programmes');
$listValues     = about_list('values');
$listGeo        = about_list('geo');
$customCount    = count(array_filter($texts, fn($t) => $t['custom']));

// Groups shown as cards, in page order
$groups = [
    'Hero'               => ['hero_badge','hero_title1','hero_title2','hero_desc','hero_cta1','hero_cta2'],
    'Purpose'            => ['purpose_badge','purpose_title','purpose_desc'],
    'Mission & Vision'   => ['mission_title','mission_desc','vision_title','vision_desc'],
    'Programme Areas'    => ['programmes_badge','programmes_title'],
    'Geographic Focus'   => ['geo_badge','geo_title','geo_desc','geo_cta'],
    'Guiding Values'     => ['values_badge','values_title'],
    'Team'               => ['team_badge','team_title'],
    'Call to Action'     => ['cta_title','cta_desc','cta_btn1','cta_btn2'],
];
$longFields = ['hero_desc','purpose_desc','mission_desc','vision_desc','geo_desc','cta_desc'];
$labels = [
    'hero_badge'=>'Badge','hero_title1'=>'Headline line 1','hero_title2'=>'Headline line 2 (gold)',
    'hero_desc'=>'Intro paragraph','hero_cta1'=>'Primary button','hero_cta2'=>'Secondary button',
    'purpose_badge'=>'Badge','purpose_title'=>'Heading','purpose_desc'=>'Paragraph',
    'mission_title'=>'Mission heading','mission_desc'=>'Mission text',
    'vision_title'=>'Vision heading','vision_desc'=>'Vision text',
    'programmes_badge'=>'Badge','programmes_title'=>'Heading',
    'geo_badge'=>'Badge','geo_title'=>'Heading','geo_desc'=>'Paragraph','geo_cta'=>'Button label',
    'values_badge'=>'Badge','values_title'=>'Heading',
    'team_badge'=>'Badge','team_title'=>'Heading',
    'cta_title'=>'Heading','cta_desc'=>'Paragraph','cta_btn1'=>'Primary button','cta_btn2'=>'Secondary button',
];

$pageTitle      = 'About Page | Tafakari Admin';
$adminPageTitle = 'About Page';
$adminPageSub   = 'Edit the public About Us page content without touching code';
?>
<?php include dirname(__DIR__, 2) . '/includes/head.php'; ?>
<style>
  .ab-input {
    width:100%; padding:10px 13px; border:1.5px solid #e2e8f0; border-radius:11px;
    background:#f8fafc; font-size:14px; font-family:inherit; outline:none; box-sizing:border-box;
    transition:border-color .15s;
  }
  .ab-input:focus { border-color:#750B25; background:#fff; }
  textarea.ab-input { resize:vertical; line-height:1.6; }
  .ab-row { border:1px solid #e2e8f0; border-radius:14px; padding:14px; margin-bottom:10px; background:#fff; }
  .ab-lbl { display:block; font-size:10px; font-weight:800; text-transform:uppercase;
            letter-spacing:.1em; color:#94a3b8; margin-bottom:6px; }
</style>

<body class="antialiased font-inter" style="background:#F4F6F8">
<div class="flex h-screen overflow-hidden">
<?php include dirname(__DIR__, 2) . '/includes/admin-sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden min-w-0">
<?php include dirname(__DIR__, 2) . '/includes/admin-topbar.php'; ?>

<main class="flex-1 overflow-y-auto p-6 md:p-8">

  <?php if ($saved): ?>
    <div class="mb-6 px-5 py-3.5 rounded-2xl text-sm font-bold flex items-center gap-2.5"
         style="background:#dcfce7;color:#166534;border:1px solid #86efac">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
      <?= ['text'=>'Page text saved','programmes'=>'Programme areas saved','values'=>'Guiding values saved','geo'=>'Geographic focus saved','reset'=>'All content reset to the built-in defaults'][$saved] ?? 'Saved' ?>
      &mdash; <a href="/about" target="_blank" class="underline">view the page</a>
    </div>
  <?php endif; ?>

  <?php if ($saveErr): ?>
    <div class="mb-6 px-5 py-3.5 rounded-2xl text-sm font-bold" style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca">
      <?= h($saveErr) ?>
    </div>
  <?php endif; ?>

  <!-- How this works -->
  <div class="mb-7 rounded-2xl bg-white border border-slate-200 p-5">
    <div class="flex items-start gap-3">
      <svg width="18" height="18" fill="none" stroke="#750B25" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px">
        <circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>
      </svg>
      <div class="text-sm text-slate-600 leading-relaxed">
        <p class="font-bold text-slate-800 mb-1">How this page works</p>
        <p>
          Leave a field <strong>blank</strong> to keep the built-in wording, which stays translated into
          Swahili and French. As soon as you enter your own text it is shown exactly as typed, in every
          language &mdash; translation no longer applies to that field.
          <?php if ($customCount): ?>
            <span class="block mt-1.5 font-semibold" style="color:#750B25">
              <?= (int)$customCount ?> field<?= $customCount === 1 ? '' : 's' ?> currently customised.
            </span>
          <?php endif; ?>
        </p>
        <p class="mt-2 text-slate-400 text-[13px]">
          Team members and partners are managed separately, under
          <a href="/admin/super/team" class="underline" style="color:#750B25">Team</a> and
          <a href="/admin/super/partners" class="underline" style="color:#750B25">Partners</a>.
        </p>
      </div>
    </div>
  </div>

  <!-- ── Page text ─────────────────────────────────────────────────── -->
  <form method="POST" class="mb-8">
    <input type="hidden" name="action" value="text">
    <div class="rounded-2xl bg-white border border-slate-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="font-outfit font-black text-lg text-slate-900">Page Text</h2>
        <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white" style="background:#750B25">
          Save Page Text
        </button>
      </div>

      <div class="p-6 space-y-7">
        <?php foreach ($groups as $groupName => $keys): ?>
          <div>
            <p class="text-[10px] font-black uppercase tracking-[.14em] mb-3" style="color:#750B25"><?= h($groupName) ?></p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <?php foreach ($keys as $k):
                $isLong = in_array($k, $longFields, true);
                $t      = $texts[$k]; ?>
                <div class="<?= $isLong ? 'md:col-span-2' : '' ?>">
                  <label class="ab-lbl">
                    <?= h($labels[$k] ?? $k) ?>
                    <?php if ($t['custom']): ?>
                      <span class="ml-1 normal-case tracking-normal font-bold" style="color:#750B25">· customised</span>
                    <?php else: ?>
                      <span class="ml-1 normal-case tracking-normal text-slate-300 font-semibold">· default</span>
                    <?php endif; ?>
                  </label>
                  <?php if ($isLong): ?>
                    <textarea name="<?= h($k) ?>" rows="3" class="ab-input"
                              placeholder="<?= h($defs[$k][1]) ?>"><?= h($t['custom'] ? $t['text'] : '') ?></textarea>
                  <?php else: ?>
                    <input type="text" name="<?= h($k) ?>" class="ab-input"
                           value="<?= h($t['custom'] ? $t['text'] : '') ?>"
                           placeholder="<?= h($defs[$k][1]) ?>">
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </form>

  <!-- ── Programme areas ───────────────────────────────────────────── -->
  <form method="POST" class="mb-8" onsubmit="return serialize('programmes')">
    <input type="hidden" name="action" value="programmes">
    <input type="hidden" name="rows" id="programmes-json">
    <div class="rounded-2xl bg-white border border-slate-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
        <div>
          <h2 class="font-outfit font-black text-lg text-slate-900">Core Programme Areas</h2>
          <p class="text-xs text-slate-400 mt-0.5">Cards shown under &ldquo;What We Do&rdquo;</p>
        </div>
        <div class="flex gap-2">
          <button type="button" onclick="addRow('programmes')" class="px-4 py-2.5 rounded-xl text-xs font-bold border border-slate-200 text-slate-600 hover:bg-slate-50">+ Add</button>
          <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white" style="background:#750B25">Save</button>
        </div>
      </div>
      <div class="p-6"><div id="programmes-rows"></div></div>
    </div>
  </form>

  <!-- ── Guiding values ────────────────────────────────────────────── -->
  <form method="POST" class="mb-8" onsubmit="return serialize('values')">
    <input type="hidden" name="action" value="values">
    <input type="hidden" name="rows" id="values-json">
    <div class="rounded-2xl bg-white border border-slate-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
        <div>
          <h2 class="font-outfit font-black text-lg text-slate-900">Guiding Values</h2>
          <p class="text-xs text-slate-400 mt-0.5">Leave the letter blank to use the first letter of the title</p>
        </div>
        <div class="flex gap-2">
          <button type="button" onclick="addRow('values')" class="px-4 py-2.5 rounded-xl text-xs font-bold border border-slate-200 text-slate-600 hover:bg-slate-50">+ Add</button>
          <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white" style="background:#750B25">Save</button>
        </div>
      </div>
      <div class="p-6"><div id="values-rows"></div></div>
    </div>
  </form>

  <!-- ── Geographic focus ──────────────────────────────────────────── -->
  <form method="POST" class="mb-8" onsubmit="return serialize('geo')">
    <input type="hidden" name="action" value="geo">
    <input type="hidden" name="rows" id="geo-json">
    <div class="rounded-2xl bg-white border border-slate-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
        <div>
          <h2 class="font-outfit font-black text-lg text-slate-900">Geographic Focus</h2>
          <p class="text-xs text-slate-400 mt-0.5">Regions listed beside the &ldquo;Where We Work&rdquo; text</p>
        </div>
        <div class="flex gap-2">
          <button type="button" onclick="addRow('geo')" class="px-4 py-2.5 rounded-xl text-xs font-bold border border-slate-200 text-slate-600 hover:bg-slate-50">+ Add</button>
          <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white" style="background:#750B25">Save</button>
        </div>
      </div>
      <div class="p-6"><div id="geo-rows"></div></div>
    </div>
  </form>

  <!-- ── Reset ─────────────────────────────────────────────────────── -->
  <form method="POST" class="mb-10"
        onsubmit="return confirm('Reset every About-page field back to the built-in wording? Your custom text will be discarded.')">
    <input type="hidden" name="action" value="reset_all">
    <button type="submit" class="text-xs font-bold text-slate-400 hover:text-rose-600 underline">
      Reset all About-page content to defaults
    </button>
  </form>

</main>
</div>
</div>

<script>
var DATA = {
  programmes: <?= json_encode($listProgrammes, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>,
  values:     <?= json_encode($listValues,     JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>,
  geo:        <?= json_encode($listGeo,        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>
};
var GEO_TAGS = <?= json_encode(about_geo_tags(), JSON_HEX_TAG) ?>;

function esc(s) {
  return String(s == null ? '' : s)
    .replace(/&/g, '&amp;').replace(/"/g, '&quot;')
    .replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function delBtn(kind, i) {
  return '<button type="button" onclick="delRow(\'' + kind + '\',' + i + ')" '
       + 'class="text-xs font-bold text-slate-300 hover:text-rose-600" title="Remove">Remove</button>';
}

function render(kind) {
  var host = document.getElementById(kind + '-rows');
  var rows = DATA[kind] || [];

  if (!rows.length) {
    host.innerHTML = '<p class="text-sm text-slate-400 py-4 text-center">'
      + 'Nothing here yet. Add a row, or save empty to restore the built-in list.</p>';
    return;
  }

  host.innerHTML = rows.map(function (r, i) {
    var head = '<div class="flex items-center justify-between mb-3">'
             + '<span class="text-[10px] font-black uppercase tracking-widest text-slate-300">#' + (i + 1) + '</span>'
             + delBtn(kind, i) + '</div>';

    if (kind === 'programmes') {
      return '<div class="ab-row">' + head
        + '<div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">'
        +   '<div><label class="ab-lbl">Number</label>'
        +     '<input class="ab-input f-num" value="' + esc(r.num) + '" placeholder="01"></div>'
        +   '<div class="md:col-span-3"><label class="ab-lbl">Title</label>'
        +     '<input class="ab-input f-title" value="' + esc(r.title) + '" placeholder="Programme title"></div>'
        + '</div>'
        + '<div class="mb-3"><label class="ab-lbl">Description</label>'
        +   '<textarea class="ab-input f-desc" rows="3">' + esc(r.desc) + '</textarea></div>'
        + '<div><label class="ab-lbl">Tags (comma separated)</label>'
        +   '<input class="ab-input f-tags" value="' + esc((r.tags || []).join(', ')) + '" placeholder="Conflict Analysis, Early Warning"></div>'
        + '</div>';
    }

    if (kind === 'values') {
      return '<div class="ab-row">' + head
        + '<div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">'
        +   '<div><label class="ab-lbl">Letter</label>'
        +     '<input class="ab-input f-letter" maxlength="1" value="' + esc(r.letter) + '" placeholder="R"></div>'
        +   '<div class="md:col-span-3"><label class="ab-lbl">Title</label>'
        +     '<input class="ab-input f-title" value="' + esc(r.title) + '" placeholder="Rigour"></div>'
        + '</div>'
        + '<div><label class="ab-lbl">Description</label>'
        +   '<textarea class="ab-input f-desc" rows="2">' + esc(r.desc) + '</textarea></div>'
        + '</div>';
    }

    // geo
    var opts = GEO_TAGS.map(function (t) {
      return '<option value="' + esc(t) + '"' + (r.tag === t ? ' selected' : '') + '>' + esc(t) + '</option>';
    }).join('');
    return '<div class="ab-row">' + head
      + '<div class="grid grid-cols-1 md:grid-cols-3 gap-3">'
      +   '<div><label class="ab-lbl">Region</label>'
      +     '<input class="ab-input f-region" value="' + esc(r.region) + '" placeholder="East Africa"></div>'
      +   '<div><label class="ab-lbl">Countries</label>'
      +     '<input class="ab-input f-countries" value="' + esc(r.countries) + '" placeholder="Kenya · Uganda"></div>'
      +   '<div><label class="ab-lbl">Status</label>'
      +     '<select class="ab-input f-tag">' + opts + '</select></div>'
      + '</div></div>';
  }).join('');
}

function readRows(kind) {
  var host = document.getElementById(kind + '-rows');
  var out  = [];
  host.querySelectorAll('.ab-row').forEach(function (el) {
    var v = function (sel) { var n = el.querySelector(sel); return n ? n.value.trim() : ''; };
    if (kind === 'programmes') {
      out.push({
        num: v('.f-num'), title: v('.f-title'), desc: v('.f-desc'),
        tags: v('.f-tags').split(',').map(function (t) { return t.trim(); }).filter(Boolean)
      });
    } else if (kind === 'values') {
      out.push({ letter: v('.f-letter'), title: v('.f-title'), desc: v('.f-desc') });
    } else {
      out.push({ region: v('.f-region'), countries: v('.f-countries'), tag: v('.f-tag') });
    }
  });
  return out;
}

function addRow(kind) {
  // Read what is on screen first so in-progress edits are not lost
  DATA[kind] = readRows(kind);
  DATA[kind].push(kind === 'programmes' ? { num: '', title: '', desc: '', tags: [] }
                : kind === 'values'     ? { letter: '', title: '', desc: '' }
                :                         { region: '', countries: '', tag: 'Monitoring' });
  render(kind);
}

function delRow(kind, i) {
  DATA[kind] = readRows(kind);
  DATA[kind].splice(i, 1);
  render(kind);
}

function serialize(kind) {
  document.getElementById(kind + '-json').value = JSON.stringify(readRows(kind));
  return true;
}

['programmes', 'values', 'geo'].forEach(render);
</script>
</body>
</html>
