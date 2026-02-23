<div class="widget tag-cloud-widget">
    <?php if (!empty($title)): ?>
        <h4 class="widget-title"><?= esc($title) ?></h4>
    <?php endif; ?>
    <div class="tag-cloud">
        <?php foreach ($tags as $tag): ?>
            <a href="<?= tag_url($tag->slug) ?>"><?= esc($tag->name) ?></a>
        <?php endforeach; ?>
        <?php if (empty($tags)): ?>
            <p class="text-muted small">No tags yet.</p>
        <?php endif; ?>
    </div>
</div>
