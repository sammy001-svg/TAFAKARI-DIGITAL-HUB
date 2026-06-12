<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$docs = [];
try {
    $stmt = db()->query(
        "SELECT id, title, description, mediaUrl, country, region, issueCategory, downloadCount, createdAt
         FROM Post WHERE type = 'DOCUMENT' AND status = 'PUBLISHED'
         ORDER BY createdAt DESC LIMIT 50"
    );
    $docs = $stmt->fetchAll();
} catch (Exception $e) { /* ignore */ }

$pageTitle = 'Document Library | Tafakari Digital Hub';
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex flex-col bg-slate-50 font-inter">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="flex-grow max-w-7xl mx-auto px-6 py-16">
  <div class="mb-12">
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-black uppercase tracking-widest mb-4">Knowledge Repository</div>
    <h1 class="font-outfit text-4xl font-black text-slate-900">Document Library</h1>
    <p class="text-slate-500 mt-2">Research reports, policy briefs, and actionable datasets.</p>
  </div>

  <?php if (empty($docs)): ?>
    <div class="text-center py-24">
      <div class="text-5xl mb-4">📁</div>
      <h3 class="font-outfit font-bold text-xl text-slate-900">No documents available yet</h3>
      <p class="text-slate-400 mt-2 text-sm">Research reports and briefs will appear here once published.</p>
    </div>
  <?php else: ?>
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
      <table class="w-full">
        <thead>
          <tr class="border-b border-slate-100">
            <th class="text-left px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Document</th>
            <th class="text-left px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 hidden md:table-cell">Region</th>
            <th class="text-left px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 hidden lg:table-cell">Category</th>
            <th class="text-left px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 hidden lg:table-cell">Date</th>
            <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Download</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          <?php foreach ($docs as $doc):
            $ext = '';
            if (!empty($doc['mediaUrl'])) {
                $parts = pathinfo(parse_url($doc['mediaUrl'], PHP_URL_PATH));
                $ext   = strtoupper($parts['extension'] ?? '');
            }
          ?>
            <tr class="hover:bg-slate-50/50 transition-colors">
              <td class="px-8 py-5">
                <div class="flex items-center gap-3">
                  <?php if ($ext): ?>
                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-slate-100 text-slate-600"><?= h($ext) ?></span>
                  <?php else: ?>
                    <span class="text-xl">📄</span>
                  <?php endif; ?>
                  <div>
                    <p class="text-sm font-bold text-slate-900"><?= h($doc['title']) ?></p>
                    <?php if (!empty($doc['description'])): ?>
                      <p class="text-xs text-slate-400 mt-0.5 line-clamp-2"><?= h($doc['description']) ?></p>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td class="px-8 py-5 hidden md:table-cell">
                <span class="text-xs font-bold text-slate-700"><?= h($doc['country']) ?></span>
                <?php if (!empty($doc['region'])): ?>
                  <span class="text-xs text-slate-400 block"><?= h($doc['region']) ?></span>
                <?php endif; ?>
              </td>
              <td class="px-8 py-5 hidden lg:table-cell">
                <span class="px-2 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-amber-50 text-amber-700">
                  <?= h($doc['issueCategory']) ?>
                </span>
              </td>
              <td class="px-8 py-5 text-xs text-slate-400 hidden lg:table-cell"><?= format_date($doc['createdAt']) ?></td>
              <td class="px-8 py-5 text-right">
                <?php if (!empty($doc['mediaUrl'])): ?>
                  <a href="<?= h($doc['mediaUrl']) ?>" target="_blank" rel="noopener noreferrer"
                     class="btn-primary text-xs px-4 py-2">
                    ⬇ Download
                  </a>
                <?php else: ?>
                  <span class="text-xs text-slate-300">Unavailable</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="px-8 py-4 border-t border-slate-50 text-xs text-slate-400">
        <?= count($docs) ?> document<?= count($docs) !== 1 ? 's' : '' ?> available
      </div>
    </div>
  <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
