<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_once dirname(__DIR__, 2) . '/includes/upload-widget.php';
require_once dirname(__DIR__, 2) . '/includes/partners.php';

$me  = require_super_admin();
$pdo = db();
ensure_settings_table();
ensure_partner_schema();
$v2 = partner_has_v2();

if (empty($_SESSION['partner_csrf'])) $_SESSION['partner_csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['partner_csrf'];

$error = '';
$form  = null;   // values to reopen the drawer with after a validation error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['_action'] ?? '');
    $isAjax = $action === 'reorder';
    if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
        if ($isAjax) json_error('Your session expired. Reload the page.', 403);
        $error = 'Your session expired. Please try again.';
    } else {
        $id = trim((string) ($_POST['id'] ?? ''));
        try {
            switch ($action) {
                case 'save':
                    $r = partner_save($_POST);
                    if ($r['ok']) { header('Location: /admin/super/partners?saved=' . ($id ? 'updated' : 'created')); exit; }
                    $error = $r['error'];
                    $form  = $_POST;
                    break;
                case 'delete':
                    if ($id) $pdo->prepare('DELETE FROM Partner WHERE id=?')->execute([$id]);
                    header('Location: /admin/super/partners?saved=deleted'); exit;
                case 'toggle':
                    if ($id) $pdo->prepare('UPDATE Partner SET isActive = 1 - isActive WHERE id=?')->execute([$id]);
                    header('Location: /admin/super/partners?saved=visibility'); exit;
                case 'feature':
                    if ($id && $v2) $pdo->prepare('UPDATE Partner SET featured = 1 - featured WHERE id=?')->execute([$id]);
                    header('Location: /admin/super/partners?saved=featured'); exit;
                case 'reorder':
                    $n = partner_reorder((array) ($_POST['ids'] ?? []));
                    json_response(['ok' => true, 'ordered' => $n]);
            }
        } catch (Throwable $e) {
            error_log('[admin/partners] ' . $action . ': ' . $e->getMessage());
            if ($isAjax) json_error('Could not save the new order.', 500);
            $error = 'Something went wrong: ' . $e->getMessage();
        }
    }
}

$partners = partners_all();
$total    = count($partners);
$visible  = count(array_filter($partners, fn($p) => $p['isActive']));
$featured = count(array_filter($partners, fn($p) => $p['featured']));
$typesIn  = array_unique(array_column($partners, 'category'));
$saved    = (string) ($_GET['saved'] ?? '');
$toastMsg = ['created' => 'Partner added', 'updated' => 'Changes saved', 'deleted' => 'Partner deleted',
             'visibility' => 'Visibility updated', 'featured' => 'Featured partners updated'][$saved] ?? '';

$icon = [
    'grip'   => '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="6" r="1.6"/><circle cx="15" cy="6" r="1.6"/><circle cx="9" cy="12" r="1.6"/><circle cx="15" cy="12" r="1.6"/><circle cx="9" cy="18" r="1.6"/><circle cx="15" cy="18" r="1.6"/></svg>',
    'star'   => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 3.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9l-5.2 2.7 1-5.8L3.5 9.7l5.9-.9z"/></svg>',
    'staro'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><path d="M12 3.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9l-5.2 2.7 1-5.8L3.5 9.7l5.9-.9z"/></svg>',
    'eye'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>',
    'eyeoff' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18M10.6 10.6a2 2 0 002.8 2.8M9.9 5.2A10.4 10.4 0 0112 5c6.4 0 10 7 10 7a17.6 17.6 0 01-3.1 3.9M6.6 6.6A17.4 17.4 0 002 12s3.6 7 10 7a9.7 9.7 0 004.4-1"/></svg>',
    'trash'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 002 2h6a2 2 0 002-2l1-12M9 7V4h6v3"/></svg>',
    'edit'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4z"/></svg>',
    'plus'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>',
    'search' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>',
    'grid'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/></svg>',
    'list'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><path d="M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01"/></svg>',
    'globe'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 010 18M12 3a14 14 0 000 18"/></svg>',
    'ext'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 01-1 1H5a1 1 0 01-1-1V7a1 1 0 011-1h5"/></svg>',
    'users'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 20v-1.5a3.5 3.5 0 00-3.5-3.5h-5A3.5 3.5 0 004 18.5V20M10 11a3.5 3.5 0 100-7 3.5 3.5 0 000 7zM20 20v-1.5a3.5 3.5 0 00-2.5-3.35M15.5 4.15a3.5 3.5 0 010 6.7"/></svg>',
    'layers' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><path d="M12 3l9 5-9 5-9-5zM3 13l9 5 9-5"/></svg>',
];

