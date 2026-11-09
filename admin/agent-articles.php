<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/agent-articles.php';
require_once dirname(__DIR__) . '/includes/agent-videos.php';
require_once dirname(__DIR__) . '/includes/agent-nav.php';

$me      = require_auth();
$isSuper = is_super_admin();
$uid     = $me['id'];

ensure_settings_table();
ensure_agent_tables();
ensure_agent_article_table();

if (empty($_SESSION['agent_csrf'])) $_SESSION['agent_csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['agent_csrf'];

$id = (string) ($_GET['id'] ?? '');

// Word download: the current saved version
if ($id !== '' && isset($_GET['word'])) {
    $a = agent_article_get($id);
    if (!$a) { http_response_code(404); exit('Article not found.'); }
    try {
        $bytes = agent_article_docx($a);
    } catch (Throwable $e) {
        error_log('[admin/agent-articles] word: ' . $e->getMessage());
        http_response_code(500);
        exit('The Word file could not be created. Check that the PHP zip extension is enabled.');
    }
    $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($a['title'])) ?? '', '-');
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . (substr($slug, 0, 80) ?: 'tafakari-article') . '.docx"');
    header('Content-Length: ' . strlen($bytes));
    header('X-Content-Type-Options: nosniff');
    echo $bytes;
    exit;
}

$back = function (string $url, string $msg, bool $err = false): never {
    $_SESSION['article_flash'] = ['msg' => $msg, 'err' => $err];
    header('Location: ' . $url);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $self   = $id !== '' ? '/admin/agent/articles/' . rawurlencode($id) : '/admin/agent/articles';
    if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) $back($self, 'Your session expired. Please try again.', true);

    try {
        switch ($action) {
            case 'save':
                agent_article_save($id, (string) ($_POST['title'] ?? ''), (string) ($_POST['standfirst'] ?? ''), (string) ($_POST['body'] ?? ''), $uid);
                $back($self, 'Changes saved.');

            case 'publish':
                // Publishes what is on screen, including edits not yet saved
                agent_article_save($id, (string) ($_POST['title'] ?? ''), (string) ($_POST['standfirst'] ?? ''), (string) ($_POST['body'] ?? ''), $uid);
                agent_article_publish($id, $uid, isset($_POST['verified']));
                $back($self, 'Published. The article is now live on the site.');

            case 'reject':
                agent_article_reject($id, $uid, (string) ($_POST['notes'] ?? ''));
                $back('/admin/agent/articles', 'Article rejected. It stays on record under Rejected.');

            case 'restore':
                agent_article_restore($id);
                $back($self, 'Returned for review.');

            default:
                $back($self, 'Unknown action.', true);
        }
    } catch (Throwable $e) {
        error_log('[admin/agent-articles] ' . $action . ': ' . $e->getMessage());
        $back($self, $e->getMessage(), true);
    }
}

$flash = $_SESSION['article_flash'] ?? null;
unset($_SESSION['article_flash']);

$article = $id !== '' ? agent_article_get($id) : null;
if ($id !== '' && !$article) {
    $_SESSION['article_flash'] = ['msg' => 'Article not found.', 'err' => true];
    header('Location: /admin/agent/articles');
    exit;
}

$validTabs = ['PROPOSED' => 'To verify', 'PUBLISHED' => 'Published', 'REJECTED' => 'Rejected'];
$tab = isset($validTabs[$_GET['status'] ?? '']) ? $_GET['status'] : 'PROPOSED';
$counts = ['PROPOSED' => 0, 'PUBLISHED' => 0, 'REJECTED' => 0];
$rows = [];
try {
    foreach (db()->query('SELECT status, COUNT(*) AS n FROM AgentArticle GROUP BY status')->fetchAll() as $r) $counts[$r['status']] = (int) $r['n'];
    if (!$article) {
        $q = db()->prepare('SELECT * FROM AgentArticle WHERE status = ? ORDER BY createdAt DESC LIMIT 100');
        $q->execute([$tab]);
        $rows = array_map('agent_article_decode', $q->fetchAll());
    }
} catch (Throwable $e) {}

$reviewer = null;
if ($article && !empty($article['reviewedBy'])) {
    try {
        $q = db()->prepare('SELECT name FROM User WHERE id = ? LIMIT 1');
        $q->execute([$article['reviewedBy']]);
        $reviewer = $q->fetchColumn() ?: null;
    } catch (Throwable $e) {}
}

$confBadge = ['high' => 'tk-badge--ok', 'medium' => 'tk-badge--warn', 'low' => 'tk-badge--danger'];

$pageTitle      = 'Articles | Tafakari Agent';
$adminPageTitle = 'Tafakari Agent';
$adminPageSub   = 'Drafts by the Tafakari Agent, awaiting verification';
?>
<?php include dirname(__DIR__) . '/includes/head.php'; ?>
<body class="antialiased font-inter tk-admin" style="background:var(--tk-canvas)">
<div class="flex h-screen overflow-hidden">
<?php include dirname(__DIR__) . '/includes/admin-sidebar.php'; ?>
<div class="flex-1 flex flex-col overflow-hidden min-w-0">
<?php include dirname(__DIR__) . '/includes/admin-topbar.php'; ?>
<main class="flex-1 overflow-y-auto p-6 md:p-8">

