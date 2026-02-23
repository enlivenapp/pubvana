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
        <h2 class="ember-section-heading">
            <i class="fas fa-tag me-2" style="color:var(--ember-accent)"></i><?= esc($tag->name) ?>
        </h2>

        <?php if (empty($posts)): ?>
            <div class="alert alert-light border">No posts tagged "<?= esc($tag->name) ?>" yet.</div>
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
