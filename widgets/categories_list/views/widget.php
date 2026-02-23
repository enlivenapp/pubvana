<div class="widget categories-widget">
    <?php if (!empty($title)): ?>
        <h4 class="widget-title"><?= esc($title) ?></h4>
    <?php endif; ?>
    <ul class="list-unstyled">
        <?php foreach ($categories as $cat): ?>
        <li class="d-flex justify-content-between align-items-center mb-1">
            <a href="<?= category_url($cat->slug) ?>" class="text-decoration-none"><?= esc($cat->name) ?></a>
            <?php if (!empty($show_count)): ?>
                <span class="badge bg-secondary"><?= $cat->post_count ?></span>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
        <?php if (empty($categories)): ?>
            <li class="text-muted small">No categories yet.</li>
        <?php endif; ?>
    </ul>
</div>
