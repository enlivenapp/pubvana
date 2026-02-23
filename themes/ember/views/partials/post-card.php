<article class="ember-card">
    <?php if ($post->featured_image): ?>
        <a href="<?= post_url($post->slug) ?>" class="card-image-wrap d-block">
            <img src="<?= esc(base_url($post->featured_image)) ?>"
                 alt="<?= esc($post->title) ?>"
                 loading="lazy">
        </a>
    <?php endif; ?>

    <div class="card-body">
        <h2 class="card-title h5 mb-1">
            <a href="<?= post_url($post->slug) ?>"><?= esc($post->title) ?></a>
        </h2>

        <div class="card-meta">
            <span><i class="fas fa-calendar-alt"></i> <?= date('M j, Y', strtotime($post->published_at ?? $post->created_at)) ?></span>
            <?php if ($post->views): ?>
            <span><i class="fas fa-eye"></i> <?= number_format($post->views) ?></span>
            <?php endif; ?>
        </div>

        <?php if ($post->excerpt): ?>
            <p class="card-excerpt"><?= esc($post->excerpt) ?></p>
        <?php endif; ?>

        <a href="<?= post_url($post->slug) ?>" class="btn-read-more">
            Read more <i class="fas fa-arrow-right fa-xs"></i>
        </a>
    </div>
</article>
