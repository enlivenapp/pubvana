<?php
$show_sidebar = active_theme()
    ? db_connect()->table('theme_options')->where('theme_id', active_theme()->id)->where('option_key', 'show_sidebar')->get()->getRowObject()->option_value ?? '1'
    : '1';
ob_start();
?>

<div class="row">
    <!-- Posts -->
    <div class="<?= $show_sidebar ? 'col-lg-8' : 'col-12' ?>">
        <?= widget_area('before-content') ?>

        <h1 class="h2 mb-4"><?= esc(site_name()) ?> &mdash; <small class="text-muted"><?= esc(site_tagline()) ?></small></h1>

        <?php if (empty($posts)): ?>
            <div class="alert alert-info">No posts yet. Check back soon!</div>
        <?php endif; ?>

        <?php foreach ($posts as $post): ?>
            <?= theme_view(THEMES_PATH . 'default/views/partials/post-card.php', ['post' => $post]) ?>
        <?php endforeach; ?>

        <?php if (isset($pager)): ?>
            <?= theme_view(THEMES_PATH . 'default/views/partials/pagination.php', ['pager' => $pager]) ?>
        <?php endif; ?>
    </div>

    <?php if ($show_sidebar): ?>
    <!-- Sidebar -->
    <div class="col-lg-4">
        <?= theme_view(THEMES_PATH . 'default/views/partials/sidebar.php') ?>
    </div>
    <?php endif; ?>
</div>

<?php
$main_content = ob_get_clean();
echo theme_view(THEMES_PATH . 'default/views/layout.php', ['seo' => $seo ?? [], 'primary_nav' => $primary_nav ?? [], 'social_links' => $social_links ?? [], 'main_content' => $main_content]);
?>