$pageTitle      = 'Partners | Tafakari Admin';
$adminPageTitle = 'Partners & Network';
$adminPageSub   = $total . ' partner' . ($total === 1 ? '' : 's') . ' · ' . $visible . ' visible';
?>
<?php include dirname(__DIR__, 2) . '/includes/head.php'; ?>
<body class="antialiased font-inter tk-admin" style="background:var(--tk-canvas)">
<div class="flex h-screen overflow-hidden">
<?php include dirname(__DIR__, 2) . '/includes/admin-sidebar.php'; ?>
<div class="flex-1 flex flex-col overflow-hidden min-w-0">
<?php include dirname(__DIR__, 2) . '/includes/admin-topbar.php'; ?>

<main class="flex-1 overflow-y-auto p-6 md:p-8">
<style>
.pt-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:16px; }
.pt-card { padding:18px; display:flex; flex-direction:column; gap:14px; position:relative; }
.pt-card__top { display:flex; gap:12px; align-items:flex-start; }
.pt-card__handle { margin:-4px -4px 0 -6px; padding:4px 2px; display:flex; }
.pt-card__handle svg { width:16px; height:16px; }
.pt-card__id { min-width:0; flex:1; }
.pt-card__id p { font:500 12.5px/1.45 var(--tk-font); color:var(--tk-text-3); margin:3px 0 0; }
.pt-card__desc { font:400 13px/1.6 var(--tk-font); color:var(--tk-text-2); margin:0; min-height:3.2em; }
.pt-card__meta { display:flex; flex-wrap:wrap; gap:6px; }
.pt-card__foot { display:flex; align-items:center; justify-content:space-between; gap:10px; padding-top:14px; border-top:1px solid var(--tk-line); margin-top:auto; }
.pt-card__actions { display:flex; align-items:center; gap:2px; }
.pt-card.is-hidden .pt-card__top, .pt-card.is-hidden .pt-card__desc, .pt-card.is-hidden .pt-card__meta { opacity:.55; }
.pt-feat { position:absolute; top:-1px; right:18px; height:22px; padding:0 9px; border-radius:0 0 9px 9px; background:var(--tk-amber); color:var(--tk-ink);
           font:800 10px/22px var(--tk-font); letter-spacing:.1em; text-transform:uppercase; }
