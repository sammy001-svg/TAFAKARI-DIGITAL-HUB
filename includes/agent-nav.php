<?php
/** The one navigation shared by every Tafakari Agent page. Needs agent-updates.php, agent-articles.php and agent-videos.php. */
function agent_nav(string $active, bool $isSuper): void {
    require_once __DIR__ . '/agent-grades.php';
    $count = fn(int $n) => $n > 0 ? " ({$n})" : '';
    $links = [
        'updates'  => ['Country updates' . $count(agent_update_pending_count()), '/admin/agent/updates'],
        'findings' => ['Findings' . $count(agent_pending_count()), '/admin/agent'],
        'grades'   => ['Document grades' . $count(agent_grade_pending_count()), '/admin/agent/grades'],
        'articles' => ['Articles' . $count(agent_article_pending_count()), '/admin/agent/articles'],
        'videos'   => ['Videos' . $count(agent_video_pending_count()), '/admin/agent/videos'],
    ];
    if ($isSuper) {
        $links['runs']     = ['Activity', '/admin/agent?tab=runs'];
        $links['settings'] = ['Settings', '/admin/agent?tab=settings'];
    }
    require_once __DIR__ . '/agent-health.php';
    $banner = $isSuper ? agent_health_banner() : null;
    ?>
    <nav class="tk-segment" aria-label="Tafakari Agent" style="margin-bottom:20px;flex-wrap:wrap">
      <?php foreach ($links as $key => [$label, $href]): ?>
        <a href="<?= h($href) ?>" class="<?= $key === $active ? 'is-active' : '' ?>"<?= $key === $active ? ' aria-current="page"' : '' ?>><?= h($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <?php if ($banner): ?>
      <div class="tk-alert <?= $banner['level'] === 'danger' ? 'tk-alert--danger' : 'tk-alert--warn' ?>" role="status" style="margin-bottom:20px">
        <span><?= h($banner['text']) ?> <a href="/admin/agent?tab=settings" class="font-bold underline">Settings</a> · <a href="/admin/agent?tab=runs" class="font-bold underline">Activity</a></span>
      </div>
    <?php endif; ?>
    <?php
}
