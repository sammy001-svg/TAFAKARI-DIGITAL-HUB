<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_once dirname(__DIR__, 2) . '/includes/upload-widget.php';

$me  = require_super_admin();
$pdo = db();

ensure_team_table();

$error = '';
$saved = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['_action'] ?? '';

    if ($action === 'save') {
        $id        = trim($_POST['id'] ?? '');
        $name      = trim($_POST['name'] ?? '');
        $role      = trim($_POST['role'] ?? '');
        $bio       = trim($_POST['bio'] ?? '');
        $photoUrl  = trim($_POST['photoUrl'] ?? '');
        $sortOrder = (int)($_POST['sortOrder'] ?? 0);
        $isActive  = isset($_POST['isActive']) ? 1 : 0;

        if (!$name) {
            $error = 'Name is required.';
        } else {
            if ($id) {
                $pdo->prepare(
                    'UPDATE TeamMember SET name=?, role=?, bio=?, photoUrl=?, sortOrder=?, isActive=? WHERE id=?'
                )->execute([$name, $role, $bio, $photoUrl, $sortOrder, $isActive, $id]);
                header('Location: /admin/super/team?saved=updated');
                exit;
            } else {
                $newId = generate_id();
                $pdo->prepare(
                    'INSERT INTO TeamMember (id,name,role,bio,photoUrl,sortOrder,isActive,createdAt,updatedAt)
                     VALUES (?,?,?,?,?,?,?,NOW(3),NOW(3))'
                )->execute([$newId, $name, $role, $bio, $photoUrl, $sortOrder, $isActive]);
                header('Location: /admin/super/team?saved=created');
                exit;
            }
        }
    } elseif ($action === 'delete') {
        $id = trim($_POST['id'] ?? '');
        if ($id) $pdo->prepare('DELETE FROM TeamMember WHERE id=?')->execute([$id]);
        header('Location: /admin/super/team?saved=deleted');
        exit;
    } elseif ($action === 'toggle') {
        $id = trim($_POST['id'] ?? '');
        if ($id) $pdo->prepare('UPDATE TeamMember SET isActive = 1 - isActive WHERE id=?')->execute([$id]);
        header('Location: /admin/super/team');
        exit;
    }
}

if (isset($_GET['saved'])) $saved = $_GET['saved'];

$members = $pdo->query('SELECT * FROM TeamMember ORDER BY sortOrder ASC, createdAt ASC')->fetchAll();

$pageTitle      = 'Team | Tafakari Admin';
$adminPageTitle = 'Team';
$adminPageSub   = count($members) . ' member' . (count($members) !== 1 ? 's' : '') . ' · shown on the About page';
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
    <?= ['created'=>'Team member added','updated'=>'Team member updated','deleted'=>'Team member deleted'][$saved] ?? 'Saved' ?>
  </div>
  <script>setTimeout(function(){ var t=document.getElementById('save-toast'); if(t) t.style.opacity='0'; }, 2800);</script>