<?php if ($flash): ?>
  <div class="tk-alert <?= $flash['err'] ? 'tk-alert--danger' : 'tk-alert--ok' ?>" role="status" style="margin-bottom:16px"><?= h($flash['msg']) ?></div>
<?php endif; ?>

<?php if ($article):
    $open = $article['status'] === 'PROPOSED';
    $statusLabel = ['PROPOSED' => ['tk-badge--warn', 'Awaiting verification'], 'PUBLISHED' => ['tk-badge--ok', 'Published'], 'REJECTED' => ['tk-badge--danger', 'Rejected']][$article['status']] ?? ['tk-badge--muted', $article['status']];
    $selfUrl = '/admin/agent/articles/' . rawurlencode($article['id']);
?>
  <!-- ── Verify one article ─────────────────────────────────────── -->
  <header class="tk-page-head">
    <div>
      <a href="/admin/agent/articles" class="text-xs font-bold" style="color:#750B25">&larr; All articles</a>
      <h1 class="tk-h1" style="margin-top:10px"><?= $open ? 'Verify this article' : h($article['title']) ?></h1>
      <p class="tk-lead"><?= h($article['regionName']) ?> · <?= h($article['category']) ?> · drafted <?= h(format_relative_time($article['createdAt'])) ?></p>
    </div>
    <div class="tk-page-head__actions">
      <span class="tk-badge <?= h($statusLabel[0]) ?>"><?= h($statusLabel[1]) ?></span>
      <a href="<?= h($selfUrl) ?>/word" class="tk-btn tk-btn--outline">Download Word</a>
    </div>
  </header>

  <div class="grid xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2">
      <?php if ($open): ?>
        <form method="POST" id="ar-form" class="tk-card tk-card--pad" style="display:flex;flex-direction:column;gap:18px">
          <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
          <div class="tk-field">
            <label class="tk-label" for="ar-title">Title</label>
            <input id="ar-title" name="title" class="tk-input" maxlength="255" required value="<?= h($article['title']) ?>">
          </div>
          <div class="tk-field">
            <label class="tk-label" for="ar-stand">Summary</label>
            <textarea id="ar-stand" name="standfirst" class="tk-textarea" rows="3" maxlength="600" required><?= h($article['standfirst']) ?></textarea>
          </div>
          <div class="tk-field">
            <label class="tk-label" for="ar-body">Article text</label>
            <textarea id="ar-body" name="body" class="tk-textarea" rows="26" required style="line-height:1.65"><?= h($article['body']) ?></textarea>
            <p class="tk-help">Numbers such as [2] refer to the numbered sources. "## " starts a heading. Save before downloading, so the Word file includes your edits.</p>
          </div>
          <label class="flex items-start gap-3 cursor-pointer" style="padding:14px 16px;border:1px solid var(--tk-line);border-radius:14px">
            <input type="checkbox" name="verified" value="1" id="ar-ok" class="mt-1 w-4 h-4" style="accent-color:#750B25">
            <span class="text-sm text-slate-800"><strong>I have verified this article.</strong> I opened the sources, they support what the article says, and no private individual is identified or put at risk.</span>
          </label>
          <div class="flex flex-wrap gap-3">
            <button type="submit" name="action" value="publish" id="ar-pub" class="tk-btn tk-btn--primary" disabled>Publish article</button>
            <button type="submit" name="action" value="save" class="tk-btn tk-btn--outline">Save changes</button>
          </div>
        </form>
        <form method="POST" class="tk-card tk-card--pad flex flex-wrap items-center gap-3" style="margin-top:16px">
          <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
          <input type="hidden" name="action" value="reject">
          <input type="text" name="notes" maxlength="1000" required class="tk-input" style="flex:1;min-width:220px" placeholder="Reason for rejecting (required)" aria-label="Reason for rejecting">
          <button type="submit" class="tk-btn tk-btn--danger">Reject</button>
        </form>
        <script>
          (function () {
            var ok = document.getElementById('ar-ok'), pub = document.getElementById('ar-pub');
            ok.addEventListener('change', function () { pub.disabled = !ok.checked; });
          })();
        </script>
      <?php else: ?>
        <article class="tk-card tk-card--pad">
          <p class="text-base text-slate-600 leading-relaxed" style="margin-bottom:12px"><?= h($article['standfirst']) ?></p>
          <div class="prose max-w-none text-slate-700 leading-relaxed"><?= markdown_to_html($article['body']) ?></div>
        </article>
      <?php endif; ?>
    </div>

    <aside style="display:flex;flex-direction:column;gap:16px">
      <?php if (!$open): ?>
        <div class="tk-card tk-card--pad text-sm text-slate-700">
          <strong><?= h($statusLabel[1]) ?></strong><?php if ($reviewer): ?> by <?= h($reviewer) ?><?php endif; ?>
          <?php if (!empty($article['reviewedAt'])): ?> · <?= h(format_date($article['reviewedAt'], 'j M Y, H:i')) ?><?php endif; ?>
          <?php if (!empty($article['reviewNotes'])): ?><p class="mt-2 text-slate-500">“<?= h($article['reviewNotes']) ?>”</p><?php endif; ?>
          <?php if (!empty($article['postId'])): ?>
            <p class="mt-3"><a class="font-bold underline" style="color:#750B25" target="_blank" rel="noopener" href="/news/<?= h(rawurlencode($article['postId'])) ?>">Open the public article</a>
              · <a class="font-bold underline" style="color:#750B25" href="/admin/content/<?= h(rawurlencode($article['postId'])) ?>/edit">Edit it</a></p>
          <?php endif; ?>
          <?php if ($article['status'] === 'REJECTED'): ?>
            <form method="POST" class="mt-3">
              <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
              <input type="hidden" name="action" value="restore">
              <button type="submit" class="tk-btn tk-btn--outline tk-btn--sm">Return for review</button>
            </form>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <div class="tk-card tk-card--pad">
        <h2 class="font-outfit font-black text-base text-slate-900">Check these first</h2>
        <div class="flex flex-wrap gap-2 mt-3">
          <span class="tk-badge <?= h($confBadge[$article['confidence']] ?? 'tk-badge--muted') ?>">Confidence: <?= h($article['confidence']) ?></span>
          <span class="tk-badge tk-badge--muted"><?= h(implode(', ', $article['countries'])) ?></span>
        </div>
        <?php if ($article['verifyPoints']): ?>
          <ul class="mt-3 text-sm text-slate-700 leading-relaxed" style="list-style:disc;padding-left:18px">
            <?php foreach ($article['verifyPoints'] as $p): ?><li style="margin-bottom:6px"><?= h($p) ?></li><?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="tk-help" style="margin-top:12px">The agent named no specific points. Check the main claims against the sources.</p>
        <?php endif; ?>
      </div>

      <div class="tk-card tk-card--pad">
        <h2 class="font-outfit font-black text-base text-slate-900">Sources</h2>
        <p class="tk-help" style="margin-top:4px">Each was retrieved by the agent during this run.</p>
        <ol class="mt-3 text-xs leading-relaxed" style="list-style:decimal;padding-left:20px">
          <?php foreach ($article['sources'] as $s): ?>
            <li style="margin-bottom:9px">
              <a href="<?= h($s['url']) ?>" target="_blank" rel="noopener noreferrer nofollow" class="font-bold underline" style="color:#750B25"><?= h($s['publisher']) ?></a>
              <?php if ($s['title'] !== ''): ?><span class="text-slate-600"> · <?= h($s['title']) ?></span><?php endif; ?>
              <?php if ($s['published'] !== ''): ?><span class="text-slate-400"> · <?= h($s['published']) ?></span><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ol>
      </div>
    </aside>
  </div>

