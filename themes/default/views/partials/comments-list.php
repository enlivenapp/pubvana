<div class="comments-list mb-4">
    <?php foreach ($comments as $comment): ?>
        <?= theme_view(THEMES_PATH . 'default/views/partials/_comment.php', ['comment' => $comment, 'depth' => 0]) ?>
    <?php endforeach; ?>
</div>
