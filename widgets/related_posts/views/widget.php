<?php if (!empty($posts)): ?>
<div class="widget related-posts-widget">
    <?php if (!empty($title)): ?>
        <h4 class="widget-title"><?= esc($title) ?></h4>
    <?php endif; ?>
    <div class="row row-cols-2 g-2">
        <?php foreach ($posts as $post): ?>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm">
                <?php if (!empty($show_thumbnail) && !empty($post['featured_image'])): ?>
                    <img src="<?= esc(base_url($post['featured_image'])) ?>" class="card-img-top" alt="<?= esc($post['title']) ?>" style="height:80px;object-fit:cover">
                <?php endif; ?>
                <div class="card-body p-2">
                    <a href="<?= post_url($post['slug']) ?>" class="text-decoration-none small fw-semibold"><?= esc($post['title']) ?></a>
                    <?php if (!empty($post['published_at'])): ?>
                        <div class="text-muted" style="font-size:.7rem"><?= date('M j, Y', strtotime($post['published_at'])) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
