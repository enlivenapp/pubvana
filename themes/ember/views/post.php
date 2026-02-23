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

// Estimate reading time
$wordCount    = str_word_count(strip_tags($post->body ?? ''));
$readingMins  = max(1, (int) ceil($wordCount / 200));

ob_start();
?>

<div class="row g-4">
    <div class="<?= $show_sidebar ? 'col-lg-8' : 'col-12' ?>">

        <?= widget_area('before-content') ?>

        <article class="ember-article">
            <div class="article-header">

                <?php if ($post->featured_image): ?>
                    <div class="featured-image-wrap">
                        <img src="<?= esc(base_url($post->featured_image)) ?>"
                             alt="<?= esc($post->title) ?>">
                    </div>
                <?php endif; ?>

                <h1><?= esc($post->title) ?></h1>

                <div class="article-meta">
                    <span><i class="fas fa-calendar-alt me-1"></i><?= date('F j, Y', strtotime($post->published_at ?? $post->created_at)) ?></span>
                    <?php if ($post->views): ?>
                    <span><i class="fas fa-eye me-1"></i><?= number_format($post->views) ?> views</span>
                    <?php endif; ?>
                    <span class="reading-time-badge">
                        <i class="fas fa-clock"></i> <?= $readingMins ?> min read
                    </span>
                </div>
            </div>

            <div class="article-body">
                <?= render_content($post) ?>
            </div>

            <?php if (!empty($author_profile)): ?>
                <?= theme_view($THEME . 'partials/author-card.php', ['author_profile' => $author_profile, 'post' => $post]) ?>
            <?php endif; ?>

            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success mt-4"><?= esc(session()->getFlashdata('success')) ?></div>
            <?php endif; ?>
        </article>

        <hr class="ember-divider">

        <!-- Comments -->
        <section id="comments">
            <h3 class="ember-section-heading">Comments (<?= count($comments) ?>)</h3>

            <?php if (! empty($comments)): ?>
                <?= theme_view($THEME . 'partials/comments-list.php', ['comments' => $comments]) ?>
            <?php endif; ?>

            <?php if (setting('App.commentsEnabled')): ?>
                <?= theme_view($THEME . 'partials/comment-form.php', ['post' => $post]) ?>
            <?php endif; ?>
        </section>
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
