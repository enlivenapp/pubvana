<div class="widget social-links-widget">
    <?php if (!empty($title)): ?>
        <h4 class="widget-title"><?= esc($title) ?></h4>
    <?php endif; ?>
    <div class="d-flex flex-wrap gap-2">
        <?php foreach ($links as $link): ?>
            <a href="<?= esc($link->url) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                <i class="<?= esc($link->icon) ?>"></i>
                <?php if (($style ?? 'icons') === 'icons+text'): ?>
                    <?= esc($link->platform) ?>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
