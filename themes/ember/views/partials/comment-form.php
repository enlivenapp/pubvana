<div class="card border-0 shadow-sm mt-4" style="border-radius:var(--ember-radius)">
    <div class="card-body p-4">
        <h5 class="mb-3 fw-bold">Leave a Comment</h5>

        <?php if (auth()->loggedIn()): ?>
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
            <?php endif; ?>
            <?php if (setting('App.commentModeration')): ?>
                <div class="alert alert-info small py-2">
                    <i class="fas fa-info-circle me-1"></i> Comments are moderated and will appear after approval.
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= post_url($post->slug) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="parent_id" value="0">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Your Comment</label>
                    <textarea name="body" class="form-control" rows="5" required
                              style="border-radius:var(--ember-radius-sm)"></textarea>
                </div>
                <?php if ($hcaptchaSiteKey = env('hcaptcha.siteKey')): ?>
                    <div class="mb-3">
                        <div class="h-captcha" data-sitekey="<?= esc($hcaptchaSiteKey) ?>"></div>
                        <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
                    </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-warning text-dark fw-semibold">
                    <i class="fas fa-paper-plane me-1"></i> Post Comment
                </button>
            </form>
        <?php else: ?>
            <div class="alert alert-light border text-center py-3">
                <i class="fas fa-lock me-2 text-muted"></i>
                <a href="<?= base_url('login') ?>" class="fw-semibold" style="color:var(--ember-accent-dark)">Log in</a>
                to leave a comment.
            </div>
        <?php endif; ?>
    </div>
</div>
