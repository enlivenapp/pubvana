<div class="widget recent-comments-widget">
    <?php if (!empty($title)): ?>
        <h4 class="widget-title"><?= esc($title) ?></h4>
    <?php endif; ?>
    <?php if (!empty($comments)): ?>
    <ul class="list-unstyled">
        <?php foreach ($comments as $c): ?>
        <li class="mb-2">
            <a href="<?= post_url($c->post_slug) ?>#comments" class="text-decoration-none">
                <strong><?= esc($c->author_name) ?></strong>
            </a>
            <span class="text-muted small"> on <?= esc($c->post_title) ?></span>
            <p class="text-muted small mb-0"><?= esc(excerpt($c->content, 60)) ?></p>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php else: ?>
        <p class="text-muted small">No recent comments.</p>
    <?php endif; ?>
</div>
