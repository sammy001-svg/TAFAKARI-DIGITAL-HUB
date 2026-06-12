<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

$me = require_super_admin();
$pdo = db();

$users = $pdo->query(
    "SELECT u.id, u.name, u.username, u.email, u.role, u.createdAt,
            COUNT(p.id) AS postCount
     FROM User u LEFT JOIN Post p ON p.authorId = u.id
     GROUP BY u.id ORDER BY u.createdAt DESC"
)->fetchAll();

$pageTitle = 'User Management | Tafakari Admin';
?>
<?php include dirname(__DIR__, 2) . '/includes/head.php'; ?>
<body class="antialiased bg-slate-100 font-inter">
<div class="flex h-screen overflow-hidden">
<?php include dirname(__DIR__, 2) . '/includes/admin-sidebar.php'; ?>

<div class="flex-1 overflow-y-auto p-10">
  <div class="flex justify-between items-center mb-10">
    <div>
      <h1 class="font-outfit text-3xl font-bold text-slate-900">User Management</h1>
      <p class="text-slate-500 mt-1"><?= count($users) ?> account<?= count($users) !== 1 ? 's' : '' ?> total</p>
    </div>
    <button onclick="openModal('create')" class="btn-primary">+ Add User</button>
  </div>

  <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
    <table class="w-full">
      <thead>
        <tr class="border-b border-slate-100">
          <th class="text-left px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">User</th>
          <th class="text-left px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 hidden md:table-cell">Role</th>
          <th class="text-left px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 hidden lg:table-cell">Posts</th>
          <th class="text-left px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 hidden lg:table-cell">Joined</th>
          <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-50">
        <?php foreach ($users as $u): ?>
          <tr class="hover:bg-slate-50/50 transition-colors">
            <td class="px-8 py-5">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white font-black text-sm shrink-0" style="background:#9A1415">
                  <?= strtoupper(substr($u['name'] ?? $u['username'] ?? 'U', 0, 1)) ?>
                </div>
                <div>
                  <p class="text-sm font-bold text-slate-900"><?= h($u['name'] ?? $u['username']) ?></p>
                  <p class="text-xs text-slate-400">@<?= h($u['username']) ?> &bull; <?= h($u['email'] ?? '—') ?></p>
                </div>
              </div>
            </td>
            <td class="px-8 py-5 hidden md:table-cell">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest <?= $u['role'] === 'SUPER_ADMIN' ? 'bg-rose-50 text-rose-600' : 'bg-slate-100 text-slate-600' ?>">
                <?= h(str_replace('_', ' ', $u['role'])) ?>
              </span>
            </td>
            <td class="px-8 py-5 text-sm text-slate-600 hidden lg:table-cell"><?= $u['postCount'] ?></td>
            <td class="px-8 py-5 text-xs text-slate-400 hidden lg:table-cell"><?= format_date($u['createdAt'], 'M j, Y') ?></td>
            <td class="px-8 py-5">
              <div class="flex justify-end gap-2">
                <button onclick='openModal("edit",<?= json_encode($u) ?>)'
                        class="px-4 py-2 text-[10px] font-black uppercase tracking-widest bg-slate-100 text-slate-600 rounded-xl hover:bg-slate-200 transition-colors">
                  Edit
                </button>
                <?php if ($u['id'] !== $me['id']): ?>
                  <button onclick="deleteUser('<?= h($u['id']) ?>',<?= (int)$u['postCount'] ?>,'<?= h($u['name'] ?? $u['username']) ?>')"
                          class="px-4 py-2 text-[10px] font-black uppercase tracking-widest bg-rose-100 text-rose-700 rounded-xl hover:bg-rose-200 transition-colors">
                    Delete
                  </button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</div>

