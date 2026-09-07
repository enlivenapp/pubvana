<?php
/**
 * Create page - admin form.
 *
 * @var string $pageTitle
 * @var string $adminBase
 */
?>

<div class="d-flex align-items-center justify-content-end mb-4">
    <a href="<?= $adminBase ?>" class="btn btn-outline-secondary">Back</a>
</div>

<form method="POST" action="<?= $adminBase ?>/store">
    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="title">Title</label>
                        <input type="text" name="title" id="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="content">Content</label>
                        <textarea name="content" id="content" class="form-control" rows="15"></textarea>
                    </div>
                </div>
            </div>

            <?php foreach (\Flight::app()->adext()->get('content.edit.panel', 'default', ['content_type' => 'page', 'content_id' => 0]) as $panel): ?>
                <?= $panel['output'] ?? '' ?>
            <?php endforeach; ?>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Publish</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="status">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-check">
                            <input type="checkbox" name="allow_comments" value="1" class="form-check-input">
                            <span class="form-check-label">Allow Comments</span>
                        </label>
                        <div class="text-secondary small fst-italic">Requires Global Comments to be enabled in Comment Settings.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Create Page</button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php if (!empty($joditHtml)): ?>
    <?= $joditHtml ?>
<?php endif; ?>