.pt-link { display:inline-flex; align-items:center; gap:5px; font:600 12px/1 var(--tk-font); color:var(--tk-brand); text-decoration:none; }
.pt-link:hover { text-decoration:underline; }
.pt-link svg { width:13px; height:13px; }
.pt-preview { background:var(--tk-canvas); border:1px dashed var(--tk-line-2); border-radius:var(--tk-r-lg); padding:16px; }
.pt-preview .pt-card { box-shadow:var(--tk-shadow-2); }
.pt-count { font:500 12px var(--tk-font); color:var(--tk-text-3); }
[hidden] { display:none !important; }
</style>

  <header class="tk-page-head">
    <div>
      <span class="tk-eyebrow">Network</span>
      <h1 class="tk-h1" style="margin-top:12px">Partners &amp; Network Members</h1>
      <p class="tk-lead">Organisations shown on the public <a href="/partners" target="_blank" rel="noopener" class="pt-link" style="font-size:14px">Partners page</a>, the About page and the site footer.
        Drag cards to set their order; featured partners are shown first.</p>
    </div>
    <div class="tk-page-head__actions">
      <a href="/partners" target="_blank" rel="noopener" class="tk-btn tk-btn--outline"><?= $icon['ext'] ?>View public page</a>
      <button type="button" class="tk-btn tk-btn--primary" onclick="ptOpen()"><?= $icon['plus'] ?>Add partner</button>
    </div>
  </header>

  <?php if ($error && !$form): ?>
    <div class="tk-alert tk-alert--danger" role="alert"><?= h($error) ?></div>
  <?php endif; ?>
  <?php if (!$v2): ?>
    <div class="tk-alert tk-alert--danger">Partner types and featured partners need a small database update that could not be applied automatically. Run <code>migrations/008-partners-premium.sql</code> in phpMyAdmin.</div>
  <?php endif; ?>

  <section class="tk-stats" aria-label="Summary">
    <div class="tk-stat"><span class="tk-stat__icon"><?= $icon['users'] ?></span><div><p class="tk-stat__label">Partners</p><p class="tk-stat__value"><?= $total ?></p></div></div>
    <div class="tk-stat"><span class="tk-stat__icon tk-stat__icon--ok"><?= $icon['eye'] ?></span><div><p class="tk-stat__label">Visible on the site</p><p class="tk-stat__value"><?= $visible ?></p></div></div>
    <div class="tk-stat"><span class="tk-stat__icon tk-stat__icon--amber"><?= $icon['star'] ?></span><div><p class="tk-stat__label">Featured</p><p class="tk-stat__value"><?= $featured ?></p></div></div>
    <div class="tk-stat"><span class="tk-stat__icon tk-stat__icon--ink"><?= $icon['layers'] ?></span><div><p class="tk-stat__label">Partner types</p><p class="tk-stat__value"><?= count($typesIn) ?></p></div></div>
  </section>

  <?php if (!$partners): ?>
    <section class="tk-card tk-empty">
      <div class="tk-empty__icon"><?= $icon['users'] ?></div>
      <h2 class="tk-h2">No partners yet</h2>
      <p class="tk-lead" style="margin:8px auto 22px;font-size:14px">Add the organisations CRTP works with. Until then, the About page shows its default partner content.</p>
      <button type="button" class="tk-btn tk-btn--primary" onclick="ptOpen()"><?= $icon['plus'] ?>Add your first partner</button>
    </section>
  <?php else: ?>

  <div class="tk-card tk-toolbar" role="search">
    <label class="tk-input-icon" style="margin:0">
      <span class="sr-only" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)">Search partners</span>
      <?= $icon['search'] ?>
      <input type="search" id="pt-q" class="tk-input" placeholder="Search by name, subtitle or country" oninput="ptFilter()">
    </label>
    <div class="tk-segment" role="group" aria-label="Visibility">
      <button type="button" class="is-active" data-status="" onclick="ptStatus(this)">All <span class="tk-count"><?= $total ?></span></button>
      <button type="button" data-status="visible" onclick="ptStatus(this)">Visible <span class="tk-count"><?= $visible ?></span></button>
      <button type="button" data-status="hidden" onclick="ptStatus(this)">Hidden <span class="tk-count"><?= $total - $visible ?></span></button>
    </div>
    <?php if ($v2): ?>
      <select id="pt-type" class="tk-select" style="width:auto;min-width:210px" onchange="ptFilter()" aria-label="Partner type">
        <option value="">All types</option>
        <?php foreach (partner_types() as $k => $l): if (!in_array($k, $typesIn, true)) continue; ?>
          <option value="<?= h($k) ?>"><?= h($l) ?></option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>
    <div class="tk-segment" role="group" aria-label="Layout" style="margin-left:auto">
      <button type="button" class="is-active" data-view="grid" onclick="ptView(this)" aria-label="Card view"><?= $icon['grid'] ?></button>
      <button type="button" data-view="list" onclick="ptView(this)" aria-label="List view"><?= $icon['list'] ?></button>
    </div>
  </div>

  <p class="pt-count" id="pt-count" style="margin:-6px 0 12px" aria-live="polite"></p>

  <!-- Card view -->
  <div class="pt-grid" id="pt-grid">
    <?php foreach ($partners as $p):
      $web = partner_safe_url($p['websiteUrl'] ?? ''); ?>
      <article class="tk-card tk-card--hover pt-card <?= $p['isActive'] ? '' : 'is-hidden' ?>" draggable="true"
               data-id="<?= h($p['id']) ?>" data-type="<?= h($p['category']) ?>" data-status="<?= $p['isActive'] ? 'visible' : 'hidden' ?>"
               data-search="<?= h(mb_strtolower($p['name'] . ' ' . ($p['subtitle'] ?? '') . ' ' . $p['country'])) ?>">
        <?php if ($p['featured']): ?><span class="pt-feat">Featured</span><?php endif; ?>
        <div class="pt-card__top">
          <span class="tk-drag-handle pt-card__handle" title="Drag to reorder" aria-hidden="true"><?= $icon['grip'] ?></span>
          <?= partner_logo_html($p) ?>
          <div class="pt-card__id">
            <h3 class="tk-h3 tk-clamp-2"><?= h($p['name']) ?></h3>
            <?php if (!empty($p['subtitle'])): ?><p class="tk-clamp-2"><?= h($p['subtitle']) ?></p><?php endif; ?>
          </div>
        </div>
        <p class="pt-card__desc tk-clamp-3"><?= !empty($p['description']) ? h($p['description']) : '<span class="tk-muted">No description yet.</span>' ?></p>
        <div class="pt-card__meta">
          <?php if ($v2): ?><span class="tk-chip"><?= h($p['typeLabel']) ?></span><?php endif; ?>
          <?php if ($p['country'] !== ''): ?><span class="tk-chip"><?= h($p['country']) ?></span><?php endif; ?>
          <?php if ($p['partnerSince']): ?><span class="tk-chip">Since <?= (int) $p['partnerSince'] ?></span><?php endif; ?>
          <?php if ($web): ?><a class="pt-link" href="<?= h($web) ?>" target="_blank" rel="noopener noreferrer" style="margin-left:auto"><?= $icon['globe'] ?><?= h(partner_host($web)) ?></a><?php endif; ?>
        </div>
        <div class="pt-card__foot">
          <span class="tk-badge <?= $p['isActive'] ? 'tk-badge--ok' : 'tk-badge--muted' ?>"><?= $p['isActive'] ? 'Visible' : 'Hidden' ?></span>
          <div class="pt-card__actions">
            <?php if ($v2): ?>
              <form method="POST"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="_action" value="feature"><input type="hidden" name="id" value="<?= h($p['id']) ?>">
                <button type="submit" class="tk-icon-btn <?= $p['featured'] ? 'is-on' : '' ?>" title="<?= $p['featured'] ? 'Remove from featured' : 'Feature this partner' ?>" aria-label="<?= $p['featured'] ? 'Remove from featured' : 'Feature this partner' ?>"><?= $p['featured'] ? $icon['star'] : $icon['staro'] ?></button></form>
            <?php endif; ?>
            <form method="POST"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="_action" value="toggle"><input type="hidden" name="id" value="<?= h($p['id']) ?>">
              <button type="submit" class="tk-icon-btn" title="<?= $p['isActive'] ? 'Hide from the site' : 'Show on the site' ?>" aria-label="<?= $p['isActive'] ? 'Hide from the site' : 'Show on the site' ?>"><?= $p['isActive'] ? $icon['eyeoff'] : $icon['eye'] ?></button></form>
            <button type="button" class="tk-icon-btn tk-icon-btn--danger" title="Delete" aria-label="Delete <?= h($p['name']) ?>" onclick='ptAskDelete(<?= h(json_encode(['id' => $p['id'], 'name' => $p['name']], JSON_UNESCAPED_UNICODE)) ?>)'><?= $icon['trash'] ?></button>
            <button type="button" class="tk-btn tk-btn--outline tk-btn--sm" style="margin-left:6px" onclick='ptOpen(<?= h(json_encode($p, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>)'><?= $icon['edit'] ?>Edit</button>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <!-- List view -->
  <div class="tk-card" id="pt-list" hidden>
    <div class="tk-table-wrap">
      <table class="tk-table">
        <thead><tr><th style="width:36px"></th><th>Partner</th><?php if ($v2): ?><th>Type</th><?php endif; ?><th>Country</th><th>Website</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
        <tbody id="pt-tbody">
          <?php foreach ($partners as $p): $web = partner_safe_url($p['websiteUrl'] ?? ''); ?>
            <tr draggable="true" data-id="<?= h($p['id']) ?>" data-type="<?= h($p['category']) ?>" data-status="<?= $p['isActive'] ? 'visible' : 'hidden' ?>"
                data-search="<?= h(mb_strtolower($p['name'] . ' ' . ($p['subtitle'] ?? '') . ' ' . $p['country'])) ?>">
              <td><span class="tk-drag-handle" title="Drag to reorder" style="display:flex;width:16px"><?= $icon['grip'] ?></span></td>
              <td>
                <div style="display:flex;align-items:center;gap:12px">
                  <?= partner_logo_html($p, 'sm') ?>
                  <div style="min-width:0">
                    <strong style="color:var(--tk-text);display:block"><?= h($p['name']) ?> <?= $p['featured'] ? '<span class="tk-badge tk-badge--amber tk-badge--plain" style="height:20px;margin-left:4px">Featured</span>' : '' ?></strong>
                    <?php if (!empty($p['subtitle'])): ?><span class="tk-muted" style="font-size:12.5px"><?= h($p['subtitle']) ?></span><?php endif; ?>
                  </div>
                </div>
              </td>
              <?php if ($v2): ?><td><?= h($p['typeLabel']) ?></td><?php endif; ?>
              <td><?= $p['country'] !== '' ? h($p['country']) : '<span class="tk-muted">—</span>' ?></td>
              <td><?= $web ? '<a class="pt-link" href="' . h($web) . '" target="_blank" rel="noopener noreferrer">' . h(partner_host($web)) . '</a>' : '<span class="tk-muted">—</span>' ?></td>
              <td><span class="tk-badge <?= $p['isActive'] ? 'tk-badge--ok' : 'tk-badge--muted' ?>"><?= $p['isActive'] ? 'Visible' : 'Hidden' ?></span></td>
              <td style="text-align:right;white-space:nowrap">
                <button type="button" class="tk-btn tk-btn--ghost tk-btn--sm" onclick='ptOpen(<?= h(json_encode($p, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>)'><?= $icon['edit'] ?>Edit</button>
                <button type="button" class="tk-icon-btn tk-icon-btn--danger" aria-label="Delete <?= h($p['name']) ?>" onclick='ptAskDelete(<?= h(json_encode(['id' => $p['id'], 'name' => $p['name']], JSON_UNESCAPED_UNICODE)) ?>)'><?= $icon['trash'] ?></button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="tk-card tk-empty" id="pt-none" hidden>
    <div class="tk-empty__icon"><?= $icon['search'] ?></div>
    <h2 class="tk-h3">No partners match</h2>
    <p class="tk-help" style="margin-top:6px">Try a different search, or clear the filters.</p>
  </div>
  <?php endif; ?>
