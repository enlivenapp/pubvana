<?php
$show_sidebar = active_theme()
    ? db_connect()->table('theme_options')->where('theme_id', active_theme()->id)->where('option_key', 'show_sidebar')->get()->getRowObject()->option_value ?? '1'
    : '1';
ob_start();
?>

<div class="row">
    <div class="<?= $show_sidebar ? 'col-lg-8' : 'col-12' ?>">
        <?= widget_area('before-content') ?>

        <article>
            <?php if ($post->featured_image): ?>
                <img src="<?= esc(base_url($post->featured_image)) ?>" alt="<?= esc($post->title) ?>" class="img-fluid rounded mb-4 w-100" style="max-height:400px;object-fit:cover;">
            <?php endif; ?>

            <h1><?= esc($post->title) ?></h1>

            <div class="text-muted small mb-4">
                <i class="fas fa-calendar-alt"></i> <?= date('F j, Y', strtotime($post->published_at)) ?>
                &nbsp;&middot;&nbsp;
                <i class="fas fa-eye"></i> <?= number_format($post->views) ?> views
            </div>

            <?php if (!empty($paywall)): ?>
                <?php if (!empty($post->excerpt)): ?>
                    <div class="post-content"><?= nl2br(esc($post->excerpt)) ?></div>
                <?php endif; ?>
                <?= view('partials/paywall') ?>
            <?php else: ?>
            <div class="post-content">
                <?= render_content($post) ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($author_profile)): ?>
                <?= theme_view(THEMES_PATH . 'default/views/partials/author-card.php', ['author_profile' => $author_profile, 'post' => $post]) ?>
            <?php endif; ?>

            <!-- Flash Messages -->
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success mt-4"><?= esc(session()->getFlashdata('success')) ?></div>
            <?php endif; ?>
        </article>

        <hr class="my-5">

        <!-- Comments -->
        <div id="comments">
            <h3 class="mb-4">Comments (<?= count($comments) ?>)</h3>

            <?php if (! empty($comments)): ?>
                <?= theme_view(THEMES_PATH . 'default/views/partials/comments-list.php', ['comments' => $comments]) ?>
            <?php endif; ?>

            <?php if (setting('App.commentsEnabled')): ?>
                <?= theme_view(THEMES_PATH . 'default/views/partials/comment-form.php', ['post' => $post]) ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($show_sidebar): ?>
    <div class="col-lg-4">
        <?= theme_view(THEMES_PATH . 'default/views/partials/sidebar.php') ?>
    </div>
    <?php endif; ?>
</div>

<?php
$main_content = ob_get_clean();
echo theme_view(theme_layout(), ['seo' => $seo ?? [], 'primary_nav' => $primary_nav ?? [], 'social_links' => $social_links ?? [], 'main_content' => $main_content, 'json_ld' => $json_ld ?? '', 'preview_mode' => $preview_mode ?? false]);
?>
