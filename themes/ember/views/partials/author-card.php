<?php if (!empty($author_profile)): ?>
<div class="ember-author-card">
    <?php
    $avatarUrl = $author_profile->avatar_url ?? null;
    if (! $avatarUrl) {
        $email     = $post->author_email ?? '';
        $hash      = md5(strtolower(trim($email)));
        $avatarUrl = "https://www.gravatar.com/avatar/{$hash}?s=128&d=identicon";
    }
    ?>
    <img src="<?= esc($avatarUrl) ?>" alt="<?= esc($author_profile->display_name ?? '') ?>" class="author-avatar">

    <div>
        <div class="author-name"><?= esc($author_profile->display_name ?? '') ?></div>
        <?php if (!empty($author_profile->bio)): ?>
            <p class="author-bio"><?= nl2br(esc($author_profile->bio)) ?></p>
        <?php endif; ?>
        <div class="author-social">
            <?php if (!empty($author_profile->website)): ?>
                <a href="<?= esc($author_profile->website) ?>" target="_blank" rel="noopener" title="Website"><i class="fas fa-globe"></i></a>
            <?php endif; ?>
            <?php if (!empty($author_profile->twitter)): ?>
                <a href="https://twitter.com/<?= esc($author_profile->twitter) ?>" target="_blank" rel="noopener" title="Twitter/X"><i class="fab fa-x-twitter"></i></a>
            <?php endif; ?>
            <?php if (!empty($author_profile->facebook)): ?>
                <a href="<?= esc($author_profile->facebook) ?>" target="_blank" rel="noopener" title="Facebook"><i class="fab fa-facebook-f"></i></a>
            <?php endif; ?>
            <?php if (!empty($author_profile->linkedin)): ?>
                <a href="<?= esc($author_profile->linkedin) ?>" target="_blank" rel="noopener" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>
