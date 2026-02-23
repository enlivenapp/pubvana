<?php
$THEME = THEMES_PATH . 'ember/views/';
$show_sidebar = '1';
$t = active_theme();
if ($t) {
    $row = db_connect()->table('theme_options')
        ->where('theme_id', $t->id)->where('option_key', 'show_sidebar')
        ->get()->getRowObject();
    $show_sidebar = $row->option_value ?? '1';
}
ob_start();
?>

<div class="row g-4">
    <div class="<?= $show_sidebar ? 'col-lg-8' : 'col-12' ?>">
        <form class="mb-4" action="<?= base_url('search') ?>" method="GET">
            <div class="input-group">
                <input class="form-control" type="search" name="q" value="<?= esc($query ?? '') ?>" placeholder="Search…" required>
                <button class="btn btn-warning text-dark fw-semibold" type="submit">
                    <i class="fas fa-search me-1"></i> Search
                </button>
            </div>
        </form>

        <?php if (!empty($query)): ?>
            <h2 class="ember-section-heading">
                Results for "<?= esc($query) ?>"
            </h2>
        <?php endif; ?>

        <?php if (empty($posts)): ?>
            <div class="alert alert-light border">No results found. Try different keywords.</div>
        <?php endif; ?>

        <?php foreach ($posts as $post): ?>
            <?= theme_view($THEME . 'partials/post-card.php', ['post' => $post]) ?>
        <?php endforeach; ?>

        <?php if (isset($pager)): ?>
            <?= theme_view($THEME . 'partials/pagination.php', ['pager' => $pager]) ?>
        <?php endif; ?>
    </div>

    <?php if ($show_sidebar): ?>
    <div class="col-lg-4">
        <div class="ember-sidebar">
            <?= theme_view($THEME . 'partials/sidebar.php') ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
$main_content = ob_get_clean();
echo theme_view($THEME . 'layout.php', [
    'seo'          => $seo ?? [],
    'primary_nav'  => $primary_nav ?? [],
    'social_links' => $social_links ?? [],
    'main_content' => $main_content,
]);
?>
