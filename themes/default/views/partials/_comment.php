<div class="comment mb-3 <?= $depth > 0 ? 'ms-4 ps-3 border-start border-2' : '' ?>">
    <div class="d-flex">
        <div class="flex-shrink-0 me-3">
            <img src="https://www.gravatar.com/avatar/<?= md5(strtolower($comment->author_email)) ?>?s=48&d=mp" class="rounded-circle" width="48" height="48" alt="">
        </div>
        <div class="flex-grow-1">
            <div class="fw-bold"><?= esc($comment->author_name) ?></div>
            <div class="text-muted small"><?= date('F j, Y \a\t g:i a', strtotime($comment->created_at)) ?></div>
            <div class="mt-2"><?= nl2br(esc($comment->content)) ?></div>
        </div>
    </div>
    <?php if (! empty($comment->children)): ?>
        <?php foreach ($comment->children as $child): ?>
            <?= theme_view(THEMES_PATH . 'default/views/partials/_comment.php', ['comment' => $child, 'depth' => $depth + 1]) ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
