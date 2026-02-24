<?php ob_start(); ?>

<div class="row">
    <div class="col-lg-8">
        <h1 class="mb-1">Category: <strong><?= esc($category->name) ?></strong></h1>
        <?php if ($category->description): ?>
            <p class="text-muted mb-4"><?= esc($category->description) ?></p>
        <?php endif; ?>

        <?php foreach ($posts as $post): ?>
            <?= theme_view(THEMES_PATH . 'default/views/partials/post-card.php', ['post' => $post]) ?>
        <?php endforeach; ?>
        <?php if (empty($posts)): ?>
            <p class="text-muted">No posts in this category yet.</p>
        <?php endif; ?>

        <?php if (isset($pager)): ?>
            <?= theme_view(THEMES_PATH . 'default/views/partials/pagination.php', ['pager' => $pager]) ?>
        <?php endif; ?>
    </div>
    <div class="col-lg-4">
        <?= theme_view(THEMES_PATH . 'default/views/partials/sidebar.php') ?>
    </div>
</div>

<?php
$main_content = ob_get_clean();
echo theme_view(theme_layout(), ['seo' => $seo ?? [], 'primary_nav' => $primary_nav ?? [], 'social_links' => $social_links ?? [], 'main_content' => $main_content, 'json_ld' => $json_ld ?? '']);
?>
