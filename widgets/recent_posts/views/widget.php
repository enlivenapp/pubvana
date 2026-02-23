<div class="widget recent-posts-widget">
    <?php if (!empty($title)): ?>
        <h4 class="widget-title"><?= esc($title) ?></h4>
    <?php endif; ?>
    <?php if (!empty($posts)): ?>
        <ul class="list-unstyled">
        <?php foreach ($posts as $post): ?>
            <li class="mb-2">
                <a href="<?= post_url($post->slug) ?>" class="text-decoration-none"><?= esc($post->title) ?></a>
                <?php if (!empty($show_date) && $post->published_at): ?>
                    <br><small class="text-muted"><?= date('M j, Y', strtotime($post->published_at)) ?></small>
                <?php endif; ?>
                <?php if (!empty($show_excerpt) && $post->excerpt): ?>
                    <p class="small text-muted mb-0"><?= esc(excerpt($post->excerpt, 80)) ?></p>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="text-muted small">No recent posts.</p>
    <?php endif; ?>
</div>
