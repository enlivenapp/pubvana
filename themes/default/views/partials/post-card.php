<article class="card mb-4 shadow-sm border-0">
    <?php if ($post->featured_image): ?>
        <a href="<?= post_url($post->slug) ?>">
            <img src="<?= esc(base_url($post->featured_image)) ?>" class="card-img-top" alt="<?= esc($post->title) ?>" style="height:220px;object-fit:cover;">
        </a>
    <?php endif; ?>
    <div class="card-body">
        <h2 class="card-title h5">
            <a href="<?= post_url($post->slug) ?>" class="text-decoration-none text-dark"><?= esc($post->title) ?></a>
        </h2>
        <div class="text-muted small mb-2">
            <i class="fas fa-calendar-alt"></i>
            <?= date('F j, Y', strtotime($post->published_at ?? $post->created_at)) ?>
            <?php if ($post->views): ?>
                &nbsp;&middot;&nbsp;<i class="fas fa-eye"></i> <?= number_format($post->views) ?>
            <?php endif; ?>
        </div>
        <?php if ($post->excerpt): ?>
            <p class="card-text text-muted"><?= esc($post->excerpt) ?></p>
        <?php endif; ?>
        <a href="<?= post_url($post->slug) ?>" class="btn btn-sm btn-outline-primary">Read More</a>
    </div>
</article>