<?php endif; ?>

  <!-- Page Header -->
  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="font-outfit font-black text-2xl text-slate-900">Team</h1>
      <p class="text-sm text-slate-400 mt-1">People shown in the "Our Team" section on the About page.</p>
    </div>
    <button type="button" onclick="openMemberDrawer('create')"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-[13px] font-bold shadow-sm transition-opacity hover:opacity-90"
            style="background:#750B25;color:#fff;border:none;cursor:pointer">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
      Add Team Member
    </button>
  </div>

  <?php if ($error): ?>
    <div class="mb-5 px-4 py-3 rounded-2xl text-sm font-medium text-red-800 bg-red-50 border border-red-200"><?= h($error) ?></div>
  <?php endif; ?>

  <?php if (empty($members)): ?>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-16 text-center">
      <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4" style="background:rgba(117,11,37,.06)">
        <svg width="24" height="24" fill="none" stroke="#750B25" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
          <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
      </div>
      <h3 class="font-outfit font-bold text-xl text-slate-900">No team members yet</h3>
      <p class="text-slate-500 mt-2 text-sm mb-6">The About page currently falls back to its default placeholder roles until you add someone here.</p>
      <button type="button" onclick="openMemberDrawer('create')" class="btn-primary" style="padding:.65rem 1.5rem;border:none;cursor:pointer">Add Your First Team Member</button>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <?php foreach ($members as $m): ?>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 flex flex-col <?= (int)$m['isActive'] === 0 ? 'opacity-50' : '' ?>">
          <div class="flex items-start gap-3 mb-3">
            <div class="w-12 h-12 rounded-xl border border-slate-100 flex items-center justify-center shrink-0 bg-slate-50 overflow-hidden">
              <?php if (!empty($m['photoUrl'])): ?>
                <img src="<?= h($m['photoUrl']) ?>" alt="" class="w-full h-full object-cover" onerror="this.style.display='none'">
              <?php else: ?>
                <span class="font-outfit font-black text-lg text-slate-300"><?= h(strtoupper(substr($m['name'], 0, 1))) ?></span>
              <?php endif; ?>
            </div>
            <div class="grow min-w-0">
              <p class="text-sm font-bold text-slate-900 leading-snug truncate"><?= h($m['name']) ?></p>
              <?php if (!empty($m['role'])): ?>
                <p class="text-[11px] text-amber-700 font-bold uppercase tracking-widest truncate"><?= h($m['role']) ?></p>
              <?php endif; ?>
            </div>
            <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full shrink-0 <?= (int)$m['isActive'] === 1 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' ?>">
              <?= (int)$m['isActive'] === 1 ? 'Active' : 'Hidden' ?>
            </span>
          </div>
          <?php if (!empty($m['bio'])): ?>
            <p class="text-xs text-slate-500 leading-relaxed line-clamp-3 mb-4"><?= h($m['bio']) ?></p>
          <?php endif; ?>
          <div class="flex items-center gap-2 mt-auto pt-3 border-t border-slate-50">
            <button type="button" onclick='openMemberDrawer("edit", <?= json_encode($m, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>)'
                    class="flex-1 px-3 py-1.5 text-[10px] font-black uppercase tracking-widest bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-colors">
              Edit
            </button>
            <form method="POST" onsubmit="return confirm('Toggle visibility of <?= h(addslashes($m['name'])) ?>?')">
              <input type="hidden" name="_action" value="toggle">
              <input type="hidden" name="id" value="<?= h($m['id']) ?>">
              <button type="submit" class="px-3 py-1.5 text-[10px] font-black uppercase tracking-widest bg-amber-50 text-amber-700 rounded-lg hover:bg-amber-100 transition-colors">
                <?= (int)$m['isActive'] === 1 ? 'Hide' : 'Show' ?>
              </button>
            </form>
            <form method="POST" onsubmit="return confirm('Permanently delete <?= h(addslashes($m['name'])) ?>? This cannot be undone.')">
              <input type="hidden" name="_action" value="delete">
              <input type="hidden" name="id" value="<?= h($m['id']) ?>">
              <button type="submit" class="px-3 py-1.5 text-[10px] font-black uppercase tracking-widest bg-rose-100 text-rose-700 rounded-lg hover:bg-rose-200 transition-colors">
                Delete
              </button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</main>
</div>
</div>

<!-- ── Create/Edit Team Member Drawer ─────────────────────────────── -->
<div id="create-backdrop" onclick="closeMemberDrawer()"
     style="position:fixed;inset:0;z-index:400;background:rgba(0,0,0,.45);opacity:0;pointer-events:none;transition:opacity .25s"></div>

