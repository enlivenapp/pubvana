<?php ob_start(); ?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <article>
            <h1 class="mb-4"><?= esc($page->title) ?></h1>
            <div class="page-content">
                <?= render_content($page) ?>
            </div>

            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success mt-4"><?= esc(session()->getFlashdata('success')) ?></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger mt-4"><?= esc(session()->getFlashdata('error')) ?></div>
            <?php endif; ?>
        </article>
    </div>
</div>

<?php
$main_content = ob_get_clean();
echo theme_view(THEMES_PATH . 'default/views/layout.php', ['seo' => $seo ?? [], 'primary_nav' => $primary_nav ?? [], 'social_links' => $social_links ?? [], 'main_content' => $main_content]);
?>