<!-- Modal -->
<div id="modal" class="fixed inset-0 z-50 hidden items-center justify-center" style="background:rgba(0,0,0,.5)">
  <div class="bg-white rounded-3xl p-8 shadow-xl w-full max-w-md mx-4">
    <div class="flex justify-between items-center mb-6">
      <h2 id="modal-title" class="font-outfit font-bold text-xl text-slate-900">Add User</h2>
      <button onclick="closeModal()" class="text-slate-400 hover:text-slate-700 text-2xl leading-none">&times;</button>
    </div>
    <div id="modal-err" class="hidden mb-4 p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-sm"></div>
    <div class="space-y-4">
      <input id="f-name"     type="text"  placeholder="Full Name"  class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none">
      <input id="f-username" type="text"  placeholder="Username"   class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none">
      <input id="f-email"    type="email" placeholder="Email"      class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none">
      <input id="f-password" type="password" placeholder="Password (leave blank to keep)" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none">
      <select id="f-role" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none">
        <option value="ADMIN">Admin</option>
        <option value="SUPER_ADMIN">Super Admin</option>
      </select>
    </div>
    <div class="flex gap-3 mt-6">
      <button id="modal-submit" onclick="submitModal()" class="btn-primary flex-1 py-3">Save</button>
      <button onclick="closeModal()" class="flex-1 py-3 rounded-full border border-slate-200 text-sm font-bold text-slate-600 hover:bg-slate-50">Cancel</button>
    </div>
  </div>
</div>

<script>
var modalMode = 'create';
var editId    = null;

function openModal(mode, user) {
  modalMode = mode;
  editId = user ? user.id : null;
  document.getElementById('modal-title').textContent = mode==='create' ? 'Add User' : 'Edit User';
  document.getElementById('f-name').value     = user ? (user.name||'')     : '';
  document.getElementById('f-username').value = user ? (user.username||'') : '';
  document.getElementById('f-email').value    = user ? (user.email||'')    : '';
  document.getElementById('f-password').value = '';
  document.getElementById('f-role').value     = user ? user.role : 'ADMIN';
  document.getElementById('f-password').placeholder = mode==='create' ? 'Password (required)' : 'Password (leave blank to keep)';
  document.getElementById('modal-err').classList.add('hidden');
  document.getElementById('modal').classList.remove('hidden');
  document.getElementById('modal').classList.add('flex');
}

function closeModal() {
  document.getElementById('modal').classList.add('hidden');
  document.getElementById('modal').classList.remove('flex');
}

function submitModal() {
  var data = {
    name:     document.getElementById('f-name').value.trim(),
    username: document.getElementById('f-username').value.trim(),
    email:    document.getElementById('f-email').value.trim(),
    role:     document.getElementById('f-role').value,
  };
  var pass = document.getElementById('f-password').value;
  if (pass) data.password = pass;

  var url    = modalMode==='create' ? '/api/users' : '/api/users/'+editId;
  var method = modalMode==='create' ? 'POST' : 'PUT';

  document.getElementById('modal-submit').textContent = 'Saving…';
  fetch(url, { method:method, headers:{'Content-Type':'application/json'}, body: JSON.stringify(data) })
    .then(r=>r.json())
    .then(function(d) {
      document.getElementById('modal-submit').textContent = 'Save';
      if (d.error) { var e=document.getElementById('modal-err'); e.textContent=d.error; e.classList.remove('hidden'); return; }
      closeModal(); location.reload();
    }).catch(function(){ document.getElementById('modal-submit').textContent='Save'; });
}

function deleteUser(id, postCount, name) {
  if (postCount > 0) { alert(name + ' has ' + postCount + ' post(s). Delete or reassign them first.'); return; }
  if (!confirm('Permanently delete user ' + name + '?')) return;
  fetch('/api/users/'+id, { method:'DELETE' }).then(r=>r.json()).then(function(d){
    if (d.error) { alert(d.error); return; }
    location.reload();
  });
}

document.getElementById('modal').addEventListener('click', function(e){ if(e.target===this) closeModal(); });
</script>
</body>
</html>
