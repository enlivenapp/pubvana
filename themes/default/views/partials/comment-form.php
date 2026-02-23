<div class="card mt-4">
    <div class="card-body">
        <h4 class="card-title">Leave a Comment</h4>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>
        <form action="<?= post_url($post->slug) ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="parent_id" value="">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Name *</label>
                    <input type="text" name="author_name" class="form-control" required value="<?= esc(old('author_name')) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email * <small class="text-muted">(not published)</small></label>
                    <input type="email" name="author_email" class="form-control" required value="<?= esc(old('author_email')) ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Comment *</label>
                <textarea name="content" class="form-control" rows="5" required><?= esc(old('content')) ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Post Comment</button>
            <?php if (setting('App.commentModeration')): ?>
                <small class="text-muted ms-2">Comments are moderated before appearing.</small>
            <?php endif; ?>
        </form>
    </div>
</div>
