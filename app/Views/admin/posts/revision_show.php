<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Revision — <?= esc($revision->title) ?></h1>
    <div>
        <a href="<?= base_url('admin/posts/' . $post->id . '/revisions') ?>" class="btn btn-sm btn-outline-secondary">All Revisions</a>
        <form method="POST" action="<?= base_url('admin/posts/revisions/' . $revision->id . '/restore') ?>" class="d-inline"
              onsubmit="return confirm('Restore this revision?')">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-warning ml-1">Restore This Revision</button>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Content</h6></div>
            <div class="card-body">
                <?php if ($revision->content_type === 'markdown'): ?>
                    <pre class="border p-3 rounded bg-light" style="white-space:pre-wrap"><?= esc($revision->content) ?></pre>
                <?php else: ?>
                    <div class="border p-3 rounded"><?= $revision->content ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($revision->excerpt): ?>
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Excerpt</h6></div>
            <div class="card-body"><p class="mb-0"><?= esc($revision->excerpt) ?></p></div>
        </div>
        <?php endif; ?>
    </div>
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Details</h6></div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt>Saved</dt>
                    <dd><?= esc($revision->created_at) ?></dd>
                    <dt>Author</dt>
                    <dd><?= esc($revision->author_name ?? '—') ?></dd>
                    <dt>Status</dt>
                    <dd><span class="badge badge-<?= $revision->status === 'published' ? 'success' : 'secondary' ?>"><?= esc($revision->status) ?></span></dd>
                    <dt>Content type</dt>
                    <dd><?= esc($revision->content_type) ?></dd>
                </dl>
            </div>
        </div>
        <?php if ($revision->meta_title || $revision->meta_description): ?>
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">SEO</h6></div>
            <div class="card-body">
                <?php if ($revision->meta_title): ?>
                    <strong>Meta Title</strong>
                    <p><?= esc($revision->meta_title) ?></p>
                <?php endif; ?>
                <?php if ($revision->meta_description): ?>
                    <strong>Meta Description</strong>
                    <p class="mb-0"><?= esc($revision->meta_description) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
