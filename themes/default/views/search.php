<?php ob_start(); ?>

<div class="row">
    <div class="col-lg-8">
        <h1 class="mb-2">Search Results</h1>
        <?php if ($query): ?>
            <p class="text-muted mb-4">Showing results for: <strong><?= esc($query) ?></strong></p>
        <?php endif; ?>

        <form action="<?= base_url('search') ?>" method="GET" class="mb-4">
            <div class="input-group">
                <input type="text" class="form-control" name="q" value="<?= esc($query) ?>" placeholder="Search posts…">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
            </div>
        </form>

        <?php foreach ($posts as $post): ?>
            <?= theme_view(THEMES_PATH . 'default/views/partials/post-card.php', ['post' => $post]) ?>
        <?php endforeach; ?>
        <?php if (empty($posts) && $query): ?>
            <p class="text-muted">No posts found for "<?= esc($query) ?>".</p>
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
echo theme_view(theme_layout(), ['seo' => $seo ?? [], 'primary_nav' => $primary_nav ?? [], 'social_links' => $social_links ?? [], 'main_content' => $main_content]);
?>