<div id="create-drawer"
     style="position:fixed;top:0;right:0;bottom:0;z-index:500;width:min(520px,100vw);
            background:#fff;transform:translateX(100%);transition:transform .3s cubic-bezier(.4,0,.2,1);
            overflow-y:auto;display:flex;flex-direction:column;box-shadow:-8px 0 48px rgba(0,0,0,.14)">

  <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid #f1f5f9;flex-shrink:0;position:sticky;top:0;background:#fff;z-index:1">
    <div>
      <p id="drawer-eyebrow" style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.14em;color:#750B25;margin:0 0 3px">New Team Member</p>
      <p style="font-size:13px;font-weight:600;color:#0f172a;margin:0">Shown in the Our Team section</p>
    </div>
    <button onclick="closeMemberDrawer()" style="width:36px;height:36px;border-radius:50%;border:none;background:#f1f5f9;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#64748b;font-size:20px;line-height:1">&times;</button>
  </div>

  <?php if ($error): ?>
    <div style="margin:16px 24px 0;padding:12px 16px;background:#fef2f2;border:1px solid #fecaca;border-radius:12px;color:#dc2626;font-size:13px;font-weight:500"><?= h($error) ?></div>
  <?php endif; ?>

  <form method="POST" id="member-form" onsubmit="return uwCheckRequired(this)" style="flex:1;padding:24px;display:flex;flex-direction:column;gap:18px">
    <input type="hidden" name="_action" value="save">
    <input type="hidden" name="id" id="f-id" value="">

    <div>
      <label style="display:block;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:#94a3b8;margin-bottom:7px">Full Name *</label>
      <input type="text" name="name" id="f-name" required
             style="width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#f8fafc;font-size:14px;box-sizing:border-box;outline:none;font-family:inherit"
             placeholder="e.g. Jane Mwangi">
    </div>

    <div>
      <label style="display:block;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:#94a3b8;margin-bottom:7px">Role / Title</label>
      <input type="text" name="role" id="f-role"
             style="width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#f8fafc;font-size:14px;box-sizing:border-box;outline:none;font-family:inherit"
             placeholder="e.g. Head of Research">
    </div>

    <?php uw_img('member-photo', 'photoUrl', 'Photo', 'Square headshot works best.'); ?>

    <div>
      <label style="display:block;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:#94a3b8;margin-bottom:7px">Bio</label>
      <textarea name="bio" id="f-bio" rows="3"
                style="width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#f8fafc;font-size:14px;box-sizing:border-box;outline:none;resize:none;font-family:inherit"
                placeholder="Brief biography (optional)"></textarea>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;align-items:end">
      <div>
        <label style="display:block;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:#94a3b8;margin-bottom:7px">Sort Order</label>
        <input type="number" name="sortOrder" id="f-sortOrder" value="0"
               style="width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#f8fafc;font-size:14px;box-sizing:border-box;outline:none;font-family:inherit">
      </div>
      <label style="display:flex;align-items:center;gap:8px;padding-bottom:12px;cursor:pointer">
        <input type="checkbox" name="isActive" id="f-isActive" value="1" checked style="width:16px;height:16px;accent-color:#750B25;cursor:pointer">
        <span style="font-size:13px;font-weight:600;color:#334155">Active (visible on About page)</span>
      </label>
    </div>

    <div style="display:flex;gap:12px;padding-top:4px;padding-bottom:8px">
      <button type="submit"
              style="flex:1;padding:13px;border:none;border-radius:12px;background:#750B25;color:#fff;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;transition:opacity .15s"
              onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
        Save Team Member
      </button>
      <button type="button" onclick="closeMemberDrawer()"
              style="padding:13px 20px;border:1.5px solid #e2e8f0;border-radius:12px;background:#fff;color:#64748b;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit">
        Cancel
      </button>
    </div>
  </form>
</div>

<?php uw_scripts(); ?>
<script>
function openMemberDrawer(mode, member) {
  document.getElementById('drawer-eyebrow').textContent = mode === 'edit' ? 'Edit Team Member' : 'New Team Member';
  document.getElementById('f-id').value        = member ? (member.id || '') : '';
  document.getElementById('f-name').value      = member ? (member.name || '') : '';
  document.getElementById('f-role').value      = member ? (member.role || '') : '';
  document.getElementById('f-bio').value       = member ? (member.bio || '') : '';
  document.getElementById('f-sortOrder').value = member ? (member.sortOrder || 0) : 0;
  document.getElementById('f-isActive').checked = member ? (parseInt(member.isActive, 10) === 1) : true;

  // Populate the photo upload widget's current value/preview
  var photoUrl = member ? (member.photoUrl || '') : '';
  var valEl = document.getElementById('uw-val-member-photo');
  var imgEl = document.getElementById('uw-img-member-photo');
  var nmEl  = document.getElementById('uw-name-member-photo');
  var prEl  = document.getElementById('uw-prev-member-photo');
  var emEl  = document.getElementById('uw-empty-member-photo');
  if (valEl) valEl.value = photoUrl;
  if (photoUrl) {
    if (imgEl) { imgEl.src = photoUrl; imgEl.style.display = 'block'; }
    if (nmEl)  nmEl.textContent = photoUrl.split('/').pop();
    if (prEl)  prEl.style.display = '';
    if (emEl)  emEl.style.display = 'none';
  } else {
    if (imgEl) { imgEl.src = ''; imgEl.style.display = 'none'; }
    if (prEl)  prEl.style.display = 'none';
    if (emEl)  emEl.style.display = '';
  }

  document.getElementById('create-drawer').style.transform = 'translateX(0)';
  var bd = document.getElementById('create-backdrop');
  bd.style.opacity = '1'; bd.style.pointerEvents = 'auto';
  document.body.style.overflow = 'hidden';
}
function closeMemberDrawer() {
  document.getElementById('create-drawer').style.transform = 'translateX(100%)';
  var bd = document.getElementById('create-backdrop');
  bd.style.opacity = '0'; bd.style.pointerEvents = 'none';
  document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeMemberDrawer(); });
<?php if ($error): ?>document.addEventListener('DOMContentLoaded', function(){ openMemberDrawer('create'); });<?php endif; ?>
</script>
</body>
</html>