<?php else: ?>
  <!-- ── Queue and coverage ─────────────────────────────────────── -->
  <header class="tk-page-head">
    <div>
      <span class="tk-eyebrow">Monitoring</span>
      <h1 class="tk-h1" style="margin-top:12px">Articles</h1>
      <p class="tk-lead">Articles the agent wrote on your regions and indicators. Check them, then publish or reject.</p>
    </div>
  </header>

  <?php agent_nav('articles', $isSuper); ?>
  <div class="flex flex-wrap gap-4 mb-5 text-sm font-bold">
    <?php foreach ($validTabs as $k => $l): ?>
      <a href="/admin/agent/articles?status=<?= h($k) ?>" style="color:<?= $k === $tab ? '#750B25' : '#64748b' ?>"<?= $k === $tab ? ' aria-current="true"' : '' ?>><?= h($l) ?> (<?= (int) $counts[$k] ?>)</a>
    <?php endforeach; ?>
  </div>

  <?php if (!$rows): ?>
    <div class="tk-card tk-empty"><p class="text-sm font-semibold text-slate-600"><?= $tab === 'PROPOSED' ? 'No articles are waiting to be verified.' : 'Nothing here yet.' ?></p></div>
  <?php else: ?>
    <div class="tk-card tk-table-wrap">
      <table class="tk-table">
        <thead><tr><th>Article</th><th>Region</th><th>Indicator</th><th>Confidence</th><th>Sources</th><th>Drafted</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td style="max-width:380px"><a class="font-bold text-slate-900" href="/admin/agent/articles/<?= h(rawurlencode($r['id'])) ?>"><?= h($r['title']) ?></a></td>
              <td><?= h($r['regionName']) ?></td>
              <td><?= h($r['category']) ?></td>
              <td><span class="tk-badge <?= h($confBadge[$r['confidence']] ?? 'tk-badge--muted') ?>"><?= h(ucfirst($r['confidence'])) ?></span></td>
              <td><?= count($r['sources']) ?></td>
              <td class="whitespace-nowrap"><?= h(format_date($r['createdAt'], 'j M Y')) ?></td>
              <td class="whitespace-nowrap"><a class="tk-btn tk-btn--outline tk-btn--sm" href="/admin/agent/articles/<?= h(rawurlencode($r['id'])) ?>/word">Word</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

<?php endif; ?>

</main>
</div>
</div>
</body>
</html>