</main>
</div>
</div>

<!-- ── Editor drawer ───────────────────────────────────────────────── -->
<div class="tk-backdrop" id="pt-backdrop" onclick="ptClose()"></div>
<aside class="tk-drawer" id="pt-drawer" role="dialog" aria-modal="true" aria-labelledby="pt-drawer-title">
  <form method="POST" id="pt-form" onsubmit="return uwCheckRequired(this)" style="display:flex;flex-direction:column;height:100%">
    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
    <input type="hidden" name="_action" value="save">
    <input type="hidden" name="id" id="f-id" value="">

    <div class="tk-drawer__head">
      <div>
        <span class="tk-eyebrow" id="pt-drawer-eyebrow">New partner</span>
        <h2 class="tk-h2" id="pt-drawer-title" style="margin-top:10px">Add a partner</h2>
      </div>
      <button type="button" class="tk-icon-btn" onclick="ptClose()" aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
    </div>

    <div class="tk-drawer__body">
      <?php if ($error && $form): ?><div class="tk-alert tk-alert--danger" role="alert" style="margin:0"><?= h($error) ?></div><?php endif; ?>

      <div>
        <p class="tk-section-title">Live preview</p>
        <div class="pt-preview"><div id="pt-preview"></div></div>
      </div>

      <div>
        <p class="tk-section-title">Identity</p>
        <div style="display:flex;flex-direction:column;gap:14px">
          <div class="tk-field">
            <label class="tk-label" for="f-name">Partner name</label>
            <input type="text" name="name" id="f-name" class="tk-input" required maxlength="200" placeholder="e.g. Hekima University College" oninput="ptPreview()">
          </div>
          <div class="tk-field">
            <label class="tk-label" for="f-subtitle">Subtitle <small>optional</small></label>
            <input type="text" name="subtitle" id="f-subtitle" class="tk-input" maxlength="200" placeholder="e.g. Institute of Peace Studies & International Relations" oninput="ptPreview()">
          </div>
          <?php if ($v2): ?>
            <div class="tk-grid-2">
              <div class="tk-field">
                <label class="tk-label" for="f-category">Type</label>
                <select name="category" id="f-category" class="tk-select" onchange="ptPreview()">
                  <?php foreach (partner_types() as $k => $l): ?><option value="<?= h($k) ?>"><?= h($l) ?></option><?php endforeach; ?>
                </select>
              </div>
              <div class="tk-field">
                <label class="tk-label" for="f-country">Country <small>optional</small></label>
                <select name="country" id="f-country" class="tk-select" onchange="ptPreview()">
                  <option value="">Not specified</option>
                  <option value="International">International</option>
                  <?php foreach (african_countries() as $c): ?><option value="<?= h($c) ?>"><?= h($c) ?></option><?php endforeach; ?>
                </select>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div>
        <p class="tk-section-title">Brand</p>
        <?php uw_img('partner-logo', 'logoUrl', 'Logo', 'Square or wide logo; a transparent PNG looks best. Without one, a monogram is shown.'); ?>
      </div>

      <div>
        <p class="tk-section-title">About</p>
        <div style="display:flex;flex-direction:column;gap:14px">
          <div class="tk-field">
            <label class="tk-label" for="f-description">Description <small id="f-desc-count">0 / 600</small></label>
            <textarea name="description" id="f-description" class="tk-textarea" rows="4" maxlength="600" placeholder="One or two sentences on who they are and how they work with CRTP." oninput="ptPreview()"></textarea>
          </div>
          <div class="tk-grid-2">
            <div class="tk-field">
              <label class="tk-label" for="f-websiteUrl">Website <small>optional</small></label>
              <input type="url" name="websiteUrl" id="f-websiteUrl" class="tk-input" placeholder="https://" oninput="ptPreview()">
            </div>
            <?php if ($v2): ?>
              <div class="tk-field">
                <label class="tk-label" for="f-partnerSince">Partner since <small>year</small></label>
                <input type="number" name="partnerSince" id="f-partnerSince" class="tk-input" min="1950" max="<?= (int) date('Y') + 1 ?>" placeholder="<?= date('Y') ?>" oninput="ptPreview()">
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div>
        <p class="tk-section-title">Visibility</p>
        <div style="display:flex;flex-direction:column;gap:10px">
          <label class="tk-switch">
            <span class="tk-switch__text"><strong>Visible on the site</strong><span>Shown on the Partners page, the About page and in the footer.</span></span>
            <input type="checkbox" name="isActive" id="f-isActive" value="1" checked onchange="ptPreview()"><span class="tk-switch__track"></span>
          </label>
          <?php if ($v2): ?>
            <label class="tk-switch">
              <span class="tk-switch__text"><strong>Featured partner</strong><span>Listed first and given a spotlight card on the Partners page.</span></span>
              <input type="checkbox" name="featured" id="f-featured" value="1" onchange="ptPreview()"><span class="tk-switch__track"></span>
            </label>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="tk-drawer__foot">
      <button type="submit" class="tk-btn tk-btn--primary" style="flex:1" id="pt-submit">Save partner</button>
      <button type="button" class="tk-btn tk-btn--outline" onclick="ptClose()">Cancel</button>
    </div>
  </form>
