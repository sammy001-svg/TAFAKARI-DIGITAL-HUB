<?php
/** The one navigation shared by every Tafakari Agent page. Needs agent-articles.php and agent-videos.php. */
function agent_nav(string $active, bool $isSuper): void {
    $count = fn(int $n) => $n > 0 ? " ({$n})" : '';
    $links = [
        'findings' => ['Findings' . $count(agent_pending_count()), '/admin/agent'],
        'articles' => ['Articles' . $count(agent_article_pending_count()), '/admin/agent/articles'],
        'videos'   => ['Videos' . $count(agent_video_pending_count()), '/admin/agent/videos'],
    ];
    if ($isSuper) {
        $links['runs']     = ['Activity', '/admin/agent?tab=runs'];
        $links['settings'] = ['Settings', '/admin/agent?tab=settings'];
    }
    ?>
    <nav class="tk-segment" aria-label="Tafakari Agent" style="margin-bottom:20px;flex-wrap:wrap">
      <?php foreach ($links as $key => [$label, $href]): ?>
        <a href="<?= h($href) ?>" class="<?= $key === $active ? 'is-active' : '' ?>"<?= $key === $active ? ' aria-current="page"' : '' ?>><?= h($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <?php
}
