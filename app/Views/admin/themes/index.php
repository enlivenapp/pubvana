<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<h1 class="h3 mb-4 text-gray-800">Themes</h1>

<div class="row">
<?php foreach ($themes as $theme): ?>
    <?php
    $infoFile = THEMES_PATH . $theme->folder . '/theme_info.php';
    $info = is_file($infoFile) ? require $infoFile : [];
    ?>
    <div class="col-md-4 mb-4">
        <div class="card shadow h-100 <?= $theme->is_active ? 'border-primary' : '' ?>">
            <?php if (!empty($info['screenshot'])): ?>
                <img src="<?= theme_url($info['screenshot']) ?>" class="card-img-top" style="height:180px;object-fit:cover" alt="">
            <?php else: ?>
                <div class="card-img-top bg-gradient-primary d-flex align-items-center justify-content-center" style="height:180px">
                    <i class="fas fa-palette fa-3x text-white-50"></i>
                </div>
            <?php endif; ?>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <h5 class="card-title"><?= esc($theme->name) ?></h5>
                    <?php if ($theme->is_active): ?>
                        <span class="badge badge-primary">Active</span>
                    <?php endif; ?>
                </div>
                <p class="card-text text-muted small"><?= esc($info['description'] ?? '') ?></p>
                <p class="text-muted" style="font-size:0.8rem">By <?= esc($info['author'] ?? 'Unknown') ?> &middot; v<?= esc($theme->version ?? '?') ?></p>
            </div>
            <div class="card-footer bg-white d-flex gap-2">
                <?php if (! $theme->is_active): ?>
                <form method="POST" action="<?= base_url('admin/themes/' . $theme->id . '/activate') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-primary">Activate</button>
                </form>
                <?php endif; ?>
                <?php if (!empty($info['options'])): ?>
                <a href="<?= base_url('admin/themes/' . $theme->id . '/options') ?>" class="btn btn-sm btn-outline-secondary">Options</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<?php if (empty($themes)): ?>
    <div class="col-12"><p class="text-muted text-center py-4">No themes installed. Visit the Marketplace to get themes.</p></div>
<?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
