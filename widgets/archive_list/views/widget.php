<div class="widget archive-list-widget">
    <?php if (!empty($title)): ?>
        <h4 class="widget-title"><?= esc($title) ?></h4>
    <?php endif; ?>
    <ul class="list-unstyled">
        <?php foreach ($rows as $r): ?>
        <li class="d-flex justify-content-between mb-1">
            <a href="<?= esc($r->url) ?>" class="text-decoration-none"><?= esc($r->label) ?></a>
            <span class="badge bg-secondary"><?= $r->count ?></span>
        </li>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
            <li class="text-muted small">No archived posts.</li>
        <?php endif; ?>
    </ul>
</div>