</aside>

<!-- ── Delete confirmation ─────────────────────────────────────────── -->
<div class="tk-modal" id="pt-modal" role="alertdialog" aria-modal="true" aria-labelledby="pt-modal-title">
  <form method="POST" class="tk-modal__box">
    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
    <input type="hidden" name="_action" value="delete">
    <input type="hidden" name="id" id="pt-del-id">
    <div class="tk-empty__icon" style="margin:0 0 14px;background:var(--tk-danger-50);color:var(--tk-danger)"><?= $icon['trash'] ?></div>
    <h2 class="tk-h2" id="pt-modal-title">Delete this partner?</h2>
    <p class="tk-lead" style="font-size:14px;margin-top:8px"><strong id="pt-del-name"></strong> will be removed from the site permanently. To take it off the site but keep it, hide it instead.</p>
    <div style="display:flex;gap:10px;margin-top:22px">
      <button type="submit" class="tk-btn tk-btn--danger" style="flex:1">Delete partner</button>
      <button type="button" class="tk-btn tk-btn--outline" onclick="ptCloseModal()">Cancel</button>
    </div>
  </form>
</div>

<div class="tk-toast" id="pt-toast" role="status" aria-live="polite"><span class="tk-toast__dot"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg></span><span id="pt-toast-msg"></span></div>

