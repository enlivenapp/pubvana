<div class="card mt-4">
    <div class="card-body">
        <h4 class="card-title">Leave a Comment</h4>
        <?php if (auth()->loggedIn()): ?>
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
            <?php endif; ?>
            <form action="<?= post_url($post->slug) ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="parent_id" value="">
                <div class="mb-3">
                    <label class="form-label">Comment *</label>
                    <textarea name="content" class="form-control" rows="5" required><?= esc(old('content')) ?></textarea>
                </div>
                <?php if (getenv('HCAPTCHA_SITE_KEY')): ?>
                    <div class="h-captcha mb-3" data-sitekey="<?= esc(getenv('HCAPTCHA_SITE_KEY')) ?>"></div>
                    <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">Post Comment</button>
                <?php if (setting('App.commentModeration')): ?>
                    <small class="text-muted ms-2">Comments are moderated before appearing.</small>
                <?php endif; ?>
            </form>
        <?php else: ?>
            <div class="alert alert-info">
                <a href="<?= base_url('login') ?>">Log in</a> to leave a comment.
            </div>
        <?php endif; ?>
    </div>
</div>
