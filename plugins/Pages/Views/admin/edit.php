<?php
/**
 * Edit page - admin form.
 *
 * @var string $pageTitle
 * @var \Pubvana\Plugins\Pages\Models\Page $editPage
 * @var string $adminBase
 * @var string $publicBase
 */
?>

<div class="d-flex align-items-center justify-content-end mb-4">
    <a href="<?= $adminBase ?>" class="btn btn-outline-secondary">Back</a>
    <a href="<?= $adminBase ?>/<?= (int) $editPage->id ?>/revisions" class="btn btn-outline-secondary ms-2">Revisions</a>
    <?php if ($editPage->status === 'published'): ?>
        <a href="<?= $publicBase ?>/<?= htmlspecialchars($editPage->slug) ?>" class="btn btn-outline-success ms-2" target="_blank">View</a>
    <?php endif; ?>
</div>

<form method="POST" action="<?= $adminBase ?>/<?= (int) $editPage->id ?>/update">
    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="title">Title</label>
                        <input type="text" name="title" id="title" class="form-control"
                               value="<?= htmlspecialchars($editPage->title ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="content">Content</label>
                        <textarea name="content" id="content" class="form-control" rows="15"><?= htmlspecialchars($editPage->content ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <?php foreach (\Flight::app()->adext()->get('content.edit.panel', 'default', ['content_type' => 'page', 'content_id' => (int) $editPage->id]) as $panel): ?>
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
                            <option value="draft" <?= $editPage->status === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="published" <?= $editPage->status === 'published' ? 'selected' : '' ?>>Published</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-check">
                            <input type="checkbox" name="allow_comments" value="1" class="form-check-input"
                                   <?= (int) $editPage->allow_comments ? 'checked' : '' ?>>
                            <span class="form-check-label">Allow Comments</span>
                        </label>
                        <div class="text-secondary small fst-italic">Requires Global Comments to be enabled in Comment Settings.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Update Page</button>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Info</h3>
                </div>
                <div class="card-body">
                    <div class="text-secondary small mb-2">
                        Slug: <code><?= htmlspecialchars($editPage->slug ?? '') ?></code>
                    </div>
                    <div class="text-secondary small mb-2">
                        Created: <?= htmlspecialchars($editPage->created_at ?? 'Unknown') ?>
                    </div>
                    <?php if (!empty($editPage->updated_at)): ?>
                        <div class="text-secondary small">
                            Updated: <?= htmlspecialchars($editPage->updated_at) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<?php if (!empty($joditHtml)): ?>
    <?= $joditHtml ?>
<?php endif; ?>
