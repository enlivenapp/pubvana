<?php
/**
 * Author Bio Card partial
 * Variables: $author_profile (object|null), $post (object)
 */
if (empty($author_profile)) {
    return;
}

$display_name = $author_profile->display_name ?? ($author_profile->username ?? 'Unknown Author');
$bio          = $author_profile->bio ?? '';
$avatar       = $author_profile->avatar ?? null;
$website      = $author_profile->website ?? null;
$twitter      = $author_profile->twitter ?? null;
$facebook     = $author_profile->facebook ?? null;
$linkedin     = $author_profile->linkedin ?? null;

if (empty($bio) && empty($avatar) && empty($twitter) && empty($facebook) && empty($linkedin)) {
    return;
}

$avatarUrl = $avatar
    ? base_url('writable/' . $avatar)
    : 'https://www.gravatar.com/avatar/' . md5(strtolower($author_profile->email ?? '')) . '?s=80&d=mp';
?>
<div class="card border-0 bg-light rounded-lg p-4 my-5 d-flex flex-row align-items-start">
    <img src="<?= esc($avatarUrl) ?>"
         alt="<?= esc($display_name) ?>"
         class="rounded-circle mr-4 flex-shrink-0"
         width="80" height="80"
         style="object-fit:cover">
    <div>
        <div class="d-flex align-items-center mb-1">
            <h6 class="font-weight-bold mb-0 mr-2"><?= esc($display_name) ?></h6>
            <?php if ($website): ?>
                <a href="<?= esc($website) ?>" class="text-muted small mr-2" target="_blank" rel="noopener">
                    <i class="fas fa-globe"></i>
                </a>
            <?php endif; ?>
            <?php if ($twitter): ?>
                <a href="https://twitter.com/<?= esc($twitter) ?>" class="text-info small mr-2" target="_blank" rel="noopener">
                    <i class="fab fa-twitter"></i>
                </a>
            <?php endif; ?>
            <?php if ($facebook): ?>
                <a href="<?= esc(strpos($facebook, 'http') === 0 ? $facebook : 'https://facebook.com/' . $facebook) ?>"
                   class="text-primary small mr-2" target="_blank" rel="noopener">
                    <i class="fab fa-facebook"></i>
                </a>
            <?php endif; ?>
            <?php if ($linkedin): ?>
                <a href="<?= esc(strpos($linkedin, 'http') === 0 ? $linkedin : 'https://linkedin.com/in/' . $linkedin) ?>"
                   class="text-primary small" target="_blank" rel="noopener">
                    <i class="fab fa-linkedin"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php if ($bio): ?>
            <p class="text-muted small mb-0"><?= nl2br(esc($bio)) ?></p>
        <?php endif; ?>
    </div>
</div>