<?php uw_scripts(); ?>
<script>
(function () {
  var CSRF  = <?= json_encode($csrf) ?>;
  var TYPES = <?= json_encode(partner_types(), JSON_UNESCAPED_UNICODE) ?>;
  var V2    = <?= $v2 ? 'true' : 'false' ?>;
  var $ = function (id) { return document.getElementById(id); };
  var status = '';

  // ── Toast ──
  function toast(msg, isError) {
    var t = $('pt-toast');
    $('pt-toast-msg').textContent = msg;
    t.classList.toggle('tk-toast--error', !!isError);
    t.classList.add('is-shown');
    clearTimeout(toast._t);
    toast._t = setTimeout(function () { t.classList.remove('is-shown'); }, 2800);
  }
  window.ptToast = toast;
  <?php if ($toastMsg): ?>document.addEventListener('DOMContentLoaded', function () { toast(<?= json_encode($toastMsg) ?>); });<?php endif; ?>

  // ── Filtering (search, visibility, type) ──
  function matches(el) {
    var q = ($('pt-q') ? $('pt-q').value : '').trim().toLowerCase();
    var type = $('pt-type') ? $('pt-type').value : '';
    return (!q || el.dataset.search.indexOf(q) !== -1)
        && (!status || el.dataset.status === status)
        && (!type || el.dataset.type === type);
  }
  window.ptFilter = function () {
    var n = 0, total = 0;
    document.querySelectorAll('#pt-grid [data-id]').forEach(function (el) { total++; var ok = matches(el); el.hidden = !ok; if (ok) n++; });
    document.querySelectorAll('#pt-tbody [data-id]').forEach(function (el) { el.hidden = !matches(el); });
    if ($('pt-none')) $('pt-none').hidden = n !== 0;
    if ($('pt-count')) $('pt-count').textContent = n === total ? '' : 'Showing ' + n + ' of ' + total + ' partners';
  };
  window.ptStatus = function (btn) {
    btn.parentNode.querySelectorAll('button').forEach(function (b) { b.classList.toggle('is-active', b === btn); });
    status = btn.dataset.status;
    ptFilter();
  };
  window.ptView = function (btn) {
    btn.parentNode.querySelectorAll('button').forEach(function (b) { b.classList.toggle('is-active', b === btn); });
    var list = btn.dataset.view === 'list';
    $('pt-grid').hidden = list;
    $('pt-list').hidden = !list;
    try { localStorage.setItem('tk.partners.view', btn.dataset.view); } catch (e) {}
  };
  document.addEventListener('DOMContentLoaded', function () {
    try {
      if (localStorage.getItem('tk.partners.view') === 'list') {
        var b = document.querySelector('[data-view="list"]'); if (b) ptView(b);
      }
    } catch (e) {}
  });

  // ── Drag-and-drop ordering (cards and table rows share one order) ──
  function makeSortable(container, selector) {
    if (!container) return;
    var dragEl = null;
    container.addEventListener('dragstart', function (e) {
      dragEl = e.target.closest(selector);
      if (!dragEl) return;
      dragEl.classList.add('is-dragging');
      e.dataTransfer.effectAllowed = 'move';
      try { e.dataTransfer.setData('text/plain', dragEl.dataset.id); } catch (err) {}
    });
    container.addEventListener('dragover', function (e) {
      if (!dragEl) return;
      e.preventDefault();
      var over = e.target.closest(selector);
      if (!over || over === dragEl) return;
      var r = over.getBoundingClientRect();
      var after = container.tagName === 'TBODY' ? (e.clientY > r.top + r.height / 2) : (e.clientX > r.left + r.width / 2 || e.clientY > r.bottom - 10);
      container.insertBefore(dragEl, after ? over.nextSibling : over);
    });
    container.addEventListener('dragend', function () {
      if (!dragEl) return;
      dragEl.classList.remove('is-dragging');
      dragEl = null;
      saveOrder(Array.prototype.map.call(container.querySelectorAll(selector), function (el) { return el.dataset.id; }));
    });
  }
  function saveOrder(ids) {
    // Mirror the order into the other view so both stay in step
    ['pt-grid', 'pt-tbody'].forEach(function (cid) {
      var c = $(cid); if (!c) return;
      ids.forEach(function (id) { var el = c.querySelector('[data-id="' + CSS.escape(id) + '"]'); if (el) c.appendChild(el); });
    });
    var body = new URLSearchParams();
    body.append('csrf', CSRF); body.append('_action', 'reorder');
    ids.forEach(function (id) { body.append('ids[]', id); });
    fetch('/admin/super/partners', { method: 'POST', body: body, credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
      .then(function (r) { return r.json().then(function (j) { if (!r.ok) throw new Error(j.error || 'Failed'); }); })
      .then(function () { toast('Order saved'); })
      .catch(function (err) { toast(err.message || 'Could not save the order', true); });
  }
  makeSortable($('pt-grid'), '.pt-card');
  makeSortable($('pt-tbody'), 'tr');

  // ── Editor drawer with live preview ──
  function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
  function initials(name) {
    var skip = ['of','the','and','for','in','&','de','la','des'];
    var w = String(name || '').trim().split(/[\s\-]+/).filter(function (x) { return x && skip.indexOf(x.toLowerCase()) === -1; });
    return w.length ? (w[0][0] + (w[1] ? w[1][0] : '')).toUpperCase() : '?';
  }
  function host(u) { try { return new URL(/^https?:\/\//i.test(u) ? u : 'https://' + u).hostname.replace(/^www\./, ''); } catch (e) { return ''; } }
  window.ptPreview = function () {
    var name = $('f-name').value.trim() || 'Partner name';
    var logo = ($('uw-val-partner-logo') || {}).value || '';
    var desc = $('f-description').value.trim();
    var web  = $('f-websiteUrl').value.trim();
    var type = V2 ? TYPES[$('f-category').value] : '';
    var ctry = V2 ? $('f-country').value : '';
    var since = V2 ? $('f-partnerSince').value : '';
    var on = $('f-isActive').checked, feat = V2 && $('f-featured').checked;
    $('f-desc-count').textContent = desc.length + ' / 600';
    var logoHtml = logo
      ? '<span class="tk-logo"><img src="' + esc(logo) + '" alt=""></span>'
      : '<span class="tk-logo"><span class="tk-mono">' + esc(initials(name)) + '</span></span>';
    $('pt-preview').innerHTML =
      '<article class="tk-card pt-card' + (on ? '' : ' is-hidden') + '">'
      + (feat ? '<span class="pt-feat">Featured</span>' : '')
      + '<div class="pt-card__top">' + logoHtml + '<div class="pt-card__id"><h3 class="tk-h3 tk-clamp-2">' + esc(name) + '</h3>'
      + ($('f-subtitle').value.trim() ? '<p class="tk-clamp-2">' + esc($('f-subtitle').value.trim()) + '</p>' : '') + '</div></div>'
      + '<p class="pt-card__desc tk-clamp-3">' + (desc ? esc(desc) : '<span class="tk-muted">No description yet.</span>') + '</p>'
      + '<div class="pt-card__meta">' + (type ? '<span class="tk-chip">' + esc(type) + '</span>' : '')
      + (ctry ? '<span class="tk-chip">' + esc(ctry) + '</span>' : '') + (since ? '<span class="tk-chip">Since ' + esc(since) + '</span>' : '')
      + (web && host(web) ? '<span class="pt-link" style="margin-left:auto">' + esc(host(web)) + '</span>' : '') + '</div>'
      + '<div class="pt-card__foot"><span class="tk-badge ' + (on ? 'tk-badge--ok">Visible' : 'tk-badge--muted">Hidden') + '</span></div></article>';
  };
  function setLogo(url) {
    var valEl = $('uw-val-partner-logo'), imgEl = $('uw-img-partner-logo'), nmEl = $('uw-name-partner-logo'),
        prEl = $('uw-prev-partner-logo'), emEl = $('uw-empty-partner-logo');
    if (valEl) valEl.value = url;
    if (url) {
      if (imgEl) { imgEl.src = url; imgEl.style.display = 'block'; }
      if (nmEl) nmEl.textContent = url.split('/').pop();
      if (prEl) prEl.style.display = '';
      if (emEl) emEl.style.display = 'none';
    } else {
      if (imgEl) { imgEl.src = ''; imgEl.style.display = 'none'; }
      if (prEl) prEl.style.display = 'none';
      if (emEl) emEl.style.display = '';
    }
  }
  var lastFocus = null;
  window.ptOpen = function (p) {
    lastFocus = document.activeElement;
    p = p || {};
    $('pt-drawer-eyebrow').textContent = p.id ? 'Edit partner' : 'New partner';
    $('pt-drawer-title').textContent = p.id ? (p.name || 'Edit partner') : 'Add a partner';
    $('pt-submit').textContent = p.id ? 'Save changes' : 'Add partner';
    $('f-id').value = p.id || '';
    $('f-name').value = p.name || '';
    $('f-subtitle').value = p.subtitle || '';
    $('f-description').value = p.description || '';
    $('f-websiteUrl').value = p.websiteUrl || '';
    $('f-isActive').checked = p.id ? (p.isActive === true || parseInt(p.isActive, 10) === 1) : true;
    if (V2) {
      $('f-category').value = p.category || 'NETWORK';
      $('f-country').value = p.country || '';
      $('f-partnerSince').value = p.partnerSince || '';
      $('f-featured').checked = p.featured === true || parseInt(p.featured, 10) === 1;
    }
    setLogo(p.logoUrl || '');
    ptPreview();
    $('pt-drawer').classList.add('is-open');
    $('pt-backdrop').classList.add('is-open');
    document.body.style.overflow = 'hidden';
    setTimeout(function () { $('f-name').focus(); }, 60);
  };
  window.ptClose = function () {
    $('pt-drawer').classList.remove('is-open');
    $('pt-backdrop').classList.remove('is-open');
    document.body.style.overflow = '';
    if (lastFocus) lastFocus.focus();
  };
  // Refresh the preview when the upload widget sets a new logo
  document.addEventListener('change', function (e) { if (e.target && e.target.id === 'uw-val-partner-logo') ptPreview(); }, true);
  setInterval(function () { var v = $('uw-val-partner-logo'); if (v && v.value !== (v._last || '') && $('pt-drawer').classList.contains('is-open')) { v._last = v.value; ptPreview(); } }, 600);

  // ── Delete confirmation ──
  window.ptAskDelete = function (p) {
    $('pt-del-id').value = p.id;
    $('pt-del-name').textContent = p.name;
    $('pt-modal').classList.add('is-open');
    $('pt-backdrop').classList.add('is-open');
    setTimeout(function () { $('pt-modal').querySelector('.tk-btn--outline').focus(); }, 60);
  };
  window.ptCloseModal = function () {
    $('pt-modal').classList.remove('is-open');
    if (!$('pt-drawer').classList.contains('is-open')) $('pt-backdrop').classList.remove('is-open');
  };
  $('pt-backdrop').addEventListener('click', ptCloseModal);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { ptCloseModal(); ptClose(); } });

  <?php if ($form): ?>
  // Reopen the editor with what was typed, after a validation error
  document.addEventListener('DOMContentLoaded', function () {
    ptOpen(<?= json_encode(array_intersect_key($form, array_flip(['id', 'name', 'subtitle', 'description', 'websiteUrl', 'logoUrl', 'category', 'country', 'partnerSince'])) + ['isActive' => !empty($form['isActive']) ? 1 : 0, 'featured' => !empty($form['featured']) ? 1 : 0], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>);
  });
  <?php endif; ?>
})();
</script>
</body>
</html>
