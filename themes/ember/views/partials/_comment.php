<?php $THEME = THEMES_PATH . 'ember/views/'; ?>
<div class="ember-comment <?= $depth > 0 ? 'ms-4 ps-3 border-start border-warning' : '' ?> mb-3">
    <div class="d-flex align-items-center gap-2 mb-1">
        <img src="https://www.gravatar.com/avatar/<?= md5(strtolower(trim($comment->email ?? ''))) ?>?s=40&d=identicon"
             width="36" height="36" class="rounded-circle" alt="">
        <span class="comment-author"><?= esc($comment->author_name ?? 'Anonymous') ?></span>
        <span class="comment-time ms-auto">
            <i class="fas fa-clock fa-xs me-1"></i><?= date('M j, Y', strtotime($comment->created_at)) ?>
        </span>
    </div>
    <div class="comment-body"><?= nl2br(esc($comment->body)) ?></div>

    <?php if (!empty($comment->children)): ?>
        <?php foreach ($comment->children as $child): ?>
            <?= theme_view($THEME . 'partials/_comment.php', ['comment' => $child, 'depth' => $depth + 1]) ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
