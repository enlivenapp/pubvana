<div class="widget text-block-widget">
    <?php if (!empty($title)): ?>
        <h4 class="widget-title"><?= esc($title) ?></h4>
    <?php endif; ?>
    <div class="widget-content">
        <?= $content ?? '' ?>
    </div>
</div>
